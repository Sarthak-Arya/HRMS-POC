<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /** @var list<string> */
    private array $departmentNames = [
        'Human Resources',
        'Finance',
        'Operations',
        'Sales',
        'Engineering',
        'Customer Support',
    ];

    public function run(): void
    {
        CompanySeeder::demoCompaniesQuery()
            ->each(function (Company $company) {
                Location::where('company_id', $company->id)->each(function (Location $location) {
                    $departmentCount = random_int(3, 6);
                    $names = collect($this->departmentNames)->shuffle()->take($departmentCount);

                    foreach ($names as $name) {
                        Department::firstOrCreate(
                            [
                                'company_id' => $location->company_id,
                                'department_name' => $location->location_code . ' - ' . $name,
                            ],
                        );
                    }
                });
            });
    }
}
