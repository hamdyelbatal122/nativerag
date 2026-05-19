<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Services;

class PromptCompiler
{
    /**
     * Compile a prompt template with the provided variable replacements.
     *
     * @param string $template The prompt template containing {{placeholders}}
     * @param array<string, string> $variables Key-value pairs for substitution
     * @return string
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
     * @param string $systemInstruction Base instructions for the AI persona
     * @param string $context The text retrieved from the Vector Search Engine
     * @param string $query The user's specific question
     * @return string
     */
    public function buildRagPrompt(string $systemInstruction, string $context, string $query): string
    {
        $template = <<<PROMPT
{{instruction}}

You are a highly capable and intelligent assistant. Use ONLY the following provided context to answer the user's question. If the answer is not contained within the context, state that you do not have the information, and do not hallucinate an answer.

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
