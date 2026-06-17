<main class="main-content" @if($batchId) wire:poll.2s="pollBatchProgress" @endif>
    <div class="container-fluid py-4">
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
            <div>
                <a href="{{ route('salary-generator', ['company_id' => $companyId]) }}" class="text-sm text-muted">&larr; All Payroll Runs</a>
                <h5 class="mb-0 mt-1">{{ $periodLabel }} Payroll</h5>
                <p class="text-sm text-muted mb-0">{{ $run->company->company_name ?? 'Company' }}</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge badge-lg bg-gradient-{{ $statusBadge }}">{{ $run->status->value }}</span>
                @if(in_array($run->status->value, ['COMPLETED', 'LOCKED']))
                    <a href="{{ route('payroll.payslip.bulk', ['company_id' => $companyId, 'run_id' => $run->id]) }}" class="btn btn-sm btn-outline-dark" target="_blank">Export All Payslips</a>
                @endif
            </div>
        </div>

        @if($run->isLocked())
            <div class="alert alert-info text-sm">This payroll run is locked. All data is read-only.</div>
        @endif

        <div class="row mb-4">
            @foreach([['Employees', $summary['total']], ['Gross', '₹'.number_format($summary['gross'], 2)], ['Deductions', '₹'.number_format($summary['deductions'], 2)], ['Net Pay', '₹'.number_format($summary['net'], 2)], ['Approved', $summary['approved']], ['Paid', $summary['paid']]] as [$label, $value])
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card h-100"><div class="card-body p-3"><p class="text-sm text-muted mb-1">{{ $label }}</p><h5 class="mb-0">{{ $value }}</h5></div></div>
                </div>
            @endforeach
        </div>

        @if(!$readiness['is_ready'] && !$run->isLocked())
            <div class="alert alert-warning text-sm">
                <strong>Prerequisites:</strong> {{ $readiness['ready_count'] }}/{{ $readiness['total_employees'] }} employees ready.
                <a href="{{ route('attendance-entry', ['company_id' => $companyId]) }}">Attendance</a> ·
                <a href="{{ route('compensation', ['company_id' => $companyId]) }}">Compensation</a>
            </div>
        @endif

        @if($batchId)
            <div class="card mb-4"><div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span class="text-sm">Processing payroll...</span><span class="text-sm font-weight-bold">{{ $batchProgress }}%</span></div>
                <div class="progress"><div class="progress-bar bg-gradient-dark" style="width: {{ $batchProgress }}%"></div></div>
            </div></div>
        @endif

        <ul class="nav nav-pills nav-fill mb-4">
            @foreach(['employees' => 'Employees', 'adjustments' => 'Adjustments', 'history' => 'History & Audit'] as $tab => $label)
                <li class="nav-item"><button type="button" class="nav-link {{ $activeTab === $tab ? 'active bg-gradient-dark' : '' }}" wire:click="setTab('{{ $tab }}')">{{ $label }}</button></li>
            @endforeach
        </ul>

        @if($activeTab === 'employees')
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 pb-0">
                    <h6 class="mb-0">Employee Payroll</h6>
                    @if(!$run->isLocked())
                        <div class="d-flex gap-2 flex-wrap">
                            <select class="form-control form-control-sm" wire:model="selectedDepartment" style="width: 140px;"><option value="">All departments</option>@foreach($departments as $dept)<option value="{{ $dept->id }}">{{ $dept->department_name }}</option>@endforeach</select>
                            <select class="form-control form-control-sm" wire:model="selectedDesignation" style="width: 140px;"><option value="">All designations</option>@foreach($designations as $des)<option value="{{ $des->id }}">{{ $des->designation_name }}</option>@endforeach</select>
                            <input type="text" class="form-control form-control-sm" wire:model.debounce.300ms="search" placeholder="Search..." style="width: 140px;">
                            <button class="btn btn-sm bg-gradient-dark" wire:click="processPayroll" wire:loading.attr="disabled">Calculate Payroll</button>
                            <button class="btn btn-sm btn-outline-dark" wire:click="approveAll">Approve All</button>
                            @if(in_array($run->status->value, ['PROCESSING', 'DRAFT']))
                                <button class="btn btn-sm btn-outline-success" wire:click="completeRun" @if($summary['draft'] > 0) disabled @endif>Complete</button>
                            @endif
                            @if($run->status->value === 'COMPLETED')
                                <button class="btn btn-sm btn-outline-danger" wire:click="lockRun">Lock</button>
                                <button class="btn btn-sm btn-outline-info" wire:click="markAllPaid">Mark Paid</button>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead><tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Employee</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Days</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Gross</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Deductions</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Net</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                <th></th>
                            </tr></thead>
                            <tbody>
                                @forelse($employeePayrolls as $ep)
                                    <tr>
                                        <td><div class="d-flex flex-column"><span class="text-sm font-weight-bold">{{ $ep->employee->employee_name }}</span><span class="text-xs text-muted">{{ $ep->employee->employee_code }}</span></div></td>
                                        <td class="text-sm">{{ $ep->attendanceSummary?->worked_days ?? '—' }}/{{ $ep->attendanceSummary?->total_days ?? '—' }}</td>
                                        <td class="text-sm">₹{{ number_format($ep->gross_earnings, 2) }}</td>
                                        <td class="text-sm">₹{{ number_format($ep->gross_deductions, 2) }}</td>
                                        <td class="text-sm font-weight-bold">₹{{ number_format($ep->net_pay, 2) }}</td>
                                        <td><span class="badge badge-sm bg-gradient-{{ match($ep->status->value) { 'DRAFT' => 'secondary', 'APPROVED' => 'success', default => 'info' } }}">{{ $ep->status->value }}</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-link text-dark text-sm mb-0" wire:click="viewEmployeePayroll({{ $ep->id }})">Review</button>
                                            @if(in_array($run->status->value, ['COMPLETED', 'LOCKED']))
                                                <a href="{{ route('payroll.payslip', ['company_id' => $companyId, 'run_id' => $run->id, 'employee_payroll_id' => $ep->id]) }}" class="btn btn-link text-sm mb-0" target="_blank">PDF</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">No records yet. Click Calculate Payroll.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 pt-3">{{ $employeePayrolls->links() }}</div>
                </div>
            </div>
        @endif

        @if($activeTab === 'adjustments')
            <div class="card">
                <div class="card-header d-flex justify-content-between"><h6 class="mb-0">Adjustments</h6>@if(!$run->isLocked())<button class="btn btn-sm bg-gradient-dark" wire:click="openAdjustmentModal">Add</button>@endif</div>
                <div class="card-body p-0">
                    <table class="table mb-0"><thead><tr><th>Employee</th><th>Type</th><th>Amount</th><th>Remarks</th></tr></thead>
                    <tbody>@forelse($adjustments as $adj)<tr><td class="text-sm">{{ $adj->employee->employee_name }}</td><td><span class="badge bg-gradient-{{ $adj->adjustment_type->value === 'ADDITION' ? 'success' : 'danger' }}">{{ $adj->adjustment_type->value }}</span></td><td>₹{{ number_format($adj->amount, 2) }}</td><td>{{ $adj->remarks ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">No adjustments.</td></tr>@endforelse</tbody></table>
                </div>
            </div>
        @endif

        @if($activeTab === 'history')
            <div class="row">
                <div class="col-md-6 mb-4"><div class="card"><div class="card-header"><h6 class="mb-0">Version History</h6></div><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Ver</th><th>Reason</th><th>When</th></tr></thead><tbody>@forelse($history as $h)<tr><td>v{{ $h->version_no }}</td><td>{{ $h->change_reason ?? '—' }}</td><td>{{ $h->created_at?->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-3">No history.</td></tr>@endforelse</tbody></table></div></div></div>
                <div class="col-md-6 mb-4"><div class="card"><div class="card-header"><h6 class="mb-0">Audit Trail</h6></div><div class="card-body p-0" style="max-height:400px;overflow-y:auto"><table class="table mb-0"><thead><tr><th>Event</th><th>User</th><th>When</th></tr></thead><tbody>@forelse($auditLogs as $log)<tr><td>{{ $log->event_type->value }}</td><td>{{ $log->changedBy?->name ?? 'System' }}</td><td>{{ $log->changed_at?->format('d M H:i') }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-3">No audit events.</td></tr>@endforelse</tbody></table></div></div></div>
            </div>
        @endif
    </div>

    @if($showAdjustmentModal)
        <div class="modal show d-block" style="background:rgba(0,0,0,.5)"><div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h6 class="modal-title">Add Adjustment</h6><button class="btn-close" wire:click="closeAdjustmentModal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label text-sm">Employee</label><select class="form-control" wire:model="adjustmentEmployeeId"><option value="">Select</option>@foreach($employees as $emp)<option value="{{ $emp->id }}">{{ $emp->employee_name }}</option>@endforeach</select>@error('adjustmentEmployeeId')<span class="text-danger text-xs">{{ $message }}</span>@enderror</div>
                <div class="mb-3"><label class="form-label text-sm">Type</label><select class="form-control" wire:model="adjustmentType"><option value="ADDITION">Addition</option><option value="DEDUCTION">Deduction</option></select></div>
                <div class="mb-3"><label class="form-label text-sm">Amount</label><input type="number" step="0.01" class="form-control" wire:model="adjustmentAmount">@error('adjustmentAmount')<span class="text-danger text-xs">{{ $message }}</span>@enderror</div>
                <div class="mb-3"><label class="form-label text-sm">Remarks</label><textarea class="form-control" wire:model="adjustmentRemarks" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button class="btn btn-outline-secondary btn-sm" wire:click="closeAdjustmentModal">Cancel</button><button class="btn bg-gradient-dark btn-sm" wire:click="saveAdjustment">Save</button></div>
        </div></div></div>
    @endif
</main>
