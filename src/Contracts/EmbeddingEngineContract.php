<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Contracts;

interface EmbeddingEngineContract
{
    /**
     * Convert text into a numerical vector embedding.
     *
     * @param string|array<string> $text
     * @param array<string, mixed> $options
     * @return array<int|string, array<float>|float>
     */
    public function embed(string|array $text, array $options = []): array;
}
