<?php

namespace App\Enums\Payroll;

enum AuditEventType: string
{
    case CREATE = 'CREATE';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE';
    case STATUS_CHANGE = 'STATUS_CHANGE';
    case CALCULATION = 'CALCULATION';
}
