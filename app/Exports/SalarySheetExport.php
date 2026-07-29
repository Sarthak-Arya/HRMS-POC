<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SalarySheetExport implements FromArray, WithEvents, WithTitle
{
    /**
     * @param  array<string, mixed>  $sheet
     */
    public function __construct(
        private readonly array $sheet,
    ) {}

    public function title(): string
    {
        return substr((string) ($this->sheet['title'] ?? 'Salary Sheet'), 0, 31);
    }

    public function array(): array
    {
        $rows = [];
        $companyName = $this->sheet['company']->company_name ?? 'Company';
        $rows[] = [$companyName];
        $rows[] = ['SALARY REGISTER FOR THE MONTH OF '.$this->sheet['period_label']];
        if (! empty($this->sheet['group_by']) && $this->sheet['group_by'] !== 'company') {
            $rows[] = ['Scope: '.$this->sheet['title']];
        }
        $rows[] = ['E.S.I. NO.', $this->sheet['esi_code'] ?? '', 'P.F. NO.', $this->sheet['pf_code'] ?? ''];
        $rows[] = [];

        $earningColumns = array_values($this->sheet['earning_columns']);
        $header = array_merge(
            ['SNO', 'Employee Name', "Father's Name", 'ESI No', 'PF No', 'Work Days', 'Hol Days', 'Tot Days'],
            array_map(fn (string $name) => $this->abbreviateHeader('Act '.$name), $earningColumns),
            ['Actual Gross'],
            array_map(fn (string $name) => $this->abbreviateHeader('Pay '.$name), $earningColumns),
            ['Variable Pay', 'Payable Gross', 'ESI Wages', 'ESI Empl.', 'PF Wages', 'PF', 'TDS', 'Advance', 'Other Ded', 'Total Ded', 'Net Pay'],
        );
        $rows[] = $header;

        foreach ($this->sheet['employees'] as $employee) {
            $row = [
                $employee['sno'],
                $employee['employee_name'],
                $employee['father_name'],
                $employee['esi_no'] ?? '',
                $employee['pf_no'] ?? '',
                $employee['work_days'],
                $employee['holiday_days'],
                $employee['total_days'],
            ];

            foreach (array_keys($this->sheet['earning_columns']) as $componentId) {
                $row[] = $employee['actual_earnings'][$componentId] ?? 0;
            }

            $row[] = $employee['actual_gross'];

            foreach (array_keys($this->sheet['earning_columns']) as $componentId) {
                $row[] = $employee['payable_earnings'][$componentId] ?? 0;
            }

            $rows[] = array_merge($row, [
                $employee['variable_pay'],
                $employee['payable_gross'],
                $employee['esi_wages'],
                $employee['esi_employer'],
                $employee['pf_wages'],
                $employee['pf_employee'],
                $employee['tds'],
                $employee['advance'],
                $employee['other_deductions'],
                $employee['total_deductions'],
                $employee['net_pay'],
            ]);
        }

        $totals = $this->sheet['totals'];
        $totalRow = ['PAGE TOTAL', '', '', '', '', '', '', ''];
        foreach (array_keys($this->sheet['earning_columns']) as $componentId) {
            $totalRow[] = $totals['actual_earnings'][$componentId] ?? 0;
        }
        $totalRow[] = $totals['actual_gross'];
        foreach (array_keys($this->sheet['earning_columns']) as $componentId) {
            $totalRow[] = $totals['payable_earnings'][$componentId] ?? 0;
        }
        $rows[] = array_merge($totalRow, [
            $totals['variable_pay'],
            $totals['payable_gross'],
            $totals['esi_wages'],
            $totals['esi_employer'],
            $totals['pf_wages'],
            $totals['pf_employee'],
            $totals['tds'],
            $totals['advance'],
            $totals['other_deductions'],
            $totals['total_deductions'],
            $totals['net_pay'],
        ]);
        $rows[] = [];

        $summary = $this->sheet['statutory_summary'];
        $rows[] = ['SUMMARY OF DEDUCTIONS FOR THE MONTH OF '.$this->sheet['period_label']];
        $rows[] = ['Total Employees', $summary['employee_count']];
        $rows[] = ['Total Gross Salary', $summary['total_gross_salary']];
        $rows[] = ['Exempted ESI Salary', $summary['exempted_esi_salary']];
        $rows[] = ['Exempted PF Salary', $summary['exempted_pf_salary']];
        $rows[] = ['ESI Employees', $summary['esi_employee_count']];
        $rows[] = ['ESI Wages', $summary['esi_wages']];
        $rows[] = ['ESI Employee Contribution', $summary['esi_employee_contribution']];
        $rows[] = ['ESI Employer Contribution', $summary['esi_employer_contribution']];
        $rows[] = ['Total ESI', $summary['esi_total']];
        $rows[] = ['PF Employees', $summary['pf_employee_count']];
        $rows[] = ['PF Wages', $summary['pf_wages']];
        $rows[] = ['Chalan 01', $summary['pf_chalan_01']];
        $rows[] = ['Chalan 10', $summary['pf_chalan_10']];
        $rows[] = ['Chalan 21', $summary['pf_chalan_21']];
        $rows[] = ['Chalan 22', $summary['pf_chalan_22']];
        $rows[] = ['Chalan 02', $summary['pf_chalan_02']];
        $rows[] = ['Total PF', $summary['pf_total']];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 5;
                $lastColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle('A1:D1')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFF3F4F6');
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $widths = [
                    1 => 5,
                    2 => 22,
                    3 => 18,
                    4 => 12,
                    5 => 12,
                    6 => 8,
                    7 => 8,
                    8 => 8,
                ];

                $earningCount = count($this->sheet['earning_columns']);
                $earningStart = 9;
                for ($i = 0; $i < ($earningCount * 2) + 2; $i++) {
                    $widths[$earningStart + $i] = 12;
                }

                $trailingStart = $earningStart + ($earningCount * 2) + 2;
                for ($i = 0; $i < 11; $i++) {
                    $widths[$trailingStart + $i] = 11;
                }

                foreach ($widths as $index => $width) {
                    if ($index <= $lastColumnIndex) {
                        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth($width);
                    }
                }

                $sheet->freezePane('C6');
                $sheet->getRowDimension($headerRow)->setRowHeight(36);
            },
        ];
    }

    private function abbreviateHeader(string $label): string
    {
        return strlen($label) > 14 ? substr($label, 0, 12).'…' : $label;
    }
}
