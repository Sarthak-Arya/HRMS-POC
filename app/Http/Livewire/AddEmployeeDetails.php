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
use App\Models\Location;
use Illuminate\Support\Facades\Log;
use App\Exports\EmployeesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeeImport;

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
    
    public string $location = '';
    
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
    public $importLocation = '';
    public $isImporting = false;
    public $importMessage = '';
    public $importError = '';

    public function mount(?string $company_id = null)
    {
        $this->companyId = $company_id ?? request()->session()->get('companyId', '');
    }

    public function render()
    {
        $company = Company::with(['departments', 'locations'])->find(request()->session()->get('companyId'));
        $departments = $company ? $company->departments : collect();
        $locations = $company ? $company->locations : collect();

        return view('livewire.add-employee-details', [
            "departments" => $departments,
            "locations" => $locations
        ]);
    }

    public function save()
    {
        // $validatedData = $this->validate();
        Log::debug("Saving employee data");

        try {
            $employee = Employee::create([
                'company_id' => request()->session()->get("companyId"),
                'first_name' => $this->firstName,
                'middle_name' => $this->middleName,
                'last_name' => $this->lastName,
                'father_name' => $this->fatherName,
                'gender' => strtoupper(substr($this->gender, 0, 1)), // Convert to M/F/O format
                'dob' => $this->dob,
                'company_code' => $this->employeeCompanyCode,
                'joining_date' => $this->joiningDate,
                'leaving_date' => $this->leavingDate ?: null,
                'esi_no' => $this->esiNo,
                'pf_no' => $this->pfNo,
                'designation_id' => $this->designation,
                'department_id' => $this->department,
                'location_id' => $this->location ?: null,
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

    /**
     * Validate the Excel import input (file, department, and location)
     * Returns an array of issues (empty if valid)
     */
    private function validateExcelImportInput()
    {
        $inputIssues = [];
        if (empty($this->excelFile)) {
            $inputIssues[] = 'Excel file is required.';
        } elseif (!in_array($this->excelFile->getClientOriginalExtension(), ['xlsx', 'xls'])) {
            $inputIssues[] = 'The file must be an Excel file (.xlsx or .xls).';
        }
        if (empty($this->importDepartment)) {
            $inputIssues[] = 'Department is required for import.';
        }
        if (empty($this->importLocation)) {
            $inputIssues[] = 'Location is required for import.';
        }
        return $inputIssues;
    }

    public function importEmployees()
    {
        // Validate input using the new function
        $inputIssues = $this->validateExcelImportInput();
        if (!empty($inputIssues)) {
            $this->importError = implode("\n", $inputIssues);
            return;
        }
        try {
            $this->isImporting = true;
            $this->importMessage = '';
            $this->importError = '';

            Log::info('Starting employee import', [
                'user_id' => auth()->id(),
                'company_id' => $this->companyId,
                'department' => $this->importDepartment,
                'location' => $this->importLocation,
                'file_name' => $this->excelFile ? $this->excelFile->getClientOriginalName() : null
            ]);

            $import = new EmployeeImport($this->importLocation, $this->importDepartment);
            Excel::import($import, $this->excelFile);

            $errors = $import->getErrors();
            if (!empty($errors)) {
                $this->importError = "Import failed. No data was imported. Errors:\n";
                foreach ($errors as $error) {
                    $this->importError .= "Row {$error['row']}, Column '{$error['field']}': {$error['message']}\n";
                }
                Log::warning('Employee import failed, no data imported', [
                    'user_id' => auth()->id(),
                    'company_id' => $this->companyId,
                    'errors' => $errors
                ]);
                $this->isImporting = false;
                return;
            }
            $import->processImport();
            $this->importMessage = 'All employees imported successfully!';
            $this->isImporting = false;
        } catch (\Exception $e) {
            $this->importError = 'Error importing data: ' . $e->getMessage();
            $this->isImporting = false;
        }
    }

    public function exportToExcel()
    {
        try {
            $query = Employee::query()
                ->with(['department', 'designation', 'location'])  // Eager load the relationships
                ->where('company_id', request()->session()->get('companyId'));

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
