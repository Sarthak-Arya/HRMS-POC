<?php

namespace App\Enums\Reports;

enum ReportColumnFormat: string
{
    case Text = 'text';
    case Date = 'date';
    case Currency = 'currency';
    case Number = 'number';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
