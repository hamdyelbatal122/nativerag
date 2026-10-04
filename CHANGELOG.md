# Changelog

All notable changes to `hamzi/nativerag` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-10-04

### Added
- Added `NativeRag::searchHybrid()` combining cosine vector similarity and keyword search via Reciprocal Rank Fusion (RRF).
- Added `nativerag:health` Artisan command for diagnosing local AI driver connectivity, embedding dimensions, database statistics, and queue status.
- Added `nativerag:index {model}` Artisan command with progress bar reporting, chunk batching, and forced re-indexing (`--force`) capabilities.
- Added `SyncEmbeddingsJob` and `nativerag.queue` configuration to offload document chunking and vector generation to background queue workers.
- Added `preserve_system_messages` option in `config/nativerag.php` to protect system instructions from being dropped during sliding-window conversation pruning.
- Added payload encryption support (`encrypt_payloads`) for stored conversation messages and metadata.
- Expanded automated unit test suite to 45 tests covering all drivers, commands, hybrid search, and queued jobs.

### Fixed
- Fixed SQLite custom function lifecycle in `VectorSearchEngine` by dynamically tracking registered PDO instances (`spl_object_id`) to avoid missing function errors upon database reconnection.
- Hardened exception handling in `VectorSearchEngine` by catching `\Throwable` instead of `\Exception` to guarantee safe fallback to collection search.
- Added failed HTTP response validation (`throw()`) in `stream()` methods for both `OllamaDriver` and `LmStudioDriver` to surface network and server failures promptly.
- Fixed static `$latestResponse` compatibility property in base `TestCase` to maintain compatibility with `orchestra/testbench-core: v9.0.x` under `prefer-lowest`.

## [1.1.0] - 2026-05-31

### Added
- Added LM Studio embedding driver integration via `/v1/embeddings`.
- Added sliding-window memory pruning supporting both count-based and token-based strategies.
- Added `EmbeddableContract` interface for type-safe model indexing.
- Added connection abort detection during SSE streaming responses.
- Added configurable database connection for embeddings via `nativerag.embeddings.connection`.

### Changed
- Extracted `EmbeddingService` and `ConversationService` for clean architectural separation of concerns.
- Optimized embedding synchronization to use batch requests and minimize HTTP round-trips.

## [1.0.8] - 2026-05-20

### Added
- Added UTF-8 multi-byte string handling across `TextChunker` for reliable natural language segmentation.
- Added native SQLite PDO `cosine_similarity` function registration for zero-dependency local vector queries.

### Fixed
- Improved natural sentence and paragraph boundary detection during document chunking.

## [1.0.7] - 2026-05-19

### Changed
- Adjusted CI test matrix constraints to exclude PHP 8.2 with Laravel 13 to align with Laravel 13's PHP 8.3+ requirement.

## [1.0.6] - 2026-05-19

### Added
- Added `phpunit.xml.dist` to repository for reproducible local test environments.

### Fixed
- Stabilized Composer dependency constraints for multi-version CI test execution.

## [1.0.5] - 2026-05-19

### Added
- Added Orchestra Testbench `^11.0` support for Laravel 13 testing compatibility.
- Added `$latestResponse` compatibility shim in test base class for older Testbench v9 installations.

## [1.0.4] - 2026-05-19

### Changed
- Standardized docblock imports and type annotations across all classes.
- Formatted entire codebase to comply fully with Laravel Pint rules.

## [1.0.3] - 2026-05-19

### Added
- Added official support for PHP 8.5 (`^8.2|^8.5` in `composer.json`).
- Added PHP 8.5 to GitHub Actions CI matrix across Laravel 11, 12, and 13.
- Added automated unit test suite for package installation, text chunking, and prompt compilation.

## [1.0.2] - 2026-05-19

### Fixed
- Removed conflicting `protected $casts` property from `NativeRagConversation` to rely on the canonical `casts()` method.
- Corrected `Embeddable` boot callbacks to use `self` for accurate static resolution.
- Optimized `syncEmbeddings()` hash check to use a single database query.
- Fixed Eloquent relationship generic type hints for PHPStan compliance.

### Added
- Added PHP 8.4 support to GitHub Actions CI matrix.

## [1.0.1] - 2026-05-19

### Added
- Extended framework support to include Laravel 12.x and Laravel 13.x alongside Laravel 11.x.
- Added PHPStan static analysis configuration at Level 6.
- Added Laravel Pint code style preset and automated linting scripts.
- Added GitHub Actions CI matrix covering multi-version matrix runs.

## [1.0.0] - 2026-05-19

### Added
- Initial release of Laravel NativeRAG.
- Multi-driver LLM support for Ollama and LM Studio via Laravel's Manager pattern.
- Zero-infrastructure vector search with cosine similarity calculation.
- Real-time Server-Sent Events (SSE) streaming response handler.
- Embeddable Eloquent model trait with automatic text chunking and indexing on save.
- Persistent conversation and message models with UUID primary keys.
- Optional payload encryption for stored chat messages and metadata.
- Published configuration and database migration files.
- `NativeRag` Facade for static access.

[1.2.0]: https://github.com/hamdyelbatal122/nativerag/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/hamdyelbatal122/nativerag/compare/v1.0.8...v1.1.0
[1.0.8]: https://github.com/hamdyelbatal122/nativerag/compare/v1.0.7...v1.0.8
[1.0.7]: https://github.com/hamdyelbatal122/nativerag/compare/v1.0.6...v1.0.7
[1.0.6]: https://github.com/hamdyelbatal122/nativerag/compare/v1.0.5...v1.0.6
[1.0.5]: https://github.com/hamdyelbatal122/nativerag/compare/v1.0.4...v1.0.5
[1.0.4]: https://github.com/hamdyelbatal122/nativerag/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/hamdyelbatal122/nativerag/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/hamdyelbatal122/nativerag/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/hamdyelbatal122/nativerag/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/hamdyelbatal122/nativerag/releases/tag/v1.0.0
