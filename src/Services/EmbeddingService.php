<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Services;

use Hamzi\NativeRag\Contracts\EmbeddableContract;
use Hamzi\NativeRag\Facades\NativeRag;

class EmbeddingService
{
    protected TextChunker $chunker;

    protected int $batchSize;

    public function __construct(?TextChunker $chunker = null)
    {
        $this->chunker = $chunker ?? new TextChunker;
        $this->batchSize = (int) config('nativerag.embeddings.batch_size', 32);
    }

    /**
     * Synchronize text chunks and their vector embeddings for a given embeddable model.
     */
    public function sync(EmbeddableContract $model, bool $force = false): void
    {
        $content = $model->toEmbeddableString();
        $hash = md5($content);

        $existingEmbedding = $model->embeddings()->first();

        if (! $force && $existingEmbedding !== null && $existingEmbedding->hash === $hash) {
            return;
        }

        $model->embeddings()->delete();

        if (trim($content) === '') {
            return;
        }

        $chunkSize = (int) config('nativerag.embeddings.chunk_size', 1000);
        $chunkOverlap = (int) config('nativerag.embeddings.chunk_overlap', 200);

        $chunks = $this->chunker->chunk($content, $chunkSize, $chunkOverlap);

        $embedder = NativeRag::embedding();

        // Batch embed to minimize HTTP round-trips
        foreach (array_chunk($chunks, $this->batchSize) as $batch) {
            $vectors = $embedder->embed($batch);
            $vectors = array_values($vectors);

            foreach ($batch as $index => $chunkContent) {
                $vector = $vectors[$index] ?? null;

                if (! is_array($vector)) {
                    throw new \RuntimeException("Failed to generate embedding vector for chunk index {$index}.");
                }

                $model->embeddings()->create([
                    'chunk_content' => $chunkContent,
                    'embedding' => $vector,
                    'hash' => $hash,
                ]);
            }
        }
    }
}
