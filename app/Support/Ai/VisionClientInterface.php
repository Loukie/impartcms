<?php

namespace App\Support\Ai;

interface VisionClientInterface
{
    /**
     * Generate text with one or more local image files.
     *
     * @param array<int,string> $imagePaths Absolute or relative filesystem paths.
     * @return array{output_text:string, model?:string, meta?:array<string,mixed>}
     */
    public function generateTextWithImages(string $input, string $instructions, array $imagePaths): array;
}
