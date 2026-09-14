<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Mengelola lifecycle langganan & pembayaran.
 * Aktivasi selalu lewat PaymentService → webhook → markPaid.
 */
class SubscriptionService
{
    public const FREE_GRACE_DAYS = 3;

    public function __construct(private PaymentManager $payments) {}

    /** Mulai checkout: buat payment + subscription pending, kembalikan instruksi bayar. */
    public function checkout(User $user, Plan $plan): array
    {
        return DB::transaction(function () use ($user, $plan) {
            $active = $user->subscriptions()
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->lockForUpdate()
                ->exists();

            abort_if($active, 422, 'Anda masih memiliki langganan aktif.');

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
            ]);

            $payment = Payment::create([
                'reference' => 'SA-'.strtoupper(Str::random(8)),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
                'provider' => $this->payments->default()->name(),
                'amount' => $plan->price,
                'status' => 'pending',
            ]);

            $checkout = $this->payments->default()->create($payment);
            $payment->update(['provider_reference' => $checkout['reference'] ?? $payment->provider_reference]);

            return [
                'subscription' => $subscription,
                'payment' => $payment,
                'checkout' => $checkout,
            ];
        });
    }

    /**
     * Rincian transfer untuk pembayaran manual yang masih menunggu.
     * Payload provider manual murni baca config (tanpa panggilan jaringan),
     * jadi aman dipanggil berulang saat membuka halaman langganan.
     * Return null kalau tidak ada pembayaran manual tertunda atau kanalnya kosong.
     */
    public function pendingManualInstructions(User $user): ?array
    {
        $payment = $user->payments()
            ->with('plan')
            ->where('provider', 'manual')
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $payment) {
            return null;
        }

        $checkout = $this->payments->provider('manual')->create($payment);

        if (empty($checkout['channels'])) {
            return null;
        }

        return $checkout + [
            'plan' => $payment->plan?->name,
            'payment_id' => $payment->id,
            'channel' => $payment->channel,
        ];
    }

    /** Tandai payment lunas lalu aktifkan subscription. Dipanggil dari webhook/konfirmasi admin. */
    public function markAsPaid(Payment $payment): Subscription
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'paid') {
                return $payment->subscription;
            }

            abort_if($payment->status !== 'pending', 422, 'Pembayaran sudah tidak dapat dikonfirmasi.');
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'invoice_number' => $payment->invoice_number ?? Payment::nextInvoiceNumber(),
            ]);

            $subscription = Subscription::query()->whereKey($payment->subscription_id)->lockForUpdate()->firstOrFail();
            $now = now();
            $endsAt = $payment->plan->interval === 'year'
                ? $now->addYear()
                : $now->addMonth();

            $subscription->update([
                'status' => 'active',
                'starts_at' => $now,
                'ends_at' => $endsAt,
                'canceled_at' => null,
            ]);

            return $subscription;
        });
    }

    public function cancel(User $user): void
    {
        $user->activeSubscription()->first()?->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'ends_at' => now(),
        ]);
    }

    /** Tangani webhook pembayaran dari provider. */
    public function handleWebhook(string $providerName, array $payload, array $headers = []): void
    {
        $provider = $this->payments->provider($providerName);

        abort_unless($provider->verifyWebhook($payload, $headers), 400, 'Signature webhook tidak valid.');

        $status = $provider->statusFromWebhook($payload);
        abort_unless(in_array($status, ['paid', 'failed', 'expired', 'pending'], true), 422, 'Status pembayaran tidak valid.');

        $providerRef = $payload['id'] ?? $payload['transaction_id'] ?? null;
        $reference = $payload['external_id'] ?? $payload['order_id'] ?? null;

        DB::transaction(function () use ($providerName, $payload, $status, $providerRef, $reference) {
            $payment = ($reference
                ? Payment::where('reference', $reference)->where('provider', $providerName)
                : Payment::where('provider', $providerName)->where('provider_reference', $providerRef)
            )->lockForUpdate()->first();

            abort_unless($payment, 404, 'Pembayaran tidak ditemukan.');
            abort_unless($this->webhookAmount($providerName, $payload) === (int) $payment->amount, 422, 'Nominal pembayaran tidak sesuai.');

            $wasPaid = $payment->status === 'paid';
            $payment->update([
                'payload' => $payload,
                'provider_reference' => $payment->provider_reference ?? $providerRef,
                'status' => $wasPaid ? 'paid' : ($status === 'paid' ? 'pending' : $status),
            ]);

            if ($status === 'paid' && ! $wasPaid) {
                $this->markAsPaid($payment);
            }
        });
    }

    private function webhookAmount(string $provider, array $payload): int
    {
        $amount = $provider === 'midtrans'
            ? $payload['gross_amount'] ?? null
            : $payload['amount'] ?? null;

        abort_unless(is_numeric($amount), 422, 'Nominal pembayaran tidak ditemukan.');

        return (int) round((float) $amount);
    }
}