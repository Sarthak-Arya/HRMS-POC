<?php

namespace App\Support\Payroll;

use App\Enums\Settings\CompanySettingsSection;
use App\Models\Company;
use App\Services\Compensation\StatutoryComplianceCalculator;
use App\Services\Settings\Adapters\StatutorySettingsAdapter;
use App\Services\Settings\CompanySettingsService;

/**
 * Statutory contribution rates for salary sheet summaries.
 * Prefers statutory-components settings; falls back to company profile / company table.
 */
class SalarySheetStatutoryRates
{
    public function __construct(
        private readonly CompanySettingsService $settingsService,
        private readonly StatutorySettingsAdapter $statutoryAdapter,
    ) {}

    /**
     * @return array{
     *     esi_employee_percent: float,
     *     esi_employer_percent: float,
     *     pf_employee_percent: float,
     *     pf_employer_percent: float,
     *     pf_admin_percent: float,
     *     pf_edli_percent: float,
     *     pf_inspection_percent: float,
     *     pf_wage_ceiling: float,
     *     esi_wage_ceiling: float,
     * }
     */
    public function forCompany(Company $company): array
    {
        $statutory = $this->statutoryAdapter->read($company->id);
        $profile = $this->settingsService->getSection($company->id, CompanySettingsSection::CompanyProfile);

        $esiContribution = $profile['esiContribution'] ?? $company->esi_contribution;
        $pfContribution = $profile['pfContribution'] ?? $company->pf_contribution;

        $esiRates = [
            'employee' => (float) ($statutory['esi']['employeeContributionPercent'] ?? 0.75),
            'employer' => (float) ($statutory['esi']['employerContributionPercent'] ?? 3.25),
        ];
        if (! ($statutory['esi']['enabled'] ?? false) && filled($esiContribution)) {
            $esiRates = $this->parseRatePair($esiContribution, 0.75, 3.25);
        }

        $pfRates = $this->parseRatePair($pfContribution, 12.0, 12.0);
        if ($statutory['epf']['enabled'] ?? false) {
            $pfRates = [
                'employee' => 12.0,
                'employer' => 12.0,
            ];
        }

        return [
            'esi_employee_percent' => $esiRates['employee'],
            'esi_employer_percent' => $esiRates['employer'],
            'pf_employee_percent' => $pfRates['employee'],
            'pf_employer_percent' => $pfRates['employer'],
            'pf_admin_percent' => StatutoryComplianceCalculator::PF_ADMIN_PERCENT,
            'pf_edli_percent' => StatutoryComplianceCalculator::EDLI_PERCENT,
            'pf_inspection_percent' => 0.0,
            'pf_wage_ceiling' => (float) ($statutory['epf']['wageCeiling'] ?? StatutoryComplianceCalculator::PF_WAGE_CEILING),
            'esi_wage_ceiling' => (float) ($statutory['esi']['wageCeiling'] ?? StatutoryComplianceCalculator::ESI_WAGE_CEILING),
        ];
    }

    /**
     * @return array{employee: float, employer: float}
     */
    private function parseRatePair(mixed $value, float $defaultEmployee, float $defaultEmployer): array
    {
        if (is_array($value)) {
            return [
                'employee' => (float) ($value['employee'] ?? $value['employee_percent'] ?? $defaultEmployee),
                'employer' => (float) ($value['employer'] ?? $value['employer_percent'] ?? $defaultEmployer),
            ];
        }

        if (is_string($value) && str_contains($value, '/')) {
            [$employee, $employer] = array_map('trim', explode('/', $value, 2));

            return [
                'employee' => (float) $employee,
                'employer' => (float) $employer,
            ];
        }

        if (is_numeric($value)) {
            $rate = (float) $value;

            return ['employee' => $rate, 'employer' => $rate];
        }

        return ['employee' => $defaultEmployee, 'employer' => $defaultEmployer];
    }
}
