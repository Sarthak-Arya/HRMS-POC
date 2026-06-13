<?php

namespace App\Enums\Compensation;

enum CalculationType: string
{
    case FIXED = 'FIXED';
    case PERCENT_BASIC = 'PERCENT_BASIC';
    case PERCENT_CTC = 'PERCENT_CTC';
    case FORMULA = 'FORMULA';
}
