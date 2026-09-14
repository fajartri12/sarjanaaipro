<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** Scope: langganan yang belum kedaluwarsa. */
    public function scopeNotExpired($query)
    {
        return $query->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /** Scope: langganan aktif dan belum kedaluwarsa. */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->notExpired();
    }

    /** Jumlah langganan aktif. */
    public static function activeCount(): int
    {
        return static::active()->count();
    }

    /** Distribusi langganan aktif per paket, untuk grafik. */
    public static function planDistribution(): array
    {
        return static::active()
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->selectRaw('plans.name, COUNT(*) as total')
            ->groupBy('plans.id', 'plans.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => $row->name, 'value' => (int) $row->total])
            ->values()
            ->all();
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && (! $this->ends_at || $this->ends_at->isFuture());
    }
}
