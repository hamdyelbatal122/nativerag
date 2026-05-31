<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Tests\Unit;

use Hamzi\NativeRag\Contracts\ChatEngineContract;
use Hamzi\NativeRag\Data\ChatResponse;
use Hamzi\NativeRag\Facades\NativeRag;
use Hamzi\NativeRag\Models\NativeRagConversation;
use Hamzi\NativeRag\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    public function test_can_create_conversation_and_messages(): void
    {
        $conversation = NativeRagConversation::create(['name' => 'Test Chat']);

        $this->assertDatabaseHas('nativerag_conversations', [
            'id' => $conversation->id,
            'name' => 'Test Chat',
        ]);

        $conversation->addSystemMessage('You are helpful.');
        $conversation->addUserMessage('Hello!');
        $conversation->addAssistantMessage('Hi there!', null, 5);

        $this->assertCount(3, $conversation->messages);

        $this->assertDatabaseHas('nativerag_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'system',
            'content' => 'You are helpful.',
        ]);

        $this->assertDatabaseHas('nativerag_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello!',
        ]);

        $this->assertDatabaseHas('nativerag_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Hi there!',
            'tokens' => 5,
        ]);
    }

    public function test_messages_for_chat_returns_correct_format(): void
    {
        $conversation = NativeRagConversation::create();
        $conversation->addSystemMessage('System instructions.');
        $conversation->addUserMessage('Query.');

        $expected = [
            ['role' => 'system', 'content' => 'System instructions.'],
            ['role' => 'user', 'content' => 'Query.'],
        ];

        $this->assertSame($expected, $conversation->messagesForChat());
    }

    public function test_pruning_count_strategy(): void
    {
        config(['nativerag.conversations.pruning_strategy' => 'count']);
        config(['nativerag.conversations.max_history_count' => 3]);

        $conversation = NativeRagConversation::create();

        $conversation->addMessage('user', 'Msg 1');
        $conversation->addMessage('user', 'Msg 2');
        $conversation->addMessage('user', 'Msg 3');
        $conversation->addMessage('user', 'Msg 4');

        $messages = $conversation->messages()->oldest()->get();

        $this->assertCount(3, $messages);
        $this->assertSame('Msg 2', $messages[0]->content);
        $this->assertSame('Msg 3', $messages[1]->content);
        $this->assertSame('Msg 4', $messages[2]->content);
    }

    public function test_pruning_token_strategy_via_heuristic(): void
    {
        config(['nativerag.conversations.pruning_strategy' => 'token']);
        // Approximate threshold in tokens (1 token ~ 4 characters).
        // 40 characters => ~10 tokens.
        config(['nativerag.conversations.max_tokens_threshold' => 10]);

        $conversation = NativeRagConversation::create();

        // 12 chars = ~3 tokens
        $conversation->addMessage('user', 'Hello world!');
        // 12 chars = ~3 tokens (total ~6 tokens)
        $conversation->addMessage('assistant', 'How are you?');
        // 40 chars = ~10 tokens (total ~16 tokens, exceeds 10)
        $conversation->addMessage('user', 'I am doing exceptionally well today, thank you.');

        $messages = $conversation->messages()->oldest()->get();

        // Should prune oldest messages.
        // The last message is 10 tokens. The previous message is 3 tokens.
        // Summing from latest:
        // 'I am doing...' = 10 tokens.
        // 'How are you?' = 3 tokens (10 + 3 = 13 > 10, so it should prune this and Hello world)
        // Wait, since 'I am doing...' is 10 tokens, and we allow keeping the latest message.
        $this->assertCount(1, $messages);
        $this->assertSame('I am doing exceptionally well today, thank you.', $messages[0]->content);
    }

    public function test_pruning_token_strategy_via_tokens_column(): void
    {
        config(['nativerag.conversations.pruning_strategy' => 'token']);
        config(['nativerag.conversations.max_tokens_threshold' => 15]);

        $conversation = NativeRagConversation::create();

        $conversation->addMessage('user', 'Message 1', null, 8);
        $conversation->addMessage('assistant', 'Message 2', null, 5); // total 13
        $conversation->addMessage('user', 'Message 3', null, 6); // total 11 (Message 3 + Message 2 = 11 <= 15; Message 1 pruned)

        $messages = $conversation->messages()->oldest()->get();

        $this->assertCount(2, $messages);
        $this->assertSame('Message 2', $messages[0]->content);
        $this->assertSame('Message 3', $messages[1]->content);
    }

    public function test_ask_method_communicates_with_driver_and_stores_response(): void
    {
        $conversation = NativeRagConversation::create();
        $conversation->addSystemMessage('System prompt');

        $mockResponse = new ChatResponse(
            content: 'This is the mocked response.',
            role: 'assistant',
            raw: [],
            promptTokens: 10,
            completionTokens: 20
        );

        // Mock NativeRag Facade driver
        $mockDriver = \Mockery::mock(ChatEngineContract::class);
        $mockDriver->shouldReceive('chat')
            ->once()
            ->with([
                ['role' => 'system', 'content' => 'System prompt'],
                ['role' => 'user', 'content' => 'Hello artificial intelligence!'],
            ], [])
            ->andReturn($mockResponse);

        NativeRag::shouldReceive('driver')
            ->once()
            ->with(null)
            ->andReturn($mockDriver);

        $assistantMessage = $conversation->ask('Hello artificial intelligence!');

        $this->assertSame('This is the mocked response.', $assistantMessage->content);
        $this->assertSame(20, $assistantMessage->tokens);

        $this->assertDatabaseHas('nativerag_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello artificial intelligence!',
        ]);

        $this->assertDatabaseHas('nativerag_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'This is the mocked response.',
            'tokens' => 20,
        ]);
    }
}
