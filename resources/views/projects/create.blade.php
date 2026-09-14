@extends('layouts.app')

@section('title', 'Project baru')

@section('header')
    <div>
        <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 transition hover:text-blue-700">
            @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
            Semua project
        </a>
        <h1 class="mt-2 ui-page-title">Project baru</h1>
        <p class="ui-page-sub">Kerangka BAB dibuat otomatis sesuai jenjang setelah project tersimpan.</p>
    </div>
@endsection

@section('content')
    <div class="max-w-3xl space-y-5">
        @if ($projectLimit !== null)
            <div class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white text-gray-500">
                    @include('partials.icon', ['name' => 'inbox', 'size' => 'h-4 w-4'])
                </span>
                <p class="text-[13px] leading-relaxed text-gray-600">
                    Plan Anda mengizinkan <span class="font-semibold text-blue-700">{{ $projectLimit }}</span> project aktif,
                    terpakai {{ $activeCount }}.
                </p>
            </div>
        @endif

        <div class="ui-card p-5 sm:p-6">
            @include('projects._form', ['project' => null])
        </div>
    </div>
@endsection
