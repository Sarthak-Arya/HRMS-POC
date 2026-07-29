<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\WeeklyOffRule;
use App\Models\AttendancePolicy;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class WeeklyOffPatternService
{
    /**
     * @return list<string> Date strings (Y-m-d)
     */
    public function resolveWeeklyOffDates(AttendancePolicy $policy, int $month, int $year): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $dates = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($this->isWeeklyOff($policy, $date)) {
                $dates[] = $date->toDateString();
            }
        }

        return $dates;
    }

    public function isWeeklyOff(AttendancePolicy $policy, Carbon $date): bool
    {
        $dayOfWeek = (int) $date->dayOfWeek;

        return match ($policy->weekly_off_rule) {
            WeeklyOffRule::SUNDAY => $dayOfWeek === Carbon::SUNDAY,
            WeeklyOffRule::SAT_SUN => in_array($dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true),
            WeeklyOffRule::ALTERNATE_SATURDAY => $this->isAlternateSaturdayOff($policy, $date),
            WeeklyOffRule::CUSTOM => in_array($dayOfWeek, $policy->custom_weekly_off_days ?? [], true),
        };
    }

    private function isAlternateSaturdayOff(AttendancePolicy $policy, Carbon $date): bool
    {
        if ($date->dayOfWeek !== Carbon::SATURDAY) {
            return $date->dayOfWeek === Carbon::SUNDAY;
        }

        $weekOfMonth = (int) ceil($date->day / 7);
        $offWeeks = $policy->alternate_saturday_weeks ?? [2, 4];

        return in_array($weekOfMonth, $offWeeks, true);
    }

    /**
     * @return list<int> Week numbers (1-5) that are off for alternate Saturday pattern
     */
    public function defaultAlternateSaturdayWeeks(): array
    {
        return [2, 4];
    }
}
