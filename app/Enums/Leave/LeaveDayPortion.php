<?php

namespace App\Enums\Leave;

enum LeaveDayPortion: string
{
    case Full = 'full';
    case FirstHalf = 'first_half';
    case SecondHalf = 'second_half';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Full day',
            self::FirstHalf => 'First half',
            self::SecondHalf => 'Second half',
        };
    }

    public function dayValue(): float
    {
        return $this === self::Full ? 1.0 : 0.5;
    }
}
