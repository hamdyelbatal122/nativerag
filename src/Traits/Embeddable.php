<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Traits;

use Hamzi\NativeRag\Jobs\SyncEmbeddingsJob;
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
            if (config('nativerag.queue.enabled', false)) {
                $job = new SyncEmbeddingsJob($model);

                $connection = config('nativerag.queue.connection');
                if ($connection !== null) {
                    $job->onConnection((string) $connection);
                }

                $queue = config('nativerag.queue.queue');
                if ($queue !== null) {
                    $job->onQueue((string) $queue);
                }

                dispatch($job);
            } else {
                $model->syncEmbeddings();
            }
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

    public function toEmbeddableString(): string
    {
        return $this->toJson();
    }

    public function syncEmbeddings(bool $force = false): void
    {
        app(EmbeddingService::class)->sync($this, $force);
    }
}
