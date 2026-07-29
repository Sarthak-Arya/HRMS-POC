<?php

namespace Tests\Feature;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;
use App\Enums\Payroll\EmployeePayrollStatus;
use App\Enums\Payroll\PayrollLineComponentType;
use App\Enums\Payroll\PayrollRunStatus;
use App\Models\Company;
use App\Models\CompensationComponent;
use App\Models\CompensationStructure;
use App\Models\StructureComponent;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\EmployeePayroll;
use App\Models\Location;
use App\Models\EmployeePayrollLine;
use App\Models\MonthlyAttendance;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Payroll\SalarySheetService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalarySheetExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private PayrollRun $run;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->payrollManager()->create();
        $this->actingAs($this->user);

        $this->company = Company::factory()->ownedBy($this->user)->create([
            'company_name' => 'R.K. BAWTA',
            'company_address' => 'Addr',
            'is_esi' => true,
            'is_pf' => true,
        ]);

        $department = Department::create([
            'company_id' => $this->company->id,
            'department_name' => 'Operations',
        ]);

        $designation = Designation::create([
            'company_id' => $this->company->id,
            'designation_name' => 'Staff',
        ]);

        $location = Location::factory()->create(['company_id' => $this->company->id]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'employee_code' => 'EMP001',
            'employee_name' => 'Mr.AMAN BAWTA',
            'father_name' => 'RAJESH KUMAR',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
            'esi_no' => '1113667512',
            'pf_no' => '24',
        ]);

        $basic = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'Basic',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::FIXED,
            'is_active' => true,
            'display_order' => 1,
            'created_by' => $this->user->id,
        ]);

        $hra = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'HRA',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::FIXED,
            'is_active' => true,
            'display_order' => 2,
            'created_by' => $this->user->id,
        ]);

        $structure = CompensationStructure::create([
            'company_id' => $this->company->id,
            'structure_name' => 'Standard',
            'is_default' => true,
            'is_active' => true,
        ]);

        StructureComponent::create([
            'structure_id' => $structure->id,
            'component_id' => $basic->id,
            'value' => 15000,
            'calculation_type' => CalculationType::FIXED,
            'is_mandatory' => true,
        ]);

        StructureComponent::create([
            'structure_id' => $structure->id,
            'component_id' => $hra->id,
            'value' => 8910,
            'calculation_type' => CalculationType::FIXED,
            'is_mandatory' => false,
        ]);

        $compensation = EmployeeCompensationHistory::create([
            'employee_id' => $employee->id,
            'company_id' => $this->company->id,
            'structure_id' => $structure->id,
            'annual_ctc' => 286920,
            'monthly_gross' => 23910,
            'effective_from' => now()->subYear()->toDateString(),
            'approved_by' => $this->user->id,
        ]);

        $attendance = MonthlyAttendance::create([
            'employee_id' => $employee->id,
            'company_id' => $this->company->id,
            'month' => 5,
            'year' => 2026,
            'entry_source' => 'manual',
            'working_days' => 26,
            'present_days' => 23,
            'worked_days' => 23,
            'holiday_days' => 0,
            'total_days' => 30,
        ]);

        $this->run = PayrollRun::create([
            'company_id' => $this->company->id,
            'month' => 5,
            'year' => 2026,
            'status' => PayrollRunStatus::COMPLETED,
        ]);

        $payroll = EmployeePayroll::create([
            'payroll_run_id' => $this->run->id,
            'employee_id' => $employee->id,
            'attendance_summary_id' => $attendance->id,
            'employee_compensation_id' => $compensation->id,
            'gross_earnings' => 17740,
            'gross_deductions' => 1469,
            'employer_contributions' => 134,
            'net_pay' => 16271,
            'status' => EmployeePayrollStatus::APPROVED,
        ]);

        EmployeePayrollLine::create([
            'employee_payroll_id' => $payroll->id,
            'component_id' => $basic->id,
            'component_name' => 'Basic',
            'component_type' => PayrollLineComponentType::EARNING,
            'calculated_amount' => 11129,
        ]);

        EmployeePayrollLine::create([
            'employee_payroll_id' => $payroll->id,
            'component_id' => $hra->id,
            'component_name' => 'HRA',
            'component_type' => PayrollLineComponentType::EARNING,
            'calculated_amount' => 6611,
        ]);
    }

    public function test_salary_sheet_service_builds_company_sheet(): void
    {
        $sheets = app(SalarySheetService::class)->buildSheets($this->run->fresh(['company']), 'company');

        $this->assertCount(1, $sheets);
        $this->assertSame('R.K. BAWTA', $sheets[0]['title']);
        $this->assertCount(1, $sheets[0]['employees']);
        $this->assertSame('Mr.AMAN BAWTA', $sheets[0]['employees'][0]['employee_name']);
        $this->assertSame(16271.0, $sheets[0]['employees'][0]['net_pay']);
        $this->assertSame(1, $sheets[0]['statutory_summary']['employee_count']);
    }

    public function test_salary_sheet_pdf_download_returns_pdf(): void
    {
        $response = $this->get(route('payroll.salary-sheet', [
            'company_id' => $this->company->id,
            'run_id' => $this->run->id,
            'group_by' => 'company',
            'format' => 'pdf',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_salary_sheet_excel_download_returns_xlsx(): void
    {
        $response = $this->get(route('payroll.salary-sheet', [
            'company_id' => $this->company->id,
            'run_id' => $this->run->id,
            'group_by' => 'company',
            'format' => 'xlsx',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('xlsx', (string) $response->headers->get('content-disposition'));
    }
}
