<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Employee
 *
 * @property int $id
 * @property int $company_id
 * @property string $employee_code
 * @property string $employee_name
 * @property string|null $gender
 * @property string|null $father_name
 * @property int|null $location_id
 * @property \Illuminate\Support\Carbon|null $dob
 * @property \Illuminate\Support\Carbon $doj
 * @property \Illuminate\Support\Carbon|null $dol
 * @property string|null $present_address_line1
 * @property string|null $present_address_line2
 * @property string|null $present_city
 * @property string|null $present_state
 * @property string|null $present_pincode
 * @property string|null $present_country
 * @property string|null $permanent_address_line1
 * @property string|null $permanent_address_line2
 * @property string|null $permanent_city
 * @property string|null $permanent_state
 * @property string|null $permanent_pincode
 * @property string|null $permanent_country
 * @property string|null $pf_no
 * @property string|null $esi_no
 * @property int|null $department_id
 * @property int|null $designation_id
 * @property string|null $pay_mode
 * @property string|null $pf_mode
 * @property string|null $bank_name
 * @property string|null $bank_account_no
 * @property string|null $bank_ifsc_code
 * @property string|null $bank_account_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $full_name
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\Designation|null $designation
 * @property-read \App\Models\Location|null $location
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EmployeeCompensationHistory[] $compensationHistories
 * @property-read \App\Models\EmployeeCompensationHistory|null $activeCompensationHistory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PayrollHeader[] $payrollHeaders
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Earning[] $earnings
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Deduction[] $deductions
 */
class Employee extends Model
{
    use HasFactory;

    protected $casts = [
        'dob' => 'date',
        'doj' => 'date',
        'dol' => 'date',
    ];

    protected $table = 'employees';

    protected $fillable = [
        'company_id',
        'employee_code',
        'employee_name',
        'gender',
        'father_name',
        'location_id',
        'dob',
        'doj',
        'dol',
        'present_address_line1',
        'present_address_line2',
        'present_city',
        'present_state',
        'present_pincode',
        'present_country',
        'permanent_address_line1',
        'permanent_address_line2',
        'permanent_city',
        'permanent_state',
        'permanent_pincode',
        'permanent_country',
        'pf_no',
        'esi_no',
        'department_id',
        'designation_id',
        'pay_mode',
        'pf_mode',
        'bank_name',
        'bank_account_no',
        'bank_ifsc_code',
        'bank_account_type',
    ];

    /**
     * Get the company that the employee belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the department the employee belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the designation of the employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * Get the location where the employee is based.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the compensation history for the employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function compensationHistories()
    {
        return $this->hasMany(EmployeeCompensationHistory::class)->orderByDesc('effective_from');
    }

    /**
     * Get the current active compensation history for the employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function activeCompensationHistory()
    {
        return $this->hasOne(EmployeeCompensationHistory::class)
            ->whereNull('effective_to')
            ->latestOfMany('effective_from');
    }

    /**
     * Get the full name of the employee.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->employee_name;
    }

    /**
     * Get the payroll headers for the employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payrollHeaders()
    {
        return $this->hasMany(PayrollHeader::class);
    }

    /**
     * Get the earnings records for the employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function earnings()
    {
        return $this->hasMany(Earning::class);
    }

    /**
     * Get the deductions records for the employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }

    /**
     * Get the v2 employee payroll records for the employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employeePayrolls()
    {
        return $this->hasMany(EmployeePayroll::class);
    }

    /**
     * Get the employee loans.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function loans()
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    /**
     * Get the payroll adjustments for the employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payrollAdjustments()
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    /**
     * Get monthly attendance summaries for the employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function monthlyAttendances()
    {
        return $this->hasMany(MonthlyAttendance::class, 'employee_id');
    }
}
