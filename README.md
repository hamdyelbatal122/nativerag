# Laravel NativeRAG 🧠

**A world-class, production-ready, and highly secure local AI controller and Retrieval-Augmented Generation (RAG) engine designed strictly for Laravel 11+.**

Laravel NativeRAG allows developers to run localized, privacy-first AI workflows via models hosted in **Ollama** or **LM Studio**, delivering **100% data residency** and **zero external cloud dependencies**, backed by an elegant and fluent Laravel native Developer Experience (DX).

---

## 🔥 Features

- **Multi-Driver LLM Support:** Seamlessly switch between local Ollama and LM Studio endpoints using Laravel's fluent gateway `Manager` pattern.
- **Zero-Infra Vector Search:** Eliminate the need for costly external vector databases like Pinecone. Uses highly optimized Cosine Similarity matrix calculations in pure PHP memory, with raw SQL query fallback capabilities optimized directly for MySQL, PostgreSQL (`pgvector`), or SQLite.
- **Native SSE Streaming Layer:** Implements PSR-compliant chunked transfer encoding `StreamedResponse`, perfect for reactive real-time frontend streaming via Alpine.js or Livewire without blocking or lagging.
- **Automated Text Chunking & Hashing:** Attach the `Embeddable` trait to any Eloquent model to automatically hook into Model Observers. Long documents are automatically broken down into overlapping context chunks, preventing token truncation while hashing content to completely eliminate redundant embedding API calls.
- **Persistent AI Memory:** Out-of-the-box automated SQL database tables for full Multi-Turn Chat persistence. Features token-aware, sliding-window message pruning and payload encryption to secure sensitive metadata.

---

## 📦 Installation

Since this package is local, ensure your repository has it wired up, then run:

```bash
composer require hamzi/nativerag
```

Publish the package configuration and zero-infra migrations:

```bash
php artisan vendor:publish --tag="nativerag-config"
php artisan vendor:publish --tag="nativerag-migrations"
php artisan migrate
```

---

## 🚀 Quick Start Guide

### 1. Simple Completions

Use the robust Facade to interact directly with your local LLM driver:

```php
use Hamzi\NativeRag\Facades\NativeRag;

$response = NativeRag::chat([
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'Explain quantum computing in one sentence.']
]);

echo $response->content; 
// The response is wrapped in a strongly-typed `ChatResponse` DTO for perfect IDE autocomplete.
```

### 2. Zero-Infra Model Embeddings

Simply attach the `Embeddable` trait to any Eloquent model. The package automatically listens to model saves, chunks the text, and calculates local embeddings via Ollama.

```php
use Illuminate\Database\Eloquent\Model;
use Hamzi\NativeRag\Traits\Embeddable;

class Article extends Model
{
    use Embeddable;
    
    // Tell NativeRAG what data to embed
    public function toEmbeddableString(): string
    {
        return $this->title . "\n\n" . $this->content;
    }
}
```

Now, searching your models via Vector Similarity is natively integrated into your PHP application:

```php
use Hamzi\NativeRag\Services\VectorSearchEngine;
use Hamzi\NativeRag\Facades\NativeRag;

// 1. Convert user's question to a vector
$questionVector = NativeRag::embedding()->embed("How does quantum physics work?");

// 2. Scan your database natively
$engine = new VectorSearchEngine();
$closestChunks = $engine->search($questionVector, limit: 3, minScore: 0.50);

foreach ($closestChunks as $chunk) {
    echo $chunk->chunk_content; // Raw text matching the query
    echo $chunk->similarity; // e.g. 0.8921 cosine similarity score
}
```

### 3. Reactive SSE Streaming

Send tokens to the browser in real-time as your local GPU/CPU generates them:

```php
use Hamzi\NativeRag\Facades\NativeRag;

Route::post('/api/stream', function () {
    return NativeRag::stream([
        ['role' => 'user', 'content' => 'Write a very long poem about Laravel.']
    ]);
});
```

---

## 🔒 Security & Performance

- **100% Privacy:** All queries execute locally against your GPU/CPU via LM Studio or Ollama. Zero data is transmitted to OpenAI, Anthropic, or external providers.
- **Encrypted Payloads:** Enable `'encrypt_payloads' => true` in `config/nativerag.php` to cryptographically encrypt chat histories stored in MySQL/PostgreSQL using Laravel's native App Key.
- **Strict Typing:** Built with PHP 8.2+ readonly DTOs, Enums, and Constructor Promotion under `declare(strict_types=1)`.

*Engineered by Hamzi.*
