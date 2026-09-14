<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AiProvider;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Mengambil konteks dari dokumen yang diunggah lewat pencarian vektor/cosine.
 * Kalau embedding belum tersedia (provider null), fallback ke pencarian kata.
 */
class VectorSearch
{
    public function __construct(private AiProvider $provider) {}

    /**
     * Cari chunk paling relevan dari kumpulan dokumen untuk sebuah pertanyaan.
     *
     * @param  Collection<int, \App\Models\Document>  $documents
     * @return array<int, array{content: string, label: string, score: float}>
     */
    public function search(Collection $documents, string $query, int $topK = 4): array
    {
        $chunks = $documents->filter(fn ($d) => $d->isReady())
            ->flatMap(function ($doc) {
                return $doc->chunks->map(fn ($chunk) => [
                    'content' => $chunk->content,
                    'label' => $doc->title ?? $doc->original_name.' (hal. '.(floor($chunk->position / 5) + 1).')',
                    'embedding' => $chunk->embedding,
                    'tokens' => $chunk->tokens ?? 0,
                ]);
            });

        if ($chunks->isEmpty()) {
            return [];
        }

        // ponytail: endpoint kita tidak punya model embedding, jadi tiap panggilan
        // pasti gagal. Pencarian kata di bawah sudah cukup untuk dokumen penelitian.
        try {
            $queryVector = $this->provider->embed($query);
        } catch (Throwable $e) {
            report($e);
            $queryVector = [];
        }

        if (count($queryVector) < 2) {
            return $this->keywordFallback($chunks, $query, $topK);
        }

        return $chunks
            ->map(function ($chunk) use ($queryVector) {
                $vec = $chunk['embedding'] ?? [];
                $score = is_array($vec) ? $this->cosine($queryVector, $vec) : 0;

                return [
                    'content' => $chunk['content'],
                    'label' => $chunk['label'],
                    'score' => $score + (min($chunk['tokens'], 400) / 4000), // bobot kecil utk chunk berdaging
                ];
            })
            ->filter(fn ($c) => $c['score'] > 0)
            ->sortByDesc('score')
            ->take($topK)
            ->values()
            ->all();
    }

    private function keywordFallback(Collection $chunks, string $query, int $topK): array
    {
        $keywords = array_values(array_filter(preg_split('/\s+/', strtolower($query))));

        return $chunks->map(function ($chunk) use ($keywords) {
            $haystack = strtolower($chunk['content']);
            $hits = collect($keywords)->filter(fn ($k) => str_contains($haystack, $k))->count();

            return [
                'content' => $chunk['content'],
                'label' => $chunk['label'],
                'score' => $keywords ? $hits / count($keywords) : 0,
            ];
        })
            ->filter(fn ($c) => $c['score'] > 0)
            ->sortByDesc('score')
            ->take($topK)
            ->values()
            ->all();
    }

    private function cosine(array $a, array $b): float
    {
        if (! $a || ! $b || count($a) !== count($b)) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        foreach ($a as $i => $v) {
            $dot += $v * $b[$i];
            $normA += $v * $v;
            $normB += $b[$i] * $b[$i];
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}