<?php

declare(strict_types=1);

namespace Hamzi\NativeRag;

use Hamzi\NativeRag\Contracts\ChatEngineContract;
use Hamzi\NativeRag\Contracts\EmbeddingEngineContract;
use Hamzi\NativeRag\Drivers\LmStudioDriver;
use Hamzi\NativeRag\Drivers\OllamaDriver;
use Illuminate\Support\Manager;

class NativeRagManager extends Manager
{
    /**
     * Get the default chat driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('nativerag.default', 'ollama');
    }

    /**
     * Create an instance of the Ollama driver.
     */
    public function createOllamaDriver(): ChatEngineContract&EmbeddingEngineContract
    {
        return new OllamaDriver($this->config->get('nativerag.drivers.ollama', []));
    }

    /**
     * Create an instance of the LM Studio driver.
     */
    public function createLmstudioDriver(): ChatEngineContract&EmbeddingEngineContract
    {
        return new LmStudioDriver($this->config->get('nativerag.drivers.lmstudio', []));
    }

    /**
     * Get the embedding driver instance.
     */
    public function embedding(?string $driver = null): EmbeddingEngineContract
    {
        $driver ??= $this->config->get('nativerag.embeddings.driver') ?? $this->getDefaultDriver();

        $instance = $this->driver($driver);

        if (! $instance instanceof EmbeddingEngineContract) {
            throw new \InvalidArgumentException("Driver [{$driver}] does not support embeddings.");
        }

        return $instance;
    }
}
