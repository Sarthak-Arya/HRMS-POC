<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceTemplateExport implements FromArray, WithHeadings
{
    protected $header;
    protected $rows;

    public function __construct(array $header, array $rows = [])
    {
        $this->header = $header;
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->header;
    }
} 