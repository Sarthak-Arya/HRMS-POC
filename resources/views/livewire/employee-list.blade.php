<div class="card">
    <div class="card-header pb-0">
        <div class="row">
            <div class="col-md-6">
                <h6>Employees List</h6>
            </div>
            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Search by name/code...">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-control-label">Filter by Designation</label>
                            <select wire:model="selectedDesignation" class="form-control">
                                <option value="">All Designations</option>
                                @foreach($designations as $designation)
                                    <option value="{{ $designation->id }}">{{ $designation->designation_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-control-label">Filter by Department</label>
                            <select wire:model="selectedDepartment" class="form-control">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-control-label">Filter by Location</label>
                            <select wire:model="selectedLocation" class="form-control">
                                <option value="">All Locations</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-control-label">Filter by Status</label>
                            <select wire:model="selectedStatus" class="form-control">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body px-0 pt-0 pb-2">
        <div class="table-responsive p-0">
            @if($employees->count() > 0)
                <table class="table align-items-center mb-0">
                    <thead>
                            <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Employee Code</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Full Name</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Designation</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Department</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Location</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Joining Date</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $employee->employee_code }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $employee->employee_name }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $employee->designation->designation_name ?? 'N/A' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $employee->department->department_name ?? 'N/A' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $employee->location->name ?? 'N/A' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ optional($employee->doj)->format('d/m/Y') ?? 'N/A' }}</p>
                                </td>
                                <td>
                                    <span class="badge badge-sm {{ $employee->dol ? 'bg-gradient-danger' : 'bg-gradient-success' }}">
                                        {{ $employee->dol ? 'Inactive' : 'Active' }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <a href="{{ route('employee-details', ['company_id' => $companyId, 'employee_id' => $employee->id]) }}"
                                        class="text-secondary font-weight-bold text-xs" data-toggle="tooltip" data-original-title="View employee">
                                        <i class="fas fa-eye text-info me-2"></i>
                                    </a>
                                    {{-- <a href="{{ route('view-employee-details', ['employee_id' => $employee->id]) }}" class="text-secondary font-weight-bold text-xs" data-toggle="tooltip" data-original-title="View employee">
                                        <i class="fas fa-eye text-info me-2"></i>
                                    </a> --}}
                                    <a href="{{ route('edit-employee-details', ['company_id' => $companyId, 'employee_id' => $employee->id]) }}"
                                        class="text-secondary font-weight-bold text-xs" data-toggle="tooltip" data-original-title="Edit employee">
                                        <i class="fas fa-edit text-warning"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center p-4">
                    <p class="text-muted">No employees found</p>
                </div>
            @endif
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-center">
            {{ $employees->links() }}
        </div>
    </div>
</div>

<style>
    .pagination {
        margin-bottom: 0;
    }
    .page-link {
        position: relative;
        display: block;
        padding: 0.5rem 0.75rem;
        margin-left: -1px;
        line-height: 1.25;
        color: #344767;
        background-color: #fff;
        border: 1px solid #dee2e6;
    }
    .page-item.active .page-link {
        z-index: 3;
        color: #fff;
        background-color: #344767;
        border-color: #344767;
    }
    .page-item.disabled .page-link {
        color: #6c757d;
        pointer-events: none;
        background-color: #fff;
        border-color: #dee2e6;
    }
</style> 
