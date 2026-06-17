<?php

namespace App\Enums\Payroll;

enum EmployeePayrollStatus: string
{
    case DRAFT = 'DRAFT';
    case APPROVED = 'APPROVED';
    case PAID = 'PAID';

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::APPROVED],
            self::APPROVED => [self::PAID, self::DRAFT],
            self::PAID => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
