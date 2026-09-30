<main class="main-content ui-page">
    <div class="container-fluid py-4">
        <section class="ui-page-header">
            <div>
                <p class="ui-dashboard-greeting">{{ $todayLabel }}</p>
                <h1 class="ui-page-title">Hi, {{ $employeeName }}</h1>
                <p class="ui-page-subtitle">
                    {{ $companyName }}<span class="ui-page-subtitle-sep">|</span>
                    Your leave, attendance, and pay in one place.
                </p>
            </div>
            <div class="ui-page-actions">
                @can('ess.attendance')
                    <a href="{{ route('ess.attendance', ['company_id' => $companyId]) }}" class="ui-btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">fingerprint</span>
                        Punch
                    </a>
                @endcan
                @can('ess.leave')
                    <a href="{{ route('ess.leave', ['company_id' => $companyId]) }}" class="ui-btn-secondary">
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">event_busy</span>
                        Apply Leave
                    </a>
                @endcan
            </div>
        </section>

        <section class="ui-stat-grid">
            <div class="ui-stat-card ui-stat-card--accent">
                <p class="ui-stat-card-label">Today</p>
                <p class="ui-stat-card-value ui-stat-card-value--sm">
                    @if(($todayPunch['status'] ?? '') === 'not_in')
                        Not punched in
                    @elseif(($todayPunch['status'] ?? '') === 'in')
                        In · {{ $todayPunch['clock_in'] }}
                    @else
                        {{ $todayPunch['clock_in'] }} – {{ $todayPunch['clock_out'] }}
                    @endif
                </p>
            </div>
            <div class="ui-stat-card">
                <p class="ui-stat-card-label">Leave balances</p>
                <p class="ui-stat-card-value ui-stat-card-value--sm">
                    @forelse(array_slice($balances, 0, 3) as $balance)
                        {{ $balance['code'] }} {{ number_format($balance['closing'], 1) }}@if(!$loop->last) · @endif
                    @empty
                        —
                    @endforelse
                </p>
            </div>
            @if($pendingApprovals > 0)
                <a href="{{ route('ess.approvals', ['company_id' => $companyId]) }}" class="ui-stat-card ui-stat-card--link">
                    <p class="ui-stat-card-label">Pending approvals</p>
                    <p class="ui-stat-card-value">{{ $pendingApprovals }}</p>
                </a>
            @endif
            @if($latestPayslip)
                <a href="{{ route('ess.payslips', ['company_id' => $companyId]) }}" class="ui-stat-card ui-stat-card--link">
                    <p class="ui-stat-card-label">Latest payslip</p>
                    <p class="ui-stat-card-value ui-stat-card-value--sm">{{ $latestPayslip['label'] }}</p>
                </a>
            @endif
        </section>

        <div class="row g-4 mt-1">
            <div class="col-lg-7">
                <section class="ui-panel">
                    <div class="ui-panel-header">
                        <h2 class="ui-panel-title">Recent leave</h2>
                        <a href="{{ route('ess.leave', ['company_id' => $companyId]) }}" class="ui-link-btn">View all</a>
                    </div>
                    @forelse($recentLeaves as $leave)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <strong>{{ $leave['type'] }}</strong>
                                <span class="text-muted ms-2">{{ $leave['range'] }}</span>
                            </div>
                            <span class="badge bg-gradient-{{ $leave['status_key'] === 'approved' ? 'success' : ($leave['status_key'] === 'pending' ? 'warning' : 'secondary') }}">
                                {{ $leave['status'] }}
                            </span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No leave requests yet.</p>
                    @endforelse
                </section>
            </div>
            <div class="col-lg-5">
                <section class="ui-panel">
                    <div class="ui-panel-header">
                        <h2 class="ui-panel-title">Upcoming holidays</h2>
                    </div>
                    @forelse($upcomingHolidays as $holiday)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>{{ $holiday['name'] }}</span>
                            <span class="text-muted">{{ $holiday['date'] }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No upcoming holidays.</p>
                    @endforelse
                </section>
            </div>
        </div>
    </div>
</main>
