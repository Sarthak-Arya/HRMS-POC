<?php

namespace App\Enums\Attendance;

enum LeaveExceedAction: string
{
    case BLOCK = 'block';
    case WARN = 'warn';
    case ROUTE_TO_LWP = 'route_to_lwp';
    case REQUIRE_OVERRIDE = 'require_override';
}
