@extends('layouts.app')

@section('title', 'Kuota & Pemakaian')

@php
    $rupiah = fn ($value) => \App\Support\Labels::rupiah($value);
    $angka = fn ($value) => \App\Support\Labels::angka($value);
    $maxHistory = max(array_column($history, 'calls')) ?: 1;
    $toneOf = function (array $row): array {
        if ($row['exhausted']) {
            return ['bar' => 'bg-rose-500', 'value' => 'text-rose-600 dark:text-rose-400'];
        }
        if ($row['percent'] >= 75) {
            return ['bar' => 'bg-amber-500', 'value' => 'text-amber-600 dark:text-amber-400'];
        }

        return ['bar' => 'bg-blue-600', 'value' => 'text-gray-900 dark:text-white'];
    };
@endphp

@section('header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="ui-page-title">Kuota & pemakaian</h1>
            <p class="ui-page-sub">Sisa kuota bulan ini, rincian per fitur, dan biaya yang Anda hasilkan.</p>
        </div>
        <a href="{{ route('subscription.prices') }}" class="ui-btn-primary ui-btn-sm">
            @include('partials.icon', ['name' => 'card', 'size' => 'h-4 w-4'])
            Lihat paket
        </a>
    </div>
@endsection

@section('content')
    {{-- Ringkasan paket + siklus --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="ui-card p-5 sm:p-6 lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                        @include('partials.icon', ['name' => 'shield', 'size' => 'h-5 w-5'])
                    </span>
                    <div>
                        <h2 class="ui-card-title">Paket {{ $plan?->name ?? 'Free' }}</h2>
                        <p class="ui-card-sub">
                            @if ($subscription?->ends_at)
                                Aktif sampai {{ \App\Support\Labels::tanggal($subscription->ends_at) }}
                                ({{ max(0, (int) now()->diffInDays($subscription->ends_at, false)) }} hari lagi)
                            @else
                                Tanpa tanggal berakhir — kuota direset tiap awal bulan.
                            @endif
                        </p>
                    </div>
                </div>
                @if ($upgrade)
                    <a href="{{ route('subscription.prices') }}" class="ui-btn-secondary ui-btn-sm">
                        Naik ke {{ $upgrade['name'] }}
                        @include('partials.icon', ['name' => 'arrow-right', 'size' => 'h-4 w-4'])
                    </a>
                @endif
            </div>

            <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                    <p class="text-[13px] text-gray-500 dark:text-gray-400">Token bulan ini</p>
                    <p class="mt-1 text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $angka($cost['month_tokens']) }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                    <p class="text-[13px] text-gray-500 dark:text-gray-400">Biaya AI Anda</p>
                    <p class="mt-1 text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $rupiah($cost['month_cost']) }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                    <p class="text-[13px] text-gray-500 dark:text-gray-400">Nilai langganan</p>
                    <p class="mt-1 text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $rupiah($cost['revenue']) }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                    <p class="text-[13px] text-gray-500 dark:text-gray-400">Pemakaian kuota</p>
                    <p class="mt-1 text-xl font-bold tabular-nums {{ $cost['ratio'] > 100 ? 'text-rose-600' : 'text-gray-900 dark:text-white' }}">
                        {{ $angka($cost['ratio']) }}%
                    </p>
                </div>
            </div>
        </div>

        {{-- Tren 6 bulan --}}
        <div class="ui-card p-5 sm:p-6">
            <h2 class="ui-card-title">Tren 6 bulan</h2>
            <p class="ui-card-sub">Jumlah pemanggilan AI per bulan.</p>
            <div class="mt-5 flex h-28 items-end gap-2">
                @foreach ($history as $month)
                    @php $height = max(3, round($month['calls'] / $maxHistory * 100)); @endphp
                    <div class="group flex-1" title="{{ $month['label'] }} · {{ $angka($month['calls']) }} panggilan · {{ $angka($month['tokens']) }} token">
                        <div class="w-full rounded-t {{ $month['current'] ? 'bg-blue-600' : 'bg-blue-300 dark:bg-blue-900' }}"
                             style="height: {{ $height }}%; min-height: 3px;"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-[11px] text-gray-400">
                <span>{{ $history[0]['label'] ?? '' }}</span>
                <span>Bulan ini</span>
            </div>
        </div>
    </div>

    {{-- Detail kuota per fitur --}}
    <div class="ui-card overflow-hidden p-0">
        <div class="ui-card-head">
            <div>
                <h2 class="ui-card-title">Rincian kuota</h2>
                <p class="ui-card-sub">Dihitung dari pemanggilan yang berhasil maupun yang masih diproses.</p>
            </div>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($rows as $key => $row)
                @php $tone = $toneOf($row); @endphp
                <div class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                @include('partials.icon', ['name' => $row['icon'], 'size' => 'h-4 w-4'])
                            </span>
                            <div>
                                <p class="text-[15px] font-semibold text-gray-900 dark:text-white">{{ $row['label'] }}</p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    @if ($row['limit'] === null)
                                        Tanpa batas di paket ini
                                    @else
                                        Sisa {{ $angka($row['remaining']) }} dari {{ $angka($row['limit']) }} kuota bulan ini
                                    @endif
                                    @if ($row['tokens'])
                                        <span class="text-gray-300 dark:text-gray-600">·</span>
                                        {{ $angka($row['tokens']) }} token
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="text-right">
                            <p class="text-lg font-bold tabular-nums {{ $tone['value'] }}">
                                {{ $angka($row['used']) }}<span class="text-sm font-medium text-gray-400"> / {{ $row['limit'] === null ? '∞' : $angka($row['limit']) }}</span>
                            </p>
                            @if ($row['exhausted'])
                                <p class="text-[11px] font-semibold text-rose-600 dark:text-rose-400">Kuota habis</p>
                            @endif
                        </div>
                    </div>

                    <div class="ui-progress mt-3">
                        <div class="ui-progress-fill {{ $tone['bar'] }}" style="width: {{ $row['limit'] === null ? 100 : $row['percent'] }}%"></div>
                    </div>

                    {{-- Rincian sub-fitur --}}
                    @if ($row['breakdown'])
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($row['breakdown'] as $sub)
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 px-2.5 py-1.5 text-xs dark:bg-gray-800/60">
                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $sub['label'] }}</span>
                                    <span class="tabular-nums text-gray-400">{{ $angka($sub['calls']) }}×</span>
                                </span>
                            @endforeach
                            @if ($row['burst'])
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs dark:bg-blue-950/40">
                                    <span class="font-medium text-blue-700 dark:text-blue-300">Tersibuk {{ $row['burst']['day'] }}</span>
                                    <span class="tabular-nums text-blue-500 dark:text-blue-400">{{ $angka($row['burst']['calls']) }}×</span>
                                </span>
                            @endif
                        </div>
                    @endif

                    @if ($row['exhausted'] && $upgrade)
                        <p class="mt-3 text-[13px] text-gray-500 dark:text-gray-400">
                            Kuota habis. <a href="{{ route('subscription.prices') }}" class="ui-link text-[13px]">Naik ke {{ $upgrade['name'] }}</a> untuk lanjut tanpa menunggu bulan baru.
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection