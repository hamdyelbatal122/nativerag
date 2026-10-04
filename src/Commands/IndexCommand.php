<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Commands;

use Hamzi\NativeRag\Contracts\EmbeddableContract;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class IndexCommand extends Command
{
    protected $signature = 'nativerag:index
                            {model : The class name of the Eloquent model to index}
                            {--chunk=100 : Number of records to process per batch}
                            {--force : Re-index records even if content hash has not changed}';

    protected $description = 'Index all records for an embeddable Eloquent model';

    public function handle(): int
    {
        $modelClass = (string) $this->argument('model');

        if (! class_exists($modelClass)) {
            $this->error("Class [{$modelClass}] does not exist.");

            return self::FAILURE;
        }

        if (! is_subclass_of($modelClass, Model::class) || ! is_subclass_of($modelClass, EmbeddableContract::class)) {
            $this->error("Class [{$modelClass}] must be an Eloquent Model implementing EmbeddableContract.");

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $chunkSize = max(1, (int) $this->option('chunk'));

        /** @var Model&EmbeddableContract $instance */
        $instance = new $modelClass;
        $totalRecords = $instance->newQuery()->count();

        if ($totalRecords === 0) {
            $this->warn("No records found for [{$modelClass}].");

            return self::SUCCESS;
        }

        $this->info("Indexing {$totalRecords} records for [{$modelClass}]...");
        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $processed = 0;

        $instance->newQuery()->chunkById($chunkSize, function ($records) use ($bar, $force, &$processed) {
            foreach ($records as $record) {
                /** @var EmbeddableContract $record */
                $record->syncEmbeddings($force);
                $processed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully processed {$processed} records for [{$modelClass}].");

        return self::SUCCESS;
    }
}
