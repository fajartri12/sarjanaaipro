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

            <a href="{{ route('google.redirect') }}" class="flex w-full items-center justify-center gap-3 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M21.35 12.23c0-.71-.06-1.4-.18-2.05H12v3.88h5.24a4.48 4.48 0 0 1-1.94 2.94v2.45h3.14c1.84-1.7 2.91-4.2 2.91-7.22Z"/>
                    <path fill="#34A853" d="M12 21.5c2.63 0 4.84-.87 6.45-2.35l-3.14-2.45c-.87.58-1.98.92-3.31.92-2.54 0-4.69-1.72-5.46-4.03H3.3v2.53A9.74 9.74 0 0 0 12 21.5Z"/>
                    <path fill="#FBBC05" d="M6.54 13.59A5.86 5.86 0 0 1 6.23 12c0-.55.11-1.09.31-1.59V7.88H3.3A9.76 9.76 0 0 0 2.25 12c0 1.57.38 3.05 1.05 4.12l3.24-2.53Z"/>
                    <path fill="#EA4335" d="M12 6.38c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.84 3.47 14.63 2.5 12 2.5a9.74 9.74 0 0 0-8.7 5.38l3.24 2.53c.77-2.31 2.92-4.03 5.46-4.03Z"/>
                </svg>
                Lanjutkan dengan Google
            </a>

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
