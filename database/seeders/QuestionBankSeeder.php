<?php

namespace Database\Seeders;

use App\Models\QuestionBank;
use Illuminate\Database\Seeder;

/**
 * Bank pertanyaan sempro. Pertanyaan ini dipakai sebagai titik awal
 * sebelum AI melengkapi dengan pertanyaan spesifik project.
 *
 * `method = null` berarti pertanyaan berlaku untuk semua metode.
 * Tambah pertanyaan baru di sini — tidak perlu ubah kode lain.
 */
class QuestionBankSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // ---------- Rumusan masalah & tujuan (semua metode) ----------
            ['rumusan masalah', null, 'bab1', 'dasar',
                'Apa rumusan masalah utama penelitian Anda, dan mengapa masalah itu penting untuk diteliti sekarang?',
                'Rumusan masalah jelas, alasan urgensi (gap, dampak, kebaruan).', 'Sebut satu masalah utama, bukan daftar panjang.'],
            ['rumusan masalah', null, 'bab1', 'menengah',
                'Bagaimana Anda memastikan rumusan masalah tidak terlalu luas sehingga bisa diselesaikan dalam waktu penelitian?',
                'Batasan masalah, ruang lingkup, variabel yang dipilih.', 'Bandingkan dengan judul: apakah semua variabel di judul sudah masuk rumusan masalah?'],
            ['rumusan masalah', null, 'bab1', 'sulit',
                'Kalau penguji meminta Anda membuang salah satu rumusan masalah, mana yang Anda buang dan mengapa?',
                'Prioritas masalah, alasan logis, dampak ke tujuan penelitian.', 'Uji apakah tiap rumusan masalah benar-benar perlu.'],
            ['tujuan', null, 'bab1', 'dasar',
                'Apa tujuan penelitian Anda, dan bagaimana tujuan itu menjawab rumusan masalah?',
                'Tujuan sejalan satu-satu dengan rumusan masalah.', 'Cek jumlah: tiap rumusan masalah biasanya punya satu tujuan.'],
            ['tujuan', null, 'bab1', 'menengah',
                'Apa manfaat praktis penelitian Anda bagi objek penelitian, bukan hanya bagi ilmu pengetahuan?',
                'Manfaat praktis konkret untuk objek/lokasi.', 'Hindari manfaat generik seperti "menambah wawasan".'],

            // ---------- Landasan teori & kebaruan ----------
            ['landasan teori', null, 'bab2', 'dasar',
                'Teori utama apa yang Anda pakai sebagai dasar, dan mengapa teori itu yang paling relevan?',
                'Nama teori, alasan pemilihan, keterkaitan dengan variabel.', 'Sebutkan minimal satu teori besar dan satu penelitian pendukung.'],
            ['landasan teori', null, 'bab2', 'menengah',
                'Apa perbedaan penelitian Anda dengan penelitian sebelumnya yang paling mirip?',
                'Kebaruan: objek, metode, variabel, atau konteks yang berbeda.', 'Buat tabel perbandingan kalau belum ada.'],
            ['landasan teori', null, 'bab2', 'sulit',
                'Kalau ada penelitian yang sudah menjawab pertanyaan ini, apa yang membuat penelitian Anda tetap layak dilakukan?',
                'Novelty, replikasi di konteks berbeda, kontribusi metodologis.', 'Jangan jawab "belum ada yang meneliti" tanpa bukti.'],

            // ---------- Metodologi: umum ----------
            ['metodologi', null, 'bab3', 'dasar',
                'Metode penelitian apa yang Anda gunakan, dan mengapa memilih metode itu?',
                'Nama metode, alasan kesesuaian dengan rumusan masalah.', 'Sebutkan pendekatan (kuantitatif/kualitatif) dan desainnya.'],
            ['metodologi', null, 'bab3', 'menengah',
                'Bagaimana Anda menentukan jumlah sampel atau informan, dan apa dasarnya?',
                'Teknik sampling, rumus atau kriteria, justifikasi.', 'Kalau pakai rumus, sebut nama rumusnya.'],
            ['metodologi', null, 'bab3', 'sulit',
                'Apa kelemahan metode yang Anda pilih, dan bagaimana Anda mengurangi dampaknya?',
                'Keterbatasan metode + mitigasi yang sudah dilakukan.', 'Jujur soal kelemahan justru menaikkan kredibilitas.'],

            // ---------- Metodologi: kuantitatif ----------
            ['metodologi', 'kuantitatif', 'bab3', 'menengah',
                'Bagaimana Anda menguji validitas dan reliabilitas instrumen penelitian?',
                'Uji validitas (korelasi/Pearson), uji reliabilitas (Cronbach alpha).', 'Sebut nilai minimum yang dipakai, biasanya 0,7.'],
            ['metodologi', 'kuantitatif', 'bab3', 'sulit',
                'Apa hipotesis penelitian Anda, dan bagaimana arah pengaruh antar variabelnya?',
                'H0/H1, arah (positif/negatif), dasar teori hipotesis.', 'Hipotesis harus punya dasar teori, bukan tebakan.'],
            ['metodologi', 'kuantitatif', 'bab3', 'sulit',
                'Teknik analisis data apa yang Anda pakai, dan mengapa bukan teknik lain?',
                'Nama uji (regresi, PLS, ANOVA), syarat uji terpenuhi.', 'Sebut uji asumsi klasik yang harus dilalui.'],

            // ---------- Metodologi: kualitatif ----------
            ['metodologi', 'kualitatif', 'bab3', 'menengah',
                'Bagaimana Anda menjaga keabsahan data kualitatif Anda?',
                'Triangulasi sumber/teknik, member checking, saturasi data.', 'Sebut minimal dua teknik triangulasi.'],
            ['metodologi', 'kualitatif', 'bab3', 'sulit',
                'Bagaimana Anda memastikan jumlah informan sudah cukup, dan tidak perlu ditambah lagi?',
                'Saturasi data, kriteria penghentian pengumpulan data.', 'Saturasi bukan "sudah lelah", harus ada kriteria.'],
            ['metodologi', 'kualitatif', 'bab3', 'sulit',
                'Bagaimana posisi Anda sebagai peneliti terhadap objek yang diteliti?',
                'Refleksivitas, bias peneliti, cara menjaga netralitas.', 'Penguji sering menanyakan ini untuk uji subjektivitas.'],

            // ---------- Metodologi: mixed / studi kasus / eksperimen ----------
            ['metodologi', 'studi kasus', 'bab3', 'menengah',
                'Mengapa satu kasus ini layak diteliti, dan apa yang membuatnya khas?',
                'Alasan pemilihan kasus, keunikan, keterwakilan.', 'Studi kasus tidak menuntut generalisasi statistik.'],
            ['metodologi', 'eksperimen', 'bab3', 'sulit',
                'Bagaimana Anda mengendalikan variabel pengganggu selama eksperimen?',
                'Kelompok kontrol, randomisasi, blind test.', 'Sebut variabel yang dikendalikan satu per satu.'],
            ['metodologi', 'mixed methods', 'bab3', 'sulit',
                'Bagaimana Anda menggabungkan temuan kuantitatif dan kualitatif jika keduanya saling bertentangan?',
                'Strategi integrasi temuan, urutan (sekuensial/konkuren).', 'Sebut desain mixed-nya: explanatory, exploratory, atau convergent.'],

            // ---------- Hasil & pembahasan ----------
            ['hasil', null, 'bab4', 'menengah',
                'Bagaimana Anda menafsirkan hasil penelitian, dan apakah hasilnya sesuai dugaan awal?',
                'Interpretasi temuan vs teori, penjelasan bila tidak sesuai.', 'Hasil yang tidak sesuai teori bukan kegagalan penelitian.'],
            ['hasil', null, 'bab4', 'sulit',
                'Apa keterbatasan penelitian Anda yang bisa memengaruhi kesimpulan?',
                'Keterbatasan sampel, waktu, alat ukur, generalisasi.', 'Sebutkan dampaknya ke kesimpulan, bukan hanya daftar keterbatasan.'],

            // ---------- Umum / kesiapan ----------
            ['umum', null, null, 'dasar',
                'Dalam satu menit, jelaskan penelitian Anda dari masalah sampai kesimpulan.',
                'Alur singkat: masalah, tujuan, metode, temuan, kesimpulan.', 'Latih dengan stopwatch, jangan improvisasi.'],
            ['umum', null, null, 'menengah',
                'Apa kontribusi penelitian Anda bagi pengembangan ilmu di bidang Anda?',
                'Kontribusi teoretis dan praktis, kaitan dengan penelitian sebelumnya.', 'Bedakan kontribusi teori dan kontribusi praktik.'],
            ['umum', null, null, 'sulit',
                'Jika ada peneliti yang ingin melanjutkan penelitian Anda, apa yang Anda sarankan?',
                'Rekomendasi penelitian lanjutan, celah yang belum terjawab.', 'Tunjuk celah yang Anda sendiri tidak sempat teliti.'],
        ];

        $position = 0;

        foreach ($rows as [$category, $method, $section, $difficulty, $question, $expected, $hint]) {
            QuestionBank::updateOrCreate(
                ['question' => $question],
                [
                    'category' => $category,
                    'method' => $method,
                    'section' => $section,
                    'difficulty' => $difficulty,
                    'expected_points' => $expected,
                    'hint' => $hint,
                    'position' => $position++,
                    'is_active' => true,
                ],
            );
        }
    }
}
