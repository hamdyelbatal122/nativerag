<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NativeRagConversation extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('nativerag.conversations.table_conversations', 'nativerag_conversations');
    }

    /**
     * Use the casts() method (Laravel 11+) — do NOT combine with a $casts property.
     *
     * @return array<string, string>
     */
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

    /**
     * Prune old messages according to the configured strategy.
     */
    public function pruneHistory(): void
    {
        $strategy = config('nativerag.conversations.pruning_strategy', 'count');

        if ($strategy === 'count') {
            $maxHistory = (int) config('nativerag.conversations.max_history_count', 10);

            $idsToKeep = $this->messages()
                ->latest()
                ->limit($maxHistory)
                ->pluck('id');

            $this->messages()
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        }
    }
}
