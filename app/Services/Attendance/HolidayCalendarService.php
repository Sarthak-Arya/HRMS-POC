<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\HolidaySourceType;
use App\Models\AttendancePolicy;
use App\Models\CompanyHoliday;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HolidayCalendarService
{
    /**
     * @return Collection<int, CompanyHoliday>
     */
    public function listForCompany(int $companyId, ?int $month = null, ?int $year = null): Collection
    {
        $query = CompanyHoliday::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('holiday_date');

        if ($month && $year) {
            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $query->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()]);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, array $data): CompanyHoliday
    {
        $validated = $this->validateHoliday($data);

        return CompanyHoliday::create(array_merge($validated, ['company_id' => $companyId]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $companyId, int $holidayId, array $data): CompanyHoliday
    {
        $holiday = CompanyHoliday::where('company_id', $companyId)->findOrFail($holidayId);
        $validated = $this->validateHoliday($data);
        $holiday->update($validated);

        return $holiday->fresh();
    }

    public function delete(int $companyId, int $holidayId): void
    {
        CompanyHoliday::where('company_id', $companyId)->findOrFail($holidayId)->delete();
    }

    /**
     * @return list<string> Date strings applicable to employee in month
     */
    public function resolveHolidayDatesForEmployee(
        Employee $employee,
        AttendancePolicy $policy,
        int $month,
        int $year,
    ): array {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $allowedSources = $this->allowedSourceTypes($policy);

        $holidays = CompanyHoliday::query()
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('source_type', array_map(fn ($s) => $s->value, $allowedSources))
            ->where(function ($q) use ($employee) {
                $q->whereNull('location_id')
                    ->orWhere('location_id', $employee->location_id);
            })
            ->orderBy('holiday_date')
            ->get();

        return $holidays
            ->unique(fn (CompanyHoliday $h) => $h->holiday_date->toDateString())
            ->pluck('holiday_date')
            ->map(fn ($d) => $d->toDateString())
            ->values()
            ->all();
    }

    /**
     * @return list<CompanyHoliday>
     */
    public function resolveHolidaysForEmployee(
        Employee $employee,
        AttendancePolicy $policy,
        int $month,
        int $year,
    ): array {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $allowedSources = $this->allowedSourceTypes($policy);

        return CompanyHoliday::query()
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('source_type', array_map(fn ($s) => $s->value, $allowedSources))
            ->where(function ($q) use ($employee) {
                $q->whereNull('location_id')
                    ->orWhere('location_id', $employee->location_id);
            })
            ->orderBy('holiday_date')
            ->get()
            ->unique(fn (CompanyHoliday $h) => $h->holiday_date->toDateString())
            ->values()
            ->all();
    }

    public function countHolidaysForMonth(int $companyId, int $month, int $year): int
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return CompanyHoliday::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
            ->count();
    }

    /**
     * @return list<HolidaySourceType>
     */
    private function allowedSourceTypes(AttendancePolicy $policy): array
    {
        $sources = [HolidaySourceType::COMPANY];

        if ($policy->include_public_holidays) {
            $sources[] = HolidaySourceType::PUBLIC;
        }
        if ($policy->include_regional_holidays) {
            $sources[] = HolidaySourceType::REGIONAL;
        }
        if ($policy->include_emergency_holidays) {
            $sources[] = HolidaySourceType::EMERGENCY;
        }

        return $sources;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateHoliday(array $data): array
    {
        $validator = Validator::make($data, [
            'holiday_date' => 'required|date',
            'name' => 'required|string|max:150',
            'source_type' => 'required|in:public,company,regional,emergency',
            'location_id' => 'nullable|integer|exists:locations,id',
            'region_code' => 'nullable|string|max:50',
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
            'is_recurring' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $validated['source_type'] = HolidaySourceType::from($validated['source_type'])->value;

        return $validated;
    }
}
