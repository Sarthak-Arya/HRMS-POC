<main class="main-content">
<div>
    <div class="card">
        <div class="card-header pb-0">
            <h6>Attendance Entry</h6>
        </div>
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive p-0">
                <form wire:submit.prevent="save">
                    <div class="row px-4">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="from_date" class="form-control-label">From Date</label>
                                <input type="date" class="form-control" wire:model="from_date" id="from_date">
                                @error('from_date') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="to_date" class="form-control-label">To Date</label>
                                <input type="date" class="form-control" wire:model="to_date" id="to_date">
                                @error('to_date') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-control-label">Total Days</label>
                                <input type="text" class="form-control" value="{{ $this->days }}" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row px-4 mt-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-control-label">Filter by Department</label>
                                <select wire:model.live="selectedDepartment" class="form-control">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-control-label">Filter by Designation</label>
                                <select wire:model.live="selectedDesignation" class="form-control">
                                    <option value="">All Designations</option>
                                    @foreach($designations as $designation)
                                        <option value="{{ $designation->id }}">{{ $designation->designation_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    @if(!$isEditMode)
                    <div class="row px-4 mt-3">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" wire:click="toggleEditMode">
                                Update Attendance
                            </button>
                        </div>
                    </div>
                    @endif

                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Employee</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Department</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Designation</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Days</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Casual Leave</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Earned Leave</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Maternity Leave</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Earnings</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Deductions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $employee)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $employee->employee_name }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $employee->department->department_name }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $employee->designation->designation_name }}</p>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" value="{{ $this->days }}" readonly>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" 
                                           wire:model="attendanceData.{{ $employee->id }}.casual_leave" 
                                           min="0" @if(!$isEditMode) disabled @endif>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" 
                                           wire:model="attendanceData.{{ $employee->id }}.earned_leave" 
                                           min="0" @if(!$isEditMode) disabled @endif>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" 
                                           wire:model="attendanceData.{{ $employee->id }}.maternity_leave" 
                                           min="0" @if(!$isEditMode) disabled @endif>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        @foreach($attendanceData[$employee->id]['earnings'] as $index => $earning)
                                        <div class="d-flex mb-2">
                                            <input type="text" class="form-control form-control-sm me-2" 
                                                   wire:model="attendanceData.{{ $employee->id }}.earnings.{{ $index }}.name" 
                                                   placeholder="Name" @if(!$isEditMode) disabled @endif>
                                            <input type="number" class="form-control form-control-sm me-2" 
                                                   wire:model="attendanceData.{{ $employee->id }}.earnings.{{ $index }}.amount" 
                                                   placeholder="Amount" min="0" @if(!$isEditMode) disabled @endif>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    wire:click="removeEarning({{ $employee->id }}, {{ $index }})"
                                                    @if(!$isEditMode) disabled @endif>
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        @endforeach
                                        <button type="button" class="btn btn-sm btn-success" 
                                                wire:click="addEarning({{ $employee->id }})"
                                                @if(!$isEditMode) disabled @endif>
                                            Add Earning
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        @foreach($attendanceData[$employee->id]['deductions'] as $index => $deduction)
                                        <div class="d-flex mb-2">
                                            <input type="text" class="form-control form-control-sm me-2" 
                                                   wire:model="attendanceData.{{ $employee->id }}.deductions.{{ $index }}.name" 
                                                   placeholder="Name" @if(!$isEditMode) disabled @endif>
                                            <input type="number" class="form-control form-control-sm me-2" 
                                                   wire:model="attendanceData.{{ $employee->id }}.deductions.{{ $index }}.amount" 
                                                   placeholder="Amount" min="0" @if(!$isEditMode) disabled @endif>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    wire:click="removeDeduction({{ $employee->id }}, {{ $index }})"
                                                    @if(!$isEditMode) disabled @endif>
                                                <i class="ni ni-fat-remove"></i>
                                            </button>
                                        </div>
                                        @endforeach
                                        <button type="button" class="btn btn-sm btn-success" 
                                                wire:click="addDeduction({{ $employee->id }})"
                                                @if(!$isEditMode) disabled @endif>
                                            Add Deduction
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="row px-4 mt-4">
                        <div class="col-md-12">
                            {{ $employees->links() }}
                        </div>
                    </div>

                    <div class="row px-4 mt-4">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary" @if(!$isEditMode) disabled @endif>
                                Save Attendance
                            </button>
                            @if($isEditMode)
                            <button type="button" class="btn btn-secondary" wire:click="toggleEditMode">
                                Cancel Edit
                            </button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
</div> 
</main>