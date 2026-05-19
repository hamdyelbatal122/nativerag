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
    public function createLmstudioDriver(): ChatEngineContract
    {
        return new LmStudioDriver($this->config->get('nativerag.drivers.lmstudio', []));
    }

    /**
     * Get the default embedding driver.
     * By default, LM Studio may not provide distinct embedding endpoints in all setups,
     * so we fallback to Ollama if explicitly requested or let the driver handle it.
     */
    public function embedding(string $driver = 'ollama'): EmbeddingEngineContract
    {
        $instance = $this->driver($driver);

        if (! $instance instanceof EmbeddingEngineContract) {
            throw new \InvalidArgumentException("Driver [{$driver}] does not support embeddings.");
        }

        return $instance;
    }
}
