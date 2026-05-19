<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Responses;

use Symfony\Component\HttpFoundation\StreamedResponse;

class NativeRagStreamResponse extends StreamedResponse
{
    /**
     * Create a new SSE Streamed Response.
     *
     * @param  array<string, string>  $headers
     */
    public function __construct(callable $callback, int $status = 200, array $headers = [])
    {
        $defaultHeaders = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no', // Disable Nginx buffering
            'Connection' => 'keep-alive',
        ];

        parent::__construct($callback, $status, array_merge($defaultHeaders, $headers));
    }

    /**
     * Helper to yield a formatted SSE data chunk and flush the buffer immediately.
     */
    public static function sendChunk(mixed $data, ?string $event = null, ?string $id = null): void
    {
        if ($id !== null) {
            echo "id: {$id}\n";
        }
        if ($event !== null) {
            echo "event: {$event}\n";
        }

        $payload = is_string($data) ? $data : json_encode($data);
        echo "data: {$payload}\n\n";

        self::flushBuffer();
    }

    /**
     * Flush all system output buffers natively to ensure instant streaming delivery.
     */
    public static function flushBuffer(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
