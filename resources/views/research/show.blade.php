@extends('layouts.app')

@section('title', $document->title ?: 'Dokumen')

@php
    $meta = \App\Support\Labels::meta(\App\Support\Labels::DOCUMENT_STATUS, $document->status);
    $metadata = is_array($document->metadata) ? $document->metadata : [];
    $rows = [
        'purpose' => 'Tujuan penelitian',
        'method' => 'Metode',
        'dataset' => 'Data yang dipakai',
        'result' => 'Hasil',
        'limitations' => 'Keterbatasan',
        'research_gap' => 'Research gap',
        'relevance' => 'Relevansi ke studi Anda',
    ];
@endphp

@section('header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('research.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                Bahan riset
            </a>
            <h1 class="mt-2 ui-page-title">{{ $document->title ?: $document->original_name }}</h1>
            <p class="ui-page-sub">
                {{ $document->author ?: 'Tanpa penulis' }}
                <span class="text-gray-300">·</span>
                {{ $document->year ?: '—' }}
                <span class="text-gray-300">·</span>
                {{ \App\Support\Labels::angka($document->size / 1024) }} KB
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @include('partials.badge', ['tone' => $meta['tone'], 'label' => $meta['label']])
            <a href="{{ route('research.download', $document->id) }}" class="ui-btn-secondary ui-btn-sm">Unduh</a>
            <form method="POST" action="{{ route('research.analyze', $document->id) }}"
                  data-ai-stages='{"title":"Menganalisis dokumen","stages":["Membaca isi dokumen","Menilai metode","Merangkum temuan"]}'>
                @csrf
                <button type="submit" class="ui-btn-primary ui-btn-sm">
                    @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-4 w-4'])
                    Analisa dengan AI
                </button>
            </form>
        </div>
    </div>
@endsection

@section('content')
    @if ($document->status === 'failed')
        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white text-rose-600">
                @include('partials.icon', ['name' => 'warning', 'size' => 'h-4 w-4'])
            </span>
            <p class="text-[13px] leading-relaxed text-rose-800">
                Ekstraksi teks gagal. {{ $document->failure_reason ?: 'Coba unggah ulang dokumen.' }}
            </p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="ui-card p-5 lg:col-span-2">
            <h2 class="ui-card-title">Hasil analisa</h2>

            @if (count(array_filter($metadata)))
                <dl class="mt-5 space-y-5">
                    @foreach ($rows as $key => $label)
                        <div>
                            <dt class="ui-eyebrow">{{ $label }}</dt>
                            @if (! empty($metadata[$key]))
                                <dd class="mt-1.5 whitespace-pre-line text-[13px] leading-relaxed text-gray-700">{{ $metadata[$key] }}</dd>
                            @else
                                <dd class="mt-1.5 text-[13px] text-gray-400">Belum dianalisa.</dd>
                            @endif
                        </div>
                    @endforeach
                </dl>
            @else
                <div class="ui-empty mt-5">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gray-100 text-gray-400">
                        @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-5 w-5'])
                    </span>
                    <p class="mt-3.5 text-sm font-medium text-gray-900">Belum ada hasil analisa</p>
                    <p class="mt-1 max-w-sm text-[13px] leading-relaxed text-gray-500">
                        Klik <b class="font-medium text-gray-800">Analisa dengan AI</b> untuk mengekstrak isi penting dokumen ini.
                    </p>
                </div>
            @endif

            <p class="mt-6 border-t border-gray-100 pt-4 text-[11px] text-gray-400">
                Teks terbagi jadi {{ $document->chunks->count() }} potongan untuk pencarian.
            </p>
        </div>

        <div class="ui-card flex flex-col">
            <div class="ui-card-head">
                <h2 class="ui-card-title">Teks lengkap</h2>
                <span class="ui-eyebrow">Ekstraksi PDF</span>
            </div>
            <div class="max-h-[28rem] flex-1 overflow-y-auto rounded-b-xl bg-gray-50 p-5">
                @if ($document->extracted_text)
                    <pre class="whitespace-pre-wrap font-serif text-xs leading-[1.8] text-gray-700">{{ $document->extracted_text }}</pre>
                @else
                    <p class="text-[13px] leading-relaxed text-gray-500">
                        Teks belum tersedia — tunggu proses ekstraksi selesai atau ulangi prosesnya.
                    </p>
                @endif
            </div>
        </div>
    </div>
@endsection
