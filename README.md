<p align="center">
  <h1 align="center">🧠 Laravel NativeRAG</h1>
  <p align="center">A world-class, production-ready Local AI & RAG Engine for Laravel 11, 12 & 13</p>
</p>

<p align="center">
  <a href="https://packagist.org/packages/hamzi/nativerag"><img src="https://img.shields.io/packagist/v/hamzi/nativerag.svg?style=flat-square&color=4f46e5" alt="Latest Version"></a>
  <a href="https://github.com/hamdyelbatal122/nativerag/actions"><img src="https://img.shields.io/github/actions/workflow/status/hamdyelbatal122/nativerag/run-tests.yml?branch=master&label=tests&style=flat-square" alt="Tests Status"></a>
  <a href="https://github.com/hamdyelbatal122/nativerag/actions"><img src="https://img.shields.io/github/actions/workflow/status/hamdyelbatal122/nativerag/run-tests.yml?branch=master&label=pint&style=flat-square&color=22c55e" alt="Code Style"></a>
  <a href="https://packagist.org/packages/hamzi/nativerag"><img src="https://img.shields.io/packagist/dt/hamzi/nativerag.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/hamzi/nativerag"><img src="https://img.shields.io/packagist/php-v/hamzi/nativerag.svg?style=flat-square" alt="PHP Version"></a>
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-success.svg?style=flat-square" alt="License"></a>
</p>

---

**Laravel NativeRAG** empowers you to run fully **localized, privacy-first AI workflows** using models hosted in [Ollama](https://ollama.com/) or [LM Studio](https://lmstudio.ai/) — directly from your Laravel application.

No OpenAI keys. No Pinecone. No cloud data leaks. **100% data residency. Zero external dependencies.**

---

## ✨ Features

| Feature | Details |
|---|---|
| 🤖 **Multi-Driver LLM** | Switch between Ollama & LM Studio via Laravel's Manager pattern |
| 🗄️ **Zero-Infra Vector Search** | Cosine Similarity powered by PHP + native SQL. No Pinecone needed |
| ⚡ **SSE Streaming** | Real-time token streaming to Alpine.js / Livewire frontends |
| 🧩 **Auto-Embedding Trait** | Add `Embeddable` to any Eloquent model for automatic vector indexing |
| 🧠 **Persistent Memory** | Multi-turn chat history with sliding-window pruning |
| 🔒 **Payload Encryption** | Encrypt stored chat history using Laravel's App Key |
| 🛡️ **Strict Types** | PHP 8.2+ with `declare(strict_types=1)`, Readonly DTOs, Enums |
| 🐘 **pgvector Support** | Native PostgreSQL pgvector cosine distance queries |

---

## ✅ Compatibility

| Laravel | PHP | Status |
|---------|-----|--------|
| 13.x | 8.2, 8.3 | ✅ Fully Supported |
| 12.x | 8.2, 8.3 | ✅ Fully Supported |
| 11.x | 8.2, 8.3 | ✅ Fully Supported |

---

## 🚀 Installation

```bash
composer require hamzi/nativerag
```

Publish configuration and migrations:

```bash
php artisan vendor:publish --tag="nativerag-config"
php artisan vendor:publish --tag="nativerag-migrations"
php artisan migrate
```

---

## 🛠️ Configuration

Set your driver settings in `.env`:

```env
NATIVE_RAG_DRIVER=ollama

# Ollama
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_CHAT_MODEL=llama3
OLLAMA_EMBEDDING_MODEL=nomic-embed-text

# LM Studio
LMSTUDIO_BASE_URL=http://localhost:1234
LMSTUDIO_CHAT_MODEL=meta-llama-3-8b-instruct

# Chunking & Retrieval
NATIVE_RAG_CHUNK_SIZE=1000
NATIVE_RAG_CHUNK_OVERLAP=200
NATIVE_RAG_MIN_SCORE=0.35

# Security
NATIVE_RAG_ENCRYPT_PAYLOADS=false
```

---

## 📖 Usage

### 1. Chat Completions

```php
use Hamzi\NativeRag\Facades\NativeRag;

$response = NativeRag::chat([
    ['role' => 'system', 'content' => 'You are a senior Laravel engineer.'],
    ['role' => 'user',   'content' => 'Explain service containers briefly.'],
]);

echo $response->content;        // The generated text
echo $response->promptTokens;   // Input tokens used
echo $response->completionTokens; // Output tokens generated
```

### 2. Real-Time SSE Streaming

```php
use Hamzi\NativeRag\Facades\NativeRag;
use Illuminate\Support\Facades\Route;

Route::post('/api/ai/stream', function () {
    return NativeRag::stream([
        ['role' => 'user', 'content' => 'Write a comprehensive guide on Eloquent ORM.'],
    ]);
});
```

**Consume in JavaScript (Alpine.js / Vanilla):**

```js
const source = new EventSource('/api/ai/stream');
source.onmessage = ({ data }) => {
    const { content, done } = JSON.parse(data);
    if (done) { source.close(); return; }
    document.querySelector('#output').insertAdjacentText('beforeend', content);
};
```

### 3. Embeddable Models (Auto-Indexing)

Attach the `Embeddable` trait to any Eloquent model. Whenever the model is saved, its content is automatically chunked and embedded locally.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Hamzi\NativeRag\Traits\Embeddable;

class Article extends Model
{
    use Embeddable;

    /**
     * Define the text payload the AI engine will index.
     */
    public function toEmbeddableString(): string
    {
        return "Title: {$this->title}\n\nContent: {$this->content}";
    }
}
```

### 4. Semantic Vector Search

```php
use Hamzi\NativeRag\Services\VectorSearchEngine;
use Hamzi\NativeRag\Facades\NativeRag;

// 1. Embed the user's question
$queryVector = NativeRag::embedding()->embed('How does quantum physics relate to computing?');

// 2. Search native database for closest chunks
$engine = new VectorSearchEngine();
$results = $engine->search($queryVector, limit: 5, minScore: 0.50);

foreach ($results as $chunk) {
    echo $chunk->chunk_content; // Matching text passage
    echo $chunk->similarity;    // Score: 0.0 – 1.0
}
```

### 5. Switch Driver at Runtime

```php
use Hamzi\NativeRag\Facades\NativeRag;

// Use LM Studio for this specific call
$response = NativeRag::driver('lmstudio')->chat([
    ['role' => 'user', 'content' => 'Summarize this document.'],
]);
```

---

## 🔒 Security & Privacy

- **100% On-Premise:** All inference runs against Ollama/LM Studio on your own hardware. Zero network calls leave your server.
- **Payload Encryption:** Enable `NATIVE_RAG_ENCRYPT_PAYLOADS=true` to encrypt all stored chat content and metadata using Laravel's native AES-256-CBC encryption.
- **SQL Injection Safe:** Strictly uses Laravel's parameterized query builder with no raw string interpolations.
- **Change Detection:** MD5 content hashing prevents re-embedding unchanged documents — eliminating redundant local API calls.

---

## 🧪 Testing & Code Quality

```bash
# Run tests
composer test

# Run code style fixer
composer lint

# Run static analysis (PHPStan Level 6)
composer analyse
```

---

## 🤝 Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## 🛡️ Security Vulnerabilities

Please review the [SECURITY.md](SECURITY.md) policy to learn how to responsibly report a vulnerability.

## 📄 License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

---

<p align="center">Engineered with ❤️ by <strong>Hamdy Elbatal</strong></p>
