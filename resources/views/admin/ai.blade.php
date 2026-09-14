@extends('layouts.app')

@section('title', 'Admin · Biaya & Performa AI')

@php
    $featureLabel = \App\Support\Labels::FEATURE_LABEL;
    $rupiah = fn ($value) => \App\Support\Labels::rupiah($value);
    $angka = fn ($value) => \App\Support\Labels::angka($value);

    $maxDailyCost = max(array_column($daily, 'cost')) ?: 1;
    $maxUserCost = (int) ($byUser->max('cost') ?? 0) ?: 1;
    $maxFeatureCost = (int) ($byFeature->max('cost') ?? 0) ?: 1;
@endphp

@section('header')
    <div class="space-y-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                Admin
            </a>
            <h1 class="ui-page-title mt-2">Biaya & performa AI</h1>
            <p class="ui-page-sub">Ke mana anggaran AI pergi, siapa yang paling banyak memakai, dan apakah provider melambat.</p>
        </div>
        @include('partials.admin-nav')
    </div>
@endsection

@section('content')
    {{-- Pemilih rentang waktu --}}
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-[13px] font-medium text-gray-500 dark:text-gray-400">Rentang:</span>
        @foreach ([7 => '7 hari', 30 => '30 hari', 90 => '90 hari'] as $value => $label)
            <a href="{{ route('admin.ai', ['days' => $value]) }}"
               @if ($days === $value) aria-current="page" @endif
               class="rounded-lg px-3 py-1.5 text-[13px] font-medium transition
                      {{ $days === $value
                            ? 'bg-blue-600 text-white shadow-sm'
                            : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @include('partials.stat', ['label' => 'Biaya AI', 'value' => $rupiah($totals['cost']), 'hint' => $days.' hari terakhir', 'tone' => 'warning', 'icon' => 'chart'])
        @include('partials.stat', ['label' => 'Pemanggilan', 'value' => $angka($totals['calls']), 'hint' => $angka($totals['tokens']).' token', 'icon' => 'sparkles'])
        @include('partials.stat', ['label' => 'Waktu respons', 'value' => $angka($totals['avg_ms']).' ms', 'hint' => 'rata-rata panggilan sukses', 'icon' => 'clock'])
        @include('partials.stat', ['label' => 'Gagal', 'value' => $angka($totals['failed']), 'hint' => $totals['calls'] ? round($totals['failed'] / $totals['calls'] * 100, 1).'% dari total' : '—', 'tone' => $totals['failed'] ? 'negative' : 'default', 'icon' => 'warning'])
        @include('partials.stat', ['label' => 'Biaya per panggilan', 'value' => $rupiah($totals['calls'] ? (int) ($totals['cost'] / $totals['calls']) : 0), 'hint' => 'rata-rata', 'icon' => 'card'])
    </div>

    {{-- Grafik biaya harian --}}
    <div class="ui-card p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="ui-card-title">Biaya harian</h2>
                <p class="ui-card-sub">Biaya estimasi per hari dalam {{ $days }} hari terakhir.</p>
            </div>
            <p class="text-[13px] text-gray-500 dark:text-gray-400">
                Puncak: <span class="font-semibold text-gray-900 dark:text-white">{{ $rupiah(max(array_column($daily, 'cost'))) }}</span>
            </p>
        </div>

        <div class="mt-6 flex h-40 items-end gap-1 overflow-hidden">
            @foreach ($daily as $day)
                @php $height = max(2, round($day['cost'] / $maxDailyCost * 100)); @endphp
                <div class="group relative flex-1" title="{{ $day['label'] }} · {{ $rupiah($day['cost']) }} · {{ $day['calls'] }} panggilan">
                    <div class="w-full rounded-t bg-blue-500/80 transition group-hover:bg-blue-600"
                         style="height: {{ $height }}%; min-height: 2px;"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex justify-between text-[11px] text-gray-400">
            <span>{{ $daily[0]['label'] ?? '' }}</span>
            <span>{{ $daily[count($daily) - 1]['label'] ?? '' }}</span>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Biaya per fitur --}}
        <div class="ui-card overflow-hidden p-0">
            <div class="ui-card-head">
                <div>
                    <h2 class="ui-card-title">Biaya per fitur</h2>
                    <p class="ui-card-sub">Fitur mana yang paling menguras anggaran.</p>
                </div>
            </div>

            @if ($byFeature->count())
                <div class="space-y-4 p-5 sm:p-6">
                    @foreach ($byFeature as $row)
                        @php
                            $percent = round($row->cost / $maxFeatureCost * 100);
                            $failRate = $row->calls > 0 ? round($row->failed / $row->calls * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2 text-[13px]">
                                <span class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ $featureLabel[$row->feature] ?? $row->feature }}
                                </span>
                                <span class="tabular-nums text-gray-500 dark:text-gray-400">
                                    {{ $rupiah($row->cost) }}
                                </span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-amber-500" style="width: {{ $percent }}%"></div>
                            </div>
                            <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-0.5 text-[11px] text-gray-400">
                                <span>{{ $angka($row->calls) }} panggilan</span>
                                <span>{{ $angka($row->tokens) }} token</span>
                                <span>{{ $angka((int) $row->avg_ms) }} ms</span>
                                @if ($failRate > 0)
                                    <span class="font-medium text-rose-500">{{ $failRate }}% gagal</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="ui-empty border-0 bg-transparent">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Belum ada data</p>
                    <p class="mt-1 text-[13px] text-gray-500">Belum ada pemanggilan AI dalam rentang ini.</p>
                </div>
            @endif
        </div>

        {{-- Pengguna paling mahal --}}
        <div class="ui-card overflow-hidden p-0">
            <div class="ui-card-head">
                <div>
                    <h2 class="ui-card-title">Pengguna paling mahal</h2>
                    <p class="ui-card-sub">15 akun dengan biaya AI tertinggi.</p>
                </div>
            </div>

            @if ($byUser->count())
                <div class="overflow-x-auto">
                    <table class="ui-table">
                        <thead class="ui-thead">
                            <tr>
                                <th scope="col" class="ui-th">Pengguna</th>
                                <th scope="col" class="ui-th text-right">Panggilan</th>
                                <th scope="col" class="ui-th text-right">Token</th>
                                <th scope="col" class="ui-th text-right">Biaya</th>
                            </tr>
                        </thead>
                        <tbody class="ui-tbody">
                            @foreach ($byUser as $row)
                                <tr class="ui-tr">
                                    <th scope="row" class="ui-td font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2.5">
                                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-gray-100 text-xs font-semibold uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                {{ mb_substr($row->user?->name ?? '?', 0, 1) }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate">{{ $row->user?->name ?? '—' }}</p>
                                                <div class="mt-1 h-1 w-24 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                                    <div class="h-full rounded-full bg-blue-500" style="width: {{ round($row->cost / $maxUserCost * 100) }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <td class="ui-td text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $angka($row->calls) }}</td>
                                    <td class="ui-td text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $angka($row->tokens) }}</td>
                                    <td class="ui-td text-right font-semibold tabular-nums text-gray-900 dark:text-white">{{ $rupiah($row->cost) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="ui-empty border-0 bg-transparent">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Belum ada data</p>
                    <p class="mt-1 text-[13px] text-gray-500">Belum ada pemanggilan AI dalam rentang ini.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Biaya per model --}}
    @if ($byModel->count())
        <div class="ui-table-wrap">
            <div class="ui-card-head">
                <div>
                    <h2 class="ui-card-title">Biaya per model</h2>
                    <p class="ui-card-sub">Harga model menentukan margin. Model dengan biaya 0 memakai provider tanpa tarif.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="ui-th">Model</th>
                            <th scope="col" class="ui-th text-right">Panggilan</th>
                            <th scope="col" class="ui-th text-right">Token</th>
                            <th scope="col" class="ui-th text-right">Biaya</th>
                            <th scope="col" class="ui-th text-right">Biaya / panggilan</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($byModel as $row)
                            <tr class="ui-tr">
                                <th scope="row" class="ui-td">
                                    <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-xs font-medium text-gray-700 dark:bg-gray-700/60 dark:text-gray-300">
                                        {{ $row->model ?: 'default' }}
                                    </span>
                                </th>
                                <td class="ui-td text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $angka($row->calls) }}</td>
                                <td class="ui-td text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $angka($row->tokens) }}</td>
                                <td class="ui-td text-right font-semibold tabular-nums text-gray-900 dark:text-white">{{ $rupiah($row->cost) }}</td>
                                <td class="ui-td text-right tabular-nums text-gray-600 dark:text-gray-300">
                                    {{ $rupiah($row->calls ? (int) ($row->cost / $row->calls) : 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection