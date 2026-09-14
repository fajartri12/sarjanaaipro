@extends('layouts.app')

@section('title', 'Langganan Saya')

@php
    $paymentMeta = \App\Support\Labels::PAYMENT_STATUS;
    $subMeta = \App\Support\Labels::SUBSCRIPTION_STATUS;
    $featureLabel = \App\Support\Labels::FEATURE_LABEL;
    $rupiah = fn ($value) => \App\Support\Labels::rupiah($value);
    $angka = fn ($value) => \App\Support\Labels::angka($value);
    $tanggal = fn ($value) => \App\Support\Labels::tanggal($value);
    $limitText = function ($plan, string $feature) use ($angka) {
        $value = $plan?->limits[$feature] ?? null;
        if ($value === null) {
            return '—';
        }

        return $value < 0 ? 'Tanpa batas' : $angka($value).'×';
    };
    $isFree = $currentPlan?->slug === \App\Models\Plan::FREE;
@endphp

@section('header')
    <div>
        <h1 class="ui-page-title">Langganan saya</h1>
        <p class="ui-page-sub">Status paket, riwayat pembayaran, dan pemakaian AI Anda.</p>
    </div>
@endsection

@section('content')
    <!-- Plan aktif - Hero card -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 p-6 text-white shadow-lg sm:p-8">
        <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/5"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-medium backdrop-blur-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Paket aktif
                </p>
                <h2 class="mt-4 text-3xl font-bold tracking-tight">{{ $currentPlan?->name ?? 'Free' }}</h2>
                <p class="mt-1 text-blue-100">
                    {{ $currentPlan?->price ? $rupiah($currentPlan->price).' / '.($currentPlan->interval === 'month' ? 'bulan' : ($currentPlan->interval === 'year' ? 'tahun' : $currentPlan->interval)) : 'Tanpa biaya — upgrade kapan saja' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('subscription.prices') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50">
                    @include('partials.icon', ['name' => 'card', 'size' => 'h-4 w-4'])
                    {{ $isFree ? 'Upgrade paket' : 'Ganti paket' }}
                </a>
                @if (!$isFree)
                    <form method="POST" action="{{ route('subscription.cancel') }}" class="inline"
                          onsubmit="return confirm('Batalkan langganan aktif? Anda akan kembali ke paket Free.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-5 py-2.5 text-sm font-medium text-white backdrop-blur-sm transition hover:bg-white/20">
                            Batalkan
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Limits -->
        <div class="relative mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach (['generate_titles' => 'Generate Judul', 'ai_chat' => 'AI Chat', 'ai_reviewer' => 'Reviewer', 'projects' => 'Project'] as $key => $label)
                <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                    <p class="text-xs font-medium text-blue-200">{{ $label }}</p>
                    <p class="mt-1 text-xl font-bold">{{ $limitText($currentPlan, $key) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    @if ($manualInstructions && $manualInstructions['channels'])
        <!-- Instruksi transfer manual -->
        <div class="ui-card p-5 sm:p-6">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                    @include('partials.icon', ['name' => 'receipt', 'size' => 'h-5 w-5'])
                </span>
                <div>
                    <h2 class="ui-card-title">Instruksi pembayaran</h2>
                    <p class="ui-card-sub">Transfer {{ $rupiah($manualInstructions['amount']) }}@if ($manualInstructions['plan']) untuk paket {{ $manualInstructions['plan'] }}@endif ke salah satu rekening di bawah, lalu kirim bukti ke admin.</p>
                </div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($manualInstructions['channels'] as $channel)
                    @php $selected = ($manualInstructions['channel'] ?? '') === $channel['name']; @endphp
                    <div class="rounded-xl border p-4 transition @if ($selected) border-blue-300 bg-blue-50/50 dark:border-blue-700 dark:bg-blue-950/20 @else border-gray-100 dark:border-gray-700 @endif">
                        <div class="flex items-center gap-2">
                            @if (($channel['type'] ?? 'bank') === 'ewallet')
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                    @include('partials.icon', ['name' => 'wallet', 'size' => 'h-4 w-4'])
                                </span>
                            @else
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                                    @include('partials.icon', ['name' => 'card', 'size' => 'h-4 w-4'])
                                </span>
                            @endif
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $channel['name'] }}</span>
                            @if ($selected)
                                <span class="ml-auto inline-flex items-center gap-1 rounded-md bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                    @include('partials.icon', ['name' => 'check', 'size' => 'h-3 w-3'])
                                    Dipilih
                                </span>
                            @endif
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                            <code class="select-all text-sm font-bold tracking-wider text-gray-900 dark:text-white">{{ $channel['number'] }}</code>
                            <button type="button" data-copy="{{ $channel['number'] }}"
                                    class="grid h-9 w-9 shrink-0 place-items-center rounded-md text-gray-400 transition hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300 sm:h-8 sm:w-8"
                                    aria-label="Salin nomor {{ $channel['name'] }}">
                                @include('partials.icon', ['name' => 'document', 'size' => 'h-4 w-4'])
                            </button>
                        </div>
                        @if (! empty($channel['holder']))
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">a.n. {{ $channel['holder'] }}</p>
                        @endif
                        @unless ($selected)
                            <form method="POST" action="{{ route('subscription.payments.channel', $manualInstructions['payment_id']) }}" class="mt-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="channel" value="{{ $channel['name'] }}">
                                <button type="submit" class="ui-btn-secondary ui-btn-xs w-full">
                                    Pilih rekening ini
                                </button>
                            </form>
                        @endunless
                    </div>
                @endforeach
            </div>

            @if ($manualInstructions['contact'])
                <div class="mt-4 flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                    @include('partials.icon', ['name' => 'chat', 'size' => 'h-4 w-4'])
                    <span>Setelah transfer, kirim bukti via <a href="{{ $manualInstructions['contact'] }}" class="font-semibold underline underline-offset-2" target="_blank" rel="noopener">WhatsApp</a> dengan menyertakan kode <code class="rounded bg-blue-100 px-1.5 py-0.5 font-mono text-xs dark:bg-blue-900/50">{{ $manualInstructions['reference'] }}</code>.</span>
                </div>
            @else
                <div class="mt-4 flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                    @include('partials.icon', ['name' => 'chat', 'size' => 'h-4 w-4'])
                    <span>Setelah transfer, kirim bukti ke admin dengan menyertakan kode <code class="rounded bg-blue-100 px-1.5 py-0.5 font-mono text-xs dark:bg-blue-900/50">{{ $manualInstructions['reference'] }}</code>.</span>
                </div>
            @endif
        </div>
    @endif

    <!-- Statistik penggunaan -->
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Pemakaian AI -->
        <div class="ui-card p-5 sm:p-6">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                    @include('partials.icon', ['name' => 'chart', 'size' => 'h-5 w-5'])
                </span>
                <div>
                    <h2 class="ui-card-title">Pemakaian AI</h2>
                    <p class="ui-card-sub">Total token dan jumlah pemanggilan per fitur.</p>
                </div>
            </div>

            @if ($usage->count())
                <div class="mt-5 space-y-4">
                    @foreach ($usage as $row)
                        @php
                            $feature = $featureLabel[$row->feature] ?? $row->feature;
                            $maxTokens = $usage->max('tokens');
                            $percent = $maxTokens > 0 ? round(($row->tokens / $maxTokens) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $feature }}</span>
                                <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ $angka($row->calls) }} kali · {{ $angka($row->tokens) }} token</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-blue-500 transition-all" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-6 flex flex-col items-center py-8 text-center">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                        @include('partials.icon', ['name' => 'chart', 'size' => 'h-6 w-6'])
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">Belum ada pemakaian AI</p>
                    <p class="mt-1 text-[13px] text-gray-500 dark:text-gray-400">Mulai gunakan fitur AI untuk melihat statistik di sini.</p>
                </div>
            @endif
        </div>

        <!-- Ringkasan aktivitas -->
        <div class="ui-card p-5 sm:p-6">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                    @include('partials.icon', ['name' => 'sparkles', 'size' => 'h-5 w-5'])
                </span>
                <div>
                    <h2 class="ui-card-title">Ringkasan</h2>
                    <p class="ui-card-sub">Aktivitas akun Anda.</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-4">
                <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                    <p class="text-[13px] text-gray-500 dark:text-gray-400">Total pemanggilan</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $angka($usage->sum('calls')) }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">kali</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                    <p class="text-[13px] text-gray-500 dark:text-gray-400">Total token</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $angka($usage->sum('tokens')) }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">token</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                    <p class="text-[13px] text-gray-500 dark:text-gray-400">Riwayat bayar</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $angka($payments->count()) }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">transaksi</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                    <p class="text-[13px] text-gray-500 dark:text-gray-400">Riwayat langganan</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $angka($subscriptions->total()) }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">langganan</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat pembayaran -->
    <div class="ui-card overflow-hidden p-0">
        <div class="flex items-center gap-3 border-b border-gray-100 p-5 sm:p-6 dark:border-gray-700">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                @include('partials.icon', ['name' => 'card', 'size' => 'h-5 w-5'])
            </span>
            <div>
                <h2 class="ui-card-title">Riwayat pembayaran</h2>
                <p class="ui-card-sub">{{ $angka($payments->count()) }} transaksi pembayaran tercatat.</p>
            </div>
        </div>

        @if ($payments->count())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3 font-medium">Paket</th>
                            <th scope="col" class="px-6 py-3 font-medium">Kode Referensi</th>
                            <th scope="col" class="px-6 py-3 font-medium">Waktu</th>
                            <th scope="col" class="px-6 py-3 font-medium">Nominal</th>
                            <th scope="col" class="px-6 py-3 text-right font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($payments as $payment)
                            @php $meta = $paymentMeta[$payment->status] ?? ['label' => $payment->status, 'tone' => 'gray']; @endphp
                            <tr>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $payment->plan?->name ?? 'Paket' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $payment->reference }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                    {{ $tanggal($payment->created_at) }}
                                </td>
                                <td class="px-6 py-4 font-semibold tabular-nums text-gray-900 dark:text-white">
                                    {{ $rupiah($payment->amount) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @include('partials.badge', ['tone' => $meta['tone'], 'label' => $meta['label']])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center py-12 text-center">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'card', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Belum ada pembayaran</p>
                <p class="mt-1 max-w-sm text-[13px] text-gray-500 dark:text-gray-400">
                    Pembayaran akan muncul di sini setelah Anda memilih paket berbayar.
                </p>
            </div>
        @endif
    </div>

    <!-- Riwayat langganan -->
    <div class="ui-card overflow-hidden p-0">
        <div class="flex items-center gap-3 border-b border-gray-100 p-5 sm:p-6 dark:border-gray-700">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                @include('partials.icon', ['name' => 'tag', 'size' => 'h-5 w-5'])
            </span>
            <div>
                <h2 class="ui-card-title">Riwayat langganan</h2>
                <p class="ui-card-sub">{{ $angka($subscriptions->total()) }} langganan akun Anda.</p>
            </div>
        </div>

        @if ($subscriptions->count())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3 font-medium">Paket</th>
                            <th scope="col" class="px-6 py-3 font-medium">Periode Aktif</th>
                            <th scope="col" class="px-6 py-3 text-right font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($subscriptions as $item)
                            @php $meta = $subMeta[$item->status] ?? ['label' => $item->status, 'tone' => 'gray']; @endphp
                            <tr>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $item->plan?->name ?? 'Paket' }}
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                    {{ $tanggal($item->starts_at) }} — {{ $item->ends_at ? $tanggal($item->ends_at) : 'tanpa batas' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @include('partials.badge', ['tone' => $meta['tone'], 'label' => $meta['label']])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                {{ $subscriptions->links() }}
            </div>
        @else
            <div class="flex flex-col items-center py-12 text-center">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'tag', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Belum ada langganan</p>
                <p class="mt-1 max-w-sm text-[13px] text-gray-500 dark:text-gray-400">
                    Pilih paket berbayar untuk membuka kuota AI lebih besar.
                </p>
                <a href="{{ route('subscription.prices') }}" class="ui-btn-primary ui-btn-sm mt-5">
                    Lihat paket
                </a>
            </div>
        @endif
    </div>
@endsection
