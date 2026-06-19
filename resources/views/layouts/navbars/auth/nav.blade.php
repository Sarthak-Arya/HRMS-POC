<main class="main-content mt-1 border-radius-lg">
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur"
        navbar-scroll="true">
        <div class="container-fluid py-2 px-3">
            <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 min-w-0 navbar-page-title">
                    @if (!in_array(request()->route()->getName(), ['view-companies', 'add-company-details']))
                        <button type="button" class="btn btn-link nav-sidenav-toggle p-0 text-body flex-shrink-0" id="iconNavbarSidenav" aria-label="Toggle sidebar">
                            <span class="sidenav-toggler-inner">
                                <span class="sidenav-toggler-line"></span>
                                <span class="sidenav-toggler-line"></span>
                                <span class="sidenav-toggler-line"></span>
                            </span>
                        </button>
                    @endif
                    <nav aria-label="breadcrumb" class="min-w-0 navbar-breadcrumb-block">
                        <ol class="breadcrumb bg-transparent mb-1 pb-0 pt-0 px-0">
                            <li class="breadcrumb-item text-md"><a class="opacity-5 text-dark" href="javascript:;">Pages</a>
                            </li>
                            <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">
                                {{ str_replace('-', ' ', Route::currentRouteName()) }}</li>
                        </ol>
                        <h6 class="font-weight-bolder mb-0 text-capitalize text-dark">
                            {{ str_replace('-', ' ', Route::currentRouteName()) }}</h6>
                    </nav>
                </div>
                <div class="navbar-actions d-flex align-items-center flex-shrink-0">
                    @if (in_array(request()->route()->getName(), ['view-companies']))
                        <a class="btn btn-dark btn-sm text-nowrap" href="{{ route('add-company-details') }}">Add New Company</a>
                    @elseif (in_array(request()->route()->getName(), ['add-company-details']))
                        <a class="btn btn-dark btn-sm text-nowrap" href="{{ route('view-companies') }}">View All Companies</a>
                    @endif

                    <x-theme-toggle wrapper="div" />
                    <livewire:auth.logout />
                </div>
            </div>
        </div>
    </nav>
