<?php

namespace App\Services\Compensation;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;

class ResolvedComponentLine
{
    public function __construct(
        public int $componentId,
        public string $componentName,
        public ComponentType $componentType,
        public CalculationType $calculationType,
        public ?float $value,
        public ?string $formulaExpression,
        public bool $isMandatory,
        public int $displayOrder,
        public string $source,
        public ?float $monthlyAmount = null,
    ) {}
}
