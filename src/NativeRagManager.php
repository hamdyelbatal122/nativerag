<?php

declare(strict_types=1);

namespace Hamzi\NativeRag;

use Hamzi\NativeRag\Contracts\ChatEngineContract;
use Hamzi\NativeRag\Contracts\EmbeddingEngineContract;
use Hamzi\NativeRag\Drivers\LmStudioDriver;
use Hamzi\NativeRag\Drivers\OllamaDriver;
use Hamzi\NativeRag\Models\NativeRagEmbedding;
use Hamzi\NativeRag\Services\VectorSearchEngine;
use Illuminate\Support\Collection;
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

    /**
     * Perform a semantic vector search across indexed embeddings.
     * Accepts either a pre-computed vector array or a raw query string.
     *
     * @param  string|array<float>  $query
     * @return Collection<int, NativeRagEmbedding>
     */
    public function search(string|array $query, int $limit = 5, ?float $minScore = null): Collection
    {
        $vector = is_string($query)
            ? $this->embedding()->embed($query)
            : $query;

        /** @var array<float> $vector */
        return $this->container->make(VectorSearchEngine::class)
            ->search($vector, $limit, $minScore);
    }

    /**
     * Perform a hybrid search combining vector similarity and keyword search via Reciprocal Rank Fusion.
     *
     * @return Collection<int, NativeRagEmbedding>
     */
    public function searchHybrid(string $query, int $limit = 5, ?float $minScore = null, int $rrfK = 60): Collection
    {
        /** @var array<float> $vector */
        $vector = $this->embedding()->embed($query);

        return $this->container->make(VectorSearchEngine::class)
            ->searchHybrid($vector, $query, $limit, $minScore, $rrfK);
    }
}
