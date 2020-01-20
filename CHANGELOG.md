# Changelog

All notable changes to `hamzi/nativerag` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.3] - 2026-05-19

### Added
- Added official support for **PHP 8.5** (`^8.2|^8.5` in `composer.json`).
- Included **PHP 8.5** in the GitHub Actions CI matrix to automatically test against Laravel 11/12/13.
- Updated compatibility matrix in documentation.
- Added comprehensive unit tests for `PackageInstallTest`, `TextChunkerTest`, and `PromptCompilerTest` using Orchestra Testbench.

## [1.0.2] - 2026-05-19

### Fixed
- **[CRITICAL]** Removed conflicting `protected $casts = [...]` property from `NativeRagConversation` that clashed with the `casts()` method — caused unpredictable behavior in Laravel 12+ where `casts()` is the canonical approach.
- Fixed `Embeddable::bootEmbeddable()` callbacks to use `self` type hint instead of `Model`, ensuring correct static resolution and full PHPStan compatibility.
- Removed redundant double `count()` query in `syncEmbeddings()` — now uses a single `first()` check for hash comparison (2× fewer DB queries per save).
- Fixed generic type hints: `HasMany<NativeRagMessage, $this>`, `BelongsTo<NativeRagConversation, $this>`, `MorphMany<NativeRagEmbedding, $this>` for PHPStan Level 6 compliance.
- Cast `config()` return values to `int` in `pruneHistory()` and `syncEmbeddings()` to prevent type coercion warnings in strict mode.

### Added
- Full **PHP 8.4** support added to GitHub Actions CI matrix (now tests PHP 8.2, 8.3, 8.4 × Laravel 11/12/13).
- Updated compatibility table in `README.md` to reflect PHP 8.4 support.

## [1.0.1] - 2026-05-19

### Added
- Full support for **Laravel 12.x** and **Laravel 13.x** (in addition to Laravel 11.x).
- Added `phpstan/phpstan` (Level 6) for static type analysis.
- Added `laravel/pint` code style enforcement with `pint.json` preset.
- Added `phpunit.xml` configuration with in-memory SQLite test environment.
- Added `phpstan.neon` configuration for static analysis.
- Added `composer analyse` and `composer lint` scripts.
- Upgraded GitHub Actions CI matrix to test all combinations of PHP 8.2/8.3 × Laravel 11/12/13 × prefer-lowest/prefer-stable.
- Added separate CI jobs for Code Style (Pint) and Static Analysis (PHPStan).
- Added `keywords` and `homepage` fields to `composer.json` for Packagist discoverability.
- Added `orchestra/testbench ^10.0` and `phpunit/phpunit ^11.0` support in `require-dev`.
- Added JavaScript `EventSource` streaming example in README.
- Added runtime driver switching documentation in README.
- Added full compatibility table in README (Laravel 11/12/13 × PHP 8.2/8.3).

### Changed
- Updated `composer.json` description to reflect multi-version Laravel support.
- Updated `.gitattributes` to `export-ignore` new dev configuration files.
- Improved README structure with feature table, compatibility matrix, and testing section.

## [1.0.0] - 2026-05-19

### Added
- Initial release of **Laravel NativeRAG** engine.
- `NativeRagManager` extending Laravel `Manager` for multi-driver gateway support.
- `OllamaDriver`: Full chat completions, SSE streaming, and embedding generation via Ollama local API.
- `LmStudioDriver`: OpenAI-compatible chat completions and SSE streaming via LM Studio local API.
- `ChatEngineContract` and `EmbeddingEngineContract` strict interfaces.
- `ChatResponse` immutable readonly DTO for type-safe driver responses.
- `NativeRagStreamResponse` for PSR-compliant Server-Sent Events with aggressive buffer flushing.
- `VectorSearchEngine` with PHP Cosine Similarity matrix math and PostgreSQL pgvector fallback.
- `TextChunker` for overlapping context-preserving document chunking.
- `PromptCompiler` with `{{placeholder}}` substitution and RAG-specific prompt templates.
- `Embeddable` Eloquent trait for automatic model chunking and vector embedding sync on save.
- `NativeRagConversation` and `NativeRagMessage` Eloquent models with UUID primary keys.
- `NativeRagEmbedding` polymorphic Eloquent model with hash-based deduplication.
- Database migrations for `nativerag_conversations`, `nativerag_messages`, and `nativerag_embeddings`.
- `NativeRag` Facade for ergonomic static access.
- `NativeRagServiceProvider` with config/migration publishing.
- Full `config/nativerag.php` with env-driven driver, chunking, memory, and encryption options.
- `LICENSE.md`, `CONTRIBUTING.md`, `SECURITY.md`, `CHANGELOG.md`.
- GitHub Actions CI workflow.
- [2020-01-20]: refactor: optimize vector database embedding queries
