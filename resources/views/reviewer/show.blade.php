@extends('layouts.app')

@section('title', 'Review · '.$project->name)

@php
    $statusMeta = \App\Support\Labels::SECTION_STATUS;
    $scoreLabels = [
        'clarity' => 'Kejelasan',
        'writing' => 'Tulisan',
        'problem' => 'Rumusan masalah',
        'gap' => 'Research gap',
        'consistency' => 'Konsistensi',
        'methodology' => 'Metodologi',
        'citation' => 'Sitasi',
        'structure' => 'Struktur',
    ];
    $tone = fn (?int $score) => $score === null ? 'gray' : ($score >= 75 ? 'green' : ($score >= 50 ? 'amber' : 'red'));
    $barColor = fn (string $tone) => match ($tone) {
        'green' => 'bg-emerald-500',
        'amber' => 'bg-amber-500',
        'red' => 'bg-rose-500',
        default => 'bg-gray-400',
    };
    $latest = $project->reviews->first();
    $sectionName = fn ($id) => $id
        ? ($project->sections->firstWhere('id', $id)->title ?? 'Bagian terhapus')
        : 'Seluruh draft';
@endphp

@section('header')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('reviewer.index') }}"
               class="inline-flex items-center gap-1.5 text-[13px] font-medium text-gray-500 transition hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-400">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-4 w-4'])
                Reviewer
            </a>
            <h1 class="ui-page-title mt-2">{{ $project->name }}</h1>
            <p class="ui-page-sub">Nilai kualitas draft dan dapatkan saran perbaikan.</p>
        </div>
        <form method="POST" action="{{ route('reviewer.review', $project->id) }}"
              data-ai-stages='{"title":"Menilai seluruh draft","stages":["Membaca tiap bagian","Menilai kualitas","Menyusun saran"]}'>
            @csrf
            <button type="submit" class="ui-btn-primary">
                @include('partials.icon', ['name' => 'clipboard-check', 'size' => 'h-4 w-4'])
                Review seluruh draft
            </button>
        </form>
    </div>
@endsection

@section('content')
    @if ($latest)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-start justify-between gap-4 p-5 sm:p-6">
                <div>
                    <h2 class="ui-card-title">Review terbaru</h2>
                    <p class="ui-card-sub">{{ $sectionName($latest->section_id) }}</p>
                </div>
                @include('partials.badge', ['tone' => $tone($latest->score), 'label' => 'Skor '.$latest->score])
            </div>

            @if (! empty($latest->scores))
                <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-700">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nilai per aspek</h3>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($latest->scores as $key => $score)
                            @php $scoreTone = $tone((int) $score); @endphp
                            <div class="rounded-xl border border-gray-200 p-4 transition hover:shadow-sm dark:border-gray-700">
                                <p class="ui-eyebrow">{{ $scoreLabels[$key] ?? $key }}</p>
                                <p class="mt-1.5 text-2xl font-bold leading-none tabular-nums tracking-tight text-gray-900 dark:text-white">{{ $score }}</p>
                                <div class="ui-progress mt-3">
                                    <div class="ui-progress-fill {{ $barColor($scoreTone) }}"
                                         style="width: {{ min(100, max(0, (int) $score)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($latest->summary)
                <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-700">
                    <div class="flex items-start gap-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                            @include('partials.icon', ['name' => 'document', 'size' => 'h-4 w-4'])
                        </span>
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Ringkasan</h3>
                            <p class="mt-2 text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $latest->summary }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if (! empty($latest->recommendations))
                <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-700">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Saran perbaikan</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach ($latest->recommendations as $index => $item)
                            <li class="flex gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm leading-relaxed text-gray-700 transition dark:border-gray-700 dark:text-gray-300">
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-amber-100 text-[11px] font-bold text-amber-800 dark:bg-amber-950/50 dark:text-amber-400">{{ $index + 1 }}</span>
                                <span>{{ is_array($item) ? implode(' — ', $item) : $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @else
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start gap-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-950/50 dark:text-violet-400">
                    @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-5 w-5'])
                </span>
                <div>
                    <h2 class="ui-card-title">Belum ada review</h2>
                    <p class="ui-card-sub">Tekan "Review seluruh draft" untuk memulai.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Pemeriksaan konsistensi --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-start justify-between gap-4 p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-950/50 dark:text-violet-400">
                    @include('partials.icon', ['name' => 'link', 'size' => 'h-[18px] w-[18px]'])
                </span>
                <div>
                    <h2 class="ui-card-title">Konsistensi skripsi</h2>
                    <p class="ui-card-sub">Cek apakah judul, rumusan masalah, tujuan, dan metode sudah sejalan.</p>
                </div>
            </div>
            <button type="button" id="btn-consistency" class="ui-btn-secondary">
                @include('partials.icon', ['name' => 'clipboard-check', 'size' => 'h-4 w-4'])
                Periksa konsistensi
            </button>
        </div>

        <div id="consistency-result" class="hidden border-t border-gray-100 p-5 sm:p-6 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <span id="consistency-badge"></span>
                <span id="consistency-loading" class="hidden text-sm text-gray-500">Memeriksa...</span>
            </div>
            <p id="consistency-summary" class="mt-3 text-sm leading-relaxed text-gray-700 dark:text-gray-300"></p>

            <div id="consistency-issues-wrap" class="mt-4 hidden">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Ketidaksesuaian</h3>
                <ul id="consistency-issues" class="mt-2 space-y-2"></ul>
            </div>

            <div id="consistency-suggestions-wrap" class="mt-4 hidden">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Saran perbaikan</h3>
                <ul id="consistency-suggestions" class="mt-2 space-y-2"></ul>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('btn-consistency');
            const wrap = document.getElementById('consistency-result');
            const loading = document.getElementById('consistency-loading');
            const badge = document.getElementById('consistency-badge');
            const summary = document.getElementById('consistency-summary');
            const issuesWrap = document.getElementById('consistency-issues-wrap');
            const issuesList = document.getElementById('consistency-issues');
            const saranWrap = document.getElementById('consistency-suggestions-wrap');
            const saranList = document.getElementById('consistency-suggestions');

            if (!btn) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function renderList(el, items) {
                el.replaceChildren(...items.map(item => {
                    const li = document.createElement('li');
                    li.className = 'flex gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm leading-relaxed text-gray-700 dark:border-gray-700 dark:text-gray-300';

                    const dot = document.createElement('span');
                    dot.className = 'mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400';

                    const text = document.createElement('span');
                    text.textContent = item;

                    li.append(dot, text);

                    return li;
                }));
            }

            btn.addEventListener('click', async function() {
                btn.disabled = true;
                btn.classList.add('opacity-60');
                wrap.classList.remove('hidden');
                loading.classList.remove('hidden');
                badge.innerHTML = '';
                summary.textContent = '';
                issuesWrap.classList.add('hidden');
                saranWrap.classList.add('hidden');

                try {
                    const response = await fetch('{{ route('reviewer.consistency', $project->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                    });

                    if (!response.ok) {
                        const err = await response.json().catch(() => ({}));
                        summary.textContent = err.message || 'Gagal memeriksa konsistensi. Tulis dulu beberapa bagian draft.';
                        return;
                    }

                    const data = await response.json();
                    const ok = !!data.consistent;

                    badge.className = 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold '
                        + (ok
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400'
                            : 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400');
                    badge.textContent = ok ? 'Sudah konsisten' : 'Perlu ditinjau';

                    summary.textContent = data.summary || '—';

                    const issues = data.issues || [];
                    if (issues.length) {
                        renderList(issuesList, issues);
                        issuesWrap.classList.remove('hidden');
                    }

                    const suggestions = data.suggestions || [];
                    if (suggestions.length) {
                        renderList(saranList, suggestions);
                        saranWrap.classList.remove('hidden');
                    }
                } catch (e) {
                    summary.textContent = 'Koneksi gagal. Periksa jaringan lalu coba lagi.';
                } finally {
                    btn.disabled = false;
                    btn.classList.remove('opacity-60');
                    loading.classList.add('hidden');
                }
            });
        });
    </script>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-start justify-between gap-4 p-5 sm:p-6">
            <div>
                <h2 class="ui-card-title">Review per bagian</h2>
                <p class="ui-card-sub">Lebih cepat dan hemat kuota dibanding review penuh.</p>
            </div>
        </div>

        @if ($project->sections->count())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-gray-100 bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium w-16">Kode</th>
                            <th scope="col" class="px-5 py-3 font-medium">Bagian</th>
                            <th scope="col" class="px-5 py-3 font-medium">Panjang</th>
                            <th scope="col" class="px-5 py-3 font-medium">Status</th>
                            <th scope="col" class="px-5 py-3 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($project->sections as $section)
                            @php $meta = $statusMeta[$section->status] ?? ['label' => $section->status, 'tone' => 'gray']; @endphp
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                <td class="px-5 py-3 font-mono text-xs font-semibold text-gray-400 dark:text-gray-500">
                                    {{ $section->key }}
                                </td>
                                <th scope="row" class="px-5 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $section->title }}
                                </th>
                                <td class="px-5 py-3 font-mono text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                    {{ \App\Support\Labels::angka($section->word_count) }} kata
                                </td>
                                <td class="px-5 py-3">
                                    @include('partials.badge', ['tone' => $meta['tone'], 'label' => $meta['label']])
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <form method="POST" action="{{ route('reviewer.section', $project->id) }}"
                                          class="inline-flex"
                                          data-ai-stages='{"title":"Menilai bagian ini","stages":["Membaca bagian","Menilai kualitas","Menyusun saran"]}'>
                                        @csrf
                                        <input type="hidden" name="section_id" value="{{ $section->id }}">
                                        <button type="submit"
                                                @disabled(! $section->content)
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-white">
                                            @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-3.5 w-3.5'])
                                            Review
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-700">
                <div class="ui-empty border-0 bg-transparent py-8">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                        @include('partials.icon', ['name' => 'document', 'size' => 'h-6 w-6'])
                    </span>
                    <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Project ini belum punya bagian</p>
                    <p class="mt-1 max-w-sm text-[13px] leading-relaxed text-gray-500 dark:text-gray-400">
                        Buka draft untuk membuat kerangka bab terlebih dahulu.
                    </p>
                </div>
            </div>
        @endif

        @error('section_id')
            <p class="border-t border-gray-100 px-5 py-3 text-sm text-rose-600 dark:border-gray-700 dark:text-rose-400">{{ $message }}</p>
        @enderror
    </div>

    @if ($project->reviews->count() > 1)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-100 p-5 sm:p-6 dark:border-gray-700">
                <h2 class="ui-card-title">Riwayat review</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-gray-100 bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Bagian</th>
                            <th scope="col" class="px-5 py-3 font-medium">Ringkasan Temuan</th>
                            <th scope="col" class="px-5 py-3 font-medium text-right">Skor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($project->reviews as $review)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                <th scope="row" class="px-5 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $sectionName($review->section_id) }}
                                </th>
                                <td class="max-w-md truncate px-5 py-3 text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                                    {{ \Illuminate\Support\Str::limit($review->summary, 120) }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @include('partials.badge', ['tone' => $tone($review->score), 'label' => 'Skor '.$review->score])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
