<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsage extends Model
{
    protected $table = 'ai_usage';

    protected $fillable = [
        'user_id',
        'project_id',
        'provider',
        'model',
        'feature',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'estimated_cost',
        'response_time',
        'status',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'total_tokens' => 'integer',
            'estimated_cost' => 'integer',
            'response_time' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Scope: panggilan sukses. */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /** Scope: panggilan sejak tanggal tertentu. */
    public function scopeSince($query, $date)
    {
        return $query->where('created_at', '>=', $date);
    }

    /** Scope: panggilan bulan berjalan. */
    public function scopeThisMonth($query)
    {
        return $query->where('created_at', '>=', now()->startOfMonth());
    }

    /** Total token pemakaian. */
    public static function totalTokens(?int $userId = null): int
    {
        return (int) static::when($userId, fn ($q) => $q->where('user_id', $userId))->sum('total_tokens');
    }

    /** Total biaya pemakaian yang sukses. */
    public static function totalCost(?int $userId = null): int
    {
        return (int) static::successful()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->sum('estimated_cost');
    }

    /** Ringkasan token & biaya bulan berjalan milik satu user. */
    public static function monthlySummary(int $userId): array
    {
        return [
            'tokens' => (int) static::where('user_id', $userId)->thisMonth()->sum('total_tokens'),
            'cost' => (int) static::successful()->where('user_id', $userId)->thisMonth()->sum('estimated_cost'),
        ];
    }

    /** Rekap pemakaian per fitur, termahal dulu. */
    public function scopeFeatureSummary($query)
    {
        return $query->selectRaw('feature, count(*) as calls, sum(total_tokens) as tokens, sum(estimated_cost) as cost')
            ->groupBy('feature')
            ->orderByDesc('cost');
    }

    /** Rekap pemakaian per user, termahal dulu. */
    public function scopeUserSummary($query, int $limit = 15)
    {
        return $query->selectRaw('user_id, count(*) as calls, sum(total_tokens) as tokens, sum(estimated_cost) as cost')
            ->with('user:id,name,email')
            ->groupBy('user_id')
            ->orderByDesc('cost')
            ->limit($limit);
    }

    /** Rekap pemakaian per model, termahal dulu. */
    public function scopeModelSummary($query)
    {
        return $query->selectRaw('model, count(*) as calls, sum(total_tokens) as tokens, sum(estimated_cost) as cost')
            ->groupBy('model')
            ->orderByDesc('cost');
    }

    /** Rekap per fitur lengkap dengan waktu respons dan jumlah gagal. */
    public function scopeFeatureDetail($query)
    {
        return $query->selectRaw("feature, count(*) as calls, sum(total_tokens) as tokens, sum(estimated_cost) as cost, avg(response_time) as avg_ms, sum(case when status != 'success' then 1 else 0 end) as failed")
            ->groupBy('feature')
            ->orderByDesc('cost');
    }

    /** Biaya, jumlah panggilan, dan waktu respons rata-rata per hari. */
    public static function dailyTrend(int $days = 30): array
    {
        $rows = static::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(created_at) as day, sum(estimated_cost) as cost, count(*) as calls, avg(response_time) as avg_ms')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $row = $rows[$date->toDateString()] ?? null;
            $out[] = [
                'label' => $date->translatedFormat('j M'),
                'cost' => (int) ($row->cost ?? 0),
                'calls' => (int) ($row->calls ?? 0),
                'avg_ms' => (int) round((float) ($row->avg_ms ?? 0)),
            ];
        }

        return $out;
    }

    /** Pemakaian per sub-fitur bulan berjalan milik satu user. */
    public function scopeFeatureBreakdown($query, int $userId, array $features)
    {
        return $query->where('user_id', $userId)
            ->whereIn('feature', $features)
            ->thisMonth()
            ->selectRaw('feature, count(*) as calls, sum(total_tokens) as tokens')
            ->groupBy('feature')
            ->orderByDesc('calls');
    }
}
