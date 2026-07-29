<?php

namespace App\Http\Middleware;

use App\Support\Observability\MetricsRegistry;
use App\Support\Observability\Telemetry;
use App\Support\Observability\TelemetryContext;
use App\Support\Observability\TraceExporter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RequestContextMiddleware
{
    public function __construct(
        private readonly MetricsRegistry $metrics,
        private readonly Telemetry $telemetry,
        private readonly TraceExporter $traces,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $incomingId = $request->headers->get('X-Request-ID')
            ?: $request->headers->get('X-Correlation-ID');

        TelemetryContext::boot(
            $incomingId,
            $request->headers->get('traceparent')
        );

        Log::withContext(TelemetryContext::baseAttributes());

        $started = microtime(true);
        $this->traces->startSpan('http.request');

        try {
            /** @var Response $response */
            $response = $next($request);
        } catch (Throwable $e) {
            $this->telemetry->exception($e, [
                'http.method' => $request->method(),
                'route.template' => $this->routeTemplate($request),
            ]);
            $this->traces->endSpan('http.request', 'error');
            throw $e;
        }

        $duration = microtime(true) - $started;
        $status = $response->getStatusCode();
        $route = $this->routeTemplate($request);

        $this->metrics->recordHttp($request->method(), $route, $status, $duration);

        $outcome = $status >= 500 ? 'failure' : 'success';
        $this->telemetry->event(
            'http.request.completed',
            'application',
            $outcome,
            [
                'http.method' => $request->method(),
                'route.template' => $route,
                'http.status' => $status,
                'http.status_class' => $status >= 500 ? '5xx' : ($status >= 400 ? '4xx' : '2xx'),
                'duration_ms' => (int) round($duration * 1000),
            ],
            $status >= 500 ? 'error' : 'info',
            'application'
        );

        $this->traces->endSpan('http.request', $status >= 500 ? 'error' : 'ok', [
            'http.status' => $status,
            'duration_ms' => (int) round($duration * 1000),
        ]);

        $response->headers->set('X-Request-ID', TelemetryContext::requestId());
        $response->headers->set('traceparent', TelemetryContext::traceparent());

        return $response;
    }

    private function routeTemplate(Request $request): string
    {
        $route = $request->route();
        if ($route && method_exists($route, 'uri')) {
            return '/'.ltrim((string) $route->uri(), '/');
        }

        return $request->path() ?: '/';
    }
}
