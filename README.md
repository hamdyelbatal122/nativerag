<p align="center">
  <h1 align="center">🧠 Laravel NativeRAG</h1>
  <p align="center">A privacy-first Local AI & Retrieval-Augmented Generation (RAG) engine for Laravel 11, 12, and 13</p>
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

**NativeRAG** allows you to run **localized, privacy-first AI workflows** using models hosted in [Ollama](https://ollama.com/) or [LM Studio](https://lmstudio.ai/) directly from your Laravel application.

No third-party cloud API keys. No external vector databases. **100% data residency with zero external infrastructure.**

---

## ✨ Features

| Feature | Details |
|---|---|
| 🤖 **Multi-Driver LLM** | Switch seamlessly between Ollama and LM Studio via Laravel's Manager pattern |
| 🗄️ **Zero-Infra Vector Search** | Built-in cosine similarity powered by SQLite PDO functions, PostgreSQL pgvector, or PHP collections |
| ⚡ **SSE Streaming** | Real-time token streaming responses ready for Alpine.js, Livewire, or vanilla JavaScript |
| 🧩 **Auto-Embedding Trait** | Add `Embeddable` to any Eloquent model for automatic chunking and vector indexing on save |
| 🧠 **Persistent Memory** | Multi-turn chat conversations with sliding-window history pruning and system prompt preservation |
| 🔒 **Payload Encryption** | Optional AES-256 encryption for stored chat messages and metadata using your Laravel application key |
| 🛡️ **Modern PHP & Strict Types** | Full PHP 8.2+ compatibility with `declare(strict_types=1)`, readonly DTOs, and static analysis (PHPStan Level 6) |

---

## ✅ Compatibility

| Laravel | PHP | Status |
|---------|-----|--------|
| 13.x | 8.2, 8.3, 8.4, 8.5 | ✅ Supported |
| 12.x | 8.2, 8.3, 8.4, 8.5 | ✅ Supported |
| 11.x | 8.2, 8.3, 8.4, 8.5 | ✅ Supported |

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require hamzi/nativerag
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag="nativerag-config"
php artisan vendor:publish --tag="nativerag-migrations"
php artisan migrate
```

---

## 🛠️ Configuration

Configure your local models and retrieval settings in `.env`:

```env
NATIVE_RAG_DRIVER=ollama

# Ollama Settings
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_CHAT_MODEL=llama3
OLLAMA_EMBEDDING_MODEL=nomic-embed-text

# LM Studio Settings
LMSTUDIO_BASE_URL=http://localhost:1234
LMSTUDIO_CHAT_MODEL=meta-llama-3-8b-instruct
LMSTUDIO_EMBEDDING_MODEL=nomic-embed-text

# Chunking & Retrieval
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

## 📖 Usage

### 1. Chat Completions

```php
use Hamzi\NativeRag\Facades\NativeRag;

$response = NativeRag::chat([
    ['role' => 'system', 'content' => 'You are a helpful software architect.'],
    ['role' => 'user',   'content' => 'Explain service containers simply.'],
]);

echo $response->content;          // Generated text
echo $response->promptTokens;     // Input tokens used
echo $response->completionTokens; // Output tokens generated
```

### 2. Real-Time SSE Streaming

```php
use Hamzi\NativeRag\Facades\NativeRag;
use Illuminate\Support\Facades\Route;

Route::post('/api/ai/stream', function () {
    return NativeRag::stream([
        ['role' => 'user', 'content' => 'Write a short overview of Laravel Eloquent.'],
    ]);
});
```

**Consume in JavaScript (EventSource / Fetch):**

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

### 3. Embeddable Models (Automatic Indexing)

Implement `EmbeddableContract` and use the `Embeddable` trait on any Eloquent model. When saved, the model's text is automatically chunked, embedded locally, and synchronized in the database. If unchanged, duplicate embeddings are skipped via MD5 hashing.

```php
namespace App\Models;

use Hamzi\NativeRag\Contracts\EmbeddableContract;
use Hamzi\NativeRag\Traits\Embeddable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model implements EmbeddableContract
{
    use Embeddable;

    /**
     * Define the text content to be indexed.
     */
    public function toEmbeddableString(): string
    {
        return "Title: {$this->title}\n\nContent: {$this->content}";
    }
}
```

### 4. Semantic Vector Search

You can search indexed chunks using either raw text queries or pre-calculated vector arrays:

```php
use Hamzi\NativeRag\Facades\NativeRag;

// Search directly using a question string (automatically embeds the query)
$results = NativeRag::search('How does database indexing work?', limit: 5, minScore: 0.40);

foreach ($results as $chunk) {
    echo $chunk->chunk_content; // Matching text passage
    echo $chunk->similarity;    // Cosine similarity score (0.0 to 1.0)
}

// Or pass a raw vector array directly
$vector = NativeRag::embedding()->embed('Query text');
$results = NativeRag::search($vector, limit: 5);
```

### 5. Persistent Multi-Turn Conversations

Build interactive chat experiences with persistent database storage and automatic history pruning:

```php
use Hamzi\NativeRag\Models\NativeRagConversation;

// 1. Create a conversation session
$conversation = NativeRagConversation::create([
    'name' => 'Project Architecture Chat',
]);

// 2. Set system instructions
$conversation->addSystemMessage('You are an expert Laravel developer.');

// 3. Ask a question (saves both messages and returns the assistant response)
$response = $conversation->ask('How should I structure my repository?');
echo $response->content;

// 4. Continue the chat with full context retained
$followUp = $conversation->ask('Can you show a code example?');
echo $followUp->content;
```

#### Memory Pruning Strategies
- **`count` (Default)**: Keeps the last `N` messages in active context.
- **`token`**: Keeps messages up to a specified token threshold (approximated at `ceil(chars / 4)` if exact tokens are unavailable).
- **System Prompt Preservation**: Enabled by default (`preserve_system_messages => true`), ensuring initial system prompts remain intact regardless of conversation length.

### 6. Switch Drivers at Runtime

```php
use Hamzi\NativeRag\Facades\NativeRag;

// Use LM Studio for a specific request
$response = NativeRag::driver('lmstudio')->chat([
    ['role' => 'user', 'content' => 'Summarize this file.'],
]);
```

---

## 🔒 Security & Privacy

- **Local Inference:** All LLM prompts and embeddings run against your self-hosted Ollama or LM Studio instance. No data is transmitted to external cloud APIs.
- **Payload Encryption:** When enabled (`NATIVE_RAG_ENCRYPT_PAYLOADS=true`), chat messages and metadata are encrypted in the database using Laravel's native encryption.
- **Safe Queries:** Uses parameterized database queries and prepared statements throughout.
- **Hash Change Detection:** MD5 checksums prevent re-embedding unchanged documents.

---

## 🧪 Testing & Code Quality

```bash
# Run unit tests
composer test

# Format code style (Laravel Pint)
composer lint

# Static analysis (PHPStan Level 6)
composer analyse
```

---

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on our workflow and pull request guidelines.

## 🛡️ Security Vulnerabilities

If you discover a security vulnerability, please review [SECURITY.md](SECURITY.md) for responsible disclosure instructions.

## 📄 License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for details.
