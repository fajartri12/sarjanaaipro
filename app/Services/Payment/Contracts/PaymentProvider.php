<?php

namespace App\Services\Payment\Contracts;

use App\Models\Payment;

interface PaymentProvider
{
    /** Buat transaksi di gateway, kembalikan referensi + data untuk halaman bayar. */
    public function create(Payment $payment): array;

    /** Verifikasi signature webhook. Return false kalau tidak valid. */
    public function verifyWebhook(array $payload, array $headers = []): bool;

    /** Status pembayaran dari payload webhook: paid | failed | expired | pending */
    public function statusFromWebhook(array $payload): string;

    public function name(): string;
}
