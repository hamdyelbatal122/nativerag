<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Tests\Unit;

use Hamzi\NativeRag\Drivers\LmStudioDriver;
use Hamzi\NativeRag\Drivers\OllamaDriver;
use Hamzi\NativeRag\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class EmbeddingDriverTest extends TestCase
{
    public function test_ollama_driver_embed_single_string(): void
    {
        Http::fake([
            'http://localhost:11434/api/embed' => Http::response([
                'embeddings' => [
                    [0.1, 0.2, 0.3],
                ],
            ], 200),
        ]);

        $driver = new OllamaDriver([
            'base_url' => 'http://localhost:11434',
            'embedding_model' => 'nomic-embed-text',
        ]);

        $embedding = $driver->embed('Hello world');

        $this->assertSame([0.1, 0.2, 0.3], $embedding);
    }

    public function test_ollama_driver_embed_multiple_strings(): void
    {
        Http::fake([
            'http://localhost:11434/api/embed' => Http::response([
                'embeddings' => [
                    [0.1, 0.2, 0.3],
                    [0.4, 0.5, 0.6],
                ],
            ], 200),
        ]);

        $driver = new OllamaDriver([
            'base_url' => 'http://localhost:11434',
            'embedding_model' => 'nomic-embed-text',
        ]);

        $embeddings = $driver->embed(['Hello', 'World']);

        $this->assertSame([
            [0.1, 0.2, 0.3],
            [0.4, 0.5, 0.6],
        ], $embeddings);
    }

    public function test_lmstudio_driver_embed_single_string(): void
    {
        Http::fake([
            'http://localhost:1234/v1/embeddings' => Http::response([
                'data' => [
                    [
                        'embedding' => [0.7, 0.8, 0.9],
                        'index' => 0,
                    ],
                ],
            ], 200),
        ]);

        $driver = new LmStudioDriver([
            'base_url' => 'http://localhost:1234',
            'embedding_model' => 'nomic-embed-text',
        ]);

        $embedding = $driver->embed('Test string');

        $this->assertSame([0.7, 0.8, 0.9], $embedding);
    }

    public function test_lmstudio_driver_embed_multiple_strings(): void
    {
        Http::fake([
            'http://localhost:1234/v1/embeddings' => Http::response([
                'data' => [
                    [
                        'embedding' => [0.7, 0.8, 0.9],
                        'index' => 0,
                    ],
                    [
                        'embedding' => [0.1, 0.2, 0.3],
                        'index' => 1,
                    ],
                ],
            ], 200),
        ]);

        $driver = new LmStudioDriver([
            'base_url' => 'http://localhost:1234',
            'embedding_model' => 'nomic-embed-text',
        ]);

        $embeddings = $driver->embed(['String 1', 'String 2']);

        $this->assertSame([
            [0.7, 0.8, 0.9],
            [0.1, 0.2, 0.3],
        ], $embeddings);
    }
}
