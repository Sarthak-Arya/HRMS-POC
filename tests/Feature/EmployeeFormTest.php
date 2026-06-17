<?php

namespace Tests\Feature;

use App\Http\Livewire\AddEmployeeDetails;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->hrManager()->create();
        $this->company = Company::factory()->create(['company_handled_by' => $this->user->id]);
        $this->actingAs($this->user);
    }

    private function employeeForm(): \Livewire\Testing\TestableLivewire
    {
        return Livewire::test(AddEmployeeDetails::class, [
            'company_id' => (string) $this->company->id,
        ]);
    }

    public function test_it_can_create_an_employee_with_required_fields_only(): void
    {
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $designation = Designation::factory()->create(['company_id' => $this->company->id]);
        $location = Location::factory()->create(['company_id' => $this->company->id]);

        $this->employeeForm()
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('fatherName', 'John Doe Sr')
            ->set('gender', 'male')
            ->set('dob', '1990-01-01')
            ->set('department', $department->department_name)
            ->set('designation', $designation->designation_name)
            ->set('location', $location->location_name)
            ->set('employeeCompanyCode', 'EMP001')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'employee_name' => 'John Doe',
            'father_name' => 'John Doe Sr',
            'gender' => 'M',
            'employee_code' => 'EMP001',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_it_can_create_an_employee_with_all_fields(): void
    {
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $designation = Designation::factory()->create(['company_id' => $this->company->id]);
        $location = Location::factory()->create(['company_id' => $this->company->id]);

        $this->employeeForm()
            ->set('firstName', 'John')
            ->set('middleName', 'William')
            ->set('lastName', 'Doe')
            ->set('fatherName', 'John Doe Sr')
            ->set('gender', 'male')
            ->set('dob', '1990-01-01')
            ->set('department', $department->department_name)
            ->set('designation', $designation->designation_name)
            ->set('location', $location->location_name)
            ->set('employeeCompanyCode', 'EMP002')
            ->set('joiningDate', '2023-01-01')
            ->set('esiNo', 'ESI123456')
            ->set('pfNo', 'PF123456')
            ->set('accountNo', '1234567890')
            ->set('bankName', 'Test Bank')
            ->set('ifscCode', 'TEST123456')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'employee_name' => 'John William Doe',
            'father_name' => 'John Doe Sr',
            'gender' => 'M',
            'employee_code' => 'EMP002',
            'doj' => '2023-01-01',
            'esi_no' => 'ESI123456',
            'pf_no' => 'PF123456',
            'bank_name' => 'Test Bank',
            'bank_account_no' => '1234567890',
            'bank_ifsc_code' => 'TEST123456',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_it_validates_required_fields(): void
    {
        $this->employeeForm()
            ->set('firstName', '')
            ->set('lastName', '')
            ->set('fatherName', '')
            ->set('gender', '')
            ->set('department', '')
            ->set('designation', '')
            ->set('location', '')
            ->set('employeeCompanyCode', '')
            ->call('save')
            ->assertHasErrors([
                'firstName' => 'required',
                'lastName' => 'required',
                'fatherName' => 'required',
                'gender' => 'required',
                'department' => 'required',
                'designation' => 'required',
                'location' => 'required',
                'employeeCompanyCode' => 'required',
            ]);
    }

    public function test_it_validates_date_format(): void
    {
        $this->employeeForm()
            ->set('dob', 'invalid-date')
            ->set('joiningDate', 'invalid-date')
            ->call('save')
            ->assertHasErrors([
                'dob' => 'date',
                'joiningDate' => 'date',
            ]);
    }

    public function test_it_creates_missing_department_and_designation_names(): void
    {
        $location = Location::factory()->create(['company_id' => $this->company->id]);

        $this->employeeForm()
            ->set('firstName', 'Jane')
            ->set('lastName', 'Smith')
            ->set('fatherName', 'Jane Smith Sr')
            ->set('gender', 'female')
            ->set('department', 'New Department')
            ->set('designation', 'New Designation')
            ->set('location', $location->location_name)
            ->set('employeeCompanyCode', 'EMP003')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('departments', [
            'company_id' => $this->company->id,
            'department_name' => 'New Department',
        ]);

        $this->assertDatabaseHas('designations', [
            'company_id' => $this->company->id,
            'designation_name' => 'New Designation',
        ]);

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP003',
            'employee_name' => 'Jane Smith',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_it_rejects_duplicate_employee_code(): void
    {
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $designation = Designation::factory()->create(['company_id' => $this->company->id]);
        $location = Location::factory()->create(['company_id' => $this->company->id]);

        $this->employeeForm()
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('fatherName', 'John Doe Sr')
            ->set('gender', 'male')
            ->set('department', $department->department_name)
            ->set('designation', $designation->designation_name)
            ->set('location', $location->location_name)
            ->set('employeeCompanyCode', 'EMP-DUP')
            ->call('save')
            ->assertHasNoErrors();

        $this->employeeForm()
            ->set('firstName', 'Jane')
            ->set('lastName', 'Doe')
            ->set('fatherName', 'Jane Doe Sr')
            ->set('gender', 'female')
            ->set('department', $department->department_name)
            ->set('designation', $designation->designation_name)
            ->set('location', $location->location_name)
            ->set('employeeCompanyCode', 'EMP-DUP')
            ->call('save')
            ->assertHasErrors(['employeeCompanyCode' => 'unique']);
    }
}
