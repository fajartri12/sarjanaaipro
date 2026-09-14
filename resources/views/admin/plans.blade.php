@extends('layouts.app')

@section('title', 'Admin · Paket')

@php
    $rupiah = fn ($value) => \App\Support\Labels::rupiah($value);
    $smallInput = 'ui-input mt-1';
@endphp

@section('header')
    <div class="space-y-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                Admin
            </a>
            <h1 class="ui-page-title mt-2">Paket langganan</h1>
            <p class="ui-page-sub">Ubah harga, kuota fitur, dan daftar keunggulan. Nilai limit -1 berarti tanpa batas.</p>
        </div>
        @include('partials.admin-nav')
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        @foreach ($plans as $plan)
            <div class="ui-card">
                <div class="ui-card-head">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="ui-card-title">{{ $plan->name }}</h2>
                            @include('partials.badge', ['tone' => $plan->is_active ? 'green' : 'gray', 'label' => $plan->is_active ? 'Aktif' : 'Nonaktif'])
                            @if ($plan->is_popular)
                                @include('partials.badge', ['tone' => 'violet', 'label' => 'Populer'])
                            @endif
                        </div>
                        <p class="ui-card-sub">
                            <span class="font-semibold tabular-nums text-gray-900">{{ (int) $plan->price === 0 ? 'Gratis' : $rupiah($plan->price) }}</span>
                            / {{ $plan->interval === 'month' ? 'bulan' : ($plan->interval === 'year' ? 'tahun' : $plan->interval) }} · slug: <span class="font-mono">{{ $plan->slug }}</span>
                        </p>
                    </div>
                </div>

                <div class="space-y-5 p-5">
                    @if (! empty($plan->features))
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($plan->features as $feature)
                                @include('partials.badge', ['tone' => 'blue', 'label' => $feature])
                            @endforeach
                        </div>
                    @endif

                    <details class="group">
                        <summary class="ui-btn-secondary ui-btn-sm list-none">
                            @include('partials.icon', ['name' => 'plus', 'size' => 'h-4 w-4'])
                            Ubah paket
                        </summary>

                        <form method="POST" action="{{ route('admin.plans.update', $plan->id) }}"
                              class="mt-5 grid gap-5 sm:grid-cols-2">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="name-{{ $plan->id }}" class="ui-label">Nama</label>
                                <input id="name-{{ $plan->id }}" name="name" type="text" required
                                       value="{{ old('name', $plan->name) }}" class="{{ $smallInput }}">
                                @error('name') <p class="ui-error">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="price-{{ $plan->id }}" class="ui-label">Harga (Rp)</label>
                                <input id="price-{{ $plan->id }}" name="price" type="number" min="0" required
                                       value="{{ old('price', $plan->price) }}" class="{{ $smallInput }}">
                                @error('price') <p class="ui-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="desc-{{ $plan->id }}" class="ui-label">Deskripsi</label>
                                <input id="desc-{{ $plan->id }}" name="description" type="text"
                                       value="{{ old('description', $plan->description) }}" class="{{ $smallInput }}">
                            </div>

                            <div class="flex flex-wrap gap-5 sm:col-span-2">
                                <label class="flex items-center gap-2 text-[13px] text-gray-700">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                                    Aktif
                                </label>
                                <label class="flex items-center gap-2 text-[13px] text-gray-700">
                                    <input type="hidden" name="is_popular" value="0">
                                    <input type="checkbox" name="is_popular" value="1" @checked(old('is_popular', $plan->is_popular))
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                                    Tandai populer
                                </label>
                            </div>

                            <div>
                                <label for="limits-{{ $plan->id }}" class="ui-label">Limits (JSON)</label>
                                <textarea id="limits-{{ $plan->id }}" name="limits" rows="6"
                                          class="ui-input mt-1 font-mono text-[13px]">{{ old('limits', json_encode($plan->limits ?? new stdClass, JSON_PRETTY_PRINT)) }}</textarea>
                                <p class="ui-hint">Kirim sebagai JSON objek. Contoh: {"ai_chat": 10, "projects": 3}</p>
                            </div>

                            <div>
                                <label for="features-{{ $plan->id }}" class="ui-label">Keunggulan (satu per baris)</label>
                                <textarea id="features-{{ $plan->id }}" name="features" rows="6"
                                          class="ui-input mt-1 text-[13px]">{{ old('features', implode("\n", $plan->features ?? [])) }}</textarea>
                                <p class="ui-hint">Satu keunggulan per baris.</p>
                            </div>

                            <div class="sm:col-span-2">
                                <button type="submit" class="ui-btn-primary">
                                    @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </details>
                </div>
            </div>
        @endforeach
    </div>
@endsection
