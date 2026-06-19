<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (!theme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                theme = 'dark';
            }
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <!-- Metas -->
    @if (env('IS_DEMO'))
        <x-demo-metas></x-demo-metas>
    @endif
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <title>
        Soft UI Dashboard by Creative Tim
    </title>

    <!-- Fonts and icons     -->
    <script src="https://kit.fontawesome.com/bcb22c69aa.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <!-- Nucleo Icons -->
    <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/nucleo-svg.css') }}" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link href="{{ asset('assets/css/nucleo-svg.css') }}" rel="stylesheet" />
    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link id="pagestyle" href="{{ asset('assets/css/soft-ui-dashboard.css') }}?v=1" rel="stylesheet" />
    <link href="{{ asset('assets/css/dark-mode.css') }}?v=8" rel="stylesheet" />
    <!-- Alpine -->
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    @livewireStyles
    <style>
        html,
        body {
            overflow-x: hidden;
        }

        .main-content {
            max-width: 100%;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Sidebar layout: offset main content only (not the body) */
        body.g-sidenav-pinned {
            margin-left: 0 !important;
            width: 100% !important;
        }

        @media (min-width: 1200px) {
            body.g-sidenav-pinned .sidenav.fixed-start ~ .main-content {
                margin-left: 17.125rem;
            }
        }

        body:not(.g-sidenav-pinned) .sidenav.fixed-start ~ .main-content {
            margin-left: 0 !important;
        }

        .nav-sidenav-toggle {
            width: 2.25rem;
            height: 2.25rem;
            overflow: visible;
        }

        .nav-sidenav-toggle .sidenav-toggler-inner {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            width: 18px;
            gap: 3px;
        }

        .nav-sidenav-toggle .sidenav-toggler-line {
            display: block;
            width: 18px;
            height: 2px;
            border-radius: 1px;
            background: currentColor;
            margin: 0;
        }

        .navbar-page-title {
            align-items: center;
        }

        .navbar-breadcrumb-block h6 {
            line-height: 1.2;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .theme-toggle-btn,
        .nav-logout-btn,
        .nav-sidenav-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2rem;
            line-height: 1;
            padding: 0;
            text-decoration: none;
            box-shadow: none;
        }

        .theme-toggle-btn {
            width: 2rem;
            font-size: 1rem;
        }

        .nav-logout-btn {
            gap: 0.375rem;
            font-weight: 600;
            font-size: 0.875rem;
            white-space: nowrap;
        }

        .navbar-vertical.navbar-expand-xs .navbar-nav {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }

        .navbar-vertical.navbar-expand-xs .navbar-nav .nav-link {
            margin-left: 0;
            margin-right: 0;
            align-items: center;
        }

        .navbar-vertical.navbar-expand-xs .navbar-nav .nav-link .icon {
            flex-shrink: 0;
            align-self: center;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            margin-top: 0;
            box-shadow: none !important;
        }

        .navbar-vertical.navbar-expand-xs .navbar-nav .nav-link {
            border-radius: 0.5rem;
        }

        .navbar-vertical.navbar-expand-xs .navbar-nav .nav-link .nav-link-text {
            line-height: 1.25;
        }

        .navbar-actions .btn-dark.btn-sm {
            padding: 0.45rem 1rem;
            font-size: 0.75rem;
            line-height: 1.4;
        }

        .dashboard-stat-card {
            overflow: hidden;
        }
    </style>

</head>

<body class="g-sidenav-show bg-gray-100">

    {{ $slot }}

    <!--   Core JS Files   -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/plugins/smooth-scrollbar.min.js') }}"></script>
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>
    <!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="{{ asset('assets/js/soft-ui-dashboard.js') }}"></script>
    <script>
        (function() {
            function preferredTheme() {
                if (localStorage.getItem('theme')) return localStorage.getItem('theme');
                return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' :
                    'light';
            }

            function syncThemeToggleIcons(isDark) {
                document.querySelectorAll('.theme-toggle-btn').forEach(function(btn) {
                    var moon = btn.querySelector('.theme-icon-moon');
                    var sun = btn.querySelector('.theme-icon-sun');
                    if (moon) moon.classList.toggle('d-none', isDark);
                    if (sun) sun.classList.toggle('d-none', !isDark);
                });
                var checkbox = document.getElementById('theme-toggle');
                if (checkbox) checkbox.checked = isDark;
            }

            function applyTheme(theme) {
                var isDark = theme === 'dark';
                document.documentElement.setAttribute('data-theme', theme);
                document.body.classList.toggle('theme-dark', isDark);
                localStorage.setItem('theme', theme);
                syncThemeToggleIcons(isDark);
            }

            window.__applyTheme = applyTheme;
            window.__toggleTheme = function() {
                var next = document.body.classList.contains('theme-dark') ? 'light' : 'dark';
                applyTheme(next);
            };

            document.addEventListener('DOMContentLoaded', function() {
                applyTheme(preferredTheme());
            });

            document.addEventListener('change', function(e) {
                if (e.target && e.target.id === 'theme-toggle') {
                    applyTheme(e.target.checked ? 'dark' : 'light');
                }
            });

            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                    if (!localStorage.getItem('theme')) {
                        applyTheme(e.matches ? 'dark' : 'light');
                    }
                });
            }

            document.addEventListener('livewire:load', function() {
                applyTheme(preferredTheme());
            });
            document.addEventListener('livewire:update', function() {
                syncThemeToggleIcons(document.body.classList.contains('theme-dark'));
            });
        })();
    </script>
    @livewireScripts
</body>

</html>
