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
                <div class="d-flex gap-2">
                    <a href="{{ route('employee-compensation', ['company_id' => $companyId, 'employee_id' => $employee->id]) }}"
                        class="btn btn-sm btn-outline-dark">
                        Manage Compensation
                    </a>
                    <a href="{{ route('edit-employee-details', ['company_id' => $companyId, 'employee_id' => $employee->id]) }}"
                        class="btn btn-sm bg-gradient-dark">
                        Edit
                    </a>
                </div>
            </div>

            @if($inviteFlash)
                <div class="alert alert-{{ $inviteFlashType === 'error' ? 'danger' : 'success' }}" role="status">
                    {{ $inviteFlash }}
                    @if($tempPassword)
                        <div class="mt-2"><strong>Temporary password:</strong> <code>{{ $tempPassword }}</code></div>
                    @endif
                </div>
            @endif

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
                            <div class="mb-2"><span class="text-sm text-muted">Phone:</span> {{ $employee->phone ?? 'N/A' }}</div>
                            <div class="mb-2"><span class="text-sm text-muted">Work email:</span> {{ $employee->work_email ?? 'N/A' }}</div>
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
                            <div class="mb-2"><span class="text-sm text-muted">Manager:</span> {{ $employee->manager->employee_name ?? 'N/A' }}</div>
                            <div class="mb-2"><span class="text-sm text-muted">Status:</span>
                                <span class="badge badge-sm {{ $employee->dol ? 'bg-gradient-danger' : 'bg-gradient-success' }}">
                                    {{ $employee->dol ? 'Inactive' : 'Active' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @canany(['ess.portal.invite', 'employees.edit'])
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <div class="card">
                            <div class="card-header pb-0">
                                <h6 class="mb-0">Reporting manager</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex gap-2 align-items-end">
                                    <div class="flex-grow-1">
                                        <label class="form-label">Manager</label>
                                        <select class="form-control" wire:model="managerId">
                                            <option value="">— None —</option>
                                            @foreach($managers as $manager)
                                                <option value="{{ $manager->id }}">
                                                    {{ $manager->employee_name }} ({{ $manager->employee_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('managerId') <div class="text-danger text-sm">{{ $message }}</div> @enderror
                                    </div>
                                    <button type="button" class="btn btn-sm bg-gradient-dark" wire:click="saveManager">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <div class="card">
                            <div class="card-header pb-0">
                                <h6 class="mb-0">Employee portal access</h6>
                            </div>
                            <div class="card-body">
                                @if($employee->user)
                                    <div class="mb-3 text-sm">
                                        Linked to <strong>{{ $employee->user->email }}</strong>
                                        ({{ $employee->user->roles->pluck('name')->join(', ') }})
                                    </div>
                                @else
                                    <div class="mb-3 text-sm text-muted">No portal login linked yet.</div>
                                @endif
                                <div class="mb-2">
                                    <label class="form-label">Invite email</label>
                                    <input type="email" class="form-control" wire:model.defer="inviteEmail">
                                    @error('inviteEmail') <div class="text-danger text-sm">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Portal role</label>
                                    <select class="form-control" wire:model.defer="inviteRole">
                                        <option value="employee">Employee</option>
                                        <option value="manager">People Manager</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-sm bg-gradient-dark" wire:click="inviteToPortal">
                                    {{ $employee->user ? 'Update portal access' : 'Invite to portal' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endcanany
        @endif
    </div>
</main>
