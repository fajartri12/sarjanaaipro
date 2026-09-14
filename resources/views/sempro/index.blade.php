@extends('layouts.app')

@section('title', 'Latihan Sempro')

@php
    $statusMeta = \App\Support\Labels::SESSION_STATUS;
    $smallInput = 'ui-input';
@endphp

@section('header')
    <div>
        <h1 class="ui-page-title">Latihan sempro</h1>
        <p class="ui-page-sub">Simulasi sidang proposal: AI bertanya seperti penguji, jawaban Anda dinilai per aspek.</p>
    </div>
@endsection

@section('content')
    <div class="ui-card p-5">
        <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-600">
                @include('partials.icon', ['name' => 'cap', 'size' => 'h-[18px] w-[18px]'])
            </span>
            <div>
                <h2 class="ui-card-title">Mulai sesi baru</h2>
                <p class="ui-card-sub">Pilih project dan jumlah pertanyaan yang ingin dilatih.</p>
            </div>
        </div>

        @if ($projects->count())
            <form method="POST" action="{{ route('sempro.store') }}" class="mt-5 flex flex-wrap items-end gap-4 border-t border-gray-100 pt-5"
                  data-ai-stages='{"title":"Menyiapkan pertanyaan sidang","stages":["Membaca judul dan draft","Menyusun pertanyaan penguji"]}'>
                @csrf
                <div class="min-w-[220px] flex-1">
                    <label for="project_id" class="ui-label">Project</label>
                    <select id="project_id" name="project_id" class="{{ $smallInput }} mt-1.5 w-full">
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    @error('project_id') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="category" class="ui-label">Fokus kategori</label>
                    <select id="category" name="category" class="{{ $smallInput }} mt-1.5">
                        <option value="">Semua kategori</option>
                        @foreach (['metodologi', 'landasan teori', 'rumusan masalah', 'tujuan', 'hasil', 'pembahasan', 'umum'] as $cat)
                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="difficulty" class="ui-label">Tingkat</label>
                    <select id="difficulty" name="difficulty" class="{{ $smallInput }} mt-1.5">
                        <option value="">Campur</option>
                        @foreach (['dasar', 'menengah', 'sulit'] as $level)
                            <option value="{{ $level }}" @selected(old('difficulty') === $level)>{{ ucfirst($level) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="question_count" class="ui-label">Jumlah pertanyaan</label>
                    <input id="question_count" name="question_count" type="number" min="3" max="10"
                           value="{{ old('question_count', 5) }}" class="{{ $smallInput }} mt-1.5 w-24">
                    @error('question_count') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="ui-btn-primary">
                    @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4'])
                    Mulai latihan
                </button>
            </form>
            <p class="mt-3 text-[11px] text-gray-400">
                Pertanyaan diambil dari bank pertanyaan bawaan lebih dulu — hemat kuota AI. AI dipakai hanya bila bank belum mencakup kategori yang dipilih.
            </p>
        @else
            <div class="mt-5 border-t border-gray-100 pt-5">
                <div class="ui-empty">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gray-100 text-gray-400">
                        @include('partials.icon', ['name' => 'folder', 'size' => 'h-5 w-5'])
                    </span>
                    <p class="mt-3.5 text-sm font-medium text-gray-900">Belum ada project</p>
                    <p class="mt-1 max-w-sm text-[13px] leading-relaxed text-gray-500">
                        Buat satu project dulu untuk mulai latihan sempro.
                    </p>
                    <a href="{{ route('projects.create') }}" class="ui-btn-primary ui-btn-sm mt-5">
                        @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
                        Buat project
                    </a>
                </div>
            </div>
        @endif
    </div>

    <div class="ui-table-wrap">
        <div class="border-b border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="ui-card-title">Riwayat sesi latihan sempro</h2>
            <p class="ui-card-sub">{{ \App\Support\Labels::angka($sessions->count()) }} sesi latihan simulasi tersimpan.</p>
        </div>

        @if ($sessions->count())
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="ui-th">Project Penelitian</th>
                            <th scope="col" class="ui-th">Jumlah Pertanyaan</th>
                            <th scope="col" class="ui-th">Waktu Latihan</th>
                            <th scope="col" class="ui-th">Status & Skor</th>
                            <th scope="col" class="ui-th text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($sessions as $session)
                            @php
                                $meta = $statusMeta[$session->status] ?? ['label' => $session->status, 'tone' => 'gray'];
                                $routeName = $session->status === 'finished' ? 'sempro.result' : 'sempro.show';
                            @endphp
                            <tr class="ui-tr">
                                <th scope="row" class="ui-td font-medium text-gray-900 dark:text-white">
                                    <a href="{{ route($routeName, $session->id) }}"
                                       class="font-semibold text-gray-900 transition hover:text-blue-700 dark:text-white dark:hover:text-blue-400">
                                        {{ $session->project?->name ?? 'Project' }}
                                    </a>
                                </th>
                                <td class="ui-td font-mono text-xs tabular-nums text-gray-600 dark:text-gray-300">
                                    {{ $session->question_count }} soal
                                </td>
                                <td class="ui-td font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ \App\Support\Labels::tanggal($session->created_at) }}
                                </td>
                                <td class="ui-td">
                                    <div class="flex items-center gap-2">
                                        @include('partials.badge', ['tone' => $meta['tone'], 'label' => $meta['label']])
                                        @if ($session->status === 'finished')
                                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                                Skor {{ $session->score ?? 0 }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="ui-td">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route($routeName, $session->id) }}"
                                           class="ui-btn-table">
                                            @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-3.5 w-3.5'])
                                            Buka
                                        </a>
                                        <form method="POST" action="{{ route('sempro.destroy', $session->id) }}"
                                              onsubmit="return confirm('Hapus sesi ini beserta seluruh jawabannya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="ui-btn-table-danger">
                                                @include('partials.icon', ['name' => 'trash', 'size' => 'h-3.5 w-3.5'])
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ui-empty border-0 bg-transparent py-16">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'cap', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3.5 text-sm font-semibold text-gray-900 dark:text-white">Belum ada sesi</p>
                <p class="mt-1 max-w-sm text-[13px] leading-relaxed text-gray-500">
                    Mulai simulasi baru untuk melatih presentasi dan antisipasi pertanyaan dosen penguji.
                </p>
            </div>
        @endif
    </div>
@endsection
