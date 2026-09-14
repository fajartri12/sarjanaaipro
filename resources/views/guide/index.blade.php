@extends('layouts.app')

@section('title', 'Buku Panduan')

@php
    use App\Support\Labels;

    $rupiah = fn ($value) => Labels::rupiah($value);

    /**
     * Alur utama mengikuti urutan kerja nyata: bikin project dulu, baru
     * judul, bahan, menulis, nilai, lalu ekspor. Nomor langkah dipakai
     * sebagai anchor supaya bisa ditaut dari halaman lain.
     */
    $flow = [
        [
            'id' => 'project',
            'icon' => 'folder',
            'title' => 'Buat project',
            'lede' => 'Project adalah wadah satu penelitian. Semua judul, referensi, draft, dan latihan sempro menempel ke sini.',
            'steps' => [
                'Buka <b>Project</b> di sidebar, lalu klik <b>Project baru</b>.',
                'Isi <b>Nama project</b> untuk membedakan antar project. Nama ini tidak tercetak di dokumen.',
                'Isi <b>Program studi</b>, <b>Universitas</b>, dan <b>Dosen pembimbing</b> — dipakai di cover export.',
                'Pilih <b>Jenjang</b>. Ini yang menentukan kerangka BAB, istilah dokumen (skripsi/tesis/disertasi), dan gelar di cover.',
                'Pilih <b>Jenis penelitian</b> dan <b>Metode analisis</b> untuk memberi konteks ke AI.',
                'Opsional: isi <b>Target selesai</b> untuk dapat pengingat otomatis di H-7, H-3, dan H-1.',
                'Opsional: tulis <b>Catatan awal</b> dan pilih <b>Format kampus</b>.',
            ],
            'note' => 'Begitu project dibuat, kerangka BAB I–V langsung terbentuk otomatis sesuai jenjang. Anda tidak perlu menyusun daftar isi manual.',
        ],
        [
            'id' => 'judul',
            'icon' => 'sparkles',
            'title' => 'Tentukan judul',
            'lede' => 'Kalau judul sudah ada dari pembimbing, isi manual di form project. Kalau masih mencari, pakai generator judul.',
            'steps' => [
                'Buka menu <b>Judul</b>.',
                'Isi topik, objek, dan lokasi penelitian. Metode dan kata kunci boleh dikosongkan.',
                'AI mengembalikan beberapa alternatif judul, masing-masing dengan skor.',
                'Klik salah satu judul untuk melihat <b>analisis mendalam</b> sebelum memutuskan.',
                'Klik <b>Pilih</b> pada judul yang final. Judul itu otomatis menjadi judul project.',
            ],
            'scores' => [
                ['Relevansi', 'Kesesuaian judul dengan bidang studi Anda.'],
                ['Kebaruan', 'Seberapa berbeda dari riset yang sudah ada.'],
                ['Kelayakan', 'Kemudahan akses data dan pengerjaan metodologinya.'],
                ['Kompleksitas', 'Tingkat kerumitan pengerjaan. Skor moderat biasanya paling aman.'],
                ['Kekuatan gap', 'Seberapa jelas celah riset yang ingin diisi.'],
            ],
            'note' => 'Semua skor 0–100 dan hanya alat bantu menimbang. Keputusan akhir tetap milik Anda dan dosen pembimbing.',
        ],
        [
            'id' => 'bahan',
            'icon' => 'book',
            'title' => 'Kumpulkan bahan & referensi',
            'lede' => 'Draft yang kuat berdiri di atas bacaan yang cukup. Ada dua jalur: cari jurnal baru, atau unggah PDF yang sudah Anda punya.',
            'groups' => [
                [
                    'title' => 'Cari jurnal',
                    'icon' => 'search',
                    'steps' => [
                        'Buka <b>Cari Jurnal</b>, masukkan kata kunci.',
                        'Hasil diambil dari CrossRef — metadata penulis, tahun, dan jurnal sudah lengkap.',
                        'Simpan hasil yang relevan. Judul yang sama tidak akan tersimpan dua kali.',
                    ],
                ],
                [
                    'title' => 'Unggah PDF',
                    'icon' => 'document',
                    'steps' => [
                        'Buka <b>Jurnal Tersimpan</b>, unggah file PDF.',
                        'Status dokumen berubah: <b>Menunggu</b> → <b>Diproses</b> → <b>Siap</b>. Tunggu sampai <b>Siap</b> sebelum dipakai.',
                        'Setelah siap, klik <b>Analisis</b> supaya AI merangkum tujuan, metode, hasil, dan keterbatasannya.',
                        'Pakai <b>Tanya isi dokumen</b> untuk mengajukan pertanyaan langsung ke isi PDF tanpa membaca ulang.',
                    ],
                ],
                [
                    'title' => 'Kelola referensi',
                    'icon' => 'link',
                    'steps' => [
                        'Buka <b>Referensi</b> untuk melihat semua sumber yang tersimpan.',
                        'Tambah manual, atau tempel satu <b>DOI</b> supaya metadata terisi otomatis.',
                        'Pilih gaya sitasi: <b>APA</b>, <b>IEEE</b>, atau <b>Harvard</b>.',
                    ],
                ],
            ],
            'note' => 'Analisis PDF termasuk fitur berbayar. Paket Free tidak mendapat kuota analisis PDF sama sekali.',
        ],
        [
            'id' => 'chat',
            'icon' => 'chat',
            'title' => 'Tanya AI soal project Anda',
            'lede' => 'Chat di dalam project bukan chatbot umum. AI sudah tahu judul, program studi, dan metode penelitian ini.',
            'steps' => [
                'Buka halaman project, klik tombol <b>Chat AI</b> di kanan atas.',
                'Buat percakapan baru. Pisahkan topik — misalnya satu percakapan untuk metodologi, satu untuk teori.',
                'Ajukan pertanyaan. AI menjawab spesifik untuk konteks project ini, bukan nasihat umum.',
                'Hapus percakapan yang tidak dipakai lagi supaya daftar tetap ringkas.',
            ],
            'note' => 'Percakapan mengingat 20 pesan terakhir. Kalau mulai melebar, buat percakapan baru dengan topik yang lebih sempit.',
        ],
        [
            'id' => 'draft',
            'icon' => 'document',
            'title' => 'Tulis draft BAB I–V',
            'lede' => 'Ini inti pekerjaannya. Kerangka sudah tersedia; tugas Anda mengisi tiap bagian dan memperbaikinya.',
            'steps' => [
                'Buka <b>Buka draft</b> dari halaman project, atau <b>Lanjut menulis</b> dari dashboard.',
                'Kerjakan berurutan mulai <b>BAB I</b>. Mengisi BAB I dulu membuat bagian berikutnya lebih mudah diturunkan.',
                'Klik satu bagian untuk membuka editornya.',
                'Minta AI mengisi bagian itu, lalu <b>baca dan perbaiki</b>. Jangan langsung diterima apa adanya.',
                'Perubahan tersimpan otomatis — lihat penanda <b>Tersimpan</b> di editor. Butuh kembali ke tulisan lama? Buka <b>Riwayat versi</b>, lalu pulihkan versi mana pun.',
            ],
            'scores' => [
                ['Perbaiki', 'Merapikan kualitas bahasa dan alur argumentasi.'],
                ['Perluas', 'Menambah penjelasan pada bagian yang masih tipis.'],
                ['Padatkan', 'Memadatkan tulisan yang bertele-tele.'],
                ['Formalkan', 'Mengubah ke bahasa akademik formal.'],
                ['Lanjutkan', 'Menyambung tulisan yang terputus di tengah.'],
                ['Jelaskan', 'Menyederhanakan kalimat yang sulit dipahami.'],
            ],
            'statuses' => [
                ['Kosong', 'gray', 'Bagian ini belum ada isinya.'],
                ['Draft', 'amber', 'Sudah ada tulisan, tapi masih di bawah 150 kata.'],
                ['Selesai', 'green', 'Sudah 150 kata atau lebih.'],
            ],
            'note' => 'Angka 150 kata adalah ambang penanda, bukan standar baku. Bagian yang sudah "Selesai" tetap bisa Anda perbaiki lagi.',
        ],
        [
            'id' => 'sitasi',
            'icon' => 'link',
            'title' => 'Sematkan sitasi di dalam naskah',
            'lede' => 'Referensi yang sudah terkumpul bisa langsung disisipkan ke bagian tertentu, lengkap dengan sitasi dalam teks.',
            'steps' => [
                'Buka editor bagian draft yang ingin diberi sitasi.',
                'Pilih <b>sitasi</b> dari daftar referensi project.',
                'Tentukan posisi — awal, tengah, atau akhir bagian.',
                'Sitasi masuk sesuai gaya yang Anda pilih sebelumnya (APA, IEEE, atau Harvard).',
            ],
            'note' => 'Satu referensi tidak bisa disitasi dua kali di bagian yang sama. Sistem mencegahnya supaya daftar pustaka tetap bersih.',
        ],
        [
            'id' => 'review',
            'icon' => 'clipboard-check',
            'title' => 'Nilai dan rapikan sebelum ke dosen',
            'lede' => 'Dua alat berbeda: Reviewer menilai keseluruhan naskah, simulasi Sempro melatih cara Anda menjawab.',
            'groups' => [
                [
                    'title' => 'Reviewer naskah',
                    'icon' => 'clipboard-check',
                    'steps' => [
                        'Buka <b>Reviewer</b> dan pilih project.',
                        'Jalankan <b>Review</b> untuk menilai seluruh draft sekaligus.',
                        'Atau tinjau per bagian kalau ingin fokus ke BAB tertentu.',
                        'Pakai <b>Cek konsistensi</b> untuk memastikan judul, rumusan masalah, tujuan, dan metode saling nyambung.',
                    ],
                ],
                [
                    'title' => 'Deteksi masalah',
                    'icon' => 'compass',
                    'steps' => [
                        'Dari <b>Jurnal Tersimpan</b>, jalankan pemetaan <b>research gap</b> atas beberapa dokumen sekaligus.',
                        'Manfaatkan <b>asisten metodologi</b> kalau ragu memilih pendekatan analisis.',
                    ],
                ],
            ],
            'scores' => [
                ['Kejelasan', 'Seberapa mudah tulisan dipahami.'],
                ['Tulisan', 'Kualitas bahasa dan tata tulis.'],
                ['Rumusan masalah', 'Ketajaman masalah yang diangkat.'],
                ['Research gap', 'Seberapa jelas celah risetnya.'],
                ['Konsistensi', 'Kesinambungan antar bagian naskah.'],
                ['Metodologi', 'Ketepatan metode dengan masalah yang diangkat.'],
                ['Sitasi', 'Kualitas dan kecukupan rujukan.'],
                ['Struktur', 'Kerapian susunan dokumen.'],
            ],
            'note' => 'Reviewer butuh minimal satu bagian draft yang berisi tulisan. Naskah kosong tidak bisa dinilai.',
        ],
        [
            'id' => 'sempro',
            'icon' => 'cap',
            'title' => 'Latihan sidang sempro',
            'lede' => 'AI berperan sebagai dosen penguji dan menanyai proposal Anda. Setiap jawaban dinilai.',
            'steps' => [
                'Buka <b>Simulasi Sempro</b>, mulai sesi baru. Jumlah pertanyaan bisa diatur antara 3–10, bawaan 5.',
                'Jawab satu per satu. Jawaban dinilai dari beberapa aspek sekaligus.',
                'Selesaikan sesi untuk melihat skor akhir dan rinciannya.',
                'Baca catatan perbaikan, lalu ulangi latihan setelah proposal direvisi.',
            ],
            'aspects' => [
                ['Pemahaman konsep', 'Ketepatan konsep yang Anda jelaskan.'],
                ['Relevansi jawaban', 'Seberapa nyambung jawaban dengan pertanyaannya.'],
                ['Kekuatan argumen', 'Kualitas alasan yang Anda bangun.'],
                ['Metodologi', 'Pemahaman atas metode yang dipakai.'],
                ['Kejelasan', 'Kerapian cara Anda menyampaikan.'],
                ['Kepercayaan diri', 'Seberapa mantap penyampaian Anda.'],
            ],
            'thresholds' => [
                ['80–100', 'green', 'Siap menghadapi sempro. Pertahankan dan latih sekali dua kali lagi.'],
                ['65–79', 'amber', 'Sudah cukup baik. Rapikan bagian yang masih lemah, lalu ulangi latihan.'],
                ['0–64', 'rose', 'Masih perlu banyak perbaikan. Baca ulang proposal dan latih jawaban Anda.'],
            ],
        ],
        [
            'id' => 'export',
            'icon' => 'download',
            'title' => 'Ekspor naskah',
            'lede' => 'Draft yang sudah jadi dirapikan menjadi satu dokumen siap cetak.',
            'steps' => [
                'Buka halaman draft project.',
                'Pilih <b>Export PDF</b> atau <b>Export DOCX</b>.',
                'Dokumen berisi cover, daftar isi dengan nomor halaman, seluruh BAB, dan daftar pustaka dalam satu file.',
                'Format mengikuti <b>Format kampus</b> yang dipilih saat membuat project — huruf, spasi, margin, dan penomoran halaman.',
            ],
            'note' => 'Export DOCX (Word) hanya untuk paket Student ke atas. Paket Free tetap bisa export PDF. Kalau tombol DOCX tergembok, klik untuk melihat pilihan paket.',
        ],
        [
            'id' => 'notif',
            'icon' => 'bell',
            'title' => 'Pencarian & notifikasi',
            'lede' => 'Dua alat lintas-halaman yang membantu Anda tetap teratur.',
            'steps' => [
                'Gunakan <b>Pencarian</b> di pojok kanan atas untuk mencari project, draft, atau jurnal tanpa perlu membuka menu satu per satu.',
                'Klik <b>Lonceng notifikasi</b> di samping pencarian untuk melihat pengingat deadline, pemberitahuan selesai proses, dan info paket.',
            ],
        ],
    ];

    $faq = [
        ['Apakah AI menulis penelitian saya dari nol?', 'Tidak. AI merumuskan, mengisi draft, dan menyarankan perbaikan. Anda tetap yang menulis dan memutuskan isi akhir — itu yang menjaga orisinalitas penelitian Anda.'],
        ['Seberapa akurat keluaran AI?', 'Tidak selalu akurat. Sistem sengaja menandai bagian yang butuh verifikasi (misalnya penanda seperti <code>[data perlu diisi]</code>). Selalu periksa ulang ke sumber asli dan dosen pembimbing.'],
        ['Kuota AI habis di tengah bulan, bagaimana?', 'Kuota direset tiap awal bulan. Kalau perlu lanjut sekarang, naikkan paket — atau tunggu reset.'],
        ['Bisakah pindah paket kapan saja?', 'Bisa. Upgrade langsung aktif. Downgrade berlaku mulai periode berikutnya.'],
        ['Apa bedanya jenjang S1, S2, dan S3?', 'Jenjang menentukan kerangka BAB yang dibuat otomatis, istilah dokumen (skripsi/tesis/disertasi), dan gelar yang tercetak di cover.'],
        ['Apakah data penelitian saya aman?', 'Ya. Project dan dokumen hanya bisa diakses oleh pemiliknya. Data tidak dibagikan ke pihak ketiga.'],
        ['Di mana saya bisa melihat pemakaian kuota?', 'Buka halaman <b>Kuota</b> untuk rincian per fitur, tren enam bulan, dan perkiraan biaya.'],
        ['Bagaimana kalau saya tidak paham pesan error?', 'Kebanyakan error berasal dari kuota habis atau dokumen belum selesai diproses. Cek status di halaman <b>Kuota</b> dulu, lalu ulangi aksinya.'],
    ];
@endphp

@section('header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-2xl">
            <p class="ui-eyebrow">Panduan pemakaian</p>
            <h1 class="mt-1 ui-page-title">Buku panduan</h1>
            <p class="ui-page-sub">
                Urutan kerja dari nol sampai naskah siap cetak. Ikuti berurutan kalau ini project pertama Anda.
            </p>
        </div>
        <a href="{{ route('projects.create') }}" class="ui-btn-primary ui-btn-sm">
            @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
            Mulai project
        </a>
    </div>
@endsection

@section('content')
    {{-- Daftar isi --}}
    <nav class="ui-card p-5" aria-label="Daftar isi panduan">
        <p class="ui-eyebrow">Isi panduan</p>
        <ol class="mt-3 grid gap-x-6 gap-y-1.5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($flow as $i => $item)
                <li>
                    <a href="#{{ $item['id'] }}"
                       class="group flex items-center gap-2 rounded-lg px-2 py-1.5 text-[13px] text-gray-600 transition hover:bg-blue-50/70 hover:text-blue-700">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-md bg-gray-100 text-[11px] font-semibold tabular-nums text-gray-500 transition group-hover:bg-blue-600 group-hover:text-white">
                            {{ $i + 1 }}
                        </span>
                        <span class="truncate">{{ $item['title'] }}</span>
                    </a>
                </li>
            @endforeach
            <li>
                <a href="#paket"
                   class="group flex items-center gap-2 rounded-lg px-2 py-1.5 text-[13px] text-gray-600 transition hover:bg-blue-50/70 hover:text-blue-700">
                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-md bg-gray-100 text-gray-500 transition group-hover:bg-blue-600 group-hover:text-white">
                        @include('partials.icon', ['name' => 'tag', 'size' => 'h-3 w-3'])
                    </span>
                    <span class="truncate">Paket & kuota</span>
                </a>
            </li>
            <li>
                <a href="#tanya"
                   class="group flex items-center gap-2 rounded-lg px-2 py-1.5 text-[13px] text-gray-600 transition hover:bg-blue-50/70 hover:text-blue-700">
                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-md bg-gray-100 text-gray-500 transition group-hover:bg-blue-600 group-hover:text-white">
                        @include('partials.icon', ['name' => 'chat', 'size' => 'h-3 w-3'])
                    </span>
                    <span class="truncate">Pertanyaan umum</span>
                </a>
            </li>
        </ol>
    </nav>

    {{-- Alur utama --}}
    @foreach ($flow as $i => $item)
        <section id="{{ $item['id'] }}" class="ui-card scroll-mt-20">
            <div class="ui-card-head">
                <div class="flex min-w-0 items-start gap-3.5">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-600 text-[15px] font-bold tabular-nums text-white">
                        {{ $i + 1 }}
                    </span>
                    <div class="min-w-0">
                        <h2 class="ui-card-title flex items-center gap-2">
                            @include('partials.icon', ['name' => $item['icon'], 'size' => 'h-4 w-4 text-gray-400'])
                            {{ $item['title'] }}
                        </h2>
                        <p class="ui-card-sub">{{ $item['lede'] }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-5 p-5 sm:p-6">
                @isset($item['steps'])
                    <ol class="space-y-2.5">
                        @foreach ($item['steps'] as $n => $step)
                            <li class="flex gap-3 text-[13px] leading-relaxed text-gray-700">
                                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-gray-100 text-[11px] font-semibold tabular-nums text-gray-500">
                                    {{ $n + 1 }}
                                </span>
                                <span>{!! $step !!}</span>
                            </li>
                        @endforeach
                    </ol>
                @endisset

                @isset($item['groups'])
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($item['groups'] as $group)
                            <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                                <p class="flex items-center gap-2 text-[13px] font-semibold text-gray-900">
                                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-white text-blue-600 shadow-sm">
                                        @include('partials.icon', ['name' => $group['icon'], 'size' => 'h-3.5 w-3.5'])
                                    </span>
                                    {{ $group['title'] }}
                                </p>
                                <ol class="mt-3 space-y-2">
                                    @foreach ($group['steps'] as $n => $step)
                                        <li class="flex gap-2.5 text-[13px] leading-relaxed text-gray-600">
                                            <span class="mt-0.5 shrink-0 font-semibold tabular-nums text-gray-400">{{ $n + 1 }}.</span>
                                            <span>{!! $step !!}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endforeach
                    </div>
                @endisset

                {{-- Skor / aksi / aspek --}}
                @foreach (['scores' => 'Yang dinilai', 'aspects' => 'Aspek penilaian'] as $key => $heading)
                    @isset($item[$key])
                        <div>
                            <p class="ui-eyebrow">{{ $heading }}</p>
                            <dl class="mt-2.5 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                                @foreach ($item[$key] as [$term, $desc])
                                    <div class="flex gap-2 text-[13px] leading-relaxed">
                                        <dt class="w-36 shrink-0 font-medium text-gray-900">{{ $term }}</dt>
                                        <dd class="min-w-0 text-gray-600">{{ $desc }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endisset
                @endforeach

                {{-- Status badge --}}
                @isset($item['statuses'])
                    <div>
                        <p class="ui-eyebrow">Arti status bagian</p>
                        <div class="mt-2.5 space-y-2">
                            @foreach ($item['statuses'] as [$label, $tone, $desc])
                                <div class="flex flex-wrap items-center gap-2.5 text-[13px]">
                                    @include('partials.badge', ['tone' => $tone, 'label' => $label])
                                    <span class="text-gray-600">{{ $desc }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endisset

                {{-- Ambang skor sempro --}}
                @isset($item['thresholds'])
                    <div>
                        <p class="ui-eyebrow">Arti skor akhir</p>
                        <div class="mt-2.5 space-y-2">
                            @foreach ($item['thresholds'] as [$range, $tone, $desc])
                                <div class="flex flex-wrap items-center gap-2.5 text-[13px]">
                                    <span class="w-20 shrink-0">@include('partials.badge', ['tone' => $tone, 'label' => $range])</span>
                                    <span class="min-w-0 flex-1 text-gray-600">{{ $desc }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endisset

                @isset($item['note'])
                    <p class="flex gap-2.5 rounded-xl bg-amber-50 px-4 py-3 text-[13px] leading-relaxed text-amber-900">
                        <span class="shrink-0 text-amber-500">
                            @include('partials.icon', ['name' => 'warning', 'size' => 'h-4 w-4'])
                        </span>
                        <span>{!! $item['note'] !!}</span>
                    </p>
                @endisset
            </div>
        </section>
    @endforeach

    {{-- Paket & kuota --}}
    <section id="paket" class="ui-card scroll-mt-20">
        <div class="ui-card-head">
            <div>
                <h2 class="ui-card-title">Paket & kuota</h2>
                <p class="ui-card-sub">Kuota AI direset tiap awal bulan. Rincian pemakaian ada di halaman Kuota.</p>
            </div>
            <a href="{{ route('quota') }}" class="ui-btn-secondary ui-btn-xs">Lihat kuota saya</a>
        </div>
        <div class="grid gap-4 p-5 sm:p-6 lg:grid-cols-3">
            @foreach ($plans as $plan)
                <div class="rounded-xl border border-gray-100 p-4">
                    <p class="text-[13px] font-semibold text-gray-900">{{ $plan->name }}</p>
                    <p class="mt-1 flex items-baseline gap-1.5">
                        <span class="text-lg font-semibold tabular-nums tracking-tight text-gray-900">
                            {{ (int) $plan->price === 0 ? 'Gratis' : $rupiah($plan->price) }}
                        </span>
                        @if ((int) $plan->price > 0)
                            <span class="text-[13px] text-gray-500">/{{ $plan->interval === 'month' ? 'bulan' : $plan->interval }}</span>
                        @endif
                    </p>
                    <ul class="mt-3 space-y-1.5">
                        @foreach ($plan->features ?? [] as $feature)
                            <li class="flex gap-2 text-[13px] leading-relaxed text-gray-600">
                                <span class="mt-0.5 shrink-0 text-emerald-500">
                                    @include('partials.icon', ['name' => 'check', 'size' => 'h-3.5 w-3.5', 'stroke' => 2.4])
                                </span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <div class="border-t border-gray-100 px-5 py-4 sm:px-6">
            <a href="{{ route('subscription.prices') }}" class="ui-link text-[13px]">Bandingkan paket lengkap & naikkan paket →</a>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="tanya" class="scroll-mt-20">
        <div class="max-w-2xl">
            <h2 class="ui-page-title">Pertanyaan umum</h2>
            <p class="ui-page-sub">Hal yang paling sering ditanyakan pengguna baru.</p>
        </div>
        <div class="mt-5 space-y-3">
            @foreach ($faq as [$q, $a])
                <details class="ui-card group p-5">
                    <summary class="flex cursor-pointer items-center justify-between gap-4 text-[13px] font-medium text-gray-900">
                        {{ $q }}
                        <span class="shrink-0 text-gray-400 transition group-open:rotate-180">
                            @include('partials.icon', ['name' => 'chevron-down', 'size' => 'h-4 w-4'])
                        </span>
                    </summary>
                    <p class="mt-3 text-[13px] leading-relaxed text-gray-600">{!! $a !!}</p>
                </details>
            @endforeach
        </div>
    </section>

    <p class="text-[13px] leading-relaxed text-gray-500">
        Masih bingung? Hubungi
        <a href="mailto:support@sarjanaai.test" class="ui-link">support@sarjanaai.test</a>
        dengan menyebut nama project dan halaman yang bermasalah.
    </p>
@endsection
