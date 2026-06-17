<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PayrollDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            LocationSeeder::class,
            DepartmentSeeder::class,
            EmployeeSeeder::class,
            CompensationSeeder::class,
        ]);
    }
}
