<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Designation;
use Illuminate\Database\Eloquent\Factories\Factory;

class DesignationFactory extends Factory
{
    protected $model = Designation::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'designation_name' => $this->faker->jobTitle(),
        ];
    }
}
