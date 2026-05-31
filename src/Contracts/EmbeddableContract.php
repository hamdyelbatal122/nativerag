<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Contracts;

use Hamzi\NativeRag\Models\NativeRagEmbedding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface EmbeddableContract
{
    /**
     * Define the string content that should be vector embedded.
     */
    public function toEmbeddableString(): string;

    /**
     * Define the polymorphic relationship to the embeddings table.
     *
     * @return MorphMany<NativeRagEmbedding, Model&EmbeddableContract>
     */
    public function embeddings(): MorphMany;

    /**
     * Synchronize the text chunks and their embeddings into the database.
     */
    public function syncEmbeddings(): void;
}
