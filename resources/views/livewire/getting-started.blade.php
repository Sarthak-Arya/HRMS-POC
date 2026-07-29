<main class="main-content ui-page">
    <div class="container-fluid py-4">
        <section class="ui-page-header">
            <div>
                <p class="ui-dashboard-greeting">
                    <span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: -3px;">auto_awesome</span>
                    Getting Started
                </p>
                <h1 class="ui-page-title">Set up your organisation</h1>
                <p class="ui-page-subtitle">
                    {{ $progress['company_name'] ?? 'Your company' }}
                    <span class="ui-page-subtitle-sep">|</span>
                    Complete these steps so HR and payroll are ready to run.
                </p>
            </div>
            <div class="ui-page-actions">
                @if($canManageMultipleCompanies)
                    <a href="{{ route('view-companies') }}" class="ui-btn-secondary">
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">corporate_fare</span>
                        All Companies
                    </a>
                @endif
                @if($progress['is_complete'] ?? false)
                    <button type="button" class="ui-btn-primary" wire:click="goToDashboard">
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">dashboard</span>
                        Go to Dashboard
                    </button>
                @else
                    <a href="{{ route('dashboard', ['company_id' => $companyId]) }}" class="ui-btn-secondary">
                        Skip for now
                    </a>
                @endif
            </div>
        </section>

        <div class="ui-setup-card">
            <div class="ui-setup-card-header">
                <div>
                    <h2 class="ui-setup-card-title">Get started with PayrollPro</h2>
                    <p class="ui-setup-card-subtitle mb-0">
                        Follow the checklist below. You can return anytime from the sidebar.
                    </p>
                </div>
                <div class="ui-setup-progress" aria-label="Setup progress">
                    <div class="ui-setup-progress-meta">
                        <span class="ui-setup-progress-count">{{ $progress['completed_count'] }}/{{ $progress['total_count'] }}</span>
                        <span class="ui-setup-progress-label">Completed</span>
                    </div>
                    <div class="progress ui-setup-progress-bar" role="progressbar"
                         aria-valuenow="{{ $progress['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: {{ $progress['percent'] }}%;"></div>
                    </div>
                </div>
            </div>

            <ol class="ui-setup-steps">
                @foreach($progress['steps'] as $step)
                    @php
                        $isStatutory = $step['key'] === 'statutory_components';
                        $isOpen = $isStatutory && !empty($step['substeps']);
                    @endphp
                    <li class="ui-setup-step {{ $step['completed'] ? 'is-complete' : '' }} {{ $isOpen ? 'is-expanded' : '' }}">
                        <div class="ui-setup-step-main">
                            <div class="ui-setup-step-status" aria-hidden="true">
                                @if($step['completed'])
                                    <span class="material-symbols-outlined ui-setup-check">check_circle</span>
                                @else
                                    <span class="ui-setup-step-number">{{ $step['number'] }}</span>
                                @endif
                            </div>
                            <div class="ui-setup-step-body">
                                <div class="ui-setup-step-title-row">
                                    <h3 class="ui-setup-step-title">
                                        {{ $step['number'] }}. {{ $step['title'] }}
                                    </h3>
                                    @if($step['completed'])
                                        <span class="ui-setup-badge ui-setup-badge--done">COMPLETED</span>
                                    @endif
                                </div>
                                <p class="ui-setup-step-desc">{{ $step['description'] }}</p>

                                @if($isOpen)
                                    <ul class="ui-setup-substeps">
                                        @foreach($step['substeps'] as $sub)
                                            <li class="{{ $sub['completed'] ? 'is-complete' : '' }}">
                                                <span class="material-symbols-outlined">
                                                    {{ $sub['completed'] ? 'check_circle' : 'radio_button_unchecked' }}
                                                </span>
                                                {{ $sub['label'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="ui-setup-step-action">
                                <a href="{{ $step['action_url'] }}" class="{{ $step['completed'] ? 'ui-btn-secondary' : 'ui-btn-primary' }} ui-setup-action-btn">
                                    {{ $step['action_label'] }}
                                    <span class="material-symbols-outlined" style="font-size: 1rem;">chevron_right</span>
                                </a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>

            <div class="ui-setup-card-footer">
                <button type="button" class="ui-link-btn" wire:click="refreshProgress">
                    <span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: -3px;">refresh</span>
                    Refresh progress
                </button>
                @if($progress['is_complete'] ?? false)
                    <button type="button" class="ui-btn-primary" wire:click="goToDashboard">
                        Continue to Dashboard
                    </button>
                @endif
            </div>
        </div>
    </div>
</main>
