<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiVisionClient implements VisionClientInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeoutSeconds = 60,
    ) {}

    public function generateTextWithImages(string $input, string $instructions, array $imagePaths): array
    {
        $model = $this->model;

        $prompt = trim($instructions) !== ''
            ? "INSTRUCTIONS:\n{$instructions}\n\nINPUT:\n{$input}"
            : $input;

        $parts = [
            ['text' => $prompt],
        ];

        foreach ($imagePaths as $p) {
            $p = (string) $p;
            if ($p === '' || !is_file($p)) continue;
            $mime = $this->guessMime($p);
            $data = base64_encode((string) file_get_contents($p));
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mime,
                    'data' => $data,
                ],
            ];
        }

        if (count($parts) < 2) {
            throw new RuntimeException('No readable screenshots were provided to the vision model.');
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $parts,
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
            ],
        ];

        $resp = Http::timeout($this->timeoutSeconds)
            ->withHeaders([
                'x-goog-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, $payload);

        if (!$resp->successful()) {
            $json = $resp->json();
            $msg = is_array($json) ? ($json['error']['message'] ?? null) : null;
            $msg = is_string($msg) && $msg !== '' ? $msg : $resp->body();
            throw new RuntimeException("Gemini API error: {$msg}");
        }

        $json = $resp->json();
        if (!is_array($json)) {
            throw new RuntimeException('Gemini API returned an invalid response.');
        }

        $text = $this->extractText($json);
        $text = trim($text);
        if ($text === '') {
            throw new RuntimeException('Gemini returned no text output.');
        }

        return [
            'output_text' => $text,
            'model' => $model,
            'meta' => ['raw' => $json],
        ];
    }

    private function guessMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    private function extractText(array $json): string
    {
        $candidates = $json['candidates'] ?? null;
        if (is_array($candidates) && isset($candidates[0]) && is_array($candidates[0])) {
            $content = $candidates[0]['content'] ?? null;
            if (is_array($content)) {
                $parts = $content['parts'] ?? null;
                if (is_array($parts)) {
                    $out = '';
                    foreach ($parts as $p) {
                        if (!is_array($p)) continue;
                        if (isset($p['text']) && is_string($p['text'])) {
                            $out .= $p['text'];
                        }
                    }
                    if ($out !== '') return $out;
                }
            }
        }

        return '';
    }
}
