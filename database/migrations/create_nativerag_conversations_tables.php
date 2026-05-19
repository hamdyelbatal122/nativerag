<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableConversations = config('nativerag.conversations.table_conversations', 'nativerag_conversations');
        $tableMessages = config('nativerag.conversations.table_messages', 'nativerag_messages');

        if (! Schema::hasTable($tableConversations)) {
            Schema::create($tableConversations, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($tableMessages)) {
            Schema::create($tableMessages, function (Blueprint $table) use ($tableConversations) {
                $table->uuid('id')->primary();
                $table->uuid('conversation_id');
                $table->string('role', 32); // system, user, assistant
                $table->longText('content');
                $table->unsignedInteger('tokens')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('conversation_id')
                    ->references('id')
                    ->on($tableConversations)
                    ->cascadeOnDelete();

                $table->index(['conversation_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableMessages = config('nativerag.conversations.table_messages', 'nativerag_messages');
        $tableConversations = config('nativerag.conversations.table_conversations', 'nativerag_conversations');

        Schema::dropIfExists($tableMessages);
        Schema::dropIfExists($tableConversations);
    }
};
