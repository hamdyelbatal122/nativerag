<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Services;

use Hamzi\NativeRag\Facades\NativeRag;
use Hamzi\NativeRag\Models\NativeRagConversation;
use Hamzi\NativeRag\Models\NativeRagMessage;

class ConversationService
{
    /**
     * Send a user message to the conversation session, call the AI driver,
     * save both to the database, and return the assistant response message.
     *
     * @param  array<string, mixed>  $options
     */
    public function ask(NativeRagConversation $conversation, string $userMessage, array $options = []): NativeRagMessage
    {
        // 1. Add user message
        $conversation->addUserMessage($userMessage);

        // 2. Compile full chat history for the driver
        $messages = $conversation->messagesForChat();

        // 3. Request LLM response
        $driverName = $options['driver'] ?? null;
        $driver = NativeRag::driver($driverName);

        unset($options['driver']);

        $chatResponse = $driver->chat($messages, $options);

        // 4. Add assistant response
        return $conversation->addAssistantMessage(
            content: $chatResponse->content,
            tokens: $chatResponse->completionTokens
        );
    }
}
