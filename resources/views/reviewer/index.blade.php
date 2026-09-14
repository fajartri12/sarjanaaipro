@extends('layouts.app')

@section('title', 'Reviewer')

@php
    $statusMeta = \App\Support\Labels::PROJECT_STATUS;
    $tone = fn (?int $score) => $score === null ? 'gray' : ($score >= 75 ? 'green' : ($score >= 50 ? 'amber' : 'red'));
@endphp

@section('header')
    <div>
        <h1 class="ui-page-title">Reviewer</h1>
        <p class="ui-page-sub">Pilih draft yang ingin dinilai kualitasnya.</p>
    </div>
@endsection

@section('content')
    @if ($projects->count())
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $project)
                <a href="{{ route('reviewer.show', $project->id) }}"
                   class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow-card-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold tracking-tight text-gray-900 dark:text-white">{{ $project->name }}</p>
                            <p class="mt-1 truncate text-[13px] text-gray-500 dark:text-gray-400">{{ $project->title ?: 'Judul belum dipilih' }}</p>
                        </div>
                        @include('partials.badge', [
                            'tone' => ($statusMeta[$project->status]['tone'] ?? 'gray'),
                            'label' => ($statusMeta[$project->status]['label'] ?? $project->status),
                        ])
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-3">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50">
                            <p class="ui-eyebrow">Progres</p>
                            <p class="mt-1 text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $project->progress ?? 0 }}%</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50">
                            <p class="ui-eyebrow">Bagian</p>
                            <p class="mt-1 text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $project->sections_count }}</p>
                        </div>
                        <div class="min-w-0 rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50">
                            <p class="ui-eyebrow">Metode</p>
                            <p class="mt-1 truncate text-sm font-medium text-gray-900 dark:text-white">{{ $project->method ?: '—' }}</p>
                        </div>
                    </div>

                    <div class="ui-progress mt-5">
                        <div class="ui-progress-fill bg-blue-600 dark:bg-blue-500" style="width: {{ min(100, max(0, (int) $project->progress)) }}%"></div>
                    </div>

                    <span class="absolute right-5 top-5 text-gray-300 opacity-0 transition group-hover:opacity-100 dark:text-gray-600">
                        @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
                    </span>
                </a>
            @endforeach
        </div>
    @else
        <div class="ui-empty">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-violet-50 to-violet-100 text-violet-500 shadow-sm dark:from-violet-950/50 dark:to-violet-900/50 dark:text-violet-400">
                @include('partials.icon', ['name' => 'clipboard-check', 'size' => 'h-7 w-7'])
            </span>
            <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">Belum ada draft untuk direview</p>
            <p class="mt-1 max-w-sm text-[13px] leading-relaxed text-gray-500 dark:text-gray-400">
                Mulai satu project dulu, lalu kembali ke halaman ini.
            </p>
            <a href="{{ route('projects.create') }}" class="ui-btn-primary mt-5">
                @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
                Buat project
            </a>
        </div>
    @endif
@endsection
