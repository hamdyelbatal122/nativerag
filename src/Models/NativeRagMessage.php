<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NativeRagMessage extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('nativerag.conversations.table_messages', 'nativerag_messages');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        $casts = [
            'metadata' => 'array',
            'tokens' => 'integer',
        ];

        if (config('nativerag.conversations.encrypt_payloads', false)) {
            $casts['content'] = 'encrypted';
            $casts['metadata'] = 'encrypted:array';
        }

        return $casts;
    }

    /**
     * @return BelongsTo<NativeRagConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(NativeRagConversation::class, 'conversation_id');
    }
}
