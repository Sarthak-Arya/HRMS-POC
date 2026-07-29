<?php

namespace App\Enums\Attendance;

enum AttendanceEntrySource: string
{
    case MANUAL = 'manual';
    case DAILY_AGGREGATED = 'daily_aggregated';
    case CLOCK_AGGREGATED = 'clock_aggregated';
}
