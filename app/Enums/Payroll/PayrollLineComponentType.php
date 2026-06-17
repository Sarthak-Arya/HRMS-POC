<?php

namespace App\Enums\Payroll;

enum PayrollLineComponentType: string
{
    case EARNING = 'EARNING';
    case DEDUCTION = 'DEDUCTION';
    case EMPLOYER_CONTRIBUTION = 'EMPLOYER_CONTRIBUTION';
}
