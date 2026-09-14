@extends('layouts.app')

@section('title', $project->title ?: $project->name)

@php
    use App\Support\Labels;
    $status = Labels::meta(Labels::PROJECT_STATUS, $project->status);
    $selectedTitle = $project->titles->firstWhere('is_selected', true);
    $progress = max(0, min(100, (int) $project->progress));
    $refCount = $project->references->count();
    $docCount = $project->documents->count();
    $sectionCount = $project->sections->count();
    $doneSections = $project->sections->where('status', 'selesai')->count();
@endphp

@section('header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                Semua project
            </a>
            <h1 class="mt-2 ui-page-title">{{ $project->title ?: $project->name }}</h1>
            <p class="ui-page-sub">
                {{ $project->study_program ?: 'Program studi belum diisi' }}
                <span class="text-gray-300">·</span>
                {{ $project->university ?: 'Universitas belum diisi' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @include('partials.badge', ['tone' => 'violet', 'label' => Labels::DEGREE_LEVEL[$project->degree_level]['label'] ?? $project->degree_level])
            @include('partials.badge', ['tone' => $status['tone'], 'label' => $status['label']])
            <a href="{{ route('projects.chat.index', $project->id) }}" class="ui-btn-secondary ui-btn-sm gap-1.5">
                @include('partials.icon', ['name' => 'chat', 'size' => 'h-3.5 w-3.5'])
                Chat AI
            </a>
            <a href="{{ route('projects.edit', $project->id) }}" class="ui-btn-secondary ui-btn-sm">Edit</a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Baris statistik --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Progres --}}
        <div class="ui-card ui-card-hover p-5">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="ui-eyebrow">Progres</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight tabular-nums text-gray-900">{{ $progress }}%</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $doneSections }}/{{ $sectionCount }} bagian selesai</p>
                </div>
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-blue-100 text-blue-700">
                    @include('partials.icon', ['name' => 'chart', 'size' => 'h-6 w-6'])
                </span>
            </div>
        </div>

        {{-- Judul terpilih --}}
        <div class="ui-card ui-card-hover p-5 sm:col-span-2 lg:col-span-1">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="ui-eyebrow">Judul terpilih</p>
                    <p class="mt-2 line-clamp-2 text-sm font-medium leading-snug text-gray-900">
                        {{ $selectedTitle?->title ?: 'Belum ada judul yang dipilih' }}
                    </p>
                    @if ($selectedTitle && $selectedTitle->relevance)
                        <p class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-violet-50 px-2.5 py-0.5 text-[11px] font-semibold text-violet-700">
                            Relevansi {{ $selectedTitle->relevance }}
                        </p>
                    @endif
                    @if (! $selectedTitle)
                        <a href="{{ route('titles.index') }}" class="ui-hint mt-2 inline-block text-blue-600 underline underline-offset-2">Cari judul →</a>
                    @endif
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-600">
                    @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-5 w-5'])
                </span>
            </div>
        </div>

        {{-- Referensi --}}
        <div class="ui-card ui-card-hover p-5">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="ui-eyebrow">Referensi</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight tabular-nums text-gray-900">{{ Labels::angka($refCount) }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $refCount === 0 ? 'Belum ada' : 'Jurnal & buku' }}</p>
                </div>
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
                    @include('partials.icon', ['name' => 'link', 'size' => 'h-6 w-6'])
                </span>
            </div>
        </div>

        {{-- Dokumen jurnal --}}
        <div class="ui-card ui-card-hover p-5">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="ui-eyebrow">Dokumen jurnal</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight tabular-nums text-gray-900">{{ Labels::angka($docCount) }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $docCount === 0 ? 'Belum diunggah' : 'PDF terindeks' }}</p>
                </div>
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-amber-100 text-amber-700">
                    @include('partials.icon', ['name' => 'book', 'size' => 'h-6 w-6'])
                </span>
            </div>
        </div>
    </div>

    @php $daysLeft = $project->daysUntilDeadline(); @endphp
    @if ($project->deadline)
        <div class="ui-card flex flex-wrap items-center justify-between gap-3 px-5 py-4 {{ $daysLeft !== null && $daysLeft <= 3 ? 'border-rose-200 bg-rose-50' : '' }}">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $daysLeft !== null && $daysLeft <= 3 ? 'bg-rose-100 text-rose-600' : 'bg-gray-100 text-gray-600' }}">
                    @include('partials.icon', ['name' => 'clock', 'size' => 'h-5 w-5'])
                </span>
                <div>
                    <p class="ui-eyebrow">Deadline</p>
                    <p class="text-sm font-semibold text-gray-900">
                        {{ \Illuminate\Support\Carbon::parse($project->deadline)->translatedFormat('d F Y') }}
                    </p>
                </div>
            </div>
            @if ($daysLeft !== null)
                <p class="text-sm font-medium {{ $daysLeft < 0 ? 'text-rose-600' : ($daysLeft <= 3 ? 'text-rose-600' : 'text-gray-600') }}">
                    @if ($daysLeft < 0)
                        Terlambat {{ abs($daysLeft) }} hari
                    @elseif ($daysLeft === 0)
                        Deadline hari ini
                    @else
                        {{ $daysLeft }} hari lagi
                    @endif
                </p>
            @endif
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Kerangka penelitian --}}
        <div class="ui-card lg:col-span-2">
            <div class="ui-card-head">
                <div>
                    <h2 class="ui-card-title">Kerangka penelitian</h2>
                    <p class="ui-card-sub">Bagian yang sudah jadi ditandai selesai.</p>
                </div>
                <a href="{{ route('draft.index', $project->id) }}" class="ui-btn-primary ui-btn-xs">Buka draft</a>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($project->sections as $section)
                    @php
                        $sectionMeta = Labels::meta(Labels::SECTION_STATUS, $section->status);
                        $done = $section->status === 'selesai';
                    @endphp
                    <a href="{{ route('draft.show', [$project->id, $section->id]) }}"
                       class="group flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50/80">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg tabular-nums text-[11px] font-semibold
                                     {{ $done ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                            @if ($done)
                                @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                            @else
                                {{ $section->key }}
                            @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-medium {{ $done ? 'text-gray-900' : 'text-gray-700' }}">
                                {{ $section->chapter }} · {{ $section->title }}
                            </span>
                            <span class="block text-[11px] text-gray-400">{{ number_format($section->word_count) }} kata</span>
                        </span>
                        @include('partials.badge', ['tone' => $sectionMeta['tone'], 'label' => $sectionMeta['label']])
                        @include('partials.icon', ['name' => 'chevron-right', 'size' => 'h-4 w-4', 'class' => 'shrink-0 text-gray-300 transition group-hover:text-gray-500'])
                    </a>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-gray-500">Kerangka belum dibuat.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            {{-- Aksi cepat --}}
            <div class="ui-card">
                <div class="ui-card-head">
                    <h2 class="ui-card-title">Aksi cepat</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ([
                        ['titles.index', 'sparkles', 'Buat / pilih judul', 'Cari judul penelitian yang tepat'],
                        ['research.index', 'book', 'Unggah jurnal', 'Upload PDF referensi'],
                        ['sempro.index', 'cap', 'Simulasi sempro', 'Latihan sidang proposal'],
                        ['projects.edit', 'document', 'Edit project', 'Ubah data project'],
                    ] as [$routeName, $icon, $label, $desc])
                        <a href="{{ $routeName === 'projects.edit' ? route($routeName, $project->id) : route($routeName) }}"
                           class="group flex items-center gap-3 px-5 py-3.5 transition hover:bg-gray-50/80">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                                @include('partials.icon', ['name' => $icon, 'size' => 'h-[18px] w-[18px]'])
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium text-gray-800">{{ $label }}</span>
                                <span class="block text-xs text-gray-400">{{ $desc }}</span>
                            </span>
                            @include('partials.icon', ['name' => 'chevron-right', 'size' => 'h-4 w-4', 'class' => 'shrink-0 text-gray-300 transition group-hover:text-gray-500'])
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Judul kandidat --}}
            <div class="ui-card">
                <div class="ui-card-head">
                    <h2 class="ui-card-title">Judul kandidat</h2>
                    <a href="{{ route('titles.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">Kelola →</a>
                </div>
                @if ($project->titles->count())
                    <div class="divide-y divide-gray-100">
                        @foreach ($project->titles->take(5) as $title)
                            <div class="flex items-center justify-between gap-3 px-5 py-3">
                                <p class="min-w-0 flex-1 truncate text-[13px] font-medium text-gray-800">
                                    {{ $title->title }}
                                </p>
                                <span class="shrink-0 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold tabular-nums text-blue-700">
                                    {{ $title->relevance ?? '—' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="px-5 py-8 text-center text-sm text-gray-500">
                        Belum ada judul.
                        <a href="{{ route('titles.index') }}" class="font-semibold text-blue-600 hover:underline">Generate</a>
                    </p>
                @endif
            </div>

            {{-- Sesi sempro --}}
            <div class="ui-card">
                <div class="ui-card-head">
                    <h2 class="ui-card-title">Sesi sempro</h2>
                    <a href="{{ route('sempro.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">Mulai →</a>
                </div>
                @if ($project->semproSessions->count())
                    <div class="divide-y divide-gray-100">
                        @foreach ($project->semproSessions as $session)
                            <a href="{{ route('sempro.show', $session->id) }}"
                               class="group flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-gray-50/80">
                                <div class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-gray-800">
                                        {{ $session->score === null ? 'Belum dinilai' : 'Skor '.$session->score }}
                                    </span>
                                    <span class="block text-xs text-gray-400">{{ $session->question_count }} soal</span>
                                </div>
                                <span class="shrink-0 text-xs tabular-nums text-gray-400">
                                    {{ Labels::tanggal($session->created_at) }}
                                </span>
                                @include('partials.icon', ['name' => 'chevron-right', 'size' => 'h-4 w-4', 'class' => 'shrink-0 text-gray-300 transition group-hover:text-gray-500'])
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="px-5 py-8 text-center text-sm text-gray-500">Belum ada sesi.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Hapus project --}}
    <div class="ui-card overflow-hidden border-rose-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-4 px-5 py-5">
            <div class="flex items-start gap-4">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-600">
                    @include('partials.icon', ['name' => 'warning', 'size' => 'h-5 w-5'])
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Hapus project ini</p>
                    <p class="mt-0.5 max-w-md text-xs leading-relaxed text-gray-500">
                        Seluruh draft, referensi, dan sesi sempro di dalamnya ikut terhapus permanen.
                        Tindakan ini tidak bisa dibatalkan.
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('projects.destroy', $project->id) }}"
                  onsubmit="return confirm('Yakin hapus project ini? Seluruh draft, referensi, dan sesi sempro ikut terhapus permanen.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="ui-btn-danger ui-btn-sm">
                    @include('partials.icon', ['name' => 'trash', 'size' => 'h-4 w-4'])
                    Hapus project
                </button>
            </form>
        </div>
    </div>
@endsection
