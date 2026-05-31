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

class LmStudioDriver implements ChatEngineContract, EmbeddingEngineContract
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
        $this->baseUrl = rtrim($config['base_url'] ?? 'http://localhost:1234', '/');
        $this->chatModel = $config['model'] ?? 'local-model';
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
        $payload = array_merge([
            'model' => $this->chatModel,
            'messages' => $messages,
            'stream' => false,
        ], $this->options, $options);

        $response = $this->client()->post("{$this->baseUrl}/v1/chat/completions", $payload)->throw();
        $data = $response->json();

        return new ChatResponse(
            content: $data['choices'][0]['message']['content'] ?? '',
            role: $data['choices'][0]['message']['role'] ?? 'assistant',
            raw: $data,
            promptTokens: $data['usage']['prompt_tokens'] ?? null,
            completionTokens: $data['usage']['completion_tokens'] ?? null,
        );
    }

    /**
     * @param  array<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     */
    public function stream(array $messages, array $options = []): StreamedResponse
    {
        $payload = array_merge([
            'model' => $this->chatModel,
            'messages' => $messages,
            'stream' => true,
        ], $this->options, $options);

        return new NativeRagStreamResponse(function () use ($payload) {
            $response = Http::timeout($this->timeout)
                ->withOptions(['stream' => true])
                ->post("{$this->baseUrl}/v1/chat/completions", $payload);

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

                // SSE streams are delimited by double newlines or single newlines
                $lines = explode("\n", $buffer);
                $buffer = array_pop($lines);

                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, ':')) {
                        continue; // Skip empty lines and SSE comments
                    }

                    if (str_starts_with($line, 'data: ')) {
                        $dataStr = substr($line, 6);

                        if ($dataStr === '[DONE]') {
                            NativeRagStreamResponse::sendChunk(['content' => '', 'done' => true]);

                            break;
                        }

                        $data = json_decode($dataStr, true);
                        if (is_array($data)) {
                            $token = $data['choices'][0]['delta']['content'] ?? '';
                            $isDone = ($data['choices'][0]['finish_reason'] ?? null) !== null;

                            if ($token !== '' || $isDone) {
                                NativeRagStreamResponse::sendChunk([
                                    'content' => $token,
                                    'done' => $isDone,
                                ]);
                            }
                        }
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
        $payload = [
            'model' => $this->embeddingModel,
            'input' => is_array($text) ? array_values($text) : [$text],
        ];

        if (! empty($options)) {
            $payload = array_merge($payload, $options);
        }

        $response = $this->client()->post("{$this->baseUrl}/v1/embeddings", $payload)->throw();
        $data = $response->json();

        $embeddings = [];
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                if (isset($item['embedding']) && is_array($item['embedding'])) {
                    $embeddings[] = $item['embedding'];
                }
            }
        }

        if (is_string($text) && count($embeddings) === 1) {
            return $embeddings[0];
        }

        return $embeddings;
    }
}
