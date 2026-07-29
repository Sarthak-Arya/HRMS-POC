<?php

namespace App\Support\Observability;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Lightweight OTLP/HTTP JSON span exporter (best-effort, non-blocking).
 */
class TraceExporter
{
    /** @var list<array<string, mixed>> */
    private array $buffer = [];

    private float $startedAt;

    public function __construct()
    {
        $this->startedAt = microtime(true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addEvent(string $name, array $attributes = []): void
    {
        if (! config('observability.otel.enabled')) {
            return;
        }

        $this->buffer[] = [
            'name' => $name,
            'timeUnixNano' => (string) (int) (microtime(true) * 1e9),
            'attributes' => $this->toAttributes($attributes),
        ];

        if (count($this->buffer) >= 20) {
            $this->flush();
        }
    }

    public function startSpan(string $name): void
    {
        $this->startedAt = microtime(true);
        TelemetryContext::startChildSpan();
        $this->addEvent($name.'.started');
    }

    public function endSpan(string $name, string $status = 'ok', array $attributes = []): void
    {
        $durationMs = (int) ((microtime(true) - $this->startedAt) * 1000);
        $this->addEvent($name.'.completed', array_merge($attributes, [
            'duration_ms' => $durationMs,
            'status' => $status,
        ]));
        $this->flush();
    }

    public function flush(): void
    {
        if ($this->buffer === [] || ! config('observability.otel.enabled')) {
            $this->buffer = [];

            return;
        }

        if (app()->environment('testing') || config('observability.otel.export_disabled')) {
            $this->buffer = [];

            return;
        }

        $endpoint = rtrim((string) config('observability.otel.endpoint'), '/').'/v1/traces';
        $payload = [
            'resourceSpans' => [[
                'resource' => [
                    'attributes' => $this->toAttributes([
                        'service.name' => config('observability.service_name', 'payroll-app'),
                        'service.version' => config('observability.service_version', 'unknown'),
                        'deployment.environment' => config('app.env'),
                    ]),
                ],
                'scopeSpans' => [[
                    'scope' => ['name' => 'payroll-app', 'version' => '1'],
                    'spans' => [[
                        'traceId' => TelemetryContext::traceId(),
                        'spanId' => TelemetryContext::spanId(),
                        'name' => 'payroll.request',
                        'kind' => 1,
                        'startTimeUnixNano' => (string) (int) ((microtime(true) - 0.001) * 1e9),
                        'endTimeUnixNano' => (string) (int) (microtime(true) * 1e9),
                        'events' => $this->buffer,
                        'attributes' => $this->toAttributes(TelemetryContext::baseAttributes()),
                    ]],
                ]],
            ]],
        ];

        $this->buffer = [];

        try {
            Http::timeout((float) config('observability.otel.timeout_seconds', 0.5))
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'traceparent' => TelemetryContext::traceparent(),
                ])
                ->post($endpoint, $payload);
        } catch (Throwable) {
            // Never block the request path.
        }
    }

    /**
     * @param  array<string, mixed>  $map
     * @return list<array{key: string, value: array<string, mixed>}>
     */
    private function toAttributes(array $map): array
    {
        $attrs = [];
        foreach ($map as $key => $value) {
            if ($value === null || is_array($value)) {
                continue;
            }
            if (is_bool($value)) {
                $attrs[] = ['key' => (string) $key, 'value' => ['boolValue' => $value]];
            } elseif (is_int($value)) {
                $attrs[] = ['key' => (string) $key, 'value' => ['intValue' => $value]];
            } elseif (is_float($value)) {
                $attrs[] = ['key' => (string) $key, 'value' => ['doubleValue' => $value]];
            } else {
                $attrs[] = ['key' => (string) $key, 'value' => ['stringValue' => (string) $value]];
            }
        }

        return $attrs;
    }
}
