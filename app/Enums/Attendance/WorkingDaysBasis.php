<?php

namespace App\Enums\Attendance;

enum WorkingDaysBasis: string
{
    case FIXED_26 = 'fixed_26';
    case CALENDAR_DAYS = 'calendar_days';
    case CUSTOM = 'custom';
}
