<?php

namespace App\Enums;

enum AddonSource: string
{
    case Selected = 'selected';
    case ChatFlag = 'chat_flag';
    case Admin = 'admin';
}
