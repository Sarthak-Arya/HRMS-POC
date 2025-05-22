<?php

namespace App\Http\Livewire;

use Exception;
use Illuminate\Database\QueryException;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Http\Controllers\ImportExcel;
use Livewire\Attributes\Validate;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Support\Facades\Log;
use App\Exports\EmployeesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeesImport;

class AddEmployeeDetails extends Component
{
    use WithFileUploads;
    
    public string $companyId = '';

    // New properties for form data with default values
    #[Validate('required|min:2')]
    public string $firstName = '';
    
    #[Validate('nullable')]
    public string $middleName = '';
    
    #[Validate('required|min:2')]
    public string $lastName = '';
    
    #[Validate('required|min:2')]
    public string $fatherName = '';
    
    #[Validate('required')]
    public string $gender = '';
    
    #[Validate('required|date')]
    public string $dob = '';
    
    #[Validate('required')]
    public string $department = '';
    
    #[Validate('required')]
    public string $designation = '';
    
    public string $companyName = '';
    public string $employeeCompanyCode = '';
    public string $joiningDate = '';
    public string $leavingDate = '';
    public string $esiNo = '';
    public string $pfNo = '';
    public string $accountNo = '';
    public string $bankName = '';
    public string $ifscCode = '';

    public $selectedDepartment = '';
    public $excelFile;
    public $importDepartment = '';
    public $isImporting = false;
    public $importMessage = '';
    public $importError = '';

    public function mount(?string $company_id = null)
    {
        $this->companyId = $company_id ?? request()->session()->get('companyId', '');
    }

    public function render()
    {
        $company = Company::with('departments')->find(request()->session()->get('companyIdNum'));
        $departments = $company->departments;

        return view('livewire.add-employee-details', ["departments" => $departments]);
    }

    public function save()
    {
        // $validatedData = $this->validate();
        Log::debug("Saving employee data");

        try {
            $employee = Employee::create([
                'company_id' => request()->session()->get("companyIdNum"),
                'employee_first_name' => $this->firstName,
                'employee_middle_name' => $this->middleName,
                'employee_last_name' => $this->lastName,
                'employee_father_name' => $this->fatherName,
                'employee_gender' => $this->gender,
                'employee_dob' => $this->dob,
                'employee_company_code' => $this->employeeCompanyCode,
                'employee_joining_date' => $this->joiningDate,
                'employee_leaving_date' => $this->leavingDate,
                'employee_esi_no' => $this->esiNo,
                'employee_pf_no' => $this->pfNo,
                'designation_id' => $this->designation,
                'department_id' => $this->department,
                'employee_age' => $this->calculateAge($this->dob)
            ]);

            session()->flash('success', 'Employee details saved successfully!');
            $this->reset();  // Clear the form after successful save
        }
        catch(QueryException $e){
            Log::debug("Error occurred in saving employee to database due to SQL Exception: " . $e->getMessage());
            session()->flash('error', 'Failed to save employee details. Database error occurred.');
        }  
        catch(Exception $e){
            Log::error("Error occurred in saving employee: " . $e->getMessage());
            session()->flash('error', 'Failed to save employee details. Please try again.');
        }
    }

    public function importEmployees()
    {
        $this->validate([
            'excelFile' => 'required|mimes:xlsx,xls',
            'importDepartment' => 'required'
        ]);

        try {
            $this->isImporting = true;
            $this->importMessage = '';
            $this->importError = '';

            $import = new EmployeesImport($this->companyId, $this->importDepartment);
            Excel::import($import, $this->excelFile);

            $this->importMessage = 'Employees imported successfully!';
            $this->reset(['excelFile', 'importDepartment']);
        } catch (Exception $e) {
            Log::error('Import failed: ' . $e->getMessage());
            $this->importError = 'Failed to import employees. Please check the file format and try again.';
        } finally {
            $this->isImporting = false;
        }
    }

    private function calculateAge($dob)
    {
        return \Carbon\Carbon::parse(time: $dob)->age; // Calculate age from date of birth
    }

    public function exportToExcel()
    {
        try {
            $query = Employee::query()
                ->with(['department', 'designation'])  // Eager load the relationships
                ->where('company_id', request()->session()->get('companyIdNum'));

            if ($this->selectedDepartment) {
                $query->where('department_id', $this->selectedDepartment);
            }

            $fileName = 'employees_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download(new EmployeesExport($query), $fileName);

        } catch (Exception $e) {
            Log::error("Error occurred while exporting: " . $e->getMessage());
            session()->flash('error', 'Failed to export data. Please try again.');
        }
    }
}
