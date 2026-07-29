<?php

namespace App\Support\Observability;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Request / job scoped correlation and actor context.
 */
class TelemetryContext
{
    private static ?string $requestId = null;

    private static ?string $traceId = null;

    private static ?string $spanId = null;

    /** @var array<string, mixed> */
    private static array $baggage = [];

    public static function boot(?string $requestId = null, ?string $traceparent = null): void
    {
        self::$requestId = $requestId ?: (string) Str::uuid();
        self::parseTraceparent($traceparent);
        self::$baggage = [];
    }

    public static function forJob(string $requestId, ?string $traceId = null, ?string $spanId = null): void
    {
        self::$requestId = $requestId;
        self::$traceId = $traceId ?: self::generateTraceId();
        self::$spanId = $spanId ?: self::generateSpanId();
    }

    public static function reset(): void
    {
        self::$requestId = null;
        self::$traceId = null;
        self::$spanId = null;
        self::$baggage = [];
    }

    public static function requestId(): string
    {
        if (! self::$requestId) {
            self::$requestId = (string) Str::uuid();
        }

        return self::$requestId;
    }

    public static function traceId(): string
    {
        if (! self::$traceId) {
            self::$traceId = self::generateTraceId();
        }

        return self::$traceId;
    }

    public static function spanId(): string
    {
        if (! self::$spanId) {
            self::$spanId = self::generateSpanId();
        }

        return self::$spanId;
    }

    public static function startChildSpan(): string
    {
        self::$spanId = self::generateSpanId();

        return self::$spanId;
    }

    public static function traceparent(): string
    {
        return sprintf('00-%s-%s-01', self::traceId(), self::spanId());
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function merge(array $values): void
    {
        self::$baggage = array_merge(self::$baggage, $values);
    }

    /**
     * @return array<string, mixed>
     */
    public static function baseAttributes(): array
    {
        $user = Auth::user();

        return array_filter([
            'service.name' => config('observability.service_name', 'payroll-app'),
            'service.version' => config('observability.service_version', 'unknown'),
            'deployment.environment' => config('app.env'),
            'event.schema_version' => config('observability.schema_version', 1),
            'request.id' => self::requestId(),
            'trace.id' => self::traceId(),
            'span.id' => self::spanId(),
            'actor.user_id' => $user?->id,
            'actor.role' => $user?->roles?->first()?->name,
            'company.id' => session('company_id'),
        ] + self::$baggage, static fn ($v) => $v !== null && $v !== '');
    }

    private static function parseTraceparent(?string $header): void
    {
        if (! $header || ! preg_match('/^00-([0-9a-f]{32})-([0-9a-f]{16})-([0-9a-f]{2})$/i', trim($header), $m)) {
            self::$traceId = self::generateTraceId();
            self::$spanId = self::generateSpanId();

            return;
        }

        self::$traceId = strtolower($m[1]);
        self::$spanId = self::generateSpanId();
    }

    private static function generateTraceId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private static function generateSpanId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
