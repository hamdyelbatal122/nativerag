<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Data;

final readonly class ChatResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $content,
        public string $role,
        public array $raw,
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
    ) {}

    /**
     * Helper to array-serialize the response context.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'role' => $this->role,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'raw' => $this->raw,
        ];
    }
}
