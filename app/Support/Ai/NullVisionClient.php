<?php

namespace App\Support\Ai;

use RuntimeException;

class NullVisionClient implements VisionClientInterface
{
    public function __construct(
        private readonly string $message = 'Vision AI is not configured.',
    ) {}

    public function generateTextWithImages(string $input, string $instructions, array $imagePaths): array
    {
        throw new RuntimeException($this->message);
    }
}
