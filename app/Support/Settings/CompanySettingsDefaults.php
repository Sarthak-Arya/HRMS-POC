<?php

namespace App\Support\Settings;

use App\Enums\Settings\CompanySettingsSection;

class CompanySettingsDefaults
{
    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return [
            CompanySettingsSection::CompanyProfile->value => self::companyProfile(),
            CompanySettingsSection::Organization->value => self::organization(),
            CompanySettingsSection::Attendance->value => self::attendance(),
            CompanySettingsSection::Compensation->value => self::compensation(),
            CompanySettingsSection::Reports->value => self::reports(),
            CompanySettingsSection::Tax->value => self::tax(),
            CompanySettingsSection::Statutory->value => self::statutory(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function forSection(CompanySettingsSection $section): array
    {
        return self::all()[$section->value];
    }

    /**
     * @return array<string, mixed>
     */
    public static function companyProfile(): array
    {
        return [
            'timezone' => 'Asia/Kolkata',
            'fiscalYearStartMonth' => 4,
            'payrollCurrency' => 'INR',
            'dateFormat' => 'd/m/Y',
            'fieldSeparator' => '/',
            'gstNumber' => null,
            'zipCode' => null,
            'state' => null,
            'country' => 'India',
            'city' => null,
            'addressLine1' => null,
            'addressLine2' => null,
            'businessLocation' => 'India',
            'industry' => null,
            'logoPath' => null,
            'filingAddress' => null,
            'filingLocationId' => null,
            'primaryColor' => '#0058be',
            'secondaryColor' => '#131b2e',
            'fontFamily' => 'Inter',
            'esiCode' => null,
            'esiContribution' => null,
            'esiCoverageStartDate' => null,
            'esiCoverageEndDate' => null,
            'pfCode' => null,
            'pfContribution' => null,
            'pfCoverageStartDate' => null,
            'pfCoverageEndDate' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        return [
            'requireDepartment' => true,
            'requireDesignation' => true,
            'requireLocation' => false,
            'allowDuplicateDepartmentNames' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function attendance(): array
    {
        return [
            'defaultView' => 'monthly_summary',
            'monthLockEnforced' => true,
            'showDailyMarking' => true,
            'defaultPolicyId' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function compensation(): array
    {
        return [
            'defaultPayCycle' => 'monthly',
            'roundPayrollToNearest' => 1,
            'showInactiveComponents' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function reports(): array
    {
        return [
            'defaultExportFormat' => 'xlsx',
            'includeCompanyLogo' => false,
            'defaultTemplateCategory' => 'all',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function tax(): array
    {
        return [
            'pan' => null,
            'tan' => null,
            'tdsCircleArea' => null,
            'tdsCircleRange' => null,
            'tdsCircleNumber' => null,
            'tdsCircleAo' => null,
            'taxPaymentFrequency' => 'monthly',
            'deductorType' => 'employee',
            'deductorName' => null,
            'deductorFatherName' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function statutory(): array
    {
        return [
            'epf' => [
                'enabled' => false,
                'epfNumber' => null,
                'deductionCycle' => 'monthly',
                'employeeContributionRate' => '12_actual',
                'employerContributionRate' => '12_actual',
                'includeEmployerContributionInCtc' => true,
                'includeEdliInCtc' => false,
                'includeAdminChargesInCtc' => false,
                'allowEmployeeRateOverride' => false,
                'proRateRestrictedPfWage' => false,
                'considerAllComponentsWhenBelowCeilingAfterLop' => true,
                'wageCeiling' => 15000,
                'samplePfWage' => 20000,
            ],
            'esi' => [
                'enabled' => false,
                'esiNumber' => null,
                'deductionCycle' => 'monthly',
                'employeeContributionPercent' => 0.75,
                'employerContributionPercent' => 3.25,
                'wageCeiling' => 21000,
                'sampleGrossWage' => 20000,
            ],
            'professionalTax' => [
                'enabled' => false,
                'stateCode' => null,
                'deductionCycle' => 'monthly',
                'registrationNumber' => null,
                'slabs' => [],
                'sampleGrossWage' => 25000,
            ],
            'labourWelfareFund' => [
                'enabled' => false,
                'stateCode' => null,
                'deductionCycle' => 'half_yearly',
                'employeeContribution' => 0,
                'employerContribution' => 0,
                'sampleEmployees' => 1,
            ],
            'statutoryBonus' => [
                'enabled' => false,
                'bonusPercent' => 8.33,
                'eligibilityWageCeiling' => 21000,
                'calculationWageCeiling' => 7000,
                'minimumWorkedDays' => 30,
                'sampleMonthlyWage' => 15000,
            ],
        ];
    }
}
