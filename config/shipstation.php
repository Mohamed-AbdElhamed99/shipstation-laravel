<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your ShipStation API key. Generated from the ShipStation account
    | settings under API Settings. Sandbox keys are prefixed with TEST_.
    |
    */
    'api_key' => env('SHIPSTATION_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Supported: "production", "sandbox". The sandbox environment is not
    | always available — verify availability in your ShipStation dashboard.
    |
    */
    'environment' => env('SHIPSTATION_ENV', 'production'),

    'base_urls' => [
        'production' => 'https://api.shipstation.com/v2',
        'sandbox'    => 'https://api-stage.shipstation.com/v2',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    */
    'timeout'       => env('SHIPSTATION_TIMEOUT', 30),
    'connect_timeout' => env('SHIPSTATION_CONNECT_TIMEOUT', 10),
    'retries'       => env('SHIPSTATION_RETRIES', 2),
    'retry_delay_ms' => env('SHIPSTATION_RETRY_DELAY', 500),

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | The secret used to verify incoming webhook signatures. If null, the
    | package will still accept webhooks but WILL NOT verify them — only
    | use that in local development.
    |
    */
    'webhooks' => [
        'secret' => env('SHIPSTATION_WEBHOOK_SECRET'),
        'tolerance_seconds' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log channel for API errors and request/response debug info. Set to
    | null to disable. Use a dedicated channel in production.
    |
    */
    'log_channel' => env('SHIPSTATION_LOG_CHANNEL'),
    'log_requests' => env('SHIPSTATION_LOG_REQUESTS', false),
];
