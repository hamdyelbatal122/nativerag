<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Drivers;

use Hamzi\NativeRag\Contracts\ChatEngineContract;
use Hamzi\NativeRag\Contracts\EmbeddingEngineContract;
use Hamzi\NativeRag\Data\ChatResponse;
use Hamzi\NativeRag\Responses\NativeRagStreamResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OllamaDriver implements ChatEngineContract, EmbeddingEngineContract
{
    protected string $baseUrl;
    protected string $chatModel;
    protected string $embeddingModel;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retrySleepMs;
    /** @var array<string, mixed> */
    protected array $options;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'] ?? 'http://localhost:11434', '/');
        $this->chatModel = $config['model'] ?? 'llama3';
        $this->embeddingModel = $config['embedding_model'] ?? 'nomic-embed-text';
        $this->timeout = $config['timeout'] ?? 60;
        $this->retryAttempts = $config['retry_attempts'] ?? 3;
        $this->retrySleepMs = $config['retry_sleep_ms'] ?? 1000;
        $this->options = $config['options'] ?? [];
    }

    protected function client(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->retry($this->retryAttempts, $this->retrySleepMs)
            ->acceptJson()
            ->asJson();
    }

    /**
     * @param  array<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     */
    public function chat(array $messages, array $options = []): ChatResponse
    {
        $payload = [
            'model' => $this->chatModel,
            'messages' => $messages,
            'stream' => false,
            'options' => array_merge($this->options, $options),
        ];

        $response = $this->client()->post("{$this->baseUrl}/api/chat", $payload)->throw();
        $data = $response->json();

        return new ChatResponse(
            content: $data['message']['content'] ?? '',
            role: $data['message']['role'] ?? 'assistant',
            raw: $data,
            promptTokens: $data['prompt_eval_count'] ?? null,
            completionTokens: $data['eval_count'] ?? null,
        );
    }

    /**
     * @param  array<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     */
    public function stream(array $messages, array $options = []): StreamedResponse
    {
        $payload = [
            'model' => $this->chatModel,
            'messages' => $messages,
            'stream' => true,
            'options' => array_merge($this->options, $options),
        ];

        return new NativeRagStreamResponse(function () use ($payload) {
            $response = Http::timeout($this->timeout)
                ->withOptions(['stream' => true])
                ->post("{$this->baseUrl}/api/chat", $payload);

            if ($response->failed()) {
                $response->throw();
            }

            $body = $response->toPsrResponse()->getBody();

            $buffer = '';
            while (! $body->eof()) {
                if (connection_aborted()) {
                    break;
                }

                $chunk = $body->read(1024);
                if ($chunk === '') {
                    continue;
                }

                $buffer .= $chunk;

                // Ollama streams JSON objects delimited by newlines
                $lines = explode("\n", $buffer);
                // Keep the last incomplete line in the buffer
                $buffer = array_pop($lines);

                foreach ($lines as $line) {
                    if (trim($line) === '') {
                        continue;
                    }

                    $data = json_decode($line, true);
                    if (is_array($data)) {
                        $token = $data['message']['content'] ?? '';
                        $isDone = $data['done'] ?? false;

                        NativeRagStreamResponse::sendChunk([
                            'content' => $token,
                            'done' => $isDone,
                        ]);
                    }
                }
            }
        });
    }

    /**
     * @param  string|array<string>  $text
     * @param  array<string, mixed>  $options
     * @return array<int|string, array<float>|float>
     */
    public function embed(string|array $text, array $options = []): array
    {
        // Ollama supports /api/embed which accepts `input` as string or array of strings.
        $payload = [
            'model' => $this->embeddingModel,
            'input' => is_array($text) ? array_values($text) : [$text],
            'options' => array_merge($this->options, $options),
        ];

        $response = $this->client()->post("{$this->baseUrl}/api/embed", $payload)->throw();
        $data = $response->json();

        $embeddings = $data['embeddings'] ?? [];

        if (is_string($text)) {
            return $embeddings[0] ?? [];
        }

        return $embeddings;
    }
}
