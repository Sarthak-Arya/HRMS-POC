<main class="main-content ui-page">
    <div class="container-fluid py-4">
        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Leave</h1>
                <p class="ui-page-subtitle">Apply for leave, track balances, and manage pending requests.</p>
            </div>
        </section>

        @if($flash)
            <div class="ui-alert-banner mb-3 {{ $flashType === 'error' ? 'ui-readiness--warn' : '' }}" role="status">
                <span class="ui-alert-banner-icon material-symbols-outlined">
                    {{ $flashType === 'error' ? 'error' : 'check_circle' }}
                </span>
                <div><p class="ui-alert-banner-title mb-0">{{ $flash }}</p></div>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-4">
                <section class="ui-panel">
                    <h2 class="ui-panel-title">Balances {{ now()->year }}</h2>
                    @forelse($balances as $balance)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>{{ $balance->leaveType?->name ?? 'Leave' }}
                                <span class="text-muted">({{ $balance->leaveType?->code }})</span>
                            </span>
                            <strong>{{ number_format((float) $balance->closing_balance, 1) }}</strong>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No balances yet. HR will allocate leave after setup.</p>
                    @endforelse
                </section>
            </div>
            <div class="col-lg-8">
                <section class="ui-panel mb-4">
                    <h2 class="ui-panel-title">Apply leave</h2>
                    <form wire:submit.prevent="submit" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Leave type</label>
                            <select class="form-control" wire:model="leave_type_id">
                                @foreach($leaveTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Day portion</label>
                            <select class="form-control" wire:model="day_portion">
                                @foreach($portions as $portion)
                                    <option value="{{ $portion->value }}">{{ $portion->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">From</label>
                            <input type="date" class="form-control" wire:model="start_date">
                            @error('start_date') <div class="text-danger text-sm">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To</label>
                            <input type="date" class="form-control" wire:model="end_date">
                            @error('end_date') <div class="text-danger text-sm">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" rows="2" wire:model.defer="reason"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="ui-btn-primary" @disabled($leaveTypes->isEmpty())>Submit request</button>
                        </div>
                    </form>
                </section>

                <section class="ui-panel">
                    <h2 class="ui-panel-title">History</h2>
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Dates</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $item)
                                    <tr>
                                        <td>{{ $item->leaveType?->code ?? '—' }}</td>
                                        <td>
                                            {{ $item->start_date->format('d M Y') }}
                                            @if(!$item->start_date->equalTo($item->end_date))
                                                – {{ $item->end_date->format('d M Y') }}
                                            @endif
                                            <div class="text-xs text-muted">{{ $item->day_portion?->label() }}</div>
                                        </td>
                                        <td>{{ number_format((float) $item->days, 1) }}</td>
                                        <td>{{ $item->status?->label() }}</td>
                                        <td class="text-end">
                                            @if($item->status === $pendingStatus)
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    wire:click="cancelRequest({{ $item->id }})"
                                                    onclick="return confirm('Cancel this leave request?')">
                                                    Cancel
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">No leave requests yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
