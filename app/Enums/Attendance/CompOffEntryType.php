<?php

namespace App\Enums\Attendance;

enum CompOffEntryType: string
{
    case EARNED = 'earned';
    case USED = 'used';
    case EXPIRED = 'expired';
    case ADJUSTED = 'adjusted';
}
