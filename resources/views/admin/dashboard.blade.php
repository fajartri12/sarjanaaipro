@extends('layouts.app')

@section('title', 'Admin')

@php
    $paymentMeta = \App\Support\Labels::PAYMENT_STATUS;
    $featureLabel = \App\Support\Labels::FEATURE_LABEL;
    $rupiah = fn ($value) => \App\Support\Labels::rupiah($value);
    $angka = fn ($value) => \App\Support\Labels::angka($value);
    $tanggal = fn ($value) => \App\Support\Labels::tanggal($value);
@endphp

@section('header')
    <div class="space-y-4">
        <div>
            <h1 class="ui-page-title">Dashboard admin</h1>
            <p class="ui-page-sub">Ringkasan bisnis: pengguna, pendapatan, dan biaya AI.</p>
        </div>
        @include('partials.admin-nav')
    </div>
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @include('partials.stat', ['label' => 'Pendapatan', 'value' => $rupiah($stats['revenue']), 'tone' => 'positive', 'icon' => 'card', 'hint' => 'Total pembayaran lunas'])
        @include('partials.stat', ['label' => 'Biaya AI', 'value' => $rupiah($stats['aiCost']), 'tone' => 'warning', 'icon' => 'sparkles', 'hint' => $angka($stats['aiCalls']).' pemanggilan'])
        @include('partials.stat', ['label' => 'Margin', 'value' => $rupiah($stats['margin']), 'tone' => $stats['margin'] >= 0 ? 'positive' : 'negative', 'icon' => 'chart', 'hint' => 'Pendapatan − biaya AI'])
        @include('partials.stat', ['label' => 'Pembayaran pending', 'value' => $angka($stats['pendingPayments']), 'tone' => $stats['pendingPayments'] > 0 ? 'warning' : 'default', 'icon' => 'clock', 'hint' => 'Perlu dikonfirmasi'])
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @include('partials.stat', ['label' => 'Total pengguna', 'value' => $angka($stats['users']), 'icon' => 'user'])
        @include('partials.stat', ['label' => 'Mahasiswa', 'value' => $angka($stats['students']), 'icon' => 'cap'])
        @include('partials.stat', ['label' => 'Project', 'value' => $angka($stats['projects']), 'icon' => 'folder'])
        @include('partials.stat', ['label' => 'Langganan aktif', 'value' => $angka($stats['activeSubscriptions']), 'tone' => 'positive', 'icon' => 'check'])
    </div>

    {{-- Grafik: tren pendapatan & pertumbuhan pengguna --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="ui-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="ui-card-title">Pendapatan 30 hari</h2>
                    <p class="ui-card-sub">Total pembayaran lunas per hari.</p>
                </div>
                <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                    {{ $rupiah(array_sum(array_column($revenueTrend, 'value'))) }}
                </span>
            </div>
            <div class="mt-5">
                @include('partials.chart-line', [
                    'data' => $revenueTrend,
                    'format' => $rupiah,
                ])
            </div>
        </div>

        <div class="ui-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="ui-card-title">Pertumbuhan pengguna</h2>
                    <p class="ui-card-sub">Total akun kumulatif 30 hari.</p>
                </div>
                <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                    {{ $angka($stats['users']) }} akun
                </span>
            </div>
            <div class="mt-5">
                @include('partials.chart-line', [
                    'data' => $userGrowth,
                    'stroke' => '#8b5cf6',
                    'fill' => 'rgba(139, 92, 246, 0.08)',
                ])
            </div>
        </div>
    </div>

    {{-- Distribusi langganan per paket --}}
    <div class="ui-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="ui-card-title">Langganan per paket</h2>
                <p class="ui-card-sub">Paket yang sedang dipakai pengguna aktif.</p>
            </div>
            <a href="{{ route('admin.plans') }}" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400">Kelola paket</a>
        </div>
        <div class="mt-5">
            @if (count($planDistribution))
                @include('partials.chart-bar', ['data' => $planDistribution])
            @else
                <p class="py-6 text-center text-[13px] text-gray-500">Belum ada langganan aktif.</p>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="ui-table-wrap">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <h2 class="ui-card-title">Biaya per fitur</h2>
                    <p class="ui-card-sub">Akumulasi token dan biaya tiap fitur AI.</p>
                </div>
                <a href="{{ route('admin.usage') }}" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400">Lihat detail</a>
            </div>
            @if ($usageByFeature->count())
                <div class="overflow-x-auto">
                    <table class="ui-table">
                        <thead class="ui-thead">
                            <tr>
                                <th scope="col" class="ui-th">Fitur AI</th>
                                <th scope="col" class="ui-th">Panggilan</th>
                                <th scope="col" class="ui-th text-right">Biaya</th>
                            </tr>
                        </thead>
                        <tbody class="ui-tbody">
                            @foreach ($usageByFeature as $row)
                                <tr class="ui-tr">
                                    <th scope="row" class="ui-td font-medium text-gray-900 dark:text-white">
                                        {{ $featureLabel[$row->feature] ?? $row->feature }}
                                    </th>
                                    <td class="ui-td font-mono text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                        {{ $angka($row->calls) }}× · {{ $angka($row->tokens) }} tok
                                    </td>
                                    <td class="ui-td text-right font-mono text-xs font-semibold tabular-nums text-gray-900 dark:text-white">
                                        {{ $rupiah($row->cost) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="p-8 text-center text-[13px] text-gray-500">Belum ada pemakaian AI.</p>
            @endif
        </div>

        <div class="ui-table-wrap">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <h2 class="ui-card-title">Pembayaran terbaru</h2>
                    <p class="ui-card-sub">Transaksi terakhir beserta statusnya.</p>
                </div>
                <a href="{{ route('admin.payments') }}" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400">Lihat semua</a>
            </div>
            @if ($recentPayments->count())
                <div class="overflow-x-auto">
                    <table class="ui-table">
                        <thead class="ui-thead">
                            <tr>
                                <th scope="col" class="ui-th">Pengguna</th>
                                <th scope="col" class="ui-th">Paket</th>
                                <th scope="col" class="ui-th text-right">Nominal</th>
                                <th scope="col" class="ui-th text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="ui-tbody">
                            @foreach ($recentPayments as $payment)
                                @php $meta = $paymentMeta[$payment->status] ?? ['label' => $payment->status, 'tone' => 'gray']; @endphp
                                <tr class="ui-tr">
                                    <th scope="row" class="ui-td font-medium text-gray-900 dark:text-white">
                                        {{ $payment->user?->name ?? '—' }}
                                        <span class="mt-0.5 block font-mono text-[11px] font-normal text-gray-400 dark:text-gray-500">{{ $tanggal($payment->created_at) }}</span>
                                    </th>
                                    <td class="ui-td text-xs text-gray-600 dark:text-gray-300">
                                        {{ $payment->plan?->name ?? '—' }}
                                    </td>
                                    <td class="ui-td text-right font-mono text-xs tabular-nums text-gray-900 dark:text-white">
                                        {{ $rupiah($payment->amount) }}
                                    </td>
                                    <td class="ui-td text-right">
                                        @include('partials.badge', ['tone' => $meta['tone'], 'label' => $meta['label']])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="p-8 text-center text-[13px] text-gray-500">Belum ada pembayaran.</p>
            @endif
        </div>
    </div>

    <div class="ui-table-wrap">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div>
                <h2 class="ui-card-title">Pengguna terbaru</h2>
                <p class="ui-card-sub">Akun yang baru mendaftar.</p>
            </div>
            <a href="{{ route('admin.users') }}" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400">Lihat semua</a>
        </div>
        @if ($recentUsers->count())
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="ui-th">Nama & Email</th>
                            <th scope="col" class="ui-th">Peran</th>
                            <th scope="col" class="ui-th text-right">Terdaftar</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($recentUsers as $user)
                            <tr class="ui-tr">
                                <th scope="row" class="ui-td font-medium text-gray-900 dark:text-white">
                                    {{ $user->name }}
                                    <span class="mt-0.5 block text-[11px] font-normal text-gray-400 dark:text-gray-500">{{ $user->email }}</span>
                                </th>
                                <td class="ui-td">
                                    @include('partials.badge', ['tone' => $user->role === 'admin' ? 'violet' : 'gray', 'label' => $user->role])
                                </td>
                                <td class="ui-td text-right font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ $tanggal($user->created_at) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="p-8 text-center text-[13px] text-gray-500">Belum ada pengguna.</p>
        @endif
    </div>
@endsection
