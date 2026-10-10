<?php

use App\Services\Payments\DemoGateway;
use App\Services\Payments\StripeCheckoutGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Driver
    |--------------------------------------------------------------------------
    |
    | The gateway used for new checkouts:
    |   - "demo"   simulates a hosted checkout and signed webhooks (no account needed)
    |   - "stripe" uses Stripe Checkout (hosted page, card data never touches this app)
    |
    */

    'driver' => env('PAYMENT_DRIVER', 'demo'),

    'drivers' => [
        'demo' => DemoGateway::class,
        'stripe' => StripeCheckoutGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Checkout Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Stripe requires at least 30 minutes; shorter values are raised to 30.
    |
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

    /*
    |--------------------------------------------------------------------------
    | Stripe Checkout
    |--------------------------------------------------------------------------
    |
    | Use test-mode keys (sk_test_..., whsec_...) until launch. The webhook
    | endpoint is {APP_URL}/webhooks/payments/stripe and should receive:
    | checkout.session.completed, checkout.session.async_payment_succeeded,
    | checkout.session.async_payment_failed and checkout.session.expired.
    |
    */

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'api_base' => env('STRIPE_API_BASE', 'https://api.stripe.com/v1'),
        'tolerance_seconds' => 300,
    ],

];
