<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Traits;

use Hamzi\NativeRag\Models\NativeRagEmbedding;
use Hamzi\NativeRag\Services\EmbeddingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Implements EmbeddableContract for Eloquent models.
 *
 * This trait should be used alongside `implements EmbeddableContract` on the model class.
 * It automatically hooks into Eloquent lifecycle events to synchronize embeddings.
 *
 * @mixin Model
 */
trait Embeddable
{
    /**
     * Boot the Embeddable trait to attach model lifecycle hooks.
     */
    public static function bootEmbeddable(): void
    {
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
     * Synchronize the text chunks and their embeddings into the database.
     * Delegates to EmbeddingService for clean separation of concerns.
     */
    public function syncEmbeddings(): void
    {
        app(EmbeddingService::class)->sync($this);
    }
}
