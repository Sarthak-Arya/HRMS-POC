<?php

namespace App\Enums\Payroll;

enum PayrollRunStatus: string
{
    case DRAFT = 'DRAFT';
    case PROCESSING = 'PROCESSING';
    case COMPLETED = 'COMPLETED';
    case LOCKED = 'LOCKED';

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::PROCESSING], true);
    }

    public function isLocked(): bool
    {
        return $this === self::LOCKED;
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PROCESSING],
            self::PROCESSING => [self::DRAFT, self::COMPLETED],
            self::COMPLETED => [self::LOCKED, self::PROCESSING],
            self::LOCKED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
