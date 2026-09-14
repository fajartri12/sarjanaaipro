@extends('layouts.app')

@section('title', 'Admin · Pemakaian AI')

@php
    $featureLabel = \App\Support\Labels::FEATURE_LABEL;
    $rupiah = fn ($value) => \App\Support\Labels::rupiah($value);
    $angka = fn ($value) => \App\Support\Labels::angka($value);
    $tanggal = fn ($value) => \App\Support\Labels::tanggal($value);
@endphp

@section('header')
    <div class="space-y-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                Admin
            </a>
            <h1 class="ui-page-title mt-2">Pemakaian AI</h1>
            <p class="ui-page-sub">Catatan token, biaya, dan status tiap pemanggilan AI.</p>
        </div>
        @include('partials.admin-nav')
    </div>
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-3">
        @include('partials.stat', ['label' => 'Total pemanggilan', 'value' => $angka($totals['calls'])])
        @include('partials.stat', ['label' => 'Total token', 'value' => $angka($totals['tokens'])])
        @include('partials.stat', ['label' => 'Total biaya', 'value' => $rupiah($totals['cost']), 'tone' => 'warning'])
    </div>

    <div class="ui-table-wrap">
        @if ($usage->count())
            <div class="ui-card-head">
                <div>
                    <h2 class="ui-card-title">Riwayat pemanggilan AI</h2>
                    <p class="ui-card-sub">{{ \App\Support\Labels::angka($usage->total()) }} total pemanggilan tercatat di sistem.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="ui-th">Waktu</th>
                            <th scope="col" class="ui-th">Pengguna</th>
                            <th scope="col" class="ui-th">Fitur</th>
                            <th scope="col" class="ui-th text-right">Total Token</th>
                            <th scope="col" class="ui-th text-right">Estimasi Biaya</th>
                            <th scope="col" class="ui-th text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($usage as $row)
                            <tr class="ui-tr">
                                <td class="ui-td whitespace-nowrap font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ $tanggal($row->created_at) }}
                                </td>
                                <th scope="row" class="ui-td font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2.5">
                                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-gray-100 text-xs font-semibold uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            {{ mb_substr($row->user?->name ?? '?', 0, 1) }}
                                        </span>
                                        <span class="truncate">{{ $row->user?->name ?? '—' }}</span>
                                    </div>
                                </th>
                                <td class="ui-td">
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                        {{ $featureLabel[$row->feature] ?? $row->feature }}
                                    </span>
                                </td>
                                <td class="ui-td text-right font-mono font-medium tabular-nums text-gray-900 dark:text-gray-200">
                                    {{ $angka($row->total_tokens) }}
                                </td>
                                <td class="ui-td text-right font-medium tabular-nums text-gray-900 dark:text-gray-200">
                                    {{ $rupiah($row->estimated_cost) }}
                                </td>
                                <td class="ui-td text-center">
                                    @include('partials.badge', [
                                        'tone' => $row->status === 'success' ? 'green' : 'red',
                                        'label' => $row->status,
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                {{ $usage->links() }}
            </div>
        @else
            <div class="ui-empty border-0 bg-transparent">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'chart', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3.5 text-sm font-semibold text-gray-900 dark:text-white">Belum ada catatan</p>
                <p class="mt-1 text-[13px] text-gray-500">Pemanggilan AI akan tercatat di sini secara otomatis.</p>
            </div>
        @endif
    </div>
@endsection
