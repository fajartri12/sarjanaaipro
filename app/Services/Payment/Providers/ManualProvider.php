<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment;
use App\Models\Setting;
use App\Services\Payment\Contracts\PaymentProvider;

/**
 * Provider manual — dipakai saat gateway belum dikonfigurasi.
 * Checkout tetap berjalan dan menunggu konfirmasi admin.
 */
class ManualProvider implements PaymentProvider
{
    /**
     * Kanal transfer aktif. Sumber utama tabel settings (diisi admin lewat
     * panel), fallback ke config hanya kalau barisnya belum ada sama sekali.
     * Daftar kosong dianggap sah — admin memang belum mengisi rekening.
     *
     * @return array<int, array{name: string, type: string, number: string, holder: string}>
     */
    public static function channels(): array
    {
        $stored = Setting::get('payment_channels');

        return is_array($stored) ? array_values($stored) : config('payment.providers.manual.channels', []);
    }

    /** Kanal yang nomornya sudah diisi — ini yang tampil di halaman pembayaran. */
    public static function availableChannels(): array
    {
        return array_values(array_filter(
            static::channels(),
            fn ($channel) => ! empty($channel['number'])
        ));
    }

    public static function contact(): string
    {
        return (string) (Setting::get('payment_contact') ?? config('payment.providers.manual.contact', ''));
    }

    public function create(Payment $payment): array
    {
        return [
            'provider' => 'manual',
            'reference' => $payment->reference,
            'amount' => $payment->amount,
            'channels' => static::availableChannels(),
            'contact' => static::contact(),
            'instructions' => 'Transfer sesuai nominal, lalu konfirmasi ke admin dengan kode '.$payment->reference.'.',
            'redirect_url' => null,
        ];
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        return false;
    }

    public function statusFromWebhook(array $payload): string
    {
        return 'pending';
    }

    public function name(): string
    {
        return 'manual';
    }
}
