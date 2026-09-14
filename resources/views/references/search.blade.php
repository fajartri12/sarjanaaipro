@extends('layouts.app')

@section('title', 'Cari Jurnal')

@php
    $labels = \App\Support\Labels::REFERENCE_TYPE;
    $input = 'ui-input mt-1.5';
@endphp

@section('header')
    <div>
        <h1 class="ui-page-title">Cari jurnal</h1>
        <p class="ui-page-sub">
            Pencarian memakai CrossRef, jadi judul, penulis, dan DOI diambil dari catatan resmi
            penerbit — bukan dikarang. Hasil yang Anda simpan masuk ke daftar referensi project.
        </p>
    </div>
@endsection

@section('content')
    <form method="GET" action="{{ route('references.search') }}" class="ui-card p-5 sm:p-6">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="q" class="ui-label">Kata kunci, judul, atau topik</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 dark:text-gray-500">
                        @include('partials.icon', ['name' => 'search', 'size' => 'h-4 w-4'])
                    </span>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}"
                           placeholder="mis. AI adoption UMKM"
                           class="block w-full rounded-lg border-gray-300 py-2.5 pl-10 pr-3 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500" autofocus>
                </div>
            </div>

            <div>
                <label for="year_from" class="ui-label">Tahun dari</label>
                <input id="year_from" name="year_from" type="number" min="1900" max="2100"
                       value="{{ $filters['year_from'] ?? '' }}" class="{{ $input }}"
                       placeholder="2020">
            </div>

            <div>
                <label for="year_to" class="ui-label">Sampai</label>
                <input id="year_to" name="year_to" type="number" min="1900" max="2100"
                       value="{{ $filters['year_to'] ?? '' }}" class="{{ $input }}"
                       placeholder="{{ date('Y') }}">
            </div>

            <div class="sm:col-span-2">
                <label for="project_id" class="ui-label">Simpan ke project</label>
                <select id="project_id" name="project_id" class="{{ $input }}">
                    <option value="">Tanpa project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected(($filters['project_id'] ?? '') == $project->id)>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                <p class="ui-hint">Dipakai saat menekan Simpan, bukan untuk menyaring hasil.</p>
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" class="ui-btn-primary">
                    @include('partials.icon', ['name' => 'search', 'size' => 'h-4 w-4'])
                    Cari
                </button>
                @if ($query !== '')
                    <a href="{{ route('references.search') }}" class="ui-btn-ghost">Reset</a>
                @endif
            </div>
        </div>
    </form>

    @if ($query === '')
        <div class="ui-empty">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 text-blue-500 shadow-sm dark:from-blue-950/50 dark:to-blue-900/50 dark:text-blue-400">
                @include('partials.icon', ['name' => 'search', 'size' => 'h-7 w-7'])
            </span>
            <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">Belum ada pencarian</p>
            <p class="mt-1 max-w-md text-[13px] leading-relaxed text-gray-500 dark:text-gray-400">
                Masukkan kata kunci di atas. Gunakan istilah yang dipakai di literatur internasional
                supaya hasilnya lebih banyak.
            </p>
        </div>
    @elseif (empty($results))
        <div class="ui-empty">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100 text-amber-500 shadow-sm dark:from-amber-950/50 dark:to-amber-900/50 dark:text-amber-400">
                @include('partials.icon', ['name' => 'warning', 'size' => 'h-7 w-7'])
            </span>
            <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">Tidak ada hasil untuk “{{ $query }}”</p>
            <p class="mt-1 max-w-md text-[13px] leading-relaxed text-gray-500 dark:text-gray-400">
                Coba kata kunci yang lebih umum, longgarkan rentang tahun, atau cari sinonimnya
                dalam bahasa Inggris.
            </p>
        </div>
    @else
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                    @include('partials.icon', ['name' => 'search', 'size' => 'h-5 w-5'])
                </span>
                <div>
                    <h2 class="ui-card-title">{{ count($results) }} hasil</h2>
                    <p class="ui-card-sub">Diurutkan menurut tingkat kecocokan CrossRef.</p>
                </div>
            </div>

            <div class="grid gap-4">
                @foreach ($results as $item)
                    @php
                        $doi = $item['doi'];
                        $saved = $doi && isset($savedDois[$doi]);
                        $typeLabel = $labels[$item['type']] ?? ucfirst($item['type']);
                        $typeTone = match ($item['type']) {
                            'journal' => 'blue',
                            'book', 'chapter' => 'violet',
                            'thesis' => 'emerald',
                            'conference' => 'amber',
                            'web' => 'cyan',
                            default => 'gray',
                        };
                    @endphp

                    <div class="ui-card ui-card-hover overflow-hidden">
                        <div class="p-5 sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @include('partials.badge', ['tone' => $typeTone, 'label' => $typeLabel])
                                        @if ($item['year'])
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                {{ $item['year'] }}
                                            </span>
                                        @endif
                                        @if ($saved)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">
                                                @include('partials.icon', ['name' => 'check', 'size' => 'h-3 w-3'])
                                                Tersimpan
                                            </span>
                                        @endif
                                    </div>

                                    <h3 class="mt-3 text-sm font-semibold leading-snug text-gray-900 dark:text-white">
                                        {{ $item['title'] }}
                                    </h3>

                                    <p class="mt-1.5 text-[13px] text-gray-600 dark:text-gray-300">
                                        {{ $item['authors'] ?: 'Penulis tidak tercatat' }}
                                    </p>

                                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[13px] text-gray-500 dark:text-gray-400">
                                        @if ($item['container'])
                                            <span class="italic">{{ $item['container'] }}</span>
                                        @endif
                                        @if ($item['volume'])
                                            <span>vol. {{ $item['volume'] }}</span>
                                        @endif
                                        @if ($item['issue'])
                                            <span>({{ $item['issue'] }})</span>
                                        @endif
                                        @if ($item['pages'])
                                            <span>hlm. {{ $item['pages'] }}</span>
                                        @endif
                                        @if ($item['publisher'])
                                            <span>· {{ $item['publisher'] }}</span>
                                        @endif
                                    </div>

                                    @if ($item['abstract'])
                                        <details class="group mt-3">
                                            <summary class="inline-flex cursor-pointer items-center gap-1.5 text-[13px] font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                                @include('partials.icon', ['name' => 'document', 'size' => 'h-3.5 w-3.5'])
                                                Lihat abstrak
                                                @include('partials.icon', ['name' => 'chevron-down', 'size' => 'h-3.5 w-3.5', 'class' => 'transition group-open:rotate-180'])
                                            </summary>
                                            <p class="mt-2 max-w-3xl text-[13px] leading-relaxed text-gray-600 dark:text-gray-300">
                                                {{ $item['abstract'] }}
                                            </p>
                                        </details>
                                    @endif

                                    @if ($doi)
                                        <p class="mt-3 text-[11px] font-mono text-gray-400 dark:text-gray-500">
                                            DOI: {{ $doi }}
                                        </p>
                                    @else
                                        <p class="mt-3 text-[11px] text-gray-400 dark:text-gray-500">
                                            DOI tidak tercatat — sitasi tidak bisa dibuat otomatis
                                        </p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 flex-col gap-2">
                                    @if ($doi)
                                        <form method="POST" action="{{ route('references.doi') }}">
                                            @csrf
                                            <input type="hidden" name="doi" value="{{ $doi }}">
                                            <input type="hidden" name="project_id" value="{{ $filters['project_id'] ?? '' }}">
                                            <button type="submit"
                                                    class="ui-btn-secondary {{ $saved ? 'ui-btn-save' : '' }}"
                                                    @disabled($saved)>
                                                @include('partials.icon', ['name' => $saved ? 'check' : 'plus', 'size' => 'h-4 w-4'])
                                                {{ $saved ? 'Tersimpan' : 'Simpan' }}
                                            </button>
                                        </form>

                                        <a href="https://doi.org/{{ $doi }}" target="_blank" rel="noopener noreferrer"
                                           class="ui-btn-ghost">
                                            @include('partials.icon', ['name' => 'link', 'size' => 'h-4 w-4'])
                                            Buka sumber
                                        </a>
                                    @else
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer"
                                           class="ui-btn-ghost">
                                            @include('partials.icon', ['name' => 'link', 'size' => 'h-4 w-4'])
                                            Buka sumber
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
