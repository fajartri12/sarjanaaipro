@extends('layouts.app')

@section('title', 'Profil')

@section('header')
    <div>
        <h1 class="ui-page-title">Profil</h1>
        <p class="ui-page-sub">Kelola informasi akun, kata sandi, dan pengaturan keamanan.</p>
    </div>
@endsection

@section('content')
    <!-- Header profil -->
    <div class="ui-card p-5 sm:p-6">
        <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
            <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-2xl font-bold text-white shadow-md">
                {{ mb_strtoupper(mb_substr(auth()->user()->name ?? '?', 0, 1)) }}
            </span>
            <div class="text-center sm:text-left">
                <h2 class="text-lg font-semibold text-gray-900">{{ auth()->user()->name }}</h2>
                <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>
                @if (auth()->user()->hasVerifiedEmail())
                    <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Terverifikasi
                    </span>
                @else
                    <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-medium text-amber-700 ring-1 ring-amber-200">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        Belum verifikasi
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Informasi akun -->
    <div class="ui-card p-5 sm:p-6">
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-blue-600">
                @include('partials.icon', ['name' => 'user', 'size' => 'h-5 w-5'])
            </span>
            <div>
                <h2 class="ui-card-title">Informasi akun</h2>
                <p class="ui-card-sub">Perbarui nama dan email Anda.</p>
            </div>
        </div>

        <form id="profile-info-form" method="POST" action="{{ route('profile.update') }}" class="mt-5 max-w-xl space-y-5">
            @csrf
            @method('patch')

            <div>
                <label for="name" class="ui-label">Nama</label>
                <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" required autofocus
                       autocomplete="name"
                       class="ui-input mt-1.5">
                @error('name')
                    <p class="ui-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="ui-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required
                       autocomplete="username"
                       class="ui-input mt-1.5">
                @error('email')
                    <p class="ui-error">{{ $message }}</p>
                @enderror

                @if ($mustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-[13px] leading-relaxed text-amber-800">
                            Email Anda belum terverifikasi.
                            <button type="submit" form="send-verification"
                                    class="font-semibold underline underline-offset-2 hover:text-amber-950">
                                Kirim ulang tautan verifikasi.
                            </button>
                        </p>
                        @if ($status === 'verification-link-sent')
                            <p class="mt-2 flex items-center gap-1.5 text-[13px] font-medium text-emerald-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Tautan verifikasi baru sudah dikirim.
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div>
                <label for="degree_level" class="ui-label">Jenjang</label>
                <select id="degree_level" name="degree_level" class="ui-input mt-1.5">
                    @foreach (\App\Support\Labels::DEGREE_LEVEL as $key => $level)
                        <option value="{{ $key }}" @selected(old('degree_level', auth()->user()->degree_level) === $key)>
                            {{ $key }} — {{ $level['label'] }}
                        </option>
                    @endforeach
                </select>
                <p class="ui-hint mt-1.5">Dipakai sebagai bawaan saat membuat project baru.</p>
                @error('degree_level')
                    <p class="ui-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5">
                <button type="submit" class="ui-btn-primary">
                    @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                    Simpan perubahan
                </button>
                @if (session('status') === 'profile-updated')
                    <span class="inline-flex items-center gap-1.5 text-[13px] font-medium text-emerald-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Tersimpan
                    </span>
                @endif
            </div>
        </form>

        <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">
            @csrf
        </form>
    </div>

    <!-- Ubah kata sandi -->
    <div class="ui-card p-5 sm:p-6">
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-blue-600">
                @include('partials.icon', ['name' => 'lock', 'size' => 'h-5 w-5'])
            </span>
            <div>
                <h2 class="ui-card-title">Ubah kata sandi</h2>
                <p class="ui-card-sub">Gunakan kata sandi yang panjang dan acak agar tetap aman.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="mt-5 max-w-xl space-y-5">
            @csrf
            @method('put')

            <div>
                <label for="current_password" class="ui-label">Kata sandi saat ini</label>
                <div class="relative mt-1.5">
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                           class="ui-input pr-10">
                    <button type="button" data-show-password
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition hover:text-gray-600"
                            aria-label="Tampilkan kata sandi">
                        @include('partials.icon', ['name' => 'eye', 'size' => 'h-5 w-5'])
                    </button>
                </div>
                @error('current_password', 'updatePassword')
                    <p class="ui-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="new_password" class="ui-label">Kata sandi baru</label>
                <div class="relative mt-1.5">
                    <input id="new_password" name="password" type="password" autocomplete="new-password"
                           class="ui-input pr-10">
                    <button type="button" data-show-password
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition hover:text-gray-600"
                            aria-label="Tampilkan kata sandi">
                        @include('partials.icon', ['name' => 'eye', 'size' => 'h-5 w-5'])
                    </button>
                </div>
                @error('password', 'updatePassword')
                    <p class="ui-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="ui-label">Ulangi kata sandi baru</label>
                <div class="relative mt-1.5">
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                           class="ui-input pr-10">
                    <button type="button" data-show-password
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition hover:text-gray-600"
                            aria-label="Tampilkan kata sandi">
                        @include('partials.icon', ['name' => 'eye', 'size' => 'h-5 w-5'])
                    </button>
                </div>
                @error('password_confirmation', 'updatePassword')
                    <p class="ui-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5">
                <button type="submit" class="ui-btn-primary">
                    @include('partials.icon', ['name' => 'shield', 'size' => 'h-4 w-4'])
                    Perbarui kata sandi
                </button>
                @if (session('status') === 'password-updated')
                    <span class="inline-flex items-center gap-1.5 text-[13px] font-medium text-emerald-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Tersimpan
                    </span>
                @endif
            </div>
        </form>
    </div>

    <!-- Hapus akun -->
    <div class="ui-card border-rose-200 bg-rose-50/30 p-5 sm:p-6">
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-rose-100 text-rose-600">
                @include('partials.icon', ['name' => 'trash', 'size' => 'h-5 w-5'])
            </span>
            <div>
                <h2 class="ui-card-title text-rose-900">Hapus akun</h2>
                <p class="ui-card-sub text-rose-700">Setelah dihapus, semua data dan sumber daya akan hilang permanen.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('profile.destroy') }}" class="mt-5 max-w-xl space-y-5"
              onsubmit="return confirm('Yakin ingin menghapus akun Anda? Tindakan ini permanen dan tidak bisa dibatalkan.')">
            @csrf
            @method('delete')

            <div>
                <label for="delete_password" class="ui-label">Konfirmasi kata sandi</label>
                <div class="relative mt-1.5">
                    <input id="delete_password" name="password" type="password" autocomplete="current-password"
                           placeholder="Masukkan kata sandi Anda"
                           class="ui-input pr-10">
                    <button type="button" data-show-password
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition hover:text-gray-600"
                            aria-label="Tampilkan kata sandi">
                        @include('partials.icon', ['name' => 'eye', 'size' => 'h-5 w-5'])
                    </button>
                </div>
                @error('password', 'userDeletion')
                    <p class="ui-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="ui-btn-danger">
                @include('partials.icon', ['name' => 'trash', 'size' => 'h-4 w-4'])
                Hapus akun permanen
            </button>
        </form>
    </div>
@endsection
