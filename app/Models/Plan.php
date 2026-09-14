<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    public const FREE = 'free';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'interval',
        'sort',
        'is_active',
        'is_popular',
        'limits',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'sort' => 'integer',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'limits' => 'array',
            'features' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Scope: paket aktif, urut sesuai kolom sort. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort');
    }

    /** Paket berikutnya yang lebih mahal dari paket sekarang, untuk saran upgrade. */
    public static function nextAfter(?Plan $current): ?Plan
    {
        return static::where('is_active', true)
            ->where('price', '>', $current?->price ?? 0)
            ->orderBy('price')
            ->first();
    }

    /** Limit sebuah fitur. null = unlimited. */
    public function limit(string $feature): ?int
    {
        // Default ke null (unlimited) kalau fitur tidak ada di limits.
        // Sebelumnya default ke 0 yang menyebabkan 429 untuk fitur baru.
        $value = $this->limits[$feature] ?? null;

        return $value === null ? null : ($value < 0 ? null : (int) $value);
    }
}
