<?php

namespace App\Http\Controllers;

use App\Models\AiUsage;
use App\Models\Plan;
use App\Services\AI\UsageLimiter;
use Illuminate\Http\Request;

class QuotaController extends Controller
{
    /** Fitur yang punya kuota bulanan, beserta label dan ikonnya. */
    private const FEATURES = [
        'generate_titles' => ['label' => 'Generate Judul', 'icon' => 'sparkles', 'sub' => ['titles', 'title']],
        'ai_chat' => ['label' => 'AI Chat & Draft', 'icon' => 'chat', 'sub' => ['chat', 'draft', 'reviewer', 'sempro', 'research']],
        'ai_reviewer' => ['label' => 'AI Reviewer', 'icon' => 'shield', 'sub' => ['reviewer', 'similarity']],
        'pdf_analysis' => ['label' => 'Analisis PDF', 'icon' => 'document', 'sub' => ['pdf_analysis']],
        'projects' => ['label' => 'Project', 'icon' => 'folder', 'sub' => []],
    ];

    public function __construct(private UsageLimiter $limiter) {}

    public function index(Request $request): \Illuminate\View\View
    {
        $user = $request->user();
        $plan = $user->currentPlan();
        $subscription = $user->activeSubscription;

        return view('quota', [
            'rows' => $this->rows($user),
            'plan' => $plan,
            'subscription' => $subscription,
            'upgrade' => $this->upgradeHint($plan)?->only(['name', 'slug', 'price', 'limits']),
            'cost' => $this->costSummary($user),
            'history' => $this->monthlyHistory($user),
        ]);
    }

    /** Satu baris per fitur: limit, terpakai, sisa, dan pemakaian harian. */
    private function rows($user): array
    {
        $rows = [];

        foreach (self::FEATURES as $feature => $meta) {
            $limit = $this->limiter->limit($user, $feature);
            $used = $this->limiter->used($user, $feature);
            $remaining = $limit === null ? null : max(0, $limit - $used);

            $rows[$feature] = [
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'limit' => $limit,
                'used' => $used,
                'remaining' => $remaining,
                'percent' => $limit === null ? 0 : min(100, (int) round($used / max(1, $limit) * 100)),
                'exhausted' => $limit !== null && $used >= $limit,
                'breakdown' => $this->breakdown($user, $meta['sub']),
                'tokens' => AiUsage::where('user_id', $user->id)
                    ->whereIn('feature', $meta['sub'] ?: ['__none__'])
                    ->thisMonth()
                    ->sum('total_tokens'),
                'burst' => $this->busiestDay($user, $meta['sub']),
            ];
        }

        return $rows;
    }

    /** Pemakaian per sub-fitur di bulan berjalan, mis. chat vs draft. */
    private function breakdown($user, array $subFeatures): array
    {
        if (! $subFeatures) {
            return [];
        }

        return AiUsage::where('user_id', $user->id)
            ->whereIn('feature', $subFeatures)
            ->thisMonth()
            ->selectRaw('feature, count(*) as calls, sum(total_tokens) as tokens')
            ->groupBy('feature')
            ->orderByDesc('calls')
            ->get()
            ->map(fn ($row) => [
                'label' => \App\Support\Labels::FEATURE_LABEL[$row->feature] ?? $row->feature,
                'calls' => (int) $row->calls,
                'tokens' => (int) $row->tokens,
            ])
            ->all();
    }

    /** Hari tersibuk bulan ini, supaya kelihatan polanya. */
    private function busiestDay($user, array $subFeatures): ?array
    {
        if (! $subFeatures) {
            return null;
        }

        $row = AiUsage::where('user_id', $user->id)
            ->whereIn('feature', $subFeatures)
            ->thisMonth()
            ->selectRaw('DATE(created_at) as day, count(*) as calls')
            ->groupBy('day')
            ->orderByDesc('calls')
            ->first();

        return $row ? ['day' => \App\Support\Labels::tanggal($row->day), 'calls' => (int) $row->calls] : null;
    }

    /**
     * Paket berikutnya setelah paket sekarang. Cuma lihat harga, bukan
     * perbandingan limit — `limits` yang kosong artinya "tak dibatasi",
     * jadi membandingkannya angka per angka justru menyesatkan.
     */
    private function upgradeHint(?Plan $current): ?Plan
    {
        return Plan::nextAfter($current);
    }

    /**
     * Biaya AI yang dihasilkan akun ini, dibandingkan dengan nilai langganannya.
     * Dihitung per-bulan di PHP, bukan DATE_FORMAT, supaya kompatibel SQLite.
     */
    private function costSummary($user): array
    {
        $summary = AiUsage::monthlySummary($user->id);
        $revenue = (int) ($user->currentPlan()?->price ?? 0);

        return [
            'month_cost' => $summary['cost'],
            'month_tokens' => $summary['tokens'],
            'revenue' => $revenue,
            'margin' => $revenue - $summary['cost'],
            'ratio' => $revenue > 0 ? round($summary['cost'] / $revenue * 100, 1) : 0,
        ];
    }

    /**
     * Pemakaian 6 bulan terakhir, untuk melihat tren.
     * Dikelompokkan di PHP, bukan lewat DATE_FORMAT, supaya jalan di MySQL maupun SQLite.
     */
    private function monthlyHistory($user): array
    {
        $rows = AiUsage::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['created_at', 'total_tokens', 'estimated_cost', 'status']);

        $buckets = [];

        foreach ($rows as $row) {
            $key = \Illuminate\Support\Carbon::parse($row->created_at)->format('Y-m');
            $buckets[$key] ??= ['calls' => 0, 'tokens' => 0];
            $buckets[$key]['calls']++;
            $buckets[$key]['tokens'] += (int) $row->total_tokens;
        }

        $out = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $out[] = [
                'label' => $month->translatedFormat('M Y'),
                'calls' => $buckets[$month->format('Y-m')]['calls'] ?? 0,
                'tokens' => $buckets[$month->format('Y-m')]['tokens'] ?? 0,
                'current' => $i === 0,
            ];
        }

        return $out;
    }
}
