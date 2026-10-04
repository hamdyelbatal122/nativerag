<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Services;

class PromptCompiler
{
    /**
     * Compile a prompt template with the provided variable replacements.
     *
     * @param  string  $template  The prompt template containing {{placeholders}}
     * @param  array<string, string>  $variables  Key-value pairs for substitution
     */
    public function compile(string $template, array $variables = []): string
    {
        $compiled = $template;

        foreach ($variables as $key => $value) {
            $compiled = str_replace("{{{$key}}}", $value, $compiled);
        }

        return trim($compiled);
    }

    /**
     * Helper to build a standard RAG system prompt incorporating search context.
     *
     * @param  string  $systemInstruction  Base instructions for the AI persona
     * @param  string  $context  The text retrieved from the Vector Search Engine
     * @param  string  $query  The user's specific question
     */
    public function buildRagPrompt(string $systemInstruction, string $context, string $query): string
    {
        $template = <<<'PROMPT'
{{instruction}}

Use the following context to answer the question. If the context does not contain the answer, state that you do not have enough information.

<context>
{{context}}
</context>

Question: {{query}}
Answer:
PROMPT;

        return $this->compile($template, [
            'instruction' => $systemInstruction,
            'context' => empty($context) ? 'No relevant context found.' : $context,
            'query' => $query,
        ]);
    }
}
