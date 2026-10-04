<p align="center">
  <h1 align="center">Laravel NativeRAG</h1>
  <p align="center">Local AI and Retrieval-Augmented Generation (RAG) engine for Laravel 11, 12, and 13</p>
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

**NativeRAG** allows you to run localized, privacy-first AI workflows using models hosted in [Ollama](https://ollama.com/) or [LM Studio](https://lmstudio.ai/) directly from your Laravel application.

No external cloud API keys and no third-party vector databases. All inference and embeddings remain on your infrastructure.

---

## Features

- **Multi-Driver Support:** Switch between Ollama and LM Studio via Laravel's Manager pattern.
- **Embedded Vector Search:** Cosine similarity search powered by SQLite PDO custom functions, PostgreSQL pgvector, or PHP collection math.
- **SSE Streaming:** Real-time token streaming responses ready for Alpine.js, Livewire, or frontend clients.
- **Automatic Model Indexing:** `Embeddable` trait for Eloquent models with automatic chunking and hash-based deduplication on save.
- **Persistent Conversations:** Multi-turn chat persistence with sliding-window history pruning and system prompt preservation.
- **Encrypted Storage:** Optional AES-256 payload encryption for stored messages and metadata using your application key.
- **Type Safety:** PHP 8.2+ with strict types, readonly DTOs, and PHPStan Level 6 static analysis.

---

## Compatibility

| Laravel | PHP | Status |
|---|---|---|
| 13.x | 8.2, 8.3, 8.4, 8.5 | Supported |
| 12.x | 8.2, 8.3, 8.4, 8.5 | Supported |
| 11.x | 8.2, 8.3, 8.4, 8.5 | Supported |

---

## Installation

Install the package via Composer:

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

## Configuration

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
LMSTUDIO_EMBEDDING_MODEL=nomic-embed-text

# Chunking & Search
NATIVE_RAG_CHUNK_SIZE=1000
NATIVE_RAG_CHUNK_OVERLAP=200
NATIVE_RAG_MIN_SCORE=0.35

# Conversation Memory
NATIVE_RAG_MAX_HISTORY_COUNT=10
NATIVE_RAG_PRUNING_STRATEGY=count
NATIVE_RAG_PRESERVE_SYSTEM_MESSAGES=true

# Security
NATIVE_RAG_ENCRYPT_PAYLOADS=false
```

---

## Usage

### Chat Completions

```php
use Hamzi\NativeRag\Facades\NativeRag;

$response = NativeRag::chat([
    ['role' => 'system', 'content' => 'You are an experienced software engineer.'],
    ['role' => 'user',   'content' => 'Explain service containers briefly.'],
]);

echo $response->content;
echo $response->promptTokens;
echo $response->completionTokens;
```

### Server-Sent Events (SSE) Streaming

```php
use Hamzi\NativeRag\Facades\NativeRag;
use Illuminate\Support\Facades\Route;

Route::post('/api/ai/stream', function () {
    return NativeRag::stream([
        ['role' => 'user', 'content' => 'Write a short overview of Laravel Eloquent.'],
    ]);
});
```

Consume in JavaScript:

```js
const source = new EventSource('/api/ai/stream');

source.onmessage = ({ data }) => {
    const { content, done } = JSON.parse(data);
    if (done) {
        source.close();
        return;
    }
    document.querySelector('#output').insertAdjacentText('beforeend', content);
};
```

### Auto-Indexing Models

Implement `EmbeddableContract` and use the `Embeddable` trait on an Eloquent model:

```php
namespace App\Models;

use Hamzi\NativeRag\Contracts\EmbeddableContract;
use Hamzi\NativeRag\Traits\Embeddable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model implements EmbeddableContract
{
    use Embeddable;

    public function toEmbeddableString(): string
    {
        return "Title: {$this->title}\n\nContent: {$this->content}";
    }
}
```

When saved, the model's embeddable text is automatically chunked and synchronized. Unchanged records are skipped via MD5 hash comparison.

### Semantic Vector Search

Search indexed chunks with a text query or a raw vector array:

```php
use Hamzi\NativeRag\Facades\NativeRag;

// Search directly using a question string (embeds automatically)
$results = NativeRag::search('How does database indexing work?', limit: 5, minScore: 0.40);

foreach ($results as $chunk) {
    echo $chunk->chunk_content;
    echo $chunk->similarity;
}

// Or search with an existing embedding vector
$vector = NativeRag::embedding()->embed('Query text');
$results = NativeRag::search($vector, limit: 5);
```

### Multi-Turn Conversations

```php
use Hamzi\NativeRag\Models\NativeRagConversation;

$conversation = NativeRagConversation::create([
    'name' => 'Support Session #101',
]);

$conversation->addSystemMessage('You are a technical support representative.');

$response = $conversation->ask('How do I run database migrations?');
echo $response->content;

// The next message keeps the full conversation history
$followUp = $conversation->ask('Can I roll back the last batch?');
echo $followUp->content;
```

#### Memory Pruning
- `count` (default): Retains the latest N messages.
- `token`: Retains messages within a token threshold (`ceil(chars / 4)` approximation when token count is null).
- `preserve_system_messages`: System prompts are preserved from deletion by default.

### Driver Switching

```php
use Hamzi\NativeRag\Facades\NativeRag;

$response = NativeRag::driver('lmstudio')->chat([
    ['role' => 'user', 'content' => 'Summarize this file.'],
]);
```

---

## Security

- **Local Inference:** Queries never leave your server.
- **Database Encryption:** Enable `NATIVE_RAG_ENCRYPT_PAYLOADS=true` to encrypt stored message bodies and metadata via Laravel's encryption service.
- **Prepared Queries:** Built entirely on Laravel's query builder.

---

## Testing & Quality Checks

```bash
composer test
composer lint
composer analyse
```

---

## Contributing

Please review [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on code style, tests, and pull requests.

## Security Vulnerabilities

Please report security issues according to our policy in [SECURITY.md](SECURITY.md).

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.
