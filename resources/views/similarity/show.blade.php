@extends('layouts.app')

@section('title', 'Cek Kemiripan · '.$project->name)

@php
    $tone = fn (int $score) => match (true) {
        $score >= 50 => 'red',
        $score >= 20 => 'amber',
        default => 'green',
    };
    $barColor = fn (int $score) => match (true) {
        $score >= 50 => 'bg-rose-500',
        $score >= 20 => 'bg-amber-500',
        default => 'bg-emerald-500',
    };
    $verdict = fn (int $score) => match (true) {
        $score >= 50 => 'Perlu ditulis ulang',
        $score >= 20 => 'Perlu ditinjau',
        default => 'Aman',
    };
@endphp

@section('header')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('similarity.index') }}"
               class="inline-flex items-center gap-1.5 text-[13px] font-medium text-gray-500 transition hover:text-amber-700 dark:text-gray-400 dark:hover:text-amber-400">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-4 w-4'])
                Cek Kemiripan
            </a>
            <h1 class="ui-page-title mt-2">{{ $project->name }}</h1>
            <p class="ui-page-sub">Periksa apakah ada kalimat yang terpakai ulang dari dokumen Anda atau dari bagian lain.</p>
        </div>

        @if ($project->sections->count())
            <div class="flex flex-wrap items-center gap-3">
                @if ($latestFull || $latestBySection->isNotEmpty())
                    <a href="{{ route('export.similarity', $project->id) }}" class="ui-btn-secondary">
                        @include('partials.icon', ['name' => 'download', 'size' => 'h-4 w-4'])
                        Unduh laporan PDF
                    </a>
                @endif

                <form method="POST" action="{{ route('similarity.full', $project->id) }}"
                      data-ai-stages='{"title":"Memeriksa seluruh draft","stages":["Membaca tiap bagian","Mencocokkan kalimat","Menyusun ringkasan"]}'>
                    @csrf
                    <button type="submit" class="ui-btn-primary">
                        @include('partials.icon', ['name' => 'search', 'size' => 'h-4 w-4'])
                        Periksa seluruh project
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection

@section('content')
    @if (session('report'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
            @include('partials.badge', ['tone' => 'green', 'label' => 'Pemeriksaan selesai'])
        </div>
    @endif

    @if ($latestFull)
        <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="ui-card-title">Skor keseluruhan</h2>
                    <p class="ui-card-sub">Rata-rata dari {{ count($latestFull->matches ?? []) }} bagian · {{ \App\Support\Labels::tanggal($latestFull->created_at) }}</p>
                </div>
                @include('partials.badge', ['tone' => $tone($latestFull->similarity_score), 'label' => $latestFull->similarity_score.'% · '.$verdict($latestFull->similarity_score)])
            </div>

            <div class="ui-progress mt-4 h-2">
                <div class="ui-progress-fill {{ $barColor($latestFull->similarity_score) }}"
                     style="width: {{ min(100, max(0, $latestFull->similarity_score)) }}%"></div>
            </div>

            @if ($latestFull->summary)
                <p class="mt-4 text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $latestFull->summary }}</p>
            @endif

            @if (! empty($latestFull->matches))
                <div class="mt-5 divide-y divide-gray-100 border-t border-gray-100 dark:divide-gray-700 dark:border-gray-700">
                    @foreach ($latestFull->matches as $row)
                        <div class="flex items-center justify-between gap-4 py-3">
                            <span class="min-w-0 truncate text-sm text-gray-700 dark:text-gray-300">{{ $row['title'] }}</span>
                            @include('partials.badge', ['tone' => $tone((int) $row['score']), 'label' => $row['score'].'%'])
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if ($project->sections->count())
        <div class="space-y-4">
            @foreach ($project->sections as $section)
                @php $report = $latestBySection->get($section->id); @endphp
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                        <div class="min-w-0">
                            <p class="ui-eyebrow">{{ $section->chapter }}</p>
                            <h2 class="mt-1 truncate text-sm font-semibold tracking-tight text-gray-900 dark:text-white">{{ $section->title }}</h2>
                            <p class="mt-1 text-[13px] text-gray-500 dark:text-gray-400">
                                {{ \App\Support\Labels::angka($section->word_count ?? 0) }} kata
                                @if ($report) · diperiksa {{ \App\Support\Labels::tanggal($report->created_at) }} @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($report)
                                @include('partials.badge', ['tone' => $tone($report->similarity_score), 'label' => $report->similarity_score.'% · '.$verdict($report->similarity_score)])
                            @endif
                            <form method="POST" action="{{ route('similarity.section', $project->id) }}"
                                  data-ai-stages='{"title":"Memeriksa bagian","stages":["Membaca bagian","Mencocokkan kalimat","Menyusun saran"]}'>
                                @csrf
                                <input type="hidden" name="section_id" value="{{ $section->id }}">
                                <button type="submit" class="ui-btn-secondary">
                                    @include('partials.icon', ['name' => 'search', 'size' => 'h-4 w-4'])
                                    {{ $report ? 'Periksa ulang' : 'Periksa' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    @if ($report)
                        @if ($report->summary)
                            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-700">
                                <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $report->summary }}</p>
                            </div>
                        @endif

                        @if (! empty($report->matches))
                            <div class="border-t border-gray-100 dark:border-gray-700">
                                <p class="ui-eyebrow px-5 pt-4">Kalimat yang cocok</p>
                                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($report->matches as $match)
                                        <div class="px-5 py-4">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <span class="inline-flex min-w-0 items-center gap-2 text-[13px] font-medium text-gray-900 dark:text-white">
                                                    @include('partials.icon', [
                                                        'name' => $match['source'] === 'document' ? 'document' : 'link',
                                                        'size' => 'h-4 w-4',
                                                        'class' => 'shrink-0 text-gray-400',
                                                    ])
                                                    <span class="truncate">{{ $match['label'] }}</span>
                                                </span>
                                                @include('partials.badge', ['tone' => $tone((int) $match['score']), 'label' => $match['score'].'% kalimat'])
                                            </div>
                                            <blockquote class="mt-2 border-l-2 border-gray-200 pl-3 text-[13px] italic leading-relaxed text-gray-600 dark:border-gray-600 dark:text-gray-400">
                                                “{{ $match['snippet'] }}”
                                            </blockquote>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="border-t border-gray-100 px-5 py-4 text-[13px] text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                Tidak ada kalimat yang cocok dengan sumber mana pun.
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="ui-empty">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100 text-amber-500 shadow-sm dark:from-amber-950/50 dark:to-amber-900/50 dark:text-amber-400">
                @include('partials.icon', ['name' => 'search', 'size' => 'h-7 w-7'])
            </span>
            <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">Draft masih kosong</p>
            <p class="mt-1 max-w-sm text-[13px] leading-relaxed text-gray-500 dark:text-gray-400">
                Tulis dulu isi bagian di halaman draft, lalu cek kemiripannya di sini.
            </p>
            <a href="{{ route('draft.index', $project->id) }}" class="ui-btn-primary mt-5">
                @include('partials.icon', ['name' => 'document', 'size' => 'h-4 w-4'])
                Buka draft
            </a>
        </div>
    @endif
@endsection