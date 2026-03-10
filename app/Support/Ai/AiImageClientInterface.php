<?php

namespace App\Support\Ai;

interface AiImageClientInterface
{
    /**
     * Generate an image from a text prompt.
     *
     * @param array<string,mixed> $options
     * @return array{binary:string,mime_type:string,provider?:string,model?:string,revised_prompt?:string}
     */
    public function generateImage(string $prompt, array $options = []): array;
}
