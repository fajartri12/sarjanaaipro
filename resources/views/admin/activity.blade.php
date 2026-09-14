@extends('layouts.app')

@section('title', 'Admin · Aktivitas')

@php
    $tanggal = fn ($value) => \App\Support\Labels::tanggal($value);
@endphp

@section('header')
    <div class="space-y-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-gray-500 transition hover:text-blue-700">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 'h-3.5 w-3.5'])
                Admin
            </a>
            <h1 class="ui-page-title mt-2">Log aktivitas</h1>
            <p class="ui-page-sub">Jejak aktivitas pengguna di dalam aplikasi.</p>
        </div>
        @include('partials.admin-nav')
    </div>
@endsection

@section('content')
    <div class="ui-table-wrap">
        @if ($logs->count())
            <div class="ui-card-head">
                <div>
                    <h2 class="ui-card-title">Riwayat aktivitas & audit log</h2>
                    <p class="ui-card-sub">{{ \App\Support\Labels::angka($logs->total()) }} catatan aktivitas pengguna dan sistem.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="ui-th">Waktu</th>
                            <th scope="col" class="ui-th">Pengguna</th>
                            <th scope="col" class="ui-th">Aksi</th>
                            <th scope="col" class="ui-th">Deskripsi & Objek Terkait</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($logs as $log)
                            @php
                                $subject = $log->subject_type ? class_basename($log->subject_type) : null;
                            @endphp
                            <tr class="ui-tr">
                                <td class="ui-td whitespace-nowrap font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ $tanggal($log->created_at) }}
                                </td>
                                <th scope="row" class="ui-td font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2.5">
                                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            {{ mb_substr($log->user?->name ?? 'S', 0, 1) }}
                                        </span>
                                        <span class="font-semibold">{{ $log->user?->name ?? 'Sistem' }}</span>
                                    </div>
                                </th>
                                <td class="ui-td">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-medium text-slate-700 dark:bg-slate-700/60 dark:text-slate-300">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="ui-td">
                                    <div class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                        {{ $log->description ?: $log->action }}
                                    </div>
                                    @if ($subject)
                                        <div class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                            Model: <span class="font-mono text-gray-600 dark:text-gray-400">{{ $subject }}</span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        @else
            <div class="ui-empty border-0 bg-transparent py-16">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'clock', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3.5 text-sm font-semibold text-gray-900 dark:text-white">Belum ada aktivitas</p>
                <p class="mt-1 text-[13px] text-gray-500">Log akan terisi begitu pengguna mulai beraktivitas.</p>
            </div>
        @endif
    </div>
@endsection
