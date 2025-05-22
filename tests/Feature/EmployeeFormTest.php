<?php

namespace Tests\Feature;

use App\Http\Livewire\AddEmployeeDetails;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a test company
        $this->company = Company::factory()->create();
        session()->put('companyIdNum', $this->company->id);
    }

    /** @test */
    public function it_can_create_an_employee_with_required_fields_only()
    {
        // Create required department and designation
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $designation = Designation::factory()->create(['company_id' => $this->company->id]);

        // Test data with required fields only
        $testData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fatherName' => 'John Doe Sr',
            'gender' => 'male',
            'dob' => '1990-01-01',
            'department' => $department->id,
            'designation' => $designation->id,
        ];

        // Test the Livewire component
        Livewire::test(AddEmployeeDetails::class)
            ->set('firstName', $testData['firstName'])
            ->set('lastName', $testData['lastName'])
            ->set('fatherName', $testData['fatherName'])
            ->set('gender', $testData['gender'])
            ->set('dob', $testData['dob'])
            ->set('department', $testData['department'])
            ->set('designation', $testData['designation'])
            ->call('save')
            ->assertHasNoErrors();

        // Assert the employee was created in the database with required fields
        $this->assertDatabaseHas('employees', [
            'employee_first_name' => $testData['firstName'],
            'employee_last_name' => $testData['lastName'],
            'employee_father_name' => $testData['fatherName'],
            'employee_gender' => $testData['gender'],
            'employee_dob' => $testData['dob'],
            'department_id' => $testData['department'],
            'designation_id' => $testData['designation'],
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_can_create_an_employee_with_all_fields()
    {
        // Create required department and designation
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $designation = Designation::factory()->create(['company_id' => $this->company->id]);

        // Test data with all fields
        $testData = [
            'firstName' => 'John',
            'middleName' => 'William',
            'lastName' => 'Doe',
            'fatherName' => 'John Doe Sr',
            'gender' => 'male',
            'dob' => '1990-01-01',
            'department' => $department->id,
            'designation' => $designation->id,
            'employeeCompanyCode' => 'EMP001',
            'joiningDate' => '2023-01-01',
            'leavingDate' => null,
            'esiNo' => 'ESI123456',
            'pfNo' => 'PF123456',
            'accountNo' => '1234567890',
            'bankName' => 'Test Bank',
            'ifscCode' => 'TEST123456',
        ];

        // Test the Livewire component
        Livewire::test(AddEmployeeDetails::class)
            ->set('firstName', $testData['firstName'])
            ->set('middleName', $testData['middleName'])
            ->set('lastName', $testData['lastName'])
            ->set('fatherName', $testData['fatherName'])
            ->set('gender', $testData['gender'])
            ->set('dob', $testData['dob'])
            ->set('department', $testData['department'])
            ->set('designation', $testData['designation'])
            ->set('employeeCompanyCode', $testData['employeeCompanyCode'])
            ->set('joiningDate', $testData['joiningDate'])
            ->set('leavingDate', $testData['leavingDate'])
            ->set('esiNo', $testData['esiNo'])
            ->set('pfNo', $testData['pfNo'])
            ->set('accountNo', $testData['accountNo'])
            ->set('bankName', $testData['bankName'])
            ->set('ifscCode', $testData['ifscCode'])
            ->call('save')
            ->assertHasNoErrors();

        // Assert the employee was created in the database with all fields
        $this->assertDatabaseHas('employees', [
            'employee_first_name' => $testData['firstName'],
            'employee_middle_name' => $testData['middleName'],
            'employee_last_name' => $testData['lastName'],
            'employee_father_name' => $testData['fatherName'],
            'employee_gender' => $testData['gender'],
            'employee_dob' => $testData['dob'],
            'department_id' => $testData['department'],
            'designation_id' => $testData['designation'],
            'employee_company_code' => $testData['employeeCompanyCode'],
            'employee_joining_date' => $testData['joiningDate'],
            'employee_leaving_date' => $testData['leavingDate'],
            'employee_esi_no' => $testData['esiNo'],
            'employee_pf_no' => $testData['pfNo'],
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        Livewire::test(AddEmployeeDetails::class)
            ->set('firstName', '')
            ->set('lastName', '')
            ->set('fatherName', '')
            ->set('gender', '')
            ->set('dob', '')
            ->set('department', '')
            ->set('designation', '')
            ->call('save')
            ->assertHasErrors([
                'firstName' => 'required',
                'lastName' => 'required',
                'fatherName' => 'required',
                'gender' => 'required',
                'dob' => 'required',
                'department' => 'required',
                'designation' => 'required',
            ]);
    }

    /** @test */
    public function it_validates_date_format()
    {
        Livewire::test(AddEmployeeDetails::class)
            ->set('dob', 'invalid-date')
            ->set('joiningDate', 'invalid-date')
            ->call('save')
            ->assertHasErrors([
                'dob' => 'date',
                'joiningDate' => 'date',
            ]);
    }

    /** @test */
    public function it_validates_department_exists()
    {
        Livewire::test(AddEmployeeDetails::class)
            ->set('department', 999) // Non-existent department
            ->call('save')
            ->assertHasErrors(['department' => 'exists']);
    }

    /** @test */
    public function it_validates_designation_exists()
    {
        Livewire::test(AddEmployeeDetails::class)
            ->set('designation', 999) // Non-existent designation
            ->call('save')
            ->assertHasErrors(['designation' => 'exists']);
    }
} 