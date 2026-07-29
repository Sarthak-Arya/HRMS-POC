<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompensationComponent;
use App\Models\User;
use App\Services\Compensation\CompensationComponentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompensationComponentTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
        $this->company = Company::factory()->ownedBy($user)->create();
    }

    public function test_it_creates_component_scoped_to_company(): void
    {
        $component = app(CompensationComponentService::class)->create($this->company->id, [
            'component_name' => 'Basic',
            'component_type' => 'EARNING',
            'default_calculation_type' => 'FIXED',
            'default_value' => 25000,
            'is_taxable' => true,
            'is_active' => true,
            'display_order' => 1,
        ]);

        $this->assertDatabaseHas('compensation_components', [
            'id' => $component->id,
            'company_id' => $this->company->id,
            'component_name' => 'Basic',
        ]);
    }

    public function test_it_rejects_duplicate_component_names_per_company(): void
    {
        CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'Basic',
            'component_type' => 'EARNING',
            'default_calculation_type' => 'FIXED',
        ]);

        $this->expectException(ValidationException::class);

        app(CompensationComponentService::class)->create($this->company->id, [
            'component_name' => 'Basic',
            'component_type' => 'EARNING',
            'default_calculation_type' => 'FIXED',
        ]);
    }

    public function test_it_creates_payroll_adjustment_component(): void
    {
        $component = app(CompensationComponentService::class)->create($this->company->id, [
            'component_name' => 'Performance Bonus',
            'component_type' => 'EARNING',
            'default_calculation_type' => 'FIXED',
            'is_payroll_adjustment' => true,
            'is_taxable' => true,
            'is_active' => true,
            'display_order' => 10,
        ]);

        $this->assertTrue($component->is_payroll_adjustment);
        $this->assertSame('FIXED', $component->default_calculation_type->value);
        $this->assertNull($component->default_value);
        $this->assertNull($component->statutory_component);
    }
}
