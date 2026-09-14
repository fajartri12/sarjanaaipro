<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\ThesisSection;
use App\Models\ThesisSectionVersion;
use App\Models\User;
use App\Support\AiText;
use App\Support\SafeHtml;
use Illuminate\Support\Facades\DB;

class DraftService
{
    public function __construct(private AiService $ai) {}

    /** Tulis satu bagian dari nol. */
    public function generate(Project $project, ThesisSection $section): ThesisSection
    {
        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($project->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::draftSection([
                    'title' => $project->title ?? $project->name,
                    'study_program' => $project->study_program,
                    'method' => $project->method,
                    'degree_level' => $project->degree_level,
                    'section' => "{$section->chapter} {$section->key} {$section->title}",
                ])],
            ],
            feature: 'draft',
            user: $project->user,
            project: $project,
        );

        return DB::transaction(function () use ($project, $section, $result) {
            $this->snapshot($section, $project->user, 'ai_generate');
            $section->content = $this->toHtml($result->text);
            $section->recalculate();
            $project->syncProgress();

            return $section;
        });
    }

    /**
     * Jalankan salah satu aksi editor: improve, expand, summarize, dan sejenisnya.
     *
     * @return array{section: ThesisSection, explanation: ?string}
     */
    public function applyAction(Project $project, ThesisSection $section, string $action): array
    {
        $allowed = ['improve', 'expand', 'summarize', 'formalize', 'continue', 'explain'];

        if (! in_array($action, $allowed, true)) {
            abort(422, 'Aksi tidak dikenal.');
        }

        $plain = trim(strip_tags((string) $section->content));

        if ($plain === '') {
            abort(422, 'Bagian ini masih kosong. Jalankan Generate dulu.');
        }

        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($project->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::draftAction($action, "{$section->chapter} {$section->title}", $plain, $project->degree_level)],
            ],
            feature: 'draft',
            user: $project->user,
            project: $project,
        );

        // "explain" hanya menjelaskan isi, jadi naskah tidak ditimpa
        if ($action === 'explain') {
            return ['section' => $section, 'explanation' => $result->text];
        }

        return DB::transaction(function () use ($project, $section, $result) {
            $this->snapshot($section, $project->user, 'ai_action');
            $section->content = $this->toHtml($result->text);
            $section->recalculate();
            $project->syncProgress();

            return ['section' => $section, 'explanation' => null];
        });
    }

    /** Simpan hasil ketikan user lalu sinkronkan progress project. */
    public function save(ThesisSection $section, string $content, ?User $user = null, string $reason = 'manual'): ThesisSection
    {
        // Snapshot isi lama dulu supaya bisa dikembalikan.
        if ($user) {
            $this->snapshot($section, $user, $reason);
        }

        // Ketikan user masuk sebagai HTML mentah; bersihkan sebelum disimpan.
        $section->content = SafeHtml::clean($content);
        $section->recalculate();
        $section->project->syncProgress();

        return $section;
    }

    /**
     * Simpan snapshot isi bagian sebelum ditimpa.
     *
     * Versi hanya dibuat kalau isinya benar-benar berubah, supaya riwayat tidak
     * penuh dengan entri identik. Snapshot lama dipangkas agar tidak menggelembung.
     */
    public function snapshot(ThesisSection $section, User $user, string $reason = 'manual', int $keep = 25): ?ThesisSectionVersion
    {
        $current = (string) $section->content;

        if ($current === '') {
            return null;
        }

        // Jangan simpan kalau isi terakhir yang tercatat sama persis.
        $last = $section->versions()->first();
        if ($last && $last->content === $current) {
            return null;
        }

        $version = $section->versions()->create([
            'user_id' => $user->id,
            'content' => $current,
            'word_count' => $section->word_count,
            'reason' => $reason,
        ]);

        // ponytail: simpan 25 versi terakhir, cukup untuk undo. Ganti ke
        // penyimpanan diff kalau riwayat panjang jadi kebutuhan nyata.
        // Diambil lewat pluck + slice, bukan offset, karena SQLite butuh LIMIT
        // saat ada OFFSET dan tabelnya kecil (dipangkas tiap kali).
        $stale = $section->versions()->latest('id')->pluck('id')->slice($keep);
        if ($stale->isNotEmpty()) {
            ThesisSectionVersion::whereIn('id', $stale)->delete();
        }

        return $version;
    }

    /** Kembalikan isi bagian ke sebuah versi lama (versi saat ini ikut disnapshot dulu). */
    public function restore(ThesisSection $section, ThesisSectionVersion $version, User $user): ThesisSection
    {
        abort_unless($version->section_id === $section->id, 404);

        $this->snapshot($section, $user, 'before_restore');

        $section->content = $version->content;
        $section->recalculate();
        $section->project->syncProgress();

        return $section;
    }

    /** Teks AI diubah jadi paragraf HTML sederhana agar aman di editor. */
    private function toHtml(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $html = '';

        foreach ($lines as $line) {
            // Penekanan markdown dibuang dulu: editor menyimpan HTML, dan
            // `**tebal**` akan tampil apa adanya di dalam <p>.
            $line = trim((string) AiText::plain($line));

            if ($line === '') {
                continue;
            }

            // Daftar berpoin tetap didukung karena naskah memang sering memakainya.
            if (preg_match('/^(?:[-*+]|\d+\.)\s+(.+)$/', $line, $m)) {
                $html .= '<li>'.e($m[1]).'</li>';
            } else {
                $html .= '<p>'.e($line).'</p>';
            }
        }

        // bungkus <li> yang berdampingan jadi satu <ul>
        $html = preg_replace_callback('#(?:<li>.*?</li>)+#s', fn ($m) => '<ul>'.$m[0].'</ul>', $html);

        // Sanitasi akhir untuk memastikan tidak ada tag berbahaya.
        return SafeHtml::clean($html);
    }
}
