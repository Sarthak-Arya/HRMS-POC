@include('livewire.partials.ui-data-grid-assets')

<main class="main-content ui-page" @if ($batchId) wire:poll.2s="pollBatchProgress" @endif>
    <div class="container-fluid py-4">
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button
                    type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button
                    type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <section class="ui-page-header">
            <div>
                <a href="{{ route('salary-generator', ['company_id' => $companyId]) }}" class="ui-back-link">
                    <span class="material-symbols-outlined">arrow_back</span>
                    All Payroll Runs
                </a>
                <h1 class="ui-page-title">{{ $periodLabel }} Payroll</h1>
                <p class="ui-page-subtitle">{{ $companySubtitle }}</p>
            </div>
            <div class="ui-page-actions">
                <span
                    class="ui-badge ui-badge--{{ strtolower($run->status->value) }}">{{ $run->status->value }}</span>
                @if (in_array($run->status->value, ['COMPLETED', 'LOCKED']))
                    <div class="dropdown">
                        <button class="ui-btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Export Salary Sheet
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Company-wise</h6></li>
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('payroll.salary-sheet', ['company_id' => $companyId, 'run_id' => $run->id, 'group_by' => 'company', 'format' => 'pdf']) }}"
                                    target="_blank">PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('payroll.salary-sheet', ['company_id' => $companyId, 'run_id' => $run->id, 'group_by' => 'company', 'format' => 'xlsx']) }}"
                                    target="_blank">Excel</a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Department-wise (ZIP)</h6></li>
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('payroll.salary-sheet', ['company_id' => $companyId, 'run_id' => $run->id, 'group_by' => 'department', 'format' => 'pdf']) }}"
                                    target="_blank">PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('payroll.salary-sheet', ['company_id' => $companyId, 'run_id' => $run->id, 'group_by' => 'department', 'format' => 'xlsx']) }}"
                                    target="_blank">Excel</a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Location-wise (ZIP)</h6></li>
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('payroll.salary-sheet', ['company_id' => $companyId, 'run_id' => $run->id, 'group_by' => 'location', 'format' => 'pdf']) }}"
                                    target="_blank">PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('payroll.salary-sheet', ['company_id' => $companyId, 'run_id' => $run->id, 'group_by' => 'location', 'format' => 'xlsx']) }}"
                                    target="_blank">Excel</a>
                            </li>
                        </ul>
                    </div>
                    <a href="{{ route('payroll.payslip.bulk', ['company_id' => $companyId, 'run_id' => $run->id]) }}"
                        class="ui-btn-secondary" target="_blank">
                        Export All Payslips
                    </a>
                @endif
            </div>
        </section>

        @if ($run->isLocked())
            <div class="ui-info-banner">
                <span class="material-symbols-outlined">lock</span>
                This payroll run is locked. All data is read-only.
            </div>
        @endif

        <section class="ui-stat-grid">
            <div class="ui-stat-card">
                <p class="ui-stat-card-label">Employees</p>
                <p class="ui-stat-card-value">{{ $summary['total'] }}</p>
            </div>
            <div class="ui-stat-card">
                <p class="ui-stat-card-label">Gross</p>
                <p class="ui-stat-card-value">₹{{ number_format($summary['gross'], 0) }}</p>
            </div>
            <div class="ui-stat-card ui-stat-card--danger">
                <p class="ui-stat-card-label">Deductions</p>
                <p class="ui-stat-card-value">₹{{ number_format($summary['deductions'], 0) }}</p>
            </div>
            <div class="ui-stat-card ui-stat-card--accent">
                <p class="ui-stat-card-label">Net Pay</p>
                <p class="ui-stat-card-value">₹{{ number_format($summary['net'], 0) }}</p>
            </div>
            <div class="ui-stat-card">
                <p class="ui-stat-card-label">Approved</p>
                <p class="ui-stat-card-value">{{ $summary['approved'] }}</p>
            </div>
            <div class="ui-stat-card">
                <p class="ui-stat-card-label">Paid</p>
                <p class="ui-stat-card-value">{{ $summary['paid'] }}</p>
            </div>
        </section>

        @if (!$readiness['is_ready'] && !$run->isLocked())
            <section class="ui-alert-banner">
                <div class="ui-alert-banner-icon">
                    <span class="material-symbols-outlined">warning</span>
                </div>
                <div>
                    <p class="ui-alert-banner-title">
                        Prerequisites Check:
                        <span
                            style="font-weight:500;">{{ $readiness['ready_count'] }}/{{ $readiness['total_employees'] }}
                            employees ready for processing.</span>
                    </p>
                    <p class="ui-alert-banner-body">
                        @if ($readiness['unlocked_summaries']->isNotEmpty())
                            {{ $readiness['unlocked_summaries']->count() }} employee(s) have unlocked attendance
                            summaries. Please review the pending items before finalizing.
                        @endif
                        @if (!empty($readiness['reconciliation_conflicts']))
                            {{ count($readiness['reconciliation_conflicts']) }} attendance reconciliation conflict(s).
                        @endif
                        @if (!empty($readiness['governance_messages']))
                            @foreach ($readiness['governance_messages'] as $msg)
                                {{ $msg }}
                            @endforeach
                        @endif
                        <span class="ui-meta-divider">•</span>
                        <a href="{{ route('attendance-entry', ['company_id' => $companyId]) }}">Attendance</a>
                        <span class="ui-meta-divider">·</span>
                        <a href="{{ route('compensation', ['company_id' => $companyId]) }}">Compensation</a>
                    </p>
                </div>
            </section>
        @endif

        @if ($batchId)
            <div class="ui-progress-block">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-sm">Processing payroll...</span>
                    <span class="text-sm font-weight-bold">{{ $batchProgress }}%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width: {{ $batchProgress }}%"></div>
                </div>
            </div>
        @endif

        <nav class="ui-tab-bar">
            @foreach (['employees' => 'Employees', 'adjustments' => 'Adjustments', 'history' => 'History & Audit'] as $tab => $label)
                <button type="button" class="ui-tab-btn {{ $activeTab === $tab ? 'active' : '' }}"
                    wire:click="setTab('{{ $tab }}')">{{ $label }}</button>
            @endforeach
        </nav>

        @if ($activeTab === 'employees')
            <section class="ui-card">
                <div class="ui-card-header">
                    <div>
                        <h2 class="ui-card-title">Employee Payroll</h2>
                        <p class="ui-card-desc">Review calculated earnings, approve records, and export
                            payslips.</p>
                    </div>
                    @if (!$run->isLocked())
                        <div class="ui-filters">
                            <select class="form-control form-control-sm ui-filter--department" wire:model="selectedDepartment">
                                <option value="">All departments</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
                                @endforeach
                            </select>
                            <select class="form-control form-control-sm ui-filter--designation" wire:model="selectedDesignation">
                                <option value="">All designations</option>
                                @foreach ($designations as $des)
                                    <option value="{{ $des->id }}">{{ $des->designation_name }}</option>
                                @endforeach
                            </select>
                            <div class="ui-search-field">
                                <span class="material-symbols-outlined">search</span>
                                <input type="text" class="ui-search-input" wire:model.debounce.300ms="search"
                                    placeholder="Search employees...">
                            </div>
                            <button type="button" class="ui-btn-primary" wire:click="processPayroll"
                                wire:loading.attr="disabled">Calculate</button>
                            <button type="button" class="ui-btn-secondary" wire:click="approveAll">Approve
                                All</button>
                            @if (in_array($run->status->value, ['PROCESSING', 'DRAFT']))
                                <button type="button" class="ui-btn-secondary" wire:click="completeRun"
                                    @if ($summary['draft'] > 0) disabled @endif>Complete</button>
                            @endif
                            @if ($run->status->value === 'COMPLETED')
                                <button type="button" class="ui-btn-secondary"
                                    wire:click="lockRun">Lock</button>
                                <button type="button" class="ui-btn-secondary" wire:click="markAllPaid">Mark
                                    Paid</button>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="ui-data-table-wrap">
                    <table class="ui-data-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Days</th>
                                <th>Gross</th>
                                <th>Deductions</th>
                                <th>Net</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employeePayrolls as $ep)
                                <tr>
                                    <td>
                                        <div class="text-name">{{ $ep->employee->employee_name }}</div>
                                        <div class="text-code">{{ $ep->employee->employee_code }}</div>
                                    </td>
                                    <td>{{ $ep->attendanceSummary?->worked_days ?? '—' }}/{{ $ep->attendanceSummary?->total_days ?? '—' }}
                                    </td>
                                    <td>₹{{ number_format($ep->gross_earnings, 2) }}</td>
                                    <td>₹{{ number_format($ep->gross_deductions, 2) }}</td>
                                    <td class="text-net">₹{{ number_format($ep->net_pay, 2) }}</td>
                                    <td>
                                        <span
                                            class="ui-badge ui-badge--{{ strtolower($ep->status->value === 'APPROVED' ? 'completed' : ($ep->status->value === 'PAID' ? 'locked' : 'draft')) }}">
                                            {{ $ep->status->value }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="ui-link-btn"
                                            wire:click="viewEmployeePayroll({{ $ep->id }})">Review</button>
                                        @if (in_array($run->status->value, ['COMPLETED', 'LOCKED']))
                                            <a href="{{ route('payroll.payslip', ['company_id' => $companyId, 'run_id' => $run->id, 'employee_payroll_id' => $ep->id]) }}"
                                                class="ui-link-btn ms-2" target="_blank">PDF</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No records yet. Click
                                        Calculate Payroll.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="ui-pagination-wrap">{{ $employeePayrolls->links() }}</div>
            </section>
        @endif

        @if ($activeTab === 'adjustments')
            <section class="ui-card">
                <div class="ui-card-header">
                    <div>
                        <h2 class="ui-card-title">Adjustments</h2>
                        <p class="ui-card-desc">Enter one-off bonuses and recoveries per employee. Each
                            adjustment type from Compensation appears as its own column below.</p>
                    </div>
                    <a href="{{ route('compensation', ['company_id' => $companyId]) }}"
                        class="ui-btn-secondary">Manage Adjustment Types</a>
                </div>

                <div class="ui-panel-grid-wrap">
                    @if ($adjustmentComponents->isEmpty())
                        <div class="p-4">
                            <div class="ui-alert-banner mb-0">
                                <div class="ui-alert-banner-icon">
                                    <span class="material-symbols-outlined">info</span>
                                </div>
                                <div>
                                    <p class="ui-alert-banner-title">No adjustment types configured</p>
                                    <p class="ui-alert-banner-body">
                                        Go to Compensation → Components → Add Adjustment Type (e.g. Performance Bonus,
                                        Advance Recovery) before adding adjustments here.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        @include('livewire.partials.payroll-adjustments-grid', [
                            'adjustmentMatrix' => $adjustmentMatrix,
                            'run' => $run,
                        ])
                    @endif
                </div>
            </section>
        @endif

        @if ($activeTab === 'history')
            <div class="ui-split-grid">
                <section class="ui-card">
                    <div class="ui-card-header">
                        <h2 class="ui-card-title">Version History</h2>
                    </div>
                    <div class="ui-data-table-wrap">
                        <table class="ui-data-table">
                            <thead>
                                <tr>
                                    <th>Ver</th>
                                    <th>Reason</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $h)
                                    <tr>
                                        <td>v{{ $h->version_no }}</td>
                                        <td>{{ $h->change_reason ?? '—' }}</td>
                                        <td>{{ $h->created_at?->format('d M Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No history.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
                <section class="ui-card">
                    <div class="ui-card-header">
                        <h2 class="ui-card-title">Audit Trail</h2>
                    </div>
                    <div class="ui-data-table-wrap ui-scroll-panel">
                        <table class="ui-data-table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>User</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td>{{ $log->event_type->value }}</td>
                                        <td>{{ $log->changedBy?->name ?? 'System' }}</td>
                                        <td>{{ $log->changed_at?->format('d M H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No audit events.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        @endif

        @if ($adjustmentComponents->isNotEmpty())
            <div id="payroll-adjustment-matrix-data" class="d-none" aria-hidden="true">@json($adjustmentMatrix)
            </div>
        @endif
    </div>
</main>
