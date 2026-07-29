<main class="main-content ui-page">
    <div class="container-fluid py-4">
        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Employees</h1>
                <p class="ui-page-subtitle">
                    {{ $companyName }}<span class="ui-page-subtitle-sep">|</span>
                    Search, filter, and manage your workforce records.
                </p>
            </div>
            @canany(['employees.create', 'employees.edit'])
                <div class="ui-page-actions">
                    <a href="{{ route('add-employee-details', ['company_id' => $companyId]) }}" class="ui-btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">person_add</span>
                        Add Employee
                    </a>
                </div>
            @endcanany
        </section>

        <section class="ui-card">
            <div class="ui-card-toolbar">
                <div class="ui-search-field flex-grow-1" style="max-width: 22rem;">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text"
                        wire:model.debounce.300ms="search"
                        class="ui-search-input"
                        placeholder="Search by name or employee code...">
                </div>
            </div>

            <div class="ui-card-body border-bottom">
                <div class="ui-filter-grid">
                    <div>
                        <label class="form-label">Designation</label>
                        <select wire:model="selectedDesignation" class="form-control form-select">
                            <option value="">All Designations</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}">{{ $designation->designation_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Department</label>
                        <select wire:model="selectedDepartment" class="form-control form-select">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Location</label>
                        <select wire:model="selectedLocation" class="form-control form-select">
                            <option value="">All Locations</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select wire:model="selectedStatus" class="form-control form-select">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="ui-data-table-wrap">
                <table class="ui-data-table">
                    <thead>
                        <tr>
                            <th>Employee Code</th>
                            <th>Full Name</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Location</th>
                            <th>Joining Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr>
                                <td class="text-code">{{ $employee->employee_code }}</td>
                                <td class="text-name">{{ $employee->employee_name }}</td>
                                <td>{{ $employee->designation->designation_name ?? 'N/A' }}</td>
                                <td>{{ $employee->department->department_name ?? 'N/A' }}</td>
                                <td>{{ $employee->location->name ?? 'N/A' }}</td>
                                <td>{{ optional($employee->doj)->format('d/m/Y') ?? 'N/A' }}</td>
                                <td>
                                    <span class="ui-badge {{ $employee->dol ? 'ui-badge--danger' : 'ui-badge--success' }}">
                                        {{ $employee->dol ? 'Inactive' : 'Active' }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('employee-details', ['company_id' => $companyId, 'employee_id' => $employee->id]) }}"
                                        class="ui-link-btn"
                                        title="View employee">
                                        View
                                    </a>
                                    @canany(['employees.create', 'employees.edit'])
                                        <a href="{{ route('edit-employee-details', ['company_id' => $companyId, 'employee_id' => $employee->id]) }}"
                                            class="ui-link-btn ms-2"
                                            title="Edit employee">
                                            Edit
                                        </a>
                                    @endcanany
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No employees found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ui-pagination-wrap d-flex justify-content-center">
                {{ $employees->links() }}
            </div>
        </section>
    </div>
</main>
