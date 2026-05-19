<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Contracts;

use Hamzi\NativeRag\Data\ChatResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface ChatEngineContract
{
    /**
     * Send a list of messages to the local model and get a single chat completion response.
     *
     * @param array<array{role: string, content: string}> $messages
     * @param array<string, mixed> $options
     * @return ChatResponse
     */
    public function chat(array $messages, array $options = []): ChatResponse;

    /**
     * Send a list of messages to the local model and return a streamed SSE response.
     *
     * @param array<array{role: string, content: string}> $messages
     * @param array<string, mixed> $options
     * @return StreamedResponse
     */
    public function stream(array $messages, array $options = []): StreamedResponse;
}
