<?php

namespace App\Services\AI;

/** Hasil satu pemanggilan AI beserta metrik token dan waktunya. */
class AiResult
{
    public function __construct(
        public readonly string $text,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $responseTime = 0,
        public readonly array $raw = [],
    ) {}

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }

    /**
     * Ambil JSON dari balasan model. Model sering membungkus JSON dengan
     * pagar ```json, jadi pagar itu dibuang dulu sebelum di-decode.
     */
    public function json(): array
    {
        $text = trim($this->text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);

        // ambil blok {...} atau [...] paling luar kalau masih ada teks pengantar
        if (! str_starts_with($text, '{') && ! str_starts_with($text, '[')) {
            $start = strcspn($text, '{[');
            $end = max(strrpos($text, '}'), strrpos($text, ']'));

            if ($end !== false && $end > $start) {
                $text = substr($text, $start, $end - $start + 1);
            }
        }

        $decoded = json_decode($text, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        // Model sering menempelkan basa-basi setelah blok JSON. Ambil dari
        // kurung pembuka pertama sampai kurung penutup terakhir, lalu coba lagi.
        $start = strcspn($text, '{[');
        $end = max(strrpos($text, '}') ?: -1, strrpos($text, ']') ?: -1);

        if ($end > $start) {
            $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
        }

        return is_array($decoded) ? $decoded : [];
    }
}
