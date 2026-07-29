<?php

namespace App\Services\Setup;

use App\Enums\Compensation\StatutoryComponent;
use App\Enums\Settings\CompanySettingsSection;
use App\Enums\Setup\CompanySetupStep;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\CompensationComponent;
use App\Models\CompensationStructure;
use App\Models\Employee;
use App\Services\Settings\Adapters\CompanyProfileSettingsAdapter;
use App\Services\Settings\CompanySettingsService;

class CompanySetupProgressService
{
    public function __construct(
        private readonly CompanySettingsService $settingsService,
        private readonly CompanyProfileSettingsAdapter $profileAdapter,
    ) {}

    /**
     * @return array{
     *     company_id: int,
     *     company_name: string,
     *     completed_count: int,
     *     total_count: int,
     *     percent: int,
     *     is_complete: bool,
     *     steps: list<array<string, mixed>>
     * }
     */
    public function forCompany(Company|int $company): array
    {
        $company = $company instanceof Company
            ? $company
            : Company::query()->findOrFail($company);

        $profile = $this->profileAdapter->read($company->id);
        $compensationPrefs = $this->settingsService->getSection(
            $company->id,
            CompanySettingsSection::Compensation,
        );

        $employeeCount = Employee::query()->where('company_id', $company->id)->count();
        $departmentCount = $company->departments()->count();
        $designationCount = $company->designations()->count();
        $earningComponentCount = CompensationComponent::query()
            ->where('company_id', $company->id)
            ->whereNull('statutory_component')
            ->count();
        $structureCount = CompensationStructure::query()
            ->where('company_id', $company->id)
            ->count();
        $policyCount = AttendancePolicy::query()
            ->where('company_id', $company->id)
            ->count();

        $statutoryStatus = $this->statutoryStatus($company);
        $steps = [];

        foreach (CompanySetupStep::ordered() as $step) {
            $completed = match ($step) {
                CompanySetupStep::OrganisationDetails => $this->organisationDetailsComplete($company, $profile),
                CompanySetupStep::TaxDetails => $this->taxDetailsComplete($company, $profile),
                CompanySetupStep::PaySchedule => filled($compensationPrefs['defaultPayCycle'] ?? null),
                CompanySetupStep::StatutoryComponents => $statutoryStatus['complete'],
                CompanySetupStep::SalaryComponents => $earningComponentCount > 0 || $structureCount > 0,
                CompanySetupStep::AddEmployees => $employeeCount > 0,
                CompanySetupStep::OrganisationStructure => $departmentCount > 0 && $designationCount > 0,
            };

            $steps[] = [
                'key' => $step->value,
                'number' => $step->number(),
                'title' => $step->title(),
                'description' => $step->description(),
                'completed' => $completed,
                'action_url' => $this->actionUrl($company->id, $step),
                'action_label' => $completed ? 'Review' : 'Complete Now',
                'substeps' => $step === CompanySetupStep::StatutoryComponents
                    ? $statutoryStatus['items']
                    : [],
            ];
        }

        $completedCount = count(array_filter($steps, fn (array $step) => $step['completed']));
        $totalCount = count($steps);

        return [
            'company_id' => $company->id,
            'company_name' => $company->company_name,
            'completed_count' => $completedCount,
            'total_count' => $totalCount,
            'percent' => $totalCount > 0 ? (int) round(($completedCount / $totalCount) * 100) : 0,
            'is_complete' => $completedCount === $totalCount,
            'meta' => [
                'employee_count' => $employeeCount,
                'department_count' => $departmentCount,
                'designation_count' => $designationCount,
                'earning_component_count' => $earningComponentCount,
                'structure_count' => $structureCount,
                'policy_count' => $policyCount,
            ],
            'steps' => $steps,
        ];
    }

    public function isSetupComplete(Company|int $company): bool
    {
        return $this->forCompany($company)['is_complete'];
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function organisationDetailsComplete(Company $company, array $profile): bool
    {
        return filled($company->company_name)
            && filled($company->company_address)
            && filled($profile['preferences']['timezone'] ?? null)
            && filled($profile['preferences']['payrollCurrency'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function taxDetailsComplete(Company $company, array $profile): bool
    {
        $hasGst = filled($profile['gstNumber'] ?? null);
        $pfOk = ! $company->is_pf || filled($profile['pfCode'] ?? null);
        $esiOk = ! $company->is_esi || filled($profile['esiCode'] ?? null);

        return $hasGst && $pfOk && $esiOk;
    }

    /**
     * @return array{complete: bool, items: list<array{key: string, label: string, completed: bool}>}
     */
    private function statutoryStatus(Company $company): array
    {
        $configured = CompensationComponent::query()
            ->where('company_id', $company->id)
            ->whereNotNull('statutory_component')
            ->pluck('statutory_component')
            ->map(fn ($value) => $value instanceof StatutoryComponent ? $value->value : (string) $value)
            ->unique()
            ->all();

        $hasPf = in_array(StatutoryComponent::PF->value, $configured, true);
        $hasEsic = in_array(StatutoryComponent::ESIC->value, $configured, true);
        $hasLwf = in_array(StatutoryComponent::LWF->value, $configured, true);
        $hasPt = in_array(StatutoryComponent::PT->value, $configured, true);

        $items = [
            [
                'key' => StatutoryComponent::PF->value,
                'label' => "Employees' Provident Fund",
                'completed' => $hasPf,
            ],
            [
                'key' => StatutoryComponent::ESIC->value,
                'label' => "Employees' State Insurance",
                'completed' => $hasEsic,
            ],
            [
                'key' => StatutoryComponent::LWF->value,
                'label' => 'Labour Welfare Fund',
                'completed' => $hasLwf,
            ],
            [
                'key' => StatutoryComponent::PT->value,
                'label' => 'Professional Tax',
                'completed' => $hasPt,
            ],
        ];

        $pfSatisfied = ! $company->is_pf || $hasPf;
        $esiSatisfied = ! $company->is_esi || $hasEsic;
        $hasAnyStatutory = $hasPf || $hasEsic || $hasLwf || $hasPt;

        // Always require at least one statutory compensation component. When PF/ESI
        // are enabled on the company, those specific components must also exist.
        return [
            'complete' => $pfSatisfied && $esiSatisfied && $hasAnyStatutory,
            'items' => $items,
        ];
    }

    private function actionUrl(int $companyId, CompanySetupStep $step): string
    {
        return match ($step) {
            CompanySetupStep::OrganisationDetails => route('settings', [
                'company_id' => $companyId,
                'category' => 'organization-profile',
            ]),
            CompanySetupStep::TaxDetails => route('settings', [
                'company_id' => $companyId,
                'category' => 'organization-profile',
            ]),
            CompanySetupStep::PaySchedule => route('settings', [
                'company_id' => $companyId,
                'category' => 'compensation',
            ]),
            CompanySetupStep::StatutoryComponents => route('settings', [
                'company_id' => $companyId,
                'category' => 'statutory',
            ]),
            CompanySetupStep::SalaryComponents => route('compensation', [
                'company_id' => $companyId,
            ]),
            CompanySetupStep::AddEmployees => route('add-employee-details', [
                'company_id' => $companyId,
            ]),
            CompanySetupStep::OrganisationStructure => route('settings', [
                'company_id' => $companyId,
                'category' => 'organization-profile',
            ]),
        };
    }
}
