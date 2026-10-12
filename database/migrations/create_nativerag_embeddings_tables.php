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
        $tableName = config('nativerag.embeddings.table_name', 'nativerag_embeddings');

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('embeddable_type');
                $table->string('embeddable_id');
                $table->longText('chunk_content');
                $table->json('embedding');
                $table->string('hash', 64);
                $table->timestamps();

                $table->index(['embeddable_type', 'embeddable_id']);
                $table->index('hash');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('nativerag.embeddings.table_name', 'nativerag_embeddings');

        Schema::dropIfExists($tableName);
    }
};
