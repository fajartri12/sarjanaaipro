@extends('layouts.guest')

@section('title', 'Konfirmasi Kata Sandi')

@section('content')
    <h1 class="ui-page-title">Konfirmasi kata sandi</h1>
    <p class="ui-page-sub">
        Ini area yang dilindungi. Konfirmasi kata sandi Anda sebelum melanjutkan.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
        @csrf

        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-3">
            <label for="password" class="ui-label">Kata sandi</label>
            <input id="password" name="password" type="password" required autofocus
                   autocomplete="current-password"
                   class="ui-input mt-1.5">
            @error('password')
                <p class="ui-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end border-t border-gray-100 pt-5">
            <button type="submit" class="ui-btn-primary">
                @include('partials.icon', ['name' => 'shield', 'size' => 'h-4 w-4'])
                Konfirmasi
            </button>
        </div>
    </form>
@endsection
