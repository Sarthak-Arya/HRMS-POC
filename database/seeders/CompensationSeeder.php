<?php

namespace Database\Seeders;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;
use App\Enums\Compensation\CompensationScopeType;
use App\Enums\Compensation\OverrideType;
use App\Enums\Compensation\StatutoryComponent;
use App\Models\Company;
use App\Models\CompensationComponent;
use App\Models\CompensationOverride;
use App\Models\CompensationStructure;
use App\Models\CompensationStructureAssignment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\Location;
use App\Models\StructureComponent;
use Illuminate\Database\Seeder;

class CompensationSeeder extends Seeder
{
    public function run(): void
    {
        CompanySeeder::demoCompaniesQuery()
            ->each(function (Company $company) {
                $components = $this->seedComponents($company);
                $standardStructure = $this->seedStructure($company, $components, 'Standard Grade', false, [
                    ['key' => 'basic', 'value' => 30000, 'calc' => CalculationType::FIXED, 'mandatory' => true],
                    ['key' => 'hra', 'value' => 40, 'calc' => CalculationType::PERCENT_BASIC, 'mandatory' => false],
                    ['key' => 'conveyance', 'value' => 1600, 'calc' => CalculationType::FIXED, 'mandatory' => false],
                    ['key' => 'special', 'value' => 5000, 'calc' => CalculationType::FIXED, 'mandatory' => false],
                ]);

                $seniorStructure = $this->seedStructure($company, $components, 'Senior Grade', false, [
                    ['key' => 'basic', 'value' => 50000, 'calc' => CalculationType::FIXED, 'mandatory' => true],
                    ['key' => 'hra', 'value' => 50, 'calc' => CalculationType::PERCENT_BASIC, 'mandatory' => false],
                    ['key' => 'conveyance', 'value' => 2500, 'calc' => CalculationType::FIXED, 'mandatory' => false],
                    ['key' => 'special', 'value' => 12000, 'calc' => CalculationType::FIXED, 'mandatory' => false],
                ]);

                $defaultStructure = $this->seedStructure($company, $components, 'Default Company Structure', true, [
                    ['key' => 'basic', 'value' => 25000, 'calc' => CalculationType::FIXED, 'mandatory' => true],
                    ['key' => 'hra', 'value' => 35, 'calc' => CalculationType::PERCENT_BASIC, 'mandatory' => false],
                    ['key' => 'conveyance', 'value' => 1600, 'calc' => CalculationType::FIXED, 'mandatory' => false],
                    ['key' => 'special', 'value' => 3000, 'calc' => CalculationType::FIXED, 'mandatory' => false],
                    ['key' => 'pf', 'value' => 12, 'calc' => CalculationType::PERCENT_BASIC, 'mandatory' => false],
                ]);

                $this->seedCompanyAssignment($company, $defaultStructure);

                $firstLocation = Location::where('company_id', $company->id)->first();
                if ($firstLocation) {
                    CompensationStructureAssignment::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'scope_type' => CompensationScopeType::LOCATION,
                            'scope_id' => $firstLocation->id,
                            'structure_id' => $standardStructure->id,
                            'effective_from' => now()->subYear()->toDateString(),
                        ],
                        ['effective_to' => null],
                    );
                }

                $firstDepartment = Department::where('company_id', $company->id)->first();
                if ($firstDepartment) {
                    CompensationStructureAssignment::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'scope_type' => CompensationScopeType::DEPARTMENT,
                            'scope_id' => $firstDepartment->id,
                            'structure_id' => $seniorStructure->id,
                            'effective_from' => now()->subMonths(6)->toDateString(),
                        ],
                        ['effective_to' => null],
                    );

                    if (isset($components['hra'])) {
                        CompensationOverride::firstOrCreate(
                            [
                                'company_id' => $company->id,
                                'scope_type' => CompensationScopeType::DEPARTMENT,
                                'scope_id' => $firstDepartment->id,
                                'component_id' => $components['hra']->id,
                                'override_type' => OverrideType::REPLACE,
                                'effective_from' => now()->subMonths(6)->toDateString(),
                            ],
                            [
                                'value' => 55,
                                'calculation_type' => CalculationType::PERCENT_BASIC,
                                'effective_to' => null,
                                'created_by' => $company->company_handled_by,
                            ],
                        );
                    }
                }

                Employee::where('company_id', $company->id)->each(function (Employee $employee, int $index) use ($company, $standardStructure, $seniorStructure, $components) {
                    $structure = $index % 3 === 0 ? $seniorStructure : $standardStructure;
                    $monthlyGross = $this->estimateMonthlyGross($structure);
                    $annualCtc = round($monthlyGross * 12, 2);

                    EmployeeCompensationHistory::firstOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'effective_from' => $employee->doj?->toDateString() ?? now()->subYear()->toDateString(),
                        ],
                        [
                            'company_id' => $company->id,
                            'structure_id' => $structure->id,
                            'annual_ctc' => $annualCtc,
                            'monthly_gross' => $monthlyGross,
                            'effective_to' => null,
                            'revision_reason' => 'Initial seeded compensation',
                            'approved_by' => $company->company_handled_by,
                        ],
                    );

                    if ($index === 0 && isset($components['special'])) {
                        CompensationOverride::firstOrCreate(
                            [
                                'company_id' => $company->id,
                                'scope_type' => CompensationScopeType::EMPLOYEE,
                                'scope_id' => $employee->id,
                                'component_id' => CompensationComponent::where('company_id', $company->id)
                                    ->where('component_name', 'Special Allowance')
                                    ->value('id'),
                                'override_type' => OverrideType::REPLACE,
                                'effective_from' => now()->subMonths(3)->toDateString(),
                            ],
                            [
                                'value' => 8000,
                                'calculation_type' => CalculationType::FIXED,
                                'effective_to' => null,
                                'created_by' => $company->company_handled_by,
                            ],
                        );
                    }
                });
            });
    }

    /**
     * @return array<string, CompensationComponent>
     */
    private function seedComponents(Company $company): array
    {
        $definitions = [
            'basic' => ['name' => 'Basic', 'type' => ComponentType::EARNING, 'calc' => CalculationType::FIXED, 'order' => 1],
            'hra' => ['name' => 'HRA', 'type' => ComponentType::EARNING, 'calc' => CalculationType::PERCENT_BASIC, 'order' => 2],
            'conveyance' => ['name' => 'Conveyance', 'type' => ComponentType::EARNING, 'calc' => CalculationType::FIXED, 'order' => 3],
            'special' => ['name' => 'Special Allowance', 'type' => ComponentType::EARNING, 'calc' => CalculationType::FIXED, 'order' => 4],
            'pf' => ['name' => 'Provident Fund', 'type' => ComponentType::DEDUCTION, 'calc' => CalculationType::PERCENT_BASIC, 'order' => 5, 'statutory' => StatutoryComponent::PF],
        ];

        $components = [];

        foreach ($definitions as $key => $definition) {
            $components[$key] = CompensationComponent::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'component_name' => $definition['name'],
                ],
                [
                    'component_type' => $definition['type'],
                    'default_calculation_type' => $definition['calc'],
                    'statutory_component' => $definition['statutory'] ?? null,
                    'is_taxable' => $definition['type'] === ComponentType::EARNING,
                    'is_active' => true,
                    'display_order' => $definition['order'],
                    'created_by' => $company->company_handled_by,
                ],
            );
        }

        return $components;
    }

    /**
     * @param array<string, CompensationComponent> $components
     * @param list<array{key: string, value: float, calc: CalculationType, mandatory: bool}> $rows
     */
    private function seedStructure(
        Company $company,
        array $components,
        string $name,
        bool $isDefault,
        array $rows,
    ): CompensationStructure {
        $structure = CompensationStructure::firstOrCreate(
            [
                'company_id' => $company->id,
                'structure_name' => $name,
            ],
            [
                'description' => $name . ' compensation template',
                'effective_from' => now()->subYear()->toDateString(),
                'is_active' => true,
                'is_default' => $isDefault,
            ],
        );

        if ($isDefault) {
            CompensationStructure::where('company_id', $company->id)
                ->where('id', '!=', $structure->id)
                ->update(['is_default' => false]);
            $structure->update(['is_default' => true]);
        }

        foreach ($rows as $index => $row) {
            $component = $components[$row['key']];

            StructureComponent::firstOrCreate(
                [
                    'structure_id' => $structure->id,
                    'component_id' => $component->id,
                ],
                [
                    'value' => $row['value'],
                    'calculation_type' => $row['calc'],
                    'is_mandatory' => $row['mandatory'],
                    'display_order' => $index + 1,
                ],
            );
        }

        return $structure;
    }

    private function seedCompanyAssignment(Company $company, CompensationStructure $structure): void
    {
        CompensationStructureAssignment::firstOrCreate(
            [
                'company_id' => $company->id,
                'scope_type' => CompensationScopeType::COMPANY,
                'scope_id' => null,
                'structure_id' => $structure->id,
                'effective_from' => now()->subYear()->toDateString(),
            ],
            ['effective_to' => null],
        );
    }

    private function estimateMonthlyGross(CompensationStructure $structure): float
    {
        $structure->load('structureComponents.component');
        $basic = 0.0;
        $total = 0.0;

        foreach ($structure->structureComponents as $row) {
            if ($row->component?->component_type !== ComponentType::EARNING) {
                continue;
            }

            $calc = $row->calculation_type ?? $row->component->default_calculation_type;
            $value = (float) ($row->value ?? 0);

            if ($calc === CalculationType::FIXED && strcasecmp($row->component->component_name, 'Basic') === 0) {
                $basic = $value;
            }
        }

        foreach ($structure->structureComponents as $row) {
            if ($row->component?->component_type !== ComponentType::EARNING) {
                continue;
            }

            $calc = $row->calculation_type ?? $row->component->default_calculation_type;
            $value = (float) ($row->value ?? 0);

            $total += match ($calc) {
                CalculationType::PERCENT_BASIC => $basic * ($value / 100),
                CalculationType::PERCENT_CTC => 0,
                default => $value,
            };
        }

        return round($total, 2);
    }
}
