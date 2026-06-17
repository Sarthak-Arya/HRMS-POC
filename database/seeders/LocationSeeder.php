<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['city' => 'Mumbai', 'state' => 'Maharashtra', 'pincode' => '400001'],
            ['city' => 'Delhi', 'state' => 'Delhi', 'pincode' => '110001'],
            ['city' => 'Chennai', 'state' => 'Tamil Nadu', 'pincode' => '600001'],
            ['city' => 'Hyderabad', 'state' => 'Telangana', 'pincode' => '500001'],
        ];

        CompanySeeder::demoCompaniesQuery()
            ->each(function (Company $company) use ($cities) {
                $locationCount = random_int(1, 4);
                $companySlug = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $company->company_name), 0, 3));

                for ($i = 1; $i <= $locationCount; $i++) {
                    $city = $cities[($i - 1) % count($cities)];
                    $code = sprintf('%s-%02d', $companySlug, $i);

                    Location::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'location_code' => $code,
                        ],
                        [
                            'location_name' => $company->company_name . ' - ' . $city['city'],
                            'location_address' => random_int(10, 999) . ' Business Park',
                            'location_city' => $city['city'],
                            'location_state' => $city['state'],
                            'location_pincode' => $city['pincode'],
                            'location_country' => 'India',
                            'location_phone' => '9' . random_int(100000000, 999999999),
                            'location_email' => strtolower($code) . '@example.com',
                        ],
                    );
                }
            });
    }
}
