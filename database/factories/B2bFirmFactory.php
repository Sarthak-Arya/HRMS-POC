<?php

namespace Database\Factories;

use App\Models\B2bFirm;
use Illuminate\Database\Eloquent\Factories\Factory;

class B2bFirmFactory extends Factory
{
    protected $model = B2bFirm::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Advisors',
        ];
    }
}
