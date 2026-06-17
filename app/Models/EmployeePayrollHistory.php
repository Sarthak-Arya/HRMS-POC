<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayrollHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'employee_payroll_history';

    protected $fillable = [
        'employee_payroll_id',
        'version_no',
        'changed_by',
        'change_reason',
        'snapshot_json',
        'created_at',
    ];

    protected $casts = [
        'version_no' => 'integer',
        'snapshot_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function employeePayroll(): BelongsTo
    {
        return $this->belongsTo(EmployeePayroll::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
