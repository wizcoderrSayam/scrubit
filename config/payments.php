<?php

use App\Services\Payments\DemoGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Driver
    |--------------------------------------------------------------------------
    |
    | The gateway used for new checkouts. "demo" simulates a hosted checkout
    | page and signed webhooks so the full flow can be tested without a
    | payment provider account. Additional drivers (e.g. Stripe Checkout)
    | implement App\Services\Payments\PaymentGateway and register here.
    |
    */

    'driver' => env('PAYMENT_DRIVER', 'demo'),

    'drivers' => [
        'demo' => DemoGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Checkout Session Lifetime
    |--------------------------------------------------------------------------
    */

    'checkout_ttl_minutes' => (int) env('PAYMENT_CHECKOUT_TTL_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Demo Gateway
    |--------------------------------------------------------------------------
    |
    | The demo gateway refuses to run in production unless explicitly allowed.
    |
    */

    'demo' => [
        'webhook_secret' => env('DEMO_WEBHOOK_SECRET') ?: env('APP_KEY'),
        'allow_production' => (bool) env('DEMO_GATEWAY_ALLOW_PRODUCTION', false),
        'tolerance_seconds' => 300,
    ],

];
