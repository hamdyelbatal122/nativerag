<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Facades;

use Hamzi\NativeRag\NativeRagManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Hamzi\NativeRag\Data\ChatResponse chat(array<int, array{role: string, content: string}> $messages, array<string, mixed> $options = [])
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse stream(array<int, array{role: string, content: string}> $messages, array<string, mixed> $options = [])
 * @method static \Hamzi\NativeRag\Contracts\EmbeddingEngineContract embedding()
 * @method static \Hamzi\NativeRag\Contracts\ChatEngineContract driver(?string $driver = null)
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
