@extends('layouts.guest')

@section('title', 'Verifikasi Email')

@section('content')
    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gray-100 text-gray-500">
        @include('partials.icon', ['name' => 'inbox'])
    </span>

    <h1 class="ui-page-title mt-4">Verifikasi email Anda</h1>
    <p class="ui-page-sub">
        Terima kasih sudah mendaftar. Sebelum mulai, cek inbox email Anda dan klik tautan verifikasi yang kami kirim.
        Belum menerima emailnya? Kami bisa mengirim ulang.
    </p>

    @if (session('status') === 'verification-link-sent')
        <p class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-3 text-[13px] text-emerald-800">
            Tautan verifikasi baru sudah dikirim ke email Anda.
        </p>
    @endif

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-5">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="ui-btn-primary">
                @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
                Kirim ulang email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="ui-link text-[13px]">Keluar</button>
        </form>
    </div>
@endsection
