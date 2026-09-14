@extends('layouts.app')

@section('title', 'Admin · Project')

@php
    $projectMeta = \App\Support\Labels::PROJECT_STATUS;
    $tanggal = fn ($value) => \App\Support\Labels::tanggal($value);
@endphp

@section('header')
    <div class="space-y-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                Admin
            </a>
            <h1 class="ui-page-title mt-2">Project</h1>
            <p class="ui-page-sub">Kelola project penelitian mahasiswa.</p>
        </div>
        @include('partials.admin-nav')
    </div>
@endsection

@section('content')
    <div class="ui-table-wrap">
        {{-- Filter Bar --}}
        <div class="border-b border-gray-100 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <form method="GET" action="{{ route('admin.projects') }}" class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-[240px] flex-1">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400">
                        @include('partials.icon', ['name' => 'search', 'size' => 'h-4 w-4'])
                    </div>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama project, judul, atau prodi..."
                           class="block w-full rounded-xl border border-gray-200 bg-gray-50/70 p-2.5 ps-9 text-sm text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900/50 dark:text-white">
                </div>
                <select name="status" class="rounded-xl border border-gray-200 bg-gray-50/70 p-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <option value="">Semua status</option>
                    @foreach ($projectMeta as $key => $meta)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="ui-btn-secondary ui-btn-sm">
                    @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                    Terapkan
                </button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.projects') }}" class="ui-link text-[13px]">Reset</a>
                @endif
            </form>
        </div>

        @if ($projects->count())
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="ui-th">Project</th>
                            <th scope="col" class="ui-th">Pemilik</th>
                            <th scope="col" class="ui-th">Prodi & Universitas</th>
                            <th scope="col" class="ui-th text-center">Progress</th>
                            <th scope="col" class="ui-th">Status</th>
                            <th scope="col" class="ui-th">Dibuat</th>
                            <th scope="col" class="ui-th text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($projects as $project)
                            @php $meta = $projectMeta[$project->status] ?? ['label' => $project->status, 'tone' => 'gray']; @endphp
                            <tr class="ui-tr">
                                <th scope="row" class="ui-td">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-indigo-100 text-xs font-bold uppercase text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300">
                                            {{ mb_substr($project->name, 0, 1) }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 dark:text-white truncate max-w-[200px]">{{ $project->name }}</div>
                                            @if ($project->title)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]">{{ $project->title }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </th>
                                <td class="ui-td">
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $project->user?->name ?? '—' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $project->user?->email }}</div>
                                    </div>
                                </td>
                                <td class="ui-td text-sm text-gray-600 dark:text-gray-300">
                                    @if ($project->study_program)
                                        <div class="font-medium">{{ $project->study_program }}</div>
                                    @endif
                                    @if ($project->university)
                                        <div class="text-xs text-gray-500">{{ $project->university }}</div>
                                    @endif
                                    <div class="mt-1 text-[11px] font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ \App\Support\Labels::DEGREE_LEVEL[$project->degree_level]['label'] ?? $project->degree_level }}
                                    </div>
                                </td>
                                <td class="ui-td text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <div class="h-1.5 w-16 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div class="h-full rounded-full bg-blue-600 transition-all" style="width: {{ $project->progress }}%"></div>
                                        </div>
                                        <span class="text-xs font-medium tabular-nums text-gray-600 dark:text-gray-400">{{ $project->progress }}%</span>
                                    </div>
                                </td>
                                <td class="ui-td text-center">
                                    @include('partials.badge', ['tone' => $meta['tone'], 'label' => $meta['label']])
                                </td>
                                <td class="ui-td whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    {{ $tanggal($project->created_at) }}
                                </td>
                                <td class="ui-td text-right">
                                    <details class="group relative inline-block text-left">
                                        <summary class="ui-btn-secondary ui-btn-xs list-none cursor-pointer">
                                            @include('partials.icon', ['name' => 'plus', 'size' => 'h-3.5 w-3.5'])
                                            Kelola
                                        </summary>

                                        <div class="absolute right-0 z-30 mt-2 w-64 rounded-2xl border border-gray-100 bg-white p-4 shadow-xl dark:border-gray-700 dark:bg-gray-800">
                                            <div class="mb-3 border-b border-gray-100 pb-3 dark:border-gray-700">
                                                <div class="text-xs font-semibold text-gray-500 uppercase">Statistik</div>
                                                <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                                                    <div class="text-gray-600 dark:text-gray-400">Bagian<span class="float-right text-gray-900 dark:text-white">{{ $project->sections_count }}</span></div>
                                                    <div class="text-gray-600 dark:text-gray-400">Dokumen<span class="float-right text-gray-900 dark:text-white">{{ $project->documents_count }}</span></div>
                                                    <div class="text-gray-600 dark:text-gray-400">Judul<span class="float-right text-gray-900 dark:text-white">{{ $project->titles_count }}</span></div>
                                                    <div class="text-gray-600 dark:text-gray-400">Referensi<span class="float-right text-gray-900 dark:text-white">{{ $project->references_count }}</span></div>
                                                </div>
                                            </div>

                                            <form method="POST" action="{{ route('admin.projects.update', $project->id) }}" class="space-y-3 text-left">
                                                @csrf
                                                @method('PATCH')
                                                <div>
                                                    <label for="status-{{ $project->id }}" class="ui-label-sm">Ubah Status</label>
                                                    <select id="status-{{ $project->id }}" name="status" class="ui-input-sm mt-1 w-full">
                                                        <option value="aktif" @selected($project->status === 'aktif')>Aktif</option>
                                                        <option value="selesai" @selected($project->status === 'selesai')>Selesai</option>
                                                        <option value="ditunda" @selected($project->status === 'ditunda')>Ditunda</option>
                                                    </select>
                                                </div>
                                                <div class="border-t border-gray-100 pt-3 dark:border-gray-700">
                                                    <button type="submit" class="ui-btn-primary ui-btn-xs w-full justify-center">
                                                        Simpan Perubahan
                                                    </button>
                                                </div>
                                            </form>

                                            <div class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                                                <a href="{{ route('projects.show', $project->id) }}" target="_blank"
                                                   class="ui-btn-ghost ui-btn-xs w-full justify-center">
                                                    @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-3.5 w-3.5'])
                                                    Buka Project
                                                </a>
                                            </div>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                {{ $projects->links() }}
            </div>
        @else
            <div class="ui-empty border-0 bg-transparent py-16">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'folder', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3.5 text-sm font-semibold text-gray-900 dark:text-white">Tidak ada project ditemukan</p>
                <p class="mt-1 text-[13px] text-gray-500">Coba ubah kata kunci pencarian atau filter status.</p>
            </div>
        @endif
    </div>
@endsection