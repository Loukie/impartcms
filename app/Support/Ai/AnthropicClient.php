<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class AnthropicClient implements LlmClientInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'claude-3-5-sonnet-20241022',
        private readonly int $timeoutSeconds = 0,
    ) {}

    /**
     * Generate text using Claude API.
     *
     * @return array{output_text: string, model?: string, meta?: array<string,mixed>}
     */
    public function generateText(string $input, string $instructions = ''): array
    {
        $systemPrompt = $instructions !== '' ? $instructions : 'You are a helpful AI assistant.';

        $messages = [
            [
                'role' => 'user',
                'content' => $input,
            ],
        ];

        $payload = [
            'model' => $this->model,
            'max_tokens' => 4096,
            'system' => $systemPrompt,
            'messages' => $messages,
        ];

        $req = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ]);

        if ($this->timeoutSeconds > 0) {
            $req = $req->timeout($this->timeoutSeconds);
        }

        try {
            $res = $req->post('https://api.anthropic.com/v1/messages', $payload);
            $res->throw();
        } catch (RequestException $e) {
            $errorBody = $e->response?->body() ?? $e->getMessage();
            \Log::error('Claude request failed', [
                'status' => $e->response?->status(),
                'body' => substr((string) $errorBody, 0, 500),
            ]);
            throw new \RuntimeException(
                'Claude API error: ' . ($e->response?->body() ?? $e->getMessage())
            );
        }

        // Validate response content-type
        $contentType = $res->header('Content-Type') ?? '';
        if (stripos($contentType, 'application/json') === false && trim($res->body()) !== '') {
            \Log::error('Claude returned non-JSON', [
                'status' => $res->status(),
                'content_type' => $contentType,
                'body_preview' => substr($res->body(), 0, 200),
            ]);
            throw new \RuntimeException(
                'Claude API returned non-JSON response: ' . substr($res->body(), 0, 100)
            );
        }

        try {
            $data = $res->json();
        } catch (\Throwable $e) {
            \Log::error('Failed to parse Claude response', [
                'error' => $e->getMessage(),
                'body_preview' => substr($res->body(), 0, 300),
            ]);
            throw new \RuntimeException(
                'Failed to parse Claude API response as JSON: ' . $e->getMessage()
            );
        }

        if (!is_array($data)) {
            throw new \RuntimeException('Claude API returned an invalid response.');
        }

        // Extract text from response
        $outputText = '';
        if (isset($data['content']) && is_array($data['content'])) {
            foreach ($data['content'] as $block) {
                if (is_array($block) && ($block['type'] ?? null) === 'text') {
                    $outputText .= (string) ($block['text'] ?? '');
                }
            }
        }

        if (trim($outputText) === '') {
            throw new \RuntimeException('Claude API returned no text content.');
        }

        return [
            'output_text' => $outputText,
            'model' => is_string($data['model'] ?? null) ? (string) $data['model'] : $this->model,
            'meta' => [
                'id' => $data['id'] ?? null,
                'usage' => $data['usage'] ?? null,
            ],
        ];
    }
}
