<?php

namespace App\Services\Payroll;

use App\Exports\SalarySheetExport;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class SalarySheetExporter
{
    public function __construct(
        private readonly SalarySheetService $salarySheetService,
    ) {}

    public function download(
        PayrollRun $run,
        string $groupBy = 'company',
        string $format = 'pdf',
    ): Response|BinaryFileResponse {
        $this->assertValidOptions($groupBy, $format);

        $run->loadMissing('company');
        $sheets = $this->salarySheetService->buildSheets($run, $groupBy);

        if ($sheets === []) {
            throw ValidationException::withMessages([
                'run' => 'No payroll records found for this run.',
            ]);
        }

        $period = Carbon::create($run->year, $run->month)->format('Y-m');

        if (count($sheets) === 1) {
            return $this->downloadSingleSheet($sheets[0], $format, $period);
        }

        return $this->downloadZip($sheets, $format, $period, $groupBy);
    }

    private function assertValidOptions(string $groupBy, string $format): void
    {
        if (! in_array($groupBy, ['company', 'department', 'location'], true)) {
            throw ValidationException::withMessages([
                'group_by' => 'Group by must be company, department, or location.',
            ]);
        }

        if (! in_array($format, ['pdf', 'xlsx'], true)) {
            throw ValidationException::withMessages([
                'format' => 'Format must be pdf or xlsx.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $sheet
     */
    private function downloadSingleSheet(array $sheet, string $format, string $period): Response|BinaryFileResponse
    {
        $filename = sprintf('salary-sheet-%s-%s.%s', $sheet['slug'], $period, $format);

        if ($format === 'xlsx') {
            return Excel::download(new SalarySheetExport($sheet), $filename);
        }

        return $this->pdfResponse(
            View::make('payroll.salary-sheet-pdf', ['sheet' => $sheet])->render(),
            $filename,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $sheets
     */
    private function downloadZip(array $sheets, string $format, string $period, string $groupBy): BinaryFileResponse
    {
        $zipPath = storage_path('app/temp/salary-sheets-'.uniqid('', true).'.zip');
        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0777, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw ValidationException::withMessages([
                'export' => 'Unable to create export archive.',
            ]);
        }

        foreach ($sheets as $sheet) {
            $filename = sprintf('salary-sheet-%s-%s.%s', $sheet['slug'], $period, $format);

            if ($format === 'pdf') {
                $content = $this->renderPdfBinary(
                    View::make('payroll.salary-sheet-pdf', ['sheet' => $sheet])->render(),
                );
            } else {
                $content = Excel::raw(new SalarySheetExport($sheet), \Maatwebsite\Excel\Excel::XLSX);
            }

            $zip->addFromString($filename, $content);
        }

        $zip->close();

        $archiveName = sprintf('salary-sheets-%s-%s.zip', $groupBy, $period);

        return response()->download($zipPath, $archiveName)->deleteFileAfterSend(true);
    }

    private function pdfResponse(string $html, string $filename): Response
    {
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('a4', 'landscape')
                ->download($filename);
        }

        return response($this->renderPdfBinary($html), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function renderPdfBinary(string $html): string
    {
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('a4', 'landscape')
                ->output();
        }

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return $dompdf->output();
        }

        return $html;
    }
}
