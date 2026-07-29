<?php

namespace Tests\Feature\Observability;

use App\Support\Observability\TelemetryContext;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class HealthAndTelemetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['observability.otel.enabled' => false]);
        config(['observability.otel.export_disabled' => true]);
        TelemetryContext::reset();
    }

    public function test_liveness_endpoint_returns_ok(): void
    {
        $response = $this->get('/healthz');

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertHeader('X-Request-ID');
    }

    public function test_metrics_endpoint_requires_token_when_configured(): void
    {
        config(['observability.metrics.token' => 'secret-metrics-token']);

        $this->get('/metrics')->assertUnauthorized();

        $this->withToken('secret-metrics-token')
            ->get('/metrics')
            ->assertOk();
    }

    public function test_request_context_propagates_incoming_request_id(): void
    {
        Log::spy();

        $response = $this->withHeaders([
            'X-Request-ID' => '11111111-2222-3333-4444-555555555555',
        ])->get('/healthz');

        $response->assertOk();
        $response->assertHeader('X-Request-ID', '11111111-2222-3333-4444-555555555555');
    }
}
