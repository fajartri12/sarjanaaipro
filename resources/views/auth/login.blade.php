@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-card-lg sm:p-8">
        <!-- Header -->
        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-base font-semibold tracking-tight text-white">SA</span>
                <span class="text-lg font-semibold tracking-tight text-gray-900">Sarjana AI</span>
            </a>
            <h1 class="mt-6 text-2xl font-bold tracking-tight text-gray-900">Selamat datang kembali</h1>
            <p class="mt-2 text-sm text-gray-500">Lanjutkan penelitian Anda dari tempat terakhir Anda berhenti.</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" data-login-form>
            @csrf

            <div>
                <label for="email" class="ui-label">Email</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        @include('partials.icon', ['name' => 'mail', 'size' => 'h-5 w-5'])
                    </span>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           autocomplete="username" placeholder="nama@universitas.ac.id"
                           class="ui-input pl-10">
                </div>
                @error('email')
                    <p class="ui-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="ui-label">Kata sandi</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        @include('partials.icon', ['name' => 'lock', 'size' => 'h-5 w-5'])
                    </span>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           placeholder="Minimal 8 karakter"
                           class="ui-input pl-10 pr-10">
                    <button type="button" data-show-password
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition hover:text-gray-600"
                            aria-label="Tampilkan kata sandi">
                        @include('partials.icon', ['name' => 'eye', 'size' => 'h-5 w-5'])
                    </button>
                </div>
                @error('password')
                    <p class="ui-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2.5 text-[13px] text-gray-600">
                    <input type="checkbox" name="remember"
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-600">
                    Ingat saya
                </label>
                @if ($canResetPassword)
                    <a href="{{ route('password.request') }}" class="ui-link text-[13px]">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>

            <button type="submit" class="ui-btn-primary w-full justify-center">
                Masuk
                @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
            </button>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-white px-3 text-[11px] font-medium text-gray-400">atau</span>
                </div>
            </div>

            <p class="text-center text-sm text-gray-500">
                Belum punya akun?
                <a href="{{ route('register') }}" class="ui-link font-semibold text-blue-600">Daftar gratis</a>
            </p>
        </form>
    </div>

    <p class="mt-6 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} Sarjana AI. Hak cipta dilindungi.
    </p>
@endsection
