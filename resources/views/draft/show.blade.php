@extends('layouts.app')

@section('title', $section->chapter.' '.$section->title)

@php
    // Aksi teks harus sama dengan yang diterima DraftService.
    $actions = [
        'improve' => 'Perbaiki',
        'expand' => 'Perluas',
        'summarize' => 'Padatkan',
        'formalize' => 'Formalkan',
        'continue' => 'Lanjutkan',
        'explain' => 'Jelaskan',
    ];
    $sectionMeta = \App\Support\Labels::meta(\App\Support\Labels::SECTION_STATUS, $section->status);

    // Target kata per bagian (dari PRD: minimal 150 kata untuk dianggap selesai)
    $wordTarget = 150;
    $wordProgress = min(100, max(0, (int) (($section->word_count ?? 0) / $wordTarget * 100)));
    $isWordTargetMet = ($section->word_count ?? 0) >= $wordTarget;
@endphp

@section('header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('draft.index', $project['id']) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                Draft penelitian
            </a>
            <h1 class="mt-2 ui-page-title">{{ $section->chapter }} · {{ $section->title }}</h1>
            <p class="ui-page-sub flex flex-wrap items-center gap-2">
                <span><span id="word-count" class="font-medium tabular-nums text-gray-700">{{ $section->word_count }}</span> kata</span>
                @include('partials.badge', ['tone' => $sectionMeta['tone'], 'label' => $sectionMeta['label']])
            </p>
            {{-- Word count progress bar --}}
            <div class="mt-3 flex items-center gap-3">
                <div class="ui-progress flex-1 max-w-xs">
                    <div id="wordbar-fill" class="ui-wordbar-fill {{ $isWordTargetMet ? 'ui-wordbar-fill-done' : '' }}" 
                         style="width: {{ $wordProgress }}%" 
                         data-target="{{ $wordTarget }}"></div>
                </div>
                <span id="wordbar-label" class="text-[11px] tabular-nums text-gray-500">
                    {{ $section->word_count }}/{{ $wordTarget }} kata
                </span>
            </div>
        </div>
        <a href="{{ route('projects.show', $project['id']) }}" class="ui-btn-secondary ui-btn-sm">Ringkasan project</a>
    </div>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-4">
        <div class="space-y-6 lg:col-span-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="ui-card-title">Editor draft</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('draft.generate', [$project['id'], $section->id]) }}"
                          data-ai-stages='{"title":"Menulis bagian ini","stages":["Mengumpulkan konteks judul","Menyusun kerangka","Menulis draf"]}'>
                        @csrf
                        <button type="submit" class="ui-btn-primary ui-btn-sm">
                            @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4'])
                            Tulis dengan AI
                        </button>
                    </form>
                </div>
            </div>

            <form method="POST" action="{{ route('draft.save', [$project['id'], $section->id]) }}">
                @csrf
                @method('PATCH')
                <div class="ui-card overflow-hidden">
                    <div class="ui-card-head flex items-center justify-between py-3">
                        <h2 class="ui-card-title">Naskah</h2>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btn-paragraph-review"
                                    class="ui-btn-secondary ui-btn-xs"
                                    title="Periksa paragraf yang disorot">
                                @include('partials.icon', ['name' => 'clipboard-check', 'size' => 'h-3.5 w-3.5'])
                                Periksa paragraf
                            </button>
                            <button type="button" id="btn-citation"
                                    class="ui-btn-secondary ui-btn-xs"
                                    title="Sisipkan sitasi">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path d="M7.17 6A5.17 5.17 0 0 0 2 11.17V18h6.83v-6.83H5.5A1.67 1.67 0 0 1 7.17 9.5V6Zm10 0a5.17 5.17 0 0 0-5.17 5.17V18h6.83v-6.83H15.5A1.67 1.67 0 0 1 17.17 9.5V6Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Sitasi
                            </button>
                            <button type="submit" class="ui-btn-primary ui-btn-sm">
                                @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                                Simpan
                            </button>
                        </div>
                    </div>
                    @php
                        $editorContent = old('content', $section->content);
                        $editorContent = preg_replace('/<\\/(p|li)>/i', "\n", (string) $editorContent);
                        $editorContent = trim(strip_tags($editorContent));
                    @endphp
                    {{-- Toolbar format: sisipkan markdown-ish, textarea tetap plain text --}}
                    <div class="ui-editor-toolbar">
                        <button type="button" class="ui-editor-tool" data-format="bold" title="Tebal (Ctrl+B)" aria-label="Format tebal">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path d="M6 4h8a4 4 0 0 1 0 8H6zM6 12h9a4 4 0 0 1 0 8H6z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" class="ui-editor-tool" data-format="italic" title="Miring (Ctrl+I)" aria-label="Format miring">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path d="M19 4h-9M14 20H5M15 4L9 20" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" class="ui-editor-tool" data-format="heading" title="Sub-judul" aria-label="Sub-judul">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path d="M6 4v16M18 4v16M6 12h12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" class="ui-editor-tool" data-format="list" title="Daftar berpoin" aria-label="Daftar berpoin">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" class="ui-editor-tool" data-format="quote" title="Kutipan" aria-label="Kutipan">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2-2-2H3v6h6M10 21c3 0 7-1 7-8V5c0-1.25-.757-2-2-2h-5v6h6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <span class="mx-1 h-5 w-px bg-gray-200" aria-hidden="true"></span>
                        <button type="button" class="ui-editor-tool" data-format="undo" title="Batalkan format" aria-label="Batalkan format">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path d="M3 7v6h6M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <textarea id="editor" name="content" rows="24"
                              class="block w-full resize-y border-0 bg-white p-5 font-serif text-[15px] leading-[1.85] text-gray-900 placeholder:text-gray-400 focus:ring-0"
                              placeholder="Mulai menulis… atau klik 'Tulis dengan AI' supaya bagian ini digarap dulu.">{{ $editorContent }}</textarea>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <span id="autosave-status" class="text-[11px] text-gray-400 flex items-center gap-1.5">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span>Tersimpan</span>
                    </span>
                    <button type="button" id="btn-versions" class="text-[11px] font-medium text-blue-600 hover:underline">
                        Riwayat versi
                    </button>
                </div>
            </form>

            <div class="ui-card p-5">
                <h2 class="ui-card-title">Bantuan AI</h2>
                <p class="ui-card-sub">Pilih arah perbaikan untuk bagian ini.</p>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($actions as $action => $label)
                        <form method="POST" action="{{ route('draft.action', [$project['id'], $section->id]) }}"
                              data-ai-stages='{"title":"Menyunting bagian","stages":["Membaca isi saat ini","Menerapkan arahan","Merapikan hasil"]}'>
                            @csrf
                            <input type="hidden" name="action" value="{{ $action }}">
                            <button type="submit" class="ui-btn-secondary ui-btn-xs">{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="ui-card">
                <div class="ui-card-head">
                    <h2 class="ui-card-title">Bagian lain</h2>
                    <span class="ui-eyebrow">{{ $sections->count() }} bagian</span>
                </div>
                <ul class="max-h-[420px] divide-y divide-gray-100 overflow-y-auto">
                    @foreach ($sections as $item)
                        @php $isActive = $item->id === $section->id; @endphp
                        <li>
                            <a href="{{ route('draft.show', [$project['id'], $item->id]) }}"
                               class="flex items-center gap-2.5 px-4 py-2.5 transition {{ $isActive ? 'bg-blue-600 text-white' : 'hover:bg-gray-50' }}">
                                <span class="w-9 shrink-0 text-[11px] font-semibold tabular-nums {{ $isActive ? 'text-gray-300' : 'text-gray-400' }}">{{ $item->key }}</span>
                                <span class="min-w-0 flex-1 truncate text-[13px] {{ $isActive ? 'font-medium' : 'text-gray-700' }}">{{ $item->title }}</span>
                                <span class="shrink-0 text-[11px] tabular-nums {{ $isActive ? 'text-gray-300' : 'text-gray-400' }}">{{ $item->word_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="ui-card p-5">
                <h2 class="ui-card-title">Panduan cepat</h2>
                <ol class="mt-3 space-y-2.5 text-[13px] leading-relaxed text-gray-600">
                    <li class="flex gap-2.5"><span class="font-semibold tabular-nums text-gray-400">1.</span><span>Tulis 400 kata per bagian, lalu klik <b class="font-medium text-gray-800">Tulis dengan AI</b> kalau mulai mentok.</span></li>
                    <li class="flex gap-2.5"><span class="font-semibold tabular-nums text-gray-400">2.</span><span>Gunakan perintah perbaikan untuk penghalusan kata &amp; alur.</span></li>
                    <li class="flex gap-2.5"><span class="font-semibold tabular-nums text-gray-400">3.</span><span>Klik <b class="font-medium text-gray-800">Sitasi</b> untuk menyisipkan referensi ke dalam naskah.</span></li>
                    <li class="flex gap-2.5"><span class="font-semibold tabular-nums text-gray-400">4.</span><span>Bagian dianggap selesai setelah minimal 150 kata.</span></li>
                </ol>
            </div>

            <div class="ui-card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="ui-card-title">Sumber terkait</h2>
                    <button type="button" id="btn-load-sources"
                            class="ui-btn-ghost ui-btn-xs"
                            title="Cari sumber dari dokumen yang diunggah">
                        @include('partials.icon', ['name' => 'search', 'size' => 'h-3.5 w-3.5'])
                    </button>
                </div>
                <p class="ui-card-sub">Kutipan dari jurnal yang diunggah.</p>
                <div id="sources-list" class="mt-3 space-y-2 max-h-[200px] overflow-y-auto">
                    <p class="text-xs text-gray-400 py-2">Klik tombol refresh untuk mencari.</p>
                </div>
            </div>
        </aside>
    </div>

    {{-- Modal sitasi --}}
    <div id="citation-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sisipkan sitasi</h3>
                <button type="button" id="citation-modal-close" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700" aria-label="Tutup modal sitasi">
                    @include('partials.icon', ['name' => 'x', 'size' => 'h-5 w-5'])
                </button>
            </div>
            <div class="max-h-[360px] overflow-y-auto px-5 py-3">
                @forelse ($references as $ref)
                    <button type="button" data-ref-id="{{ $ref->id }}" data-ref-text="{{ $ref->inTextCitation('apa', $loop->iteration) }}"
                            class="citation-item flex w-full gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-blue-50 dark:hover:bg-blue-950/40">
                        <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-gray-100 text-[11px] font-bold text-gray-500 dark:bg-gray-700 dark:text-gray-300">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] font-medium leading-snug text-gray-900 dark:text-white">{{ $ref->title }}</p>
                                <p class="mt-0.5 text-[13px] text-gray-500 dark:text-gray-400">
                                {{ $ref->authors ?: 'Tanpa penulis' }} · {{ $ref->year ?? 'n.d.' }}
                            </p>
                        </div>
                        <span class="shrink-0 self-center rounded-md bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">{{ $ref->inTextCitation('apa', $loop->iteration) }}</span>
                    </button>
                @empty
                    <p class="py-8 text-center text-sm text-gray-400">Belum ada referensi. <a href="{{ route('references.index') }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">Tambah referensi</a> dulu.</p>
                @endforelse
            </div>
            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                <a href="{{ route('references.index') }}" class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">+ Referensi baru</a>
                <button type="button" id="citation-modal-cancel" class="rounded-lg px-4 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">Tutup</button>
            </div>
        </div>
    </div>

    {{-- Modal hasil review paragraf --}}
    <div id="paragraph-review-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Hasil pemeriksaan paragraf</h3>
                <button type="button" id="paragraph-review-modal-close" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700" aria-label="Tutup modal tinjauan paragraf">
                    @include('partials.icon', ['name' => 'x', 'size' => 'h-5 w-5'])
                </button>
            </div>
            <div class="px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex-1">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Skor</p>
                        <p id="pr-score" class="text-3xl font-bold text-gray-900 dark:text-white">—</p>
                    </div>
                    <div id="pr-score-bar" class="h-2 w-24 rounded-full bg-gray-200">
                        <div id="pr-score-fill" class="h-full rounded-full bg-emerald-500 transition-all" style="width: 0%"></div>
                    </div>
                </div>
                <div class="mt-4 space-y-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Masalah</p>
                        <p id="pr-issue" class="mt-1 text-sm leading-relaxed text-gray-700 dark:text-gray-300">—</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Saran</p>
                        <p id="pr-suggestion" class="mt-1 text-sm leading-relaxed text-gray-700 dark:text-gray-300">—</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Alasan</p>
                        <p id="pr-reason" class="mt-1 text-sm leading-relaxed text-gray-600 dark:text-gray-400">—</p>
                    </div>
                </div>
            </div>
            <div class="flex justify-end border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                <button type="button" id="paragraph-review-modal-ok" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-blue-700">Tutup</button>
            </div>
        </div>
    </div>

    {{-- Modal riwayat versi --}}
    <div id="versions-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Riwayat versi</h3>
                <button type="button" id="versions-modal-close" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700" aria-label="Tutup modal riwayat versi">
                    @include('partials.icon', ['name' => 'x', 'size' => 'h-5 w-5'])
                </button>
            </div>
            <div id="versions-list" class="max-h-[400px] overflow-y-auto px-5 py-3">
                <p class="py-6 text-center text-sm text-gray-400">Memuat…</p>
            </div>
        </div>
    </div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('editor');
    const btn = document.getElementById('btn-citation');
    const modal = document.getElementById('citation-modal');
    const closeBtn = document.getElementById('citation-modal-close');
    const cancelBtn = document.getElementById('citation-modal-cancel');

    if (!btn || !modal || !editor) return;

    function openModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

    btn.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    document.querySelectorAll('.citation-item').forEach(el => {
        el.addEventListener('click', function() {
            const text = this.dataset.refText;
            if (!text) return;

            const start = editor.selectionStart;
            const end = editor.selectionEnd;
            const before = editor.value.substring(0, start);
            const after = editor.value.substring(end);
            editor.value = before + text + after;
            editor.selectionStart = editor.selectionEnd = start + text.length;
            editor.focus();
            closeModal();
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('editor');
    const btn = document.getElementById('btn-paragraph-review');
    const modal = document.getElementById('paragraph-review-modal');
    const closeBtn = document.getElementById('paragraph-review-modal-close');
    const okBtn = document.getElementById('paragraph-review-modal-ok');

    if (!btn || !modal || !editor) return;

    const endpoint = "{{ route('draft.review-paragraph', [$project['id'], $section->id]) }}";
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function openModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

    closeBtn?.addEventListener('click', closeModal);
    okBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    btn.addEventListener('click', async function () {
        // Ambil paragraf yang disorot; kalau tidak ada, ambil paragraf tempat kursor berada.
        let text = editor.value.substring(editor.selectionStart, editor.selectionEnd).trim();

        if (!text) {
            const pos = editor.selectionStart;
            const before = editor.value.lastIndexOf('\n', Math.max(0, pos - 1));
            const after = editor.value.indexOf('\n', pos);
            text = editor.value
                .substring(before === -1 ? 0 : before + 1, after === -1 ? editor.value.length : after)
                .trim();
        }

        if (text.length < 10) {
            alert('Sorot satu paragraf dulu (minimal beberapa kata), lalu tekan tombol ini lagi.');
            return;
        }

        btn.disabled = true;
        btn.classList.add('opacity-60');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ paragraph: text }),
            });

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                alert(err.message || 'Gagal memeriksa paragraf. Coba lagi.');
                return;
            }

            const data = await response.json();
            const score = Number(data.score) || 0;

            document.getElementById('pr-score').textContent = score;
            document.getElementById('pr-issue').textContent = data.issue || '—';
            document.getElementById('pr-suggestion').textContent = data.suggestion || '—';
            document.getElementById('pr-reason').textContent = data.reason || '—';

            const fill = document.getElementById('pr-score-fill');
            fill.style.width = Math.max(0, Math.min(100, score)) + '%';
            fill.className = 'h-full rounded-full transition-all ' +
                (score >= 75 ? 'bg-emerald-500' : score >= 50 ? 'bg-amber-500' : 'bg-rose-500');

            openModal();
        } catch (e) {
            alert('Koneksi gagal. Periksa jaringan lalu coba lagi.');
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-60');
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('editor');
    const btnSources = document.getElementById('btn-load-sources');
    const sourcesList = document.getElementById('sources-list');

    if (!btnSources || !sourcesList || !editor) return;

    const sourcesEndpoint = "{{ route('draft.sources', [$project['id'], $section->id]) }}";
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    btnSources.addEventListener('click', async function () {
        // Ambil teks dari editor untuk mencari sumber yang relevan.
        const content = editor.value.trim();

        if (content.length < 15) {
            sourcesList.innerHTML = '<p class="text-xs text-gray-400 py-2">Tulis lebih banyak dulu (minimal 15 karakter).</p>';
            return;
        }

        btnSources.disabled = true;
        btnSources.classList.add('opacity-60');

        try {
            const response = await fetch(sourcesEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ query: content }),
            });

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                sourcesList.innerHTML = '<p class="text-xs text-rose-500 py-2">' + (err.message || 'Gagal mengambil sumber.') + '</p>';
                return;
            }

            const data = await response.json();
            const sources = data.sources || [];

            if (sources.length === 0) {
                sourcesList.innerHTML = '<p class="text-xs text-gray-400 py-2">Tidak ada sumber yang cocok. Unggah dokumen di menu Riset.</p>';
                return;
            }

            sourcesList.innerHTML = sources.map(s => `
                <button type="button" class="source-item w-full text-left rounded-lg border border-gray-200 p-2.5 transition hover:border-blue-300 hover:bg-blue-50/50">
                    <p class="text-[11px] font-medium text-gray-700 truncate">${s.label}</p>
                    <p class="mt-1 text-[11px] leading-relaxed text-gray-500 line-clamp-2">${s.content}</p>
                </button>
            `).join('');

            // Klik sumber → sisipkan kutipan ke editor.
            sourcesList.querySelectorAll('.source-item').forEach((el, idx) => {
                el.addEventListener('click', function() {
                    const source = sources[idx];
                    const citation = `[${idx + 1}] `;
                    const start = editor.selectionStart;
                    const end = editor.selectionEnd;
                    const before = editor.value.substring(0, start);
                    const after = editor.value.substring(end);
                    editor.value = before + citation + after;
                    editor.selectionStart = editor.selectionEnd = start + citation.length;
                    editor.focus();
                });
            });
        } catch (e) {
            sourcesList.innerHTML = '<p class="text-xs text-rose-500 py-2">Koneksi gagal.</p>';
        } finally {
            btnSources.disabled = false;
            btnSources.classList.remove('opacity-60');
        }
    });
});

// Autosave + riwayat versi + toolbar markdown
const wordTarget = {{ $wordTarget }};

document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('editor');
    const status = document.getElementById('autosave-status');
    const wordCountEl = document.getElementById('word-count');
    const wordBarFill = document.getElementById('wordbar-fill');
    const wordBarLabel = document.getElementById('wordbar-label');
    const btnVersions = document.getElementById('btn-versions');
    const modal = document.getElementById('versions-modal');
    const closeBtn = document.getElementById('versions-modal-close');
    const list = document.getElementById('versions-list');

    if (!editor) return;

    const autosaveEndpoint = "{{ route('draft.autosave', [$project['id'], $section->id]) }}";
    const versionsEndpoint = "{{ route('draft.versions', [$project['id'], $section->id]) }}";
    const restoreBase = "{{ route('draft.versions.restore', [$project['id'], $section->id, '__ID__']) }}";
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let lastSaved = editor.value;
    let timer = null;
    let saving = false;

    // Word count real-time
    const countWords = (text) => {
        const trimmed = text.trim();
        if (!trimmed) return 0;
        return trimmed.split(/\s+/).length;
    };

    const updateWordCount = () => {
        const count = countWords(editor.value);
        if (wordCountEl) wordCountEl.textContent = count;
        if (wordBarLabel) wordBarLabel.textContent = `${count}/${wordTarget} kata`;
        if (wordBarFill) {
            const pct = Math.min(100, Math.max(0, (count / wordTarget) * 100));
            wordBarFill.style.width = `${pct}%`;
            wordBarFill.classList.toggle('ui-wordbar-fill-done', count >= wordTarget);
        }
    };

    // Toolbar markdown shortcuts
    document.querySelectorAll('.ui-editor-tool').forEach(btn => {
        btn.addEventListener('click', () => {
            const format = btn.dataset.format;
            const textarea = editor;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selected = textarea.value.substring(start, end);
            let newText = '';
            let newStart = start;
            let newEnd = end;

            switch (format) {
                case 'bold':
                    newText = textarea.value.substring(0, start) + '**' + selected + '**' + textarea.value.substring(end);
                    newStart = start + 2;
                    newEnd = end + 2;
                    break;
                case 'italic':
                    newText = textarea.value.substring(0, start) + '*' + selected + '*' + textarea.value.substring(end);
                    newStart = start + 1;
                    newEnd = end + 1;
                    break;
                case 'heading':
                    newText = textarea.value.substring(0, start) + '\n\n### ' + selected + '\n\n' + textarea.value.substring(end);
                    newStart = start + 5;
                    newEnd = end + 5;
                    break;
                case 'list':
                    newText = textarea.value.substring(0, start) + '\n- ' + selected + textarea.value.substring(end);
                    newStart = start + 3;
                    newEnd = end + 3;
                    break;
                case 'quote':
                    newText = textarea.value.substring(0, start) + '\n> ' + selected + textarea.value.substring(end);
                    newStart = start + 3;
                    newEnd = end + 3;
                    break;
                case 'undo':
                    // Remove markdown from selection
                    newText = selected
                        .replace(/^\s*[#*>-]+\s*/gm, '')
                        .replace(/\*\*(.+?)\*\*/g, '$1')
                        .replace(/\*(.+?)\*/g, '$1');
                    textarea.value = textarea.value.substring(0, start) + newText + textarea.value.substring(end);
                    newStart = start;
                    newEnd = start + newText.length;
                    break;
                default:
                    return;
            }

            if (newText) {
                textarea.value = newText;
                textarea.selectionStart = newStart;
                textarea.selectionEnd = newEnd;
                textarea.focus();
                updateWordCount();
            }
        });
    });

    async function save() {
        if (saving || editor.value === lastSaved) return;
        saving = true;
        status.innerHTML = '<span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span><span>Menyimpan…</span>';

        try {
            const res = await fetch(autosaveEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ content: editor.value }),
            });

            if (!res.ok) {
                status.innerHTML = '<span class="inline-block h-1.5 w-1.5 rounded-full bg-rose-500"></span><span class="text-rose-500">Gagal menyimpan otomatis.</span>';
                return;
            }

            const data = await res.json();
            lastSaved = editor.value;
            const wc = document.getElementById('word-count');
            if (wc) wc.textContent = data.word_count;
            const time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            status.innerHTML = `<span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span><span>Tersimpan ${time}</span>`;
        } catch (e) {
            status.innerHTML = '<span class="inline-block h-1.5 w-1.5 rounded-full bg-rose-500"></span><span class="text-rose-500">Koneksi gagal.</span>';
        } finally {
            saving = false;
        }
    }

    editor.addEventListener('input', function() {
        updateWordCount();
        status.textContent = 'Belum tersimpan…';
        status.className = 'text-[11px] text-amber-600';
        clearTimeout(timer);
        timer = setTimeout(save, 2500);
    });

    updateWordCount();

    // Simpan juga saat meninggalkan halaman supaya tulisan tidak hilang.
    window.addEventListener('beforeunload', function(e) {
        if (editor.value !== lastSaved) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Riwayat versi
    btnVersions?.addEventListener('click', async function() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        list.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">Memuat…</p>';

        try {
            const res = await fetch(versionsEndpoint, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            const versions = data.versions || [];

            if (versions.length === 0) {
                list.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">Belum ada versi tersimpan.</p>';
                return;
            }

            list.innerHTML = versions.map(v => `
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 py-3 last:border-0 dark:border-gray-700">
                    <div class="min-w-0">
                        <p class="text-[13px] font-medium text-gray-800 dark:text-gray-200">${v.at_full}</p>
                        <p class="text-[11px] text-gray-400">${v.word_count} kata · ${v.at}</p>
                    </div>
                    <form method="POST" action="${restoreBase.replace('__ID__', v.id)}">
                        @csrf
                        <button type="submit" class="ui-btn-secondary ui-btn-xs">Kembalikan</button>
                    </form>
                </div>
            `).join('');
        } catch (e) {
            list.innerHTML = '<p class="py-6 text-center text-sm text-rose-500">Gagal memuat riwayat.</p>';
        }
    });

    closeBtn?.addEventListener('click', function() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });
    modal?.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    });
});
</script>
