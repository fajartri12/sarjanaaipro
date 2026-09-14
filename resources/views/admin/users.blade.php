@extends('layouts.app')

@section('title', 'Admin · Pengguna')

@php
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
            <h1 class="ui-page-title mt-2">Pengguna</h1>
            <p class="ui-page-sub">Kelola role dan status aktif akun.</p>
        </div>
        @include('partials.admin-nav')
    </div>
@endsection

@section('content')
    <div class="ui-table-wrap">
        {{-- Flowbite Search & Filter Bar --}}
        <div class="border-b border-gray-100 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <form method="GET" action="{{ route('admin.users') }}" class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-[240px] flex-1">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400">
                        @include('partials.icon', ['name' => 'search', 'size' => 'h-4 w-4'])
                    </div>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama atau email pengguna…"
                           class="block w-full rounded-xl border border-gray-200 bg-gray-50/70 p-2.5 ps-9 text-sm text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900/50 dark:text-white">
                </div>
                <select name="role" class="rounded-xl border border-gray-200 bg-gray-50/70 p-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <option value="">Semua role</option>
                    <option value="student" @selected(($filters['role'] ?? '') === 'student')>Mahasiswa</option>
                    <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                </select>
                <select name="degree_level" class="rounded-xl border border-gray-200 bg-gray-50/70 p-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <option value="">Semua jenjang</option>
                    @foreach (\App\Support\Labels::DEGREE_LEVEL as $key => $level)
                        <option value="{{ $key }}" @selected(($filters['degree_level'] ?? '') === $key)>{{ $key }} — {{ $level['degree'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="ui-btn-secondary ui-btn-sm">
                    @include('partials.icon', ['name' => 'check', 'size' => 'h-4 w-4'])
                    Terapkan
                </button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.users') }}" class="ui-link text-[13px]">Reset</a>
                @endif
            </form>
        </div>

        @if ($users->count())
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="ui-th">Pengguna</th>
                            <th scope="col" class="ui-th">Role</th>
                            <th scope="col" class="ui-th">Jenjang</th>
                            <th scope="col" class="ui-th">Status</th>
                            <th scope="col" class="ui-th">Project & Paket</th>
                            <th scope="col" class="ui-th">Terdaftar</th>
                            <th scope="col" class="ui-th text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($users as $user)
                            @php $active = $user->activeSubscription?->plan?->name; @endphp
                            <tr class="ui-tr">
                                <th scope="row" class="ui-td font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-blue-50 to-indigo-100 text-xs font-bold uppercase text-blue-700 shadow-sm dark:from-gray-700 dark:to-gray-800 dark:text-blue-300">
                                            {{ mb_substr($user->name, 0, 1) }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </th>
                                <td class="ui-td">
                                    @include('partials.badge', [
                                        'tone' => $user->role === 'admin' ? 'violet' : 'gray',
                                        'label' => $user->role === 'admin' ? 'Admin' : 'Mahasiswa',
                                    ])
                                </td>
                                <td class="ui-td">
                                    <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ \App\Support\Labels::DEGREE_LEVEL[$user->degree_level]['label'] ?? $user->degree_level }}
                                    </span>
                                </td>
                                <td class="ui-td">
                                    @include('partials.badge', [
                                        'tone' => $user->is_active ? 'green' : 'red',
                                        'label' => $user->is_active ? 'Aktif' : 'Nonaktif',
                                    ])
                                </td>
                                <td class="ui-td">
                                    <div class="text-xs text-gray-700 dark:text-gray-300">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $user->projects_count }}</span> project
                                        @if ($active)
                                            <span class="text-gray-300 dark:text-gray-600">·</span>
                                            <span class="inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                                {{ $active }}
                                            </span>
                                        @endif
                                        @php $sub = $user->latestSubscription; @endphp
                                        @if ($sub?->ends_at)
                                            <div class="mt-1 {{ $sub->isActive() ? 'text-gray-500 dark:text-gray-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                {{ $sub->isActive() ? 'Berakhir' : 'Kedaluwarsa' }} {{ $tanggal($sub->ends_at) }}
                                            </div>
                                        @elseif ($sub)
                                            <div class="mt-1 text-gray-400 dark:text-gray-500">Tanpa tanggal akhir</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="ui-td whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    {{ $tanggal($user->created_at) }}
                                </td>
                                <td class="ui-td text-right">
                                    <details class="group relative inline-block text-left">
                                        <summary class="ui-btn-secondary ui-btn-xs list-none cursor-pointer">
                                            @include('partials.icon', ['name' => 'plus', 'size' => 'h-3.5 w-3.5'])
                                            Kelola
                                        </summary>

                                        <div class="absolute right-0 z-30 mt-2 w-72 rounded-2xl border border-gray-100 bg-white p-4 shadow-xl dark:border-gray-700 dark:bg-gray-800">
                                            <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="space-y-3 text-left">
                                                @csrf
                                                @method('PATCH')
                                                <div>
                                                    <label for="role-{{ $user->id }}" class="ui-label-sm">Ubah Role</label>
                                                    <select id="role-{{ $user->id }}" name="role" class="ui-input-sm mt-1 w-full">
                                                        <option value="student" @selected($user->role === 'student')>Mahasiswa</option>
                                                        <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label for="degree-{{ $user->id }}" class="ui-label-sm">Jenjang</label>
                                                    <select id="degree-{{ $user->id }}" name="degree_level" class="ui-input-sm mt-1 w-full">
                                                        @foreach (\App\Support\Labels::DEGREE_LEVEL as $key => $level)
                                                            <option value="{{ $key }}" @selected($user->degree_level === $key)>{{ $key }} — {{ $level['degree'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="pt-1">
                                                    <label class="flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                        <input type="hidden" name="is_active" value="0">
                                                        <input type="checkbox" name="is_active" value="1" @checked($user->is_active)
                                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        Status Akun Aktif
                                                    </label>
                                                </div>
                                                <div class="border-t border-gray-100 pt-3 dark:border-gray-700">
                                                    <button type="submit" class="ui-btn-primary ui-btn-xs w-full justify-center">
                                                        Simpan Perubahan
                                                    </button>
                                                </div>
                                            </form>

                                            @if ($user->latestSubscription)
                                                <form method="POST" action="{{ route('admin.users.subscription', $user->id) }}" class="mt-3 space-y-3 border-t border-gray-100 pt-3 text-left dark:border-gray-700">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div>
                                                        <label for="ends-{{ $user->id }}" class="ui-label-sm">Tanggal Kedaluwarsa</label>
                                                        <input id="ends-{{ $user->id }}" name="ends_at" type="date"
                                                               value="{{ $user->latestSubscription->ends_at?->format('Y-m-d') }}"
                                                               class="ui-input-sm mt-1 w-full">
                                                        <p class="mt-1 text-[11px] leading-relaxed text-gray-400 dark:text-gray-500">
                                                            Kosongkan untuk menghapus batas waktu. Tanggal di masa depan otomatis mengaktifkan langganan.
                                                        </p>
                                                    </div>
                                                    <button type="submit" class="ui-btn-secondary ui-btn-xs w-full justify-center">
                                                        @include('partials.icon', ['name' => 'clock', 'size' => 'h-3.5 w-3.5'])
                                                        Ubah Masa Aktif
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                {{ $users->links() }}
            </div>
        @else
            <div class="ui-empty border-0 bg-transparent py-16">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'user', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3.5 text-sm font-semibold text-gray-900 dark:text-white">Tidak ada pengguna ditemukan</p>
                <p class="mt-1 text-[13px] text-gray-500">Coba ubah kata kunci pencarian atau filter role.</p>
            </div>
        @endif
    </div>
@endsection
