<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $payment->invoice_number }}</title>
    <style>
        @page { margin: 2cm; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.5;
        }
        .row { width: 100%; }
        .head {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 14px;
            margin-bottom: 24px;
        }
        .brand { font-size: 22px; font-weight: bold; color: #1d4ed8; letter-spacing: -0.5px; }
        .brand-sub { font-size: 10px; color: #6b7280; margin-top: 2px; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.meta td { vertical-align: top; padding: 0; }
        .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 3px;
        }
        .value { font-size: 12px; font-weight: bold; color: #111827; }
        .value-plain { font-size: 11px; color: #374151; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.items th {
            background: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
            padding: 9px 11px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4b5563;
            text-align: left;
        }
        table.items th.right, table.items td.right { text-align: right; }
        table.items td {
            padding: 12px 11px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .item-name { font-weight: bold; color: #111827; font-size: 12px; }
        .item-desc { color: #6b7280; font-size: 10px; margin-top: 2px; }
        table.totals { width: 100%; border-collapse: collapse; }
        table.totals td { padding: 6px 11px; }
        table.totals td.right { text-align: right; }
        table.totals .grand {
            border-top: 2px solid #2563eb;
            font-size: 15px;
            font-weight: bold;
            color: #1d4ed8;
        }
        .paid-stamp {
            display: inline-block;
            border: 2px solid #059669;
            color: #059669;
            border-radius: 6px;
            padding: 5px 14px;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .pending-stamp {
            display: inline-block;
            border: 2px solid #d97706;
            color: #d97706;
            border-radius: 6px;
            padding: 5px 14px;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .foot {
            margin-top: 34px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #6b7280;
            text-align: center;
        }
        .note {
            margin-top: 22px;
            padding: 11px 13px;
            background: #eff6ff;
            border-left: 3px solid #2563eb;
            font-size: 10px;
            color: #1e40af;
        }
    </style>
</head>
<body>

    <div class="head">
        <table class="row">
            <tr>
                <td>
                    <div class="brand">Sarjana AI</div>
                    <div class="brand-sub">Asisten penulisan skripsi, tesis, dan disertasi</div>
                </td>
                <td style="text-align: right;">
                    <div class="label">Invoice</div>
                    <div class="value">{{ $payment->invoice_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td width="33%">
                <div class="label">Ditagihkan kepada</div>
                <div class="value">{{ $payment->user?->name ?? '—' }}</div>
                <div class="value-plain">{{ $payment->user?->email ?? '' }}</div>
                @if ($payment->user?->university)
                    <div class="value-plain">{{ $payment->user->university }}</div>
                @endif
            </td>
            <td width="33%">
                <div class="label">Tanggal terbit</div>
                <div class="value-plain">{{ \App\Support\Labels::tanggal($payment->created_at) }}</div>

                <div class="label" style="margin-top: 12px;">Tanggal bayar</div>
                <div class="value-plain">{{ $payment->paid_at ? \App\Support\Labels::tanggal($payment->paid_at) : '—' }}</div>
            </td>
            <td width="33%" style="text-align: right;">
                <div class="label">Status</div>
                <div style="margin-top: 4px;">
                    <span class="{{ $payment->status === 'paid' ? 'paid-stamp' : 'pending-stamp' }}">
                        {{ \App\Support\Labels::PAYMENT_STATUS[$payment->status]['label'] ?? $payment->status }}
                    </span>
                </div>

                <div class="label" style="margin-top: 12px;">Metode</div>
                <div class="value-plain">{{ ucfirst($payment->provider) }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th>Referensi</th>
                <th class="right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="item-name">Langganan {{ $payment->plan?->name ?? 'Paket' }}</div>
                    <div class="item-desc">
                        Akses penuh selama 1 {{ $payment->plan?->interval === 'year' ? 'tahun' : 'bulan' }}
                    </div>
                </td>
                <td style="font-family: monospace; font-size: 10px; color: #6b7280;">
                    {{ $payment->reference }}
                </td>
                <td class="right" style="font-weight: bold;">
                    {{ \App\Support\Labels::rupiah($payment->amount) }}
                </td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td width="70%"></td>
            <td class="right" style="color: #6b7280; font-size: 10px;">Subtotal</td>
            <td class="right" style="width: 22%; font-weight: bold;">{{ \App\Support\Labels::rupiah($payment->amount) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="right" style="color: #6b7280; font-size: 10px;">Pajak</td>
            <td class="right" style="font-weight: bold;">Rp 0</td>
        </tr>
        <tr>
            <td></td>
            <td class="right grand">Total</td>
            <td class="right grand">{{ \App\Support\Labels::rupiah($payment->amount) }}</td>
        </tr>
    </table>

    @if ($payment->status === 'paid')
        <div class="note">
            Pembayaran ini sudah diterima dan diverifikasi. Invoice berlaku sebagai bukti
            pembayaran resmi untuk langganan {{ $payment->plan?->name ?? 'paket' }}.
        </div>
    @else
        <div class="note">
            Pembayaran belum diterima. Transfer sesuai nominal di atas lalu tunggu konfirmasi
            admin maksimal 1×24 jam kerja.
        </div>
    @endif

    <div class="foot">
        Sarjana AI · Invoice {{ $payment->invoice_number }} · Dicetak {{ now()->translatedFormat('j F Y H:i') }}
    </div>

</body>
</html>
