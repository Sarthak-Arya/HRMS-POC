<main class="main-content ui-page">
    <div class="container-fluid py-4">
        <section class="ui-page-header">
            <div>
                <p class="ui-dashboard-greeting">{{ $todayLabel }}</p>
                <h1 class="ui-page-title">Dashboard</h1>
                <p class="ui-page-subtitle">
                    {{ $companyName }}<span class="ui-page-subtitle-sep">|</span>
                    Workforce overview, payroll readiness, and quick navigation.
                </p>
            </div>
            <div class="ui-page-actions">
                @can('companies.manage_multiple')
                    <a href="{{ route('view-companies') }}" class="ui-btn-secondary">
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">corporate_fare</span>
                        Switch Company
                    </a>
                @endcan
                @can('employees.create')
                    @if($companyId)
                        <a href="{{ route('add-employee-details', ['company_id' => $companyId]) }}" class="ui-btn-primary">
                            <span class="material-symbols-outlined" style="font-size: 1.125rem;">person_add</span>
                            Add Employee
                        </a>
                    @endif
                @endcan
            </div>
        </section>

        @if(!$companyId)
            <section class="ui-alert-banner" role="alert">
                <span class="ui-alert-banner-icon material-symbols-outlined">info</span>
                <div>
                    <p class="ui-alert-banner-title mb-1">No company selected</p>
                    <p class="ui-alert-banner-body mb-0">
                        Choose a company to view workforce metrics, payroll status, and attendance coverage.
                        @can('companies.manage_multiple')
                            <a href="{{ route('view-companies') }}" class="ui-link-btn ms-1">Browse companies</a>
                        @else
                            <a href="{{ route('home') }}" class="ui-link-btn ms-1">Go to home</a>
                        @endcan
                    </p>
                </div>
            </section>
        @else
            <section class="ui-stat-grid">
                <a href="{{ route('view-employee-details', ['company_id' => $companyId]) }}" class="ui-stat-card ui-stat-card--accent ui-stat-card--link">
                    <p class="ui-stat-card-label">Total Employees</p>
                    <p class="ui-stat-card-value">{{ number_format($stats['total_employees'] ?? 0) }}</p>
                </a>
                <a href="{{ route('view-employee-details', ['company_id' => $companyId]) }}" class="ui-stat-card ui-stat-card--link">
                    <p class="ui-stat-card-label">Active</p>
                    <p class="ui-stat-card-value">{{ number_format($stats['active_employees'] ?? 0) }}</p>
                </a>
                <div class="ui-stat-card">
                    <p class="ui-stat-card-label">Inactive</p>
                    <p class="ui-stat-card-value ui-stat-card-value--muted">{{ number_format($stats['inactive_employees'] ?? 0) }}</p>
                </div>
                <a href="{{ route('view-employee-details', ['company_id' => $companyId]) }}" class="ui-stat-card ui-stat-card--link">
                    <p class="ui-stat-card-label">Under PF</p>
                    <p class="ui-stat-card-value">{{ number_format($stats['pf_employees'] ?? 0) }}</p>
                </a>
                <a href="{{ route('view-employee-details', ['company_id' => $companyId]) }}" class="ui-stat-card ui-stat-card--link">
                    <p class="ui-stat-card-label">Under ESI</p>
                    <p class="ui-stat-card-value">{{ number_format($stats['esi_employees'] ?? 0) }}</p>
                </a>
                <div class="ui-stat-card">
                    <p class="ui-stat-card-label">Departments · Locations</p>
                    <p class="ui-stat-card-value ui-stat-card-value--sm">
                        {{ number_format($stats['departments'] ?? 0) }}
                        <span class="ui-stat-card-sep">/</span>
                        {{ number_format($stats['locations'] ?? 0) }}
                    </p>
                </div>
            </section>

            @if($readiness)
                <section class="ui-readiness {{ $readiness['is_ready'] ? 'ui-readiness--ok' : 'ui-readiness--warn' }} mb-4">
                    <strong>{{ $readiness['ready_count'] }}/{{ $readiness['total_employees'] }}</strong>
                    employees ready for {{ $readiness['period_label'] }} payroll.
                    @if(!$readiness['is_ready'])
                        @if($readiness['missing_attendance_count'] > 0)
                            {{ $readiness['missing_attendance_count'] }} missing attendance.
                        @endif
                        @if($readiness['unlocked_count'] > 0)
                            {{ $readiness['unlocked_count'] }} unlocked summary(ies).
                        @endif
                        @if($readiness['missing_compensation_count'] > 0)
                            {{ $readiness['missing_compensation_count'] }} missing compensation.
                        @endif
                        @if($readiness['conflict_count'] > 0)
                            {{ $readiness['conflict_count'] }} reconciliation conflict(s).
                        @endif
                        @can('salary.generate')
                            <a href="{{ route('salary-generator', ['company_id' => $companyId]) }}" class="ui-link-btn ms-1">Open Payroll</a>
                        @endcan
                        @can('attendance.view')
                            <a href="{{ route('attendance', ['company_id' => $companyId]) }}" class="ui-link-btn ms-1">Attendance</a>
                        @endcan
                    @endif
                </section>
            @endif

            <div class="ui-insight-grid">
                <section class="ui-insight-card">
                    <div class="ui-insight-card-body">
                        <h3 class="ui-insight-card-title">Payroll Readiness</h3>
                        <p class="ui-insight-card-desc">
                            {{ $readiness['period_label'] ?? 'Current month' }} —
                            {{ $readiness['ready_count'] ?? 0 }} of {{ $readiness['total_employees'] ?? 0 }} eligible employees have attendance and compensation in place.
                        </p>
                        <div class="ui-insight-progress mb-3">
                            <div class="ui-insight-progress-bar" style="width: {{ max($readiness['ready_pct'] ?? 0, 4) }}%;"></div>
                        </div>
                        <div class="ui-insight-badges">
                            @if($currentPayrollRun)
                                <span class="ui-badge ui-badge--{{ $currentPayrollRun['status'] }}">
                                    {{ $currentPayrollRun['status_label'] }}
                                </span>
                                <span class="ui-meta">
                                    ₹{{ number_format($currentPayrollRun['net_pay'], 0) }} net · {{ $currentPayrollRun['employee_count'] }} processed
                                </span>
                                @can('salary.generate')
                                    <a href="{{ route('payroll-run-detail', ['company_id' => $companyId, 'run_id' => $currentPayrollRun['id']]) }}" class="ui-link-btn">
                                        Open Run
                                    </a>
                                @endcan
                            @else
                                <span class="ui-badge ui-badge--neutral">No run opened</span>
                                @can('salary.generate')
                                    <a href="{{ route('salary-generator', ['company_id' => $companyId]) }}" class="ui-link-btn">Start Payroll</a>
                                @endcan
                            @endif
                        </div>
                    </div>
                    <span class="ui-insight-watermark material-symbols-outlined">payments</span>
                </section>

                <section class="ui-insight-card">
                    <div class="ui-insight-card-body">
                        <h3 class="ui-insight-card-title">Attendance Coverage</h3>
                        <p class="ui-insight-card-desc">
                            {{ $attendanceSnapshot['period_label'] ?? 'Current month' }} —
                            {{ $attendanceSnapshot['summary_count'] ?? 0 }} summaries captured for {{ $attendanceSnapshot['active_employees'] ?? 0 }} active employees.
                        </p>
                        <div class="ui-insight-progress mb-3">
                            <div class="ui-insight-progress-bar" style="width: {{ max($attendanceSnapshot['coverage_pct'] ?? 0, 4) }}%;"></div>
                        </div>
                        <div class="ui-insight-badges">
                            <span class="ui-badge ui-badge--info">{{ $attendanceSnapshot['locked_count'] ?? 0 }} locked</span>
                            <span class="ui-meta">{{ $attendanceSnapshot['locked_pct'] ?? 0 }}% of summaries finalized</span>
                            @can('attendance.view')
                                <a href="{{ route('attendance', ['company_id' => $companyId]) }}" class="ui-link-btn">Attendance Hub</a>
                            @endcan
                        </div>
                    </div>
                    <span class="ui-insight-watermark material-symbols-outlined">calendar_month</span>
                </section>
            </div>

            <section class="ui-quick-actions-grid mb-4">
                @can('employees.view')
                    <a href="{{ route('view-employee-details', ['company_id' => $companyId]) }}" class="ui-quick-action-card">
                        <div class="ui-report-featured-icon ui-report-featured-icon--primary">
                            <span class="material-symbols-outlined">groups</span>
                        </div>
                        <h3 class="ui-quick-action-title">Employees</h3>
                        <p class="ui-quick-action-desc">Search, filter, and manage workforce records.</p>
                    </a>
                @endcan
                @can('employees.create')
                    <a href="{{ route('add-employee-details', ['company_id' => $companyId]) }}" class="ui-quick-action-card">
                        <div class="ui-report-featured-icon ui-report-featured-icon--success">
                            <span class="material-symbols-outlined">person_add</span>
                        </div>
                        <h3 class="ui-quick-action-title">Add Employee</h3>
                        <p class="ui-quick-action-desc">Create profiles or import from Excel.</p>
                    </a>
                @endcan
                @can('attendance.view')
                    <a href="{{ route('attendance', ['company_id' => $companyId]) }}" class="ui-quick-action-card">
                        <div class="ui-report-featured-icon ui-report-featured-icon--info">
                            <span class="material-symbols-outlined">event_available</span>
                        </div>
                        <h3 class="ui-quick-action-title">Attendance</h3>
                        <p class="ui-quick-action-desc">Policies, monthly entry, and daily marking.</p>
                    </a>
                @endcan
                @can('salary.generate')
                    <a href="{{ route('salary-generator', ['company_id' => $companyId]) }}" class="ui-quick-action-card">
                        <div class="ui-report-featured-icon ui-report-featured-icon--primary">
                            <span class="material-symbols-outlined">account_balance_wallet</span>
                        </div>
                        <h3 class="ui-quick-action-title">Payroll Runs</h3>
                        <p class="ui-quick-action-desc">Process monthly salary and adjustments.</p>
                    </a>
                @endcan
                @can('compensation.view')
                    <a href="{{ route('compensation', ['company_id' => $companyId]) }}" class="ui-quick-action-card">
                        <div class="ui-report-featured-icon ui-report-featured-icon--success">
                            <span class="material-symbols-outlined">tune</span>
                        </div>
                        <h3 class="ui-quick-action-title">Compensation</h3>
                        <p class="ui-quick-action-desc">Structures, components, and employee pay.</p>
                    </a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('reports', ['company_id' => $companyId]) }}" class="ui-quick-action-card">
                        <div class="ui-report-featured-icon ui-report-featured-icon--info">
                            <span class="material-symbols-outlined">assessment</span>
                        </div>
                        <h3 class="ui-quick-action-title">Reports</h3>
                        <p class="ui-quick-action-desc">Payroll registers, attendance, and exports.</p>
                    </a>
                @endcan
            </section>

            <div class="ui-two-col-layout">
                <section class="ui-card">
                    <div class="ui-card-header">
                        <div>
                            <h2 class="ui-card-title mb-0">Recent Employees</h2>
                            <p class="ui-card-desc mb-0">Latest additions and updates to your workforce.</p>
                        </div>
                        @can('employees.view')
                            <a href="{{ route('view-employee-details', ['company_id' => $companyId]) }}" class="ui-link-btn">View all</a>
                        @endcan
                    </div>
                    <div class="ui-data-table-wrap">
                        <table class="ui-data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentEmployees as $employee)
                                    <tr>
                                        <td class="text-mono">{{ $employee->employee_code }}</td>
                                        <td class="text-name">{{ $employee->employee_name }}</td>
                                        <td>{{ $employee->department->department_name ?? '—' }}</td>
                                        <td>
                                            <span class="ui-badge {{ $employee->dol ? 'ui-badge--danger' : 'ui-badge--success' }}">
                                                {{ $employee->dol ? 'Inactive' : 'Active' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @can('employees.view')
                                                <a class="ui-link-btn"
                                                    href="{{ route('employee-details', ['company_id' => $companyId, 'employee_id' => $employee->id]) }}">
                                                    View
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No employees yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="ui-card">
                    <div class="ui-card-header">
                        <div>
                            <h2 class="ui-card-title mb-0">Recent Payroll Runs</h2>
                            <p class="ui-card-desc mb-0">Latest monthly payroll activity.</p>
                        </div>
                        @can('salary.generate')
                            <a href="{{ route('salary-generator', ['company_id' => $companyId]) }}" class="ui-link-btn">All runs</a>
                        @endcan
                    </div>
                    <div class="ui-data-table-wrap">
                        <table class="ui-data-table">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Employees</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPayrollRuns as $run)
                                    <tr>
                                        <td class="text-name">{{ \Carbon\Carbon::create($run->year, $run->month)->format('M Y') }}</td>
                                        <td>
                                            <span class="ui-badge ui-badge--{{ strtolower($run->status->value) }}">
                                                {{ $run->status->value }}
                                            </span>
                                        </td>
                                        <td>{{ $run->employee_payrolls_count }}</td>
                                        <td class="text-end">
                                            @can('salary.generate')
                                                <a class="ui-link-btn"
                                                    href="{{ route('payroll-run-detail', ['company_id' => $companyId, 'run_id' => $run->id]) }}">
                                                    Open
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No payroll runs yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        @endif
    </div>
</main>
