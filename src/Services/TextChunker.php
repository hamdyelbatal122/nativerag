<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Services;

class TextChunker
{
    /**
     * Chunk the given text into an array of smaller overlapping text segments.
     *
     * @param string $text
     * @param int $chunkSize
     * @param int $overlap
     * @return array<int, string>
     */
    public function chunk(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        $text = trim($text);
        
        if ($text === '') {
            return [];
        }

        if (strlen($text) <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $currentStart = 0;
        $textLength = strlen($text);

        while ($currentStart < $textLength) {
            // Find the maximum end point for the current chunk
            $endPoint = $currentStart + $chunkSize;
            
            // If we are beyond the end of the text, just take the rest and break
            if ($endPoint >= $textLength) {
                $chunks[] = trim(substr($text, $currentStart));
                break;
            }

            // Attempt to find a natural boundary (newline or period) near the end to avoid breaking sentences
            $searchRangeStart = max($currentStart, $endPoint - 100);
            $naturalBoundary = $this->findNaturalBoundary($text, $searchRangeStart, $endPoint);

            if ($naturalBoundary !== false) {
                $endPoint = $naturalBoundary;
            }

            $chunks[] = trim(substr($text, $currentStart, $endPoint - $currentStart));

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
        $segment = substr($text, $start, $end - $start);
        
        // Prefer double newline (paragraph)
        $pos = strrpos($segment, "\n\n");
        if ($pos !== false) {
            return $start + $pos + 2;
        }

        // Prefer single newline
        $pos = strrpos($segment, "\n");
        if ($pos !== false) {
            return $start + $pos + 1;
        }

        // Prefer period + space
        $pos = strrpos($segment, ". ");
        if ($pos !== false) {
            return $start + $pos + 2;
        }

        return false;
    }
}
