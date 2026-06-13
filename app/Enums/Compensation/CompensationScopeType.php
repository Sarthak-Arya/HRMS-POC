<?php

namespace App\Enums\Compensation;

enum CompensationScopeType: string
{
    case COMPANY = 'company';
    case LOCATION = 'location';
    case DEPARTMENT = 'department';
    case EMPLOYEE = 'employee';

    /** @return list<self> */
    public static function overrideCascadeOrder(): array
    {
        return [
            self::COMPANY,
            self::LOCATION,
            self::DEPARTMENT,
            self::EMPLOYEE,
        ];
    }

    /** @return list<self> */
    public static function structureCascadeOrder(): array
    {
        return [
            self::EMPLOYEE,
            self::DEPARTMENT,
            self::LOCATION,
            self::COMPANY,
        ];
    }
}
