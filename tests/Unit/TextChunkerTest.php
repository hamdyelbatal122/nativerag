<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Tests\Unit;

use Hamzi\NativeRag\Services\TextChunker;
use Hamzi\NativeRag\Tests\TestCase;

class TextChunkerTest extends TestCase
{
    private TextChunker $chunker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chunker = new TextChunker;
    }

    public function test_returns_empty_array_for_empty_string(): void
    {
        $this->assertSame([], $this->chunker->chunk(''));
        $this->assertSame([], $this->chunker->chunk('   '));
    }

    public function test_returns_single_chunk_for_short_text(): void
    {
        $text = 'Hello world.';
        $chunks = $this->chunker->chunk($text, 1000, 200);

        $this->assertCount(1, $chunks);
        $this->assertSame($text, $chunks[0]);
    }

    public function test_splits_long_text_into_multiple_chunks(): void
    {
        $text = str_repeat('Lorem ipsum dolor sit amet. ', 100); // ~2800 chars
        $chunks = $this->chunker->chunk($text, 500, 100);

        $this->assertGreaterThan(1, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertNotEmpty($chunk);
        }
    }

    public function test_chunks_contain_content_from_original_text(): void
    {
        $text = 'First paragraph content here. '.str_repeat('x', 900).' Second paragraph content here.';
        $chunks = $this->chunker->chunk($text, 500, 100);

        $combined = implode(' ', $chunks);
        $this->assertStringContainsString('First paragraph', $combined);
        $this->assertStringContainsString('Second paragraph', $combined);
    }

    public function test_respects_chunk_size(): void
    {
        $text = str_repeat('A', 3000);
        $chunks = $this->chunker->chunk($text, 500, 0);

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(500, strlen($chunk));
        }
    }
}
