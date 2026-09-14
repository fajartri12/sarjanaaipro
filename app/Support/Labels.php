<?php

namespace App\Support;

/** Label & warna badge yang dipakai lintas halaman Blade. Satu tempat, biar konsisten. */
class Labels
{
    public const SECTION_STATUS = [
        'empty' => ['label' => 'Kosong', 'tone' => 'gray'],
        'draft' => ['label' => 'Draft', 'tone' => 'amber'],
        'done' => ['label' => 'Selesai', 'tone' => 'green'],
    ];

    /**
     * Jenjang pendidikan. Satu tempat untuk semua label yang bergantung jenjang:
     * nama dokumen, gelar, dan sebutan mahasiswa. Dipakai di prompt AI, cover
     * export, dan salinan antarmuka supaya tidak lagi hardcode "skripsi".
     */
    public const DEGREE_LEVEL = [
        'S1' => ['label' => 'S1 (Sarjana)', 'document' => 'skripsi', 'degree' => 'Sarjana Komputer', 'student' => 'mahasiswa'],
        'S2' => ['label' => 'S2 (Magister)', 'document' => 'tesis', 'degree' => 'Magister Komputer', 'student' => 'mahasiswa magister'],
        'S3' => ['label' => 'S3 (Doktor)', 'document' => 'disertasi', 'degree' => 'Doktor', 'student' => 'mahasiswa doktoral'],
    ];

    public const DEGREE_LEVEL_DEFAULT = 'S1';

    /** Nama dokumen untuk jenjang, mis. "skripsi" / "tesis" / "disertasi". */
    public static function document(?string $level): string
    {
        return self::DEGREE_LEVEL[$level]['document'] ?? self::DEGREE_LEVEL[self::DEGREE_LEVEL_DEFAULT]['document'];
    }

    /** Gelar yang tercetak di cover, mis. "Sarjana Komputer". */
    public static function degree(?string $level): string
    {
        return self::DEGREE_LEVEL[$level]['degree'] ?? self::DEGREE_LEVEL[self::DEGREE_LEVEL_DEFAULT]['degree'];
    }

    /** Versi berjudul dari nama dokumen, untuk label/tab, mis. "Skripsi" / "Tesis". */
    public static function documentTitle(?string $level): string
    {
        return ucfirst(self::document($level));
    }

    public const PROJECT_STATUS = [
        'aktif' => ['label' => 'Aktif', 'tone' => 'blue'],
        'selesai' => ['label' => 'Selesai', 'tone' => 'green'],
        'ditunda' => ['label' => 'Ditunda', 'tone' => 'gray'],
    ];

    public const DOCUMENT_STATUS = [
        'pending' => ['label' => 'Menunggu', 'tone' => 'gray'],
        'processing' => ['label' => 'Diproses', 'tone' => 'amber'],
        'ready' => ['label' => 'Siap', 'tone' => 'green'],
        'failed' => ['label' => 'Gagal', 'tone' => 'red'],
    ];

    public const SESSION_STATUS = [
        'active' => ['label' => 'Berjalan', 'tone' => 'blue'],
        'finished' => ['label' => 'Selesai', 'tone' => 'green'],
    ];

    public const PAYMENT_STATUS = [
        'pending' => ['label' => 'Menunggu', 'tone' => 'amber'],
        'paid' => ['label' => 'Lunas', 'tone' => 'green'],
        'failed' => ['label' => 'Gagal', 'tone' => 'red'],
        'expired' => ['label' => 'Kedaluwarsa', 'tone' => 'gray'],
    ];

    public const SUBSCRIPTION_STATUS = [
        'pending' => ['label' => 'Menunggu', 'tone' => 'amber'],
        'active' => ['label' => 'Aktif', 'tone' => 'green'],
        'canceled' => ['label' => 'Dibatalkan', 'tone' => 'gray'],
        'expired' => ['label' => 'Kedaluwarsa', 'tone' => 'gray'],
    ];

    public const REFERENCE_TYPE = [
        'journal' => 'Jurnal',
        'book' => 'Buku',
        'chapter' => 'Bab Buku',
        'thesis' => 'Skripsi/Tesis',
        'conference' => 'Konferensi',
        'web' => 'Web',
        'other' => 'Lainnya',
    ];

    public const FEATURE_LABEL = [
        'generate_titles' => 'Generate Judul',
        'ai_chat' => 'AI Chat',
        'draft' => 'AI Draft',
        'reviewer' => 'AI Reviewer',
        'similarity' => 'Cek Kemiripan',
        'sempro' => 'Simulasi Sempro',
        'research' => 'Riset Jurnal',
        'titles' => 'Generate Judul',
    ];

    /** @param array<string, array{label: string, tone: string}> $bag */
    public static function meta(array $bag, ?string $key, ?array $fallback = null): array
    {
        $fallback ??= ['label' => (string) $key, 'tone' => 'gray'];

        return $bag[$key] ?? $fallback;
    }

    public static function rupiah(int|float|null $value): string
    {
        return 'Rp '.number_format((float) ($value ?? 0), 0, ',', '.');
    }

    public static function angka(int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), 0, ',', '.');
    }

    public static function tanggal($value): string
    {
        if (! $value) {
            return '—';
        }

        return \Illuminate\Support\Carbon::parse($value)->translatedFormat('j M Y');
    }
}
