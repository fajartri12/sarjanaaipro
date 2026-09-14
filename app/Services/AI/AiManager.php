<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Providers\NullProvider;
use App\Services\AI\Providers\OpenAiProvider;
use InvalidArgumentException;

/**
 * Pabrik provider. Membaca konfigurasi config/ai.php dan membangun
 * driver yang sesuai. Business logic tidak pernah tahu driver mana yang dipakai.
 */
class AiManager
{
    public function __construct(private array $config) {}

    public function default(): AiProvider
    {
        return $this->provider($this->config['default']);
    }

    public function provider(?string $name = null): AiProvider
    {
        $name = $name ?: $this->config['default'];
        $driver = $this->config['providers'][$name] ?? null;

        if (! $driver) {
            throw new InvalidArgumentException("AI provider '{$name}' tidak dikonfigurasi.");
        }

        return match ($driver['driver']) {
            'null' => new NullProvider($driver),
            'openai' => new OpenAiProvider($driver),
            default => throw new InvalidArgumentException("Driver AI '{$driver['driver']}' tidak dikenal."),
        };
    }

    /** Harga (rupiah) per 1 juta token untuk estimasi biaya. */
    public function pricing(string $model): array
    {
        return $this->config['pricing'][$model] ?? $this->config['pricing']['default'];
    }
}