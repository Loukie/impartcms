<?php

namespace App\Support\Ai;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OpenAiImageClient implements AiImageClientInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gpt-image-1',
        private readonly int $timeoutSeconds = 60,
    ) {}

    public function generateImage(string $prompt, array $options = []): array
    {
        $size = (string) ($options['size'] ?? '1536x1024');
        if (!in_array($size, ['1024x1024', '1792x1024', '1024x1792'], true)) {
            $size = '1024x1024';  // DALL-E 3 default
        }

        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'size' => $size,
            'n' => 1,
        ];

        $req = Http::withToken($this->apiKey)->withHeaders([
            'Content-Type' => 'application/json',
        ]);
        if ($this->timeoutSeconds > 0) {
            $req = $req->timeout($this->timeoutSeconds);
        }

        try {
            $res = $req->post('https://api.openai.com/v1/images/generations', $payload);
            $res->throw();
        } catch (RequestException $e) {
            $body = $e->response?->body() ?? $e->getMessage();
            throw new \RuntimeException('OpenAI image API error: ' . $body);
        }

        $json = $res->json();
        if (!is_array($json) || !isset($json['data'][0]['url'])) {
            throw new \RuntimeException('OpenAI image API returned invalid payload.');
        }

        // DALL-E 3 returns a URL, not base64, so we need to download it
        $imageUrl = (string) ($json['data'][0]['url'] ?? '');
        if ($imageUrl === '') {
            throw new \RuntimeException('OpenAI image API returned empty URL.');
        }

        // Download the image
        try {
            $imageResponse = Http::timeout($this->timeoutSeconds)->get($imageUrl);
            $imageResponse->throw();
            $bin = $imageResponse->body();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to download generated image: ' . $e->getMessage());
        }

        if (!is_string($bin) || $bin === '') {
            throw new \RuntimeException('OpenAI image API returned empty image data.');
        }

        return [
            'binary' => $bin,
            'mime_type' => 'image/png',
            'provider' => 'openai',
            'model' => $this->model,
            'revised_prompt' => (string) ($json['data'][0]['revised_prompt'] ?? ''),
        ];
    }
}
