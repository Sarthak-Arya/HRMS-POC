<main class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0">Employee Compensation</h5>
                @if($employee)
                    <p class="text-sm text-muted mb-0">{{ $employee->employee_name }} ({{ $employee->employee_code }})</p>
                @endif
            </div>
            @if($employee)
                <a href="{{ route('employee-details', ['company_id' => $companyId, 'employee_id' => $employee->id]) }}"
                    class="btn btn-sm btn-outline-dark">
                    Back to Employee
                </a>
            @endif
        </div>

        @livewire('employee-compensation-panel', ['companyId' => $companyId, 'employeeId' => $employeeId], key('emp-comp-'.$employeeId))
    </div>
</main>
