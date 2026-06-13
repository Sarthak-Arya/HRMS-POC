<?php

namespace App\Enums\Compensation;

enum StatutoryComponent: string
{
    case PF = 'PF';
    case ESIC = 'ESIC';
    case PT = 'PT';
    case LWF = 'LWF';
    case TDS = 'TDS';
}
