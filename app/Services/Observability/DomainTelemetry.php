<?php

namespace App\Services\Observability;

use App\Support\Observability\MetricsRegistry;
use App\Support\Observability\Telemetry;
use App\Support\Observability\TelemetryContext;

/**
 * Thin facade used by domain services to emit business/security events.
 */
class DomainTelemetry
{
    public function __construct(
        private readonly Telemetry $telemetry,
        private readonly MetricsRegistry $metrics,
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function emit(
        string $name,
        string $category,
        string $outcome = 'success',
        array $context = [],
        string $severity = 'info',
    ): void {
        if (isset($context['company_id']) && ! isset($context['company.id'])) {
            $context['company.id'] = $context['company_id'];
            unset($context['company_id']);
        }

        $this->telemetry->event($name, $category, $outcome, $context, $severity);
    }

    public function securityDenied(string $eventName, array $context = []): void
    {
        $this->emit($eventName, 'security', 'denied', $context, 'warning');
    }

    public function markBatchFailed(): void
    {
        $this->metrics->recordBatchFailed();
    }

    public function recordHttpClient(string $provider, int $status, float $seconds): void
    {
        $this->metrics->recordHttpClient($provider, $status, $seconds);
    }

    public function requestId(): string
    {
        return TelemetryContext::requestId();
    }
}
