<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /** @var list<string> */
    private array $designations = [
        'Executive',
        'Senior Associate',
        'Associate',
        'Team Lead',
    ];

    /** @var list<string> */
    private array $firstNames = ['Aarav', 'Priya', 'Rohan', 'Ananya', 'Vikram', 'Neha', 'Karan', 'Sneha'];

    public function run(): void
    {
        Company::whereIn('company_handled_by', CompanySeeder::HANDLER_USER_IDS)
            ->each(function (Company $company) {
                $designationIds = $this->ensureDesignations($company);

                Location::where('company_id', $company->id)->each(function (Location $location) use ($designationIds) {
                    $departments = Department::where('company_id', $location->company_id)
                        ->where('department_name', 'like', $location->location_code . ' - %')
                        ->get();

                    if ($departments->isEmpty()) {
                        return;
                    }

                    $employeeCount = random_int(3, 4);

                    for ($i = 1; $i <= $employeeCount; $i++) {
                        $firstName = $this->firstNames[array_rand($this->firstNames)];
                        $employeeCode = sprintf('%s-E%03d', $location->location_code, $i);

                        Employee::firstOrCreate(
                            ['employee_code' => $employeeCode],
                            [
                                'company_id' => $location->company_id,
                                'employee_name' => $firstName . ' ' . fake()->lastName(),
                                'gender' => ['M', 'F', 'O'][array_rand(['M', 'F', 'O'])],
                                'father_name' => fake()->name('male'),
                                'location_id' => $location->id,
                                'department_id' => $departments->random()->id,
                                'designation_id' => $designationIds[array_rand($designationIds)],
                                'dob' => now()->subYears(random_int(24, 45))->subDays(random_int(0, 364)),
                                'doj' => now()->subYears(random_int(1, 8))->subDays(random_int(0, 364)),
                                'pf_no' => 'PF' . random_int(100000, 999999),
                                'esi_no' => 'ESI' . random_int(100000, 999999),
                                'pay_mode' => 'Bank Transfer',
                                'pf_mode' => 'Standard',
                                'bank_name' => 'HDFC Bank',
                                'bank_account_no' => (string) random_int(100000000000, 999999999999),
                                'bank_ifsc_code' => 'HDFC0001234',
                                'bank_account_type' => 'Savings',
                            ],
                        );
                    }
                });
            });
    }

    /**
     * @return list<int>
     */
    private function ensureDesignations(Company $company): array
    {
        $ids = [];

        foreach ($this->designations as $name) {
            $designation = Designation::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'designation_name' => $name,
                ],
            );
            $ids[] = $designation->id;
        }

        return $ids;
    }
}
