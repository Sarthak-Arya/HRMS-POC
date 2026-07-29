<?php

namespace App\Enums\Attendance;

enum AttendanceRecordSource: string
{
    case MANUAL = 'manual';
    case CALCULATED = 'calculated';
}
