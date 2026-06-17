<main class="main-content">
    <div class="container-fluid py-4">
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
            <div>
                <button class="btn btn-link text-sm text-muted p-0 mb-1" wire:click="backToRun">&larr; Back to run</button>
                <h5 class="mb-0">{{ $payroll->employee->employee_name }}</h5>
                <p class="text-sm text-muted mb-0">{{ $periodLabel }} · {{ $payroll->employee->employee_code }}</p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge badge-lg bg-gradient-{{ match($payroll->status->value) { 'DRAFT' => 'secondary', 'APPROVED' => 'success', default => 'info' } }}">{{ $payroll->status->value }}</span>
                @if(in_array($run->status->value, ['COMPLETED', 'LOCKED']))
                    <a href="{{ route('payroll.payslip', ['company_id' => $companyId, 'run_id' => $run->id, 'employee_payroll_id' => $payroll->id]) }}" class="btn btn-sm btn-outline-dark" target="_blank">Download Payslip</a>
                @endif
            </div>
        </div>

        <div class="row mb-4">
            @foreach([['Gross', $payroll->gross_earnings], ['Deductions', $payroll->gross_deductions], ['Employer', $payroll->employer_contributions], ['Net Pay', $payroll->net_pay]] as [$label, $amount])
                <div class="col-md-3 mb-3"><div class="card h-100"><div class="card-body p-3"><p class="text-sm text-muted mb-1">{{ $label }}</p><h5 class="mb-0 {{ $label === 'Net Pay' ? 'text-success' : '' }}">₹{{ number_format($amount, 2) }}</h5></div></div></div>
            @endforeach
        </div>

        @if(!$run->isLocked())
            <div class="d-flex gap-2 mb-4">
                @if($payroll->status->value === 'DRAFT')
                    <button class="btn btn-sm bg-gradient-dark" wire:click="approve">Approve</button>
                    <button class="btn btn-sm btn-outline-dark" wire:click="recalculate">Recalculate</button>
                @endif
                @if($payroll->status->value === 'APPROVED')
                    <button class="btn btn-sm btn-outline-secondary" wire:click="revertToDraft">Revert</button>
                    <button class="btn btn-sm btn-outline-info" wire:click="markPaid">Mark Paid</button>
                @endif
            </div>
        @endif

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card"><div class="card-header bg-gradient-success"><h6 class="text-white mb-0">Earnings</h6></div>
                    <table class="table mb-0"><thead><tr><th>Component</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>@forelse($earnings as $line)<tr><td>{{ $line->component_name }}</td><td class="text-end">₹{{ number_format($line->calculated_amount, 2) }}</td></tr>@empty<tr><td colspan="2" class="text-center text-muted py-3">None</td></tr>@endforelse
                    <tr class="fw-bold"><td>Total</td><td class="text-end">₹{{ number_format($payroll->gross_earnings, 2) }}</td></tr></tbody></table>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card"><div class="card-header bg-gradient-danger"><h6 class="text-white mb-0">Deductions</h6></div>
                    <table class="table mb-0"><thead><tr><th>Component</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>@forelse($deductions as $line)<tr><td>{{ $line->component_name }}</td><td class="text-end">₹{{ number_format($line->calculated_amount, 2) }}</td></tr>@empty<tr><td colspan="2" class="text-center text-muted py-3">None</td></tr>@endforelse
                    <tr class="fw-bold"><td>Total</td><td class="text-end">₹{{ number_format($payroll->gross_deductions, 2) }}</td></tr></tbody></table>
                </div>
            </div>
        </div>
    </div>
</main>
