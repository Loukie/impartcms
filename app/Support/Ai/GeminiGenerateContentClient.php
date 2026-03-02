<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;

class GeminiGenerateContentClient implements LlmClientInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeoutSeconds = 60,
    ) {}

    /**
     * Must match: App\Support\Ai\LlmClientInterface::generateText(string $input, string $instructions = ''): array
     *
     * @return array{output_text:string, model?:string, meta?:array<string,mixed>}
     */
    public function generateText(string $input, string $instructions = ''): array
    {
        $model = $this->model;

        // Keep it simple + robust: embed instructions into the prompt.
        $prompt = trim($instructions) !== ''
            ? "INSTRUCTIONS:\n{$instructions}\n\nINPUT:\n{$input}"
            : $input;

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            // Conservative defaults for reliability.
            'generationConfig' => [
                'temperature' => 0.2,
            ],
        ];

        $req = Http::withToken($this->apiKey);
        if ($this->timeoutSeconds > 0) {
            $req = $req->timeout($this->timeoutSeconds);
        }
        $resp = $req
            ->withHeaders([
                'x-goog-api-key' => $this->apiKey,
                'Content-Type'   => 'application/json',
            ])
            ->post($endpoint, $payload);

        if (!$resp->successful()) {
            $json = $resp->json();
            $msg = is_array($json) ? ($json['error']['message'] ?? null) : null;
            $msg = is_string($msg) && $msg !== '' ? $msg : $resp->body();
            throw new \RuntimeException("Gemini API error: {$msg}");
        }

        $json = $resp->json();
        if (!is_array($json)) {
            throw new \RuntimeException('Gemini API returned an invalid response.');
        }

        $text = $this->extractText($json);
        $text = trim($text);

        if ($text === '') {
            // Provide a more actionable error than "no output".
            $blockReason = $json['promptFeedback']['blockReason'] ?? null;
            if (is_string($blockReason) && $blockReason !== '') {
                throw new \RuntimeException("Gemini returned no text (blocked: {$blockReason}).");
            }

            $finishReason = $json['candidates'][0]['finishReason'] ?? null;
            if (is_string($finishReason) && $finishReason !== '') {
                throw new \RuntimeException("Gemini returned no text (finishReason: {$finishReason}).");
            }

            throw new \RuntimeException('Gemini returned no text output.');
        }

        return [
            // Canonical key expected by the rest of the CMS.
            'output_text' => $text,
            // Extra convenience key (harmless if unused).
            'text' => $text,
            'model' => $model,
            'meta' => ['raw' => $json],
        ];
    }

    /**
     * Extract text from Gemini REST response (supports multiple shapes).
     */
    private function extractText(array $json): string
    {
        // Primary path: candidates[0].content.parts[*].text
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

        // Fallback: some responses might use 'text' at the top level (rare)
        if (isset($json['text']) && is_string($json['text'])) {
            return $json['text'];
        }

        return '';
    }
}
