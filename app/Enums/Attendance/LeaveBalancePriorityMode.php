<?php

namespace App\Enums\Attendance;

enum LeaveBalancePriorityMode: string
{
    case POLICY_ORDER = 'policy_order';
    case STRICT_LWP_FALLBACK = 'strict_lwp_fallback';
}
