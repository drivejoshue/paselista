<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class DeepSeekClient
{
    public function analyze(array $messages, bool $deepAnalysis): array
    {
        abort_unless(
            (bool) config('schoolpass_ai.enabled'),
            503,
            'PaseLista IA está desactivado globalmente.'
        );

        $apiKey = trim((string) config('schoolpass_ai.deepseek.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('DEEPSEEK_API_KEY no está configurada.');
        }

        $model = $deepAnalysis
            ? (string) config('schoolpass_ai.deepseek.pro_model')
            : (string) config('schoolpass_ai.deepseek.fast_model');

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => false,
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => (int) config('schoolpass_ai.deepseek.max_output_tokens', 2500),
            'thinking' => [
                'type' => $deepAnalysis ? 'enabled' : 'disabled',
            ],
        ];

        if ($deepAnalysis) {
            $payload['reasoning_effort'] = 'high';
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($apiKey)
            ->connectTimeout((int) config('schoolpass_ai.deepseek.connect_timeout_seconds', 10))
            ->timeout((int) config('schoolpass_ai.deepseek.timeout_seconds', 90))
            ->retry(2, 600, throw: false)
            ->post(
                rtrim((string) config('schoolpass_ai.deepseek.base_url'), '/')
                .'/chat/completions',
                $payload
            );

        $this->assertSuccessful($response);

        $content = trim((string) data_get(
            $response->json(),
            'choices.0.message.content',
            ''
        ));

        if ($content === '') {
            throw new RuntimeException('DeepSeek devolvió una respuesta vacía.');
        }

        $usage = (array) ($response->json('usage') ?? []);

        return [
            'model' => $model,
            'thinking_enabled' => $deepAnalysis,
            'result' => $this->decodeJson($content),
            'usage' => [
                'input_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
                'cached_input_tokens' => (int) ($usage['prompt_cache_hit_tokens'] ?? 0),
                'output_tokens' => (int) ($usage['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
            ],
        ];
    }

    public function estimatedCostUsd(string $model, array $usage): float
    {
        $price = config('schoolpass_ai.pricing.'.$model);

        if (! is_array($price)) {
            return 0.0;
        }

        $input = max(0, (int) ($usage['input_tokens'] ?? 0));
        $cached = min($input, max(0, (int) ($usage['cached_input_tokens'] ?? 0)));
        $miss = max(0, $input - $cached);
        $output = max(0, (int) ($usage['output_tokens'] ?? 0));

        return round((
            $cached * (float) $price['input_cache_hit']
            + $miss * (float) $price['input_cache_miss']
            + $output * (float) $price['output']
        ) / 1_000_000, 8);
    }

    private function assertSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $message = (string) (
            $response->json('error.message')
            ?? $response->body()
            ?? 'Error desconocido'
        );

        throw new RuntimeException(sprintf(
            'DeepSeek respondió HTTP %d: %s',
            $response->status(),
            mb_substr($message, 0, 1200)
        ));
    }

    private function decodeJson(string $content): array
    {
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content);

        try {
            $decoded = json_decode(
                (string) $clean,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'DeepSeek no devolvió JSON válido: '.$exception->getMessage()
            );
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('La respuesta JSON de DeepSeek no es un objeto.');
        }

        return $decoded;
    }
}
