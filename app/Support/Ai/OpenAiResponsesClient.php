<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class OpenAiResponsesClient implements LlmClientInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gpt-5.2',
        private readonly int $timeoutSeconds = 0,
    ) {}

    public function generateText(string $input, string $instructions = ''): array
    {
        $payload = [
            'model' => $this->model,
            'input' => $input,
        ];

        $instructions = trim($instructions);
        if ($instructions !== '') {
            $payload['instructions'] = $instructions;
        }

        $req = Http::withToken($this->apiKey)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ]);
        if ($this->timeoutSeconds > 0) {
            $req = $req->timeout($this->timeoutSeconds);
        }
        $res = $req->post('https://api.openai.com/v1/responses', $payload);

        /** @var RequestException|null $ex */
        $ex = null;
        try {
            $res->throw();
        } catch (RequestException $e) {
            $ex = $e;
        }

        $data = $res->json();
        if (!is_array($data)) {
            if ($ex) {
                throw $ex;
            }
            throw new \RuntimeException('OpenAI API returned an invalid response.');
        }

        // Preferred: output_text (present in SDK examples)
        if (isset($data['output_text']) && is_string($data['output_text'])) {
            return [
                'output_text' => $data['output_text'],
                'model' => is_string($data['model'] ?? null) ? (string) $data['model'] : $this->model,
                'meta' => [
                    'id' => $data['id'] ?? null,
                ],
            ];
        }

        // Fallback: walk output items to extract text.
        $out = '';
        if (isset($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $item) {
                if (!is_array($item)) continue;
                if (($item['type'] ?? null) !== 'message') continue;
                $content = $item['content'] ?? null;
                if (!is_array($content)) continue;
                foreach ($content as $c) {
                    if (!is_array($c)) continue;
                    if (($c['type'] ?? null) === 'output_text' && is_string($c['text'] ?? null)) {
                        $out .= (string) $c['text'];
                    }
                }
            }
        }

        $out = trim($out);
        if ($out === '') {
            if ($ex) {
                throw $ex;
            }
            throw new \RuntimeException('OpenAI API returned no text output.');
        }

        return [
            'output_text' => $out,
            'model' => is_string($data['model'] ?? null) ? (string) $data['model'] : $this->model,
            'meta' => [
                'id' => $data['id'] ?? null,
            ],
        ];
    }
}
