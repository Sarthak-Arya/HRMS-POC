<?php

namespace App\Providers;

use App\Services\Ai\AgentOrchestrator;
use App\Services\Ai\OpenRouterClient;
use App\Services\Ai\ToolRegistry;
use App\Services\Ai\Tools\AttendanceToolProvider;
use App\Services\Ai\Tools\EmployeeToolProvider;
use App\Support\Observability\MetricsRegistry;
use App\Support\Observability\Telemetry;
use App\Support\Observability\TelemetryRedactor;
use App\Support\Observability\TraceExporter;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ToolRegistry::class, function () {
            $registry = new ToolRegistry();
            $registry->registerMany([
                ...EmployeeToolProvider::tools(),
                ...AttendanceToolProvider::tools(),
            ]);

            return $registry;
        });

        $this->app->singleton(OpenRouterClient::class);
        $this->app->singleton(AgentOrchestrator::class);

        $this->app->singleton(TelemetryRedactor::class);
        $this->app->singleton(MetricsRegistry::class);
        $this->app->singleton(TraceExporter::class);
        $this->app->singleton(Telemetry::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::before(function (JobProcessing $event) {
            $telemetry = app(Telemetry::class);
            $payload = $event->job->payload();
            $requestId = data_get($payload, 'telemetry.request_id');
            if (is_string($requestId) && $requestId !== '') {
                \App\Support\Observability\TelemetryContext::forJob(
                    $requestId,
                    data_get($payload, 'telemetry.trace_id'),
                    data_get($payload, 'telemetry.span_id'),
                );
            }

            $telemetry->event('queue.job.started', 'application', 'success', [
                'job.class' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
            ]);
        });

        Queue::after(function (JobProcessed $event) {
            app(Telemetry::class)->event('queue.job.completed', 'application', 'success', [
                'job.class' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
            ]);
        });

        Queue::failing(function (JobFailed $event) {
            app(Telemetry::class)->event('queue.job.failed', 'application', 'failure', [
                'job.class' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'error.type' => $event->exception::class,
                'message' => $event->exception->getMessage(),
            ], 'error');
        });
    }
}
