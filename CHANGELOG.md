# Changelog

All notable changes to `hamzi/nativerag` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of Laravel NativeRAG Engine.
- Multi-Driver LLM Support (Ollama & LM Studio).
- Zero-Infra Vector Search capabilities via math collections and database fallbacks.
- Reactive SSE Streaming Layer for real-time frontend integration.
- `Embeddable` Eloquent trait for automatic model text chunking and vector syncing.
- Multi-Turn Conversation Models (`NativeRagConversation` & `NativeRagMessage`) with sliding window limits and payload encryption.
