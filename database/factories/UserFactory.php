<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\B2bFirm;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if (! $user->roles()->exists()) {
                Role::findOrCreate(UserRole::CompanyAdmin->value, 'web');
                $user->assignRole(UserRole::CompanyAdmin->value);
            }
        });
    }

    public function admin(): static
    {
        return $this->withRole(UserRole::Admin);
    }

    public function payrollManager(): static
    {
        return $this->withRole(UserRole::PayrollManager);
    }

    public function companyAdmin(): static
    {
        return $this->withRole(UserRole::CompanyAdmin);
    }

    public function hrManager(): static
    {
        return $this->withRole(UserRole::HrManager);
    }

    public function accountant(): static
    {
        return $this->withRole(UserRole::Accountant);
    }

    public function viewer(): static
    {
        return $this->withRole(UserRole::Viewer);
    }

    public function employee(): static
    {
        return $this->withRole(UserRole::Employee);
    }

    public function manager(): static
    {
        return $this->withRole(UserRole::Manager);
    }

    public function b2bAdmin(): static
    {
        return $this->withRole(UserRole::B2bAdmin);
    }

    public function b2bStaff(): static
    {
        return $this->withRole(UserRole::B2bStaff);
    }

    public function withRole(UserRole $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            Role::findOrCreate($role->value, 'web');
            $user->syncRoles([$role->value]);
        });
    }

    /**
     * Scope this user as B2B under the given firm (creating one when omitted).
     */
    public function forB2bFirm(B2bFirm|int|null $firm = null): static
    {
        return $this->state(function () use ($firm) {
            $firmId = $firm instanceof B2bFirm
                ? $firm->id
                : ($firm ?? B2bFirm::factory()->create()->id);

            return [
                'b2b_firm_id' => $firmId,
                'company_id' => null,
            ];
        });
    }

    /**
     * Scope this user as B2C for the given company.
     */
    public function forCompany(Company|int $company): static
    {
        return $this->state(function () use ($company) {
            return [
                'company_id' => $company instanceof Company ? $company->id : $company,
                'b2b_firm_id' => null,
            ];
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
