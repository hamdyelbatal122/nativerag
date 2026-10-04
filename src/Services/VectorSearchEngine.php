<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Services;

use Hamzi\NativeRag\Models\NativeRagEmbedding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VectorSearchEngine
{
    /** @var array<int, bool> */
    protected static array $registeredPdoIds = [];

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
     * Compatible across any database driver (SQLite, MySQL, SQL Server) without special extensions.
     *
     * @param  array<float>  $queryEmbedding
     * @return Collection<int, NativeRagEmbedding>
     */
    protected function searchViaCollection(array $queryEmbedding, int $limit, float $minScore): Collection
    {
        $results = collect();

        foreach (NativeRagEmbedding::query()->cursor() as $record) {
            $recordEmbedding = $record->embedding;

            if (empty($recordEmbedding)) {
                continue;
            }

            $score = $this->cosineSimilarity($queryEmbedding, $recordEmbedding);

            if ($score >= $minScore) {
                $record->setAttribute('similarity', $score);
                $results->push($record);
            }
        }

        return $results->sortByDesc('similarity')->take($limit)->values();
    }

    /**
     * Database queries mapping cosine similarity math to SQL.
     * Uses pgvector on PostgreSQL and custom PDO functions on SQLite,
     * falling back to PHP collection calculation if needed.
     *
     * @param  array<float>  $queryEmbedding
     * @return Collection<int, NativeRagEmbedding>
     */
    protected function searchViaDatabase(array $queryEmbedding, int $limit, float $minScore): Collection
    {
        $connection = DB::connection(config('nativerag.embeddings.connection'));
        $driver = $connection->getDriverName();

        if ($driver === 'pgsql') {
            $vectorStr = '['.implode(',', $queryEmbedding).']';

            try {
                /** @var Collection<int, NativeRagEmbedding> $results */
                $results = NativeRagEmbedding::query()
                    ->selectRaw('*, 1 - (embedding <=> ?) as similarity', [$vectorStr])
                    ->whereRaw('1 - (embedding <=> ?) >= ?', [$vectorStr, $minScore])
                    ->orderByDesc('similarity')
                    ->limit($limit)
                    ->get();

                return $results;
            } catch (\Throwable $e) {
                return $this->searchViaCollection($queryEmbedding, $limit, $minScore);
            }
        }

        if ($driver === 'sqlite') {
            try {
                $pdo = $connection->getPdo();
                $pdoId = spl_object_id($pdo);

                if (! isset(self::$registeredPdoIds[$pdoId])) {
                    $pdo->sqliteCreateFunction('cosine_similarity', function ($a, $b) {
                        $vecA = json_decode((string) $a, true);
                        $vecB = json_decode((string) $b, true);

                        if (! is_array($vecA) || ! is_array($vecB)) {
                            return 0.0;
                        }

                        return $this->cosineSimilarity($vecA, $vecB);
                    }, 2);
                    self::$registeredPdoIds[$pdoId] = true;
                }

                /** @var Collection<int, NativeRagEmbedding> $results */
                $results = NativeRagEmbedding::query()
                    ->selectRaw('*, cosine_similarity(embedding, ?) as similarity', [json_encode($queryEmbedding)])
                    ->having('similarity', '>=', $minScore)
                    ->orderByDesc('similarity')
                    ->limit($limit)
                    ->get();

                return $results;
            } catch (\Throwable $e) {
                return $this->searchViaCollection($queryEmbedding, $limit, $minScore);
            }
        }

        return $this->searchViaCollection($queryEmbedding, $limit, $minScore);
    }

    /**
     * Compute Cosine Similarity between two arrays of floats.
     * Returns a score between -1.0 and 1.0 (1.0 meaning exact match).
     *
     * @param  array<float>  $a
     * @param  array<float>  $b
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        $count = min(count($a), count($b));
        if ($count === 0) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $valA = (float) $a[$i];
            $valB = (float) $b[$i];

            $dotProduct += $valA * $valB;
            $normA += $valA ** 2;
            $normB += $valB ** 2;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Perform hybrid search combining vector similarity and keyword search via Reciprocal Rank Fusion (RRF).
     *
     * @param  array<float>  $queryEmbedding
     * @return Collection<int, NativeRagEmbedding>
     */
    public function searchHybrid(array $queryEmbedding, string $queryText, int $limit = 5, ?float $minScore = null, int $rrfK = 60): Collection
    {
        $candidateLimit = max($limit * 3, 20);
        $effectiveMinScore = $minScore ?? 0.0;

        $vectorResults = $this->search($queryEmbedding, $candidateLimit, $effectiveMinScore);

        $terms = array_filter(
            preg_split('/\s+/', trim($queryText)) ?: [],
            fn ($term) => mb_strlen($term, 'UTF-8') >= 2
        );

        /** @var \Illuminate\Database\Eloquent\Collection<int, NativeRagEmbedding> $keywordResults */
        $keywordResults = new \Illuminate\Database\Eloquent\Collection;
        if (! empty($terms)) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, NativeRagEmbedding> $keywordResults */
            $keywordResults = NativeRagEmbedding::query()
                ->where(function ($query) use ($terms) {
                    foreach ($terms as $term) {
                        $query->orWhere('chunk_content', 'like', '%'.$term.'%');
                    }
                })
                ->limit($candidateLimit)
                ->get();
        }

        $scores = [];
        $records = [];

        foreach ($vectorResults as $rank => $record) {
            $id = $record->id;
            $records[$id] = $record;
            $scores[$id] = ($scores[$id] ?? 0.0) + (1.0 / ($rrfK + $rank + 1));
        }

        foreach ($keywordResults as $rank => $record) {
            $id = $record->id;
            if (! isset($records[$id])) {
                $records[$id] = $record;
                $record->setAttribute('similarity', $this->cosineSimilarity($queryEmbedding, $record->embedding));
            }
            $scores[$id] = ($scores[$id] ?? 0.0) + (1.0 / ($rrfK + $rank + 1));
        }

        return collect($records)
            ->map(function ($record) use ($scores) {
                $record->setAttribute('hybrid_score', $scores[$record->id] ?? 0.0);

                return $record;
            })
            ->sortByDesc('hybrid_score')
            ->take($limit)
            ->values();
    }
}
