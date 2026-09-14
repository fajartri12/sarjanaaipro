@extends('layouts.guest')

@section('title', 'Lupa Kata Sandi')

@section('content')
    <h1 class="ui-page-title">Lupa kata sandi?</h1>
    <p class="ui-page-sub">
        Masukkan email Anda. Kami kirimkan tautan untuk membuat kata sandi baru.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="email" class="ui-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="ui-input mt-1.5">
            @error('email')
                <p class="ui-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-5">
            <a href="{{ route('login') }}" class="ui-link text-[13px]">Kembali</a>
            <button type="submit" class="ui-btn-primary">
                @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
                Kirim tautan
            </button>
        </div>
    </form>
@endsection
