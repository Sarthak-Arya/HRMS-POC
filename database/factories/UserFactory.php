<?php

namespace Database\Factories;

use App\Enums\UserRole;
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

    public function withRole(UserRole $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            Role::findOrCreate($role->value, 'web');
            $user->syncRoles([$role->value]);
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
