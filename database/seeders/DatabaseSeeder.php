<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@softui.com'],
            [
                'name' => 'admin',
                'password' => Hash::make('secret'),
            ],
        );
        $admin->syncRoles([UserRole::Admin->value]);

        $payrollManager = User::firstOrCreate(
            ['email' => 'payroll@softui.com'],
            [
                'name' => 'payroll_manager',
                'password' => Hash::make('secret'),
            ],
        );
        $payrollManager->syncRoles([UserRole::PayrollManager->value]);

        $this->call(PayrollDemoSeeder::class);
    }
}
