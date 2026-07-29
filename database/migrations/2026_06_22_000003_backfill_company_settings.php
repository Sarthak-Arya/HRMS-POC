<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Company;
use App\Services\Settings\CompanySettingsService;

return new class extends Migration
{
    public function up(): void
    {
        $service = app(CompanySettingsService::class);

        Company::query()->pluck('id')->each(function (int $companyId) use ($service) {
            $service->ensureExists($companyId);
        });
    }

    public function down(): void
    {
        // No rollback for data backfill.
    }
};
