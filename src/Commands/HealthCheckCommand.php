<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Commands;

use Hamzi\NativeRag\Facades\NativeRag;
use Hamzi\NativeRag\Models\NativeRagConversation;
use Hamzi\NativeRag\Models\NativeRagEmbedding;
use Hamzi\NativeRag\Models\NativeRagMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HealthCheckCommand extends Command
{
    protected $signature = 'nativerag:health';

    protected $description = 'Check connectivity to local AI drivers and database vector engine';

    public function handle(): int
    {
        $this->info('NativeRAG Health & Diagnostics');
        $this->newLine();

        $rows = [];

        // Configuration
        $defaultDriver = config('nativerag.default', 'ollama');
        $rows[] = ['Default Driver', $defaultDriver, 'OK'];

        // Driver connectivity
        try {
            $embeddingDriver = NativeRag::embedding();
            $testVector = $embeddingDriver->embed('healthcheck');
            $vectorDims = count($testVector);

            if ($vectorDims > 0) {
                $rows[] = ['Embedding Engine', "Connected ({$vectorDims} dimensions)", 'OK'];
            } else {
                $rows[] = ['Embedding Engine', 'Empty response vector', 'WARN'];
            }
        } catch (\Throwable $e) {
            $rows[] = ['Embedding Engine', 'Connection failed: '.$e->getMessage(), 'FAIL'];
        }

        // Database and search strategy
        $strategy = config('nativerag.embeddings.search_strategy', 'database');
        $dbConnection = DB::connection(config('nativerag.embeddings.connection'));
        $dbDriver = $dbConnection->getDriverName();

        $rows[] = ['Database Driver', "{$dbDriver} (Strategy: {$strategy})", 'OK'];

        // Table statistics
        try {
            $embeddingsCount = NativeRagEmbedding::query()->count();
            $conversationsCount = NativeRagConversation::query()->count();
            $messagesCount = NativeRagMessage::query()->count();

            $rows[] = ['Indexed Chunks', (string) $embeddingsCount, 'OK'];
            $rows[] = ['Stored Conversations', "{$conversationsCount} ({$messagesCount} messages)", 'OK'];
        } catch (\Throwable $e) {
            $rows[] = ['Database Records', 'Table check failed: '.$e->getMessage(), 'FAIL'];
        }

        // Queue processing
        $queueEnabled = config('nativerag.queue.enabled', false);
        $queueStatus = $queueEnabled ? 'Enabled' : 'Disabled (synchronous)';
        $rows[] = ['Queue Indexing', $queueStatus, 'OK'];

        $this->table(['Component', 'Details', 'Status'], $rows);

        $hasFailures = collect($rows)->contains(fn ($row) => $row[2] === 'FAIL');

        if ($hasFailures) {
            $this->error('One or more diagnostics reported an error.');

            return self::FAILURE;
        }

        $this->info('All diagnostics passed.');

        return self::SUCCESS;
    }
}
