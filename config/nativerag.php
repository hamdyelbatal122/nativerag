<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI driver that will be used for
    | chatting and generating completions. You can change this dynamically
    | at runtime or set it here.
    |
    | Supported: "ollama", "lmstudio"
    |
    */
    'default' => env('NATIVE_RAG_DRIVER', 'ollama'),

    /*
    |--------------------------------------------------------------------------
    | LLM and Embedding Drivers
    |--------------------------------------------------------------------------
    |
    | Here you can configure the settings for each driver.
    | Ollama and LM Studio run locally, providing complete privacy.
    |
    */
    'drivers' => [
        'ollama' => [
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'model' => env('OLLAMA_CHAT_MODEL', 'llama3'),
            'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
            'timeout' => (int) env('OLLAMA_TIMEOUT', 60),
            'retry_attempts' => (int) env('OLLAMA_RETRY_ATTEMPTS', 3),
            'retry_sleep_ms' => (int) env('OLLAMA_RETRY_SLEEP_MS', 1000),
            'options' => [
                'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.7),
                'top_p' => (float) env('OLLAMA_TOP_P', 0.9),
                'num_ctx' => (int) env('OLLAMA_CONTEXT_WINDOW', 4096),
            ],
        ],

        'lmstudio' => [
            'base_url' => env('LMSTUDIO_BASE_URL', 'http://localhost:1234'),
            'model' => env('LMSTUDIO_CHAT_MODEL', 'meta-llama-3-8b-instruct'),
            // LM Studio doesn't have a distinct embedding endpoint structure,
            // but can support embedding if using compatible models.
            'embedding_model' => env('LMSTUDIO_EMBEDDING_MODEL', 'nomic-embed-text'),
            'timeout' => (int) env('LMSTUDIO_TIMEOUT', 60),
            'retry_attempts' => (int) env('LMSTUDIO_RETRY_ATTEMPTS', 3),
            'retry_sleep_ms' => (int) env('LMSTUDIO_RETRY_SLEEP_MS', 1000),
            'options' => [
                'temperature' => (float) env('LMSTUDIO_TEMPERATURE', 0.7),
                'top_p' => (float) env('LMSTUDIO_TOP_P', 0.9),
                'max_tokens' => (int) env('LMSTUDIO_MAX_TOKENS', -1),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Vector Search & Embedding Storage Options
    |--------------------------------------------------------------------------
    |
    | Configuration for "Zero-Infra" RAG text chunking and similarity lookups.
    |
    */
    'embeddings' => [
        'table_name' => 'nativerag_embeddings',
        'connection' => env('NATIVE_RAG_DB_CONNECTION'), // null uses default application connection
        'chunk_size' => (int) env('NATIVE_RAG_CHUNK_SIZE', 1000), // in characters
        'chunk_overlap' => (int) env('NATIVE_RAG_CHUNK_OVERLAP', 200), // in characters

        // Strategy for cosine similarity search: "database" or "collection"
        // - "database": Direct raw SQL calculations (highly optimized for MySQL, PostgreSQL, SQLite).
        // - "collection": Falls back to PHP-side array calculations (best for dynamic custom schemas).
        'search_strategy' => env('NATIVE_RAG_SEARCH_STRATEGY', 'database'),

        // Threshold matching score for retrieval [0.0 to 1.0]
        'min_score' => (float) env('NATIVE_RAG_MIN_SCORE', 0.35),
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory & Chat Persistence Options
    |--------------------------------------------------------------------------
    |
    | Configurations for native conversation session storage, auto-trimming,
    | and sliding window sizes.
    |
    */
    'conversations' => [
        'table_conversations' => 'nativerag_conversations',
        'table_messages' => 'nativerag_messages',

        // Maximum messages preserved in database active sliding window context
        'max_history_count' => (int) env('NATIVE_RAG_MAX_HISTORY_COUNT', 10),

        // Strategy: "count" (limit by last N messages) or "token" (approximate by character/token count)
        'pruning_strategy' => env('NATIVE_RAG_PRUNING_STRATEGY', 'count'),

        // Limit context if using token pruning strategy
        'max_tokens_threshold' => (int) env('NATIVE_RAG_MAX_TOKENS_THRESHOLD', 4096),

        // Encrypt message contents in the database for extra security/privacy
        'encrypt_payloads' => (bool) env('NATIVE_RAG_ENCRYPT_PAYLOADS', false),
    ],
];
