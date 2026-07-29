<?php

namespace App\Services\Ai;

use App\Services\Observability\DomainTelemetry;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client for interacting with the OpenRouter AI API.
 * Handles chat completions and tool calls.
 */
class OpenRouterClient
{
    public function __construct(
        private readonly DomainTelemetry $telemetry,
    ) {
    }

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
        $started = microtime(true);

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

                if ($response->serverError() && $attempt < $attempts) {
                    usleep($delayMs * 1000 * $attempt);
                    continue;
                }

                $duration = microtime(true) - $started;
                $this->telemetry->recordHttpClient('openrouter', $response->status(), $duration);

                if (!$response->successful()) {
                    $this->telemetry->emit('ai.request.completed', 'integration', 'failure', [
                        'provider' => 'openrouter',
                        'http.status' => $response->status(),
                        'http.status_class' => $response->status() >= 500 ? '5xx' : '4xx',
                        'duration_ms' => (int) round($duration * 1000),
                        'job.attempts' => $attempt,
                    ], 'error');

                    if ($response->status() === 429) {
                        throw new RuntimeException('OpenRouter rate limit reached. Please try again later.');
                    }

                    throw new RuntimeException('AI service error: request failed with status '.$response->status());
                }

                $data = $response->json();
                $choice = $data['choices'][0]['message'] ?? null;

                if (!$choice) {
                    throw new RuntimeException('Invalid response from AI service.');
                }

                $this->telemetry->emit('ai.request.completed', 'integration', 'success', [
                    'provider' => 'openrouter',
                    'http.status' => $response->status(),
                    'http.status_class' => '2xx',
                    'duration_ms' => (int) round($duration * 1000),
                    'job.attempts' => $attempt,
                ]);

                return $choice;
            } catch (ConnectionException $e) {
                $this->telemetry->recordHttpClient('openrouter', 0, microtime(true) - $started);
                if ($attempt >= $attempts) {
                    $this->telemetry->emit('ai.request.completed', 'integration', 'failure', [
                        'provider' => 'openrouter',
                        'error.type' => ConnectionException::class,
                        'job.attempts' => $attempt,
                    ], 'error');
                    throw new RuntimeException('Could not connect to AI service.', 0, $e);
                }
                usleep($delayMs * 1000 * $attempt);
            }
        }

        throw new RuntimeException('AI request failed after retries.');
    }
}
