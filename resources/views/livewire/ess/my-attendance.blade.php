<main class="main-content ui-page">
    <div class="container-fluid py-4">
        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Attendance</h1>
                <p class="ui-page-subtitle">Punch for today and review your month.</p>
            </div>
            <div class="ui-page-actions">
                <button type="button" class="ui-btn-secondary" wire:click="previousMonth">
                    <span class="material-symbols-outlined" style="font-size: 1.125rem;">chevron_left</span>
                </button>
                <span class="align-self-center px-2">{{ $monthLabel }}</span>
                <button type="button" class="ui-btn-secondary" wire:click="nextMonth">
                    <span class="material-symbols-outlined" style="font-size: 1.125rem;">chevron_right</span>
                </button>
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

        <section class="ui-panel mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="ui-panel-title mb-1">Today’s punch</h2>
                    <p class="mb-0 text-muted">
                        @if(($todayPunch['status'] ?? '') === 'not_in')
                            You have not punched in yet.
                        @elseif(($todayPunch['status'] ?? '') === 'in')
                            In at {{ $todayPunch['clock_in'] }} — punch out when you leave.
                        @else
                            Complete: {{ $todayPunch['clock_in'] }} – {{ $todayPunch['clock_out'] }}
                        @endif
                    </p>
                </div>
                <button type="button" class="ui-btn-primary" wire:click="punch"
                    @disabled(($todayPunch['status'] ?? '') === 'complete')>
                    {{ ($todayPunch['status'] ?? '') === 'not_in' ? 'Punch in' : (($todayPunch['status'] ?? '') === 'in' ? 'Punch out' : 'Done for today') }}
                </button>
            </div>
        </section>

        <section class="ui-panel">
            <h2 class="ui-panel-title">{{ $monthLabel }}</h2>
            <div class="table-responsive">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Leave</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($days as $day)
                            <tr>
                                <td>{{ $day['label'] }}</td>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $day['status']) }}</td>
                                <td>{{ $day['leave'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
