<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AiE2eAttendanceExcel extends Command
{
    protected $signature = 'ai:e2e-attendance';

    protected $description = 'Run live end-to-end test for AI attendance Excel import';

    public function handle(): int
    {
        $script = base_path('scripts/e2e-ai-attendance-excel.php');
        if (!is_file($script)) {
            $this->error('E2E script not found.');

            return self::FAILURE;
        }

        passthru(PHP_BINARY . ' ' . escapeshellarg($script), $exitCode);

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
