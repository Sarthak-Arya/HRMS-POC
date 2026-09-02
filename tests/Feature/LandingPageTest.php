<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_guests_see_the_marketing_landing_page(): void
    {
        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('FlipCore', false);
        $response->assertSee('Close payroll for 150 employees', false);
        $response->assertSee('href="'.route('login').'"', false);
        $response->assertSee('href="'.route('sign-up').'"', false);
    }

    public function test_authenticated_users_are_sent_into_the_app(): void
    {
        $user = User::factory()->companyAdmin()->create();

        $this->actingAs($user)
            ->get(route('landing'))
            ->assertRedirect(route('add-company-details'));
    }
}
