<?php

namespace App\Enums\Attendance;

enum WeeklyOffRule: string
{
    case SUNDAY = 'sunday';
    case SAT_SUN = 'sat_sun';
    case ALTERNATE_SATURDAY = 'alternate_saturday';
    case CUSTOM = 'custom';
}
