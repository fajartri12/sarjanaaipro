@extends('layouts.app')

@section('title', 'Pencarian')

@section('header')
    <div>
        <h1 class="ui-page-title">Pencarian</h1>
        <p class="ui-page-sub">Cari project, bagian draft, jurnal tersimpan, dan referensi sekaligus.</p>
    </div>
@endsection

@section('content')
    <form method="GET" action="{{ route('search') }}" class="ui-card p-5 sm:p-6">
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                @include('partials.icon', ['name' => 'search', 'size' => 'h-5 w-5'])
            </span>
            <input type="search" name="q" value="{{ $q }}"
                   placeholder="Cari project, draft, jurnal, referensi…"
                   class="block w-full rounded-xl border-gray-300 py-3.5 pl-12 pr-4 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600"
                   autofocus autocomplete="off">
        </div>
    </form>

    @if (strlen($q) < 2)
        <div class="mt-12 text-center">
            <p class="text-sm text-gray-400">Ketik minimal 2 karakter untuk mulai mencari.</p>
        </div>
    @elseif ($results->isEmpty())
        <div class="mt-12 text-center">
            <p class="text-sm text-gray-400">Tidak ditemukan hasil untuk "<strong>{{ $q }}</strong>".</p>
        </div>
    @else
        <p class="mt-4 text-xs text-gray-400">{{ $results->count() }} hasil untuk "<strong>{{ $q }}</strong>"</p>

        <div class="mt-3 space-y-1">
            @foreach ($results as $r)
                <a href="{{ $r['url'] }}"
                   class="flex items-start gap-3 rounded-xl border border-transparent bg-white px-4 py-3.5 shadow-sm transition hover:border-gray-200 hover:shadow-card-md">
                    <span class="mt-0.5 shrink-0 text-gray-400">
                        @include('partials.icon', ['name' => match ($r['type']) {
                            'project' => 'folder',
                            'section' => 'document',
                            'document' => 'book',
                            'reference' => 'link',
                            default => 'search',
                        }, 'size' => 'h-[18px] w-[18px]'])
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-gray-900">{{ $r['label'] }}</span>
                        @if ($r['sub'])
                            <span class="mt-0.5 block truncate text-[13px] text-gray-500">{{ $r['sub'] }}</span>
                        @endif
                    </span>
                    @if ($r['meta'])
                        <span class="mt-0.5 shrink-0 text-xs text-gray-400">{{ $r['meta'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
@endsection