<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Support\Collection;

class AttendanceMonthlyMatrixService
{
    /**
     * @param  Collection<int, Employee>  $employees
     * @param  Collection<int, LeaveType>  $leaveTypes
     * @param  array<string, array<string, mixed>>  $monthlyData
     * @return array{
     *     rowData: list<array<string, mixed>>,
     *     leaveTypes: list<array{index: int, id: int, code: string, name: string, is_paid: bool}>,
     *     editable: bool,
     *     period: array{month: int, year: int}
     * }
     */
    public function buildMatrix(
        int $month,
        int $year,
        Collection $employees,
        Collection $leaveTypes,
        array $monthlyData,
        bool $editable,
    ): array {
        $rowData = [];
        $leaveTypeMeta = $leaveTypes->values()->map(fn (LeaveType $lt, int $idx) => [
            'index' => $idx,
            'id' => $lt->id,
            'code' => $lt->code,
            'name' => $lt->name,
            'is_paid' => (bool) $lt->is_paid,
        ])->all();

        foreach ($employees as $employee) {
            $rowKey = 'e'.$employee->id;
            $data = $monthlyData[$rowKey] ?? null;

            if (! is_array($data)) {
                continue;
            }

            $workingDays = (float) ($data['working_days'] ?? 26);
            $esiLeave = (float) ($data['esi_leave'] ?? 0);
            $holiday = (float) ($data['holiday'] ?? 0);

            $unpaidLeaveDays = 0.0;
            foreach ($leaveTypes as $idx => $lt) {
                if (! $lt->is_paid) {
                    $unpaidLeaveDays += (float) ($data['leave_'.$idx] ?? 0);
                }
            }

            $presentDays = max(0, $workingDays - $unpaidLeaveDays - $esiLeave - $holiday);

            $row = [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'employee_name' => $employee->employee_name,
                'department' => $employee->department?->department_name ?? '',
                'designation' => $employee->designation?->designation_name ?? '',
                'working_days' => $workingDays,
                'policy_resolved' => (bool) ($data['policy_resolved'] ?? false),
                'present_days' => $presentDays,
            ];

            foreach ($leaveTypes as $idx => $lt) {
                $row['leave_'.$idx] = (float) ($data['leave_'.$idx] ?? 0);
            }

            $row['esi_leave'] = $esiLeave;
            $row['holiday'] = $holiday;
            $row['tot_dys'] = (float) ($data['tot_dys'] ?? 0);

            $rowData[] = $row;
        }

        return [
            'rowData' => $rowData,
            'leaveTypes' => $leaveTypeMeta,
            'editable' => $editable,
            'period' => [
                'month' => $month,
                'year' => $year,
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $gridRows
     * @return array<int, array<string, mixed>>
     */
    public function rowsForSave(Collection $leaveTypes, array $gridRows): array
    {
        $rows = [];

        foreach ($gridRows as $row) {
            $employeeId = (int) ($row['employee_id'] ?? 0);
            if ($employeeId <= 0) {
                continue;
            }

            $leaves = [];
            foreach ($leaveTypes as $idx => $lt) {
                $leaves[$lt->code] = (float) ($row['leave_'.$idx] ?? 0);
            }

            $rows[$employeeId] = [
                'employee_id' => $employeeId,
                'working_days' => $row['working_days'] ?? 26,
                'leaves' => $leaves,
                'esi_leave' => $row['esi_leave'] ?? 0,
                'holiday' => $row['holiday'] ?? 0,
                'tot_dys' => $row['tot_dys'] ?? 0,
            ];
        }

        return $rows;
    }
}
