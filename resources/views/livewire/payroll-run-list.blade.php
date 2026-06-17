<main class="main-content">
    <div class="container-fluid py-4">
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h5 class="mb-0">Payroll Runs</h5>
                <p class="text-sm text-muted mb-0">Manage monthly payroll processing for your company.</p>
            </div>
            <a href="{{ route('payroll-history', ['company_id' => $companyId]) }}" class="btn btn-sm btn-outline-dark">View History</a>
        </div>

        <div class="row mb-4">
            @foreach(['DRAFT' => 'secondary', 'PROCESSING' => 'info', 'COMPLETED' => 'success', 'LOCKED' => 'dark'] as $status => $color)
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <p class="text-sm mb-1 text-capitalize text-muted">{{ strtolower($status) }} runs</p>
                            <h5 class="font-weight-bolder mb-0">{{ $statusCounts[$status] ?? 0 }}</h5>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header pb-0"><h6 class="mb-0">Start New Payroll</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-sm">Month</label>
                            <select class="form-control" wire:model="createMonth">
                                @foreach($monthOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-sm">Year</label>
                            <input type="number" class="form-control" wire:model="createYear" min="2000" max="2100">
                        </div>
                        @if($readiness)
                            <div class="alert alert-{{ $readiness['is_ready'] ? 'success' : 'warning' }} text-sm py-2">
                                <strong>{{ $readiness['ready_count'] }}/{{ $readiness['total_employees'] }}</strong> employees ready.
                            </div>
                        @endif
                        <button class="btn bg-gradient-dark w-100" wire:click="createRun" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="createRun">Open Payroll Run</span>
                            <span wire:loading wire:target="createRun">Opening...</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 pb-0">
                        <h6 class="mb-0">Payroll Runs</h6>
                        <div class="d-flex gap-2">
                            <select class="form-control form-control-sm" wire:model="filterMonth" style="width: 130px;">
                                <option value="">All months</option>
                                @foreach($monthOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="number" class="form-control form-control-sm" wire:model="filterYear" style="width: 90px;" min="2000">
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Period</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Employees</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Processed</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($runs as $run)
                                        <tr>
                                            <td><span class="text-sm font-weight-bold">{{ \Carbon\Carbon::create($run->year, $run->month)->format('F Y') }}</span></td>
                                            <td><span class="badge badge-sm bg-gradient-{{ match($run->status->value) { 'DRAFT' => 'secondary', 'PROCESSING' => 'info', 'COMPLETED' => 'success', default => 'dark' } }}">{{ $run->status->value }}</span></td>
                                            <td class="text-sm">{{ $run->employee_payrolls_count }}</td>
                                            <td class="text-sm">{{ $run->processed_at?->format('d M Y') ?? '—' }}</td>
                                            <td class="text-end"><button class="btn btn-link text-dark text-sm mb-0" wire:click="openRun({{ $run->id }})">Open</button></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">No payroll runs yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="px-3 pt-3">{{ $runs->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
