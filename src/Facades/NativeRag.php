<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Hamzi\NativeRag\Data\ChatResponse chat(array $messages, array $options = [])
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse stream(array $messages, array $options = [])
 * @method static \Hamzi\NativeRag\Contracts\ChatEngineContract driver(string|null $driver = null)
 * @method static \Hamzi\NativeRag\Contracts\EmbeddingEngineContract embedding(string $driver = 'ollama')
 *
 * @see \Hamzi\NativeRag\NativeRagManager
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
