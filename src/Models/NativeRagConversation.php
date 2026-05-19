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

    protected $casts = [
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('nativerag.conversations.table_conversations', 'nativerag_conversations');
    }

    protected function casts(): array
    {
        $casts = [
            'metadata' => 'array',
        ];

        if (config('nativerag.conversations.encrypt_payloads', false)) {
            $casts['metadata'] = 'encrypted:array';
        }

        return $casts;
    }

    /**
     * @return HasMany<NativeRagMessage>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(NativeRagMessage::class, 'conversation_id');
    }

    /**
     * Prune old messages according to configured strategy.
     */
    public function pruneHistory(): void
    {
        $strategy = config('nativerag.conversations.pruning_strategy', 'count');

        if ($strategy === 'count') {
            $maxHistory = config('nativerag.conversations.max_history_count', 10);
            
            $messagesToKeep = $this->messages()
                ->latest()
                ->take($maxHistory)
                ->pluck('id');

            $this->messages()
                ->whereNotIn('id', $messagesToKeep)
                ->delete();
        }
    }
}
