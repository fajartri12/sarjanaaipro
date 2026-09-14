@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $hour = (int) now()->format('G');
    $greeting = match (true) {
        $hour < 11 => 'Selamat pagi',
        $hour < 15 => 'Selamat siang',
        $hour < 19 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $firstName = explode(' ', trim(auth()->user()->name ?? 'Mahasiswa'))[0];
    $initial = strtoupper(substr($firstName, 0, 1));

    $limitTeks = fn ($n) => $n === null ? 'Tak terbatas' : \App\Support\Labels::angka($n).'×';

    $activityIcon = function (string $action): string {
        return match (true) {
            str_contains($action, 'title') => 'sparkles',
            str_contains($action, 'reference') => 'link',
            str_contains($action, 'research') => 'book',
            str_contains($action, 'review') => 'clipboard-check',
            str_contains($action, 'sempro') => 'cap',
            str_contains($action, 'section'), str_contains($action, 'draft') => 'document',
            str_contains($action, 'login'), str_contains($action, 'user') => 'user',
            str_contains($action, 'payment'), str_contains($action, 'subscription') => 'card',
            default => 'clock',
        };
    };

    $activityTone = function (string $action): string {
        return match (true) {
            str_contains($action, 'title') => 'violet',
            str_contains($action, 'reference') => 'cyan',
            str_contains($action, 'research') => 'emerald',
            str_contains($action, 'review') => 'amber',
            str_contains($action, 'sempro') => 'rose',
            str_contains($action, 'section'), str_contains($action, 'draft') => 'blue',
            str_contains($action, 'payment'), str_contains($action, 'subscription') => 'indigo',
            default => 'gray',
        };
    };

    $iconTone = fn (string $tone, string $mode = 'light') => match ($tone) {
        'violet' => $mode === 'light' ? 'bg-violet-100 text-violet-700' : 'bg-violet-950/50 text-violet-400',
        'cyan' => $mode === 'light' ? 'bg-cyan-100 text-cyan-700' : 'bg-cyan-950/50 text-cyan-400',
        'emerald' => $mode === 'light' ? 'bg-emerald-100 text-emerald-700' : 'bg-emerald-950/50 text-emerald-400',
        'amber' => $mode === 'light' ? 'bg-amber-100 text-amber-700' : 'bg-amber-950/50 text-amber-400',
        'rose' => $mode === 'light' ? 'bg-rose-100 text-rose-700' : 'bg-rose-950/50 text-rose-400',
        'blue' => $mode === 'light' ? 'bg-blue-100 text-blue-700' : 'bg-blue-950/50 text-blue-400',
        'indigo' => $mode === 'light' ? 'bg-indigo-100 text-indigo-700' : 'bg-indigo-950/50 text-indigo-400',
        default => $mode === 'light' ? 'bg-gray-100 text-gray-500' : 'bg-gray-800 text-gray-400',
    };

    $quotaIcon = function (string $key): string {
        return match ($key) {
            'generate_titles' => 'sparkles',
            'ai_chat' => 'chart',
            'projects' => 'folder',
            default => 'clock',
        };
    };
@endphp

@section('header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-blue-600 to-blue-700 text-base font-bold text-white shadow-sm">
                {{ $initial }}
            </span>
            <div>
                <p class="ui-eyebrow">{{ now()->translatedFormat('l, j F Y') }}</p>
                <h1 class="mt-0.5 ui-page-title">{{ $greeting }}, {{ $firstName }}</h1>
                <p class="ui-page-sub">Ini ringkasan progres penelitian dan pemakaian AI Anda.</p>
            </div>
        </div>
        <a href="{{ route('projects.create') }}" class="ui-btn-primary ui-btn-sm">
            @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
            Project baru
        </a>
    </div>
@endsection

@section('content')
    @include('partials.onboarding')

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @include('partials.stat', [
            'label' => 'Project',
            'value' => \App\Support\Labels::angka($stats['projects']),
            'icon' => 'folder',
            'tone' => 'default',
        ])
        @include('partials.stat', [
            'label' => 'Bagian tersusun',
            'value' => \App\Support\Labels::angka($stats['sectionsDone']),
            'icon' => 'document',
            'tone' => $stats['sectionsDone'] > 0 ? 'positive' : 'default',
        ])
        @include('partials.stat', [
            'label' => 'Referensi',
            'value' => \App\Support\Labels::angka($stats['references']),
            'icon' => 'link',
            'tone' => $stats['references'] > 0 ? 'positive' : 'default',
        ])
        @include('partials.stat', [
            'label' => 'Sesi sempro',
            'value' => \App\Support\Labels::angka($stats['semproSessions']),
            'icon' => 'cap',
            'tone' => $stats['semproSessions'] > 0 ? 'positive' : 'default',
        ])
    </div>

    @include('partials.milestones', ['milestones' => $milestones])

    {{-- Baris tengah: project aktif (2/3) + kuota (1/3) --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="ui-card ui-card-hover flex flex-col lg:col-span-2">
            <div class="ui-card-head">
                <div>
                    <h2 class="ui-card-title">Project aktif</h2>
                    <p class="ui-card-sub">Yang paling terakhir Anda kerjakan.</p>
                </div>
                @if ($plan)
                    @include('partials.badge', ['tone' => 'violet', 'label' => 'Paket '.$plan['name']])
                @endif
            </div>

            @if ($activeProject)
                @php
                    $status = \App\Support\Labels::meta(\App\Support\Labels::PROJECT_STATUS, $activeProject->status);
                    $progress = max(0, min(100, (int) $activeProject->progress));
                @endphp

                <div class="flex flex-1 flex-col gap-5 p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[15px] font-semibold leading-snug text-gray-900 dark:text-white">
                                {{ $activeProject->title ?: $activeProject->name }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $activeProject->study_program ?: 'Program studi belum diisi' }}
                                <span class="text-gray-300 dark:text-gray-600">·</span>
                                {{ $activeProject->method ?: 'Metode belum dipilih' }}
                            </p>
                        </div>
                        @include('partials.badge', ['tone' => $status['tone'], 'label' => $status['label']])
                    </div>

                    <div>
                        <div class="mb-2 flex items-baseline justify-between">
                            <span class="ui-eyebrow">Progres naskah</span>
                            <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $progress }}%</span>
                        </div>
                        <div class="ui-progress ui-progress-md">
                            <div class="ui-progress-fill {{ $progress >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }}"
                                 style="width: {{ $progress }}%"></div>
                        </div>
                    </div>

                    <div class="mt-auto flex flex-wrap gap-2">
                        <a href="{{ route('draft.index', $activeProject->id) }}" class="ui-btn-primary ui-btn-sm">
                            @include('partials.icon', ['name' => 'document', 'size' => 'h-4 w-4'])
                            Lanjut menulis
                        </a>
                        <a href="{{ route('projects.show', $activeProject->id) }}" class="ui-btn-secondary ui-btn-sm">
                            Detail project
                            @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
                        </a>
                    </div>
                </div>
            @else
                <div class="p-5 sm:p-6">
                    <div class="ui-empty">
                        <span class="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 text-blue-500 shadow-sm dark:from-blue-950/50 dark:to-blue-900/50 dark:text-blue-400">
                            @include('partials.icon', ['name' => 'folder', 'size' => 'h-7 w-7'])
                        </span>
                        <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">Belum ada project</p>
                        <p class="mt-1 max-w-sm text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                            Buat project pertama, lalu mulai dari menentukan judul atau langsung menulis draft.
                        </p>
                        <a href="{{ route('projects.create') }}" class="ui-btn-primary ui-btn-sm mt-5">
                            @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
                            Buat project
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <div class="ui-card ui-card-hover flex flex-col">
            <div class="ui-card-head">
                <div>
                    <h2 class="ui-card-title">Kuota bulan ini</h2>
                    <p class="ui-card-sub">Direset tiap awal bulan.</p>
                </div>
                <a href="{{ route('quota') }}" class="text-xs font-semibold text-blue-600 underline decoration-blue-300 underline-offset-2 hover:decoration-blue-600 dark:text-blue-400 dark:decoration-blue-700">
                    Rincian
                </a>
            </div>

            <div class="flex-1 space-y-5 p-5 sm:p-6">
                @foreach ($quota as $key => $item)
                    @php
                        $used = (int) $item['used'];
                        $limit = $item['limit'];
                        $bar = $limit ? min(100, (int) round($used / max(1, $limit) * 100)) : 0;
                        $habis = $limit !== null && $used >= $limit;
                        $text = $limit === null
                            ? \App\Support\Labels::angka($used).' dipakai'
                            : \App\Support\Labels::angka($used).' / '.\App\Support\Labels::angka($limit);
                    @endphp
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                @include('partials.icon', ['name' => $quotaIcon($key), 'size' => 'h-3.5 w-3.5'])
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="text-[13px] font-medium capitalize text-gray-700 dark:text-gray-200">{{ str_replace('_', ' ', $key) }}</span>
                                    <span class="text-xs tabular-nums {{ $habis ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $text }}</span>
                                </div>
                                <div class="ui-progress mt-1.5">
                                    <div class="ui-progress-fill {{ $habis ? 'bg-rose-500' : ($bar >= 75 ? 'bg-amber-500' : 'bg-blue-600') }}"
                                         style="width: {{ $bar }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="rounded-lg bg-gray-50 px-3.5 py-3 dark:bg-gray-800/50">
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Token bulan ini</span>
                        <span class="font-semibold tabular-nums text-gray-700 dark:text-gray-200">{{ \App\Support\Labels::angka($quotaSummary['tokens']) }}</span>
                    </div>
                    <div class="mt-1 flex items-center justify-between gap-3 text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Biaya AI Anda</span>
                        <span class="font-semibold tabular-nums text-gray-700 dark:text-gray-200">{{ \App\Support\Labels::rupiah($quotaSummary['cost']) }}</span>
                    </div>
                    <a href="{{ route('quota') }}" class="ui-link mt-2.5 inline-block text-xs">Lihat rincian kuota &amp; biaya</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Baris bawah: project terbaru (2/3) + aktivitas (1/3) --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="ui-card lg:col-span-2">
            <div class="ui-card-head">
                <h2 class="ui-card-title">Project terbaru</h2>
                @if (count($projects))
                    <a href="{{ route('projects.index') }}" class="text-xs font-medium text-gray-500 hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-400">
                        Lihat semua →
                    </a>
                @endif
            </div>

            @if (count($projects))
                <ul class="ui-card-list">
                    @foreach ($projects as $project)
                        @php $progress = max(0, min(100, (int) $project->progress)); @endphp
                        <li>
                            <a href="{{ route('projects.show', $project->id) }}"
                               class="group flex items-center gap-4 px-5 py-3.5 transition hover:bg-gray-50/70 dark:hover:bg-gray-800/50">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-950/50 dark:text-blue-400 dark:group-hover:bg-blue-600 dark:group-hover:text-white">
                                    @include('partials.icon', ['name' => 'folder', 'size' => 'h-[18px] w-[18px]'])
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[13px] font-medium text-gray-900 dark:text-white">{{ $project->title ?: $project->name }}</span>
                                    <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                                        {{ $project->sections_count }} bagian
                                        <span class="text-gray-300 dark:text-gray-600">·</span>
                                        {{ $project->references_count }} referensi
                                    </span>
                                </span>

                                <span class="hidden w-28 shrink-0 sm:block">
                                    <span class="ui-progress block">
                                        <span class="ui-progress-fill bg-blue-600 block" style="width: {{ $progress }}%"></span>
                                    </span>
                                </span>

                                <span class="w-10 shrink-0 text-right text-xs font-semibold tabular-nums text-gray-900 dark:text-white">{{ $progress }}%</span>
                                @include('partials.icon', ['name' => 'chevron-right', 'size' => 'h-4 w-4', 'class' => 'shrink-0 text-gray-300 transition group-hover:text-gray-500 dark:text-gray-600 dark:group-hover:text-gray-400'])
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="flex flex-col items-center py-12 text-center">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                        @include('partials.icon', ['name' => 'folder', 'size' => 'h-6 w-6'])
                    </span>
                    <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Belum ada project</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Buat project pertama Anda untuk memulai.</p>
                </div>
            @endif
        </div>

        <div class="ui-card">
            <div class="ui-card-head">
                <h2 class="ui-card-title">Aktivitas terakhir</h2>
            </div>

            @if (count($activities))
                <ul class="ui-card-list">
                    @foreach ($activities as $log)
                        @php $tone = $activityTone((string) $log['action']); @endphp
                        <li class="flex items-start gap-3 px-5 py-3.5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $iconTone($tone) }} dark:{{ $iconTone($tone, 'dark') }}">
                                @include('partials.icon', ['name' => $activityIcon((string) $log['action']), 'size' => 'h-4 w-4'])
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[13px] leading-snug text-gray-700 dark:text-gray-200">{{ $log['description'] }}</span>
                                <span class="mt-1 block text-[11px] text-gray-400 dark:text-gray-500">{{ $log['at'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="flex flex-col items-center py-12 text-center">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                        @include('partials.icon', ['name' => 'clock', 'size' => 'h-6 w-6'])
                    </span>
                    <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Belum ada aktivitas</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Mulai kerjakan penelitian untuk melihat aktivitas.</p>
                </div>
            @endif
        </div>

        @if ($activeProject)
            @include('partials.sempro-checklist', ['checklist' => $checklist])
        @endif
    </div>
@endsection

