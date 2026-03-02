<?php

namespace App\Support\Ai;

use RuntimeException;

class NullLlmClient implements LlmClientInterface
{
    public function __construct(
        private readonly string $message = "AI is not configured. Set OPENAI_API_KEY in your .env (and optionally OPENAI_MODEL), or configure Admin → AI Agent."
    ) {}

    public function generateText(string $input, string $instructions = ''): array
    {
        throw new RuntimeException($this->message);
    }
}
