<?php

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;
use App\Models\Company;
use App\Models\CompensationComponent;
use App\Models\CompensationStructure;
use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\StructureComponent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $legacyColumns = [
        'Basic' => 'basic_salary',
        'HRA' => 'hra',
        'Conveyance' => 'conveyance',
        'CCA' => 'cca',
        'DA' => 'da',
    ];

    public function up(): void
    {
        if (!Schema::hasColumn('employees', 'basic_salary')) {
            return;
        }

        $today = now()->toDateString();

        Company::query()->each(function (Company $company) use ($today) {
            $componentMap = [];
            $order = 1;

            foreach ($this->legacyColumns as $name => $column) {
                $componentMap[$column] = CompensationComponent::create([
                    'company_id' => $company->id,
                    'component_name' => $name,
                    'component_type' => ComponentType::EARNING->value,
                    'default_calculation_type' => CalculationType::FIXED->value,
                    'is_taxable' => true,
                    'is_active' => true,
                    'display_order' => $order++,
                ]);
            }

            $structure = CompensationStructure::create([
                'company_id' => $company->id,
                'structure_name' => 'Standard (Migrated)',
                'description' => 'Auto-migrated from legacy employee salary columns.',
                'effective_from' => $today,
                'is_active' => true,
                'is_default' => true,
            ]);

            $employees = Employee::where('company_id', $company->id)->get();
            $templateValues = $this->averageLegacyValues($employees);

            foreach ($this->legacyColumns as $name => $column) {
                StructureComponent::create([
                    'structure_id' => $structure->id,
                    'component_id' => $componentMap[$column]->id,
                    'value' => $templateValues[$column] ?? 0,
                    'calculation_type' => CalculationType::FIXED->value,
                    'is_mandatory' => strcasecmp($name, 'Basic') === 0,
                    'display_order' => $componentMap[$column]->display_order,
                ]);
            }

            foreach ($employees as $employee) {
                $monthlyGross = 0;
                foreach ($this->legacyColumns as $column) {
                    $monthlyGross += (float) ($employee->{$column} ?? 0);
                }

                if ($monthlyGross <= 0) {
                    continue;
                }

                EmployeeCompensationHistory::create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'structure_id' => $structure->id,
                    'annual_ctc' => round($monthlyGross * 12, 2),
                    'monthly_gross' => round($monthlyGross, 2),
                    'effective_from' => $employee->doj?->toDateString() ?? $today,
                    'revision_reason' => 'Migrated from legacy salary columns',
                ]);
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['basic_salary', 'hra', 'conveyance', 'cca', 'da']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('conveyance', 12, 2)->default(0);
            $table->decimal('cca', 12, 2)->default(0);
            $table->decimal('da', 12, 2)->default(0);
        });
    }

    /**
     * @param \Illuminate\Support\Collection<int, Employee> $employees
     * @return array<string, float>
     */
    private function averageLegacyValues($employees): array
    {
        $totals = array_fill_keys(array_values($this->legacyColumns), 0.0);
        $count = max($employees->count(), 1);

        foreach ($employees as $employee) {
            foreach ($this->legacyColumns as $column) {
                $totals[$column] += (float) ($employee->{$column} ?? 0);
            }
        }

        return array_map(fn ($total) => round($total / $count, 2), $totals);
    }
};
