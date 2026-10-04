<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Facades;

use Hamzi\NativeRag\NativeRagManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Hamzi\NativeRag\Data\ChatResponse chat(array<int, array{role: string, content: string}> $messages, array<string, mixed> $options = [])
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse stream(array<int, array{role: string, content: string}> $messages, array<string, mixed> $options = [])
 * @method static \Hamzi\NativeRag\Contracts\EmbeddingEngineContract embedding(?string $driver = null)
 * @method static \Hamzi\NativeRag\Contracts\ChatEngineContract driver(?string $driver = null)
 * @method static \Illuminate\Support\Collection<int, \Hamzi\NativeRag\Models\NativeRagEmbedding> search(string|array<float> $query, int $limit = 5, ?float $minScore = null)
 * @method static \Illuminate\Support\Collection<int, \Hamzi\NativeRag\Models\NativeRagEmbedding> searchHybrid(string $query, int $limit = 5, ?float $minScore = null, int $rrfK = 60)
 *
 * @see NativeRagManager
 */
class NativeRag extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'nativerag';
    }
}
