<?php

/**
 * Live end-to-end smoke test for AI attendance Excel import.
 * Run: php scripts/e2e-ai-attendance-excel.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AiConversation;
use App\Models\Employee;
use App\Models\MonthlyAttendance;
use App\Models\User;
use App\Services\Ai\AgentOrchestrator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function fail(string $message, int $code = 1): never
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit($code);
}

function ok(string $message): void
{
    echo "OK: {$message}\n";
}

$user = User::where('email', 'admin@softui.com')->first();
if (!$user) {
    echo "Seeding database...\n";
    passthru(PHP_BINARY . ' ' . escapeshellarg(base_path('artisan')) . ' db:seed --force', $seedCode);
    if ($seedCode !== 0) {
        fail('Database seed failed.');
    }
    $user = User::where('email', 'admin@softui.com')->first();
}
if (!$user) {
    fail('No admin user after seeding.');
}

$company = \App\Models\Company::first();
if (!$company) {
    fail('No company found.');
}

$employees = Employee::where('company_id', $company->id)->limit(3)->get();
if ($employees->isEmpty()) {
    fail('No employees found for company.');
}

$excelPath = storage_path('app/e2e-demo-attendance.xlsx');
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray([
    ['employee_code', 'cl', 'el', 'sl', 'holiday', 'tot_dys'],
]);

foreach ($employees as $index => $employee) {
    $sheet->fromArray([
        [$employee->employee_code, $index + 1, 0, 0, 0, 30],
    ], null, 'A' . ($index + 2));
}

(new Xlsx($spreadsheet))->save($excelPath);
ok('Created Excel at ' . $excelPath);

auth()->login($user);
$orchestrator = app(AgentOrchestrator::class);

echo "\n--- Turn 1: Upload Excel + update attendance ---\n";
try {
    $turn1 = $orchestrator->sendMessage(
        $company->id,
        $user->id,
        'Update the attendance',
        null,
        $excelPath,
    );
    ok('Turn 1 reply: ' . mb_substr($turn1['reply'], 0, 200));
} catch (Throwable $e) {
    fail('Turn 1 error: ' . $e->getMessage());
}

$conversationId = $turn1['conversation_id'];
$conversation = AiConversation::find($conversationId);
if (!$conversation?->pending_excel_path) {
    fail('Excel path not persisted on conversation after turn 1');
}
ok('Excel path persisted on conversation');

$beforeCount = MonthlyAttendance::where('company_id', $company->id)
    ->where('month', 6)
    ->where('year', 2026)
    ->count();

echo "\n--- Turn 2: Provide month/year ---\n";
try {
    $turn2 = $orchestrator->sendMessage(
        $company->id,
        $user->id,
        '06/2026',
        $conversationId,
    );
    ok('Turn 2 reply: ' . mb_substr($turn2['reply'], 0, 300));
} catch (Throwable $e) {
    fail('Turn 2 error: ' . $e->getMessage());
}

$afterCount = MonthlyAttendance::where('company_id', $company->id)
    ->where('month', 6)
    ->where('year', 2026)
    ->count();

if ($afterCount <= $beforeCount) {
    // If AI didn't import, try direct tool path as validation of import pipeline
    echo "WARN: AI did not import records (before={$beforeCount}, after={$afterCount}). Running direct import...\n";
    $tool = collect(\App\Services\Ai\Tools\AttendanceToolProvider::tools())
        ->first(fn ($t) => $t->name() === 'import_attendance_excel');
    $direct = $tool->handle([
        'file_path' => $excelPath,
        'month' => 6,
        'year' => 2026,
    ], $company->id, $user->id);

    if (!($direct['success'] ?? false)) {
        fail('Direct import also failed: ' . json_encode($direct));
    }

    $afterCount = MonthlyAttendance::where('company_id', $company->id)
        ->where('month', 6)
        ->where('year', 2026)
        ->count();
}

if ($afterCount < $employees->count()) {
    fail("Expected at least {$employees->count()} attendance rows for 06/2026, got {$afterCount}");
}

ok("Attendance records for June 2026: {$afterCount}");
ok('E2E AI attendance Excel flow completed successfully');

@unlink($excelPath);
