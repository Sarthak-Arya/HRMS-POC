<?php

namespace App\Services\Reports\DataSources;

use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;

class ReportValueResolver
{
    public static function resolve(object $row, string $columnKey): mixed
    {
        $segments = explode('.', $columnKey);
        $current = $row;

        foreach ($segments as $segment) {
            if ($current === null) {
                return null;
            }

            if (is_array($current)) {
                $current = Arr::get($current, $segment);
                continue;
            }

            if (is_object($current)) {
                $current = $current->{$segment} ?? null;
                continue;
            }

            return null;
        }

        return self::formatValue($current);
    }

    private static function formatValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return $value;
    }
}
