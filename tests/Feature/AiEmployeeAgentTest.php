<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Services\Ai\AgentOrchestrator;
use App\Services\Ai\OpenRouterClient;
use App\Services\Ai\ToolRegistry;
use App\Services\Ai\Tools\EmployeeToolProvider;
use App\Services\Employee\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiEmployeeAgentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Department $department;
    private Designation $designation;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->company = Company::create([
            'company_name' => 'Test Co',
            'company_address' => '123 Test St',
            'is_esi' => false,
            'is_pf' => false,
            'company_handled_by' => $this->user->id,
        ]);

        $this->department = Department::create([
            'company_id' => $this->company->id,
            'department_name' => 'Engineering',
        ]);

        $this->designation = Designation::create([
            'company_id' => $this->company->id,
            'designation_name' => 'Developer',
        ]);

        $this->location = Location::create([
            'company_id' => $this->company->id,
            'location_name' => 'HQ',
            'location_code' => 'HQ001',
            'location_address' => '',
            'location_city' => '',
            'location_state' => '',
            'location_pincode' => '',
            'location_country' => '',
            'location_phone' => '',
            'location_email' => '',
        ]);
    }

    public function test_search_employees_tool(): void
    {
        Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'EMP001',
            'employee_name' => 'Rahul Sharma',
            'gender' => 'M',
            'father_name' => 'Father Sharma',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'location_id' => $this->location->id,
        ]);

        $tool = EmployeeToolProvider::tools()[0];
        $result = $tool->handle(['search' => 'Rahul'], $this->company->id, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['count']);
        $this->assertSame('EMP001', $result['employees'][0]['employee_code']);
    }

    public function test_create_employee_via_service(): void
    {
        $service = app(EmployeeService::class);
        $result = $service->createFromAgent($this->company->id, [
            'employee_code' => 'EMP100',
            'employee_name' => 'Amit Kumar',
            'department' => 'Sales',
            'designation' => 'Executive',
            'location' => 'Delhi',
            'gender' => 'male',
        ]);

        $this->assertSame('created', $result['action']);
        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP100',
            'employee_name' => 'Amit Kumar',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_update_employee_by_code(): void
    {
        Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'EMP002',
            'employee_name' => 'Priya Singh',
            'gender' => 'F',
            'father_name' => 'Father Singh',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'location_id' => $this->location->id,
        ]);

        $updateTool = collect(EmployeeToolProvider::tools())->first(fn ($t) => $t->name() === 'update_employee');
        $result = $updateTool->handle([
            'employee_code' => 'EMP002',
            'department' => 'Sales',
        ], $this->company->id, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertSame('Sales', $result['employee']['department']);
    }

    public function test_bulk_upsert_employees(): void
    {
        $bulkTool = collect(EmployeeToolProvider::tools())->first(fn ($t) => $t->name() === 'bulk_upsert_employees');
        $result = $bulkTool->handle([
            'employees' => [
                [
                    'employee_code' => 'BULK01',
                    'employee_name' => 'Employee One',
                    'department' => 'HR',
                    'designation' => 'Staff',
                    'location' => 'Mumbai',
                ],
                [
                    'employee_code' => 'BULK02',
                    'employee_name' => 'Employee Two',
                    'department' => 'HR',
                    'designation' => 'Staff',
                    'location' => 'Mumbai',
                ],
            ],
        ], $this->company->id, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['created']);
        $this->assertDatabaseHas('employees', ['employee_code' => 'BULK01']);
        $this->assertDatabaseHas('employees', ['employee_code' => 'BULK02']);
    }

    public function test_cannot_update_employee_from_other_company(): void
    {
        $otherUser = User::factory()->create();
        $otherCompany = Company::create([
            'company_name' => 'Other Co',
            'company_address' => '456 Other St',
            'is_esi' => false,
            'is_pf' => false,
            'company_handled_by' => $otherUser->id,
        ]);

        $otherDept = Department::create([
            'company_id' => $otherCompany->id,
            'department_name' => 'Other Dept',
        ]);
        $otherDes = Designation::create([
            'company_id' => $otherCompany->id,
            'designation_name' => 'Other Role',
        ]);
        $otherLoc = Location::create([
            'company_id' => $otherCompany->id,
            'location_name' => 'Other Loc',
            'location_code' => 'OL001',
            'location_address' => '',
            'location_city' => '',
            'location_state' => '',
            'location_pincode' => '',
            'location_country' => '',
            'location_phone' => '',
            'location_email' => '',
        ]);

        Employee::create([
            'company_id' => $otherCompany->id,
            'employee_code' => 'OTHER01',
            'employee_name' => 'Other Employee',
            'gender' => 'M',
            'department_id' => $otherDept->id,
            'designation_id' => $otherDes->id,
            'location_id' => $otherLoc->id,
        ]);

        $updateTool = collect(EmployeeToolProvider::tools())->first(fn ($t) => $t->name() === 'update_employee');
        $result = $updateTool->handle([
            'employee_code' => 'OTHER01',
            'department' => 'Hacked',
        ], $this->company->id, $this->user->id);

        $this->assertFalse($result['success']);
    }

    public function test_agent_orchestrator_with_mocked_llm(): void
    {
        $mockClient = Mockery::mock(OpenRouterClient::class);
        $mockClient->shouldReceive('chat')
            ->once()
            ->andReturn([
                'content' => 'Employee EMP100 has been created successfully.',
            ]);

        $registry = new ToolRegistry();
        $registry->registerMany(EmployeeToolProvider::tools());

        $orchestrator = new AgentOrchestrator($mockClient, $registry);

        $result = $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            'Hello'
        );

        $this->assertStringContainsString('created', strtolower($result['reply']));
        $this->assertArrayHasKey('conversation_id', $result);
    }

    public function test_agent_executes_tool_call_from_mocked_llm(): void
    {
        $mockClient = Mockery::mock(OpenRouterClient::class);

        $mockClient->shouldReceive('chat')
            ->once()
            ->andReturn([
                'content' => null,
                'tool_calls' => [[
                    'id' => 'call_1',
                    'type' => 'function',
                    'function' => [
                        'name' => 'create_employee',
                        'arguments' => json_encode([
                            'employee_code' => 'AI001',
                            'employee_name' => 'AI Created',
                            'department' => 'IT',
                            'designation' => 'Dev',
                            'location' => 'HQ',
                        ]),
                    ],
                ]],
            ]);

        $mockClient->shouldReceive('chat')
            ->once()
            ->andReturn([
                'content' => 'Created employee AI001.',
            ]);

        $registry = new ToolRegistry();
        $registry->registerMany(EmployeeToolProvider::tools());
        $orchestrator = new AgentOrchestrator($mockClient, $registry);

        $result = $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            'Add employee AI Created with code AI001'
        );

        $this->assertSame('Created employee AI001.', $result['reply']);
        $this->assertDatabaseHas('employees', [
            'employee_code' => 'AI001',
            'employee_name' => 'AI Created',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
