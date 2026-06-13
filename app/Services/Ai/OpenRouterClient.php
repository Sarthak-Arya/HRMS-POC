<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Client for interacting with the OpenRouter AI API.
 * Handles chat completions and tool calls.
 */
class OpenRouterClient
{
    /**
     * Send a chat request to the OpenRouter API.
     *
     * @param array<int, array<string, mixed>> $messages List of chat messages (role and content).
     * @param array<int, array<string, mixed>> $tools List of tool definitions available to the model.
     * @return array<string, mixed> The message choice from the AI response.
     * @throws RuntimeException If API key is missing, rate limit is reached, or other API errors occur.
     */
    public function chat(array $messages, array $tools = []): array
    {
        $apiKey = config('ai.openrouter.api_key');
        if (!$apiKey) {
            throw new RuntimeException('OPENROUTER_API_KEY is not configured.');
        }

        $payload = [
            'model' => config('ai.openrouter.model'),
            'messages' => $messages,
            'max_tokens' => config('ai.openrouter.max_tokens'),
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $attempts = config('ai.openrouter.retry_attempts');
        $delayMs = config('ai.openrouter.retry_delay_ms');

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => config('ai.app_name'),
                ])
                    ->timeout(config('ai.openrouter.timeout'))
                    ->post(config('ai.openrouter.base_url') . '/chat/completions', $payload);

                if ($response->status() === 429 && $attempt < $attempts) {
                    usleep($delayMs * 1000 * $attempt);
                    continue;
                }

                if (!$response->successful()) {
                    $body = $response->json();
                    $message = $body['error']['message'] ?? $response->body();

                    if ($response->status() === 429) {
                        throw new RuntimeException('OpenRouter rate limit reached. Please try again later.');
                    }

                    Log::error('OpenRouter API error', [
                        'status' => $response->status(),
                        'body' => $body,
                    ]);

                    throw new RuntimeException('AI service error: ' . $message);
                }

                $data = $response->json();
                $choice = $data['choices'][0]['message'] ?? null;

                if (!$choice) {
                    throw new RuntimeException('Invalid response from AI service.');
                }

                return $choice;
            } catch (ConnectionException $e) {
                if ($attempt >= $attempts) {
                    throw new RuntimeException('Could not connect to AI service.', 0, $e);
                }
                usleep($delayMs * 1000 * $attempt);
            }
        }

        throw new RuntimeException('AI request failed after retries.');
    }
}
