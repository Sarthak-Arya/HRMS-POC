<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Livewire\Auth\SignUp;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SignUpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_new_user_can_access_view_companies_after_signup(): void
    {
        Livewire::test(SignUp::class)
            ->set('name', 'Test User')
            ->set('email', 'newuser@example.com')
            ->set('password', 'password123')
            ->set('role', 'company_admin')
            ->call('register')
            ->assertRedirect(route('view-companies'));

        $user = User::query()->where('email', 'newuser@example.com')->first();

        $this->assertTrue($user->hasRole(UserRole::CompanyAdmin));
        $this->assertTrue($user->hasPermission('companies.view'));

        $this->actingAs($user)
            ->get(route('view-companies'))
            ->assertOk();
    }

    public function test_signup_syncs_permissions_when_role_has_none(): void
    {
        $role = Role::query()->where('name', UserRole::CompanyAdmin->value)->first();
        $role?->syncPermissions([]);

        $this->assertSame(0, Permission::query()->count());

        Livewire::test(SignUp::class)
            ->set('name', 'Sync User')
            ->set('email', 'sync@example.com')
            ->set('password', 'password123')
            ->set('role', 'company_admin')
            ->call('register')
            ->assertRedirect(route('view-companies'));

        $user = User::query()->where('email', 'sync@example.com')->first();

        $this->assertGreaterThan(0, Permission::query()->count());
        $this->assertTrue($user->hasPermission('companies.view'));

        $this->actingAs($user)
            ->get(route('view-companies'))
            ->assertOk();
    }
}
