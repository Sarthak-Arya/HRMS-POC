<?php

namespace App\Services\Compensation;

use Illuminate\Support\Collection;

class ResolvedCompensation
{
    /**
     * @param Collection<int, ResolvedComponentLine> $lines
     */
    public function __construct(
        public ?int $structureId,
        public ?string $structureName,
        public ?float $annualCtc,
        public ?float $monthlyGross,
        public Collection $lines,
        public string $structureSource,
    ) {}

    public function totalMonthlyEarnings(): float
    {
        return (float) $this->lines
            ->filter(fn (ResolvedComponentLine $line) => $line->componentType->value === 'EARNING')
            ->sum(fn (ResolvedComponentLine $line) => $line->monthlyAmount ?? 0);
    }

    public function totalMonthlyDeductions(): float
    {
        return (float) $this->lines
            ->filter(fn (ResolvedComponentLine $line) => $line->componentType->value === 'DEDUCTION')
            ->sum(fn (ResolvedComponentLine $line) => $line->monthlyAmount ?? 0);
    }
}
