<?php

namespace App\Enums\Setup;

enum CompanySetupStep: string
{
    case OrganisationDetails = 'organisation_details';
    case TaxDetails = 'tax_details';
    case PaySchedule = 'pay_schedule';
    case StatutoryComponents = 'statutory_components';
    case SalaryComponents = 'salary_components';
    case AddEmployees = 'add_employees';
    case OrganisationStructure = 'organisation_structure';

    public function number(): int
    {
        return match ($this) {
            self::OrganisationDetails => 1,
            self::TaxDetails => 2,
            self::PaySchedule => 3,
            self::StatutoryComponents => 4,
            self::SalaryComponents => 5,
            self::AddEmployees => 6,
            self::OrganisationStructure => 7,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::OrganisationDetails => 'Add Organisation Details',
            self::TaxDetails => 'Provide your Tax Details',
            self::PaySchedule => 'Configure your Pay Schedule',
            self::StatutoryComponents => 'Set up Statutory Components',
            self::SalaryComponents => 'Set up Salary Components',
            self::AddEmployees => 'Add Employees',
            self::OrganisationStructure => 'Set up Organisation Structure',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OrganisationDetails => 'Confirm your company name, address, and basic profile so payroll documents are correct.',
            self::TaxDetails => 'Add GST and employer PF / ESI registration details used on payslips and statutory filings.',
            self::PaySchedule => 'Choose how often you run payroll (for example monthly) under compensation settings.',
            self::StatutoryComponents => 'Enable PF, ESI, Professional Tax, and other statutory deductions for this company.',
            self::SalaryComponents => 'Define earnings and deductions that make up employee salary structures.',
            self::AddEmployees => 'Add at least one employee so you can mark attendance and run payroll.',
            self::OrganisationStructure => 'Create departments, designations, and locations used when onboarding staff.',
        };
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::OrganisationDetails,
            self::TaxDetails,
            self::PaySchedule,
            self::StatutoryComponents,
            self::SalaryComponents,
            self::AddEmployees,
            self::OrganisationStructure,
        ];
    }
}
