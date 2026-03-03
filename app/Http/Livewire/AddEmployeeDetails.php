<?php

namespace App\Http\Livewire;

use Exception;
use Illuminate\Database\QueryException;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Http\Controllers\ImportExcel;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Location;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Exports\EmployeesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeeImport;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AddEmployeeDetails extends Component
{
    use WithFileUploads;
    
    public string $companyId = '';
    public ?int $employeeId = null;

    // New properties for form data with default values
    public string $firstName = '';
    
    public string $middleName = '';
    
    public string $lastName = '';
    
    public string $fatherName = '';
    
    public string $gender = '';
    
    public string $dob = '';
    
    public string $department = '';
    
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

    protected function rules(): array
    {
        return [
            'companyId' => ['required', 'integer', 'exists:company,id'],
            'firstName' => ['required', 'string', 'min:2'],
            'middleName' => ['nullable', 'string'],
            'lastName' => ['required', 'string', 'min:2'],
            'fatherName' => ['required', 'string', 'min:2'],
            'gender' => ['required', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'department' => ['required', 'string', 'max:200'],
            'designation' => ['required', 'string', 'max:200'],
            'location' => ['required', 'string', 'max:255'],
            'employeeCompanyCode' => [
                'required',
                'string',
                'max:20',
                Rule::unique('employees', 'employee_code')->ignore($this->employeeId),
            ],
            'joiningDate' => ['nullable', 'date'],
            'leavingDate' => ['nullable', 'date', 'after_or_equal:joiningDate'],
            'esiNo' => ['nullable', 'string', 'max:20'],
            'pfNo' => ['nullable', 'string', 'max:20'],
            'accountNo' => ['nullable', 'string', 'max:20'],
            'bankName' => ['nullable', 'string', 'max:200'],
            'ifscCode' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function mount(?string $company_id = null, ?string $employee_id = null)
    {
        $this->companyId = $company_id ?? request()->session()->get('companyId', '');
        if ($this->companyId !== '') {
            request()->session()->put('companyId', $this->companyId);
        }

        $this->employeeId = $employee_id !== null ? (int) $employee_id : null;
        if ($this->employeeId) {
            $employee = Employee::with(['department', 'designation', 'location'])
                ->where('company_id', $this->companyId)
                ->find($this->employeeId);

            if ($employee) {
                $nameParts = preg_split('/\\s+/', trim((string) $employee->employee_name)) ?: [];
                $this->firstName = $nameParts[0] ?? '';
                $this->lastName = count($nameParts) > 1 ? $nameParts[count($nameParts) - 1] : '';
                if (count($nameParts) > 2) {
                    $this->middleName = implode(' ', array_slice($nameParts, 1, -1));
                }

                $this->fatherName = (string) ($employee->father_name ?? '');
                $this->gender = match ($employee->gender) {
                    'M' => 'male',
                    'F' => 'female',
                    'O' => 'other',
                    default => '',
                };

                $this->dob = $employee->dob ? $employee->dob->format('Y-m-d') : '';
                $this->joiningDate = $employee->doj ? $employee->doj->format('Y-m-d') : '';
                $this->leavingDate = $employee->dol ? $employee->dol->format('Y-m-d') : '';

                $this->employeeCompanyCode = (string) $employee->employee_code;
                $this->esiNo = (string) ($employee->esi_no ?? '');
                $this->pfNo = (string) ($employee->pf_no ?? '');

                $this->department = (string) ($employee->department->department_name ?? '');
                $this->designation = (string) ($employee->designation->designation_name ?? '');
                $this->location = (string) ($employee->location->location_name ?? $employee->location->name ?? '');

                $this->bankName = (string) ($employee->bank_name ?? '');
                $this->accountNo = (string) ($employee->bank_account_no ?? '');
                $this->ifscCode = (string) ($employee->bank_ifsc_code ?? '');
            }
        }
    }

    public function render()
    {
        $company = Company::with(['departments', 'locations', 'designations'])->find($this->companyId);
        $departments = $company ? $company->departments : collect();
        $locations = $company ? $company->locations : collect();
        $designations = $company ? $company->designations : collect();

        return view('livewire.add-employee-details', [
            "departments" => $departments,
            "locations" => $locations,
            "designations" => $designations,
            "departmentOptions" => $departments->pluck('department_name')->filter()->values()->all(),
            "designationOptions" => $designations->pluck('designation_name')->filter()->values()->all(),
            "locationOptions" => $locations->pluck('location_name')->filter()->values()->all(),
            "companyId" => $this->companyId,
            "employeeId" => $this->employeeId,
        ]);
    }

    public function save()
    {
        Log::debug("Saving employee data");

        try {
            $this->validate();

            $departmentName = trim($this->department);
            $designationName = trim($this->designation);
            $locationName = trim($this->location);

            $department = Department::firstOrCreate([
                'company_id' => (int) $this->companyId,
                'department_name' => $departmentName,
            ]);

            $designation = Designation::firstOrCreate([
                'company_id' => (int) $this->companyId,
                'designation_name' => $designationName,
            ]);

            $location = Location::query()
                ->where('company_id', (int) $this->companyId)
                ->whereRaw('LOWER(location_name) = ?', [Str::lower($locationName)])
                ->first();

            if (!$location) {
                $base = preg_replace('/[^A-Z0-9]/', '', Str::upper($locationName));
                $prefix = Str::substr($base ?: 'LOC', 0, 6);
                $locationCode = $prefix . Str::upper(Str::random(4));

                $location = Location::create([
                    'company_id' => (int) $this->companyId,
                    'location_name' => $locationName,
                    'location_code' => $locationCode,
                    'location_address' => '',
                    'location_city' => '',
                    'location_state' => '',
                    'location_pincode' => '',
                    'location_country' => '',
                    'location_phone' => '',
                    'location_email' => '',
                ]);

                Log::info('Created location on employee save', [
                    'company_id' => $this->companyId,
                    'location_id' => $location->id,
                    'location_name' => $locationName,
                ]);
            }

            $fullName = trim(implode(' ', array_filter([
                $this->firstName,
                $this->middleName,
                $this->lastName,
            ])));

            $payload = [
                'company_id' => (int) $this->companyId,
                'employee_code' => $this->employeeCompanyCode,
                'employee_name' => $fullName,
                'father_name' => $this->fatherName,
                'gender' => strtoupper(substr($this->gender, 0, 1)), // M/F/O
                'dob' => $this->dob ?: null,
                'doj' => $this->joiningDate ?: null,
                'dol' => $this->leavingDate ?: null,
                'esi_no' => $this->esiNo ?: null,
                'pf_no' => $this->pfNo ?: null,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'location_id' => $location->id,
                'bank_name' => $this->bankName ?: null,
                'bank_account_no' => $this->accountNo ?: null,
                'bank_ifsc_code' => $this->ifscCode ?: null,
            ];

            if ($this->employeeId) {
                $employee = Employee::where('company_id', $this->companyId)->findOrFail($this->employeeId);
                $employee->update($payload);
                session()->flash('success', 'Employee details updated successfully!');
                return redirect()->route('employee-details', ['company_id' => $this->companyId, 'employee_id' => $employee->id]);
            }

            $employee = Employee::create($payload);
            session()->flash('success', 'Employee details saved successfully!');
            $this->resetExcept('companyId'); // Clear the form after successful save
        }
        catch(QueryException $e){
            Log::error("Error occurred in saving employee to database due to SQL Exception: " . $e->getMessage(), [
                'company_id' => $this->companyId,
                'employee_code' => $this->employeeCompanyCode,
            ]);
            $message = config('app.debug')
                ? ('Failed to save employee details: ' . $e->getMessage())
                : 'Failed to save employee details. Database error occurred.';
            session()->flash('error', $message);
        }  
        catch (ValidationException $e) {
            // Let Livewire handle validation errors and display them next to fields.
            throw $e;
        }
        catch(Exception $e){
            Log::error("Error occurred in saving employee: " . $e->getMessage(), [
                'company_id' => $this->companyId,
                'employee_code' => $this->employeeCompanyCode,
            ]);
            $message = config('app.debug')
                ? ('Failed to save employee details: ' . $e->getMessage())
                : 'Failed to save employee details. Please try again.';
            session()->flash('error', $message);
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
