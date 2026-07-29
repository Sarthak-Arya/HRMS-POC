<?php

namespace App\Services\Settings\Adapters;

use App\Enums\Settings\CompanySettingsSection;
use App\Models\Company;
use App\Services\Settings\CompanySettingsService;
use App\Support\Settings\CompanySettingsDefaults;
use Illuminate\Support\Arr;

class StatutorySettingsAdapter
{
    public function __construct(
        private readonly CompanySettingsService $settingsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function read(int $companyId): array
    {
        $company = Company::query()->findOrFail($companyId);
        $statutory = $this->settingsService->getSection($companyId, CompanySettingsSection::Statutory);
        $defaults = CompanySettingsDefaults::statutory();

        $epf = array_merge($defaults['epf'], (array) ($statutory['epf'] ?? []));
        $esi = array_merge($defaults['esi'], (array) ($statutory['esi'] ?? []));

        // Keep company table flags as source of truth for enabled state when migrating.
        if (! array_key_exists('enabled', (array) ($statutory['epf'] ?? []))) {
            $epf['enabled'] = (bool) $company->is_pf;
        }
        if (! array_key_exists('enabled', (array) ($statutory['esi'] ?? []))) {
            $esi['enabled'] = (bool) $company->is_esi;
        }

        if (blank($epf['epfNumber']) && filled($company->pf_code)) {
            $epf['epfNumber'] = $company->pf_code;
        }
        if (blank($esi['esiNumber']) && filled($company->esi_code)) {
            $esi['esiNumber'] = $company->esi_code;
        }

        return [
            'epf' => $epf,
            'esi' => $esi,
            'professionalTax' => array_merge(
                $defaults['professionalTax'],
                (array) ($statutory['professionalTax'] ?? []),
            ),
            'labourWelfareFund' => array_merge(
                $defaults['labourWelfareFund'],
                (array) ($statutory['labourWelfareFund'] ?? []),
            ),
            'statutoryBonus' => array_merge(
                $defaults['statutoryBonus'],
                (array) ($statutory['statutoryBonus'] ?? []),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function write(
        int $companyId,
        array $payload,
        int $expectedVersion,
        ?int $actorUserId = null,
        ?string $reason = null,
    ): void {
        $defaults = CompanySettingsDefaults::statutory();
        $normalized = [
            'epf' => array_merge($defaults['epf'], Arr::only((array) ($payload['epf'] ?? []), array_keys($defaults['epf']))),
            'esi' => array_merge($defaults['esi'], Arr::only((array) ($payload['esi'] ?? []), array_keys($defaults['esi']))),
            'professionalTax' => array_merge(
                $defaults['professionalTax'],
                Arr::only((array) ($payload['professionalTax'] ?? []), array_keys($defaults['professionalTax'])),
            ),
            'labourWelfareFund' => array_merge(
                $defaults['labourWelfareFund'],
                Arr::only((array) ($payload['labourWelfareFund'] ?? []), array_keys($defaults['labourWelfareFund'])),
            ),
            'statutoryBonus' => array_merge(
                $defaults['statutoryBonus'],
                Arr::only((array) ($payload['statutoryBonus'] ?? []), array_keys($defaults['statutoryBonus'])),
            ),
        ];

        $this->settingsService->updateSection(
            $companyId,
            CompanySettingsSection::Statutory,
            $normalized,
            $expectedVersion,
            $actorUserId,
            $reason ?? 'Statutory components updated',
        );

        $company = Company::query()->findOrFail($companyId);
        $company->update([
            'is_pf' => (bool) ($normalized['epf']['enabled'] ?? false),
            'is_esi' => (bool) ($normalized['esi']['enabled'] ?? false),
            'pf_code' => $normalized['epf']['epfNumber'] ?: null,
            'esi_code' => $normalized['esi']['esiNumber'] ?: null,
            'pf_contribution' => $this->formatContributionPair(
                $this->ratePercentFromCode((string) $normalized['epf']['employeeContributionRate']),
                $this->ratePercentFromCode((string) $normalized['epf']['employerContributionRate']),
            ),
            'esi_contribution' => $this->formatContributionPair(
                (float) $normalized['esi']['employeeContributionPercent'],
                (float) $normalized['esi']['employerContributionPercent'],
            ),
        ]);
    }

    private function ratePercentFromCode(string $rateCode): float
    {
        return str_starts_with($rateCode, '12_') ? 12.0 : 12.0;
    }

    private function formatContributionPair(float $employee, float $employer): string
    {
        return rtrim(rtrim(number_format($employee, 2, '.', ''), '0'), '.')
            .'/'
            .rtrim(rtrim(number_format($employer, 2, '.', ''), '0'), '.');
    }
}
