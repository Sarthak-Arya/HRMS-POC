<?php

return [
    'service_name' => env('OTEL_SERVICE_NAME', 'payroll-app'),
    'service_version' => env('APP_VERSION', env('GIT_SHA', 'dev')),
    'schema_version' => 1,

    'otel' => [
        'enabled' => filter_var(env('OTEL_ENABLED', false), FILTER_VALIDATE_BOOL),
        'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://127.0.0.1:4318'),
        'timeout_seconds' => (float) env('OTEL_EXPORT_TIMEOUT', 0.5),
        'sampler_ratio' => (float) env('OTEL_TRACES_SAMPLER_RATIO', 0.1),
        'export_disabled' => filter_var(env('OTEL_EXPORT_DISABLED', false), FILTER_VALIDATE_BOOL),
    ],

    'metrics' => [
        'token' => env('METRICS_TOKEN', ''),
    ],
];
