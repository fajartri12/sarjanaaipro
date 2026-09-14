<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiResult;
use App\Services\AI\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Provider berbasis OpenAI-compatible API (OpenAI, OpenRouter, dan sejenisnya).
 * Semua diramu lewat satu implementasi karena format request/response serupa.
 */
class OpenAiProvider implements AiProvider
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function chat(array $messages, array $options = []): AiResult
    {
        $start = hrtime(true);

        $payload = [
            'model' => $this->model(),
            'messages' => $messages,
        ];

        if (($options['json'] ?? false) === true) {
            $payload['response_format'] = ['type' => 'json_object'];
        }
        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }
        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }

        $response = Http::withToken($this->config['key'])
            ->timeout($this->config['timeout'] ?? 120)
            ->acceptJson()
            ->post(rtrim($this->config['base_url'], '/').'/chat/completions', $payload);

        if ($response->failed()) {
            throw new RuntimeException('AI provider error: '.$response->body());
        }

        $data = $response->json();
        $elapsed = (int) ((hrtime(true) - $start) / 1_000_000);

        return new AiResult(
            text: $data['choices'][0]['message']['content'] ?? '',
            inputTokens: $data['usage']['prompt_tokens'] ?? 0,
            outputTokens: $data['usage']['completion_tokens'] ?? 0,
            responseTime: $elapsed,
            raw: $data,
        );
    }

    public function embed(string $text): array
    {
        // ponytail: endpoint kustom kita tidak menjual model embedding sama sekali,
        // jadi OPENAI_EMBEDDING_MODEL sengaja dikosongkan. Pencarian dokumen sudah
        // jatuh ke pencarian kata lewat VectorSearch; lempar pesan yang jelas
        // daripada gagal dengan "undefined array key".
        if (empty($this->config['embedding_model'])) {
            throw new RuntimeException('Provider ini tidak punya model embedding.');
        }

        $response = Http::withToken($this->config['key'])
            ->timeout($this->config['timeout'] ?? 120)
            ->acceptJson()
            ->post(rtrim($this->config['base_url'], '/').'/embeddings', [
                'model' => $this->config['embedding_model'],
                'input' => $text,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Embedding provider error: '.$response->body());
        }

        return $response->json('data.0.embedding') ?? [];
    }

    public function name(): string
    {
        return $this->config['driver'];
    }

    public function model(): string
    {
        return $this->config['model'];
    }
}