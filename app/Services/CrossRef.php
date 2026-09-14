<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Pembungkus CrossRef untuk metadata publikasi ilmiah.
 *
 * Gratis dan tanpa API key. Dipakai supaya metadata jurnal (judul, penulis,
 * tahun, DOI) diambil dari sumber resmi, bukan dikarang AI.
 */
class CrossRef
{
    private const BASE = 'https://api.crossref.org/works';

    /** Field yang diambil; memperkecil payload sekaligus mempercepat respons. */
    private const FIELDS = 'DOI,title,author,issued,container-title,publisher,abstract,type,volume,issue,page,URL';

    /**
     * Cari karya ilmiah.
     *
     * @param  array{year_from?: int|null, year_to?: int|null}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, array $filters = [], int $limit = 20): array
    {
        $params = [
            'query' => $query,
            'rows' => max(1, min($limit, 50)),
            'select' => self::FIELDS,
        ];

        $from = $filters['year_from'] ?? null;
        $to = $filters['year_to'] ?? null;

        if ($from || $to) {
            $params['filter'] = 'from-pub-date:'.($from ?: 1900).'-01-01,'
                .'until-pub-date:'.($to ?: now()->year).'-12-31';
        }

        $message = $this->request('', $params);

        return collect($message['items'] ?? [])
            ->map(fn (array $item) => $this->map($item))
            ->filter(fn (array $item) => $item['title'])
            ->values()
            ->all();
    }

    /** Satu karya berdasarkan DOI. Null kalau tidak ketemu. */
    public function find(string $doi): ?array
    {
        $message = $this->request('/'.rawurlencode($doi), []);

        return $message ? $this->map($message) : null;
    }

    /**
     * CrossRef membungkus hasilnya di node `message`.
     * Gagal jaringan/koneksi tidak boleh melempar — pemanggil cukup dapat null.
     */
    private function request(string $path, array $params): ?array
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders(['Accept' => 'application/json'])
                // CrossRef menaikkan prioritas permintaan yang jelas asalnya.
                ->withUserAgent(config('app.name').' ('.config('app.url').')')
                ->get(self::BASE.$path, $params);

            return $response->successful() ? $response->json('message') : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Seragamkan bentuk CrossRef menjadi bentuk kolom `references`. */
    private function map(array $item): array
    {
        $authors = collect($item['author'] ?? [])
            ->map(function (array $a) {
                $family = trim($a['family'] ?? '');
                $given = trim($a['given'] ?? '');

                return trim($family.($given ? ', '.$given : ''), ' ,');
            })
            ->filter()
            ->implode('; ');

        $abstract = $item['abstract'] ?? null;

        return [
            'doi' => $item['DOI'] ?? null,
            'title' => Str::limit($this->text($item['title'][0] ?? ''), 490, ''),
            // kolom authors hanya 500 karakter
            'authors' => $authors ? Str::limit($authors, 490, '…') : null,
            'year' => $item['issued']['date-parts'][0][0] ?? null,
            'container' => Str::limit($this->text($item['container-title'][0] ?? ''), 250, ''),
            'publisher' => $item['publisher'] ?? null,
            'volume' => $item['volume'] ?? null,
            'issue' => $item['issue'] ?? null,
            'pages' => $item['page'] ?? null,
            'type' => $this->type($item['type'] ?? null),
            // abstrak CrossRef berformat XML JATS
            'abstract' => $abstract ? $this->text(strip_tags($abstract)) : null,
            'url' => $item['URL'] ?? null,
        ];
    }

    /**
     * CrossRef mengirim entitas HTML (`&amp;`) dan tag JATS di sebagian field.
     * Tanpa ini, judul ikut menampilkan `&amp;` apa adanya di layar.
     */
    private function text(string $value): string
    {
        return trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** Petakan jenis publikasi CrossRef ke pilihan di tabel references. */
    private function type(?string $type): string
    {
        return match ($type) {
            'journal-article' => 'journal',
            'book', 'monograph', 'reference-book', 'edited-book' => 'book',
            'book-chapter', 'book-part' => 'chapter',
            'proceedings-article', 'proceedings' => 'conference',
            'dissertation' => 'thesis',
            'posted-content', 'report', 'standard', 'dataset' => 'web',
            default => 'other',
        };
    }
}
