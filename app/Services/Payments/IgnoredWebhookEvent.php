<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * A correctly signed webhook for an event type this application does not act on.
 * It is acknowledged (HTTP 200) so the provider stops retrying.
 */
class IgnoredWebhookEvent extends RuntimeException {}
