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
                            <div class="d-inline-flex align-items-center ms-3">
                                <span class="me-2 text-sm">DED fields:</span>
                                <button type="button" class="btn btn-sm btn-outline-dark me-1" wire:click="removeDeductionColumn">-</button>
                                <button type="button" class="btn btn-sm btn-outline-dark" wire:click="addDeductionColumn">+</button>
                            </div>
                        @endif
                        @if(session()->has('message'))
                            <div class="alert alert-success mt-2">{{ session('message') }}</div>
                        @endif
                    </div>
                </div>
                <table class="table align-items-center mb-0 mt-4">
                    <thead>
                        <tr>
                            <th>EMPNO &amp; Employee Name</th>
                            <th>CL</th>
                            <th>EL</th>
                            <th>SL</th>
                            <th>ESI_LEAVE</th>
                            <th>HOLIDAY</th>
                            <th>TOT_DYS</th>
                            @for ($i = 1; $i <= $deductionCount; $i++)
                                <th>DED_{{ $i }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <span>{{ $employee->employee_code }} - {{ $employee->employee_name }}</span>
                                    <small class="text-muted">
                                        {{ $employee->department->department_name ?? 'N/A' }} /
                                        {{ $employee->designation->designation_name ?? 'N/A' }}
                                    </small>
                                </div>
                            </td>
                            <td><input type="number" step="0.5" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.cl" @if(!$isEditMode) readonly @endif></td>
                            <td><input type="number" step="0.5" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.el" @if(!$isEditMode) readonly @endif></td>
                            <td><input type="number" step="0.5" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.sl" @if(!$isEditMode) readonly @endif></td>
                            <td><input type="number" step="0.5" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.esi_leave" @if(!$isEditMode) readonly @endif></td>
                            <td><input type="number" step="0.5" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.holiday" @if(!$isEditMode) readonly @endif></td>
                            <td><input type="number" step="0.5" class="form-control form-control-sm" wire:model="attendanceData.{{ $employee->id }}.tot_dys" @if(!$isEditMode) readonly @endif></td>
                            @for ($i = 0; $i < $deductionCount; $i++)
                                <td>
                                    <input type="number" step="0.01" class="form-control form-control-sm"
                                        wire:model="attendanceData.{{ $employee->id }}.deductions.{{ $i }}"
                                        @if(!$isEditMode) readonly @endif>
                                </td>
                            @endfor
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
