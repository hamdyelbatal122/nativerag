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
            // Find the maximum end point for the current chunk
            $endPoint = $currentStart + $chunkSize;

            // If we are beyond the end of the text, just take the rest and break
            if ($endPoint >= $textLength) {
                $chunks[] = trim(mb_substr($text, $currentStart, null, 'UTF-8'));

                break;
            }

            // Attempt to find a natural boundary (newline or period) near the end to avoid breaking sentences
            $searchRangeStart = max($currentStart, $endPoint - 100);
            $naturalBoundary = $this->findNaturalBoundary($text, $searchRangeStart, $endPoint);

            if ($naturalBoundary !== false) {
                $endPoint = $naturalBoundary;
            }

            $chunks[] = trim(mb_substr($text, $currentStart, $endPoint - $currentStart, 'UTF-8'));

            // Move the start forward by (Chunk Size - Overlap)
            // Or if we found a natural boundary, adjust by the actual consumed length - overlap
            $actualLength = $endPoint - $currentStart;
            $currentStart += max(10, $actualLength - $overlap); // Ensure we always move forward at least 10 chars
        }

        return $chunks;
    }

    /**
     * Finds the last occurrence of a newline or period within the given range.
     */
    protected function findNaturalBoundary(string $text, int $start, int $end): int|false
    {
        $segment = mb_substr($text, $start, $end - $start, 'UTF-8');

        // Prefer double newline (paragraph)
        $pos = mb_strrpos($segment, "\n\n", 0, 'UTF-8');
        if ($pos !== false) {
            return $start + $pos + 2;
        }

        // Prefer single newline
        $pos = mb_strrpos($segment, "\n", 0, 'UTF-8');
        if ($pos !== false) {
            return $start + $pos + 1;
        }

        // Prefer period + space
        $pos = mb_strrpos($segment, '. ', 0, 'UTF-8');
        if ($pos !== false) {
            return $start + $pos + 2;
        }

        return false;
    }
}
