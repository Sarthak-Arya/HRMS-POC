@php
    $companyId = request()->session()->get('companyId');
    $routeName = Route::currentRouteName();
@endphp

<aside class="sidenav ui-sidebar navbar navbar-vertical navbar-expand-xs fixed-start"
    id="sidenav-main">
    <div class="ui-sidebar-header">
        <a class="ui-sidebar-brand"
            href="{{ route('dashboard', ['company_id' => $companyId]) }}">
            <span class="ui-sidebar-brand-mark" aria-hidden="true">
                <span class="material-symbols-outlined">payments</span>
            </span>
            <span class="ui-sidebar-brand-text">
                <span class="ui-sidebar-brand-title">FlipCore</span>
                <span class="ui-sidebar-brand-subtitle">Enterprise Admin</span>
            </span>
        </a>
        <button id="sidebarCollapseBtn" type="button" class="ui-sidebar-collapse-btn" aria-label="Collapse sidebar">
            <span class="material-symbols-outlined">left_panel_close</span>
        </button>
    </div>

    <div class="collapse navbar-collapse ui-sidebar-body" id="sidenav-collapse-main">
        <nav class="ui-sidebar-nav" aria-label="Main navigation">
            @can('dashboard.view')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('dashboard', ['company_id' => $companyId]),
                    'active' => $routeName === 'dashboard',
                    'icon' => 'dashboard',
                    'label' => 'Dashboard',
                ])
            @endcan

            @can('settings.view')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('getting-started', ['company_id' => $companyId]),
                    'active' => $routeName === 'getting-started',
                    'icon' => 'auto_awesome',
                    'label' => 'Getting Started',
                ])
            @endcan

            @canany(['employees.create', 'employees.edit', 'employees.view', 'attendance.view', 'attendance.manage'])
                <p class="ui-sidebar-section-label">Employee Operations</p>
            @endcanany

            @canany(['employees.create', 'employees.edit'])
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('add-employee-details', ['company_id' => $companyId]),
                    'active' => $routeName === 'add-employee-details',
                    'icon' => 'person_add',
                    'label' => 'Add Employee',
                ])
            @endcanany

            @can('employees.view')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('view-employee-details', ['company_id' => $companyId]),
                    'active' => $routeName === 'view-employee-details',
                    'icon' => 'group',
                    'label' => 'Employees',
                ])
            @endcan

            @canany(['attendance.view', 'attendance.manage'])
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('attendance', ['company_id' => $companyId]),
                    'active' => in_array($routeName, ['attendance', 'attendance-entry']),
                    'icon' => 'calendar_month',
                    'label' => 'Attendance',
                ])
            @endcanany

            @can('ai.assistant.use')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('ai-assistant', ['company_id' => $companyId]),
                    'active' => $routeName === 'ai-assistant',
                    'icon' => 'auto_awesome',
                    'label' => 'Payroll AI',
                ])
            @endcan

            @can('ess.access')
                @php
                    $linkedEmployee = auth()->user()?->employee;
                @endphp
                @if($linkedEmployee && (int) $linkedEmployee->company_id === (int) $companyId)
                    <p class="ui-sidebar-section-label">Self-Service</p>
                    @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                        'href' => route('ess.home', ['company_id' => $companyId]),
                        'active' => false,
                        'icon' => 'badge',
                        'label' => 'Employee Portal',
                    ])
                @endif
            @endcan

            @can('salary.generate')
                <p class="ui-sidebar-section-label">Salary Operations</p>
            @endcan

            @can('salary.generate')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('salary-generator', ['company_id' => $companyId]),
                    'active' => in_array($routeName, ['salary-generator', 'payroll-run-detail', 'employee-payroll-detail']),
                    'icon' => 'payments',
                    'label' => 'Payroll Runs',
                ])
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('payroll-history', ['company_id' => $companyId]),
                    'active' => $routeName === 'payroll-history',
                    'icon' => 'history',
                    'label' => 'Payroll History',
                ])
            @endcan

            @can('reports.view')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('reports', ['company_id' => $companyId]),
                    'active' => $routeName === 'reports',
                    'icon' => 'analytics',
                    'label' => 'Reports',
                ])
            @endcan

            @can('settings.view')
                <p class="ui-sidebar-section-label">Administration</p>
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('settings', [
                        'company_id' => $companyId,
                        'category' => 'organization-profile',
                    ]),
                    'active' => $routeName === 'settings',
                    'icon' => 'settings',
                    'label' => 'Settings',
                ])
            @endcan
        </nav>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sidebarBtn = document.getElementById('sidebarCollapseBtn');
        if (sidebarBtn) {
            sidebarBtn.addEventListener('click', function (e) {
                e.preventDefault();
                document.body.classList.remove('g-sidenav-pinned');
            });
        }
    });
</script>
