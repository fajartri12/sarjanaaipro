<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class XenditProvider implements PaymentProvider
{
    public function __construct(private array $config) {}

    public function create(Payment $payment): array
    {
        $response = Http::withBasicAuth($this->config['secret_key'], '')
            ->acceptJson()
            ->post($this->config['base_url'].'/v2/invoices', [
                'external_id' => $payment->reference,
                'amount' => $payment->amount,
                'payer_email' => $payment->user->email,
                'description' => 'Langganan '.$payment->plan->name.' - Sarjana AI',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Xendit error: '.$response->body());
        }

        return [
            'provider' => 'xendit',
            'reference' => $response->json('id'),
            'instructions' => 'Selesaikan pembayaran melalui halaman Xendit.',
            'redirect_url' => $response->json('invoice_url'),
            'raw' => $response->json(),
        ];
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        $token = (string) ($this->config['callback_token'] ?? '');

        return $token !== '' && hash_equals(
            $token,
            (string) ($headers['x-callback-token'] ?? ''),
        );
    }

    public function statusFromWebhook(array $payload): string
    {
        return match ($payload['status'] ?? '') {
            'PAID', 'SETTLED' => 'paid',
            'EXPIRED' => 'expired',
            'FAILED' => 'failed',
            default => 'pending',
        };
    }

    public function name(): string
    {
        return 'xendit';
    }
}
