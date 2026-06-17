<?php

namespace App\Enums\Payroll;

enum PayrollAdjustmentType: string
{
    case ADDITION = 'ADDITION';
    case DEDUCTION = 'DEDUCTION';
}
