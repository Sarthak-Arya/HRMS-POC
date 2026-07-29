<?php

namespace App\Services\Settings\Validators;

use App\Enums\Settings\CompanySettingsSection;
use App\Models\AttendancePolicy;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CompanySettingsValidator
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validate(CompanySettingsSection $section, array $payload, int $companyId): array
    {
        $rules = match ($section) {
            CompanySettingsSection::CompanyProfile => [
                'timezone' => ['required', 'string', 'max:64'],
                'fiscalYearStartMonth' => ['required', 'integer', 'min:1', 'max:12'],
                'payrollCurrency' => ['required', 'string', 'size:3'],
                'dateFormat' => ['required', 'string', 'max:32'],
                'fieldSeparator' => ['nullable', 'string', 'max:8'],
                'gstNumber' => ['nullable', 'string', 'max:64'],
                'zipCode' => ['nullable', 'string', 'max:16'],
                'state' => ['nullable', 'string', 'max:128'],
                'country' => ['nullable', 'string', 'max:128'],
                'city' => ['nullable', 'string', 'max:128'],
                'addressLine1' => ['nullable', 'string', 'max:255'],
                'addressLine2' => ['nullable', 'string', 'max:255'],
                'businessLocation' => ['nullable', 'string', 'max:128'],
                'industry' => ['nullable', 'string', 'max:128'],
                'logoPath' => ['nullable', 'string', 'max:255'],
                'filingAddress' => ['nullable', 'string', 'max:500'],
                'filingLocationId' => ['nullable', 'integer'],
                'primaryColor' => ['nullable', 'string', 'max:32'],
                'secondaryColor' => ['nullable', 'string', 'max:32'],
                'fontFamily' => ['nullable', 'string', 'max:64'],
                'esiCode' => ['nullable', 'string', 'max:64'],
                'esiContribution' => ['nullable', 'string', 'max:64'],
                'esiCoverageStartDate' => ['nullable', 'string', 'max:32'],
                'esiCoverageEndDate' => ['nullable', 'string', 'max:32'],
                'pfCode' => ['nullable', 'string', 'max:64'],
                'pfContribution' => ['nullable', 'string', 'max:64'],
                'pfCoverageStartDate' => ['nullable', 'string', 'max:32'],
                'pfCoverageEndDate' => ['nullable', 'string', 'max:32'],
            ],
            CompanySettingsSection::Organization => [
                'requireDepartment' => ['required', 'boolean'],
                'requireDesignation' => ['required', 'boolean'],
                'requireLocation' => ['required', 'boolean'],
                'allowDuplicateDepartmentNames' => ['required', 'boolean'],
            ],
            CompanySettingsSection::Attendance => [
                'defaultView' => ['required', 'string', 'in:monthly_summary,daily'],
                'monthLockEnforced' => ['required', 'boolean'],
                'showDailyMarking' => ['required', 'boolean'],
                'defaultPolicyId' => ['nullable', 'integer'],
            ],
            CompanySettingsSection::Compensation => [
                'defaultPayCycle' => ['required', 'string', 'in:monthly,weekly,biweekly'],
                'roundPayrollToNearest' => ['required', 'integer', 'min:1', 'max:1000'],
                'showInactiveComponents' => ['required', 'boolean'],
            ],
            CompanySettingsSection::Reports => [
                'defaultExportFormat' => ['required', 'string', 'in:xlsx,csv'],
                'includeCompanyLogo' => ['required', 'boolean'],
                'defaultTemplateCategory' => ['required', 'string', 'in:all,payroll,attendance,employee'],
            ],
            CompanySettingsSection::Tax => [
                'pan' => ['required', 'string', 'size:10', 'regex:/^[A-Za-z]{5}[0-9]{4}[A-Za-z]$/'],
                'tan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Za-z]{4}[0-9]{5}[A-Za-z]$/'],
                'tdsCircleArea' => ['nullable', 'string', 'max:3'],
                'tdsCircleRange' => ['nullable', 'string', 'max:2'],
                'tdsCircleNumber' => ['nullable', 'string', 'max:3'],
                'tdsCircleAo' => ['nullable', 'string', 'max:2'],
                'taxPaymentFrequency' => ['required', 'string', 'in:monthly,quarterly'],
                'deductorType' => ['required', 'string', 'in:employee,non_employee'],
                'deductorName' => ['nullable', 'string', 'max:255'],
                'deductorFatherName' => ['nullable', 'string', 'max:255'],
            ],
            CompanySettingsSection::Statutory => [
                'epf' => ['required', 'array'],
                'epf.enabled' => ['required', 'boolean'],
                'epf.epfNumber' => ['nullable', 'string', 'max:64'],
                'epf.deductionCycle' => ['required', 'string', 'in:monthly'],
                'epf.employeeContributionRate' => ['required', 'string', 'in:12_actual,12_restricted'],
                'epf.employerContributionRate' => ['required', 'string', 'in:12_actual,12_restricted'],
                'epf.includeEmployerContributionInCtc' => ['required', 'boolean'],
                'epf.includeEdliInCtc' => ['required', 'boolean'],
                'epf.includeAdminChargesInCtc' => ['required', 'boolean'],
                'epf.allowEmployeeRateOverride' => ['required', 'boolean'],
                'epf.proRateRestrictedPfWage' => ['required', 'boolean'],
                'epf.considerAllComponentsWhenBelowCeilingAfterLop' => ['required', 'boolean'],
                'epf.wageCeiling' => ['required', 'numeric', 'min:0'],
                'epf.samplePfWage' => ['required', 'numeric', 'min:0'],
                'esi' => ['required', 'array'],
                'esi.enabled' => ['required', 'boolean'],
                'esi.esiNumber' => ['nullable', 'string', 'max:64'],
                'esi.deductionCycle' => ['required', 'string', 'in:monthly'],
                'esi.employeeContributionPercent' => ['required', 'numeric', 'min:0', 'max:100'],
                'esi.employerContributionPercent' => ['required', 'numeric', 'min:0', 'max:100'],
                'esi.wageCeiling' => ['required', 'numeric', 'min:0'],
                'esi.sampleGrossWage' => ['required', 'numeric', 'min:0'],
                'professionalTax' => ['required', 'array'],
                'professionalTax.enabled' => ['required', 'boolean'],
                'professionalTax.stateCode' => ['nullable', 'string', 'max:8'],
                'professionalTax.deductionCycle' => ['required', 'string', 'in:monthly,half_yearly,yearly'],
                'professionalTax.registrationNumber' => ['nullable', 'string', 'max:64'],
                'professionalTax.slabs' => ['nullable', 'array'],
                'professionalTax.slabs.*.from' => ['nullable', 'numeric', 'min:0'],
                'professionalTax.slabs.*.to' => ['nullable', 'numeric', 'min:0'],
                'professionalTax.slabs.*.amount' => ['nullable', 'numeric', 'min:0'],
                'professionalTax.sampleGrossWage' => ['required', 'numeric', 'min:0'],
                'labourWelfareFund' => ['required', 'array'],
                'labourWelfareFund.enabled' => ['required', 'boolean'],
                'labourWelfareFund.stateCode' => ['nullable', 'string', 'max:8'],
                'labourWelfareFund.deductionCycle' => ['required', 'string', 'in:monthly,half_yearly,yearly'],
                'labourWelfareFund.employeeContribution' => ['required', 'numeric', 'min:0'],
                'labourWelfareFund.employerContribution' => ['required', 'numeric', 'min:0'],
                'labourWelfareFund.sampleEmployees' => ['required', 'integer', 'min:1'],
                'statutoryBonus' => ['required', 'array'],
                'statutoryBonus.enabled' => ['required', 'boolean'],
                'statutoryBonus.bonusPercent' => ['required', 'numeric', 'min:8.33', 'max:20'],
                'statutoryBonus.eligibilityWageCeiling' => ['required', 'numeric', 'min:0'],
                'statutoryBonus.calculationWageCeiling' => ['required', 'numeric', 'min:0'],
                'statutoryBonus.minimumWorkedDays' => ['required', 'integer', 'min:1'],
                'statutoryBonus.sampleMonthlyWage' => ['required', 'numeric', 'min:0'],
            ],
        };

        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        if ($section === CompanySettingsSection::Attendance && ! empty($validated['defaultPolicyId'])) {
            $exists = AttendancePolicy::query()
                ->where('company_id', $companyId)
                ->where('id', $validated['defaultPolicyId'])
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'defaultPolicyId' => 'The selected default policy does not belong to this company.',
                ]);
            }
        }

        return $validated;
    }
}
