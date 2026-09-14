<?php

namespace App\Support;

/**
 * Pembersih teks balasan model.
 *
 * Model sering menempelkan `**tebal**`, `##` heading, pagar ``` dan kadang
 * potongan HTML di dalam kalimat. Semua teks itu dirender lewat `{{ }}` di
 * Blade, jadi tanpa dibersihkan pengguna membaca tanda markdown apa adanya.
 * Kelas ini menyisir tanda tersebut di satu tempat supaya semua service AI
 * tidak menulis logika yang sama berulang.
 */
class AiText
{
    /**
     * Buang penanda markdown dan tag HTML, sisakan teks yang enak dibaca.
     */
    public static function plain(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return $text;
        }

        $text = self::stripTags(trim($text));

        // Pagar kode ```json ... ``` yang ikut kebawa model.
        $text = preg_replace('/^```[a-zA-Z]*\s*|\s*```$/m', '', $text);

        // Heading: "## Judul" jadi "Judul".
        $text = preg_replace('/^\s{0,3}#{1,6}\s+/m', '', $text);

        // Penekanan: **tebal**, __tebal__, *miring*, _miring_, ~~coret~~.
        $text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text);
        $text = preg_replace('/__(.+?)__/s', '$1', $text);
        $text = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/s', '$1', $text);
        $text = preg_replace('/(?<![\w_])_(?!\s)(.+?)(?<!\s)_(?![\w_])/s', '$1', $text);
        $text = preg_replace('/~~(.+?)~~/s', '$1', $text);

        // Petik kode `seperti ini` tetap dibiarkan tanpa pagar.
        $text = preg_replace('/`([^`\n]+)`/', '$1', $text);

        // Tanda kutip yang di-escape balik oleh model.
        $text = str_replace(['\\"', "\\'"], ['"', "'"], $text);

        // Tandai isi daftar supaya spasi baris berikutnya bisa dirapikan.
        $text = preg_replace('/^\s*[-*+]\s+/m', '- ', $text);
        $text = preg_replace('/^\s*\d+\.\s+/m', '- ', $text);

        // Baris kosong beruntun jadi satu saja.
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Bersihkan kumpulan teks. Nilai bukan string dilewatkan apa adanya
     * supaya angka, boolean, dan array bertingkat tidak ikut rusak.
     */
    public static function cleanArray(array $items): array
    {
        return array_map(
            fn ($value) => is_string($value) ? self::plain($value) : $value,
            $items,
        );
    }

    /**
     * Buang tag sambil menjaga pemisah baris/daftar tetap terbaca.
     *
     * `strip_tags` saja akan menempelkan dua blok jadi satu kalimat
     * ("...akhir paragraf.Awal paragraf berikut..."), jadi tag pembuka blok
     * diganti dengan baris baru dulu.
     */
    private static function stripTags(string $text): string
    {
        if (! str_contains($text, '<')) {
            return $text;
        }

        $text = preg_replace(
            '#<(?:br|/p|/div|/li|/h[1-6]|/tr|/ul|/ol|/table)\s*/?>#i',
            "\n",
            $text,
        );

        $text = preg_replace('#<li[^>]*>#i', '- ', $text);

        return html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
