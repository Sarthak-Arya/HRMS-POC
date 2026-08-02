<?php

namespace App\Support\Observability;

/**
 * In-process Prometheus-compatible metric registry.
 * Labels are cardinality-bounded. Export via /metrics or OTLP.
 *
 * Under php-fpm / artisan serve the app boots per request, so counters are
 * persisted to a local file so Prometheus scrapes see cumulative values.
 */
class MetricsRegistry
{
    /** @var array<string, array{help: string, type: string, samples: array<string, float>}> */
    private array $counters = [];

    /** @var array<string, array{help: string, type: string, samples: array<string, array{count: int, sum: float, buckets: array<string, int>}>}> */
    private array $histograms = [];

    /** @var array<string, array{help: string, type: string, samples: array<string, float>}> */
    private array $gauges = [];

    /** @var list<float> */
    private array $buckets = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10];

    private bool $loaded = false;

    private bool $dirty = false;

    public function increment(string $name, array $labels = [], float $by = 1.0, string $help = ''): void
    {
        $this->ensureLoaded();
        $key = $this->labelKey($labels);
        if (! isset($this->counters[$name])) {
            $this->counters[$name] = ['help' => $help ?: $name, 'type' => 'counter', 'samples' => []];
        }
        $this->counters[$name]['samples'][$key] = ($this->counters[$name]['samples'][$key] ?? 0) + $by;
        $this->dirty = true;
        $this->persist();
    }

    public function observe(string $name, float $seconds, array $labels = [], string $help = ''): void
    {
        $this->ensureLoaded();
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
        $this->dirty = true;
        $this->persist();
    }

    public function gauge(string $name, float $value, array $labels = [], string $help = ''): void
    {
        $this->ensureLoaded();
        $key = $this->labelKey($labels);
        if (! isset($this->gauges[$name])) {
            $this->gauges[$name] = ['help' => $help ?: $name, 'type' => 'gauge', 'samples' => []];
        }
        $this->gauges[$name]['samples'][$key] = $value;
        $this->dirty = true;
        $this->persist();
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
        $this->ensureLoaded();
        $lines = [];

        foreach ($this->counters as $name => $meta) {
            $lines[] = "# HELP {$name} {$meta['help']}";
            $lines[] = "# TYPE {$name} counter";
            foreach ($meta['samples'] as $labelKey => $value) {
                $lines[] = "{$name}{{$labelKey}} ".$this->formatFloat((float) $value);
            }
        }

        foreach ($this->gauges as $name => $meta) {
            $lines[] = "# HELP {$name} {$meta['help']}";
            $lines[] = "# TYPE {$name} gauge";
            foreach ($meta['samples'] as $labelKey => $value) {
                $lines[] = "{$name}{{$labelKey}} ".$this->formatFloat((float) $value);
            }
        }

        foreach ($this->histograms as $name => $meta) {
            $lines[] = "# HELP {$name} {$meta['help']}";
            $lines[] = "# TYPE {$name} histogram";
            foreach ($meta['samples'] as $labelKey => $sample) {
                foreach ($this->buckets as $bound) {
                    $bKey = (string) $bound;
                    // Buckets already store cumulative counts (obs with duration <= bound).
                    $count = (int) ($sample['buckets'][$bKey] ?? 0);
                    $leLabels = $labelKey === '' ? "le=\"{$bound}\"" : $labelKey.",le=\"{$bound}\"";
                    $lines[] = "{$name}_bucket{{$leLabels}} {$count}";
                }
                $infLabels = $labelKey === '' ? 'le="+Inf"' : $labelKey.',le="+Inf"';
                $lines[] = "{$name}_bucket{{$infLabels}} {$sample['count']}";
                $lines[] = "{$name}_sum{{$labelKey}} ".$this->formatFloat((float) $sample['sum']);
                $lines[] = "{$name}_count{{$labelKey}} {$sample['count']}";
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @return array{counters: array<string, mixed>, gauges: array<string, mixed>, histograms: array<string, mixed>}
     */
    public function snapshot(): array
    {
        $this->ensureLoaded();

        return [
            'counters' => $this->counters,
            'gauges' => $this->gauges,
            'histograms' => $this->histograms,
        ];
    }

    private function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        if ($this->shouldSkipPersistence()) {
            return;
        }

        $path = $this->persistPath();
        if (! is_file($path)) {
            return;
        }

        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return;
        }

        try {
            flock($fh, LOCK_SH);
            $raw = stream_get_contents($fh);
            flock($fh, LOCK_UN);
        } finally {
            fclose($fh);
        }

        if (! is_string($raw) || $raw === '') {
            return;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return;
        }

        $this->counters = is_array($decoded['counters'] ?? null) ? $decoded['counters'] : [];
        $this->gauges = is_array($decoded['gauges'] ?? null) ? $decoded['gauges'] : [];
        $this->histograms = is_array($decoded['histograms'] ?? null) ? $decoded['histograms'] : [];
    }

    private function persist(): void
    {
        if (! $this->dirty || $this->shouldSkipPersistence()) {
            return;
        }

        $path = $this->persistPath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $payload = json_encode([
            'counters' => $this->counters,
            'gauges' => $this->gauges,
            'histograms' => $this->histograms,
        ], JSON_THROW_ON_ERROR);

        $fh = @fopen($path, 'c+b');
        if ($fh === false) {
            return;
        }

        try {
            flock($fh, LOCK_EX);
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, $payload);
            fflush($fh);
            flock($fh, LOCK_UN);
            $this->dirty = false;
        } catch (\Throwable) {
            // Metrics must never break the app.
        } finally {
            fclose($fh);
        }
    }

    private function shouldSkipPersistence(): bool
    {
        try {
            return app()->environment('testing');
        } catch (\Throwable) {
            return (string) (getenv('APP_ENV') ?: '') === 'testing';
        }
    }

    private function persistPath(): string
    {
        try {
            return storage_path('framework/cache/observability-metrics.json');
        } catch (\Throwable) {
            return sys_get_temp_dir().'/payroll-observability-metrics.json';
        }
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
