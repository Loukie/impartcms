<?php

namespace App\Support\Ai;

use RuntimeException;

class NullAiImageClient implements AiImageClientInterface
{
    public function __construct(
        private readonly string $message = 'AI image generation is not configured for the current provider.'
    ) {}

    public function generateImage(string $prompt, array $options = []): array
    {
        throw new RuntimeException($this->message);
    }
}
