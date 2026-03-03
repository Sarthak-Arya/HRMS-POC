<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">{{ $companyName }}</h4>
                <div class="text-sm text-muted">Company dashboard</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('view-companies') }}" class="btn btn-sm btn-outline-dark">Switch Company</a>
                @if($companyId)
                    <a href="{{ route('add-employee-details', ['company_id' => $companyId]) }}" class="btn btn-sm bg-gradient-dark">Add Employee</a>
                @endif
            </div>
        </div>

        @if(!$companyId)
            <div class="alert alert-warning" role="alert">
                No company selected. Please choose a company to view the dashboard.
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <a class="card h-100" href="{{ $companyId ? route('view-employee-details', ['company_id' => $companyId]) : '#' }}">
                    <div class="card-body p-3">
                        <div class="text-sm text-muted">Total Employees</div>
                        <div class="h5 mb-0">{{ $stats['total_employees'] ?? 0 }}</div>
                    </div>
                </a>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <a class="card h-100" href="{{ $companyId ? route('view-employee-details', ['company_id' => $companyId]) : '#' }}">
                    <div class="card-body p-3">
                        <div class="text-sm text-muted">Active Employees</div>
                        <div class="h5 mb-0">{{ $stats['active_employees'] ?? 0 }}</div>
                    </div>
                </a>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <a class="card h-100" href="{{ $companyId ? route('view-employee-details', ['company_id' => $companyId]) : '#' }}">
                    <div class="card-body p-3">
                        <div class="text-sm text-muted">Under PF</div>
                        <div class="h5 mb-0">{{ $stats['pf_employees'] ?? 0 }}</div>
                    </div>
                </a>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <a class="card h-100" href="{{ $companyId ? route('view-employee-details', ['company_id' => $companyId]) : '#' }}">
                    <div class="card-body p-3">
                        <div class="text-sm text-muted">Under ESI</div>
                        <div class="h5 mb-0">{{ $stats['esi_employees'] ?? 0 }}</div>
                    </div>
                </a>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card h-100">
                    <div class="card-body p-3">
                        <div class="text-sm text-muted">Departments</div>
                        <div class="h5 mb-0">{{ $stats['departments'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card h-100">
                    <div class="card-body p-3">
                        <div class="text-sm text-muted">Locations</div>
                        <div class="h5 mb-0">{{ $stats['locations'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-8 mb-3">
                <div class="card h-100">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Recent Employees</h6>
                            @if($companyId)
                                <a class="text-sm" href="{{ route('view-employee-details', ['company_id' => $companyId]) }}">View all</a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if(!$companyId)
                            <div class="text-muted">Select a company to view employees.</div>
                        @elseif(($recentEmployees?->count() ?? 0) === 0)
                            <div class="text-muted">No employees yet.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Code</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Department</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Designation</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentEmployees as $employee)
                                            <tr>
                                                <td class="text-sm">{{ $employee->employee_code }}</td>
                                                <td class="text-sm">{{ $employee->employee_name }}</td>
                                                <td class="text-sm">{{ $employee->department->department_name ?? 'N/A' }}</td>
                                                <td class="text-sm">{{ $employee->designation->designation_name ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge badge-sm {{ $employee->dol ? 'bg-gradient-danger' : 'bg-gradient-success' }}">
                                                        {{ $employee->dol ? 'Inactive' : 'Active' }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a class="btn btn-sm btn-outline-dark"
                                                        href="{{ route('employee-details', ['company_id' => $companyId, 'employee_id' => $employee->id]) }}">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-header pb-0">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body pt-0 d-grid gap-2">
                        <a class="btn btn-outline-dark mb-0 {{ $companyId ? '' : 'disabled' }}"
                            href="{{ $companyId ? route('view-employee-details', ['company_id' => $companyId]) : '#' }}">
                            View Employees
                        </a>
                        <a class="btn btn-outline-dark mb-0 {{ $companyId ? '' : 'disabled' }}"
                            href="{{ $companyId ? route('add-employee-details', ['company_id' => $companyId]) : '#' }}">
                            Add Employee
                        </a>
                        <a class="btn btn-outline-dark mb-0 {{ $companyId ? '' : 'disabled' }}"
                            href="{{ $companyId ? route('attendance-entry', ['company_id' => $companyId]) : '#' }}">
                            Attendance Entry
                        </a>
                        <a class="btn btn-outline-dark mb-0 {{ $companyId ? '' : 'disabled' }}"
                            href="{{ $companyId ? route('salary-generator', ['company_id' => $companyId]) : '#' }}">
                            Salary Generator
                        </a>
                        <a class="btn btn-outline-dark mb-0 {{ $companyId ? '' : 'disabled' }}"
                            href="{{ $companyId ? route('compensation-structures', ['company_id' => $companyId]) : '#' }}">
                            Compensation Structures
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

