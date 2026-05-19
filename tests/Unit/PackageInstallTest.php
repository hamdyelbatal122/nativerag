<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Tests\Unit;

use Hamzi\NativeRag\Facades\NativeRag;
use Hamzi\NativeRag\NativeRagManager;
use Hamzi\NativeRag\Tests\TestCase;

class PackageInstallTest extends TestCase
{
    public function test_service_provider_registers_manager(): void
    {
        $this->assertInstanceOf(NativeRagManager::class, $this->app->make('nativerag'));
    }

    public function test_facade_resolves_to_manager(): void
    {
        $this->assertInstanceOf(NativeRagManager::class, NativeRag::getFacadeRoot());
    }

    public function test_config_is_loaded(): void
    {
        $this->assertNotNull(config('nativerag.default'));
        $this->assertSame('ollama', config('nativerag.default'));
        $this->assertNotNull(config('nativerag.drivers.ollama'));
        $this->assertNotNull(config('nativerag.drivers.lmstudio'));
    }

    public function test_ollama_driver_config_has_required_keys(): void
    {
        $config = config('nativerag.drivers.ollama');

        $this->assertArrayHasKey('base_url', $config);
        $this->assertArrayHasKey('model', $config);
        $this->assertArrayHasKey('embedding_model', $config);
        $this->assertArrayHasKey('timeout', $config);
    }

    public function test_lmstudio_driver_config_has_required_keys(): void
    {
        $config = config('nativerag.drivers.lmstudio');

        $this->assertArrayHasKey('base_url', $config);
        $this->assertArrayHasKey('model', $config);
        $this->assertArrayHasKey('timeout', $config);
    }

    public function test_embedding_config_has_required_keys(): void
    {
        $this->assertNotNull(config('nativerag.embeddings.chunk_size'));
        $this->assertNotNull(config('nativerag.embeddings.chunk_overlap'));
        $this->assertNotNull(config('nativerag.embeddings.min_score'));
    }
}
