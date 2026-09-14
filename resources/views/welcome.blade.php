<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sarjana AI membantu mahasiswa menyusun penelitian: cari judul, tulis draft, baca jurnal, latihan sidang, dan export naskah.">
    <link rel="canonical" href="{{ url('/') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Sarjana AI — Pendamping Penulisan Ilmiah">
    <meta property="og:description" content="Dari memilih judul sampai sidang, kerjakan penelitian Anda dengan bantuan AI yang bisa diperiksa.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="Sarjana AI">
    <title>Sarjana AI — Pendamping Penulisan Ilmiah</title>
    @include('partials.fonts')
    @include('partials.theme-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 antialiased">
    @php $user = auth()->user(); @endphp

    {{-- ======================== NAVBAR ======================== --}}
    <header class="sticky top-0 z-20 border-b border-gray-100 bg-white/85 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3.5 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-600 text-[13px] font-semibold tracking-tight text-white">SA</span>
                <span class="min-w-0">
                    <span class="block text-[13px] font-semibold leading-tight tracking-tight">Sarjana AI</span>
                    <span class="hidden text-[11px] leading-tight text-gray-500 sm:block">pendamping penelitian</span>
                </span>
            </a>
            <nav class="hidden items-center gap-6 text-[13px] font-medium text-gray-600 md:flex" aria-label="Navigasi utama">
                <a href="#fitur" class="hover:text-blue-600">Fitur</a>
                <a href="#cara-kerja" class="hover:text-blue-600">Cara Kerja</a>
                <a href="#harga" class="hover:text-blue-600">Harga</a>
                <a href="#bantuan" class="hover:text-blue-600">Bantuan</a>
            </nav>
            <div class="flex items-center gap-2">
                <details class="relative md:hidden">
                    <summary class="grid h-9 w-9 cursor-pointer place-items-center rounded-lg border border-gray-200 text-gray-700 marker:content-none hover:bg-gray-50" aria-label="Buka menu navigasi">
                        @include('partials.icon', ['name' => 'menu', 'size' => 'h-5 w-5'])
                    </summary>
                    <nav class="absolute right-0 top-11 z-30 w-44 rounded-xl border border-gray-200 bg-white p-2 shadow-lg" aria-label="Navigasi mobile">
                        <a href="#fitur" class="block rounded-lg px-3 py-2 text-[13px] font-medium text-gray-700 hover:bg-gray-50">Fitur</a>
                        <a href="#cara-kerja" class="block rounded-lg px-3 py-2 text-[13px] font-medium text-gray-700 hover:bg-gray-50">Cara Kerja</a>
                        <a href="#harga" class="block rounded-lg px-3 py-2 text-[13px] font-medium text-gray-700 hover:bg-gray-50">Harga</a>
                        <a href="#bantuan" class="block rounded-lg px-3 py-2 text-[13px] font-medium text-gray-700 hover:bg-gray-50">Bantuan</a>
                    </nav>
                </details>
                @include('partials.theme-toggle', ['simple' => true])
                @if ($user)
                    <a href="{{ route('dashboard') }}" class="ui-btn-primary ui-btn-sm">
                        Buka dashboard
                        @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
                    </a>
                @else
                    <a href="{{ route('login') }}" class="ui-btn-ghost ui-btn-sm hidden sm:inline-flex">Masuk</a>
                    <a href="{{ route('register') }}" class="ui-btn-primary ui-btn-sm">Daftar gratis</a>
                @endif
            </div>
        </div>
    </header>

    {{-- ======================== HERO + DEMO ======================== --}}
    <section class="relative overflow-hidden">
        <div data-parallax="-0.10" class="pointer-events-none absolute -top-24 right-0 h-72 w-72 rounded-full bg-blue-50/70 blur-3xl" aria-hidden="true"></div>
        <div data-parallax="0.08" class="pointer-events-none absolute -bottom-24 left-0 h-72 w-72 rounded-full bg-violet-50/70 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-6xl px-5 pb-16 pt-14 sm:px-6 sm:pb-24 sm:pt-20">
            <div class="grid gap-10 lg:grid-cols-2 lg:gap-12">
                <div class="max-w-2xl">
                    <p class="ui-eyebrow">Untuk mahasiswa tingkat akhir</p>
                    <h1 class="mt-4 text-3xl font-semibold leading-[1.15] tracking-tight sm:text-4xl lg:text-5xl">
                        Penelitian bukan soal menulis cepat.<br />
                        Ini soal tahu langkah berikutnya.
                    </h1>
                    <p class="mt-6 max-w-xl text-[15px] leading-relaxed text-gray-600">
                        Dari memilih judul sampai sidang — Sarjana AI membantu di tiap tahap. Semua hasilnya bisa Anda periksa sendiri, bukan kotak hitam.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        @if ($user)
                            <a href="{{ route('dashboard') }}" class="ui-btn-primary">
                                Lanjutkan penelitian Anda
                                @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="ui-btn-primary">
                                Mulai gratis
                                @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
                            </a>
                        @endif
                        <a href="#cara-kerja" class="ui-btn-secondary">Lihat cara kerjanya</a>
                    </div>

                    {{-- Social proof: angka nyata dari database, bukan klaim karangan. --}}
                    <dl class="mt-8 flex flex-wrap items-center gap-x-8 gap-y-4 border-t border-gray-100 pt-6">
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-gray-400">Mahasiswa terdaftar</dt>
                            <dd class="mt-0.5 text-lg font-semibold tabular-nums tracking-tight">{{ number_format($studentCount, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-gray-400">Demo tanpa login</dt>
                            <dd class="mt-0.5 text-lg font-semibold tracking-tight">Gratis</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-gray-400">Bagian naskah didukung</dt>
                            <dd class="mt-0.5 text-lg font-semibold tabular-nums tracking-tight">20</dd>
                        </div>
                    </dl>
                </div>

                {{-- Demo card: cari judul --}}
                <div data-parallax="-0.025" class="ui-card p-5 sm:p-6">
                    <div class="mb-5 flex items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700">
                            @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-5 w-5'])
                        </span>
                        <div>
                            <h2 class="text-base font-semibold tracking-tight">Cari judul yang layak</h2>
                            <p class="text-xs text-gray-500">Masukkan topik — lihat 3 contoh judul dengan skor kelayakan.</p>
                        </div>
                    </div>

                    <form id="demo-form" class="space-y-4">
                        <div>
                            <label for="demo-topic" class="ui-label">Topik / Masalah Penelitian <span class="text-rose-500">*</span></label>
                            <input id="demo-topic" name="topic" type="text" required
                                   placeholder="Contoh: Pengaruh artificial intelligence terhadap UMKM"
                                   class="ui-input mt-1.5 text-sm">
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="demo-object" class="ui-label">Objek / Variabel Terikat</label>
                                <input id="demo-object" name="object" type="text" placeholder="Efisiensi operasional"
                                       class="ui-input mt-1.5 text-sm">
                            </div>
                            <div>
                                <label for="demo-location" class="ui-label">Lokasi</label>
                                <input id="demo-location" name="location" type="text" placeholder="Kabupaten Jombang"
                                       class="ui-input mt-1.5 text-sm">
                            </div>
                        </div>
                        <div>
                            <label for="demo-method" class="ui-label">Metode</label>
                            <select id="demo-method" name="method" class="ui-input mt-1.5 text-sm">
                                <option value="">— Pilih atau biarkan kosong —</option>
                                <option value="Kuantitatif">Kuantitatif</option>
                                <option value="Kualitatif">Kualitatif</option>
                                <option value="Mixed Methods">Mixed Methods</option>
                                <option value="Studi Kasus">Studi Kasus</option>
                                <option value="Eksperimen">Eksperimen</option>
                            </select>
                        </div>
                        <button type="submit" id="demo-btn" class="ui-btn-primary w-full">
                            @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4'])
                            <span id="demo-btn-text">Judulkan</span>
                        </button>
                    </form>

                    <div id="demo-results" class="mt-5 hidden space-y-3"></div>
                    <p id="demo-hint" class="mt-4 text-[11px] text-gray-400 text-center">
                        Demo tanpa login. Daftar untuk analisis lengkap + 5 alternatif judul.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================== FITUR ======================== --}}
    <section id="fitur" class="border-y border-gray-100 bg-gray-50/70">
        <div class="mx-auto max-w-6xl px-5 py-14 sm:px-6 sm:py-16">
            <div class="max-w-2xl">
                <h2 class="text-xl font-semibold tracking-tight">Alat yang sesuai tahap Anda</h2>
                <p class="mt-2 text-[13px] leading-relaxed text-gray-600">
                    Tidak perlu semua sekaligus — pakai yang sedang Anda butuhkan, tinggalkan yang tidak.
                </p>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['sparkles', 'Cari judul yang layak', 'Masukkan topik, objek, lokasi — dapat 5 alternatif judul dengan skor relevansi, kebaruan, dan kelayakan. Tidak perlu menebak-nebak lagi.'],
                    ['document', 'Tulis draft BAB I–V', 'Kerangka 20 bagian sudah disiapkan. AI mengisi satu bagian sesuai gaya Anda — perbaiki, perluas, atau ganti kalimatnya.'],
                    ['book', 'Baca jurnal, tanya isinya', 'Unggah PDF. AI merangkum tujuan, metode, hasil, dan batasannya — Anda bisa bertanya langsung ke isi dokumen tanpa baca ulang 20 halaman.'],
                    ['link', 'Susun daftar pustaka', 'Tempel satu DOI, metadata terisi otomatis. Susun jadi daftar pustaka APA, IEEE, atau Harvard — lengkap dengan sitasi dalam teksnya.'],
                    ['cap', 'Latihan sidang proposal', 'AI bertanya seperti dosen penguji sungguhan. Setiap jawaban dinilai dari pemahaman konsep, argumen, dan metodologi — supaya Anda tidak kaget saat hari-H.'],
                    ['clipboard-check', 'Review sebelum dibaca dosen', 'Skor per aspek — kejelasan, rumusan masalah, metodologi, sitasi — plus daftar perbaikan yang harus diprioritaskan.'],
                    ['shield', 'Cek kalimat yang terpakai ulang', 'Bandingkan draft Anda dengan PDF yang diunggah dan dengan bagian lain di naskah sendiri. Setiap temuan disertai kutipan dan skor kemiripan, siap dilampirkan ke pembimbing sebagai PDF.'],
                    ['chat', 'Chat AI di dalam project', 'Tanya apa saja soal skripsi Anda. AI sudah tahu judul, program studi, dan metode project ini — jadi jawabannya spesifik, bukan nasihat umum.'],
                    ['folder', 'Export PDF & DOCX', 'Susun draft final ke siap cetak: cover, daftar isi, isi BAB per bab, dan daftar pustaka — dalam PDF atau Word (DOCX).'],
                ] as [$icon, $title, $body])
                    <div class="ui-card p-5">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                            @include('partials.icon', ['name' => $icon, 'size' => 'h-4.5 w-4.5'])
                        </span>
                        <h3 class="mt-4 text-[13px] font-semibold text-gray-900">{{ $title }}</h3>
                        <p class="mt-2 text-[13px] leading-relaxed text-gray-600">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================== CONTOH HASIL ======================== --}}
    <section class="mx-auto max-w-6xl px-5 py-14 sm:px-6 sm:py-16">
        <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
            <div>
                <p class="ui-eyebrow">Contoh hasil</p>
                <h2 class="mt-3 text-xl font-semibold tracking-tight">Dari topik yang masih luas menjadi arah penelitian yang lebih jelas.</h2>
                <p class="mt-3 text-[13px] leading-relaxed text-gray-600">AI memberi titik awal yang bisa Anda ubah, cek, dan diskusikan dengan dosen pembimbing — bukan jawaban akhir yang harus langsung dipakai.</p>
                <a href="{{ $user ? route('dashboard') : route('register') }}" class="mt-6 ui-btn-secondary">
                    {{ $user ? 'Coba di dashboard' : 'Coba dengan akun gratis' }}
                    @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
                </a>
            </div>
            <div class="ui-card overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-3 text-[11px] font-medium text-gray-500">CONTOH · JUDUL PENELITIAN</div>
                <div class="space-y-4 p-5">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Topik awal</p>
                        <p class="mt-1 text-[13px] text-gray-700">Pemanfaatan AI untuk usaha kecil.</p>
                    </div>
                    <div class="rounded-lg border border-blue-100 bg-blue-50/60 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-600">Usulan judul</p>
                        <p class="mt-1.5 text-[13px] font-medium leading-relaxed text-gray-900">Pengaruh Pemanfaatan Artificial Intelligence terhadap Efisiensi Operasional UMKM di Kabupaten Jombang</p>
                    </div>
                    <p class="text-[11px] leading-relaxed text-gray-500">Lanjutkan dengan menilai kelayakan judul, menyusun kerangka, atau membuat draft bagian pertama.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================== CARA KERJA ======================== --}}
    <section id="cara-kerja" class="mx-auto max-w-6xl px-5 py-14 sm:px-6 sm:py-16">
        <div class="max-w-2xl">
            <h2 class="text-xl font-semibold tracking-tight">Mulai dalam tiga langkah</h2>
            <p class="mt-2 text-[13px] leading-relaxed text-gray-600">
                Tidak perlu instalasi atau konfigurasi. Dari nol sampai draft pertama dalam satu sore.
            </p>
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-3">
            @foreach ([
                ['1', 'Buat project', 'Isi program studi, topik, dan metode. Tidak butuh kartu kredit — langsung bisa dipakai.', 'folder'],
                ['2', 'Tentukan judul', 'Gunakan AI untuk merumuskan dan menilai judul, atau isi manual kalau sudah ada.', 'sparkles'],
                ['3', 'Tulis & kembangkan', 'Kerjakan BAB I dulu. Minta AI mengisi, perbaiki sendiri, ulangi sampai selesai.', 'document'],
            ] as [$num, $title, $desc, $icon])
                <div class="ui-card relative p-5">
                    <span class="absolute -top-3 -left-3 grid h-8 w-8 place-items-center rounded-full bg-blue-600 text-[13px] font-bold text-white shadow">
                        {{ $num }}
                    </span>
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-blue-50 text-blue-600 mt-2">
                        @include('partials.icon', ['name' => $icon, 'size' => 'h-4.5 w-4.5'])
                    </span>
                    <h3 class="mt-4 text-[13px] font-semibold text-gray-900">{{ $title }}</h3>
                    <p class="mt-2 text-[13px] leading-relaxed text-gray-600">{{ $desc }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ $user ? route('dashboard') : route('register') }}" class="ui-btn-primary">
                {{ $user ? 'Buka dashboard' : 'Daftar gratis' }}
                @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
            </a>
        </div>
    </section>

    {{-- ======================== KEPERCAYAAN ======================== --}}
    <section class="border-y border-gray-100 bg-blue-50/40">
        <div class="mx-auto grid max-w-6xl gap-6 px-5 py-10 sm:grid-cols-3 sm:px-6 sm:py-12">
            <div class="flex gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white text-blue-600 shadow-sm">
                    @include('partials.icon', ['name' => 'lock', 'size' => 'h-4 w-4'])
                </span>
                <div><h2 class="text-[13px] font-semibold">Project tetap milik Anda</h2><p class="mt-1 text-xs leading-relaxed text-gray-600">Project dan referensi dibatasi untuk akun Anda.</p></div>
            </div>
            <div class="flex gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white text-blue-600 shadow-sm">
                    @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                </span>
                <div><h2 class="text-[13px] font-semibold">Anda tetap memutuskan</h2><p class="mt-1 text-xs leading-relaxed text-gray-600">Hasil AI adalah bahan kerja, bukan pengganti penilaian akademik.</p></div>
            </div>
            <div class="flex gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white text-blue-600 shadow-sm">
                    @include('partials.icon', ['name' => 'shield', 'size' => 'h-4 w-4'])
                </span>
                <div><h2 class="text-[13px] font-semibold">Selalu periksa ulang</h2><p class="mt-1 text-xs leading-relaxed text-gray-600">Validasi data, sitasi, dan arahan dengan dosen pembimbing.</p></div>
            </div>
        </div>
    </section>

    {{-- ======================== HARGA ======================== --}}
    <section id="harga" class="border-y border-gray-100 bg-gray-50/70">
        <div class="mx-auto max-w-6xl px-5 py-14 sm:px-6 sm:py-16">
            <div class="max-w-2xl">
                <h2 class="text-xl font-semibold tracking-tight">Pilih paket</h2>
                <p class="mt-2 text-[13px] leading-relaxed text-gray-600">
                    Mulai gratis. Naikkan paket hanya saat kuota AI benar-benar habis — bukan karena dipaksa.
                </p>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                @foreach ($plans as $plan)
                    <div class="ui-card relative flex flex-col p-6 {{ $plan->is_popular ? 'border-blue-600 shadow-card-lg' : '' }}">
                        @if ($plan->is_popular)
                            <span class="ui-chip absolute -top-3 left-6 bg-blue-600 text-white">Paling dipilih</span>
                        @endif
                        <h3 class="text-base font-semibold tracking-tight">{{ $plan->name }}</h3>
                        <p class="mt-3 flex items-baseline gap-1">
                            <span class="text-3xl font-semibold leading-none tabular-nums tracking-tight">
                                {{ (int) $plan->price === 0 ? 'Gratis' : \App\Support\Labels::rupiah($plan->price) }}
                            </span>
                            @if ((int) $plan->price > 0)
                                <span class="text-[13px] text-gray-500">/{{ $plan->interval === 'month' ? 'bulan' : ($plan->interval === 'year' ? 'tahun' : $plan->interval) }}</span>
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-gray-500">{{ $plan->description }}</p>
                        <div class="mt-5 grid grid-cols-2 gap-2 text-[11px]">
                            <span class="rounded-lg bg-gray-50 px-3 py-2 text-gray-600"><strong class="block text-gray-900">{{ $plan->limit('projects') === null ? 'Tanpa batas' : $plan->limit('projects') }}</strong>Project</span>
                            <span class="rounded-lg bg-gray-50 px-3 py-2 text-gray-600"><strong class="block text-gray-900">{{ $plan->limit('pdf_analysis') === null ? 'Tanpa batas' : $plan->limit('pdf_analysis') }}</strong>Analisis PDF</span>
                        </div>
                        <ul class="mt-6 flex-1 space-y-2.5 border-t border-gray-100 pt-5">
                            @foreach ($plan->features ?? [] as $feature)
                                <li class="flex items-start gap-2 text-sm text-gray-700">
                                    <span class="mt-0.5 shrink-0 text-emerald-500">
                                        @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4', 'stroke' => 2.2])
                                    </span>
                                    <span class="leading-relaxed">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ $user ? route('subscription.prices') : route('register') }}"
                           class="mt-6 ui-btn-block {{ $plan->is_popular ? 'ui-btn-primary' : 'ui-btn-secondary' }}">
                            {{ $plan->slug === 'free' ? 'Mulai gratis' : 'Pilih paket ini' }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================== TESTIMONI ======================== --}}
    <section class="mx-auto max-w-6xl px-5 py-14 sm:px-6 sm:py-16">
        <div class="max-w-2xl">
            <h2 class="text-xl font-semibold tracking-tight">Dipakai dari proposal sampai sidang</h2>
            <p class="mt-2 text-[13px] leading-relaxed text-gray-600">
                Cerita dari mahasiswa yang memakai Sarjana AI sebagai pendamping — bukan pengganti.
            </p>
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Nurul A.', 'Manajemen · Semester 8', 'Dulu judul saya ditolak tiga kali karena terlalu luas. Di sini saya bisa lihat skor kelayakannya dulu sebelum menghadap dosen — hemat waktu bolak-balik.'],
                ['Bagas P.', 'Teknik Informatika · Semester 7', 'Yang paling kepakai itu chat di dalam project. AI sudah tahu judul dan metode saya, jadi jawabannya kontekstual, bukan tips umum.'],
                ['Sari M.', 'Akuntansi · Alumni 2024', 'Review sebelum dibaca dosen bikin saya tahu bagian mana yang lemah. Revisinya jadi terarah, bukan coba-coba.'],
            ] as [$name, $meta, $quote])
                <figure class="ui-card flex flex-col p-5">
                    <blockquote class="flex-1 text-[13px] leading-relaxed text-gray-700">“{{ $quote }}”</blockquote>
                    <figcaption class="mt-4 flex items-center gap-3 border-t border-gray-100 pt-4">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-blue-50 text-[13px] font-semibold text-blue-600">
                            {{ mb_substr($name, 0, 1) }}
                        </span>
                        <span>
                            <span class="block text-[13px] font-semibold text-gray-900">{{ $name }}</span>
                            <span class="block text-[11px] text-gray-500">{{ $meta }}</span>
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>

        <p class="mt-6 text-[11px] text-gray-400">Contoh ilustrasi. Nama disamarkan untuk menjaga privasi pengguna.</p>
    </section>

    {{-- ======================== BANTUAN / FAQ ======================== --}}
    <section id="bantuan" class="mx-auto max-w-6xl px-5 py-14 sm:px-6 sm:py-16">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="max-w-2xl lg:col-span-1">
                <h2 class="text-xl font-semibold tracking-tight">Pertanyaan umum</h2>
                <p class="mt-2 text-[13px] leading-relaxed text-gray-600">
                    Tidak menemukan jawaban? Hubungi tim kami di <a href="mailto:support@sarjanaai.test" class="ui-link">support@sarjanaai.test</a>.
                </p>
            </div>

            <div class="space-y-4 lg:col-span-2">
                @foreach ([
                    ['Apakah AI menulis penelitian saya dari nol?', 'Tidak. AI membantu merumuskan, mengisi draft, dan memberikan saran perbaikan. Anda tetap menulis dan memutuskan isi akhir — itu yang membuat penelitian Anda orisinal.'],
                    ['Apakah hasil AI 100% akurat?', 'Tidak. AI bisa salah. Sarjana AI sengaja menandai bagian yang butuh verifikasi (placeholder seperti [data perlu diisi]) dan selalu menyarankan Anda memeriksa ulang ke dosen pembimbing.'],
                    ['Berapa lama pengerjaan dengan AI?', 'Draft BAB I bisa jadi dalam 15–30 menit. Tapi revisi, verifikasi data, dan konsultasi dengan dosen tetap butuh waktu Anda — dan itu wajar.'],
                    ['Apakah data saya aman?', 'Ya. Project dan dokumen referensi hanya bisa diakses oleh Anda sendiri. Kami tidak membagikan data ke pihak ketiga.'],
                    ['Bisa pindah paket kapan saja?', 'Bisa. Upgrade langsung aktif. Downgrade berlaku di periode berikutnya.'],
                    ['Bagaimana kalau AI habis kuotanya?', 'Kuota di-reset tiap awal bulan. Kalau butuh lebih, upgrade ke paket berikutnya — atau tunggu reset.'],
                ] as [$q, $a])
                    <details class="ui-card group p-5">
                        <summary class="flex cursor-pointer items-center justify-between gap-4 text-[13px] font-medium text-gray-900">
                            {{ $q }}
                            <span class="shrink-0 text-gray-400 transition group-open:rotate-180">
                                @include('partials.icon', ['name' => 'chevron-down', 'size' => 'h-4 w-4'])
                            </span>
                        </summary>
                        <p class="mt-3 text-[13px] leading-relaxed text-gray-600">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================== FOOTER ======================== --}}
    <footer class="border-t border-gray-100">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-8 text-[11px] text-gray-500 sm:px-6">
            <span>© {{ date('Y') }} Sarjana AI · pendamping penulisan ilmiah</span>
            <span>Hasil AI selalu bisa salah. Periksa dan sesuaikan sebelum dipakai.</span>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const parallaxItems = document.querySelectorAll('[data-parallax]');

            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                let ticking = false;
                const updateParallax = function () {
                    parallaxItems.forEach(function (item) {
                        item.style.transform = 'translate3d(0, ' + (window.scrollY * Number(item.dataset.parallax)) + 'px, 0)';
                    });
                    ticking = false;
                };

                updateParallax();
                window.addEventListener('scroll', function () {
                    if (!ticking) {
                        window.requestAnimationFrame(updateParallax);
                        ticking = true;
                    }
                }, { passive: true });
            }

            const form = document.getElementById('demo-form');
            const results = document.getElementById('demo-results');
            const hint = document.getElementById('demo-hint');
            const btn = document.getElementById('demo-btn');
            const btnText = document.getElementById('demo-btn-text');

            if (!form) return;

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const topic = document.getElementById('demo-topic').value.trim();
                if (!topic) return;

                const originalText = btnText.textContent;
                btn.disabled = true;
                btnText.textContent = 'Menganalisis…';
                results.classList.add('hidden');
                results.innerHTML = '';

                try {
                    const r = await fetch('{{ route("demo.titles") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            topic: topic,
                            object: document.getElementById('demo-object').value,
                            location: document.getElementById('demo-location').value,
                            method: document.getElementById('demo-method').value
                        })
                    });

                    const data = await r.json();

                    if (data.success && data.titles?.length) {
                        hint.classList.add('hidden');
                        results.classList.remove('hidden');
                        data.titles.forEach(function (t, i) {
                            const pct = Math.max(0, Math.min(100, t.gap_score || t.relevance || 0));
                            const tone = pct >= 75 ? 'emerald' : pct >= 50 ? 'amber' : 'rose';
                            const bg = tone === 'emerald' ? 'bg-emerald-50 border-emerald-200' : tone === 'amber' ? 'bg-amber-50 border-amber-200' : 'bg-rose-50 border-rose-200';
                            const badge = tone === 'emerald' ? 'text-emerald-700' : tone === 'amber' ? 'text-amber-700' : 'text-rose-700';
                            results.innerHTML += '<div class="rounded-xl border ' + bg + ' p-4">'
                                + '<div class="flex items-center justify-between gap-3">'
                                + '<span class="text-[13px] font-semibold text-gray-900">' + (i + 1) + '. ' + (t.title || '—') + '</span>'
                                + '<span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold ' + badge + ' bg-white/70">' + pct + '/100</span>'
                                + '</div>'
                                + '<p class="mt-1.5 text-[13px] leading-relaxed text-gray-600">' + (t.description || '') + '</p>'
                                + '</div>';
                        });
                    } else {
                        results.classList.remove('hidden');
                        results.innerHTML = '<p class="text-[13px] text-gray-500">Tidak ada hasil. Coba topik lain.</p>';
                    }
                } catch (err) {
                    results.classList.remove('hidden');
                    results.innerHTML = '<p class="text-[13px] text-rose-500">Gagal memuat. Periksa koneksi Anda.</p>';
                } finally {
                    btn.disabled = false;
                    btnText.textContent = originalText;
                }
            });
        });
    </script>
</body>
</html>