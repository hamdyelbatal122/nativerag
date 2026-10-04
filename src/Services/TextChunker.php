<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Services;

class TextChunker
{
    /**
     * Chunk the given text into an array of smaller overlapping text segments.
     *
     * @return array<int, string>
     */
    public function chunk(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        if ($chunkSize <= 0) {
            throw new \InvalidArgumentException('Chunk size must be greater than zero.');
        }

        if ($overlap < 0 || $overlap >= $chunkSize) {
            throw new \InvalidArgumentException('Overlap must be non-negative and smaller than chunk size.');
        }

        $text = trim($text);

        if ($text === '') {
            return [];
        }

        if (mb_strlen($text, 'UTF-8') <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $currentStart = 0;
        $textLength = mb_strlen($text, 'UTF-8');

        while ($currentStart < $textLength) {
            $endPoint = $currentStart + $chunkSize;

            if ($endPoint >= $textLength) {
                $chunks[] = trim(mb_substr($text, $currentStart, null, 'UTF-8'));

                break;
            }

            $searchRangeStart = max($currentStart, $endPoint - 100);
            $naturalBoundary = $this->findNaturalBoundary($text, $searchRangeStart, $endPoint);

            if ($naturalBoundary !== false) {
                $endPoint = $naturalBoundary;
            }

            $chunks[] = trim(mb_substr($text, $currentStart, $endPoint - $currentStart, 'UTF-8'));

            $actualLength = $endPoint - $currentStart;
            $currentStart += max(10, $actualLength - $overlap);
        }

        return $chunks;
    }

    /**
     * Finds the last boundary delimiter (paragraph, newline, or sentence end).
     */
    protected function findNaturalBoundary(string $text, int $start, int $end): int|false
    {
        $segment = mb_substr($text, $start, $end - $start, 'UTF-8');

        $pos = mb_strrpos($segment, "\n\n", 0, 'UTF-8');
        if ($pos !== false) {
            return $start + $pos + 2;
        }

        $pos = mb_strrpos($segment, "\n", 0, 'UTF-8');
        if ($pos !== false) {
            return $start + $pos + 1;
        }

        $pos = mb_strrpos($segment, '. ', 0, 'UTF-8');
        if ($pos !== false) {
            return $start + $pos + 2;
        }

        return false;
    }
}
