<?php

namespace App\Services\AI;

use App\Models\Plan;

/**
 * Batas kuota AI per plan. Semua limit dibaca dari tabel plans,
 * tidak ada angka yang di-hardcode di controller maupun frontend.
 *
 * Baris ai_usage berstatus `pending` ikut dihitung, bukan hanya `success`.
 * Itu yang membuat dua request bersamaan tidak bisa lolos berdua: request
 * pertama menahan slotnya lebih dulu, request kedua melihat kuota terpakai.
 */
class UsageLimiter
{
    /** Fitur yang dihitung per bulan. */
    public const MONTHLY_FEATURES = [
        'generate_titles' => ['title'],
        'ai_chat' => ['chat', 'draft', 'reviewer', 'sempro', 'research'],
        'pdf_analysis' => ['pdf_analysis'],
        'ai_reviewer' => ['reviewer', 'similarity'],
    ];

    /** Sisa kuota fitur. null = unlimited. */
    public function remaining($user, string $feature): ?int
    {
        $limit = $this->limit($user, $feature);

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->used($user, $feature));
    }

    public function allows($user, string $feature): bool
    {
        // Admin bypass: owner of the app shouldn't be blocked by their own plan quota.
        if ($user->isAdmin()) {
            return true;
        }

        $remaining = $this->remaining($user, $feature);

        return $remaining === null || $remaining > 0;
    }

    public function limit($user, string $feature): ?int
    {
        $plan = $user->currentPlan();

        if (! $plan) {
            return config("ai.default_limits.{$feature}");
        }

        return $plan->limit($feature);
    }

    /** Tag feature di ai_usage untuk sebuah kuota. */
    public function tags(string $feature): array
    {
        return self::MONTHLY_FEATURES[$feature] ?? [$feature];
    }

    /** Kelompok kuota yang menaungi feature internal, atau feature itu sendiri. */
    public function quotaFeature(string $feature): string
    {
        foreach (self::MONTHLY_FEATURES as $quotaFeature => $tags) {
            if (in_array($feature, $tags, true)) {
                return $quotaFeature;
            }
        }

        return $feature;
    }

    public function used($user, string $feature): int
    {
        return $user->aiUsages()
            ->whereIn('feature', $this->tags($feature))
            ->whereIn('status', ['pending', 'success'])
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }
}
