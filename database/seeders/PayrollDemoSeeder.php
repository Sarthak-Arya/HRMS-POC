<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PayrollDemoSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@softui.com'],
            ['name' => 'admin', 'password' => Hash::make('secret')],
        );

        User::firstOrCreate(
            ['email' => 'payroll@softui.com'],
            ['name' => 'payroll_manager', 'password' => Hash::make('secret')],
        );

        $this->call([
            CompanySeeder::class,
            LocationSeeder::class,
            DepartmentSeeder::class,
            EmployeeSeeder::class,
            CompensationSeeder::class,
            PayrollV2Seeder::class,
        ]);
    }
}
