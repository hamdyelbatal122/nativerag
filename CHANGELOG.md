# Changelog

All notable changes to `hamzi/nativerag` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2026-10-04

### Added
- Added `NativeRag::search()` helper method to `NativeRagManager` and the `NativeRag` facade for direct semantic search with raw text or vector embeddings.
- Added `preserve_system_messages` option in `config/nativerag.php` to prevent system instructions from being pruned in long-running chats.
- Added unit tests for encrypted payloads (`encrypt_payloads`) and system message preservation during history pruning.

### Fixed
- Fixed SQLite custom function registration in `VectorSearchEngine` to track registered PDO instances dynamically instead of using a global static boolean, avoiding missing function errors upon database reconnection.
- Improved exception handling across `VectorSearchEngine` by catching `\Throwable` instead of `\Exception` to gracefully handle PDO driver errors and fall back to collection search.
- Added HTTP failure checks (`throw()`) in `stream()` methods for both `OllamaDriver` and `LmStudioDriver` to surface connection errors promptly.
- Added input validation in `TextChunker` for non-positive chunk size and invalid overlap parameters.
- Cleaned up unneeded debug artifacts and unused properties in test fixtures.

## [1.1.0] - 2026-05-20

### Added
- Support for LM Studio embeddings endpoint (`/v1/embeddings`).
- Flexible memory pruning strategies: `count` (sliding window) and `token` (token threshold with fallback character approximation).
- Batch embedding support in `EmbeddingService` to minimize HTTP round-trips when chunking large models.
- Direct SQLite PDO function integration (`cosine_similarity`) for zero-dependency local vector math.
- Configurable database connection for embeddings via `nativerag.embeddings.connection`.

### Changed
- Refactored embedding synchronization to use a dedicated `EmbeddingService` for cleaner architecture and separation of concerns.
- Streamlined chat orchestration through `ConversationService`.
- Enhanced static analysis coverage with PHPStan Level 6 and Pint styling.

## [1.0.3] - 2026-05-19

### Added
- Added official support for **PHP 8.5** (`^8.2|^8.5` in `composer.json`).
- Included **PHP 8.5** in the GitHub Actions CI matrix to test against Laravel 11/12/13.
- Added comprehensive unit tests for `PackageInstallTest`, `TextChunkerTest`, and `PromptCompilerTest` using Orchestra Testbench.

## [1.0.2] - 2026-05-19

### Fixed
- Removed conflicting `protected $casts = [...]` property from `NativeRagConversation` that clashed with the `casts()` method in Laravel 11+.
- Fixed `Embeddable::bootEmbeddable()` callbacks to use `self` type hint.
- Optimized `syncEmbeddings()` to use a single `first()` check for hash comparison.
- Fixed generic type hints: `HasMany<NativeRagMessage, $this>`, `BelongsTo<NativeRagConversation, $this>`, `MorphMany<NativeRagEmbedding, $this>` for PHPStan compliance.
- Cast `config()` return values to `int` in `pruneHistory()` and `syncEmbeddings()` to prevent type coercion warnings in strict mode.

### Added
- Full **PHP 8.4** support added to GitHub Actions CI matrix.

## [1.0.1] - 2026-05-19

### Added
- Full support for **Laravel 12.x** and **Laravel 13.x** alongside Laravel 11.x.
- Added `phpstan/phpstan` (Level 6) for static type analysis.
- Added `laravel/pint` code style enforcement with `pint.json` preset.
- Added `phpunit.xml` configuration with in-memory SQLite test environment.
- Added `composer analyse` and `composer lint` scripts.
- Added GitHub Actions CI matrix for multi-version testing.

## [1.0.0] - 2026-05-19

### Added
- Initial release of **Laravel NativeRAG** engine.
- `NativeRagManager` extending Laravel `Manager` for multi-driver gateway support.
- `OllamaDriver`: Chat completions, SSE streaming, and embedding generation via Ollama local API.
- `LmStudioDriver`: OpenAI-compatible chat completions and SSE streaming via LM Studio local API.
- `ChatEngineContract` and `EmbeddingEngineContract` strict interfaces.
- `ChatResponse` immutable readonly DTO for type-safe driver responses.
- `NativeRagStreamResponse` for PSR-compliant Server-Sent Events.
- `VectorSearchEngine` with cosine similarity math and PostgreSQL pgvector fallback.
- `TextChunker` for overlapping context-preserving document chunking.
- `PromptCompiler` with `{{placeholder}}` substitution and RAG prompt templates.
- `Embeddable` Eloquent trait for automatic model chunking and vector embedding sync on save.
- `NativeRagConversation` and `NativeRagMessage` Eloquent models with UUID primary keys.
- `NativeRagEmbedding` polymorphic Eloquent model with hash-based deduplication.
- Database migrations for conversations, messages, and embeddings.
- `NativeRag` Facade for static access.
