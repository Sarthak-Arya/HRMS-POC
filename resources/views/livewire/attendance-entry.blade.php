<main class="main-content">
<div>
    <div class="card">
        <div class="card-header pb-0">
                <h6>Monthly Attendance Entry</h6>
        </div>
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive p-0">
                <div class="row px-4 mt-3">
                    <div class="col-md-2">
                        <label>Location</label>
                        <select wire:model="selectedLocation" class="form-control">
                            <option value="">All Locations</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Month</label>
                        <select wire:model="month" class="form-control">
                            @for($m=1; $m<=12; $m++)
                                <option value="{{ $m }}">{{ DateTime::createFromFormat('!m', $m)->format('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Year</label>
                        <input type="number" wire:model="year" class="form-control" min="2000" max="2100">
                    </div>
                    <div class="col-md-3">
                        <label>Department</label>
                        <select wire:model="selectedDepartment" class="form-control">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Designation</label>
                        <select wire:model="selectedDesignation" class="form-control">
                            <option value="">All Designations</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}">{{ $designation->designation_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row px-4 mt-3">
                    <div class="col-md-6">
                        <form wire:submit.prevent="importExcel" enctype="multipart/form-data">
                            <div class="input-group mb-3">
                                <input type="file" class="form-control" wire:model="excel_file" accept=".xlsx,.xls">
                                <button class="btn btn-success" type="submit">Import Monthly Attendance (Excel)</button>
                                <button type="button" class="btn btn-info ms-2" wire:click.prevent="downloadTemplate">Download Excel Template</button>
                            </div>
                            @error('excel_file') <span class="text-danger">{{ $message }}</span> @enderror
                            @if (session()->has('import_message'))
                                <div class="alert alert-success mt-2">{{ session('import_message') }}</div>
                            @endif
                        </form>
                    </div>
                </div>
                <div class="row px-4 mt-3">
                    <div class="col-md-12">
                        @if(!$isEditMode)
                            <button type="button" class="btn btn-primary" wire:click="toggleEditMode">Edit Attendance</button>
                        @endif
                        @if($isEditMode)
                            <button type="button" class="btn btn-success" wire:click="save">Save Attendance</button>
                            <button type="button" class="btn btn-secondary" wire:click="toggleEditMode">Cancel</button>
                        @endif
                        @if(session()->has('message'))
                            <div class="alert alert-success mt-2">{{ session('message') }}</div>
                        @endif
                    </div>
                </div>
                <table class="table align-items-center mb-0 mt-4">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Total Working Days</th>
                            <th>Present</th>
                            <th>Half Day</th>
                            <th>Late</th>
                            <th>Total Hours</th>
                            <th>Overtime</th>
                            <th>Total Leave</th>
                            @foreach($leaveTypes as $leaveType)
                                <th>{{ $leaveType->leave_code }}</th>
                            @endforeach
                            <th>Holidays</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                        <tr>
                            <td>{{ $employee->first_name }} {{ $employee->middle_name ?? '' }} {{ $employee->last_name }}</td>
                            <td>{{ $employee->department->department_name ?? 'N/A' }}</td>
                            <td>{{ $employee->designation->designation_name ?? 'N/A' }}</td>
                            <td>
                                <input type="number" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.total_working_days" @if(!$isEditMode) readonly @endif>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.days_present" @if(!$isEditMode) readonly @endif>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.days_half_day" @if(!$isEditMode) readonly @endif>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.days_late" @if(!$isEditMode) readonly @endif>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.total_hours_worked" @if(!$isEditMode) readonly @endif>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.overtime_hours" @if(!$isEditMode) readonly @endif>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.total_leave_days" @if(!$isEditMode) readonly @endif>
                            </td>
                            @foreach($leaveTypes as $leaveType)
                                <td>
                                    <input type="number" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.leave_taken.{{ strtolower($leaveType->leave_code) }}" @if(!$isEditMode) readonly @endif>
                                </td>
                            @endforeach
                            <td>
                                <input type="number" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.holiday_days" @if(!$isEditMode) readonly @endif>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.remarks" @if(!$isEditMode) readonly @endif>
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
            </div>
        </div>
    </div>
</div>
</main>