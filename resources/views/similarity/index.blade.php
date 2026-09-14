@extends('layouts.app')

@section('title', 'Cek Kemiripan')

@section('header')
    <div>
        <h1 class="ui-page-title">Cek Kemiripan</h1>
        <p class="ui-page-sub">Deteksi copy-paste dan plagiarisme diri sendiri di draft Anda.</p>
    </div>
@endsection

@section('content')
    {{-- Riwayat pemeriksaan --}}
    @if ($reports->count())
        <div class="mb-8 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <h2 class="ui-card-title">Riwayat pemeriksaan</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($reports as $report)
                    <div class="flex items-center gap-4 px-5 py-4">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $report->project?->name ?? 'Project terhapus' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $report->section ? "{$report->section->chapter} {$report->section->title}" : 'Seluruh project' }}
                                · {{ \App\Support\Labels::tanggal($report->created_at) }}
                            </p>
                        </div>
                        @php
                            $tone = match (true) {
                                $report->similarity_score >= 50 => 'red',
                                $report->similarity_score >= 20 => 'amber',
                                default => 'green',
                            };
                        @endphp
                        @include('partials.badge', ['tone' => $tone, 'label' => $report->similarity_score.'%'])
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Pilih project --}}
    @if ($projects->count())
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $project)
                <a href="{{ route('similarity.show', $project->id) }}"
                   class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow-card-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold tracking-tight text-gray-900 dark:text-white">{{ $project->name }}</p>
                            <p class="mt-1 truncate text-[13px] text-gray-500 dark:text-gray-400">{{ $project->title ?: 'Judul belum dipilih' }}</p>
                        </div>
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                            @include('partials.icon', ['name' => 'search', 'size' => 'h-4 w-4'])
                        </span>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50">
                            <p class="ui-eyebrow">Progres</p>
                            <p class="mt-1 text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $project->progress ?? 0 }}%</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50">
                            <p class="ui-eyebrow">Bagian</p>
                            <p class="mt-1 text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $project->sections_count }}</p>
                        </div>
                    </div>

                    <div class="ui-progress mt-5">
                        <div class="ui-progress-fill bg-amber-500" style="width: {{ min(100, max(0, (int) $project->progress)) }}%"></div>
                    </div>

                    <span class="absolute right-5 top-5 text-gray-300 opacity-0 transition group-hover:opacity-100 dark:text-gray-600">
                        @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
                    </span>
                </a>
            @endforeach
        </div>
    @else
        <div class="ui-empty">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100 text-amber-500 shadow-sm dark:from-amber-950/50 dark:to-amber-900/50 dark:text-amber-400">
                @include('partials.icon', ['name' => 'search', 'size' => 'h-7 w-7'])
            </span>
            <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">Belum ada project untuk diperiksa</p>
            <p class="mt-1 max-w-sm text-[13px] leading-relaxed text-gray-500 dark:text-gray-400">
                Buat project terlebih dahulu, lalu kembali ke halaman ini.
            </p>
            <a href="{{ route('projects.create') }}" class="ui-btn-primary mt-5">
                @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
                Buat project
            </a>
        </div>
    @endif
@endsection