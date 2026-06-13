<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_code' => strtoupper($this->faker->unique()->bothify('EMP###')),
            'employee_name' => $this->faker->name(),
            'gender' => 'M',
            'father_name' => $this->faker->name('male'),
            'location_id' => Location::factory(),
            'department_id' => Department::factory(),
            'designation_id' => Designation::factory(),
            'doj' => now()->subYear(),
        ];
    }

}
