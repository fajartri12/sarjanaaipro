@extends('layouts.guest')

@section('title', 'Atur Ulang Kata Sandi')

@section('content')
    <h1 class="ui-page-title">Atur ulang kata sandi</h1>
    <p class="ui-page-sub">Buat kata sandi baru untuk akun Anda.</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="ui-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus
                   class="ui-input mt-1.5">
            @error('email')
                <p class="ui-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="ui-label">Kata sandi baru</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="ui-input mt-1.5">
            @error('password')
                <p class="ui-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="ui-label">Ulangi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   autocomplete="new-password"
                   class="ui-input mt-1.5">
        </div>

        <div class="flex items-center justify-end border-t border-gray-100 pt-5">
            <button type="submit" class="ui-btn-primary">
                @include('partials.icon', ['name' => 'shield', 'size' => 'h-4 w-4'])
                Simpan kata sandi
            </button>
        </div>
    </form>
@endsection
