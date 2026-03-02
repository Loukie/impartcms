<?php

namespace App\Support\Ai;

interface LlmClientInterface
{
    /**
     * Generate plain text output.
     *
     * @return array{output_text: string, model?: string, meta?: array<string,mixed>}
     */
    public function generateText(string $input, string $instructions = ''): array;
}
