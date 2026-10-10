<?php

namespace App\Enums;

enum MessageSender: string
{
    case Customer = 'customer';
    case Assistant = 'assistant';
    case Admin = 'admin';
    case System = 'system';
}
