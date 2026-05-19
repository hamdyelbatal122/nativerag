<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Traits;

use Hamzi\NativeRag\Facades\NativeRag;
use Hamzi\NativeRag\Models\NativeRagEmbedding;
use Hamzi\NativeRag\Services\TextChunker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Embeddable
{
    /**
     * Boot the Embeddable trait to attach model lifecycle hooks.
     */
    public static function bootEmbeddable(): void
    {
        static::saved(function (Model $model) {
            // Attempt to sync embeddings synchronously on save.
            // In a heavily scaled app, developers might want to override this 
            // and dispatch to a queue. For NativeRAG defaults, we sync immediately.
            $model->syncEmbeddings();
        });

        static::deleted(function (Model $model) {
            $model->embeddings()->delete();
        });
    }

    /**
     * Define the polymorphic relationship to the embeddings table.
     */
    public function embeddings(): MorphMany
    {
        return $this->morphMany(NativeRagEmbedding::class, 'embeddable');
    }

    /**
     * Defines the string content that should be vector embedded.
     * Developers should override this method in their model to return the combined 
     * searchable fields (e.g. return $this->title . "\n\n" . $this->body).
     */
    public function toEmbeddableString(): string
    {
        // Fallback: json encode the model if not overridden.
        return $this->toJson();
    }

    /**
     * Synchronizes the text chunks and their embeddings into the database.
     * Generates a hash to avoid redundant API calls if the content hasn't changed.
     */
    public function syncEmbeddings(): void
    {
        $content = $this->toEmbeddableString();
        $hash = md5($content);

        // Check if the current hash exists and perfectly matches to skip re-processing.
        // We look at the first embedding's hash. If the document was empty, count == 0.
        $existingHash = $this->embeddings()->first()?->hash;
        
        if ($existingHash === $hash && $this->embeddings()->count() > 0) {
            return; // Content hasn't changed, skip expensive LLM API calls.
        }

        // Wipe old embeddings for this model
        $this->embeddings()->delete();

        if (trim($content) === '') {
            return;
        }

        $chunker = new TextChunker();
        $chunks = $chunker->chunk(
            $content,
            config('nativerag.embeddings.chunk_size', 1000),
            config('nativerag.embeddings.chunk_overlap', 200)
        );

        $embedder = NativeRag::embedding();

        foreach ($chunks as $chunkContent) {
            // Retrieve vector embedding array from the active driver (e.g. Ollama nomic-embed-text)
            $vector = $embedder->embed($chunkContent);

            $this->embeddings()->create([
                'chunk_content' => $chunkContent,
                'embedding' => $vector,
                'hash' => $hash,
            ]);
        }
    }
}
