<?php

namespace App\Enums\Attendance;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case HALF_DAY = 'half_day';
    case LEAVE = 'leave';
    case HOLIDAY = 'holiday';
    case WEEK_OFF = 'week_off';
}
