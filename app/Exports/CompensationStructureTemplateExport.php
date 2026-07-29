<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompensationStructureTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'structure_name',
            'effective_from',
            'effective_to',
            'is_default',
            'component_name',
            'calculation_type',
            'value',
            'display_order',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Standard Structure',
                now()->toDateString(),
                '',
                0,
                'Basic',
                'FIXED',
                25000,
                1,
            ],
            [
                'Standard Structure',
                now()->toDateString(),
                '',
                0,
                'HRA',
                'PERCENT_BASIC',
                40,
                2,
            ],
        ];
    }
}
