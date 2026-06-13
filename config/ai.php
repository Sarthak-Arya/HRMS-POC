<?php

return [
    'provider' => env('AI_PROVIDER', 'openrouter'),

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('AI_MODEL', 'openai/gpt-oss-120b:free'),
        'max_tokens' => (int) env('AI_MAX_TOKENS', 4096),
        'timeout' => (int) env('AI_TIMEOUT', 120),
        'retry_attempts' => (int) env('AI_RETRY_ATTEMPTS', 3),
        'retry_delay_ms' => (int) env('AI_RETRY_DELAY_MS', 1000),
    ],

    'agent' => [
        'max_tool_rounds' => (int) env('AI_MAX_TOOL_ROUNDS', 5),
        'stt_fallback' => (bool) env('AI_STT_FALLBACK', false),
    ],

    'app_name' => env('AI_APP_NAME', env('APP_NAME', 'Payroll Assistant')),
];
