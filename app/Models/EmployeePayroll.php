<?php

namespace App\Models;

use App\Enums\Payroll\EmployeePayrollStatus;
use App\Services\Payroll\PayrollRunLifecycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeePayroll extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $payroll): void {
            app(PayrollRunLifecycle::class)->assertChildWritable($payroll);
        });

        static::deleting(function (self $payroll): void {
            app(PayrollRunLifecycle::class)->assertChildWritable($payroll);
        });
    }

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'attendance_summary_id',
        'employee_compensation_id',
        'gross_earnings',
        'gross_deductions',
        'employer_contributions',
        'net_pay',
        'status',
    ];

    protected $casts = [
        'gross_earnings' => 'decimal:2',
        'gross_deductions' => 'decimal:2',
        'employer_contributions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'status' => EmployeePayrollStatus::class,
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceSummary(): BelongsTo
    {
        return $this->belongsTo(MonthlyAttendance::class, 'attendance_summary_id');
    }

    public function employeeCompensation(): BelongsTo
    {
        return $this->belongsTo(EmployeeCompensationHistory::class, 'employee_compensation_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(EmployeePayrollLine::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(EmployeePayrollHistory::class)->orderByDesc('version_no');
    }
}
