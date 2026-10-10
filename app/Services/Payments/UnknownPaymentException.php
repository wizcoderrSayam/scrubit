<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * A verified event references a payment we have no record of. The event is not stored,
 * so the provider's retry can be applied once the payment exists.
 */
class UnknownPaymentException extends RuntimeException {}
