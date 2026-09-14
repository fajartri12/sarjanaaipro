<?php

namespace App\Services\AI;

use App\Support\Labels;

/**
 * Kumpulan prompt. Semua teks prompt terkumpul di sini supaya bisa
 * ditinjau/diubah tanpa menyentuh logic fitur.
 *
 * Tiap prompt menerima jenjang ($level: S1/S2/S3) supaya asisten menyesuaikan
 * kedalaman dan istilah dokumen — skripsi, tesis, atau disertasi.
 */
class PromptLibrary
{
    public static function base(?string $level = null): string
    {
        $document = Labels::document($level);

        return "Anda adalah asisten akademik untuk mahasiswa Indonesia yang sedang menyusun {$document}. "
            .'Gunakan bahasa Indonesia akademik yang jelas dan tidak berlebihan. '
            .'Jangan mengarang referensi, data, atau angka. Jika informasi kurang, sebutkan asumsinya. '
            .'Posisikan diri sebagai pendamping: beri arahan, bukan menggantikan proses berpikir mahasiswa.';
    }

    /**
     * Aturan format untuk balasan yang isinya dibaca langsung oleh pengguna.
     * Antarmuka menampilkan teks apa adanya, jadi tanda markdown akan ikut
     * terbaca sebagai karakter aneh kalau model tetap memakainya.
     */
    public const PLAIN = 'Tulis dalam teks biasa tanpa format markdown dan tanpa tag HTML: '
        .'tanpa **, ##, tanda bintang, atau pagar ```. Gunakan kalimat utuh, bukan daftar berpoin.';

    public static function titles(array $input, ?string $level = null): string
    {
        $document = Labels::document($level);

        return self::base($level)."\n\n"
            ."Buat 5 alternatif judul {$document} berdasarkan data berikut.\n\n"
            .'Program Studi: '.($input['study_program'] ?? '-')."\n"
            .'Topik: '.($input['topic'] ?? '-')."\n"
            .'Objek: '.($input['object'] ?? '-')."\n"
            .'Lokasi: '.($input['location'] ?? '-')."\n"
            .'Metode: '.($input['method'] ?? '-')."\n"
            .'Kata kunci: '.($input['keywords'] ?? '-')."\n\n"
            ."Balas HANYA dengan JSON valid berbentuk array objek, tanpa teks lain:\n"
            .'[{"title":"...","relevance":0-100,"novelty":0-100,"feasibility":0-100,"complexity":0-100,"gap_score":0-100,"description":"...","research_gap":"...","variables":["..."],"recommendation":"..."}]';
    }

    public static function validateTitle(string $title, ?string $context = null, ?string $level = null): string
    {
        $document = Labels::document($level);

        return self::base($level)."\n\n"
            ."Nilai kelayakan judul {$document} berikut: \"{$title}\"\n"
            .($context ? "Konteks: {$context}\n" : '')
            ."\nBalas HANYA JSON valid:\n"
            .'{"relevance":0-100,"novelty":0-100,"feasibility":0-100,"complexity":0-100,"gap_score":0-100,'
            .'"research_gap":"...","problem_statement":"...","variables":["..."],"method":"...",'
            .'"recommendation":"...","risk":"..."}';
    }

    public static function draftSection(array $input): string
    {
        $document = Labels::document($input['degree_level'] ?? null);

        return self::base($input['degree_level'] ?? null)."\n\n"
            ."Tulis bagian {$document} berikut.\n\n"
            .'Judul: '.($input['title'] ?? '-')."\n"
            .'Program Studi: '.($input['study_program'] ?? '-')."\n"
            .'Metode: '.($input['method'] ?? '-')."\n"
            ."Bagian: {$input['section']}\n\n"
            .'Tulis 400-600 kata dalam bahasa akademik. Jangan mengarang data statistik atau nama penulis. '
            .'Gunakan placeholder yang jelas bila data belum ada. '
            .self::PLAIN;
    }

    public static function draftAction(string $action, string $section, string $content, ?string $level = null): string
    {
        $instruction = match ($action) {
            'improve' => 'Perbaiki kualitas bahasa dan alur argumentasi tanpa mengubah makna.',
            'expand' => 'Kembangkan bagian ini dengan penjelasan tambahan yang relevan.',
            'summarize' => 'Ringkas bagian ini menjadi paragraf padat.',
            'formalize' => 'Ubah menjadi bahasa akademik yang formal dan baku.',
            'continue' => 'Lanjutkan tulisan ini selaras dengan gaya dan konteks sebelumnya.',
            'explain' => 'Jelaskan isi bagian ini dengan bahasa yang lebih mudah dipahami.',
            default => 'Perbaiki bagian ini.',
        };

        return self::base($level)."\n\nBagian: {$section}\n\nTugas: {$instruction}\n\nNaskah:\n{$content}\n\n".self::PLAIN;
    }

    public static function reviewDraft(array $sections, ?string $level = null): string
    {
        $document = Labels::document($level);
        $body = '';
        foreach ($sections as $key => $content) {
            $body .= "### {$key}\n".mb_substr(strip_tags((string) $content), 0, 3000)."\n\n";
        }

        return self::base($level)."\n\nNilai draft {$document} berikut seperti dosen penguji.\n\n{$body}\n"
            ."Balas HANYA JSON valid:\n"
            .'{"scores":{"clarity":0-100,"writing":0-100,"problem":0-100,"gap":0-100,"consistency":0-100,'
            .'"methodology":0-100,"citation":0-100,"structure":0-100},"summary":"...","recommendations":["..."]}';
    }

    /**
     * Review satu paragraf saja — lebih fokus dan hemat token.
     */
    public static function reviewParagraph(string $paragraph, ?string $level = null): string
    {
        return self::base($level)."\n\n"
            ."Nilai paragraf berikut seperti dosen penguji menilai karya ilmiah.\n\n"
            ."Paragraf: {$paragraph}\n\n"
            ."Balas HANYA JSON valid:\n"
            .'{"score":0-100,"issue":"...","suggestion":"...","reason":"..."}'
            .' — score: nilai keseluruhan 0-100, issue: masalah utama (satu kalimat), '
            .'suggestion: saran perbaikan konkret (satu kalimat), reason: alasan penilaian (1-2 kalimat). '
            .'Tulis dalam bahasa Indonesia.';
    }

    public static function semproQuestions(array $project, int $count): string
    {
        $focus = $project['focus_category'] ?? null;
        $asked = $project['asked'] ?? [];

        $extra = '';
        if ($focus) {
            $extra .= "\nFokuskan SEMUA pertanyaan pada kategori: {$focus}.\n";
        }
        if ($asked) {
            $list = collect($asked)->take(20)->map(fn ($q) => '- '.$q)->implode("\n");
            $extra .= "\nPertanyaan berikut SUDAH ditanyakan. Jangan ulangi atau buat versi mirip:\n{$list}\n";
        }

        return self::base($project['degree_level'] ?? null)."\n\n"
            ."Buat {$count} pertanyaan seminar proposal yang realistis seperti dari dosen penguji.\n\n"
            .'Judul: '.($project['title'] ?? '-')."\n"
            .'Metode: '.($project['method'] ?? '-')."\n"
            .'Ringkasan draft: '.mb_substr((string) ($project['draft'] ?? '-'), 0, 4000)."\n"
            .$extra."\n"
            ."Balas HANYA JSON valid berupa array:\n"
            .'[{"category":"metodologi|landasan teori|rumusan masalah|tujuan|hasil|pembahasan|umum","difficulty":"dasar|menengah|sulit","question":"...","expected_points":"..."}]';
    }

    /**
     * Pertanyaan lanjutan setelah jawaban dinilai lemah.
     * Dipakai simulasi adaptif: penguji mengejar bagian yang belum dikuasai.
     */
    public static function semproFollowUp(array $input, int $count): string
    {
        $weakList = collect($input['weak_points'] ?? [])->map(fn ($w) => '- '.$w)->implode("\n");

        return self::base($input['degree_level'] ?? null)."\n\n"
            ."Mahasiswa baru saja menjawab pertanyaan berikut dan jawabannya masih lemah.\n\n"
            .'Pertanyaan: '.($input['question'] ?? '-')."\n"
            .'Ringkasan jawaban: '.mb_substr((string) ($input['answer'] ?? '-'), 0, 1200)."\n"
            .'Catatan penguji: '.($input['feedback'] ?? '-')."\n"
            .($weakList ? "Kelemahan yang terlihat:\n{$weakList}\n" : '')
            ."\nBuat {$count} pertanyaan lanjutan yang menggali kelemahan tersebut. "
            ."Pertanyaan harus lebih spesifik dari pertanyaan sebelumnya, bukan pengulangan.\n\n"
            ."Balas HANYA JSON valid berupa array:\n"
            .'[{"category":"metodologi|landasan teori|rumusan masalah|tujuan|hasil|pembahasan|umum","difficulty":"dasar|menengah|sulit","question":"...","expected_points":"..."}]';
    }

    public static function evaluateAnswer(string $question, string $answer, ?string $expected = null, ?string $level = null): string
    {
        return self::base($level)."\n\n"
            ."Nilai jawaban mahasiswa berikut.\n\nPertanyaan: {$question}\n"
            .($expected ? "Poin yang diharapkan: {$expected}\n" : '')
            ."Jawaban: {$answer}\n\n"
            ."Balas HANYA JSON valid:\n"
            .'{"concept":0-100,"relevance":0-100,"argumentation":0-100,"methodology":0-100,'
            .'"clarity":0-100,"confidence":0-100,"score":0-100,"feedback":"...","strong":["..."],"weak":["..."]}';
    }

    public static function analyzeDocument(string $text, ?string $level = null): string
    {
        return self::base($level)."\n\n"
            ."Analisis jurnal berikut dan ekstrak informasinya.\n\n"
            .mb_substr($text, 0, 12_000)."\n\n"
            ."Balas HANYA JSON valid:\n"
            .'{"title":"...","author":"...","year":0,"purpose":"...","method":"...","dataset":"...",'
            .'"result":"...","limitations":"...","research_gap":"...","relevance":"..."}';
    }

    public static function researchGap(string $topic, array $documents, ?string $level = null): string
    {
        $list = collect($documents)->map(fn ($d) => '- '.($d['title'] ?? '').' ('.($d['year'] ?? 'n.d.').'): '.mb_substr((string) ($d['abstract'] ?? ''), 0, 500))->implode("\n");

        return self::base($level)."\n\n"
            ."Identifikasi research gap dari kumpulan penelitian berikut.\n\nTopik: {$topic}\n\n{$list}\n\n"
            .'Balas HANYA JSON valid: {"gaps":["..."],"opportunities":["..."],"summary":"..."}'
            .' Isi tiap nilai berupa '.self::PLAIN;
    }

    public static function chat(string $question, array $sources = [], ?string $level = null): string
    {
        $context = '';

        foreach ($sources as $i => $source) {
            $context .= '['.($i + 1).'] '.($source['label'] ?? '')."\n".mb_substr((string) ($source['content'] ?? ''), 0, 1500)."\n\n";
        }

        /*
         * Tanpa kutipan, model akan menjawab dari pengetahuan umum dan
         * hasilnya melenceng dari dokumen mahasiswa. Lebih berguna kalau
         * model menyebut apa yang belum ada di dokumen.
         */
        if ($context === '') {
            return self::base($level)."\n\nDokumen yang diunggah tidak memuat kutipan yang cocok dengan pertanyaan ini.\n\n"
                ."Pertanyaan mahasiswa: {$question}\n\n"
                .'Sampaikan bahwa dokumen yang diunggah belum memuat jawabannya, lalu sebutkan data, '
                .'bagian, atau jenis sumber apa yang perlu ditambahkan. Jangan menjawab dari pengetahuan umum. '
                .self::PLAIN;
        }

        return self::base($level)."\n\nJawab pertanyaan mahasiswa HANYA berdasarkan kutipan dokumen di bawah. "
            .'Rujuk sumbernya dengan penanda [1], [2], dan seterusnya sesuai nomor kutipan. '
            .'Jangan menambah fakta, angka, atau referensi dari luar kutipan. '
            .'Kalau kutipan belum cukup, sebutkan bagian mana yang belum tercakup, jangan diisi dugaan. '
            .self::PLAIN."\n\nKutipan:\n\n{$context}Pertanyaan mahasiswa: {$question}";
    }

    /**
     * Asisten metodologi penelitian — menjawab pertanyaan tentang metode penelitian.
     *
     * Berguna saat mahasiswa bingung memilih metode, memahami konsep, atau
     * ingin validasi pendekatan mereka.
     */
    public static function methodologyAssistant(string $question, ?string $method = null, ?string $level = null): string
    {
        $methodInfo = $method ? "Metode yang digunakan: {$method}\n" : '';

        return self::base($level)."\n\n"
            ."Anda adalah asisten metodologi penelitian untuk mahasiswa Indonesia.\n\n"
            .$methodInfo
            ."Pertanyaan mahasiswa: {$question}\n\n"
            .'Jawab dengan bahasa Indonesia yang jelas dan akademik. '
            .'Berikan contoh bila perlu. Kalau metode yang dipilih belum tepat untuk jenis penelitiannya, '
            .'sarankan alternatif yang lebih sesuai beserta alasannya. '
            .self::PLAIN;
    }

    /**
     * Pemeriksaan konsistensi skripsi — cross-check antara judul, rumusan masalah,
     * tujuan, dan metode.
     *
     * Dijalankan dari halaman reviewer untuk memberi gambaran apakah alur
     * logis penelitian sudah tertata dengan baik.
     */
    public static function consistencyCheck(
        string $title,
        ?string $problem,
        ?string $objectives,
        ?string $method,
        ?string $level = null,
    ): string {
        return self::base($level)."\n\n"
            ."Periksa konsistensi logis antara komponen-komponen skripsi berikut.\n\n"
            ."Judul: {$title}\n"
            .($problem ? "Rumusan masalah: {$problem}\n" : '')
            .($objectives ? "Tujuan penelitian: {$objectives}\n" : '')
            .($method ? "Metode penelitian: {$method}\n" : '')."\n"
            ."Balas HANYA JSON valid:\n"
            .'{"consistent":true|false,"issues":["..."],"suggestions":["..."],"summary":"..."}'
            .' — consistent: apakah alur logis, issues: daftar ketidaksesuaian (array), '
            .'suggestions: saran perbaikan, summary: ringkasan temuan. '
            .'Tulis dalam bahasa Indonesia.';
    }
}
