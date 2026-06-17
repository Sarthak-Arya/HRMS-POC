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
use App\Services\Employee\EmployeeService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Exports\EmployeesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeeImport;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Livewire component for adding or editing employee details.
 * Supports manual form entry and Excel import/export.
 */
class AddEmployeeDetails extends Component
{
    use WithFileUploads;
    
    /** @var string The ID of the company */
    public string $companyId = '';

    /** @var int|null The ID of the employee if editing, null if creating */
    public ?int $employeeId = null;

    /** @var string Employee's first name */
    public string $firstName = '';
    
    /** @var string Employee's middle name */
    public string $middleName = '';
    
    /** @var string Employee's last name */
    public string $lastName = '';
    
    /** @var string Employee's father's name */
    public string $fatherName = '';
    
    /** @var string Employee's gender */
    public string $gender = '';
    
    /** @var string Employee's date of birth */
    public string $dob = '';
    
    /** @var string Employee's department name */
    public string $department = '';
    
    /** @var string Employee's designation name */
    public string $designation = '';
    
    /** @var string Employee's location name */
    public string $location = '';
    
    /** @var string Company name (display only) */
    public string $companyName = '';

    /** @var string Unique employee code assigned by the company */
    public string $employeeCompanyCode = '';

    /** @var string Date of joining */
    public string $joiningDate = '';

    /** @var string Date of leaving (null for active employees) */
    public string $leavingDate = '';

    /** @var string ESI number */
    public string $esiNo = '';

    /** @var string PF number */
    public string $pfNo = '';

    /** @var string Bank account number */
    public string $accountNo = '';

    /** @var string Bank name */
    public string $bankName = '';

    /** @var string Bank IFSC code */
    public string $ifscCode = '';

    /** @var string Currently selected department for filtering export */
    public $selectedDepartment = '';

    /** @var \Livewire\TemporaryUploadedFile|null Uploaded Excel file */
    public $excelFile;

    /** @var string Department for import (not used in current logic) */
    public $importDepartment = '';

    /** @var string Location for import (not used in current logic) */
    public $importLocation = '';

    /** @var bool Flag indicating if import is in progress */
    public $isImporting = false;

    /** @var string Success message from import */
    public $importMessage = '';

    /** @var string Error message(s) from import */
    public $importError = '';

    /**
     * Define validation rules for the form.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Normalize a heading cell for auto-detection of Excel headers.
     *
     * @param mixed $value Raw cell value.
     * @return string Normalized text.
     */
    private function normalizeHeadingCell(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return '';
        }

        $text = Str::lower($text);
        $text = str_replace(['_', '-', '/', '\\', '.', ':', '(', ')', '[', ']', '{', '}', "\n", "\r", "\t"], ' ', $text);
        $text = preg_replace('/\\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Auto-detect the heading row in an employee import file.
     *
     * @param string $filePath Path to the uploaded file.
     * @return int Detected heading row number.
     */
    private function detectHeadingRow(string $filePath): int
    {
        $maxScanRows = 40;

        $employeeCodeNeedles = [
            'employee code',
            'emp code',
            'empno',
            'emp no',
            'employee no',
        ];

        $nameNeedles = [
            'name',
            'employee name',
            'full name',
        ];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getSheet(0);
            $highestColumn = $sheet->getHighestColumn();
            $highestRow = min((int) $sheet->getHighestRow(), $maxScanRows);

            for ($row = 1; $row <= $highestRow; $row++) {
                $cells = $sheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, true, false);
                $values = $cells[0] ?? [];

                $normalized = [];
                foreach ($values as $cellValue) {
                    $n = $this->normalizeHeadingCell($cellValue);
                    if ($n !== '') {
                        $normalized[] = $n;
                    }
                }

                if (empty($normalized)) {
                    continue;
                }

                $rowText = ' ' . implode(' | ', $normalized) . ' ';

                $hasEmployeeCode = false;
                foreach ($employeeCodeNeedles as $needle) {
                    if (str_contains($rowText, $needle)) {
                        $hasEmployeeCode = true;
                        break;
                    }
                }

                $hasName = false;
                foreach ($nameNeedles as $needle) {
                    if (str_contains($rowText, $needle)) {
                        $hasName = true;
                        break;
                    }
                }

                if ($hasEmployeeCode && $hasName) {
                    return $row;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to auto-detect employee import heading row, falling back to row 1.', [
                'company_id' => $this->companyId,
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
        } finally {
            if (isset($spreadsheet)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
        }

        return 1;
    }

    /**
     * Initialize the component.
     *
     * @param string|null $company_id The ID of the company.
     * @param string|null $employee_id The ID of the employee for editing.
     * @return void
     */
    public function mount(?string $company_id = null, ?string $employee_id = null)
    {
        $this->companyId = $company_id ?? (string) session('companyId', '');
        if ($this->companyId !== '') {
            session(['companyId' => $this->companyId]);
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

    /**
     * Render the component view.
     *
     * @return \Illuminate\View\View
     */
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

    /**
     * Save the employee details from the form.
     *
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function save()
    {
        Log::debug("Saving employee data");

        try {
            $this->validate();

            $employeeService = app(EmployeeService::class);
            $result = $employeeService->createFromForm((int) $this->companyId, [
                'first_name' => $this->firstName,
                'middle_name' => $this->middleName,
                'last_name' => $this->lastName,
                'father_name' => $this->fatherName,
                'gender' => $this->gender,
                'dob' => $this->dob ?: null,
                'department' => $this->department,
                'designation' => $this->designation,
                'location' => $this->location,
                'employee_code' => $this->employeeCompanyCode,
                'joining_date' => $this->joiningDate ?: null,
                'leaving_date' => $this->leavingDate ?: null,
                'esi_no' => $this->esiNo ?: null,
                'pf_no' => $this->pfNo ?: null,
                'account_no' => $this->accountNo ?: null,
                'bank_name' => $this->bankName ?: null,
                'ifsc_code' => $this->ifscCode ?: null,
            ], $this->employeeId);

            if ($result['action'] === 'updated') {
                session()->flash('success', 'Employee details updated successfully!');
                return redirect()->route('employee-details', [
                    'company_id' => $this->companyId,
                    'employee_id' => $result['employee']->id,
                ]);
            }

            session()->flash('success', 'Employee details saved successfully!');
            $this->resetExcept('companyId');
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
     * Handle the Excel import of employees.
     *
     * @return void
     */
    public function importEmployees()
    {
        try {
            $this->isImporting = true;
            $this->importMessage = '';
            $this->importError = '';

            $this->validate([
                'excelFile' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            ]);

            Log::info('Starting employee import', [
                'user_id' => auth()->id(),
                'company_id' => $this->companyId,
                'file_name' => $this->excelFile ? $this->excelFile->getClientOriginalName() : null
            ]);

            $headingRow = 1;
            $realPath = $this->excelFile?->getRealPath();
            if (is_string($realPath) && $realPath !== '' && file_exists($realPath)) {
                $headingRow = app(EmployeeService::class)->detectHeadingRow($realPath);
            }

            $import = new EmployeeImport((int) $this->companyId, (int) $headingRow);
            Excel::import($import, $this->excelFile);

            $errors = $import->getErrors();

            $stats = method_exists($import, 'getImportStats') ? $import->getImportStats() : null;
            if ($stats) {
                $this->importMessage = "Imported {$stats['imported']} employees. Failed {$stats['failed']} rows. (Header row: {$headingRow})";
            } else {
                $this->importMessage = 'Employee import finished.';
            }

            if (!empty($errors)) {
                $this->importError = "Some rows were skipped/failed:\n";
                foreach (array_slice($errors, 0, 50) as $error) {
                    $this->importError .= "Row {$error['row']}: {$error['message']}\n";
                }
                if (count($errors) > 50) {
                    $this->importError .= '...more errors not shown';
                }
                Log::warning('Employee import completed with errors', [
                    'user_id' => auth()->id(),
                    'company_id' => $this->companyId,
                    'errors' => $errors,
                ]);
            }
        } catch (\Exception $e) {
            $this->importError = 'Error importing data: ' . $e->getMessage();
        } finally {
            $this->isImporting = false;
        }
    }

    /**
     * Export employees to an Excel file.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function exportToExcel()
    {
        try {
            $query = Employee::query()
                ->with(['department', 'designation', 'location'])  // Eager load the relationships
                ->where('company_id', $this->companyId);

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
