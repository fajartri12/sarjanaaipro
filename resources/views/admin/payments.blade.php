@extends('layouts.app')

@section('title', 'Admin · Pembayaran')

@php
    $paymentMeta = \App\Support\Labels::PAYMENT_STATUS;
    $rupiah = fn ($value) => \App\Support\Labels::rupiah($value);
    $tanggal = fn ($value) => \App\Support\Labels::tanggal($value);
    $smallInput = 'ui-input-sm';
@endphp

@section('header')
    <div class="space-y-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                Admin
            </a>
            <h1 class="ui-page-title mt-2">Pembayaran</h1>
            <p class="ui-page-sub">Konfirmasi pembayaran manual dan pantau status transaksi.</p>
        </div>
        @include('partials.admin-nav')
    </div>
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @include('partials.stat', [
            'label' => 'Pendapatan lunas',
            'value' => $rupiah($recon['paid_total']),
            'hint' => $recon['paid_count'].' transaksi',
            'tone' => 'positive',
            'icon' => 'card',
        ])
        @include('partials.stat', [
            'label' => 'Menunggu konfirmasi',
            'value' => $rupiah($recon['pending_total']),
            'hint' => $recon['pending_count'].' transaksi',
            'tone' => $recon['pending_count'] ? 'warning' : 'default',
            'icon' => 'clock',
        ])
        @include('partials.stat', [
            'label' => 'Menggantung > '.$recon['stale_days'].' hari',
            'value' => \App\Support\Labels::angka($recon['stale']),
            'hint' => 'Perlu ditindak atau dikedaluwarsakan',
            'tone' => $recon['stale'] ? 'negative' : 'default',
            'icon' => 'warning',
        ])
        @include('partials.stat', [
            'label' => 'Gagal / kedaluwarsa',
            'value' => \App\Support\Labels::angka($recon['expired_count']),
            'hint' => 'Tidak masuk hitungan pendapatan',
            'icon' => 'inbox',
        ])
    </div>

    @if ($recon['no_invoice'])
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/30">
            <span class="mt-0.5 shrink-0 text-amber-600">
                @include('partials.icon', ['name' => 'warning', 'size' => 'h-5 w-5'])
            </span>
            <div class="text-[13px]">
                <p class="font-semibold text-amber-900 dark:text-amber-200">
                    {{ \App\Support\Labels::angka($recon['no_invoice']) }} pembayaran lunas belum punya nomor invoice
                </p>
                <p class="mt-0.5 text-amber-800/80 dark:text-amber-300/80">
                    Pembayaran ini tercatat sebelum fitur invoice aktif. Nomor dibuat otomatis saat pembayaran baru dikonfirmasi.
                </p>
            </div>
        </div>
    @endif

    <div class="ui-table-wrap">
        {{-- Flowbite Filter Bar --}}
        <div class="border-b border-gray-100 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <form method="GET" action="{{ route('admin.payments') }}" class="flex flex-wrap items-center gap-3">
                <select name="status" class="rounded-xl border border-gray-200 bg-gray-50/70 p-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <option value="">Semua status pembayaran</option>
                    @foreach ($paymentMeta as $key => $meta)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="ui-btn-secondary ui-btn-sm">
                    @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                    Terapkan
                </button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.payments') }}" class="ui-link text-[13px]">Reset</a>
                @endif
            </form>
        </div>

        @if ($payments->count())
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="ui-th">Pengguna</th>
                            <th scope="col" class="ui-th">Referensi</th>
                            <th scope="col" class="ui-th">Invoice</th>
                            <th scope="col" class="ui-th">Paket</th>
                            <th scope="col" class="ui-th">Nominal</th>
                            <th scope="col" class="ui-th">Sumber</th>
                            <th scope="col" class="ui-th">Tanggal</th>
                            <th scope="col" class="ui-th text-center">Status</th>
                            <th scope="col" class="ui-th text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($payments as $payment)
                            @php
                                $meta = $paymentMeta[$payment->status] ?? ['label' => $payment->status, 'tone' => 'gray'];
                                $stale = $payment->status === 'pending'
                                    && $payment->created_at->lt(now()->subDays($recon['stale_days']));
                            @endphp
                            <tr class="ui-tr">
                                <th scope="row" class="ui-td font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2.5">
                                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-gray-100 text-xs font-bold uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            {{ mb_substr($payment->user?->name ?? '?', 0, 1) }}
                                        </span>
                                        <span class="truncate font-semibold">{{ $payment->user?->name ?? '—' }}</span>
                                    </div>
                                </th>
                                <td class="ui-td">
                                    <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-xs font-medium text-gray-700 dark:bg-gray-700/60 dark:text-gray-300">
                                        {{ $payment->reference }}
                                    </span>
                                </td>
                                <td class="ui-td">
                                    @if ($payment->invoice_number)
                                        <a href="{{ route('invoices.download', $payment->id) }}"
                                           class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1 font-mono text-xs font-medium text-blue-700 transition hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-300">
                                            @include('partials.icon', ['name' => 'receipt', 'size' => 'h-3 w-3'])
                                            {{ $payment->invoice_number }}
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="ui-td font-medium text-gray-800 dark:text-gray-200">
                                    {{ $payment->plan?->name ?? '—' }}
                                </td>
                                <td class="ui-td font-semibold tabular-nums text-gray-900 dark:text-white">
                                    {{ $rupiah($payment->amount) }}
                                </td>
                                <td class="ui-td">
                                    @if ($payment->channel)
                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700/60 dark:text-gray-300">
                                            @include('partials.icon', ['name' => 'card', 'size' => 'h-3 w-3'])
                                            {{ $payment->channel }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="ui-td whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    {{ $tanggal($payment->created_at) }}
                                    @if ($stale)
                                        <span class="ml-1.5 inline-flex rounded-md bg-rose-50 px-1.5 py-0.5 text-[11px] font-semibold text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">
                                            {{ $payment->created_at->diffInDays(now()) }} hari
                                        </span>
                                    @endif
                                </td>
                                <td class="ui-td text-center">
                                    @include('partials.badge', ['tone' => $meta['tone'], 'label' => $meta['label']])
                                </td>
                                <td class="ui-td text-right">
                                    @if ($payment->status === 'pending')
                                        <div class="inline-flex items-center gap-2">
                                            <form method="POST" action="{{ route('admin.payments.confirm', $payment->id) }}"
                                                  class="inline-flex items-center gap-2"
                                                  onsubmit="return confirm('Konfirmasi pembayaran {{ $payment->reference }}? Invoice akan dibuat otomatis.')">
                                                @csrf
                                                {{-- Pengguna belum memilih rekening: admin mencatat sumber dana saat konfirmasi. --}}
                                                @if ($payment->provider === 'manual' && ! $payment->channel && $channels)
                                                    <select name="channel" class="ui-input-sm w-36" aria-label="Sumber rekening {{ $payment->reference }}">
                                                        <option value="">Sumber rekening…</option>
                                                        @foreach ($channels as $name)
                                                            <option value="{{ $name }}">{{ $name }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                                <button type="submit" class="ui-btn-primary ui-btn-xs shrink-0">
                                                    @include('partials.icon', ['name' => 'check', 'size' => 'h-3.5 w-3.5'])
                                                    Konfirmasi
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.payments.expire', $payment->id) }}"
                                                  onsubmit="return confirm('Tandai pembayaran {{ $payment->reference }} sebagai kedaluwarsa?')">
                                                @csrf
                                                <button type="submit" class="ui-btn-secondary ui-btn-xs" title="Tandai kedaluwarsa" aria-label="Tandai kedaluwarsa">
                                                    @include('partials.icon', ['name' => 'x', 'size' => 'h-3.5 w-3.5'])
                                                </button>
                                            </form>
                                        </div>
                                    @elseif ($payment->invoice_number)
                                        <a href="{{ route('invoices.download', $payment->id) }}"
                                           class="ui-btn-secondary ui-btn-xs inline-flex items-center gap-1.5">
                                            @include('partials.icon', ['name' => 'download', 'size' => 'h-3.5 w-3.5'])
                                            Invoice
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                {{ $payments->links() }}
            </div>
        @else
            <div class="ui-empty border-0 bg-transparent py-16">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'card', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3.5 text-sm font-semibold text-gray-900 dark:text-white">Tidak ada data pembayaran</p>
                <p class="mt-1 text-[13px] text-gray-500">Coba ubah filter status pembayaran di atas.</p>
            </div>
        @endif
    </div>
@endsection
