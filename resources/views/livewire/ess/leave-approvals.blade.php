<main class="main-content ui-page">
    <div class="container-fluid py-4">
        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Leave approvals</h1>
                <p class="ui-page-subtitle">Review pending leave for your team{{ auth()->user()?->hasPermission(\App\Enums\Permission::EssApprovalsAny) ? ' (company-wide)' : '' }}.</p>
            </div>
        </section>

        @if($flash)
            <div class="ui-alert-banner mb-3" role="status">
                <span class="ui-alert-banner-icon material-symbols-outlined">
                    {{ $flashType === 'error' ? 'error' : 'check_circle' }}
                </span>
                <div><p class="ui-alert-banner-title mb-0">{{ $flash }}</p></div>
            </div>
        @endif

        <section class="ui-panel mb-3">
            <label class="form-label">Review note (optional)</label>
            <input type="text" class="form-control" wire:model.defer="reviewNote" placeholder="Add a note for approve / reject">
        </section>

        <section class="ui-panel">
            <div class="table-responsive">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Dates</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pending as $item)
                            <tr wire:key="leave-{{ $item->id }}">
                                <td>
                                    <strong>{{ $item->employee?->employee_name }}</strong>
                                    <div class="text-xs text-muted">{{ $item->employee?->employee_code }}</div>
                                </td>
                                <td>{{ $item->leaveType?->name }}</td>
                                <td>
                                    {{ $item->start_date->format('d M Y') }}
                                    @if(!$item->start_date->equalTo($item->end_date))
                                        – {{ $item->end_date->format('d M Y') }}
                                    @endif
                                </td>
                                <td>{{ number_format((float) $item->days, 1) }}</td>
                                <td class="text-sm">{{ $item->reason ?: '—' }}</td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm bg-gradient-success"
                                        wire:click="approve({{ $item->id }})">Approve</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="reject({{ $item->id }})">Reject</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">No pending leave requests.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
