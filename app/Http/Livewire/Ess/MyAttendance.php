<?php

namespace App\Http\Livewire\Ess;

use App\Http\Livewire\Ess\Concerns\ResolvesEmployeePortal;
use App\Models\EmployeeAttendance;
use App\Services\Attendance\DailyAttendanceService;
use App\Services\Ess\PunchService;
use Carbon\Carbon;
use Livewire\Component;

class MyAttendance extends Component
{
    use ResolvesEmployeePortal;

    public int $month;

    public int $year;

    public string $flash = '';

    public string $flashType = 'success';

    public ?array $todayPunch = null;

    public function mount(?string $company_id = null): void
    {
        $employee = $this->bootEmployeePortal($company_id);
        $this->month = (int) now()->month;
        $this->year = (int) now()->year;
        $this->refreshPunch($employee);
    }

    public function previousMonth(): void
    {
        $cursor = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->month = (int) $cursor->month;
        $this->year = (int) $cursor->year;
    }

    public function nextMonth(): void
    {
        $cursor = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->month = (int) $cursor->month;
        $this->year = (int) $cursor->year;
    }

    public function punch(PunchService $punches): void
    {
        $employee = $this->bootEmployeePortal($this->companyId);

        try {
            $result = $punches->punch($employee);
            $this->flash = $result['action'] === 'in'
                ? 'Punched in at '.$result['log']->clock_in->format('h:i A')
                : 'Punched out at '.$result['log']->clock_out->format('h:i A');
            $this->flashType = 'success';
            $this->refreshPunch($employee);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->flash = collect($e->errors())->flatten()->first() ?? 'Unable to punch.';
            $this->flashType = 'error';
        }
    }

    public function render(DailyAttendanceService $dailyAttendance)
    {
        $employee = $this->bootEmployeePortal($this->companyId);
        $start = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $records = $dailyAttendance->recordsForPeriod(
            (int) $this->companyId,
            [$employee->id],
            $start->toDateString(),
            $end->toDateString(),
        )->keyBy(fn (EmployeeAttendance $r) => $r->attendance_date->toDateString());

        $days = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $record = $records->get($key);
            $days[] = [
                'date' => $key,
                'label' => $day->format('D, M j'),
                'status' => $record?->attendance_status?->value ?? '—',
                'leave' => $record?->leaveType?->code,
            ];
        }

        return view('livewire.ess.my-attendance', [
            'days' => $days,
            'monthLabel' => $start->format('F Y'),
        ]);
    }

    private function refreshPunch($employee): void
    {
        $log = app(PunchService::class)->todaysLog($employee);
        $this->todayPunch = [
            'clock_in' => $log?->clock_in?->format('h:i A'),
            'clock_out' => $log?->clock_out?->format('h:i A'),
            'status' => ! $log ? 'not_in' : ($log->clock_out ? 'complete' : 'in'),
        ];
    }
}
