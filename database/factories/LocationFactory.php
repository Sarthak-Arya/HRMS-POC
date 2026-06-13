<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'location_name' => $this->faker->city() . ' Office',
            'location_code' => strtoupper($this->faker->lexify('???')),
            'location_address' => $this->faker->streetAddress(),
            'location_city' => $this->faker->city(),
            'location_state' => $this->faker->state(),
            'location_pincode' => $this->faker->postcode(),
            'location_country' => $this->faker->country(),
            'location_phone' => $this->faker->phoneNumber(),
            'location_email' => $this->faker->companyEmail(),
        ];
    }
}
