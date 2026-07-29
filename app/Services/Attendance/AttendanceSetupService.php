<?php

namespace App\Services\Attendance;

use App\Models\AttendancePolicy;
use App\Models\AttendancePolicyAssignment;
use App\Models\AttendancePolicyLeaveType;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;

class AttendanceSetupService
{
    /** @var array<string, array{name: string, is_paid: bool, annual_quota: float|null, carry_forward: bool, encashable: bool}> */
    private array $defaultLeaveTypes = [
        'CL' => ['name' => 'Casual Leave', 'is_paid' => true, 'annual_quota' => 7, 'carry_forward' => false, 'encashable' => false],
        'EL' => ['name' => 'Earned Leave', 'is_paid' => true, 'annual_quota' => 18, 'carry_forward' => true, 'encashable' => true],
        'SL' => ['name' => 'Sick Leave', 'is_paid' => true, 'annual_quota' => 12, 'carry_forward' => false, 'encashable' => false],
        'LWP' => ['name' => 'Leave Without Pay', 'is_paid' => false, 'annual_quota' => null, 'carry_forward' => false, 'encashable' => false],
    ];

    /** @var array<string, float> */
    private array $defaultPolicyQuotas = [
        'CL' => 7,
        'EL' => 18,
        'SL' => 12,
    ];

    public function seedCompanyDefaults(int $companyId): AttendancePolicy
    {
        $leaveTypeIds = $this->seedLeaveTypes($companyId);
        $policy = $this->seedDefaultPolicy($companyId, $leaveTypeIds);
        $this->seedPolicyAssignment($companyId, $policy->id);

        return $policy;
    }

    /**
     * @return array<string, int>
     */
    public function seedLeaveTypes(int $companyId): array
    {
        $ids = [];

        foreach ($this->defaultLeaveTypes as $code => $config) {
            $leaveType = LeaveType::query()->firstOrCreate(
                ['company_id' => $companyId, 'code' => $code],
                [
                    'name' => $config['name'],
                    'is_paid' => $config['is_paid'],
                    'annual_quota' => $config['annual_quota'],
                    'carry_forward' => $config['carry_forward'],
                    'encashable' => $config['encashable'],
                    'is_active' => true,
                ]
            );

            $ids[$code] = $leaveType->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $leaveTypeIds
     */
    public function seedDefaultPolicy(int $companyId, array $leaveTypeIds): AttendancePolicy
    {
        $policy = AttendancePolicy::query()->firstOrCreate(
            ['company_id' => $companyId, 'policy_name' => 'Company Policy'],
            [
                'attendance_mode' => 'monthly_summary',
                'working_days_basis' => 'fixed_26',
                'weekly_off_rule' => 'sunday',
                'grace_minutes' => 0,
                'min_half_day_minutes' => 240,
                'min_full_day_minutes' => 480,
                'allow_overtime' => false,
                'allow_half_day' => true,
                'allow_negative_leave_balance' => false,
                'is_active' => true,
            ]
        );

        foreach ($this->defaultPolicyQuotas as $code => $quota) {
            if (! isset($leaveTypeIds[$code])) {
                continue;
            }

            AttendancePolicyLeaveType::query()->firstOrCreate(
                ['policy_id' => $policy->id, 'leave_type_id' => $leaveTypeIds[$code]],
                [
                    'annual_quota' => $quota,
                    'carry_forward_limit' => $code === 'EL' ? 5 : null,
                    'encashable' => $code === 'EL',
                    'deduction_priority' => match ($code) {
                        'CL' => 10,
                        'EL' => 20,
                        'SL' => 30,
                        default => 100,
                    },
                ]
            );
        }

        return $policy;
    }

    public function seedPolicyAssignment(int $companyId, int $policyId): void
    {
        AttendancePolicyAssignment::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'scope_type' => 'company',
                'scope_id' => null,
                'policy_id' => $policyId,
            ],
            [
                'effective_from' => '2000-01-01',
                'effective_to' => null,
            ]
        );
    }

    /**
     * @return array<int, int> legacy attendance id => new summary id
     */
    public function migrateLegacyAttendanceRows(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('attendance')) {
            return [];
        }

        $idMap = [];
        $now = now();

        DB::table('attendance')->orderBy('id')->chunk(100, function ($rows) use (&$idMap, $now) {
            foreach ($rows as $row) {
                $companyId = (int) $row->company_id;
                $this->seedCompanyDefaults($companyId);
                $leaveTypeIds = $this->leaveTypeIdsForCompany($companyId);
                $policyId = AttendancePolicy::query()
                    ->where('company_id', $companyId)
                    ->where('policy_name', 'Company Policy')
                    ->value('id');

                $cl = (float) ($row->casual_leave ?? 0);
                $el = (float) ($row->earned_leave ?? 0);
                $sl = (float) ($row->sick_leave ?? 0);
                $holiday = (float) ($row->holiday ?? 0);
                $workedDays = (float) ($row->worked_days ?? 0);
                $totalDays = (float) ($row->total_days ?? 0);
                $month = (int) $row->month;
                $year = (int) $row->year;
                $calendarDays = (float) \Illuminate\Support\Carbon::createFromDate($year, $month, 1)->daysInMonth;
                $workingDays = $totalDays > 0 ? $totalDays : ($workedDays > 0 ? $workedDays : 26);

                DB::table('employee_attendance_summaries')->insert([
                    'id' => $row->id,
                    'employee_id' => $row->employee_id,
                    'company_id' => $companyId,
                    'month' => $month,
                    'year' => $year,
                    'policy_id' => $policyId,
                    'entry_source' => 'manual',
                    'calendar_days' => $calendarDays,
                    'working_days' => $workingDays,
                    'present_days' => $workedDays,
                    'half_days' => 0,
                    'paid_leave_days' => $cl + $el + $sl,
                    'lop_days' => 0,
                    'weekly_off_days' => 0,
                    'holiday_days' => $holiday,
                    'overtime_hours' => (float) ($row->overtime_hours ?? 0),
                    'worked_days' => $workedDays,
                    'overtime_days' => (float) ($row->overtime_days ?? 0),
                    'total_days' => $totalDays,
                    'prev_leave_days' => (float) ($row->prev_leave_days ?? 0),
                    'prev_leave_amount' => (float) ($row->prev_leave_amount ?? 0),
                    'esi_la' => (float) ($row->esi_la ?? 0),
                    'shift_code' => $row->shift_code ?? null,
                    'ded_1' => (float) ($row->ded_1 ?? 0),
                    'ded_2' => (float) ($row->ded_2 ?? 0),
                    'ded_3' => (float) ($row->ded_3 ?? 0),
                    'deductions' => $row->deductions ?? null,
                    'locked_at' => null,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->updated_at ?? $now,
                ]);

                $this->insertLeaveBreakdown((int) $row->id, $leaveTypeIds, $cl, $el, $sl, $now);
                $idMap[(int) $row->id] = (int) $row->id;
            }
        });

        return $idMap;
    }

    /**
     * @return array<string, int>
     */
    private function leaveTypeIdsForCompany(int $companyId): array
    {
        return LeaveType::query()
            ->where('company_id', $companyId)
            ->pluck('id', 'code')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  array<string, int>  $leaveTypeIds
     */
    private function insertLeaveBreakdown(int $summaryId, array $leaveTypeIds, float $cl, float $el, float $sl, $now): void
    {
        foreach (['CL' => $cl, 'EL' => $el, 'SL' => $sl] as $code => $days) {
            if ($days <= 0 || ! isset($leaveTypeIds[$code])) {
                continue;
            }

            DB::table('employee_attendance_summary_leaves')->insert([
                'summary_id' => $summaryId,
                'leave_type_id' => $leaveTypeIds[$code],
                'days' => $days,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
