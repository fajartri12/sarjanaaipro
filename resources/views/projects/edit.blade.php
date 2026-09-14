@extends('layouts.app')

@section('title', 'Edit project')

@section('header')
    <div>
        <a href="{{ route('projects.show', $project['id']) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 transition hover:text-blue-700">
            @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
            {{ $project['name'] }}
        </a>
        <h1 class="mt-2 ui-page-title">Edit project</h1>
        <p class="ui-page-sub">Perbarui identitas penelitian Anda.</p>
    </div>
@endsection

@section('content')
    <div class="ui-card max-w-3xl p-5 sm:p-6">
        @include('projects._form', ['project' => $project])
    </div>
@endsection
