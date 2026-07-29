<?php

namespace Tests\Feature;

use App\Http\Livewire\AttendanceHub;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\MonthlyAttendance;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\AttendanceSetupService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceHubMonthlyUiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->hrManager()->create();
        $this->actingAs($user);

        $this->company = Company::factory()->ownedBy($user)->create([
            'company_name' => 'Test Co',
            'company_address' => 'Addr',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        app(AttendanceSetupService::class)->seedCompanyDefaults($this->company->id);

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

    private function rowKey(): string
    {
        return 'e' . $this->employee->id;
    }

    /** @return array<string, int> code => leave index */
    private function leaveIndexes(): array
    {
        $indexes = [];
        $leaveTypes = LeaveType::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        foreach ($leaveTypes as $idx => $lt) {
            $indexes[$lt->code] = $idx;
        }

        return $indexes;
    }

    public function test_monthly_leave_values_persist_after_save(): void
    {
        $idx = $this->leaveIndexes();

        Livewire::test(AttendanceHub::class, ['company_id' => (string) $this->company->id])
            ->set('activeTab', 'monthly')
            ->set('month', 6)
            ->set('year', 2026)
            ->set('monthlyEditMode', true)
            ->set('monthlyData.' . $this->rowKey() . '.employee_id', $this->employee->id)
            ->set('monthlyData.' . $this->rowKey() . '.leave_' . $idx['CL'], 2)
            ->set('monthlyData.' . $this->rowKey() . '.leave_' . $idx['EL'], 1)
            ->set('monthlyData.' . $this->rowKey() . '.leave_' . $idx['SL'], 0.5)
            ->set('monthlyData.' . $this->rowKey() . '.leave_' . $idx['LWP'], 1)
            ->set('monthlyData.' . $this->rowKey() . '.tot_dys', 26)
            ->set('monthlyData.' . $this->rowKey() . '.working_days', 26)
            ->call('saveMonthly')
            ->assertHasNoErrors();

        $summary = MonthlyAttendance::query()
            ->where('employee_id', $this->employee->id)
            ->where('month', 6)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($summary);
        $this->assertSame(2.0, $summary->casual_leave);
        $this->assertSame(1.0, $summary->earned_leave);
        $this->assertSame(0.5, $summary->sick_leave);
        $this->assertSame(1.0, $summary->leaveDaysForCode('LWP'));
    }

    public function test_monthly_leave_values_display_after_reload(): void
    {
        $idx = $this->leaveIndexes();

        $record = MonthlyAttendance::create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'month' => 6,
            'year' => 2026,
            'entry_source' => 'manual',
            'working_days' => 26,
            'total_days' => 26,
            'present_days' => 22,
            'worked_days' => 22,
            'holiday_days' => 0,
            'esi_la' => 0,
        ]);
        $record->syncLeaveBreakdown(['CL' => 2, 'EL' => 1, 'SL' => 0.5, 'LWP' => 1]);

        $component = Livewire::test(AttendanceHub::class, ['company_id' => (string) $this->company->id])
            ->set('activeTab', 'monthly')
            ->set('month', 6)
            ->set('year', 2026);

        $row = $component->get('monthlyData')[$this->rowKey()] ?? [];
        $this->assertSame(2.0, $row['leave_' . $idx['CL']] ?? null);
        $this->assertSame(1.0, $row['leave_' . $idx['EL']] ?? null);
        $this->assertSame(0.5, $row['leave_' . $idx['SL']] ?? null);
        $this->assertSame(1.0, $row['leave_' . $idx['LWP']] ?? null);
    }

    public function test_monthly_leave_input_values_survive_rerender_in_edit_mode(): void
    {
        $idx = $this->leaveIndexes();

        $component = Livewire::test(AttendanceHub::class, ['company_id' => (string) $this->company->id])
            ->set('activeTab', 'monthly')
            ->set('month', 6)
            ->set('year', 2026)
            ->set('monthlyEditMode', true)
            ->set('monthlyData.' . $this->rowKey() . '.employee_id', $this->employee->id)
            ->set('monthlyData.' . $this->rowKey() . '.leave_' . $idx['CL'], 3)
            ->set('monthlyData.' . $this->rowKey() . '.leave_' . $idx['EL'], 2);

        $this->assertSame(3, $component->get('monthlyData')[$this->rowKey()]['leave_' . $idx['CL']] ?? null);

        $component->call('$refresh');

        $monthlyData = $component->get('monthlyData');
        $this->assertSame(3, $monthlyData[$this->rowKey()]['leave_' . $idx['CL']] ?? null, 'CL should survive re-render in edit mode');
        $this->assertSame(2, $monthlyData[$this->rowKey()]['leave_' . $idx['EL']] ?? null, 'EL should survive re-render in edit mode');
    }

    public function test_save_monthly_matrix_persists_leave_values(): void
    {
        $idx = $this->leaveIndexes();

        Livewire::test(AttendanceHub::class, ['company_id' => (string) $this->company->id])
            ->set('activeTab', 'monthly')
            ->set('month', 6)
            ->set('year', 2026)
            ->set('monthlyEditMode', true)
            ->call('saveMonthlyMatrix', [
                [
                    'employee_id' => $this->employee->id,
                    'employee_code' => $this->employee->employee_code,
                    'employee_name' => $this->employee->employee_name,
                    'department' => 'Engineering',
                    'designation' => 'Developer',
                    'working_days' => 26,
                    'leave_' . $idx['CL'] => 2,
                    'leave_' . $idx['EL'] => 1,
                    'leave_' . $idx['SL'] => 0.5,
                    'leave_' . $idx['LWP'] => 1,
                    'esi_leave' => 0,
                    'holiday' => 0,
                    'tot_dys' => 26,
                ]
            ])
            ->assertHasNoErrors();

        $summary = MonthlyAttendance::query()
            ->where('employee_id', $this->employee->id)
            ->where('month', 6)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($summary);
        $this->assertSame(2.0, $summary->casual_leave);
        $this->assertSame(1.0, $summary->earned_leave);
    }

    public function test_template_columns_include_dynamic_leave_types(): void
    {
        $columns = app(AttendanceService::class)->buildImportTemplateColumns($this->company->id);

        $this->assertSame('employee_code', $columns[0]);
        $this->assertContains('cl', $columns);
        $this->assertContains('el', $columns);
        $this->assertContains('sl', $columns);
        $this->assertContains('lwp', $columns);
        $this->assertContains('working_days', $columns);
        $this->assertNotContains('ded_1', $columns);
    }

    public function test_template_round_trip_import_persists_summary(): void
    {
        $service = app(AttendanceService::class);
        $headers = $service->buildImportTemplateColumns($this->company->id);
        $row = [
            'EMP001',
            6,
            2026,
            2,
            1,
            0,
            0,
            0,
            26,
            26,
        ];

        $path = sys_get_temp_dir() . '/hub-roundtrip-' . uniqid('', true) . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers, $row]);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $result = $service->importFromExcel($this->company->id, $path);

        $this->assertSame(0, $result['failed']);
        $this->assertSame(1, $result['created']);

        $summary = MonthlyAttendance::query()
            ->where('employee_id', $this->employee->id)
            ->where('month', 6)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($summary);
        $this->assertSame(2.0, $summary->casual_leave);
        $this->assertSame(1.0, $summary->earned_leave);

        @unlink($path);
    }

    public function test_import_excel_applies_default_month_year(): void
    {
        $path = sys_get_temp_dir() . '/hub-defaults-' . uniqid('', true) . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['employee_code', 'cl', 'tot_dys', 'working_days'],
            ['EMP001', 2, 26, 26],
        ]);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $result = app(AttendanceService::class)->importFromExcel(
            $this->company->id,
            $path,
            6,
            2026,
        );

        $this->assertSame(0, $result['failed']);
        $this->assertSame(1, $result['created']);

        $summary = MonthlyAttendance::query()
            ->where('employee_id', $this->employee->id)
            ->where('month', 6)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($summary);
        $this->assertSame(2.0, $summary->casual_leave);

        @unlink($path);
    }

    public function test_import_excel_accepts_friendly_headers(): void
    {
        $path = sys_get_temp_dir() . '/hub-friendly-' . uniqid('', true) . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Emp No', 'Casual Leave', 'Total Days', 'Month', 'Year'],
            ['EMP001', 1, 26, 6, 2026],
        ]);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $result = app(AttendanceService::class)->importFromExcel($this->company->id, $path);

        $this->assertSame(0, $result['failed']);
        $this->assertSame(1, $result['created']);

        $summary = MonthlyAttendance::query()
            ->where('employee_id', $this->employee->id)
            ->where('month', 6)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($summary);
        $this->assertSame(1.0, $summary->casual_leave);

        @unlink($path);
    }
}
