<?php

namespace App\Services\Ai;

use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

/**
 * Reads uploaded spreadsheets and formats a compact preview for the AI agent.
 */
class ExcelPreviewService
{
    private const ATTENDANCE_MARKERS = [
        'month',
        'year',
        'cl',
        'el',
        'sl',
        'tot_dys',
        'total_days',
        'casual_leave',
        'employee_id',
        'employee_code',
    ];

    private const EMPLOYEE_MARKERS = [
        'employee_name',
        'name',
        'department',
        'designation',
        'father_name',
        'gender',
    ];

    /**
     * @return array{
     *   filename: string,
     *   detected_type: string,
     *   headers: array<int, string>,
     *   preview_rows: array<int, array<string, string>>,
     *   total_rows: int,
     *   sheet_name: string
     * }
     */
    public function preview(string $filePath, int $maxRows = 20): array
    {
        if ($filePath === '' || !is_readable($filePath)) {
            throw new RuntimeException('Excel file not found or not readable.');
        }

        $sheets = Excel::toArray(null, $filePath);
        if (empty($sheets) || empty($sheets[0])) {
            throw new RuntimeException('Excel file is empty.');
        }

        $rows = $sheets[0];
        $headerRow = $this->findHeaderRow($rows);
        $headers = array_map(fn ($value) => trim((string) $value), $rows[$headerRow] ?? []);
        $dataRows = array_slice($rows, $headerRow + 1);
        $nonEmptyRows = array_values(array_filter(
            $dataRows,
            fn (array $row) => $this->rowHasData($row),
        ));

        $previewRows = array_slice($nonEmptyRows, 0, $maxRows);

        return [
            'filename' => basename($filePath),
            'detected_type' => $this->detectType($headers),
            'headers' => $headers,
            'preview_rows' => array_map(
                fn (array $row) => $this->formatPreviewRow($headers, $row),
                $previewRows,
            ),
            'total_rows' => count($nonEmptyRows),
            'sheet_name' => 'Sheet1',
        ];
    }

    public function formatForPrompt(string $filePath, int $maxRows = 20): string
    {
        $preview = $this->preview($filePath, $maxRows);
        $typeLabel = match ($preview['detected_type']) {
            'attendance' => 'attendance data',
            'employees' => 'employee data',
            default => 'spreadsheet data',
        };

        $lines = [
            "Attached file: {$preview['filename']}",
            "Detected content: {$typeLabel}",
            "Rows with data: {$preview['total_rows']}",
            'Columns: ' . implode(', ', array_filter($preview['headers'])),
        ];

        if (!empty($preview['preview_rows'])) {
            $lines[] = 'Preview (first rows):';
            foreach ($preview['preview_rows'] as $index => $row) {
                $pairs = [];
                foreach ($row as $header => $value) {
                    if ($value === '') {
                        continue;
                    }
                    $pairs[] = "{$header}={$value}";
                }
                if ($pairs !== []) {
                    $lines[] = '  Row ' . ($index + 1) . ': ' . implode(', ', $pairs);
                }
            }

            if ($preview['total_rows'] > count($preview['preview_rows'])) {
                $remaining = $preview['total_rows'] - count($preview['preview_rows']);
                $lines[] = "  … and {$remaining} more row(s) in the file.";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function findHeaderRow(array $rows): int
    {
        $bestRow = 0;
        $bestScore = -1;

        foreach (array_slice($rows, 0, 10) as $index => $row) {
            $score = count(array_filter($row, fn ($value) => trim((string) $value) !== ''));
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow = $index;
            }
        }

        return $bestRow;
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, mixed> $row
     * @return array<string, string>
     */
    private function formatPreviewRow(array $headers, array $row): array
    {
        $formatted = [];
        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }
            $formatted[$header] = trim((string) ($row[$index] ?? ''));
        }

        return $formatted;
    }

    /**
     * @param array<int, mixed> $row
     */
    private function rowHasData(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $headers
     */
    private function detectType(array $headers): string
    {
        $normalized = array_map(fn ($header) => $this->normalizeHeader($header), $headers);

        $attendanceScore = $this->scoreHeaders($normalized, self::ATTENDANCE_MARKERS);
        $employeeScore = $this->scoreHeaders($normalized, self::EMPLOYEE_MARKERS);

        $hasPeriod = in_array('month', $normalized, true) && in_array('year', $normalized, true);
        if ($hasPeriod && $attendanceScore >= 2) {
            return 'attendance';
        }

        if ($employeeScore >= 2 && $attendanceScore < 2) {
            return 'employees';
        }

        if ($attendanceScore > $employeeScore) {
            return 'attendance';
        }

        if ($employeeScore > 0) {
            return 'employees';
        }

        return 'unknown';
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, string> $markers
     */
    private function scoreHeaders(array $headers, array $markers): int
    {
        $score = 0;
        foreach ($markers as $marker) {
            $normalizedMarker = $this->normalizeHeader($marker);
            foreach ($headers as $header) {
                if ($header === $normalizedMarker || str_contains($header, $normalizedMarker)) {
                    $score++;
                    break;
                }
            }
        }

        return $score;
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = preg_replace('/[\s\-]+/', '_', $header) ?? $header;

        return $header;
    }
}
