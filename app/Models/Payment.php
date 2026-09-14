<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'reference',
        'invoice_number',
        'user_id',
        'plan_id',
        'subscription_id',
        'provider',
        'channel',
        'provider_reference',
        'amount',
        'currency',
        'status',
        'paid_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /**
     * Nomor invoice urut per tahun, mis. INV/2026/0001.
     * Diambil dari nomor terakhir di DB, bukan counter terpisah, supaya tidak ada state ganda.
     */
    public static function nextInvoiceNumber(?int $year = null): string
    {
        $year ??= (int) now()->year;
        $prefix = "INV/{$year}/";

        $last = static::where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** Scope: pembayaran lunas. */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /** Scope: pembayaran menunggu konfirmasi. */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** Scope: pembayaran gagal/kedaluwarsa. */
    public function scopeFailedOrExpired($query)
    {
        return $query->whereIn('status', ['expired', 'failed']);
    }

    /** Scope: pembayaran lunas tanpa nomor invoice. */
    public function scopeWithoutInvoice($query)
    {
        return $query->whereNull('invoice_number');
    }

    /** Scope: pembayaran menggantung lebih dari N hari. */
    public function scopeStale($query, int $days = 3)
    {
        return $query->where('status', 'pending')
            ->where('created_at', '<', now()->subDays($days));
    }

    /** Total nominal pembayaran lunas. */
    public static function paidTotal(): int
    {
        return (int) static::paid()->sum('amount');
    }

    /** Total nominal pembayaran menunggu. */
    public static function pendingTotal(): int
    {
        return (int) static::pending()->sum('amount');
    }

    /** Tren pendapatan lunas per hari, N hari terakhir. */
    public static function revenueTrend(int $days = 30): array
    {
        $rows = static::paid()
            ->where('paid_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $out[] = [
                'label' => $date->translatedFormat('j M'),
                'value' => (int) ($rows[$date->toDateString()] ?? 0),
            ];
        }

        return $out;
    }
}
