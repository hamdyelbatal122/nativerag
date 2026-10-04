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
        $conversation->addUserMessage($userMessage, prune: false);

        $messages = $conversation->messagesForChat();

        $driverName = $options['driver'] ?? null;
        $driver = NativeRag::driver($driverName);

        unset($options['driver']);

        $chatResponse = $driver->chat($messages, $options);

        return $conversation->addAssistantMessage(
            content: $chatResponse->content,
            tokens: $chatResponse->completionTokens
        );
    }
}
