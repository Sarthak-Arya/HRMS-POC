<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MonthlyAttendance;
use App\Models\User;
use App\Services\Ai\AgentOrchestrator;
use App\Services\Ai\ExcelPreviewService;
use App\Services\Ai\OpenRouterClient;
use App\Services\Ai\ToolRegistry;
use App\Services\Ai\Tools\AttendanceToolProvider;
use App\Services\Attendance\AttendanceService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AiAttendanceAgentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->hrManager()->create();
        $this->actingAs($this->user);

        $this->company = Company::create([
            'company_name' => 'Test Co',
            'company_address' => '123 Test St',
            'is_esi' => false,
            'is_pf' => false,
            'company_handled_by' => $this->user->id,
        ]);

        $department = Department::create([
            'company_id' => $this->company->id,
            'department_name' => 'Engineering',
        ]);

        $designation = Designation::create([
            'company_id' => $this->company->id,
            'designation_name' => 'Developer',
        ]);

        $location = Location::create([
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

        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'EMP001',
            'employee_name' => 'Rahul Sharma',
            'gender' => 'M',
            'father_name' => 'Father Sharma',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);
    }

    public function test_upsert_attendance_creates_record(): void
    {
        $tool = collect(AttendanceToolProvider::tools())->first(fn ($t) => $t->name() === 'upsert_attendance');
        $result = $tool->handle([
            'employee_code' => 'EMP001',
            'month' => 6,
            'year' => 2026,
            'cl' => 1,
            'el' => 0,
            'sl' => 0,
            'holiday' => 0,
            'tot_dys' => 30,
        ], $this->company->id, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertSame('created', $result['action']);
        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'month' => 6,
            'year' => 2026,
            'casual_leave' => 1,
            'total_days' => 30,
            'worked_days' => 29,
        ]);
    }

    public function test_upsert_attendance_updates_existing_record(): void
    {
        MonthlyAttendance::create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'month' => 6,
            'year' => 2026,
            'casual_leave' => 1,
            'earned_leave' => 0,
            'sick_leave' => 0,
            'holiday' => 0,
            'esi_la' => 0,
            'total_days' => 30,
            'worked_days' => 29,
        ]);

        $tool = collect(AttendanceToolProvider::tools())->first(fn ($t) => $t->name() === 'upsert_attendance');
        $result = $tool->handle([
            'employee_code' => 'EMP001',
            'month' => 6,
            'year' => 2026,
            'cl' => 2,
            'tot_dys' => 30,
        ], $this->company->id, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertSame('updated', $result['action']);
        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id,
            'casual_leave' => 2,
            'worked_days' => 28,
        ]);
    }

    public function test_get_attendance_tool(): void
    {
        MonthlyAttendance::create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'month' => 6,
            'year' => 2026,
            'casual_leave' => 1,
            'earned_leave' => 0,
            'sick_leave' => 0,
            'holiday' => 0,
            'esi_la' => 0,
            'total_days' => 30,
            'worked_days' => 29,
        ]);

        $tool = collect(AttendanceToolProvider::tools())->first(fn ($t) => $t->name() === 'get_attendance');
        $result = $tool->handle([
            'employee_code' => 'EMP001',
            'month' => 6,
            'year' => 2026,
        ], $this->company->id, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertSame('EMP001', $result['attendance']['employee_code']);
        $this->assertSame(1.0, $result['attendance']['cl']);
    }

    public function test_viewer_cannot_manage_attendance_via_tool(): void
    {
        $viewer = User::factory()->viewer()->create();

        $tool = collect(AttendanceToolProvider::tools())->first(fn ($t) => $t->name() === 'upsert_attendance');
        $result = $tool->handle([
            'employee_code' => 'EMP001',
            'month' => 6,
            'year' => 2026,
            'tot_dys' => 30,
        ], $this->company->id, $viewer->id);

        $this->assertFalse($result['success']);
        $this->assertDatabaseMissing('attendance', [
            'employee_id' => $this->employee->id,
            'month' => 6,
            'year' => 2026,
        ]);
    }

    public function test_agent_executes_upsert_attendance_from_mocked_llm(): void
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
                        'name' => 'upsert_attendance',
                        'arguments' => json_encode([
                            'employee_code' => 'EMP001',
                            'month' => 6,
                            'year' => 2026,
                            'cl' => 1,
                            'tot_dys' => 30,
                        ]),
                    ],
                ]],
            ]);

        $mockClient->shouldReceive('chat')
            ->once()
            ->andReturn([
                'content' => 'Attendance updated for EMP001.',
            ]);

        $registry = new ToolRegistry();
        $registry->registerMany(AttendanceToolProvider::tools());
        $orchestrator = new AgentOrchestrator($mockClient, $registry, app(ExcelPreviewService::class));

        $result = $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            'Set Rahul attendance for June 2026: 1 CL, 30 total days'
        );

        $this->assertSame('Attendance updated for EMP001.', $result['reply']);
        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id,
            'casual_leave' => 1,
            'total_days' => 30,
        ]);
    }

    public function test_attendance_service_bulk_upsert(): void
    {
        $service = app(AttendanceService::class);
        $result = $service->bulkUpsertFromAgent($this->company->id, [
            [
                'employee_code' => 'EMP001',
                'month' => 6,
                'year' => 2026,
                'cl' => 1,
                'tot_dys' => 30,
            ],
        ]);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['failed']);
    }

    public function test_agent_receives_excel_preview_with_user_prompt(): void
    {
        $path = sys_get_temp_dir() . '/attendance-' . uniqid('', true) . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['employee_code', 'month', 'year', 'cl', 'tot_dys'],
            ['EMP001', 6, 2026, 2, 30],
        ]);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $mockClient = Mockery::mock(OpenRouterClient::class);
        $mockClient->shouldReceive('chat')
            ->once()
            ->withArgs(function (array $messages) use ($path) {
                $lastUser = collect($messages)->reverse()->first(fn ($message) => $message['role'] === 'user');

                return str_contains($lastUser['content'], 'Update attendance from this file')
                    && str_contains($lastUser['content'], 'EMP001')
                    && str_contains($lastUser['content'], $path);
            })
            ->andReturn(['content' => 'I can see the attendance rows in your file.']);

        $registry = new ToolRegistry();
        $registry->registerMany(AttendanceToolProvider::tools());
        $orchestrator = new AgentOrchestrator($mockClient, $registry, app(ExcelPreviewService::class));

        $result = $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            'Update attendance from this file',
            null,
            $path,
        );

        $this->assertStringContainsString('attendance rows', strtolower($result['reply']));

        @unlink($path);
    }

    public function test_import_attendance_excel_tool(): void
    {
        $path = sys_get_temp_dir() . '/attendance-import-' . uniqid('', true) . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['employee_code', 'month', 'year', 'cl', 'tot_dys'],
            ['EMP001', 6, 2026, 2, 30],
        ]);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $tool = collect(AttendanceToolProvider::tools())->first(fn ($t) => $t->name() === 'import_attendance_excel');
        $result = $tool->handle(['file_path' => $path], $this->company->id, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id,
            'casual_leave' => 2,
            'total_days' => 30,
        ]);

        @unlink($path);
    }

    public function test_follow_up_message_keeps_excel_from_conversation(): void
    {
        $path = sys_get_temp_dir() . '/attendance-persist-' . uniqid('', true) . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['employee_code', 'cl', 'tot_dys'],
            ['EMP001', 2, 30],
        ]);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $mockClient = Mockery::mock(OpenRouterClient::class);
        $registry = new ToolRegistry();
        $registry->registerMany(AttendanceToolProvider::tools());
        $orchestrator = new AgentOrchestrator($mockClient, $registry, app(ExcelPreviewService::class));

        $mockClient->shouldReceive('chat')
            ->once()
            ->andReturn(['content' => 'Which month and year?']);

        $first = $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            'Update the attendance',
            null,
            $path,
        );

        $mockClient->shouldReceive('chat')
            ->once()
            ->withArgs(function (array $messages) use ($path) {
                $lastUser = collect($messages)->reverse()->first(fn ($message) => $message['role'] === 'user');

                return str_contains($lastUser['content'], '06/2026')
                    && str_contains($lastUser['content'], 'EMP001')
                    && str_contains($lastUser['content'], $path)
                    && str_contains($lastUser['content'], 'month=6 year=2026');
            })
            ->andReturn(['content' => 'Importing attendance for June 2026.']);

        $second = $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            '06/2026',
            $first['conversation_id'],
        );

        $this->assertStringContainsString('june 2026', strtolower($second['reply']));
        $this->assertSame($path, AiConversation::find($first['conversation_id'])?->pending_excel_path);

        @unlink($path);
    }

    public function test_import_attendance_excel_applies_default_month_year(): void
    {
        $path = sys_get_temp_dir() . '/attendance-defaults-' . uniqid('', true) . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['employee_code', 'cl', 'tot_dys'],
            ['EMP001', 3, 30],
        ]);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $tool = collect(AttendanceToolProvider::tools())->first(fn ($t) => $t->name() === 'import_attendance_excel');
        $result = $tool->handle([
            'file_path' => $path,
            'month' => 6,
            'year' => 2026,
        ], $this->company->id, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id,
            'month' => 6,
            'year' => 2026,
            'casual_leave' => 3,
        ]);

        @unlink($path);
    }

    public function test_direct_import_fallback_when_provider_fails(): void
    {
        $path = sys_get_temp_dir() . '/attendance-fallback-' . uniqid('', true) . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['employee_code', 'cl', 'tot_dys'],
            ['EMP001', 2, 30],
        ]);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $mockClient = Mockery::mock(OpenRouterClient::class);
        $registry = new ToolRegistry();
        $registry->registerMany(AttendanceToolProvider::tools());
        $orchestrator = new AgentOrchestrator($mockClient, $registry, app(ExcelPreviewService::class));

        $mockClient->shouldReceive('chat')
            ->once()
            ->andReturn(['content' => 'Which month and year?']);

        $first = $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            'Update the attendance',
            null,
            $path,
        );

        $mockClient->shouldReceive('chat')
            ->once()
            ->andThrow(new RuntimeException('AI service error: Provider returned error'));

        $second = $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            '06/2026',
            $first['conversation_id'],
        );

        $this->assertStringContainsString('attendance imported', strtolower($second['reply']));
        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id,
            'month' => 6,
            'year' => 2026,
            'casual_leave' => 2,
        ]);

        @unlink($path);
    }

    public function test_import_attendance_excel_with_employee_id_column_as_empno(): void
    {
        $path = sys_get_temp_dir() . '/attendance-empno-' . uniqid('', true) . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['employee_id', 'month', 'year', 'cl', 'tot_dys'],
            ['EMP001', 6, 2026, 2, 30],
        ]);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $tool = collect(AttendanceToolProvider::tools())->first(fn ($t) => $t->name() === 'import_attendance_excel');
        $result = $tool->handle(['file_path' => $path], $this->company->id, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id,
            'casual_leave' => 2,
            'total_days' => 30,
        ]);

        @unlink($path);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
