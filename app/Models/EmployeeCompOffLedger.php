<?php

namespace App\Models;

use App\Enums\Attendance\CompOffEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCompOffLedger extends Model
{
    protected $table = 'employee_comp_off_ledger';

    protected $fillable = [
        'employee_id',
        'company_id',
        'reference_date',
        'entry_type',
        'days',
        'reason',
        'attendance_id',
    ];

    protected $casts = [
        'reference_date' => 'date',
        'entry_type' => CompOffEntryType::class,
        'days' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendance::class, 'attendance_id');
    }
}
