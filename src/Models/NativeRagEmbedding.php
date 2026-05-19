<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $chunk_content
 * @property array<float> $embedding
 * @property string $hash
 */
class NativeRagEmbedding extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('nativerag.embeddings.table_name', 'nativerag_embeddings');
    }

    public function getConnectionName(): ?string
    {
        return config('nativerag.embeddings.connection');
    }

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, covariant $this>
     */
    public function embeddable(): MorphTo
    {
        return $this->morphTo('embeddable');
    }
}
