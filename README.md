<p align="center">
    <h1 align="center">Laravel NativeRAG 🧠</h1>
</p>

<p align="center">
    <strong>A world-class, production-ready Local AI & RAG Engine for Laravel 11+</strong>
</p>

<p align="center">
    <a href="https://packagist.org/packages/hamzi/nativerag"><img src="https://img.shields.io/packagist/v/hamzi/nativerag.svg?style=flat-square&color=blue" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/hamzi/nativerag/actions"><img src="https://img.shields.io/github/actions/workflow/status/hamzi/nativerag/run-tests.yml?branch=main&label=tests&style=flat-square" alt="GitHub Tests Action Status"></a>
    <a href="https://packagist.org/packages/hamzi/nativerag"><img src="https://img.shields.io/packagist/dt/hamzi/nativerag.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/hamzi/nativerag"><img src="https://img.shields.io/packagist/php-v/hamzi/nativerag.svg?style=flat-square" alt="PHP Version Requirement"></a>
    <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-success.svg?style=flat-square" alt="License"></a>
</p>

---

Laravel NativeRAG empowers developers to run **localized, privacy-first AI workflows** using models hosted in [Ollama](https://ollama.com/) or [LM Studio](https://lmstudio.ai/). 

Built specifically for the Laravel ecosystem, it guarantees **100% data residency**, **zero external cloud dependencies** (no OpenAI API keys needed), and a seamless, highly fluent Developer Experience (DX).

## ✨ Features at a Glance

- 🤖 **Multi-Driver LLM Support:** Switch between Ollama and LM Studio endpoints instantly using Laravel's fluent gateway Manager pattern.
- 🗄️ **"Zero-Infra" Vector Search:** Eliminate the need for costly external vector databases like Pinecone. Utilizes highly optimized Cosine Similarity matrix calculations directly in PHP memory, with raw SQL query fallback capabilities optimized for MySQL, PostgreSQL (`pgvector`), or SQLite.
- ⚡ **Native SSE Streaming Layer:** Implements PSR-compliant chunked transfer encoding (`StreamedResponse`), allowing flawless real-time reactive frontend streaming via Alpine.js or Livewire.
- 🧩 **Automated Text Chunking & Hashing:** Attach the `Embeddable` trait to any Eloquent model. The package automatically listens to model saves, breaks documents down into overlapping context chunks, and calculates MD5 hashes to prevent redundant API calls.
- 🧠 **Persistent AI Memory:** Out-of-the-box SQL tables for multi-turn chat persistence. Includes intelligent sliding-window message pruning and payload encryption to secure sensitive metadata.
- 🛡️ **Enterprise Security & Architecture:** Engineered strictly with PHP 8.2+ (`declare(strict_types=1)`), constructor promotion, Readonly DTOs, and Laravel 11.x best practices.

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require hamzi/nativerag
```

Publish the configuration file and database migrations:

```bash
php artisan vendor:publish --tag="nativerag-config"
php artisan vendor:publish --tag="nativerag-migrations"
```

Run the migrations to create the RAG embeddings and conversations tables:

```bash
php artisan migrate
```

---

## 🛠️ Configuration

After publishing, examine the `config/nativerag.php` file. You can easily modify your default driver, endpoints, and timeouts using environment variables in your `.env`:

```env
NATIVE_RAG_DRIVER=ollama
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_CHAT_MODEL=llama3
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
NATIVE_RAG_CHUNK_SIZE=1000
NATIVE_RAG_CHUNK_OVERLAP=200
```

---

## 📖 Usage Guide

### 1. Simple AI Completions

Interact directly with your local LLM driver using the elegant Facade. All responses are returned as a strongly-typed `ChatResponse` DTO for perfect IDE autocomplete.

```php
use Hamzi\NativeRag\Facades\NativeRag;

$response = NativeRag::chat([
    ['role' => 'system', 'content' => 'You are a senior PHP developer.'],
    ['role' => 'user', 'content' => 'Explain Laravel service containers in one sentence.']
]);

echo $response->content; 
```

### 2. Reactive SSE Streaming (Real-Time Generation)

Send tokens to the browser in real-time as your local GPU/CPU generates them. Perfect for creating ChatGPT-like typing effects natively.

```php
use Hamzi\NativeRag\Facades\NativeRag;
use Illuminate\Support\Facades\Route;

Route::post('/api/ai/stream', function () {
    return NativeRag::stream([
        ['role' => 'user', 'content' => 'Write a comprehensive guide about Eloquent.']
    ]);
});
```

### 3. "Zero-Infra" Model Embeddings

Turn any Laravel Model into a searchable AI Vector Document by simply attaching the `Embeddable` trait. The package will automatically chunk the text and calculate local embeddings in the background when the model is saved.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Hamzi\NativeRag\Traits\Embeddable;

class Article extends Model
{
    use Embeddable;
    
    /**
     * Define the specific text payload you want the AI to index.
     */
    public function toEmbeddableString(): string
    {
        return "Title: {$this->title}\n\nContent: {$this->content}";
    }
}
```

### 4. Vector Similarity Semantic Search

Query your native database using mathematical semantic search directly from PHP.

```php
use Hamzi\NativeRag\Services\VectorSearchEngine;
use Hamzi\NativeRag\Facades\NativeRag;

// 1. Convert the user's plain-text question into a mathematical vector array
$questionVector = NativeRag::embedding()->embed("How does quantum physics work?");

// 2. Scan your database natively
$engine = new VectorSearchEngine();
$closestChunks = $engine->search($questionVector, limit: 3, minScore: 0.50);

foreach ($closestChunks as $chunk) {
    echo $chunk->chunk_content; // The raw text section that matched the query
    echo $chunk->similarity; // The exact cosine similarity score (e.g. 0.8921)
}
```

---

## 🔒 Security & Privacy

- **100% Privacy-First:** All API queries execute strictly against your local or private instance via LM Studio or Ollama. Zero external transmissions.
- **Payload Encryption:** If enabled in `config/nativerag.php` (`'encrypt_payloads' => true`), all chat histories and metadata stored in the database will be cryptographically encrypted using Laravel's native App Key.
- **SQL Injection Prevention:** Utilizes strict Laravel query builders and parameterized bindings.

---

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 🛡️ Security Vulnerabilities

Please review our [SECURITY.md](SECURITY.md) policy on how to report security vulnerabilities securely.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---
<p align="center">
  <b>Engineered with ❤️ by Hamzi</b>
</p>
