<?php

namespace App\Http\Livewire\Ess;

use App\Enums\Leave\LeaveDayPortion;
use App\Enums\Leave\LeaveRequestStatus;
use App\Http\Livewire\Ess\Concerns\ResolvesEmployeePortal;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Ess\LeaveRequestService;
use Livewire\Component;

class MyLeave extends Component
{
    use ResolvesEmployeePortal;

    public int $leave_type_id = 0;

    public string $start_date = '';

    public string $end_date = '';

    public string $day_portion = 'full';

    public string $reason = '';

    public string $flash = '';

    public string $flashType = 'success';

    public function mount(?string $company_id = null): void
    {
        $this->bootEmployeePortal($company_id);
        $this->start_date = now()->toDateString();
        $this->end_date = now()->toDateString();
    }

    public function submit(LeaveRequestService $leaveRequests): void
    {
        $this->validate([
            'leave_type_id' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'day_portion' => 'required|in:full,first_half,second_half',
            'reason' => 'nullable|string|max:1000',
        ]);

        $employee = $this->bootEmployeePortal($this->companyId);

        try {
            $leaveRequests->submit($employee, [
                'leave_type_id' => $this->leave_type_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'day_portion' => $this->day_portion,
                'reason' => $this->reason,
            ]);
            $this->reason = '';
            $this->flash = 'Leave request submitted for approval.';
            $this->flashType = 'success';
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->flash = collect($e->errors())->flatten()->first() ?? 'Unable to submit leave.';
            $this->flashType = 'error';
        }
    }

    public function cancelRequest(int $requestId, LeaveRequestService $leaveRequests): void
    {
        $employee = $this->bootEmployeePortal($this->companyId);
        $request = LeaveRequest::query()
            ->where('company_id', $this->companyId)
            ->where('employee_id', $employee->id)
            ->findOrFail($requestId);

        try {
            $leaveRequests->cancel($request, $employee);
            $this->flash = 'Leave request cancelled.';
            $this->flashType = 'success';
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->flash = collect($e->errors())->flatten()->first() ?? 'Unable to cancel.';
            $this->flashType = 'error';
        }
    }

    public function render()
    {
        $employee = $this->bootEmployeePortal($this->companyId);
        $year = (int) now()->year;

        $leaveTypes = LeaveType::query()
            ->where('company_id', $this->companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($this->leave_type_id === 0 && $leaveTypes->isNotEmpty()) {
            $this->leave_type_id = (int) $leaveTypes->first()->id;
        }

        $balances = EmployeeLeaveBalance::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get();

        $history = LeaveRequest::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->latest('id')
            ->limit(50)
            ->get();

        return view('livewire.ess.my-leave', [
            'leaveTypes' => $leaveTypes,
            'balances' => $balances,
            'history' => $history,
            'portions' => LeaveDayPortion::cases(),
            'pendingStatus' => LeaveRequestStatus::Pending,
        ]);
    }
}
