<main class="main-content ui-page">
    <div class="container-fluid py-4">
        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Directory & holidays</h1>
                <p class="ui-page-subtitle">Find colleagues and company holidays for this year.</p>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-8">
                <section class="ui-panel">
                    <div class="ui-panel-header mb-3">
                        <h2 class="ui-panel-title mb-0">People</h2>
                        <input type="search" class="form-control" style="max-width: 240px"
                            placeholder="Search name or code" wire:model.debounce.300ms="search">
                    </div>
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $person)
                                    <tr>
                                        <td><strong>{{ $person->employee_name }}</strong></td>
                                        <td>{{ $person->employee_code }}</td>
                                        <td>{{ $person->department?->department_name ?? '—' }}</td>
                                        <td>{{ $person->designation?->designation_name ?? '—' }}</td>
                                        <td>{{ $person->work_email ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">No people matched.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
            <div class="col-lg-4">
                <section class="ui-panel">
                    <h2 class="ui-panel-title">Holidays {{ now()->year }}</h2>
                    @forelse($holidays as $holiday)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>{{ $holiday->name }}</span>
                            <span class="text-muted">{{ $holiday->holiday_date->format('M j') }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No holidays configured.</p>
                    @endforelse
                </section>
            </div>
        </div>
    </div>
</main>
