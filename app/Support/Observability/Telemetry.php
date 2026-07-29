<?php

namespace App\Support\Observability;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Emits structured, privacy-safe domain/ops events to Monolog channels
 * and in-process metrics/trace buffers.
 */
class Telemetry
{
    public function __construct(
        private readonly TelemetryRedactor $redactor,
        private readonly MetricsRegistry $metrics,
        private readonly TraceExporter $traces,
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function event(
        string $name,
        string $category,
        string $outcome = 'success',
        array $context = [],
        string $severity = 'info',
        ?string $channel = null,
    ): void {
        $payload = $this->redactor->sanitize(array_merge(
            TelemetryContext::baseAttributes(),
            $context,
            [
                'timestamp' => now()->utc()->toIso8601String(),
                'severity' => $severity,
                'event.name' => $name,
                'event.category' => $category,
                'event.outcome' => $outcome,
            ]
        ));

        $channelName = $channel ?: $this->channelFor($category);
        $message = $payload['message'] ?? $name;
        if (is_string($message)) {
            $message = $this->redactor->scrubMessage($message);
        } else {
            $message = $name;
        }

        try {
            Log::channel($channelName)->log($severity, $message, $payload);
        } catch (Throwable) {
            // Telemetry must never break the app.
            try {
                Log::channel('null')->debug('telemetry_channel_unavailable', ['channel' => $channelName]);
            } catch (Throwable) {
                // ignore
            }
        }

        $this->metrics->recordEvent($name, $category, $outcome);
        $this->traces->addEvent($name, $payload);
    }

    public function exception(Throwable $e, array $context = []): void
    {
        $fingerprint = substr(hash('sha256', $e::class.'|'.$e->getFile().'|'.$e->getLine()), 0, 16);

        $this->event(
            'exception.reported',
            'application',
            'failure',
            array_merge($context, [
                'error.type' => $e::class,
                'error.code' => method_exists($e, 'getCode') ? (string) $e->getCode() : '0',
                'error.fingerprint' => $fingerprint,
                'message' => $this->redactor->scrubMessage($e->getMessage()),
            ]),
            'error',
            'application'
        );
    }

    private function channelFor(string $category): string
    {
        return match ($category) {
            'security' => 'security',
            'business', 'audit' => 'payroll',
            'integration' => 'integration',
            default => 'application',
        };
    }
}
