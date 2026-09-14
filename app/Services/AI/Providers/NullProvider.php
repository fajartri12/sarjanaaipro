<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiResult;
use App\Services\AI\Contracts\AiProvider;

/**
 * Provider kosong — saat API key belum diset. Mengembalikan output contoh
 * dengan bentuk yang sama, jadi seluruh flow aplikasi bisa dites tanpa biaya.
 */
class NullProvider implements AiProvider
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function chat(array $messages, array $options = []): AiResult
    {
        $last = end($messages);
        $userText = strtolower((string) ($last['content'] ?? ''));

        $text = match (true) {
            str_contains($userText, 'kelayakan judul') => $this->sampleTitleAnalysis(),
            str_contains($userText, 'alternatif judul') => $this->sampleTitles(),
            str_contains($userText, 'pertanyaan seminar proposal') => $this->sampleSemproQuestions(),
            str_contains($userText, 'nilai jawaban mahasiswa') => $this->sampleEvaluation(),
            str_contains($userText, 'nilai paragraf berikut') => $this->sampleParagraphReview(),
            str_contains($userText, 'periksa konsistensi logis') => $this->sampleConsistency(),
            str_contains($userText, 'nilai draft') => $this->sampleReview(),
            str_contains($userText, 'identifikasi research gap') => $this->sampleResearchGap(),
            str_contains($userText, 'analisis jurnal') => $this->sampleDocumentAnalysis(),
            str_contains($userText, 'asisten metodologi penelitian') => $this->sampleMethodology(),
            str_contains($userText, 'bagian:') => $this->sampleSection($this->sectionName($userText)),
            default => 'Ini adalah balasan contoh dari provider \'null\' (belum ada AI provider aktif). '
                .'Set AI_PROVIDER dan API key di .env untuk mengaktifkan AI sungguhan.',
        };

        return new AiResult(
            text: $text,
            inputTokens: 0,
            outputTokens: mb_strlen($text) / 4,
            responseTime: 5,
        );
    }

    public function embed(string $text): array
    {
        // vektor deterministik sederhana agar flow RAG tetap bisa dites.
        $hash = crc32($text);
        $vec = [];
        for ($i = 0; $i < 8; $i++) {
            $vec[] = (float) (fmod($hash + $i * 7919, 100) / 100);
        }
        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vec)));

        return array_map(fn ($v) => $v / $norm, $vec);
    }

    public function name(): string
    {
        return 'null';
    }

    public function model(): string
    {
        return 'null-local';
    }

    private function sampleTitles(): string
    {
        return json_encode([
            ['title' => 'Pengaruh Penerapan Artificial Intelligence terhadap Kinerja UMKM di Kota Surabaya', 'relevance' => 92, 'novelty' => 81, 'feasibility' => 88, 'complexity' => 72, 'gap_score' => 84],
            ['title' => 'Analisis Adopsi Teknologi Digital pada UMKM: Studi Kasus Sektor Kuliner Surabaya', 'relevance' => 88, 'novelty' => 78, 'feasibility' => 90, 'complexity' => 68, 'gap_score' => 80],
            ['title' => 'Peran Kecerdasan Buatan dalam Transformasi Digital UMKM di Jawa Timur', 'relevance' => 85, 'novelty' => 74, 'feasibility' => 86, 'complexity' => 70, 'gap_score' => 76],
            ['title' => 'Implementasi Chatbot AI untuk Layanan Pelanggan UMKM: Perspektif Penerimaan Teknologi', 'relevance' => 90, 'novelty' => 83, 'feasibility' => 82, 'complexity' => 74, 'gap_score' => 86],
            ['title' => 'Pengaruh Literasi Digital dan Adopsi AI terhadap Daya Saing UMKM Surabaya', 'relevance' => 87, 'novelty' => 79, 'feasibility' => 89, 'complexity' => 66, 'gap_score' => 82],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function sampleTitleAnalysis(): string
    {
        return json_encode([
            'relevance' => 89,
            'novelty' => 77,
            'feasibility' => 85,
            'complexity' => 71,
            'gap_score' => 82,
            'research_gap' => 'Penelitian sebelumnya banyak membahas adopsi teknologi pada perusahaan besar, belum banyak yang menyoroti UMKM di kota kecil.',
            'problem_statement' => 'Belum jelasnya dampak penerapan AI terhadap kinerja UMKM di Kota Surabaya.',
            'variables' => ['Penerapan AI', 'Kinerja UMKM'],
            'method' => 'Kuantitatif dengan regresi linear berganda.',
            'recommendation' => 'Judul layak dilanjutkan, tetapi rumusan variabel sebaiknya dipersempit agar bisa diukur.',
            'risk' => 'Data kinerja UMKM sering tidak lengkap, siapkan alternatif sumber data primer.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function sampleSection(string $nama): string
    {
        return "### {$nama}\n\n"
            ."Ini adalah contoh draft untuk bagian {$nama}. Tulisan ini dihasilkan oleh provider 'null' "
            ."dan belum dihasilkan oleh model AI yang sebenarnya. Setelah Anda mengaktifkan AI provider di "
            .".env, bagian ini akan diisi dengan konten yang sesuai dengan topik penelitian Anda.\n\n"
            ."Sesuaikan dengan pedoman penulisan kampus Anda sebelum melanjutkan ke bagian berikutnya.";
    }

    private function sampleReview(): string
    {
        return json_encode([
            'scores' => ['clarity' => 87, 'writing' => 91, 'problem' => 72, 'gap' => 68, 'consistency' => 84, 'methodology' => 78, 'citation' => 75, 'structure' => 88],
            'summary' => 'Draft sudah cukup terstruktur. Rumusan masalah belum sepenuhnya sejalan dengan tujuan penelitian.',
            'recommendations' => ['Perjelas keterkaitan rumusan masalah dengan tujuan penelitian.', 'Perkuat argumentasi pada pemilihan metode.'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function sampleParagraphReview(): string
    {
        return json_encode([
            'score' => 72,
            'issue' => 'Paragraf tidak memiliki kalimat topik yang jelas.',
            'suggestion' => 'Mulai paragraf dengan kalimat utama yang menyatakan inti argumen.',
            'reason' => 'Pembaca kesulitan menangkap gagasan utama karena kalimat pertama langsung menyajikan detail tanpa konteks.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function sampleSemproQuestions(): string
    {
        return json_encode([
            ['category' => 'metodologi', 'question' => 'Mengapa Anda memilih metode kuantitatif untuk penelitian ini?', 'expected_points' => 'Menjelaskan alasan pemilihan metode, kaitannya dengan rumusan masalah, serta alternatif metode yang dipertimbangkan.'],
            ['category' => 'landasan teori', 'question' => 'Jelaskan teori utama yang mendasari penelitian Anda beserta relevansinya.', 'expected_points' => 'Sebutkan teori, konsep kunci, dan kaitannya dengan variabel penelitian.'],
            ['category' => 'hasil', 'question' => 'Bagaimana Anda memastikan keabsahan data yang digunakan?', 'expected_points' => 'Menjelaskan teknik validasi data, sumber data, dan langkah pengendalian kualitas data.'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function sampleEvaluation(): string
    {
        return json_encode([
            'concept' => 84,
            'relevance' => 88,
            'argumentation' => 76,
            'methodology' => 80,
            'clarity' => 86,
            'confidence' => 82,
            'score' => 83,
            'feedback' => 'Jawaban sudah mengarah ke inti pertanyaan. Alasan pemilihan metode bisa diperkuat dengan membandingkan satu alternatif metode lain.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function sampleResearchGap(): string
    {
        return json_encode([
            'gaps' => [
                'Sebagian besar studi membahas perusahaan besar, objek UMKM masih jarang.',
                'Belum ada yang menggabungkan variabel literasi digital dan adopsi AI dalam satu model.',
            ],
            'opportunities' => [
                'Meneliti UMKM sektor kuliner di kota menengah.',
                'Membandingkan efek adopsi AI antar generasi pelaku usaha.',
            ],
            'summary' => 'Research gap utama ada pada konteks objek dan kombinasi variabel yang belum banyak diteliti.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function sampleDocumentAnalysis(): string
    {
        return json_encode([
            'title' => 'Contoh Jurnal Analisis',
            'author' => 'Nama Penulis',
            'year' => 2024,
            'purpose' => 'Menguji pengaruh adopsi AI terhadap kinerja UMKM.',
            'method' => 'Survei kuantitatif dengan 200 responden.',
            'dataset' => 'Data primer kuesioner.',
            'result' => 'Adopsi AI berpengaruh positif signifikan terhadap kinerja.',
            'limitations' => 'Sampel terbatas pada satu kota.',
            'research_gap' => 'Belum diuji pada sektor jasa.',
            'relevance' => 'Relevan sebagai rujukan metodologi dan variabel.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /** Balasan contoh untuk asisten metodologi penelitian. */
    private function sampleMethodology(): string
    {
        return "Pemilihan metode harus mengikuti bentuk pertanyaan penelitian Anda. "
            ."Kalau pertanyaannya mengukur pengaruh antar variabel dengan sampel besar, metode kuantitatif "
            ."dengan regresi lebih tepat. Kalau pertanyaannya menggali alasan dan proses, kualitatif dengan "
            ."wawancara mendalam lebih sesuai.\n\n"
            ."Contoh: penelitian tentang adopsi AI pada UMKM cocok memakai kuantitatif bila Anda ingin menguji "
            ."sejauh mana AI menaikkan penjualan. Tetapi kalau fokusnya memahami kenapa sebagian pelaku usaha "
            ."menolak AI, wawancara semi-terstruktur dengan analisis tematik akan lebih menjawab.\n\n"
            ."Catatan: ini balasan contoh dari provider null. Aktifkan AI provider di .env untuk jawaban sungguhan.";
    }

    /** Balasan contoh untuk pemeriksaan konsistensi skripsi. */
    private function sampleConsistency(): string
    {
        return json_encode([
            'consistent' => true,
            'issues' => [],
            'suggestions' => [],
            'summary' => 'Judul, rumusan masalah, tujuan, dan metode sudah saling mendukung. '
                .'Rumusan masalah tentang pengaruh AI terhadap kinerja UMKM dijawab oleh tujuan yang mengukur '
                .'peningkatan kinerja, dan metode kuantitatif dengan survei cocok untuk menguji hubungan tersebut.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /** Ambil nama bagian dari prompt draft, mis. "Bagian: BAB I Latar Belakang". */
    private function sectionName(string $prompt): string
    {
        if (preg_match('/bagian:\s*(.+)/i', $prompt, $m)) {
            return trim(mb_substr($m[1], 0, 80));
        }

        return 'Bagian Dokumen';
    }

}