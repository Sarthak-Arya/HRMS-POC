<?php

namespace App\Enums\Attendance;

enum HolidaySourceType: string
{
    case PUBLIC = 'public';
    case COMPANY = 'company';
    case REGIONAL = 'regional';
    case EMERGENCY = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public Holiday',
            self::COMPANY => 'Company Holiday',
            self::REGIONAL => 'Regional Holiday',
            self::EMERGENCY => 'Emergency Closure',
        };
    }
}
