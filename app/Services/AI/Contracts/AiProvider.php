<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\AiResult;

/**
 * Kontrak provider AI. Business logic hanya bicara lewat interface ini,
 * jadi provider/model bisa ditukar tanpa mengubah service fitur.
 */
interface AiProvider
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array{json?: bool, temperature?: float, max_tokens?: int}  $options
     */
    public function chat(array $messages, array $options = []): AiResult;

    /** @return array<int, float> */
    public function embed(string $text): array;

    public function name(): string;

    public function model(): string;
}
