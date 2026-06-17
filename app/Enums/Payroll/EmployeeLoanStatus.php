<?php

namespace App\Enums\Payroll;

enum EmployeeLoanStatus: string
{
    case ACTIVE = 'ACTIVE';
    case CLOSED = 'CLOSED';
    case HOLD = 'HOLD';
}
