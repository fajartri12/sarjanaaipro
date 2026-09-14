<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\ThesisSection;
use App\Support\AiText;

class ReviewerService
{
    public function __construct(private AiService $ai) {}

    /**
     * Nilai seluruh draft project.
     *
     * @return array{scores: array<string, int>, summary: string, recommendations: array<int, string>}
     */
    public function review(Project $project): array
    {
        $sections = $project->sections()
            ->whereNotNull('content')
            ->get()
            ->mapWithKeys(fn ($s) => ["{$s->chapter} {$s->key} {$s->title}" => $s->content])
            ->all();

        if (empty($sections)) {
            abort(422, 'Draft masih kosong. Tulis minimal satu bagian sebelum direview.');
        }

        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($project->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::reviewDraft($sections, $project->degree_level)],
            ],
            feature: 'reviewer',
            user: $project->user,
            project: $project,
            options: ['json' => true],
        );

        $data = $result->json();

        return $this->shape($data);
    }

    /**
     * Review satu bagian saja. Prompt-nya sama, cuma isinya satu section
     * supaya token yang dibakar jauh lebih sedikit.
     */
    public function reviewSection(Project $project, ThesisSection $section): array
    {
        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($project->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::reviewDraft([
                    "{$section->chapter} {$section->key} {$section->title}" => $section->content,
                ], $project->degree_level)],
            ],
            feature: 'reviewer',
            user: $project->user,
            project: $project,
            options: ['json' => true],
        );

        $data = $result->json();

        return $this->shape($data);
    }

    /**
     * Nilai satu paragraf yang sedang ditulis mahasiswa.
     *
     * Dipanggil dari editor saat mahasiswa menyorot paragraf, jadi hasilnya
     * sengaja pendek: satu masalah, satu saran. Tidak disimpan ke tabel
     * `reviews` karena ini umpan balik sementara, bukan penilaian resmi —
     * riwayat review tetap bersih.
     *
     * @return array{score: int, issue: string, suggestion: string, reason: string}
     */
    public function reviewParagraph(Project $project, string $paragraph): array
    {
        $paragraph = trim($paragraph);

        if ($paragraph === '') {
            abort(422, 'Paragraf masih kosong.');
        }

        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($project->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::reviewParagraph($paragraph, $project->degree_level)],
            ],
            feature: 'reviewer',
            user: $project->user,
            project: $project,
            options: ['json' => true],
        );

        $data = $result->json();

        return [
            'score' => (int) ($data['score'] ?? 0),
            'issue' => AiText::plain($data['issue'] ?? '') ?? '',
            'suggestion' => AiText::plain($data['suggestion'] ?? '') ?? '',
            'reason' => AiText::plain($data['reason'] ?? '') ?? '',
        ];
    }

    /**
     * Periksa konsistensi antara judul, rumusan masalah, tujuan, dan metode.
     *
     * Rumusan masalah dan tujuan dibaca dari isi bagian BAB I supaya mahasiswa
     * tidak perlu mengisi ulang di form terpisah.
     *
     * @return array{consistent: bool, issues: array<int, string>, suggestions: array<int, string>, summary: string}
     */
    public function checkConsistency(Project $project): array
    {
        $title = $project->titles()->where('is_selected', true)->value('title')
            ?? $project->title;

        if (! $title) {
            abort(422, 'Isi atau pilih judul penelitian terlebih dahulu.');
        }

        $sections = $project->sections()->whereNotNull('content')->get();

        if ($sections->isEmpty()) {
            abort(422, 'Draft masih kosong. Tulis minimal satu bagian sebelum diperiksa.');
        }

        $problem = $this->findSection($sections, ['rumusan', 'masalah', 'latar']);
        $objectives = $this->findSection($sections, ['tujuan']);
        $method = $this->findSection($sections, ['metode', 'metodologi']);

        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($project->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::consistencyCheck(
                    $title,
                    $problem ?? $project->description,
                    $objectives,
                    $method ?? $project->method,
                    $project->degree_level,
                )],
            ],
            feature: 'reviewer',
            user: $project->user,
            project: $project,
            options: ['json' => true],
        );

        $data = $result->json();

        return [
            'consistent' => (bool) ($data['consistent'] ?? false),
            'issues' => array_values(AiText::cleanArray($data['issues'] ?? [])),
            'suggestions' => array_values(AiText::cleanArray($data['suggestions'] ?? [])),
            'summary' => AiText::plain($data['summary'] ?? '') ?? '',
        ];
    }

    /**
     * Ambil isi bagian pertama yang judulnya mengandung salah satu kata kunci.
     *
     * @param  \Illuminate\Support\Collection<int, ThesisSection>  $sections
     * @param  array<int, string>  $keywords
     */
    private function findSection($sections, array $keywords): ?string
    {
        foreach ($sections as $section) {
            $haystack = mb_strtolower($section->title.' '.$section->key);

            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    return mb_substr(strip_tags((string) $section->content), 0, 1500);
                }
            }
        }

        return null;
    }

    /**
     * Bentuk akhir hasil review. Teks dibersihkan dari markdown karena
     * summary dan saran tampil apa adanya di halaman reviewer.
     */
    private function shape(array $data): array
    {
        return [
            'scores' => array_map('intval', $data['scores'] ?? []),
            'summary' => AiText::plain($data['summary'] ?? ''),
            'recommendations' => array_values(AiText::cleanArray($data['recommendations'] ?? [])),
        ];
    }
}
