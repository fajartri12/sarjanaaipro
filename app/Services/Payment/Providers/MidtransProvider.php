<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransProvider implements PaymentProvider
{
    public function __construct(private array $config) {}

    public function create(Payment $payment): array
    {
        $response = Http::withBasicAuth($this->config['server_key'], '')
            ->acceptJson()
            ->post($this->config['base_url'].'/charge', [
                'payment_type' => 'bank_transfer',
                'transaction_details' => [
                    'order_id' => $payment->reference,
                    'gross_amount' => $payment->amount,
                ],
                'customer_details' => [
                    'first_name' => $payment->user->name,
                    'email' => $payment->user->email,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Midtrans error: '.$response->body());
        }

        return [
            'provider' => 'midtrans',
            'reference' => $response->json('transaction_id'),
            'instructions' => $response->json('va_numbers.0.va_number')
                ? 'Virtual Account: '.$response->json('va_numbers.0.va_number')
                : 'Ikuti instruksi pembayaran dari Midtrans.',
            'redirect_url' => $response->json('redirect_url'),
            'raw' => $response->json(),
        ];
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        $key = (string) ($this->config['server_key'] ?? '');

        // Kunci belum dikonfigurasi: tolak semua, jangan sampai signature kosong lolos.
        if ($key === '') {
            return false;
        }

        $expected = hash('sha512', ($payload['order_id'] ?? '').($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').$key);

        return hash_equals($expected, (string) ($payload['signature_key'] ?? ''));
    }

    public function statusFromWebhook(array $payload): string
    {
        $status = $payload['transaction_status'] ?? '';
        $fraud = $payload['fraud_status'] ?? 'accept';

        return match (true) {
            in_array($status, ['capture', 'settlement'], true) && $fraud === 'accept' => 'paid',
            in_array($status, ['deny', 'cancel', 'failure'], true) => 'failed',
            $status === 'expire' => 'expired',
            default => 'pending',
        };
    }

    public function name(): string
    {
        return 'midtrans';
    }
}
