<?php

namespace App\Support\Observability;

/**
 * In-process Prometheus-compatible metric registry.
 * Labels are cardinality-bounded. Export via /metrics or OTLP.
 */
class MetricsRegistry
{
    /** @var array<string, array{help: string, type: string, samples: array<string, float>}> */
    private array $counters = [];

    /** @var array<string, array{help: string, type: string, samples: array<string, array{count: int, sum: float}>}> */
    private array $histograms = [];

    /** @var array<string, array{help: string, type: string, samples: array<string, float>}> */
    private array $gauges = [];

    /** @var list<float> */
    private array $buckets = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10];

    public function increment(string $name, array $labels = [], float $by = 1.0, string $help = ''): void
    {
        $key = $this->labelKey($labels);
        if (! isset($this->counters[$name])) {
            $this->counters[$name] = ['help' => $help ?: $name, 'type' => 'counter', 'samples' => []];
        }
        $this->counters[$name]['samples'][$key] = ($this->counters[$name]['samples'][$key] ?? 0) + $by;
    }

    public function observe(string $name, float $seconds, array $labels = [], string $help = ''): void
    {
        $key = $this->labelKey($labels);
        if (! isset($this->histograms[$name])) {
            $this->histograms[$name] = ['help' => $help ?: $name, 'type' => 'histogram', 'samples' => []];
        }
        $sample = $this->histograms[$name]['samples'][$key] ?? ['count' => 0, 'sum' => 0.0, 'buckets' => []];
        $sample['count']++;
        $sample['sum'] += $seconds;
        foreach ($this->buckets as $bound) {
            $bKey = (string) $bound;
            if (! isset($sample['buckets'][$bKey])) {
                $sample['buckets'][$bKey] = 0;
            }
            if ($seconds <= $bound) {
                $sample['buckets'][$bKey]++;
            }
        }
        $this->histograms[$name]['samples'][$key] = $sample;
    }

    public function gauge(string $name, float $value, array $labels = [], string $help = ''): void
    {
        $key = $this->labelKey($labels);
        if (! isset($this->gauges[$name])) {
            $this->gauges[$name] = ['help' => $help ?: $name, 'type' => 'gauge', 'samples' => []];
        }
        $this->gauges[$name]['samples'][$key] = $value;
    }

    private function environment(): string
    {
        try {
            return (string) config('app.env', 'local');
        } catch (\Throwable) {
            return (string) (getenv('APP_ENV') ?: 'local');
        }
    }

    public function recordEvent(string $eventName, string $category, string $outcome): void
    {
        $labels = [
            'event_name' => $this->bound($eventName, 80),
            'outcome' => $this->bound($outcome, 16),
            'environment' => $this->environment(),
        ];

        if ($category === 'security') {
            $this->increment('payroll_security_events_total', $labels, 1, 'Security telemetry events');
        } elseif (in_array($category, ['business', 'audit'], true)) {
            $this->increment('payroll_business_events_total', $labels, 1, 'Business/audit telemetry events');
        } else {
            $this->increment('payroll_application_events_total', $labels, 1, 'Application telemetry events');
        }
    }

    public function recordHttp(string $method, string $route, int $status, float $durationSeconds): void
    {
        $statusClass = $status >= 500 ? '5xx' : ($status >= 400 ? '4xx' : ($status >= 300 ? '3xx' : '2xx'));
        $labels = [
            'method' => strtoupper($method),
            'route_template' => $this->bound($route ?: 'unknown', 120),
            'status_class' => $statusClass,
            'environment' => $this->environment(),
        ];
        $this->increment('payroll_http_requests_total', $labels, 1, 'HTTP requests');
        $this->observe('payroll_http_request_duration_seconds', $durationSeconds, $labels, 'HTTP request duration');
    }

    public function recordHttpClient(string $provider, int $status, float $durationSeconds): void
    {
        $statusClass = $status >= 500 ? '5xx' : ($status >= 400 ? '4xx' : ($status >= 300 ? '3xx' : ($status > 0 ? '2xx' : 'error')));
        $labels = [
            'provider' => $this->bound($provider, 40),
            'status_class' => $statusClass,
            'environment' => $this->environment(),
        ];
        $this->increment('payroll_http_client_requests_total', $labels, 1, 'Outbound HTTP client requests');
        $this->observe('payroll_http_client_duration_seconds', $durationSeconds, $labels, 'Outbound HTTP duration');
    }

    public function recordBatchFailed(): void
    {
        $this->increment('payroll_batch_failed_total', [
            'environment' => $this->environment(),
        ], 1, 'Payroll batch failures');
    }

    public function setQueueDepth(int $depth, string $queue = 'default'): void
    {
        $this->gauge('payroll_queue_depth', (float) $depth, [
            'queue' => $this->bound($queue, 40),
            'environment' => $this->environment(),
        ], 'Approximate queue depth');
    }

    public function renderPrometheus(): string
    {
        $lines = [];

        foreach ($this->counters as $name => $meta) {
            $lines[] = "# HELP {$name} {$meta['help']}";
            $lines[] = "# TYPE {$name} counter";
            foreach ($meta['samples'] as $labelKey => $value) {
                $lines[] = "{$name}{{$labelKey}} ".$this->formatFloat($value);
            }
        }

        foreach ($this->gauges as $name => $meta) {
            $lines[] = "# HELP {$name} {$meta['help']}";
            $lines[] = "# TYPE {$name} gauge";
            foreach ($meta['samples'] as $labelKey => $value) {
                $lines[] = "{$name}{{$labelKey}} ".$this->formatFloat($value);
            }
        }

        foreach ($this->histograms as $name => $meta) {
            $lines[] = "# HELP {$name} {$meta['help']}";
            $lines[] = "# TYPE {$name} histogram";
            foreach ($meta['samples'] as $labelKey => $sample) {
                $cumulative = 0;
                foreach ($this->buckets as $bound) {
                    $bKey = (string) $bound;
                    $cumulative += $sample['buckets'][$bKey] ?? 0;
                    $leLabels = $labelKey === '' ? "le=\"{$bound}\"" : $labelKey.",le=\"{$bound}\"";
                    $lines[] = "{$name}_bucket{{$leLabels}} {$cumulative}";
                }
                $infLabels = $labelKey === '' ? 'le="+Inf"' : $labelKey.',le="+Inf"';
                $lines[] = "{$name}_bucket{{$infLabels}} {$sample['count']}";
                $lines[] = "{$name}_sum{{$labelKey}} ".$this->formatFloat($sample['sum']);
                $lines[] = "{$name}_count{{$labelKey}} {$sample['count']}";
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Snapshot for OTLP export (counters + gauges only for simplicity).
     *
     * @return array{counters: array<string, mixed>, gauges: array<string, mixed>}
     */
    public function snapshot(): array
    {
        return [
            'counters' => $this->counters,
            'gauges' => $this->gauges,
            'histograms' => $this->histograms,
        ];
    }

    /**
     * @param  array<string, scalar|null>  $labels
     */
    private function labelKey(array $labels): string
    {
        $parts = [];
        ksort($labels);
        foreach ($labels as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            // Reject high-cardinality accidental IDs in label values.
            if (preg_match('/id$/i', (string) $k) || preg_match('/^[0-9a-f-]{20,}$/i', (string) $v)) {
                continue;
            }
            $parts[] = $k.'="'.$this->escape((string) $v).'"';
        }

        return implode(',', $parts);
    }

    private function bound(string $value, int $max): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_.:\/\-{}]/', '_', $value) ?? $value;

        return substr($value, 0, $max);
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', "\n", '"'], ['\\\\', '\\n', '\\"'], $value);
    }

    private function formatFloat(float $value): string
    {
        return rtrim(rtrim(sprintf('%.6F', $value), '0'), '.') ?: '0';
    }
}
