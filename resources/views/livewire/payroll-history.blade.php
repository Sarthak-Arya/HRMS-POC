<main class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div><h5 class="mb-0">Payroll History</h5><p class="text-sm text-muted mb-0">Completed and locked runs.</p></div>
            <a href="{{ route('salary-generator', ['company_id' => $companyId]) }}" class="btn btn-sm btn-outline-dark">Back</a>
        </div>
        <div class="card mb-4"><div class="card-body py-3"><label class="form-label text-sm me-2">Year</label><input type="number" class="form-control form-control-sm d-inline-block" wire:model="filterYear" style="width:100px" min="2000"></div></div>
        <div class="row">
            @forelse($runs as $run)
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card h-100"><div class="card-body">
                        <div class="d-flex justify-content-between mb-2"><h6 class="mb-0">{{ \Carbon\Carbon::create($run->year, $run->month)->format('F Y') }}</h6><span class="badge bg-gradient-{{ $run->status->value === 'LOCKED' ? 'dark' : 'success' }}">{{ $run->status->value }}</span></div>
                        <p class="text-sm text-muted mb-3">{{ $run->employee_payrolls_count }} employees · {{ $run->processed_at?->format('d M Y') ?? '—' }}</p>
                        <button class="btn btn-sm bg-gradient-dark" wire:click="openRun({{ $run->id }})">View Run</button>
                    </div></div>
                </div>
            @empty
                <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">No completed runs for {{ $filterYear }}.</div></div></div>
            @endforelse
        </div>
        {{ $runs->links() }}
    </div>
</main>
