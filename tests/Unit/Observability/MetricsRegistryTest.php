<?php

namespace Tests\Unit\Observability;

use App\Support\Observability\MetricsRegistry;
use PHPUnit\Framework\TestCase;

class MetricsRegistryTest extends TestCase
{
    public function test_it_rejects_high_cardinality_label_keys(): void
    {
        $metrics = new MetricsRegistry();
        $metrics->increment('payroll_http_requests_total', [
            'status_class' => '2xx',
            'request_id' => 'aaaaaaaaaaaaaaaaaaaa',
            'environment' => 'testing',
        ]);

        $output = $metrics->renderPrometheus();

        $this->assertStringContainsString('status_class="2xx"', $output);
        $this->assertStringNotContainsString('request_id', $output);
    }

    public function test_it_records_http_and_business_events(): void
    {
        $metrics = new MetricsRegistry();
        $metrics->recordHttp('GET', '/healthz', 200, 0.01);
        $metrics->recordEvent('payroll.run.completed', 'business', 'success');
        $metrics->recordBatchFailed();

        $output = $metrics->renderPrometheus();

        $this->assertStringContainsString('payroll_http_requests_total', $output);
        $this->assertStringContainsString('payroll_business_events_total', $output);
        $this->assertStringContainsString('payroll_batch_failed_total', $output);
    }
}
