<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Tests\Unit;

use Hamzi\NativeRag\Contracts\EmbeddableContract;
use Hamzi\NativeRag\Models\NativeRagEmbedding;
use Hamzi\NativeRag\Services\VectorSearchEngine;
use Hamzi\NativeRag\Tests\TestCase;
use Hamzi\NativeRag\Traits\Embeddable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class TestArticle extends Model implements EmbeddableContract
{
    use Embeddable;

    protected $table = 'test_articles';
    protected $guarded = [];

    public function toEmbeddableString(): string
    {
        return $this->title."\n".$this->body;
    }
}

class VectorSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_articles', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function test_embeddable_trait_lifecycle(): void
    {
        // Fake Ollama embed HTTP response sequence
        Http::fake([
            'http://localhost:11434/api/embed' => Http::sequence()
                ->push([
                    'embeddings' => [
                        [0.1, 0.2, 0.3],
                    ],
                ], 200)
                ->push([
                    'embeddings' => [
                        [0.4, 0.5, 0.6],
                    ],
                ], 200),
        ]);

        $article = TestArticle::create([
            'title' => 'Hamzi NativeRag',
            'body' => 'Local LLMs are awesome.',
        ]);

        // Verify embedding is synchronized in DB
        $this->assertDatabaseHas('nativerag_embeddings', [
            'embeddable_type' => TestArticle::class,
            'embeddable_id' => (string) $article->id,
            'hash' => md5("Hamzi NativeRag\nLocal LLMs are awesome."),
        ]);

        $embeddings = $article->embeddings;
        $this->assertCount(1, $embeddings);
        $this->assertSame([0.1, 0.2, 0.3], $embeddings->first()->embedding);

        $article->update(['body' => 'Different content.']);

        $article->refresh();
        $this->assertCount(1, $article->embeddings);
        $this->assertSame([0.4, 0.5, 0.6], $article->embeddings->first()->embedding);

        // Delete the article
        $article->delete();

        $this->assertDatabaseMissing('nativerag_embeddings', [
            'embeddable_type' => TestArticle::class,
            'embeddable_id' => (string) $article->id,
        ]);
    }

    public function test_vector_search_engine_collection_strategy(): void
    {
        config(['nativerag.embeddings.search_strategy' => 'collection']);

        // Insert manually mock embeddings
        // Vector A: [1.0, 0.0]
        // Vector B: [0.0, 1.0]
        NativeRagEmbedding::create([
            'embeddable_type' => 'App\\Models\\Dummy',
            'embeddable_id' => '1',
            'chunk_content' => 'Content matching Vector A',
            'embedding' => [1.0, 0.0],
            'hash' => 'hash_a',
        ]);

        NativeRagEmbedding::create([
            'embeddable_type' => 'App\\Models\\Dummy',
            'embeddable_id' => '2',
            'chunk_content' => 'Content matching Vector B',
            'embedding' => [0.0, 1.0],
            'hash' => 'hash_b',
        ]);

        $engine = new VectorSearchEngine;

        // Search closest to Vector A: [1.0, 0.0]
        $results = $engine->search([1.0, 0.0], limit: 2, minScore: 0.1);

        $this->assertCount(1, $results); // Vector B similarity is 0.0, which is < minScore (0.1)
        $this->assertSame('Content matching Vector A', $results[0]->chunk_content);
        $this->assertEqualsWithDelta(1.0, $results[0]->similarity, 0.001);
    }

    public function test_vector_search_engine_database_strategy_sqlite(): void
    {
        config(['nativerag.embeddings.search_strategy' => 'database']);

        NativeRagEmbedding::create([
            'embeddable_type' => 'App\\Models\\Dummy',
            'embeddable_id' => '1',
            'chunk_content' => 'Vector A',
            'embedding' => [1.0, 0.0, 0.0],
            'hash' => 'hash_a',
        ]);

        NativeRagEmbedding::create([
            'embeddable_type' => 'App\\Models\\Dummy',
            'embeddable_id' => '2',
            'chunk_content' => 'Vector B',
            'embedding' => [0.0, 1.0, 0.0],
            'hash' => 'hash_b',
        ]);

        $engine = new VectorSearchEngine;

        // Search closest to Vector B: [0.0, 1.0, 0.0]
        $results = $engine->search([0.0, 1.0, 0.0], limit: 5, minScore: 0.0);

        $this->assertCount(2, $results);
        $this->assertSame('Vector B', $results[0]->chunk_content);
        $this->assertEqualsWithDelta(1.0, $results[0]->similarity, 0.001);

        $this->assertSame('Vector A', $results[1]->chunk_content);
        $this->assertEqualsWithDelta(0.0, $results[1]->similarity, 0.001);
    }
}
