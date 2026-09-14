@extends('layouts.app')

@section('title', $title->title)

@php
    $metrics = [
        ['relevance', 'Relevansi', 'Kesesuaian dengan bidang studi'],
        ['novelty', 'Kebaruan', 'Pembeda dari riset terdahulu'],
        ['feasibility', 'Kelayakan', 'Kemudahan akses data & metodologi'],
        ['complexity', 'Kompleksitas', 'Tingkat kerumitan pengerjaan'],
        ['gap_score', 'Kekuatan Gap', 'Signifikansi celah riset'],
    ];
    $variables = is_array($title->variables) ? $title->variables : [];
@endphp

@section('header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 max-w-3xl">
            <a href="{{ route('titles.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                Kembali ke daftar judul
            </a>
            <h1 class="mt-2.5 text-xl font-bold leading-snug tracking-tight text-gray-900 sm:text-2xl">
                {{ $title->title }}
            </h1>
            @if ($title->project)
                <p class="mt-1 text-xs text-gray-500">
                    Project: <a href="{{ route('projects.show', $title->project->id) }}" class="ui-link font-medium">{{ $title->project->name }}</a>
                </p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($title->is_selected)
                @include('partials.badge', ['tone' => 'green', 'label' => 'Judul Terpilih'])
            @elseif ($title->project_id)
                <form method="POST" action="{{ route('titles.select', $title->id) }}">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $title->project_id }}">
                    <button type="submit" class="ui-btn-primary ui-btn-sm">
                        @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                        Jadikan Judul Project
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection

@section('content')
    @php
        // Kompleksitas rendah itu bagus, jadi barnya dibalik.
        $scoreTone = function ($value, $invert = false) {
            if ($value === null || $value === '') {
                return 'bg-gray-300';
            }

            $v = $invert ? 100 - (int) $value : (int) $value;

            return $v >= 75 ? 'bg-emerald-500' : ($v >= 50 ? 'bg-amber-500' : 'bg-rose-500');
        };
    @endphp

    {{-- Kartu Metrik Penilaian --}}
    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($metrics as [$key, $label, $hint])
            @php
                $value = $title->{$key};
                $isInverted = $key === 'complexity';
                $barWidth = $value === null || $value === '' ? 0 : max(0, min(100, (int) $value));
                if ($isInverted && $value !== null && $value !== '') {
                    $barWidth = max(0, min(100, 100 - (int) $value));
                }
            @endphp
            <div class="ui-card p-4 transition duration-150 hover:shadow-card-md">
                <p class="ui-eyebrow">{{ $label }}</p>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-bold leading-none tabular-nums tracking-tight text-gray-900">
                        {{ $value ?? '—' }}
                    </span>
                    @if ($value !== null && $value !== '')
                        <span class="text-[11px] font-medium text-gray-400">/100</span>
                    @endif
                </div>
                <div class="ui-progress mt-3">
                    <div class="ui-progress-fill {{ $scoreTone($value, $isInverted) }}" style="width: {{ $barWidth }}%"></div>
                </div>
                <p class="mt-2 text-[11px] leading-relaxed text-gray-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    {{-- Konten Analisis & Sidebar --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="ui-card lg:col-span-2">
            <div class="ui-card-head">
                <div>
                    <h2 class="ui-card-title">Analisis Mendalam & Rekomendasi</h2>
                    <p class="ui-card-sub text-xs">Evaluasi komprehensif terkait celah riset, kelayakan, dan potensi risiko.</p>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                {{-- Form Analisis Ulang --}}
                <form method="POST" action="{{ route('titles.analyze', $title->id) }}" class="flex flex-wrap items-end gap-3 rounded-xl bg-gray-50/80 p-4"
                      data-ai-stages='{"title":"Menganalisis judul","stages":["Membaca judul dan konteks","Menimbang metodologi","Menyusun rekomendasi"]}'>
                    @csrf
                    <div class="min-w-[240px] flex-1">
                        <label for="context" class="ui-label-sm">Konteks Tambahan (Opsional)</label>
                        <input id="context" name="context" type="text" placeholder="mis. data hanya tersedia periode 1 tahun terakhir..."
                               class="ui-input mt-1.5 text-xs">
                    </div>
                    <button type="submit" class="ui-btn-primary ui-btn-sm">
                        @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4'])
                        Analisis Ulang
                    </button>
                </form>

                <div class="mt-6 space-y-4">
                    @if ($title->recommendation)
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                            <div class="flex items-center gap-2 text-emerald-800">
                                @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4 text-emerald-600'])
                                <h3 class="text-xs font-semibold uppercase tracking-wider">Rekomendasi Peneliti</h3>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap text-[13px] leading-relaxed text-emerald-950">{{ $title->recommendation }}</p>
                        </div>
                    @endif

                    @if ($title->research_gap)
                        <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                            <div class="flex items-center gap-2 text-blue-800">
                                @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4 text-blue-600'])
                                <h3 class="text-xs font-semibold uppercase tracking-wider">Research Gap (Celah Riset)</h3>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap text-[13px] leading-relaxed text-blue-950">{{ $title->research_gap }}</p>
                        </div>
                    @endif

                    @if ($title->risk)
                        <div class="rounded-xl border border-amber-100 bg-amber-50/50 p-4">
                            <div class="flex items-center gap-2 text-amber-800">
                                @include('partials.icon', ['name' => 'warning', 'size' => 'h-4 w-4 text-amber-600'])
                                <h3 class="text-xs font-semibold uppercase tracking-wider">Potensi Risiko & Tantangan</h3>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap text-[13px] leading-relaxed text-amber-950">{{ $title->risk }}</p>
                        </div>
                    @endif

                    @if ($title->description)
                        <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                            <div class="flex items-center gap-2 text-gray-700">
                                @include('partials.icon', ['name' => 'document', 'size' => 'h-4 w-4 text-gray-500'])
                                <h3 class="text-xs font-semibold uppercase tracking-wider">Deskripsi & Ruang Lingkup</h3>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap text-[13px] leading-relaxed text-gray-700">{{ $title->description }}</p>
                        </div>
                    @endif

                    @if (! $title->recommendation && ! $title->research_gap && ! $title->risk && ! $title->description)
                        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50/50 py-8 text-center">
                            <p class="text-sm font-medium text-gray-700">Belum ada analisis mendalam</p>
                            <p class="mt-1 text-xs text-gray-500">Klik tombol <strong>Analisis Ulang</strong> di atas untuk menjalankan analisis AI.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Variabel Penelitian --}}
            <div class="ui-card">
                <div class="ui-card-head">
                    <h2 class="ui-card-title">Variabel Penelitian</h2>
                    @if (count($variables))
                        <span class="inline-flex items-center rounded-full bg-gray-200/80 px-2 py-0.5 text-[11px] font-semibold text-gray-700">
                            {{ count($variables) }} item
                        </span>
                    @endif
                </div>
                <div class="p-5">
                    @if (count($variables))
                        <ul class="space-y-2.5">
                            @foreach ($variables as $variable)
                                <li class="flex items-start gap-2.5 rounded-lg bg-gray-50 p-2.5 text-[13px] leading-relaxed text-gray-700">
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600"></span>
                                    <span>{{ is_array($variable) ? implode(' — ', $variable) : $variable }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-xs text-gray-500">Belum terdeteksi secara otomatis.</p>
                    @endif
                </div>
            </div>

            {{-- Parameter Input --}}
            <div class="ui-card">
                <div class="ui-card-head">
                    <h2 class="ui-card-title">Parameter Input</h2>
                </div>
                <dl class="space-y-3 p-5 text-[13px]">
                    @foreach ([
                        ['Program Studi', $title->study_program],
                        ['Topik Utama', $title->topic],
                        ['Objek', $title->object],
                        ['Lokasi', $title->location],
                        ['Metode', $title->method],
                        ['Kata Kunci', $title->keywords],
                    ] as [$label, $value])
                        @if ($value)
                            <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-2.5 last:border-0 last:pb-0">
                                <dt class="shrink-0 text-xs font-medium text-gray-500">{{ $label }}</dt>
                                <dd class="text-right font-medium text-gray-900">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
@endsection
