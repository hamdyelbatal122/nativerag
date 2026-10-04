<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Jobs;

use Hamzi\NativeRag\Contracts\EmbeddableContract;
use Hamzi\NativeRag\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncEmbeddingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  Model&EmbeddableContract  $model
     */
    public function __construct(
        public Model $model,
        public bool $force = false,
    ) {}

    public function handle(EmbeddingService $service): void
    {
        if (! $this->model->exists) {
            return;
        }

        $service->sync($this->model, $this->force);
    }
}
