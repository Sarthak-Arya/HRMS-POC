<div>
    @if (session()->has('compensation_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('compensation_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!$employee)
        <div class="alert alert-danger">Employee not found.</div>
    @else
        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6 class="mb-0">Assign Compensation</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Structure</label>
                            <select wire:model="structureId" class="form-control">
                                <option value="">Select structure</option>
                                @foreach($structures as $structure)
                                    <option value="{{ $structure->id }}">{{ $structure->structure_name }}</option>
                                @endforeach
                            </select>
                            @error('structure_id') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Annual CTC</label>
                                <input type="number" step="0.01" wire:model="annualCtc" class="form-control">
                                @error('annual_ctc') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monthly Gross</label>
                                <input type="number" step="0.01" wire:model="monthlyGross" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Effective From</label>
                            <input type="date" wire:model="effectiveFrom" class="form-control">
                            @error('effective_from') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Revision Reason</label>
                            <input type="text" wire:model="revisionReason" class="form-control" placeholder="e.g. Annual increment">
                        </div>
                        <button class="btn bg-gradient-dark" wire:click="saveRevision">Save Revision</button>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header pb-0">
                        <h6 class="mb-0">Revision History</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Structure</th>
                                    <th>CTC</th>
                                    <th>From</th>
                                    <th>To</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $revision)
                                    <tr>
                                        <td>{{ $revision->structure->structure_name ?? '—' }}</td>
                                        <td>{{ number_format($revision->annual_ctc, 0) }}</td>
                                        <td>{{ $revision->effective_from->format('d M Y') }}</td>
                                        <td>{{ $revision->effective_to?->format('d M Y') ?? 'Current' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted text-sm p-3">No compensation history yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header pb-0 d-flex justify-content-between">
                        <h6 class="mb-0">Resolved Compensation Preview</h6>
                        @if($resolved?->structureName)
                            <span class="badge bg-gradient-dark">{{ $resolved->structureName }}</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($resolved && $resolved->lines->isNotEmpty())
                            <p class="text-sm text-muted">
                                Source: {{ str_replace('_', ' ', $resolved->structureSource) }}
                                @if($resolved->annualCtc)
                                    · Annual CTC: {{ number_format($resolved->annualCtc, 2) }}
                                @endif
                            </p>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Component</th>
                                        <th>Type</th>
                                        <th>Source</th>
                                        <th class="text-end">Monthly</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($resolved->lines as $line)
                                        <tr>
                                            <td>{{ $line->componentName }}</td>
                                            <td>{{ $line->componentType->value }}</td>
                                            <td><span class="badge bg-secondary">{{ $line->source }}</span></td>
                                            <td class="text-end">{{ number_format($line->monthlyAmount ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Total Earnings</th>
                                        <th class="text-end">{{ number_format($resolved->totalMonthlyEarnings(), 2) }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="3">Total Deductions</th>
                                        <th class="text-end">{{ number_format($resolved->totalMonthlyDeductions(), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        @else
                            <p class="text-muted mb-0">Assign a structure and CTC to see the resolved payslip breakdown.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
