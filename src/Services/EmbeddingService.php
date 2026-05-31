<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Services;

use Hamzi\NativeRag\Contracts\EmbeddableContract;
use Hamzi\NativeRag\Facades\NativeRag;

class EmbeddingService
{
    protected TextChunker $chunker;

    public function __construct(?TextChunker $chunker = null)
    {
        $this->chunker = $chunker ?? new TextChunker;
    }

    /**
     * Synchronize text chunks and their vector embeddings for a given embeddable model.
     */
    public function sync(EmbeddableContract $model): void
    {
        $content = $model->toEmbeddableString();
        $hash = md5($content);

        // Early exit: if the content hash hasn't changed AND embeddings exist, skip.
        $existingEmbedding = $model->embeddings()->first();

        if ($existingEmbedding !== null && $existingEmbedding->hash === $hash) {
            return;
        }

        // Wipe stale embeddings before re-indexing
        $model->embeddings()->delete();

        if (trim($content) === '') {
            return;
        }

        $chunkSize = (int) config('nativerag.embeddings.chunk_size', 1000);
        $chunkOverlap = (int) config('nativerag.embeddings.chunk_overlap', 200);

        $chunks = $this->chunker->chunk($content, $chunkSize, $chunkOverlap);

        $embedder = NativeRag::embedding();

        foreach ($chunks as $chunkContent) {
            $vector = $embedder->embed($chunkContent);

            $model->embeddings()->create([
                'chunk_content' => $chunkContent,
                'embedding' => $vector,
                'hash' => $hash,
            ]);
        }
    }
}
