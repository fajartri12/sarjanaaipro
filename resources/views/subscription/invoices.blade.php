@extends('layouts.app')

@section('title', 'Riwayat Invoice')

@php
    $rupiah = fn ($value) => \App\Support\Labels::rupiah($value);
    $tanggal = fn ($value) => \App\Support\Labels::tanggal($value);
@endphp

@section('header')
    <div>
        <h1 class="ui-page-title">Riwayat invoice</h1>
        <p class="ui-page-sub">Unduh invoice untuk setiap pembayaran yang sudah lunas.</p>
    </div>
@endsection

@section('content')
    <div class="ui-table-wrap">
        @if ($invoices->count())
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="ui-thead">
                        <tr>
                            <th scope="col" class="ui-th">Invoice</th>
                            <th scope="col" class="ui-th">Paket</th>
                            <th scope="col" class="ui-th">Tanggal Bayar</th>
                            <th scope="col" class="ui-th text-right">Nominal</th>
                            <th scope="col" class="ui-th text-center">Status</th>
                            <th scope="col" class="ui-th text-right">Unduh</th>
                        </tr>
                    </thead>
                    <tbody class="ui-tbody">
                        @foreach ($invoices as $payment)
                            <tr class="ui-tr">
                                <td class="ui-td">
                                    <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-xs font-medium text-gray-700 dark:bg-gray-700/60 dark:text-gray-300">
                                        {{ $payment->invoice_number }}
                                    </span>
                                </td>
                                <td class="ui-td font-medium text-gray-900 dark:text-white">
                                    {{ $payment->plan?->name ?? '—' }}
                                </td>
                                <td class="ui-td whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    {{ $tanggal($payment->paid_at) }}
                                </td>
                                <td class="ui-td text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                    {{ $rupiah($payment->amount) }}
                                </td>
                                <td class="ui-td text-center">
                                    @include('partials.badge', [
                                        'tone' => $payment->status === 'paid' ? 'green' : 'amber',
                                        'label' => \App\Support\Labels::PAYMENT_STATUS[$payment->status]['label'] ?? $payment->status,
                                    ])
                                </td>
                                <td class="ui-td text-right">
                                    <a href="{{ route('invoices.download', $payment->id) }}"
                                       class="ui-btn-primary ui-btn-xs inline-flex items-center gap-1.5">
                                        @include('partials.icon', ['name' => 'download', 'size' => 'h-3.5 w-3.5'])
                                        PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                {{ $invoices->links() }}
            </div>
        @else
            <div class="ui-empty border-0 bg-transparent">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                    @include('partials.icon', ['name' => 'document', 'size' => 'h-6 w-6'])
                </span>
                <p class="mt-3.5 text-sm font-semibold text-gray-900 dark:text-white">Belum ada invoice</p>
                <p class="mt-1 text-[13px] text-gray-500">Invoice akan muncul di sini setelah pembayaran dikonfirmasi.</p>
            </div>
        @endif
    </div>
@endsection