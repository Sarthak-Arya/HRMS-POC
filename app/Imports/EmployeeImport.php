<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Location;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithSkipDuplicates;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeImport implements ToCollection, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    private $rowNumber = 0;
    private $importedCount = 0;
    private $failedCount = 0;
    private $errors = [];
    private $preselectedLocation = null;
    private $preselectedDepartment = null;
    
    // Cache properties for lookups
    private $departmentCache = [];
    private $locationCache = [];
    private $designationCache = [];

    private $validRows = [];

    public function __construct($preselectedLocation = null, $preselectedDepartment = null)
    {
        HeadingRowFormatter::default('none');
        // If preselectedLocation is 'all', set it to null to use Excel location
        $this->preselectedLocation = ($preselectedLocation === 'all') ? null : $preselectedLocation;
        // If preselectedDepartment is 'all', set it to null to use Excel department
        $this->preselectedDepartment = ($preselectedDepartment === 'all') ? null : $preselectedDepartment;
    }

    /**
     * Map display names to field names
     */
    private function mapDisplayNamesToFields($row)
    {
        
        $mapping = [
            'First Name*' => 'first_name',
            'Middle Name' => 'middle_name',
            'Last Name*' => 'last_name',
            'Father Name*' => 'father_name',
            'Gender* (M/F/O)' => 'gender',
            'Date of Birth* (YYYY-MM-DD)' => 'dob',
            'Company Code' => 'company_code',
            'Joining Date* (YYYY-MM-DD)' => 'joining_date',
            'Leaving Date (YYYY-MM-DD)' => 'leaving_date',
            'ESI Number' => 'esi_no',
            'PF Number' => 'pf_no',
            'Department*' => 'department',
            'Designation*' => 'designation',
            'Location' => 'location',
            'Email' => 'email',
            'Address Line 1' => 'address1',
            'Address Line 2' => 'address2',
            'City' => 'city',
            'State' => 'state',
            'Country' => 'country',
            'Zip Code' => 'zip_code',
            'Permanent Address Line 1' => 'permanent_address1',
            'Permanent Address Line 2' => 'permanent_address2',
            'Permanent City' => 'permanent_city',
            'Permanent State' => 'permanent_state',
            'Permanent Country' => 'permanent_country',
            'Permanent Zip Code' => 'permanent_zip_code',
            'Emergency Contact Name' => 'emergency_contact_name',
            'Emergency Contact Phone' => 'emergency_contact_phone',
            'Emergency Contact Relation' => 'emergency_contact_relation',
            'Emergency Contact Address Line 1' => 'emergency_contact_address1',
            'Emergency Contact Address Line 2' => 'emergency_contact_address2',
            'Emergency Contact City' => 'emergency_contact_city',
            'Emergency Contact State' => 'emergency_contact_state',
            'Emergency Contact Country' => 'emergency_contact_country',
            'Emergency Contact Zip Code' => 'emergency_contact_zip_code',
            'Emergency Contact Email' => 'emergency_contact_email',
            'PF Status (active/inactive/pending)' => 'pf_status',
            'ESI Status (active/inactive/pending)' => 'esi_status',
            'Wage Type (monthly/daily/hourly)' => 'wage_type',
            'UAN Number'=> 'uan_no'
        ];

        $mappedRow = [];
        foreach ($row as $displayName => $value) {
            if (isset($mapping[$displayName])) {
                $mappedRow[$mapping[$displayName]] = $value;
            } else {
                // If no mapping found, keep the original key
                $mappedRow[$displayName] = $value;
            }
        }


        return $mappedRow;
    }

    function isValidExcelDate($value): bool
    {
        try {
            if (is_numeric($value)) {
                ExcelDate::excelToDateTimeObject($value);
            } else {
                Carbon::parse($value);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    function convertExcelDate($value): ?Carbon
{
    try {
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($value));
        } else {
            return Carbon::parse($value);
        }
    } catch (\Exception $e) {
        return null; // Invalid date
    }
}

    /**
     * Validate a single row based on the employees table schema.
     * Returns an array of validation issues (empty if valid).
     */
    private function validateRow($row, $rowNumber)
    {
        $validationIssues = [];
        // Required fields
        if (empty($row['first_name'])) {
            $validationIssues[] = "Row $rowNumber: First Name is required.";
        }
        // if (empty($row['last_name'])) {
        //     $validationIssues[] = "Row $rowNumber: Last Name is required.";
        // }
        // if (empty($row['father_name'])) {
        //     $validationIssues[] = "Row $rowNumber: Father Name is required.";
        // }
        if (empty($row['gender']) || !in_array(strtoupper($row['gender']), ['M','F','O'])) {
            $validationIssues[] = "Row $rowNumber: Gender is required and must be one of M, F, O.";
        }
        if (empty($row['dob']) || !$this->isValidExcelDate($row['dob'])) {
            $validationIssues[] = "Row $rowNumber: Date of Birth is required and must be a valid date.";
        }
        if (empty($row['joining_date']) || !$this->isValidExcelDate($row['joining_date'])) {
            $validationIssues[] = "Row $rowNumber: Joining Date is required and must be a valid date.";
        }
        // pf_status, esi_status, wage_type enums
        if (!empty($row['pf_status']) && !in_array($row['pf_status'], ['A','I','P'])) {
            $validationIssues[] = "Row $rowNumber: PF Status must be one of A, I, P.";
        }
        if (!empty($row['esi_status']) && !in_array($row['esi_status'], ['A','I','P'])) {
            $validationIssues[] = "Row $rowNumber: ESI Status must be one of A, I, P.";
        }
        if (!empty($row['wage_type']) && !in_array($row['wage_type'], ['M','D','H'])) {
            $validationIssues[] = "Row $rowNumber: Wage Type must be one of M, D, H.";
        }
        // Email format
        if (!empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $validationIssues[] = "Row $rowNumber: Email must be a valid email address.";
        }
        if (!empty($row['emergency_contact_email']) && !filter_var($row['emergency_contact_email'], FILTER_VALIDATE_EMAIL)) {
            $validationIssues[] = "Row $rowNumber: Emergency Contact Email must be a valid email address.";
        }
        // leaving_date after joining_date
        if (!empty($row['leaving_date']) && !empty($row['joining_date'])) {
            $joining = $this->convertExcelDate($row['joining_date']);
            $leaving = $this->convertExcelDate($row['leaving_date']);
            if ($leaving && $joining && $leaving < $joining) {
                $validationIssues[] = "Row $rowNumber: Leaving Date cannot be before joining_date.";
            }
        }
        // department and designation are nullable, so no required validation
        return $validationIssues;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->rowNumber++;
            $mappedRow = $this->mapDisplayNamesToFields($row->toArray());
            $validationIssues = $this->validateRow($mappedRow, $this->rowNumber);
            if (!empty($validationIssues)) {
                foreach ($validationIssues as $issue) {
                    $this->addError($this->rowNumber, 'validation', $issue);
                }
                $this->failedCount++;
            } else {
                $this->validRows[] = $mappedRow;
            }
        }
    }

    private function addError($rowNumber, $field, $message)
    {
        $this->errors[] = [
            'row' => $rowNumber,
            'field' => $field,
            'message' => $message
        ];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getImportStats()
    {

        return [
            'imported' => $this->importedCount,
            'failed' => $this->failedCount,
            'total_processed' => $this->rowNumber
        ];
    }

    public function processImport()
    {
        if (!empty($this->errors)) {
            // If there are errors, do not insert anything
            return false;
        }
        // Insert all valid rows in a transaction, in chunks
        DB::transaction(function () {
            foreach (array_chunk($this->validRows, 100) as $chunk) {
                foreach ($chunk as $row) {
                    $this->insertEmployee($row);
                }
            }
        });
        return true;
    }

    private function insertEmployee($row)
    {
        // Find department, designation, location as in model() logic
        $department = null;
        if (!empty($row['department'])) {
            $department = Department::where('department_name', $row['department'])
                ->where('company_id', request()->session()->get('companyId'))
                ->first();
        } elseif ($this->preselectedDepartment) {
            $department = Department::find($this->preselectedDepartment);
        }
        $designation = null;
        if (!empty($row['designation'])) {
            $designation = Designation::where('designation_name', $row['designation'])
                ->where('company_id', request()->session()->get('companyId'))
                ->first();
        }
        $location = null;
        if (isset($row['location']) && !empty($row['location'])) {
            $location = Location::where('name', $row['location'])
                ->where('company_id', request()->session()->get('companyId'))
                ->first();
        } elseif ($this->preselectedLocation) {
            $location = Location::find($this->preselectedLocation);
        }
        try {
            Employee::create([
                'company_id' => request()->session()->get('companyId'),
                'first_name' => $row['first_name'],
                'middle_name' => $row['middle_name'] ?? null,
                'last_name' => $row['last_name'],
                'father_name' => $row['father_name'],
                'gender' => strtoupper(substr($row['gender'], 0, 1)),
                'dob' => $this->convertExcelDate($row['dob']) ? $this->convertExcelDate($row['dob'])->format('Y-m-d') : null,
                'company_code' => $row['company_code'] ?? null,
                'joining_date' => $this->convertExcelDate($row['joining_date']) ? $this->convertExcelDate($row['joining_date'])->format('Y-m-d') : null,
                'leaving_date' => !empty($row['leaving_date']) && $this->convertExcelDate($row['leaving_date']) ? $this->convertExcelDate($row['leaving_date'])->format('Y-m-d') : null,
                'esi_no' => $row['esi_no'] ?? null,
                'pf_no' => $row['pf_no'] ?? null,
                'department_id' => $department ? $department->id : null,
                'designation_id' => $designation ? $designation->id : null,
                'location_id' => $location ? $location->id : null,
                'email' => $row['email'] ?? null,
                'address1' => $row['address1'] ?? null,
                'address2' => $row['address2'] ?? null,
                'city' => $row['city'] ?? null,
                'state' => $row['state'] ?? null,
                'country' => $row['country'] ?? null,
                'zip_code' => $row['zip_code'] ?? null,
                'permanent_address1' => $row['permanent_address1'] ?? null,
                'permanent_address2' => $row['permanent_address2'] ?? null,
                'permanent_city' => $row['permanent_city'] ?? null,
                'permanent_state' => $row['permanent_state'] ?? null,
                'permanent_country' => $row['permanent_country'] ?? null,
                'permanent_zip_code' => $row['permanent_zip_code'] ?? null,
                'emergency_contact_name' => $row['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $row['emergency_contact_phone'] ?? null,
                'emergency_contact_relation' => $row['emergency_contact_relation'] ?? null,
                'emergency_contact_address1' => $row['emergency_contact_address1'] ?? null,
                'emergency_contact_address2' => $row['emergency_contact_address2'] ?? null,
                'emergency_contact_city' => $row['emergency_contact_city'] ?? null,
                'emergency_contact_state' => $row['emergency_contact_state'] ?? null,
                'emergency_contact_country' => $row['emergency_contact_country'] ?? null,
                'emergency_contact_zip_code' => $row['emergency_contact_zip_code'] ?? null,
                'emergency_contact_email' => $row['emergency_contact_email'] ?? null,
                'pf_status' => $row['pf_status'] ?? null,
                'esi_status' => $row['esi_status'] ?? null,
                'wage_type' => $row['wage_type'] ?? null,
                'uan_no' => $row['uan_no'] ?? null,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $sqlState = $e->getCode();
            $errorCode = isset($e->errorInfo[1]) ? $e->errorInfo[1] : null;
            $userMessage = 'Database error occurred while importing employee.';
            // Map SQLSTATE and error codes to user-friendly messages
            if ($sqlState === '23000' && $errorCode == 1062) {
                $userMessage = 'Duplicate entry: a unique field value already exists.';
            } elseif ($sqlState === '23000' && $errorCode == 1048) {
                $userMessage = 'A required field is missing or empty.';
            } elseif ($sqlState === '23000' && $errorCode == 1452) {
                $userMessage = 'Foreign key constraint failed: referenced data not found.';
            } elseif ($sqlState === 'HY000' && $errorCode == 1366) {
                $userMessage = 'Invalid character encoding in one of the fields.';
            } elseif ($sqlState === 'HY000' && $errorCode == 1292) {
                $userMessage = 'Incorrect date/time value or format.';
            } elseif ($sqlState === '42000' && $errorCode == 1054) {
                $userMessage = 'Unknown column in the database. Please contact support.';
            } elseif ($sqlState === '42000' && $errorCode == 1064) {
                $userMessage = 'Database syntax error. Please contact support.';
            } elseif ($sqlState === '42S22' && $errorCode == 1054) {
                $userMessage = 'Column not found in the database. Please contact support.';
            } elseif ($sqlState === '23000' && $errorCode == 1216) {
                $userMessage = 'Cannot add child row: missing parent in related table.';
            } elseif ($sqlState === '23000' && $errorCode == 1217) {
                $userMessage = 'Cannot delete parent row: related data exists.';
            } elseif ($sqlState === '22007' && $errorCode == 1292) {
                $userMessage = 'Invalid date or datetime format.';
            }
            \Log::error('Employee import SQL error', [
                'row' => $this->rowNumber,
                'sqlstate' => $sqlState,
                'error_code' => $errorCode,
                'exception_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'row_data' => $row
            ]);
            $this->addError($this->rowNumber, 'database', $userMessage);
        }
    }


    public function headingRow(): int
    {
        return 1;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            Log::error("Validation failure in row {$failure->row()}", [
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
                'row_number' => $failure->row()
            ]);
            
            $this->addError($failure->row(), $failure->attribute(), implode(', ', $failure->errors()));
        }
    }

    public function onError(\Throwable $e)
    {
        Log::error('Import batch failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'company_id' => request()->session()->get('companyId')
        ]);
    }
}
