<?php

namespace Database\Seeders;

use App\Services\Auth\RolePermissionSync;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        RolePermissionSync::syncAllRoles();
    }
}
