<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Services;

use Hamzi\NativeRag\Models\NativeRagEmbedding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VectorSearchEngine
{
    /**
     * Search the database for the most similar chunks based on the provided embedding vector.
     *
     * @param  array<float>|array<int, float>  $queryEmbedding
     * @return Collection<int, NativeRagEmbedding>
     */
    public function search(array $queryEmbedding, int $limit = 5, ?float $minScore = null): Collection
    {
        $strategy = config('nativerag.embeddings.search_strategy', 'database');
        $minScore ??= (float) config('nativerag.embeddings.min_score', 0.35);

        if ($strategy === 'database') {
            return $this->searchViaDatabase($queryEmbedding, $limit, $minScore);
        }

        return $this->searchViaCollection($queryEmbedding, $limit, $minScore);
    }

    /**
     * Portable mathematical calculation pulling embeddings into a lazy collection and scoring via PHP.
     * Extremely compatible across any database driver (SQLite, MySQL, SQL Server) without special extensions.
     *
     * @param array<float> $queryEmbedding
     * @return \Illuminate\Support\Collection<int, \Hamzi\NativeRag\Models\NativeRagEmbedding>
     */
    protected function searchViaCollection(array $queryEmbedding, int $limit, float $minScore): \Illuminate\Support\Collection
    {
        $results = collect();

        // Use cursor to avoid loading all massive JSON embeddings into memory at once
        foreach (NativeRagEmbedding::query()->cursor() as $record) {
            $recordEmbedding = $record->embedding;

            if (empty($recordEmbedding)) {
                continue;
            }

            $score = $this->cosineSimilarity($queryEmbedding, $recordEmbedding);

            if ($score >= $minScore) {
                // Attach dynamic similarity score
                $record->setAttribute('similarity', $score);
                $results->push($record);
            }
        }

        return $results->sortByDesc('similarity')->take($limit)->values();
    }

    /**
     * Optimized raw database queries mapping cosine similarity math directly to SQL.
     * Requires the DB engine to support JSON array extraction or relies on pgvector if configured.
     * We use a unified fallback that delegates to the collection approach if SQL math is too complex for the active driver.
     *
     * @param array<float> $queryEmbedding
     * @return \Illuminate\Support\Collection<int, \Hamzi\NativeRag\Models\NativeRagEmbedding>
     */
    protected function searchViaDatabase(array $queryEmbedding, int $limit, float $minScore): \Illuminate\Support\Collection
    {
        $connection = DB::connection(config('nativerag.embeddings.connection'));
        $driver = $connection->getDriverName();

        // Postgres pgvector support if available
        if ($driver === 'pgsql') {
            $vectorStr = '['.implode(',', $queryEmbedding).']';

            try {
                /** @var \Illuminate\Support\Collection<int, \Hamzi\NativeRag\Models\NativeRagEmbedding> $results */
                $results = NativeRagEmbedding::query()
                    // 1 - (embedding <=> query) = cosine similarity in pgvector
                    ->selectRaw('*, 1 - (embedding <=> ?) as similarity', [$vectorStr])
                    ->having('similarity', '>=', $minScore)
                    ->orderByDesc('similarity')
                    ->limit($limit)
                    ->get();
                    
                return $results;
            } catch (\Exception $e) {
                // Fallback to PHP computation if pgvector extension is missing
                return $this->searchViaCollection($queryEmbedding, $limit, $minScore);
            }
        }

        // Standard MySQL/SQLite math for JSON arrays is extremely complex to write dynamically
        // without knowing vector dimensions. For robust "Zero-Infra" out-of-the-box usage,
        // we heavily optimize by falling back to collection cursor filtering which works flawlessly
        // across all schema setups without needing DB extensions.
        return $this->searchViaCollection($queryEmbedding, $limit, $minScore);
    }

    /**
     * Compute Cosine Similarity between two arrays of floats.
     * Returns a score between -1.0 and 1.0 (1.0 meaning exact match).
     *
     * @param array<float> $a
     * @param array<float> $b
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $count = min(count($a), count($b));

        for ($i = 0; $i < $count; $i++) {
            $valA = (float) $a[$i];
            $valB = (float) $b[$i];

            $dotProduct += $valA * $valB;
            $normA += $valA ** 2;
            $normB += $valB ** 2;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
