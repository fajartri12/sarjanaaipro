@extends('layouts.app')

@section('title', 'Admin · Rekening')

@php
    $smallInput = 'ui-input mt-1';
@endphp

@section('header')
    <div class="space-y-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                Admin
            </a>
            <h1 class="ui-page-title mt-2">Rekening tujuan</h1>
            <p class="ui-page-sub">Kelola daftar rekening bank dan e-wallet untuk pembayaran manual. Kanal dengan nomor kosong disembunyikan dari pengguna.</p>
        </div>
        @include('partials.admin-nav')
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf
        @method('PATCH')

        {{-- Daftar kanal --}}
        <div class="ui-card p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="ui-card-title">Kanal transfer</h2>
                    <p class="ui-card-sub">Tambah, ubah, atau hapus rekening yang ditampilkan ke pengguna.</p>
                </div>
                <button type="button" id="add-channel"
                        class="ui-btn-secondary ui-btn-sm shrink-0">
                    @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
                    Tambah
                </button>
            </div>

            <div id="channels-wrap" class="mt-5 space-y-4">
                @forelse ($channels as $i => $channel)
                    <div class="channel-row flex flex-wrap items-start gap-3 rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                        <div class="min-w-0 flex-1 space-y-3 sm:flex sm:flex-wrap sm:items-center sm:gap-3 sm:space-y-0">
                            <input name="channels[{{ $i }}][name]" type="text" required
                                   value="{{ old("channels.$i.name", $channel['name']) }}"
                                   placeholder="Nama bank/ewallet"
                                   class="w-full sm:w-44 {{ $smallInput }}">

                            <select name="channels[{{ $i }}][type]"
                                    class="w-full sm:w-32 rounded-xl border border-gray-200 bg-gray-50/70 p-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                                <option value="bank" @selected(($channel['type'] ?? 'bank') === 'bank')>Bank</option>
                                <option value="ewallet" @selected(($channel['type'] ?? '') === 'ewallet')>E-Wallet</option>
                            </select>

                            <input name="channels[{{ $i }}][number]" type="text"
                                   value="{{ old("channels.$i.number", $channel['number']) }}"
                                   placeholder="Nomor rekening"
                                   class="w-full sm:w-48 {{ $smallInput }}">

                            <input name="channels[{{ $i }}][holder]" type="text"
                                   value="{{ old("channels.$i.holder", $channel['holder']) }}"
                                   placeholder="Atas nama"
                                   class="w-full sm:w-44 {{ $smallInput }}">
                        </div>
                        <button type="button"
                                class="remove-channel mt-1 grid h-8 w-8 shrink-0 place-items-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600 sm:mt-0"
                                aria-label="Hapus kanal pembayaran">
                            @include('partials.icon', ['name' => 'trash', 'size' => 'h-4 w-4'])
                        </button>
                    </div>
                @empty
                    <p id="no-channels-msg" class="py-6 text-center text-sm text-gray-400 dark:text-gray-500">
                        Belum ada kanal. Klik "Tambah" untuk menambahkan rekening.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- Kontak WhatsApp --}}
        <div class="ui-card p-5 sm:p-6">
            <h2 class="ui-card-title">Kontak admin</h2>
            <p class="ui-card-sub">Link WhatsApp untuk pengguna mengirim bukti transfer. Kosongkan jika tidak dipakai.</p>
            <div class="mt-3">
                <input name="contact" type="text" value="{{ old('contact', $contact) }}"
                       placeholder="https://wa.me/628123456789"
                       class="w-full sm:w-96 {{ $smallInput }}">
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="ui-btn-primary">
                @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                Simpan
            </button>
            <a href="{{ route('admin.payments') }}" class="ui-link text-[13px]">Lihat daftar pembayaran</a>
        </div>
    </form>

    {{-- Baris kanal kosong, dipakai JS sebagai cetakan. --}}
    <template id="channel-template">
        <div class="channel-row flex flex-wrap items-start gap-3 rounded-xl border border-gray-100 p-4 dark:border-gray-700">
            <div class="min-w-0 flex-1 space-y-3 sm:flex sm:flex-wrap sm:items-center sm:gap-3 sm:space-y-0">
                <input name="channels[0][name]" type="text" required placeholder="Nama bank/ewallet" class="w-full sm:w-44 {{ $smallInput }}">
                <select name="channels[0][type]" class="w-full sm:w-32 rounded-xl border border-gray-200 bg-gray-50/70 p-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <option value="bank">Bank</option>
                    <option value="ewallet">E-Wallet</option>
                </select>
                <input name="channels[0][number]" type="text" placeholder="Nomor rekening" class="w-full sm:w-48 {{ $smallInput }}">
                <input name="channels[0][holder]" type="text" placeholder="Atas nama" class="w-full sm:w-44 {{ $smallInput }}">
            </div>
            <button type="button" class="remove-channel mt-1 grid h-8 w-8 shrink-0 place-items-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600 sm:mt-0" aria-label="Hapus kanal pembayaran">
                @include('partials.icon', ['name' => 'trash', 'size' => 'h-4 w-4'])
            </button>
        </div>
    </template>

    <script>
    (function () {
        const wrap = document.getElementById('channels-wrap');
        const template = document.getElementById('channel-template');

        function emptyState() {
            const p = document.createElement('p');
            p.id = 'no-channels-msg';
            p.className = 'py-6 text-center text-sm text-gray-400 dark:text-gray-500';
            p.textContent = 'Belum ada kanal. Klik "Tambah" untuk menambahkan rekening.';
            return p;
        }

        // Nomor urut field harus rapat setelah penghapusan, kalau tidak
        // Laravel akan menganggap ada baris bolong.
        function reindex() {
            wrap.querySelectorAll('.channel-row').forEach(function (row, i) {
                row.querySelectorAll('input, select').forEach(function (el) {
                    el.name = el.name.replace(/\[\d+\]/, '[' + i + ']');
                });
            });
        }

        document.getElementById('add-channel').addEventListener('click', function () {
            const empty = document.getElementById('no-channels-msg');
            if (empty) empty.remove();
            wrap.appendChild(template.content.cloneNode(true));
            reindex();
        });

        wrap.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-channel');
            if (!btn) return;
            btn.closest('.channel-row').remove();
            reindex();
            if (!wrap.querySelector('.channel-row')) wrap.appendChild(emptyState());
        });
    })();
    </script>
@endsection