@extends('layouts.app')

@section('title', 'Referensi')

@php
    $labels = \App\Support\Labels::REFERENCE_TYPE;
    $input = 'ui-input mt-1.5';
    $smallInput = 'ui-input';
@endphp

@section('header')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="ui-page-title">Referensi</h1>
            <p class="ui-page-sub">Simpan sumber, impor dari DOI, lalu susun daftar pustaka otomatis.</p>
        </div>
        <a href="#tambah-referensi" class="ui-btn-primary ui-btn-sm">
            @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
            Tambah referensi
        </a>
    </div>
@endsection

@section('content')
    <div class="ui-card p-5">
        <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-600">
                @include('partials.icon', ['name' => 'link', 'size' => 'h-[18px] w-[18px]'])
            </span>
            <div>
                <h2 class="ui-card-title">Impor dari DOI</h2>
                <p class="ui-card-sub">Tempel DOI atau tautan doi.org — metadata diambil dari CrossRef.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('references.doi') }}" class="mt-4 flex flex-wrap items-start gap-3">
            @csrf
            <div class="min-w-[220px] flex-1">
                <input name="doi" type="text" placeholder="10.1016/j.jbusres.2020.01.001" class="{{ $smallInput }}">
                @error('doi') <p class="ui-error">{{ $message }}</p> @enderror
            </div>
            <select name="project_id" class="{{ $smallInput }} w-auto">
                <option value="">Tanpa project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="ui-btn-secondary">
                Ambil dari DOI
            </button>
        </form>
    </div>

    <details id="tambah-referensi" class="group ui-card overflow-hidden scroll-mt-24">
        <summary class="ui-card-head transition hover:bg-gray-200/60">
            <span class="flex items-start gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-gray-100 text-gray-500">
                    @include('partials.icon', ['name' => 'document', 'size' => 'h-[18px] w-[18px]'])
                </span>
                <span>
                    <span class="block ui-card-title">Referensi baru</span>
                    <span class="block ui-card-sub">Isi manual kalau DOI tidak tersedia.</span>
                </span>
            </span>
            <span class="shrink-0 text-gray-400 transition group-open:rotate-180">
                @include('partials.icon', ['name' => 'chevron-down', 'size' => 'h-5 w-5'])
            </span>
        </summary>

        <form method="POST" action="{{ route('references.store') }}" class="grid gap-5 border-t border-gray-100 p-5 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2">
                <label for="ref_title" class="ui-label">Judul *</label>
                <input id="ref_title" name="title" type="text" required value="{{ old('title') }}" class="{{ $input }}">
                @error('title') <p class="ui-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="ref_authors" class="ui-label">Penulis</label>
                <input id="ref_authors" name="authors" type="text" value="{{ old('authors') }}"
                       placeholder="Nama Belakang, Nama Depan" class="{{ $input }}">
            </div>

            <div>
                <label for="ref_year" class="ui-label">Tahun</label>
                <input id="ref_year" name="year" type="number" min="1900" max="2100"
                       value="{{ old('year', now()->year) }}" class="{{ $input }}">
            </div>

            <div>
                <label for="ref_type" class="ui-label">Jenis</label>
                <select id="ref_type" name="type" class="{{ $input }}">
                    @foreach ($labels as $key => $label)
                        <option value="{{ $key }}" @selected(old('type', 'journal') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="ref_project_id" class="ui-label">Project</label>
                <select id="ref_project_id" name="project_id" class="{{ $input }}">
                    <option value="">Tanpa project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="ref_container" class="ui-label">Jurnal/Media</label>
                <input id="ref_container" name="container" type="text" value="{{ old('container') }}" class="{{ $input }}">
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label for="ref_volume" class="ui-label">Vol</label>
                    <input id="ref_volume" name="volume" type="text" value="{{ old('volume') }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="ref_issue" class="ui-label">No</label>
                    <input id="ref_issue" name="issue" type="text" value="{{ old('issue') }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="ref_pages" class="ui-label">Hal</label>
                    <input id="ref_pages" name="pages" type="text" value="{{ old('pages') }}" class="{{ $input }}">
                </div>
            </div>

            <div>
                <label for="ref_doi" class="ui-label">DOI</label>
                <input id="ref_doi" name="doi" type="text" value="{{ old('doi') }}" class="{{ $input }}">
            </div>

            <div>
                <label for="ref_url" class="ui-label">URL</label>
                <input id="ref_url" name="url" type="text" value="{{ old('url') }}" class="{{ $input }}">
                @error('url') <p class="ui-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="ref_city" class="ui-label">Kota</label>
                <input id="ref_city" name="city" type="text" value="{{ old('city') }}" class="{{ $input }}">
            </div>

            <div>
                <label for="ref_publisher" class="ui-label">Penerbit</label>
                <input id="ref_publisher" name="publisher" type="text" value="{{ old('publisher') }}" class="{{ $input }}">
            </div>

            <div class="sm:col-span-2">
                <label for="ref_notes" class="ui-label">Catatan</label>
                <textarea id="ref_notes" name="notes" rows="2" class="{{ $input }}">{{ old('notes') }}</textarea>
            </div>

            <div class="sm:col-span-2 border-t border-gray-100 pt-5">
                <button type="submit" class="ui-btn-primary">
                    @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                    Simpan referensi
                </button>
            </div>
        </form>
    </details>

    <form method="GET" action="{{ route('references.index') }}"
          class="ui-card flex flex-wrap items-center gap-3 px-5 py-4">
        <div class="min-w-[200px] flex-1">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul atau penulis…"
                   class="{{ $smallInput }}">
        </div>
        <select name="type" class="{{ $smallInput }} w-auto">
            <option value="">Semua jenis</option>
            @foreach ($labels as $key => $label)
                <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="project_id" class="{{ $smallInput }} w-auto">
            <option value="">Semua project</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected((string) ($filters['project_id'] ?? '') === (string) $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="ui-btn-secondary ui-btn-sm">Terapkan</button>
        @if (array_filter($filters))
            <a href="{{ route('references.index') }}" class="ui-btn-ghost ui-btn-xs">Reset</a>
        @endif
    </form>

    <form method="POST" action="{{ route('references.bibliography') }}" class="ui-table-wrap">
        @csrf

        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div>
                <h2 class="ui-card-title">Daftar referensi</h2>
                <p class="ui-card-sub">Centang sumber yang ingin dimasukkan ke daftar pustaka otomatis.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <select name="style" class="rounded-xl border border-gray-200 bg-gray-50/70 p-2 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <option value="apa">Format APA 7</option>
                    <option value="ieee">Format IEEE</option>
                    <option value="harvard">Format Harvard</option>
                </select>
                <button type="submit" class="ui-btn-primary ui-btn-sm shadow-sm">
                    @include('partials.icon', ['name' => 'document', 'size' => 'h-4 w-4'])
                    Susun daftar pustaka
                </button>
            </div>
        </div>

        @if ($references->count())
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="w-4 p-4 text-center">
                                <span class="sr-only">Pilih</span>
                            </th>
                            <th scope="col" class="ui-th">Judul & Penulis</th>
                            <th scope="col" class="ui-th">Jenis</th>
                            <th scope="col" class="ui-th">Tahun</th>
                            <th scope="col" class="ui-th">Project / Tautan</th>
                            <th scope="col" class="ui-th text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($references as $reference)
                            <tr class="ui-tr">
                                <td class="w-4 p-4 text-center">
                                    <input type="checkbox" name="reference_ids[]" value="{{ $reference->id }}"
                                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800">
                                </td>
                                <th scope="row" class="ui-td font-normal text-gray-900 dark:text-white">
                                    <p class="font-medium leading-snug text-gray-900 dark:text-white">{{ $reference->title }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $reference->authors ?: 'Tanpa penulis' }}
                                        @if ($reference->container) · <span class="italic">{{ $reference->container }}</span> @endif
                                    </p>
                                </th>
                                <td class="ui-td">
                                    @include('partials.badge', ['tone' => 'gray', 'label' => $labels[$reference->type] ?? $reference->type])
                                </td>
                                <td class="ui-td font-mono text-xs tabular-nums text-gray-600 dark:text-gray-300">
                                    {{ $reference->year ?: '—' }}
                                </td>
                                <td class="ui-td">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($reference->project)
                                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                                {{ $reference->project->name }}
                                            </span>
                                        @endif
                                        @if ($reference->url)
                                            <a href="{{ $reference->url }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">
                                                @include('partials.icon', ['name' => 'link', 'size' => 'h-3 w-3'])
                                                Buka URL
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="ui-td text-right">
                                    <button type="submit" form="hapus-referensi-{{ $reference->id }}"
                                            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-50 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-950/40">
                                        @include('partials.icon', ['name' => 'trash', 'size' => 'h-3.5 w-3.5'])
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                {{ $references->links() }}
            </div>
        @else
            <div class="ui-empty border-0 bg-transparent py-16">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'link', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3.5 text-sm font-semibold text-gray-900 dark:text-white">Belum ada referensi</p>
                <p class="mt-1 text-[13px] text-gray-500">Impor dari DOI atau tambah manual untuk mulai menyusun daftar pustaka.</p>
            </div>
        @endif
    </form>

    {{-- Form hapus terpisah supaya tidak bersarang di dalam form daftar pustaka. --}}
    @foreach ($references as $reference)
        <form id="hapus-referensi-{{ $reference->id }}" method="POST"
              action="{{ route('references.destroy', $reference->id) }}"
              onsubmit="return confirm('Hapus referensi ini?')" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endsection
