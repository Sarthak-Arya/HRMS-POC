<?php

namespace App\Enums\Attendance;

enum HalfDayStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LEAVE = 'leave';
    case WEEK_OFF = 'week_off';
}
