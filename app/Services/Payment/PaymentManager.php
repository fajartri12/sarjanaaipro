<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentProvider;
use App\Services\Payment\Providers\ManualProvider;
use App\Services\Payment\Providers\MidtransProvider;
use App\Services\Payment\Providers\XenditProvider;
use InvalidArgumentException;

/** Pabrik provider pembayaran. Sama polanya dengan AiManager. */
class PaymentManager
{
    public function __construct(private array $config) {}

    public function default(): PaymentProvider
    {
        return $this->provider($this->config['default']);
    }

    public function provider(?string $name = null): PaymentProvider
    {
        $name = $name ?: $this->config['default'];
        $driver = $this->config['providers'][$name] ?? null;

        if (! $driver) {
            throw new InvalidArgumentException("Payment provider '{$name}' tidak dikonfigurasi.");
        }

        return match ($driver['driver']) {
            'manual' => new ManualProvider(),
            'midtrans' => new MidtransProvider($driver),
            'xendit' => new XenditProvider($driver),
            default => throw new InvalidArgumentException("Driver pembayaran '{$driver['driver']}' tidak dikenal."),
        };
    }
}
