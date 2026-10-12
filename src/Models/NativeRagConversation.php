<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Models;

use Hamzi\NativeRag\Services\ConversationService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string|null $name
 * @property array<string, mixed>|null $metadata
 */
class NativeRagConversation extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('nativerag.conversations.table_conversations', 'nativerag_conversations');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        if (config('nativerag.conversations.encrypt_payloads', false)) {
            return [
                'metadata' => 'encrypted:array',
            ];
        }

        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return HasMany<NativeRagMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(NativeRagMessage::class, 'conversation_id');
    }

    public function pruneHistory(): void
    {
        $strategy = config('nativerag.conversations.pruning_strategy', 'count');
        $preserveSystem = (bool) config('nativerag.conversations.preserve_system_messages', true);

        if ($strategy === 'count') {
            $maxHistory = (int) config('nativerag.conversations.max_history_count', 10);

            $query = $this->messages();
            if ($preserveSystem) {
                $query = $query->where('role', '!=', 'system');
            }

            /** @var array<int, string> $idsToKeep */
            $idsToKeep = $query->latest()
                ->limit($maxHistory)
                ->pluck('id')
                ->all();

            if ($preserveSystem) {
                $systemIds = $this->messages()
                    ->where('role', 'system')
                    ->pluck('id')
                    ->all();
                $idsToKeep = array_merge($idsToKeep, $systemIds);
            }

            $this->messages()
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        } elseif ($strategy === 'token') {
            $maxTokens = (int) config('nativerag.conversations.max_tokens_threshold', 4096);
            $totalTokens = 0;
            $idsToKeep = [];
            $keptNonSystemMessage = false;

            if ($preserveSystem) {
                $systemMessages = $this->messages()->where('role', 'system')->get();
                foreach ($systemMessages as $message) {
                    $idsToKeep[] = $message->id;
                    $totalTokens += $this->messageTokenCount($message);
                }
            }

            $messagesQuery = $this->messages();
            if ($preserveSystem) {
                $messagesQuery = $messagesQuery->where('role', '!=', 'system');
            }
            $messages = $messagesQuery->latest()->get();

            foreach ($messages as $message) {
                $messageTokens = $this->messageTokenCount($message);

                if (! $keptNonSystemMessage || ($totalTokens + $messageTokens) <= $maxTokens) {
                    $idsToKeep[] = $message->id;
                    $totalTokens += $messageTokens;
                    $keptNonSystemMessage = true;
                } else {
                    break;
                }
            }

            $this->messages()
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        }
    }

    protected function messageTokenCount(NativeRagMessage $message): int
    {
        return $message->tokens ?? (int) ceil(mb_strlen($message->content, 'UTF-8') / 4);
    }

    /** @param  array<string, mixed>|null  $metadata */
    public function addMessage(string $role, string $content, ?array $metadata = null, ?int $tokens = null, bool $prune = true): NativeRagMessage
    {
        /** @var NativeRagMessage $message */
        $message = $this->messages()->create([
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
            'tokens' => $tokens,
        ]);

        if ($prune) {
            $this->pruneHistory();
        }

        return $message;
    }

    /** @param  array<string, mixed>|null  $metadata */
    public function addSystemMessage(string $content, ?array $metadata = null): NativeRagMessage
    {
        return $this->addMessage('system', $content, $metadata);
    }

    /** @param  array<string, mixed>|null  $metadata */
    public function addUserMessage(string $content, ?array $metadata = null, bool $prune = true): NativeRagMessage
    {
        return $this->addMessage('user', $content, $metadata, null, $prune);
    }

    /** @param  array<string, mixed>|null  $metadata */
    public function addAssistantMessage(string $content, ?array $metadata = null, ?int $tokens = null, bool $prune = true): NativeRagMessage
    {
        return $this->addMessage('assistant', $content, $metadata, $tokens, $prune);
    }

    /**
     * Get the conversation history formatted for the chat drivers.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function messagesForChat(): array
    {
        return $this->messages()
            ->oldest()
            ->get(['role', 'content'])
            ->toArray();
    }

    /** @param  array<string, mixed>  $options */
    public function ask(string $userMessage, array $options = []): NativeRagMessage
    {
        return app(ConversationService::class)->ask($this, $userMessage, $options);
    }
}
