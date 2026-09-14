@extends('layouts.app')

@section('title', 'Project')

@php
    $limitReached = $projectLimit !== null && $activeCount >= $projectLimit;
@endphp

@section('header')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="ui-page-title">Project</h1>
            <p class="ui-page-sub">Setiap project punya kerangka BAB, draft, referensi, dan sesi sempro sendiri.</p>
        </div>
        @if (! $limitReached)
            <a href="{{ route('projects.create') }}" class="ui-btn-primary ui-btn-sm">
                @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
                Project baru
            </a>
        @else
            <a href="{{ route('subscription.prices') }}" class="ui-btn-secondary ui-btn-sm">
                Batas tercapai · Upgrade
            </a>
        @endif
    </div>
@endsection

@section('content')
    @if ($limitReached)
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-amber-100 text-amber-700">
                @include('partials.icon', ['name' => 'warning', 'size' => 'h-4 w-4'])
            </span>
            <p class="flex-1 text-sm text-amber-900">
                Kuota project paket Anda sudah penuh ({{ $activeCount }}/{{ $projectLimit }}).
                <a href="{{ route('subscription.prices') }}" class="font-semibold underline underline-offset-2">Naikkan paket</a>
                untuk menambah project.
            </p>
        </div>
    @endif

    @if ($projects->count())
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach ($projects as $project)
                @php
                    $status = \App\Support\Labels::meta(\App\Support\Labels::PROJECT_STATUS, $project->status);
                    $progress = max(0, min(100, (int) $project->progress));
                @endphp
                <article class="ui-card ui-card-hover flex flex-col p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('projects.show', $project->id) }}"
                               class="block text-[15px] font-semibold leading-snug text-gray-900 hover:text-blue-700 hover:underline">
                                {{ $project->title ?: $project->name }}
                            </a>
                            @if ($project->title)
                                <p class="mt-0.5 truncate text-xs text-gray-500">{{ $project->name }}</p>
                            @endif
                        </div>
                        @include('partials.badge', ['tone' => $status['tone'], 'label' => $status['label']])
                    </div>

                    <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                        @foreach ([['Bagian', $project->sections_count], ['Referensi', $project->references_count], ['Progres', $progress.'%']] as [$label, $value])
                            <div class="rounded-lg bg-gray-50 px-2 py-2.5">
                                <dt class="ui-eyebrow">{{ $label }}</dt>
                                <dd class="mt-0.5 text-sm font-semibold tabular-nums text-gray-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <div class="ui-progress mt-4">
                        <div class="ui-progress-fill {{ $progress >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }}" style="width: {{ $progress }}%"></div>
                    </div>

                    <p class="mt-3 text-[11px] text-gray-400">Dibuat {{ \App\Support\Labels::tanggal($project->created_at) }}</p>

                    <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                        <a href="{{ route('draft.index', $project->id) }}" class="ui-btn-primary ui-btn-xs">Draft</a>
                        <a href="{{ route('references.index', ['project_id' => $project->id]) }}" class="ui-btn-secondary ui-btn-xs">Referensi</a>
                        <a href="{{ route('projects.edit', $project->id) }}" class="ui-btn-ghost ui-btn-xs ml-auto">Edit</a>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="ui-empty">
            <span class="grid h-12 w-12 place-items-center rounded-xl bg-white text-gray-400 shadow-card">
                @include('partials.icon', ['name' => 'folder', 'size' => 'h-6 w-6'])
            </span>
            <p class="mt-4 text-sm font-semibold text-gray-900">Belum ada project</p>
            <p class="mt-1 max-w-sm text-sm leading-relaxed text-gray-500">Mulai dengan membuat project, isi judul dan metode, lalu susun draft per bagian.</p>
            <a href="{{ route('projects.create') }}" class="ui-btn-primary ui-btn-sm mt-5">
                @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
                Buat project pertama
            </a>
        </div>
    @endif
@endsection
