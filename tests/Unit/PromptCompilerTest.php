<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Tests\Unit;

use Hamzi\NativeRag\Services\PromptCompiler;
use Hamzi\NativeRag\Tests\TestCase;

class PromptCompilerTest extends TestCase
{
    private PromptCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compiler = new PromptCompiler;
    }

    public function test_replaces_single_placeholder(): void
    {
        $result = $this->compiler->compile('Hello {{name}}!', ['name' => 'World']);

        $this->assertSame('Hello World!', $result);
    }

    public function test_replaces_multiple_placeholders(): void
    {
        $result = $this->compiler->compile(
            'Context: {{context}} — Query: {{query}}',
            ['context' => 'PHP is great', 'query' => 'What is PHP?']
        );

        $this->assertSame('Context: PHP is great — Query: What is PHP?', $result);
    }

    public function test_ignores_unknown_placeholders(): void
    {
        $result = $this->compiler->compile('Hello {{name}} {{unknown}}!', ['name' => 'World']);

        $this->assertSame('Hello World {{unknown}}!', $result);
    }

    public function test_build_rag_prompt_contains_context_and_query(): void
    {
        $prompt = $this->compiler->buildRagPrompt(
            'You are a helpful assistant.',
            'Laravel is a PHP framework.',
            'What is Laravel?'
        );

        $this->assertStringContainsString('Laravel is a PHP framework.', $prompt);
        $this->assertStringContainsString('What is Laravel?', $prompt);
        $this->assertStringContainsString('You are a helpful assistant.', $prompt);
    }

    public function test_build_rag_prompt_uses_fallback_when_context_empty(): void
    {
        $prompt = $this->compiler->buildRagPrompt('', '', 'What is Laravel?');

        $this->assertStringContainsString('No relevant context found.', $prompt);
    }

    public function test_compile_trims_whitespace(): void
    {
        $result = $this->compiler->compile('  Hello {{name}}  ', ['name' => 'World']);

        $this->assertSame('Hello World', $result);
    }
}
