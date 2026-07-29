<?php

namespace App\Enums\Attendance;

enum AttendanceMode: string
{
    case MONTHLY_SUMMARY = 'monthly_summary';
    case DAILY_MARKING = 'daily_marking';
    case CLOCK_IN_OUT = 'clock_in_out';
}
