<?php

namespace App\Services\Settings;

use App\Enums\Settings\CompanySettingsSection;
use App\Models\CompanySetting;
use App\Services\Settings\Validators\CompanySettingsValidator;
use App\Support\Settings\CompanySettingsDefaults;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanySettingsService
{
    public function __construct(
        private readonly CompanySettingsValidator $validator,
        private readonly CompanySettingsAuditService $auditService,
    ) {}

    public function ensureExists(int $companyId): CompanySetting
    {
        return CompanySetting::query()->firstOrCreate(
            ['company_id' => $companyId],
            [
                'version' => 1,
                'settings_json' => CompanySettingsDefaults::all(),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getForCompany(int $companyId): array
    {
        $record = $this->ensureExists($companyId);

        return $this->mergeWithDefaults($record->settings_json ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSection(int $companyId, CompanySettingsSection $section): array
    {
        $settings = $this->getForCompany($companyId);

        return $settings[$section->value] ?? CompanySettingsDefaults::forSection($section);
    }

    public function currentVersion(int $companyId): int
    {
        return (int) $this->ensureExists($companyId)->version;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateSection(
        int $companyId,
        CompanySettingsSection $section,
        array $payload,
        int $expectedVersion,
        ?int $actorUserId = null,
        ?string $reason = null,
    ): CompanySetting {
        $validated = $this->validator->validate($section, $payload, $companyId);

        return DB::transaction(function () use ($companyId, $section, $validated, $expectedVersion, $actorUserId, $reason) {
            $record = CompanySetting::query()
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                $record = $this->ensureExists($companyId);
                $record = CompanySetting::query()
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            if ((int) $record->version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'version' => 'Settings were updated by another user. Please refresh and try again.',
                ]);
            }

            $before = $this->mergeWithDefaults($record->settings_json ?? []);
            $beforeSection = $before[$section->value] ?? CompanySettingsDefaults::forSection($section);

            $settings = $before;
            $settings[$section->value] = $validated;

            $record->update([
                'settings_json' => $settings,
                'version' => $record->version + 1,
            ]);

            $this->auditService->log(
                $companyId,
                $section,
                $beforeSection,
                $validated,
                $actorUserId,
                $reason,
            );

            return $record->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public function mergeWithDefaults(array $stored): array
    {
        $defaults = CompanySettingsDefaults::all();

        foreach ($defaults as $section => $sectionDefaults) {
            $sectionStored = (array) ($stored[$section] ?? []);

            if ($section === CompanySettingsSection::Statutory->value) {
                $mergedSection = $sectionDefaults;
                foreach ($sectionDefaults as $componentKey => $componentDefaults) {
                    $mergedSection[$componentKey] = array_merge(
                        (array) $componentDefaults,
                        Arr::only(
                            (array) ($sectionStored[$componentKey] ?? []),
                            array_keys((array) $componentDefaults),
                        ),
                    );
                }
                $stored[$section] = $mergedSection;

                continue;
            }

            $stored[$section] = array_merge(
                $sectionDefaults,
                Arr::only($sectionStored, array_keys($sectionDefaults)),
            );
        }

        return $stored;
    }
}
