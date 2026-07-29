<main class="main-content ui-page">
    <div class="container-fluid py-4">
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Payroll Runs</h1>
                <p class="ui-page-subtitle">{{ $companyName }}<span
                        class="ui-page-subtitle-sep">|</span>Manage monthly payroll processing</p>
            </div>
            <div class="ui-page-actions">
                <a href="{{ route('payroll-history', ['company_id' => $companyId]) }}"
                    class="ui-btn-secondary">View History</a>
            </div>
        </section>

        <section class="ui-stat-grid ui-stat-grid--compact">
            @foreach (['DRAFT' => 'draft', 'PROCESSING' => 'processing', 'COMPLETED' => 'completed', 'LOCKED' => 'locked'] as $status => $variant)
                <div class="ui-stat-card">
                    <p class="ui-stat-card-label">{{ strtolower($status) }} runs</p>
                    <p class="ui-stat-card-value">{{ $statusCounts[$status] ?? 0 }}</p>
                </div>
            @endforeach
        </section>

        <div class="ui-two-col-layout">
            <section class="ui-card">
                <div class="ui-card-header">
                    <div>
                        <h2 class="ui-card-title">Start New Payroll</h2>
                        <p class="ui-card-desc">Open a payroll run for a month and year to begin processing.</p>
                    </div>
                </div>
                <div class="ui-form-body">
                    <div class="mb-3">
                        <label class="form-label">Month</label>
                        <select class="form-control" wire:model="createMonth">
                            @foreach ($monthOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" wire:model="createYear" min="2000" max="2100">
                    </div>
                    @if ($readiness)
                        <div
                            class="ui-readiness {{ $readiness['is_ready'] ? 'ui-readiness--ok' : 'ui-readiness--warn' }}">
                            <strong>{{ $readiness['ready_count'] }}/{{ $readiness['total_employees'] }}</strong>
                            employees ready.
                            @if ($readiness['unlocked_summaries']->isNotEmpty())
                                {{ $readiness['unlocked_summaries']->count() }} unlocked attendance summary(ies).
                            @endif
                            @if (!empty($readiness['reconciliation_conflicts']))
                                {{ count($readiness['reconciliation_conflicts']) }} reconciliation conflict(s).
                            @endif
                        </div>
                    @endif
                    <button type="button" class="ui-btn-primary w-100" wire:click="createRun"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="createRun">Open Payroll Run</span>
                        <span wire:loading wire:target="createRun">Opening...</span>
                    </button>
                </div>
            </section>

            <section class="ui-card">
                <div class="ui-card-toolbar">
                    <div>
                        <h2 class="ui-card-title mb-1">All Runs</h2>
                        <p class="ui-card-desc mb-0">Browse and open existing payroll runs.</p>
                    </div>
                    <div class="ui-filters">
                        <select class="form-control form-control-sm ui-filter--month" wire:model="filterMonth">
                            <option value="">All months</option>
                            @foreach ($monthOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="number" class="form-control form-control-sm ui-filter--year"
                            wire:model="filterYear" min="2000">
                    </div>
                </div>
                <div class="ui-data-table-wrap">
                    <table class="ui-data-table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Employees</th>
                                <th>Processed</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($runs as $run)
                                <tr>
                                    <td class="text-name">
                                        {{ \Carbon\Carbon::create($run->year, $run->month)->format('F Y') }}</td>
                                    <td>
                                        <span
                                            class="ui-badge ui-badge--{{ strtolower($run->status->value) }}">{{ $run->status->value }}</span>
                                    </td>
                                    <td>{{ $run->employee_payrolls_count }}</td>
                                    <td>{{ $run->processed_at?->format('d M Y') ?? '—' }}</td>
                                    <td class="text-end">
                                        <button type="button" class="ui-link-btn"
                                            wire:click="openRun({{ $run->id }})">Open</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No payroll runs yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="ui-pagination-wrap">{{ $runs->links() }}</div>
            </section>
        </div>
    </div>
</main>
