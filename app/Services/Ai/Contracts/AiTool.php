<?php

namespace App\Services\Ai\Contracts;

interface AiTool
{
    public function name(): string;

    /**
     * @return array<string, mixed> OpenAI-compatible tool schema
     */
    public function schema(): array;

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function handle(array $args, int $companyId, int $userId): array;

    public function isMutating(): bool;
}
