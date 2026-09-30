<?php

namespace App\Services\Ess;

use App\Enums\Attendance\AttendanceLogSource;
use App\Enums\Attendance\AttendanceRecordSource;
use App\Enums\Attendance\AttendanceStatus;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PunchService
{
    /**
     * @return array{log: EmployeeAttendanceLog, action: string}
     */
    public function punch(Employee $employee, ?Carbon $at = null): array
    {
        $at = ($at ?? now())->copy();
        $date = $at->toDateString();

        return DB::transaction(function () use ($employee, $at, $date) {
            $log = EmployeeAttendanceLog::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->first();

            if (! $log) {
                $log = EmployeeAttendanceLog::query()->create([
                    'employee_id' => $employee->id,
                    'company_id' => $employee->company_id,
                    'attendance_date' => $date,
                    'clock_in' => $at,
                    'clock_out' => null,
                    'worked_minutes' => 0,
                    'late_minutes' => 0,
                    'early_exit_minutes' => 0,
                    'overtime_minutes' => 0,
                    'source' => AttendanceLogSource::WEB,
                ]);

                $this->markPresent($employee, $date);
                $action = 'in';
            } elseif ($log->clock_out === null) {
                if ($at->lte($log->clock_in)) {
                    throw ValidationException::withMessages([
                        'punch' => 'Clock-out time must be after clock-in.',
                    ]);
                }

                $worked = (int) $log->clock_in->diffInMinutes($at);
                $log->forceFill([
                    'clock_out' => $at,
                    'worked_minutes' => $worked,
                ])->save();
                $action = 'out';
            } else {
                throw ValidationException::withMessages([
                    'punch' => 'You have already completed punch in and out for today.',
                ]);
            }

            return [
                'log' => $log->fresh(),
                'action' => $action,
            ];
        });
    }

    public function todaysLog(Employee $employee, ?Carbon $date = null): ?EmployeeAttendanceLog
    {
        $date = ($date ?? now())->toDateString();

        return EmployeeAttendanceLog::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->first();
    }

    private function markPresent(Employee $employee, string $date): void
    {
        EmployeeAttendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'attendance_date' => $date,
            ],
            [
                'company_id' => $employee->company_id,
                'attendance_status' => AttendanceStatus::PRESENT->value,
                'leave_type_id' => null,
                'first_half_status' => null,
                'second_half_status' => null,
                'remarks' => 'Punched via employee portal',
                'source' => AttendanceRecordSource::MANUAL->value,
            ]
        );
    }
}
