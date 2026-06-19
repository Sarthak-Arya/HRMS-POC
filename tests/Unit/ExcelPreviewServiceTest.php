<?php

namespace Tests\Unit;

use App\Services\Ai\ExcelPreviewService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelPreviewServiceTest extends TestCase
{
    public function test_detects_attendance_sheet_and_formats_preview(): void
    {
        $path = $this->createSpreadsheet([
            ['employee_code', 'month', 'year', 'cl', 'el', 'sl', 'tot_dys'],
            ['EMP001', 6, 2026, 1, 0, 0, 30],
            ['EMP002', 6, 2026, 0, 1, 0, 30],
        ]);

        $service = new ExcelPreviewService();
        $preview = $service->preview($path);
        $prompt = $service->formatForPrompt($path);

        $this->assertSame('attendance', $preview['detected_type']);
        $this->assertSame(2, $preview['total_rows']);
        $this->assertStringContainsString('EMP001', $prompt);
        $this->assertStringContainsString('attendance data', $prompt);

        @unlink($path);
    }

    public function test_detects_employee_sheet(): void
    {
        $path = $this->createSpreadsheet([
            ['employee_code', 'employee_name', 'department', 'designation'],
            ['EMP001', 'Rahul', 'IT', 'Developer'],
        ]);

        $service = new ExcelPreviewService();
        $preview = $service->preview($path);

        $this->assertSame('employees', $preview['detected_type']);

        @unlink($path);
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function createSpreadsheet(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $path = sys_get_temp_dir() . '/ai-preview-' . uniqid('', true) . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
