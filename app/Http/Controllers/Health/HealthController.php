<?php

namespace App\Http\Controllers\Health;

use App\Support\Observability\MetricsRegistry;
use App\Support\Observability\Telemetry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController
{
    public function liveness(Telemetry $telemetry): JsonResponse
    {
        $telemetry->event('health.liveness', 'application', 'success', [], 'info', 'application');

        return response()->json([
            'status' => 'ok',
            'service' => config('observability.service_name', 'payroll-app'),
        ]);
    }

    public function readiness(Telemetry $telemetry): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = ! in_array(false, $checks, true);
        $telemetry->event(
            'health.readiness',
            'application',
            $healthy ? 'success' : 'failure',
            ['status' => $healthy ? 'ready' : 'not_ready'],
            $healthy ? 'info' : 'error',
            'application'
        );

        return response()->json([
            'status' => $healthy ? 'ready' : 'not_ready',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    public function metrics(Request $request, MetricsRegistry $metrics): Response
    {
        $token = (string) config('observability.metrics.token', '');
        if ($token !== '') {
            $provided = (string) $request->bearerToken();
            if (! hash_equals($token, $provided)) {
                abort(401);
            }
        }

        return response($metrics->renderPrometheus(), 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'healthcheck:'.uniqid('', true);
            Cache::put($key, '1', 5);

            return Cache::get($key) === '1';
        } catch (Throwable) {
            return false;
        }
    }

    private function checkQueue(): bool
    {
        $connection = config('queue.default');
        if ($connection === 'sync') {
            return true;
        }

        if ($connection === 'redis') {
            try {
                Redis::connection()->ping();

                return true;
            } catch (Throwable) {
                return false;
            }
        }

        try {
            DB::table(config('queue.connections.database.table', 'jobs'))->limit(1)->count();

            return true;
        } catch (Throwable) {
            // Database queue table may not exist yet — report unhealthy only in non-sync modes.
            return false;
        }
    }
}
