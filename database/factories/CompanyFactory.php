<?php

namespace Database\Factories;

use App\Models\B2bFirm;
use App\Models\Company;
use App\Models\User;
use App\Services\Settings\CompanySettingsService;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'company_address' => $this->faker->address(),
            'b2b_firm_id' => null,
            'is_esi' => false,
            'is_pf' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Company $company) {
            app(CompanySettingsService::class)->ensureExists($company->id);
        });
    }

    /**
     * Attach the company to a B2B firm (optionally creating one).
     */
    public function forFirm(B2bFirm|int|null $firm = null): static
    {
        return $this->state(function () use ($firm) {
            $firmId = $firm instanceof B2bFirm
                ? $firm->id
                : ($firm ?? B2bFirm::factory()->create()->id);

            return ['b2b_firm_id' => $firmId];
        });
    }

    /**
     * Create a B2C company and optionally scope a user to it.
     */
    public function ownedBy(User $user): static
    {
        return $this->afterCreating(function (Company $company) use ($user) {
            $user->forceFill([
                'company_id' => $company->id,
                'b2b_firm_id' => null,
            ])->save();
        });
    }
}
