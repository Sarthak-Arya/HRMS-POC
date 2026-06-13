<?php

namespace App\Enums\Compensation;

enum OverrideType: string
{
    case REPLACE = 'REPLACE';
    case ADD = 'ADD';
    case REMOVE = 'REMOVE';
}
