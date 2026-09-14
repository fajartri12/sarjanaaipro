<?php

namespace App\Services\AI;

use App\Models\Document;
use App\Models\Project;
use App\Models\ThesisSection;
use App\Support\AiText;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pengecekan kemiripan draft: copy-paste dari sumber yang diunggah,
 * maupun pengulangan antar bagian dalam draft sendiri.
 *
 * Dua lapis, keduanya dihitung lokal:
 * 1. Kalimat yang muncul utuh di sumber.
 * 2. Deret 4 kata yang masih sama walau beberapa kata sudah diganti,
 *    supaya parafrase ringan ikut tertangkap.
 *
 * Skor tidak diambil dari skor pencarian. Ini penting: skor VectorSearch
 * adalah rasio kata kunci dan tidak mengukur kemiripan. VectorSearch di
 * sini cuma dipakai untuk menyaring kandidat chunk supaya tidak semua
 * chunk harus dibandingkan.
 */
class SimilarityService
{
    /** Kalimat lebih pendek dari ini diabaikan — terlalu umum untuk disebut jiplakan. */
    private const MIN_SENTENCE = 40;

    /** Ambang skor minimal supaya sebuah kecocokan ditampilkan. */
    private const MIN_MATCH = 10;

    /** Panjang deret kata untuk menangkap parafrase ringan. */
    private const SHINGLE = 4;

    /**
     * Persentase deret kata yang dianggap mirip. Dipilih dari pengukuran pada
     * teks akademik Indonesia dengan n=4: satu kata yang diganti dalam kalimat
     * panjang menyisakan 89% deret yang sama, sedangkan kalimat yang ditulis
     * sendiri tapi memakai kosakata baku bidang studi cuma 60%. Ambang di
     * tengah jurang itu memisahkan keduanya.
     *
     * Jangan diturunkan tanpa mengulang pengukuran: begitu ambang masuk ke
     * 50-60%, frasa baku seperti "metode penelitian ini menggunakan pendekatan
     * kuantitatif" mulai ikut dilaporkan sebagai jiplakan.
     */
    private const SHINGLE_MATCH = 75;

    public function __construct(
        private AiService $ai,
        private VectorSearch $vector,
    ) {}

    /**
     * Cek kemiripan satu bagian.
     *
     * @return array{score: int, matches: array<int, array<string, mixed>>, summary: string}
     */
    public function checkSection(Project $project, ThesisSection $section): array
    {
        $sentences = $this->sentences($section->content);

        if ($sentences === []) {
            throw ValidationException::withMessages([
                'section_id' => 'Bagian ini masih kosong atau belum punya kalimat yang cukup panjang untuk diperiksa.',
            ]);
        }

        $matches = $this->collectMatches($project, $section, $sentences);

        return [
            'score' => (int) (collect($matches)->max('score') ?? 0),
            'matches' => $matches,
            'summary' => $this->summary($project, (int) (collect($matches)->max('score') ?? 0), $matches),
        ];
    }

    /**
     * Cek seluruh bagian dalam satu project.
     *
     * @return array{score: int, sections: array<int, array<string, mixed>>, summary: string}
     */
    public function checkFullProject(Project $project): array
    {
        $sections = $project->sections()
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->get();

        if ($sections->isEmpty()) {
            throw ValidationException::withMessages([
                'project' => 'Project ini belum punya isi untuk diperiksa.',
            ]);
        }

        $results = [];
        $total = 0;

        foreach ($sections as $section) {
            $sentences = $this->sentences($section->content);
            $matches = $sentences === [] ? [] : $this->collectMatches($project, $section, $sentences);
            $score = (int) (collect($matches)->max('score') ?? 0);

            $results[] = [
                'section_id' => $section->id,
                'title' => trim("{$section->chapter} {$section->title}"),
                'score' => $score,
                'matches' => $matches,
            ];

            $total += $score;
        }

        // Skor project = rata-rata, sebab tiap bagian dinilai sendiri-sendiri.
        // Memakai nilai maksimum di sini justru menyembunyikan bagian lain
        // yang juga bermasalah.
        $score = (int) round($total / $sections->count());

        return [
            'score' => $score,
            'sections' => collect($results)->sortByDesc('score')->values()->all(),
            'summary' => $this->summary($project, $score, []),
        ];
    }

    /**
     * Semua kecocokan untuk satu bagian: terhadap dokumen, lalu terhadap
     * bagian lain. Diurutkan dan dibersihkan dari label yang berulang.
     */
    private function collectMatches(Project $project, ThesisSection $section, array $sentences): array
    {
        return collect([
            ...$this->againstDocuments($project, $sentences),
            ...$this->againstSections($project, $section, $sentences),
        ])
            ->sortByDesc('score')
            ->unique('label')
            ->values()
            ->all();
    }

    /**
     * Bandingkan kalimat bagian dengan chunk dokumen yang diunggah user.
     *
     * Dokumen dari project lain ikut diperiksa: memakai ulang tulisan sendiri
     * untuk dua naskah adalah bentuk plagiarisme diri yang paling sering terjadi.
     */
    private function againstDocuments(Project $project, array $sentences): array
    {
        $documents = Document::ownedBy($project->user_id)
            ->where('status', 'ready')
            ->with('chunks')
            ->get();

        if ($documents->isEmpty()) {
            return [];
        }

        // Kalimat terpanjang dipakai sebagai kueri: kata uniknya paling banyak.
        $query = Str::limit(collect($sentences)->sortByDesc(fn ($s) => mb_strlen($s))->first(), 400);

        return collect($this->vector->search($documents, $query, topK: 8))
            ->map(fn (array $candidate) => [
                'source' => 'document',
                'label' => $candidate['label'],
                'snippet' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($candidate['content']))), 240),
                'score' => $this->overlap($sentences, $candidate['content']),
            ])
            ->filter(fn (array $match) => $match['score'] >= self::MIN_MATCH)
            ->values()
            ->all();
    }

    /**
     * Bandingkan kalimat bagian dengan bagian lain di project yang sama.
     * Inilah plagiarisme diri sendiri: paragraf yang dipakai ulang di bab lain.
     */
    private function againstSections(Project $project, ThesisSection $section, array $sentences): array
    {
        return $project->sections()
            ->whereKeyNot($section->id)
            ->whereNotNull('content')
            ->get()
            ->map(fn (ThesisSection $other) => [
                'source' => 'section',
                'label' => trim("{$other->chapter} {$other->title}"),
                'snippet' => Str::limit($this->firstSharedSentence($sentences, $other->content), 240),
                'score' => $this->overlap($sentences, $other->content),
            ])
            ->filter(fn (array $match) => $match['score'] >= self::MIN_MATCH)
            ->values()
            ->all();
    }

    /**
     * Persentase kalimat yang mirip dengan teks pembanding.
     *
     * Kalimat dihitung mirip kalau muncul utuh di sumber, atau masih berbagi
     * cukup banyak deret kata. Penyebutnya selalu jumlah kalimat bagian ini,
     * supaya angkanya berarti "berapa persen bagian ini yang bermasalah".
     * Skor di bawah ambang sengaja jadi 0, bukan angka kecil: tumpang-tindih
     * sisa itu hampir selalu kosakata baku bidang studi, bukan jiplakan.
     */
    private function overlap(array $sentences, ?string $haystack): int
    {
        $haystack = $this->normalize($haystack);

        if ($haystack === '' || $sentences === []) {
            return 0;
        }

        $source = $this->shingles($haystack);

        $hits = collect($sentences)
            ->filter(function (string $sentence) use ($source, $haystack) {
                $normalized = $this->normalize($sentence);

                // Kalimat utuh — pasti jiplakan.
                if (str_contains($haystack, $normalized)) {
                    return true;
                }

                // Kalimat yang masih berbagi cukup banyak deret kata.
                $gram = $this->shingles($normalized);

                if ($gram === []) {
                    return false;
                }

                return count(array_intersect_key($gram, $source)) / count($gram) * 100 >= self::SHINGLE_MATCH;
            })
            ->count();

        return (int) round($hits / count($sentences) * 100);
    }

    /** Himpunan deret n kata, dipakai untuk mengukur tumpang-tindih. */
    private function shingles(string $text): array
    {
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) < self::SHINGLE) {
            return [];
        }

        $grams = [];

        for ($i = 0; $i <= count($words) - self::SHINGLE; $i++) {
            $grams[implode(' ', array_slice($words, $i, self::SHINGLE))] = true;
        }

        return $grams;
    }

    /**
     * Kalimat yang paling mirip di teks pembanding, sebagai bukti.
     *
     * Hanya kalimat yang sudah lolos ambang yang ditampilkan, supaya kutipan
     * buktinya sejalan dengan angka skornya. Kalau tidak ada yang lolos
     * (misalnya skor datang dari kalimat lain), kembalikan yang rasio
     * tertinggi supaya barisnya tidak kosong.
     */
    private function firstSharedSentence(array $sentences, ?string $haystack): string
    {
        $haystack = $this->normalize($haystack);
        $source = $this->shingles($haystack);

        $best = $sentences[0] ?? '';
        $bestRatio = -1.0;

        foreach ($sentences as $sentence) {
            $normalized = $this->normalize($sentence);

            if (str_contains($haystack, $normalized)) {
                return $sentence;
            }

            $gram = $this->shingles($normalized);
            $ratio = $gram === [] ? 0.0 : count(array_intersect_key($gram, $source)) / count($gram);

            if ($ratio > $bestRatio) {
                $best = $sentence;
                $bestRatio = $ratio;
            }
        }

        return $best;
    }

    /** Kalimat yang cukup panjang untuk layak dibandingkan. */
    private function sentences(?string $content): array
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $content)));

        if ($text === '') {
            return [];
        }

        $parts = preg_split('/(?<=[.?!])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            array_map('trim', $parts),
            fn (string $sentence) => mb_strlen($sentence) >= self::MIN_SENTENCE,
        ));
    }

    /** Buang HTML dan tanda baca, lalu seragamkan huruf untuk pencocokan. */
    private function normalize(?string $text): string
    {
        $text = mb_strtolower(trim(preg_replace('/\s+/', ' ', strip_tags((string) $text))));

        // Tanda baca jadi pemisah supaya "kata, kata" sama dengan "kata kata".
        return trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text));
    }

    /**
     * Saran perbaikan dari AI. Sengaja tahan gagal: angka skornya tetap
     * berguna walau provider AI sedang tidak bisa dihubungi.
     */
    private function summary(Project $project, int $score, array $matches): string
    {
        if ($score < 20) {
            return 'Tidak ditemukan kemiripan yang perlu dikhawatirkan.';
        }

        $list = collect($matches)
            ->take(5)
            ->map(fn (array $match) => "- {$match['label']} (kalimat cocok {$match['score']}%)")
            ->implode("\n");

        $fallback = 'Tulis ulang bagian yang ditandai dengan kalimat sendiri, dan cantumkan sumber kalau memang mengutip.';

        try {
            $result = $this->ai->chat(
                messages: [
                    ['role' => 'system', 'content' => PromptLibrary::base($project->degree_level)],
                    ['role' => 'user', 'content' => sprintf(
                        "Draft mahasiswa punya skor kemiripan %d%%. Sumber yang mirip:\n%s\n\n"
                        .'Sebutkan bagian mana yang perlu ditulis ulang dan bagaimana caranya. Maksimal 3 kalimat. %s',
                        $score,
                        $list,
                        PromptLibrary::PLAIN,
                    )],
                ],
                feature: 'similarity',
                user: $project->user,
                project: $project,
            );
        } catch (\Throwable $e) {
            report($e);

            return $fallback;
        }

        return AiText::plain($result->text) ?: $fallback;
    }
}
