<?php

namespace Tests\Feature;

use App\Enums\Payroll\EmployeePayrollStatus;
use App\Enums\Payroll\PayrollRunStatus;
use App\Http\Livewire\ReportHub;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyReportTemplate;
use App\Models\CompensationStructure;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\EmployeePayroll;
use App\Models\Location;
use App\Models\MonthlyAttendance;
use App\Models\PayrollRun;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Attendance\AttendanceSetupService;
use App\Services\Reports\ReportDataSourceRegistry;
use App\Services\Reports\ReportRunnerService;
use App\Services\Reports\ReportTemplateService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReportTemplateSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Company $otherCompany;

    private Employee $employee;

    private EmployeeCompensationHistory $compensation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(ReportTemplateSeeder::class);

        $firm = \App\Models\B2bFirm::factory()->create();
        $this->user = User::factory()->b2bStaff()->forB2bFirm($firm)->create();
        $this->actingAs($this->user);

        $this->company = Company::factory()->forFirm($firm)->create([
            'company_name' => 'Report Co',
            'company_address' => 'Addr',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        $this->otherCompany = Company::factory()->forFirm($firm)->create([
            'company_name' => 'Other Co',
            'company_address' => 'Addr 2',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        $department = Department::create([
            'company_id' => $this->company->id,
            'department_name' => 'Engineering',
        ]);

        $designation = Designation::create([
            'company_id' => $this->company->id,
            'designation_name' => 'Developer',
        ]);

        $location = Location::factory()->create(['company_id' => $this->company->id]);

        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'employee_code' => 'EMP001',
            'employee_name' => 'Rahul Sharma',
            'gender' => 'M',
            'father_name' => 'Father Sharma',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $structure = CompensationStructure::create([
            'company_id' => $this->company->id,
            'structure_name' => 'Standard',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->compensation = EmployeeCompensationHistory::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'structure_id' => $structure->id,
            'annual_ctc' => 360000,
            'monthly_gross' => 30000,
            'effective_from' => now()->subYear()->toDateString(),
        ]);

        app(AttendanceSetupService::class)->seedCompanyDefaults($this->company->id);
    }

    public function test_registry_resolves_all_phase_one_sources(): void
    {
        $registry = app(ReportDataSourceRegistry::class);

        $this->assertCount(4, $registry->all());
        $this->assertSame('employees', $registry->get('employees')->key()->value);
        $this->assertSame('attendance_monthly', $registry->get('attendance_monthly')->key()->value);
        $this->assertSame('payroll_register', $registry->get('payroll_register')->key()->value);
        $this->assertSame('salary_sheet', $registry->get('salary_sheet')->key()->value);
    }

    public function test_employee_master_preview_is_company_scoped(): void
    {
        Employee::factory()->create([
            'company_id' => $this->otherCompany->id,
            'employee_code' => 'OTHER001',
            'employee_name' => 'Other Employee',
            'department_id' => Department::create([
                'company_id' => $this->otherCompany->id,
                'department_name' => 'Sales',
            ])->id,
            'designation_id' => Designation::create([
                'company_id' => $this->otherCompany->id,
                'designation_name' => 'Executive',
            ])->id,
            'location_id' => Location::factory()->create(['company_id' => $this->otherCompany->id])->id,
        ]);

        $template = CompanyReportTemplate::where('slug', 'employee-master')->firstOrFail();
        $result = app(ReportRunnerService::class)->preview(
            $this->company->id,
            $template,
            [],
        );

        $this->assertSame(1, $result['total']);
        $this->assertSame('EMP001', $result['rows'][0][0]);
    }

    public function test_attendance_register_preview_returns_expected_row(): void
    {
        MonthlyAttendance::create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'month' => 6,
            'year' => 2026,
            'entry_source' => 'manual',
            'working_days' => 26,
            'present_days' => 24,
            'half_days' => 0,
            'paid_leave_days' => 2,
            'lop_days' => 0,
            'holiday_days' => 0,
            'weekly_off_days' => 4,
            'overtime_hours' => 0,
            'worked_days' => 24,
            'total_days' => 30,
        ]);

        $template = CompanyReportTemplate::where('slug', 'monthly-attendance-register')->firstOrFail();
        $result = app(ReportRunnerService::class)->preview(
            $this->company->id,
            $template,
            ['month' => 6, 'year' => 2026],
        );

        $this->assertSame(1, $result['total']);
        $this->assertSame('EMP001', $result['rows'][0][0]);
        $this->assertSame('24.00', (string) $result['rows'][0][6]);
    }

    public function test_payroll_register_preview_returns_expected_row(): void
    {
        $attendance = MonthlyAttendance::create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'month' => 6,
            'year' => 2026,
            'entry_source' => 'manual',
            'working_days' => 26,
            'present_days' => 26,
            'worked_days' => 26,
            'total_days' => 30,
        ]);

        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'month' => 6,
            'year' => 2026,
            'status' => PayrollRunStatus::COMPLETED,
        ]);

        EmployeePayroll::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $this->employee->id,
            'attendance_summary_id' => $attendance->id,
            'employee_compensation_id' => $this->compensation->id,
            'gross_earnings' => 50000,
            'gross_deductions' => 5000,
            'employer_contributions' => 1800,
            'net_pay' => 45000,
            'status' => EmployeePayrollStatus::DRAFT,
        ]);

        $template = CompanyReportTemplate::where('slug', 'payroll-register')->firstOrFail();
        $result = app(ReportRunnerService::class)->preview(
            $this->company->id,
            $template,
            ['payroll_run_id' => $run->id],
        );

        $this->assertSame(1, $result['total']);
        $this->assertSame('EMP001', $result['rows'][0][0]);
        $this->assertSame('45000.00', (string) $result['rows'][0][8]);
    }

    public function test_export_creates_report_run_and_audit_log(): void
    {
        $template = CompanyReportTemplate::where('slug', 'employee-master')->firstOrFail();

        $response = app(ReportRunnerService::class)->export(
            $this->company->id,
            $template,
            [],
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('employee-master', (string) $response->headers->get('content-disposition'));

        $run = ReportRun::query()->first();
        $this->assertNotNull($run);
        $this->assertSame($this->company->id, $run->company_id);
        $this->assertSame(1, $run->row_count);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $this->company->id,
            'auditable_type' => (new ReportRun())->getMorphClass(),
            'source' => 'report_generator',
        ]);
    }

    public function test_viewer_can_open_reports_but_cannot_download(): void
    {
        $viewer = User::factory()->viewer()->create();
        $company = Company::factory()->ownedBy($viewer)->create([
            'company_name' => 'Viewer Co',
            'company_address' => 'Addr',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        $this->actingAs($viewer)
            ->get(route('reports', ['company_id' => $company->id]))
            ->assertOk();

        Livewire::actingAs($viewer)
            ->test(ReportHub::class, ['company_id' => (string) $company->id])
            ->set('selectedTemplateId', CompanyReportTemplate::where('slug', 'employee-master')->value('id'))
            ->call('downloadReport')
            ->assertForbidden();
    }

    public function test_payroll_manager_can_open_reports_page(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports', ['company_id' => $this->company->id]))
            ->assertOk();
    }

    public function test_template_service_lists_global_templates_for_company(): void
    {
        $templates = app(ReportTemplateService::class)->listForCompany($this->company->id);

        $this->assertCount(4, $templates);
        $this->assertTrue($templates->pluck('slug')->contains('employee-master'));
        $this->assertTrue($templates->pluck('slug')->contains('monthly-salary-sheet'));
    }

    public function test_report_hub_preview_renders_rows(): void
    {
        Livewire::test(ReportHub::class, ['company_id' => (string) $this->company->id])
            ->set('selectedTemplateId', CompanyReportTemplate::where('slug', 'employee-master')->value('id'))
            ->call('previewReport')
            ->assertSet('hasPreview', true)
            ->assertSet('previewTotal', 1);
    }

    public function test_invalid_template_column_configuration_is_rejected(): void
    {
        $template = CompanyReportTemplate::create([
            'company_id' => null,
            'name' => 'Broken Template',
            'slug' => 'broken-template',
            'data_source' => 'employees',
            'config' => [
                'columns' => ['not_a_real_column'],
                'filters' => [],
                'sort' => [],
                'parameters' => [],
            ],
            'default_format' => 'xlsx',
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(ReportRunnerService::class)->preview(
            $this->company->id,
            $template,
            [],
        );
    }

    public function test_company_admin_can_clone_system_template(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        $sourceId = CompanyReportTemplate::where('slug', 'employee-master')->value('id');

        Livewire::test(ReportHub::class, ['company_id' => (string) $this->company->id])
            ->set('activeTab', 'templates')
            ->call('cloneTemplate', $sourceId)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('company_report_templates', [
            'company_id' => $this->company->id,
            'data_source' => 'employees',
            'is_active' => true,
        ]);
    }

    public function test_company_admin_can_edit_cloned_template_columns(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        $cloned = app(ReportTemplateService::class)->cloneSystemTemplate(
            $this->company->id,
            CompanyReportTemplate::where('slug', 'employee-master')->value('id'),
        );

        Livewire::test(ReportHub::class, ['company_id' => (string) $this->company->id])
            ->call('openEditModal', $cloned->id)
            ->set('editName', 'Minimal Employee List')
            ->set('editColumns', ['employee_code', 'employee_name'])
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $cloned->refresh();
        $this->assertSame('Minimal Employee List', $cloned->name);
        $this->assertSame(['employee_code', 'employee_name'], $cloned->columnKeys());
    }

    public function test_edited_company_template_runs_in_preview(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        $cloned = app(ReportTemplateService::class)->cloneSystemTemplate(
            $this->company->id,
            CompanyReportTemplate::where('slug', 'employee-master')->value('id'),
        );

        app(ReportTemplateService::class)->updateCompanyTemplate($this->company->id, $cloned->id, [
            'config' => [
                'columns' => ['employee_code', 'employee_name'],
                'filters' => [],
                'sort' => [['field' => 'employee_code', 'dir' => 'asc']],
                'parameters' => ['department_id'],
            ],
        ]);

        $result = app(ReportRunnerService::class)->preview(
            $this->company->id,
            $cloned->fresh(),
            [],
        );

        $this->assertSame(1, $result['total']);
        $this->assertCount(2, $result['headings']);
    }

    public function test_company_admin_can_archive_company_template(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        $cloned = app(ReportTemplateService::class)->cloneSystemTemplate(
            $this->company->id,
            CompanyReportTemplate::where('slug', 'employee-master')->value('id'),
        );

        Livewire::test(ReportHub::class, ['company_id' => (string) $this->company->id])
            ->call('archiveTemplate', $cloned->id)
            ->assertHasNoErrors();

        $cloned->refresh();
        $this->assertFalse($cloned->is_active);
    }

    public function test_payroll_manager_cannot_clone_templates(): void
    {
        $sourceId = CompanyReportTemplate::where('slug', 'employee-master')->value('id');

        Livewire::test(ReportHub::class, ['company_id' => (string) $this->company->id])
            ->call('cloneTemplate', $sourceId)
            ->assertForbidden();
    }

    public function test_cannot_update_global_template_via_company_service(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        $globalId = CompanyReportTemplate::where('slug', 'employee-master')->value('id');

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(ReportTemplateService::class)->updateCompanyTemplate($this->company->id, $globalId, [
            'name' => 'Hacked Global Template',
        ]);
    }

    public function test_company_cannot_access_other_company_template(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        $otherClone = app(ReportTemplateService::class)->cloneSystemTemplate(
            $this->otherCompany->id,
            CompanyReportTemplate::where('slug', 'employee-master')->value('id'),
        );

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(ReportTemplateService::class)->updateCompanyTemplate(
            $this->company->id,
            $otherClone->id,
            ['name' => 'Should Fail'],
        );
    }

    public function test_template_clone_writes_audit_log(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        $cloned = app(ReportTemplateService::class)->cloneSystemTemplate(
            $this->company->id,
            CompanyReportTemplate::where('slug', 'employee-master')->value('id'),
        );

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $this->company->id,
            'auditable_type' => (new CompanyReportTemplate())->getMorphClass(),
            'auditable_id' => $cloned->id,
            'source' => 'report_generator',
        ]);
    }

    public function test_company_admin_can_create_custom_template_from_scratch(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        Livewire::test(ReportHub::class, ['company_id' => (string) $this->company->id])
            ->call('openCreateModal')
            ->set('editName', 'Engineering Roster')
            ->set('editDataSource', 'employees')
            ->set('editColumns', ['employee_code', 'employee_name', 'department.department_name'])
            ->set('editColumnLabels', [
                'employee_code' => 'Emp Code',
                'employee_name' => 'Full Name',
            ])
            ->set('editSortField', 'employee_name')
            ->set('editSortDir', 'asc')
            ->set('editParameters', ['department_id'])
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $template = CompanyReportTemplate::query()
            ->where('company_id', $this->company->id)
            ->where('name', 'Engineering Roster')
            ->first();

        $this->assertNotNull($template);
        $this->assertSame('employees', $template->data_source);
        $this->assertSame(2, $template->config['version']);
        $this->assertSame('Emp Code', $template->config['columns'][0]['label']);
    }

    public function test_custom_column_labels_appear_in_preview(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        $template = app(ReportTemplateService::class)->createCompanyTemplate($this->company->id, [
            'name' => 'Labeled Employees',
            'data_source' => 'employees',
            'config' => [
                'columns' => [
                    ['key' => 'employee_code', 'label' => 'Staff ID', 'format' => 'text', 'visible' => true, 'sequence' => 0],
                    ['key' => 'employee_name', 'label' => 'Staff Name', 'format' => 'text', 'visible' => true, 'sequence' => 1],
                ],
                'filters' => [],
                'sort' => [['field' => 'employee_code', 'dir' => 'asc']],
                'parameters' => ['department_id'],
            ],
        ]);

        $result = app(ReportRunnerService::class)->preview(
            $this->company->id,
            $template,
            [],
        );

        $this->assertSame(['Staff ID', 'Staff Name'], $result['headings']);
        $this->assertSame('EMP001', $result['rows'][0][0]);
    }

    public function test_cannot_archive_system_template_via_service(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        $globalId = CompanyReportTemplate::where('slug', 'employee-master')->value('id');

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(ReportTemplateService::class)->archiveCompanyTemplate($this->company->id, $globalId);
    }

    public function test_salary_sheet_custom_template_keeps_mandatory_columns(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(ReportTemplateService::class)->createCompanyTemplate($this->company->id, [
            'name' => 'Broken Salary Sheet',
            'data_source' => 'salary_sheet',
            'config' => [
                'columns' => [
                    'employee.employee_code',
                    'employee.employee_name',
                ],
                'filters' => [
                    ['field' => 'payroll_run_id', 'op' => '=', 'param' => 'payroll_run_id'],
                ],
                'sort' => [],
                'parameters' => ['payroll_run_id', 'group_by', 'format'],
            ],
        ]);
    }

    public function test_invalid_filter_operator_is_rejected(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(ReportTemplateService::class)->createCompanyTemplate($this->company->id, [
            'name' => 'Bad Filters',
            'data_source' => 'employees',
            'config' => [
                'columns' => ['employee_code', 'employee_name'],
                'filters' => [
                    ['field' => 'department_id', 'op' => 'like', 'param' => 'department_id'],
                ],
                'sort' => [],
                'parameters' => ['department_id'],
            ],
        ]);
    }

    public function test_payroll_manager_cannot_create_templates(): void
    {
        Livewire::test(ReportHub::class, ['company_id' => (string) $this->company->id])
            ->call('openCreateModal')
            ->assertForbidden();
    }

    public function test_column_order_is_preserved_in_preview(): void
    {
        $admin = User::factory()->companyAdmin()->create();
        $admin->forceFill(['company_id' => $this->company->id, 'b2b_firm_id' => null])->save();
        $this->actingAs($admin);

        $template = app(ReportTemplateService::class)->createCompanyTemplate($this->company->id, [
            'name' => 'Reordered Employees',
            'data_source' => 'employees',
            'config' => [
                'columns' => ['employee_name', 'employee_code'],
                'filters' => [],
                'sort' => [],
                'parameters' => ['department_id'],
            ],
        ]);

        $result = app(ReportRunnerService::class)->preview(
            $this->company->id,
            $template,
            [],
        );

        $this->assertSame(['Employee Name', 'Employee Code'], $result['headings']);
        $this->assertSame('Rahul Sharma', $result['rows'][0][0]);
        $this->assertSame('EMP001', $result['rows'][0][1]);
    }
}
