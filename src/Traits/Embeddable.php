<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Traits;

use Hamzi\NativeRag\Facades\NativeRag;
use Hamzi\NativeRag\Models\NativeRagEmbedding;
use Hamzi\NativeRag\Services\TextChunker;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Embeddable
{
    /**
     * Boot the Embeddable trait to attach model lifecycle hooks.
     */
    public static function bootEmbeddable(): void
    {
        // Use `self` so the callback receives the concrete model class
        // that uses this trait — ensures proper type resolution in PHP 8.2+
        static::saved(static function (self $model): void {
            $model->syncEmbeddings();
        });

        static::deleted(static function (self $model): void {
            $model->embeddings()->delete();
        });
    }

    /**
     * Define the polymorphic relationship to the embeddings table.
     *
     * @return MorphMany<NativeRagEmbedding, $this>
     */
    public function embeddings(): MorphMany
    {
        return $this->morphMany(NativeRagEmbedding::class, 'embeddable');
    }

    /**
     * Define the string content that should be vector embedded.
     *
     * Developers MUST override this method in their model to return the correct
     * searchable payload (e.g. return $this->title . "\n\n" . $this->body).
     */
    public function toEmbeddableString(): string
    {
        // Fallback: JSON-encode the entire model's visible attributes.
        return $this->toJson();
    }

    /**
     * Synchronizes the text chunks and their embeddings into the database.
     * Uses an MD5 hash for change detection to skip redundant embedding API calls.
     */
    public function syncEmbeddings(): void
    {
        $content = $this->toEmbeddableString();
        $hash    = md5($content);

        // Early exit: if the content hash hasn't changed AND embeddings exist, skip.
        $existingEmbedding = $this->embeddings()->first();

        if ($existingEmbedding !== null && $existingEmbedding->hash === $hash) {
            return;
        }

        // Wipe stale embeddings before re-indexing
        $this->embeddings()->delete();

        if (trim($content) === '') {
            return;
        }

        $chunkSize    = (int) config('nativerag.embeddings.chunk_size', 1000);
        $chunkOverlap = (int) config('nativerag.embeddings.chunk_overlap', 200);

        $chunks = (new TextChunker())->chunk($content, $chunkSize, $chunkOverlap);

        $embedder = NativeRag::embedding();

        foreach ($chunks as $chunkContent) {
            $vector = $embedder->embed($chunkContent);

            $this->embeddings()->create([
                'chunk_content' => $chunkContent,
                'embedding'     => $vector,
                'hash'          => $hash,
            ]);
        }
    }
}
