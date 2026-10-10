<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A booking, quote or payment action that cannot be performed in the current state.
 * The message is safe to show to the customer or administrator.
 */
class BookingActionException extends RuntimeException {}
