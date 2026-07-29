<?php

namespace App\Enums\Attendance;

enum AttendanceScopeType: string
{
    case COMPANY = 'company';
    case LOCATION = 'location';
    case DEPARTMENT = 'department';
    case DESIGNATION = 'designation';
    case EMPLOYEE = 'employee';

    /** @return list<self> */
    public static function cascadeOrder(): array
    {
        return [
            self::EMPLOYEE,
            self::DESIGNATION,
            self::DEPARTMENT,
            self::LOCATION,
            self::COMPANY,
        ];
    }
}
