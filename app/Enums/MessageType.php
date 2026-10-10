<?php

namespace App\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Widget = 'widget';
    case Flag = 'flag';
    case InternalNote = 'internal_note';
}
