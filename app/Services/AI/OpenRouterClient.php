<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.openrouter.api_key'));
    }

    public function chat(array $messages, ?string $model = null): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('OpenRouter is not configured. Set OPENROUTER_API_KEY in .env.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.openrouter.api_key'),
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => $model ?? config('services.openrouter.model'),
            'messages' => $messages,
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenRouter request failed: '.$response->body());
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new RuntimeException('OpenRouter returned an empty response.');
        }

        return $content;
    }
}
