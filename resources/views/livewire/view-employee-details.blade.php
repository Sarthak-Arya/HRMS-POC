<main class="main-content">
    <div class="container-fluid py-4">
        @if(!$employee)
            <div class="alert alert-danger" role="alert">
                Employee not found.
            </div>
        @else
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0">{{ $employee->employee_name }}</h5>
                    <div class="text-sm text-muted">Employee Code: {{ $employee->employee_code }}</div>
                </div>
                <a href="{{ route('edit-employee-details', ['company_id' => $companyId, 'employee_id' => $employee->id]) }}"
                    class="btn btn-sm bg-gradient-dark">
                    Edit
                </a>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header pb-0">
                            <h6 class="mb-0">Basic</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2"><span class="text-sm text-muted">Father Name:</span> {{ $employee->father_name ?? 'N/A' }}</div>
                            <div class="mb-2"><span class="text-sm text-muted">Gender:</span> {{ $employee->gender ?? 'N/A' }}</div>
                            <div class="mb-2"><span class="text-sm text-muted">DOB:</span> {{ optional($employee->dob)->format('d/m/Y') ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header pb-0">
                            <h6 class="mb-0">Work</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2"><span class="text-sm text-muted">Department:</span> {{ $employee->department->department_name ?? 'N/A' }}</div>
                            <div class="mb-2"><span class="text-sm text-muted">Designation:</span> {{ $employee->designation->designation_name ?? 'N/A' }}</div>
                            <div class="mb-2"><span class="text-sm text-muted">Location:</span> {{ $employee->location->name ?? 'N/A' }}</div>
                            <div class="mb-2"><span class="text-sm text-muted">Joining Date:</span> {{ optional($employee->doj)->format('d/m/Y') ?? 'N/A' }}</div>
                            <div class="mb-2"><span class="text-sm text-muted">Status:</span>
                                <span class="badge badge-sm {{ $employee->dol ? 'bg-gradient-danger' : 'bg-gradient-success' }}">
                                    {{ $employee->dol ? 'Inactive' : 'Active' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</main>
