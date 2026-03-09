<?php

declare(strict_types=1);

return [
    'base_url' => env('APP_FACILITA_BASE_URL', ''),
    'api_instance' => env('APP_FACILITA_API_INSTANCE', ''),
    'api_key' => env('APP_FACILITA_API_KEY', ''),
    'token_user' => env('APP_FACILITA_TOKEN', ''),

    'timeout' => (int) env('APP_FACILITA_TIMEOUT', 10),
    'connect_timeout' => env('APP_FACILITA_CONNECT_TIMEOUT') !== null
        ? (int) env('APP_FACILITA_CONNECT_TIMEOUT')
        : null,
    'user_agent' => env('APP_FACILITA_USER_AGENT', null),
    'proxy' => env('APP_FACILITA_PROXY', null),
    'retry_max' => (int) env('APP_FACILITA_RETRY_MAX', 3),
    'retry_base_ms' => (int) env('APP_FACILITA_RETRY_BASE_MS', 250),
];
