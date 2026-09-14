@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-card-lg sm:p-8">
        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-base font-semibold tracking-tight text-white">SA</span>
                <span class="text-lg font-semibold tracking-tight text-gray-900">Sarjana AI</span>
            </a>
            <h1 class="mt-6 text-2xl font-bold tracking-tight text-gray-900">Mulai penelitian lebih terarah</h1>
            <p class="mt-2 text-sm text-gray-500">Buat akun gratis. Dapatkan 5 judul AI, 10 sesi chat, dan 1 project — tanpa kartu kredit.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="name" class="ui-label">Nama lengkap</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        @include('partials.icon', ['name' => 'user', 'size' => 'h-5 w-5'])
                    </span>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                           autocomplete="name" placeholder="Nama Anda"
                           class="ui-input pl-10">
                </div>
                @error('name')
                    <p class="ui-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="ui-label">Email</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        @include('partials.icon', ['name' => 'mail', 'size' => 'h-5 w-5'])
                    </span>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
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
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                           placeholder="Minimal 8 karakter"
                           class="ui-input pl-10 pr-10">
                    <button type="button" data-show-password data-target="password"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition hover:text-gray-600"
                            aria-label="Tampilkan kata sandi">
                        @include('partials.icon', ['name' => 'eye', 'size' => 'h-5 w-5'])
                    </button>
                </div>
                @error('password')
                    <p class="ui-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="ui-label">Ulangi kata sandi</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        @include('partials.icon', ['name' => 'lock', 'size' => 'h-5 w-5'])
                    </span>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           autocomplete="new-password" placeholder="Tulis ulang kata sandi"
                           class="ui-input pl-10 pr-10">
                    <button type="button" data-show-password data-target="password_confirmation"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition hover:text-gray-600"
                            aria-label="Tampilkan kata sandi">
                        @include('partials.icon', ['name' => 'eye', 'size' => 'h-5 w-5'])
                    </button>
                </div>
            </div>

            <button type="submit" class="ui-btn-primary w-full justify-center">
                @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                Buat akun
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
                Daftar dengan Google
            </a>

            <p class="text-center text-sm text-gray-500">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="ui-link font-semibold text-blue-600">Masuk</a>
            </p>
        </form>
    </div>
@endsection