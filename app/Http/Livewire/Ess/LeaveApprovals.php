<?php

namespace App\Http\Livewire\Ess;

use App\Models\LeaveRequest;
use App\Services\Ess\LeaveRequestService;
use Livewire\Component;

class LeaveApprovals extends Component
{
    public string $companyId = '';

    public string $reviewNote = '';

    public string $flash = '';

    public string $flashType = 'success';

    public function mount(?string $company_id = null): void
    {
        $this->companyId = (string) ($company_id
            ?? request()->route('company_id')
            ?? request()->session()->get('companyId', ''));

        if ($this->companyId !== '') {
            request()->session()->put('companyId', $this->companyId);
        }
    }

    public function approve(int $requestId, LeaveRequestService $leaveRequests): void
    {
        $request = LeaveRequest::query()
            ->where('company_id', $this->companyId)
            ->findOrFail($requestId);

        try {
            $leaveRequests->approve($request, auth()->user(), $this->reviewNote ?: null);
            $this->reviewNote = '';
            $this->flash = 'Leave approved and attendance updated.';
            $this->flashType = 'success';
        } catch (\Throwable $e) {
            $this->flash = $e instanceof \Illuminate\Validation\ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Unable to approve.')
                : $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function reject(int $requestId, LeaveRequestService $leaveRequests): void
    {
        $request = LeaveRequest::query()
            ->where('company_id', $this->companyId)
            ->findOrFail($requestId);

        try {
            $leaveRequests->reject($request, auth()->user(), $this->reviewNote ?: null);
            $this->reviewNote = '';
            $this->flash = 'Leave request rejected.';
            $this->flashType = 'success';
        } catch (\Throwable $e) {
            $this->flash = $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function render(LeaveRequestService $leaveRequests)
    {
        $pending = $leaveRequests->pendingForApprover(auth()->user(), (int) $this->companyId);

        return view('livewire.ess.leave-approvals', [
            'pending' => $pending,
        ]);
    }
}
