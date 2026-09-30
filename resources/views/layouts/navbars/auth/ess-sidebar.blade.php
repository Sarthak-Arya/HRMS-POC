@php
    $companyId = request()->route('company_id') ?? request()->session()->get('companyId');
    $routeName = Route::currentRouteName();
@endphp

<aside class="sidenav ui-sidebar navbar navbar-vertical navbar-expand-xs fixed-start" id="sidenav-main">
    <div class="ui-sidebar-header">
        <a class="ui-sidebar-brand" href="{{ route('ess.home', ['company_id' => $companyId]) }}">
            <span class="ui-sidebar-brand-mark" aria-hidden="true">
                <span class="material-symbols-outlined">badge</span>
            </span>
            <span class="ui-sidebar-brand-text">
                <span class="ui-sidebar-brand-title">FlipCore</span>
                <span class="ui-sidebar-brand-subtitle">Employee Portal</span>
            </span>
        </a>
        <button id="sidebarCollapseBtn" type="button" class="ui-sidebar-collapse-btn" aria-label="Collapse sidebar">
            <span class="material-symbols-outlined">left_panel_close</span>
        </button>
    </div>

    <div class="collapse navbar-collapse ui-sidebar-body" id="sidenav-collapse-main">
        <nav class="ui-sidebar-nav" aria-label="Employee portal navigation">
            @can('ess.access')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('ess.home', ['company_id' => $companyId]),
                    'active' => $routeName === 'ess.home',
                    'icon' => 'home',
                    'label' => 'Home',
                ])
            @endcan

            @can('ess.profile')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('ess.profile', ['company_id' => $companyId]),
                    'active' => $routeName === 'ess.profile',
                    'icon' => 'person',
                    'label' => 'My Profile',
                ])
            @endcan

            <p class="ui-sidebar-section-label">Time off</p>

            @can('ess.leave')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('ess.leave', ['company_id' => $companyId]),
                    'active' => $routeName === 'ess.leave',
                    'icon' => 'event_busy',
                    'label' => 'Leave',
                ])
            @endcan

            @canany(['ess.approvals', 'ess.approvals.any'])
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('ess.approvals', ['company_id' => $companyId]),
                    'active' => $routeName === 'ess.approvals',
                    'icon' => 'task_alt',
                    'label' => 'Approvals',
                ])
            @endcanany

            <p class="ui-sidebar-section-label">Work</p>

            @can('ess.attendance')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('ess.attendance', ['company_id' => $companyId]),
                    'active' => $routeName === 'ess.attendance',
                    'icon' => 'fingerprint',
                    'label' => 'Attendance',
                ])
            @endcan

            @can('ess.payslips')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('ess.payslips', ['company_id' => $companyId]),
                    'active' => $routeName === 'ess.payslips',
                    'icon' => 'receipt_long',
                    'label' => 'Payslips',
                ])
            @endcan

            @can('ess.directory')
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('ess.directory', ['company_id' => $companyId]),
                    'active' => $routeName === 'ess.directory',
                    'icon' => 'group',
                    'label' => 'Directory',
                ])
            @endcan

            @can('dashboard.view')
                <p class="ui-sidebar-section-label">Admin</p>
                @include('layouts.navbars.auth.partials.ui-sidebar-link', [
                    'href' => route('dashboard', ['company_id' => $companyId]),
                    'active' => false,
                    'icon' => 'admin_panel_settings',
                    'label' => 'Admin console',
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
