@extends('layouts.app')

@section('title', 'Harga & Paket')

@php
    $rupiah = fn ($value) => \App\Support\Labels::rupiah($value);
@endphp

@section('header')
    <div>
        <h1 class="ui-page-title">Paket langganan</h1>
        <p class="ui-page-sub">Pilih sesuai kebutuhan. Semua paket berisi alat inti penulisan ilmiah.</p>
    </div>
@endsection

@section('content')
    <div class="ui-card flex flex-wrap items-center gap-3 px-5 py-4">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-600">
            @include('partials.icon', ['name' => 'card', 'size' => 'h-[18px] w-[18px]'])
        </span>
        <p class="text-[13px] text-gray-600">
            Paket aktif Anda:
            <span class="font-semibold text-gray-900">{{ $currentPlan?->name ?? 'Free' }}</span>
            @if ($subscription?->ends_at)
                <span class="text-gray-500">· berakhir {{ \App\Support\Labels::tanggal($subscription->ends_at) }}</span>
            @endif
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ($plans as $plan)
            @php
                $isCurrent = $currentPlan?->id === $plan->id;
                $isFree = $plan->slug === \App\Models\Plan::FREE;
            @endphp
            <div class="ui-card relative flex flex-col p-6 {{ $plan->is_popular ? 'border-blue-600 shadow-card-lg' : '' }}">
                @if ($plan->is_popular)
                    <span class="ui-chip absolute -top-3 left-6 bg-blue-600 text-white">
                        Paling dipilih
                    </span>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold tracking-tight text-gray-900">{{ $plan->name }}</h2>
                    @if ($isCurrent)
                        @include('partials.badge', ['tone' => 'green', 'label' => 'Aktif'])
                    @endif
                </div>
                <p class="mt-1.5 ui-card-sub">{{ $plan->description }}</p>

                <p class="mt-6 flex items-baseline gap-1.5">
                    <span class="text-3xl font-semibold leading-none tabular-nums tracking-tight text-gray-900">
                        {{ (int) $plan->price === 0 ? 'Gratis' : $rupiah($plan->price) }}
                    </span>
                    @if ((int) $plan->price > 0)
                        <span class="text-[13px] text-gray-500">/{{ $plan->interval === 'month' ? 'bulan' : ($plan->interval === 'year' ? 'tahun' : $plan->interval) }}</span>
                    @endif
                </p>

                <ul class="mt-6 flex-1 space-y-2.5 border-t border-gray-100 pt-5">
                    @foreach ($plan->features ?? [] as $feature)
                        <li class="flex gap-2.5 text-[13px] leading-relaxed text-gray-700">
                            <span class="mt-0.5 shrink-0 text-emerald-500">
                                @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4', 'stroke' => 2.2])
                            </span>
                            <span>{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($isCurrent)
                    <p class="mt-6 rounded-lg bg-gray-50 px-4 py-2.5 text-center text-[13px] font-medium text-gray-500">
                        Paket saat ini
                    </p>
                @elseif ($isFree)
                    <p class="mt-6 rounded-lg bg-gray-50 px-4 py-2.5 text-center text-[13px] font-medium text-gray-500">
                        Paket bawaan
                    </p>
                @else
                    <form method="POST" action="{{ route('subscription.checkout', $plan->id) }}" class="mt-6">
                        @csrf
                        <button type="submit"
                                class="ui-btn-block {{ $plan->is_popular ? 'ui-btn-primary' : 'ui-btn-secondary' }}">
                            Pilih paket ini
                        </button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
@endsection
