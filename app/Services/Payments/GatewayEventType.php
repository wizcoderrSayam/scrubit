<?php

namespace App\Services\Payments;

enum GatewayEventType: string
{
    case Succeeded = 'payment.succeeded';
    case Processing = 'payment.processing';
    case Failed = 'payment.failed';
    case Cancelled = 'payment.cancelled';
}
