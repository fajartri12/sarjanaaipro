<?php

namespace App\Services\AI;

use App\Models\AiUsage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Pembungkus tunggal untuk semua pemanggilan AI.
 * Tugasnya: panggil provider, catat token + biaya + waktu ke ai_usage,
 * dan pastikan kegagalan tetap tercatat.
 */
class AiService
{
    public function __construct(
        private AiManager $manager,
        private UsageLimiter $limiter,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array{json?: bool, temperature?: float, max_tokens?: int}  $options
     */
    public function chat(
        array $messages,
        string $feature,
        ?User $user = null,
        ?Project $project = null,
        array $options = [],
    ): AiResult {
        // AI endpoint bisa lambat — bebaskan batas waktu PHP.
        set_time_limit(300);

        $provider = $this->manager->default();
        $usage = $this->reserve($feature, $user, $project, $provider->name(), $provider->model());
        $start = hrtime(true);

        try {
            $result = $provider->chat($messages, $options);
        } catch (\Throwable $e) {
            $this->finish(
                $usage,
                input: 0,
                output: 0,
                elapsed: (int) ((hrtime(true) - $start) / 1_000_000),
                status: 'failed',
                error: $e->getMessage(),
            );

            throw $e;
        }

        $this->finish(
            $usage,
            input: $result->inputTokens,
            output: $result->outputTokens,
            elapsed: $result->responseTime,
            status: 'success',
        );

        return $result;
    }

    /** @param  array<int, array{role: string, content: string}>  $messages */
    public function chatWithSystem(string $system, string $userPrompt, string $feature, ?User $user = null, ?Project $project = null, array $options = []): AiResult
    {
        return $this->chat(
            [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            $feature,
            $user,
            $project,
            $options,
        );
    }

    public function embed(string $text): array
    {
        return $this->manager->default()->embed($text);
    }

    public function model(): string
    {
        return $this->manager->default()->model();
    }

    public function providerName(): string
    {
        return $this->manager->default()->name();
    }

    private function reserve(string $feature, ?User $user, ?Project $project, string $provider, string $model): AiUsage
    {
        return DB::transaction(function () use ($feature, $user, $project, $provider, $model) {
            if ($user) {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $quotaFeature = $this->limiter->quotaFeature($feature);
                abort_unless($this->limiter->allows($user, $quotaFeature), 429, 'Kuota AI bulan ini sudah habis.');
            }

            return AiUsage::create([
                'user_id' => $user?->id,
                'project_id' => $project?->id,
                'provider' => $provider,
                'model' => $model,
                'feature' => $feature,
                'status' => 'pending',
            ]);
        });
    }

    private function finish(AiUsage $usage, int $input, int $output, int $elapsed, string $status, ?string $error = null): void
    {
        $pricing = $this->manager->pricing($usage->model);
        $cost = (int) round(($input / 1_000_000) * $pricing['input'] + ($output / 1_000_000) * $pricing['output']);

        $usage->update([
            'input_tokens' => $input,
            'output_tokens' => $output,
            'total_tokens' => $input + $output,
            'estimated_cost' => $cost,
            'response_time' => $elapsed,
            'status' => $status,
            'error' => $error,
        ]);
    }
}
