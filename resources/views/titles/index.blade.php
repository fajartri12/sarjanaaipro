@extends('layouts.app')

@section('title', 'Judul Penelitian')

@php
    $input = 'ui-input mt-1.5';

    // Skor tinggi = hijau, sedang = kuning, rendah = merah.
    $tone = function ($score) {
        if ($score === null || $score === '') {
            return 'gray';
        }

        return $score >= 75 ? 'green' : ($score >= 50 ? 'amber' : 'red');
    };
@endphp

@section('header')
    <div>
        <h1 class="ui-page-title">Judul Penelitian</h1>
        <p class="ui-page-sub">Rumuskan alternatif judul penelitian, temukan research gap, dan bandingkan skor kelayakannya.</p>
    </div>
@endsection

@section('content')
    <div class="ui-card p-6 sm:p-7">
        <div class="mb-6 flex items-start gap-3.5">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700">
                @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-5 w-5'])
            </span>
            <div>
                <h2 class="ui-card-title text-lg">Generator Alternatif Judul</h2>
                <p class="ui-card-sub">Tentukan parameter topik penelitian Anda, AI akan merumuskan 5 alternatif judul lengkap dengan analisis kelayakan.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('titles.generate') }}" class="space-y-5"
              data-ai-stages='{"title":"Merumuskan judul","stages":["Membaca topik dan objek","Menyusun alternatif judul","Menilai kelayakan"]}'>
            @csrf
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="topic" class="ui-label flex items-center justify-between">
                        <span>Topik / Masalah Penelitian Utama <span class="text-rose-500">*</span></span>
                        <span class="text-xs font-normal text-gray-400">Wajib diisi</span>
                    </label>
                    <input id="topic" name="topic" type="text" required
                           value="{{ old('topic') }}" placeholder="Contoh: Pengaruh adopsi artificial intelligence terhadap efisiensi operasional UMKM"
                           class="{{ $input }} text-sm font-medium">
                    @error('topic') <p class="ui-error">{{ $message }}</p> @enderror
                </div>

                @if ($projects->count())
                    <div>
                        <label for="project_id" class="ui-label">Simpan ke Project</label>
                        <select id="project_id" name="project_id" class="{{ $input }}">
                            <option value="">Tanpa project (Mandiri)</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                        @error('project_id') <p class="ui-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label for="study_program" class="ui-label">Program Studi</label>
                    <input id="study_program" name="study_program" type="text"
                           value="{{ old('study_program') }}" placeholder="Contoh: Sistem Informasi" class="{{ $input }}">
                    @error('study_program') <p class="ui-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="object" class="ui-label">Objek Penelitian</label>
                    <input id="object" name="object" type="text" value="{{ old('object') }}"
                           placeholder="Contoh: UMKM Sektor Kuliner" class="{{ $input }}">
                    @error('object') <p class="ui-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="location" class="ui-label">Lokasi Penelitian</label>
                    <input id="location" name="location" type="text" value="{{ old('location') }}"
                           placeholder="Contoh: Kota Malang" class="{{ $input }}">
                    @error('location') <p class="ui-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="method" class="ui-label">Metode yang Diminati</label>
                    <input id="method" name="method" type="text" value="{{ old('method') }}"
                           placeholder="Contoh: PLS-SEM / Kualitatif" class="{{ $input }}">
                    @error('method') <p class="ui-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="keywords" class="ui-label">Kata Kunci</label>
                    <input id="keywords" name="keywords" type="text" value="{{ old('keywords') }}"
                           placeholder="Contoh: adopsi AI, efisiensi, UMKM" class="{{ $input }}">
                    @error('keywords') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-100 pt-5">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4 text-violet-500'])
                    <span>AI akan menghasilkan 5 judul beserta skor relevansi, novelty, dan research gap.</span>
                </div>
                <button type="submit" class="ui-btn-primary">
                    @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4'])
                    Buat 5 Alternatif Judul
                </button>
            </div>
        </form>
    </div>

    <div class="ui-card">
        <div class="ui-card-head">
            <div class="flex items-center gap-2.5">
                <h2 class="ui-card-title">Daftar Alternatif Judul</h2>
                <span class="inline-flex items-center rounded-full bg-gray-200/80 px-2.5 py-0.5 text-xs font-semibold text-gray-700">
                    {{ $titles->count() }}
                </span>
            </div>
            <p class="ui-card-sub text-xs text-gray-500">Bandingkan skor penilaian dan pilih judul terbaik untuk penelitian Anda.</p>
        </div>

        @if ($titles->count())
            <ul class="ui-card-list">
                @foreach ($titles as $title)
                    <li class="p-5 transition hover:bg-gray-50/70 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($title->is_selected)
                                        @include('partials.badge', ['tone' => 'green', 'label' => 'Judul Terpilih'])
                                    @endif
                                    @if ($title->project)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500">
                                            @include('partials.icon', ['name' => 'folder', 'size' => 'h-3.5 w-3.5 text-gray-400'])
                                            {{ $title->project->name }}
                                        </span>
                                    @endif
                                </div>
                                <h3 class="mt-2 text-base font-semibold leading-snug tracking-tight text-gray-900">
                                    {{ $title->title }}
                                </h3>

                                @if ($title->research_gap)
                                    <div class="mt-3 rounded-lg bg-gray-50 p-3 text-[13px] leading-relaxed text-gray-600">
                                        <span class="font-semibold text-gray-800">Research Gap:</span> {{ $title->research_gap }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-1.5 self-start pt-1">
                                @include('partials.badge', ['tone' => $tone($title->relevance), 'label' => 'Relevansi: '.($title->relevance ?? '—')])
                                @include('partials.badge', ['tone' => $tone($title->novelty), 'label' => 'Kebaruan: '.($title->novelty ?? '—')])
                                @include('partials.badge', ['tone' => $tone($title->gap_score), 'label' => 'Gap: '.($title->gap_score ?? '—')])
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100/80 pt-3.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('titles.show', $title->id) }}" class="ui-btn-secondary ui-btn-xs">
                                    @include('partials.icon', ['name' => 'document', 'size' => 'h-3.5 w-3.5 text-gray-500'])
                                    Detail & Analisis
                                </a>
                                @if ($title->project_id && ! $title->is_selected)
                                    <form method="POST" action="{{ route('titles.select', $title->id) }}">
                                        @csrf
                                        <input type="hidden" name="project_id" value="{{ $title->project_id }}">
                                        <button type="submit" class="ui-btn-primary ui-btn-xs">
                                            @include('partials.icon', ['name' => 'check', 'size' => 'h-3.5 w-3.5'])
                                            Jadikan judul project
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <span class="text-[11px] text-gray-400">
                                {{ $title->created_at ? $title->created_at->diffForHumans() : '' }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="px-5 py-12">
                <div class="ui-empty border-0 bg-transparent px-0 py-0">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-violet-100 text-violet-600">
                        @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-6 w-6'])
                    </span>
                    <p class="mt-3.5 text-base font-semibold text-gray-900">Belum ada alternatif judul</p>
                    <p class="mt-1.5 max-w-md text-sm leading-relaxed text-gray-500">
                        Isi form parameter penelitian di atas dan klik <strong>Buat 5 Alternatif Judul</strong> untuk mulai mengeksplorasi ide penelitian yang siap diteliti.
                    </p>
                </div>
            </div>
        @endif
    </div>
@endsection
