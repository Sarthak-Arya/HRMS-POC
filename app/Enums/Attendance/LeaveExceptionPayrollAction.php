<?php

namespace App\Enums\Attendance;

enum LeaveExceptionPayrollAction: string
{
    case NONE = 'none';
    case LOP_ONLY = 'lop_only';
    case PRORATE_ONLY = 'prorate_only';
}
