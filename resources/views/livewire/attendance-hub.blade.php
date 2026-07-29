<main class="main-content ui-page">
@include('livewire.partials.ui-data-grid-assets')
<div class="container-fluid py-4">
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <section class="ui-page-header">
        <div>
            <h1 class="ui-page-title">Attendance</h1>
            <p class="ui-page-subtitle">{{ $companyName }}<span class="ui-page-subtitle-sep">|</span>Policies, assignments, monthly summaries, and daily marking</p>
        </div>
    </section>

    <nav class="ui-tab-bar">
        @php
            $attendanceTabs = [
                'policies' => 'Policies',
                'assignments' => 'Assignments',
                'holidays' => 'Holidays',
                'leave_types' => 'Leave Types',
                'exception_rules' => 'Exception Rules',
                'monthly' => 'Monthly Entry',
            ];
            if ($showDailyMarkingTab ?? true) {
                $attendanceTabs['daily'] = 'Daily Marking';
            }
        @endphp
        @foreach($attendanceTabs as $tab => $label)
            <button type="button"
                class="ui-tab-btn {{ $activeTab === $tab ? 'active' : '' }}"
                wire:click="setTab('{{ $tab }}')">
                {{ $label }}
            </button>
        @endforeach
    </nav>
    {{-- Policies Tab --}}
    @if($activeTab === 'policies')
        <section class="ui-card">
            <div class="ui-card-header">
                <div>
                    <h2 class="ui-card-title mb-0">Attendance Policies</h2>
                    <p class="ui-card-desc mb-0">Configure capture methods, working days, and leave integration.</p>
                </div>
                @if($this->canManage())
                    <button type="button" class="ui-btn-primary ui-btn-primary--sm" wire:click="openPolicyModal">Add Policy</button>
                @endif
            </div>
            <div class="ui-card-body p-0">
                <div class="ui-data-table-wrap">
                    <table class="ui-data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Mode</th>
                                <th>Working Days</th>
                                <th>Weekly Off</th>
                                <th>Leave Types</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($policies as $policy)
                                <tr>
                                    <td class="text-name">{{ $policy->policy_name }}</td>
                                    <td>
                                        <span class="ui-badge ui-badge--info">
                                            {{ str_replace('_', ' ', ucfirst($policy->attendance_mode->value)) }}
                                        </span>
                                    </td>
                                    <td>{{ str_replace('_', ' ', $policy->working_days_basis->value) }}</td>
                                    <td>{{ str_replace('_', ' ', $policy->weekly_off_rule->value) }}</td>
                                    <td>{{ $policy->policyLeaveTypes->count() }}</td>
                                    <td>
                                        <span class="ui-badge {{ $policy->is_active ? 'ui-badge--success' : 'ui-badge--neutral' }}">
                                            {{ $policy->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if($this->canManage())
                                            <button type="button" class="ui-link-btn" wire:click="openPolicyModal({{ $policy->id }})">Edit</button>
                                            @if($policy->is_active)
                                                <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="deactivatePolicy({{ $policy->id }})" onclick="return confirm('Deactivate this policy?')">Deactivate</button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No policies yet. Create your first attendance policy.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif
    {{-- Assignments Tab --}}
    @if($activeTab === 'assignments')
        <div class="ui-two-col-layout">
            <section class="ui-card">
                <div class="ui-card-header">
                    <h2 class="ui-card-title mb-0">Assign Policy</h2>
                </div>
                <div class="ui-card-body ui-form-body">
                    <div class="mb-3">
                        <label class="form-label">Scope</label>
                        <select wire:model="assignmentScopeType" class="form-control" @if(!$this->canManage()) disabled @endif>
                            <option value="company">Company</option>
                            <option value="location">Location</option>
                            <option value="department">Department</option>
                            <option value="designation">Designation</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>
                    @if($assignmentScopeType === 'location')
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Locations</label>
                                <span class="ui-badge ui-badge--neutral">{{ count($assignmentScopeIds) }} selected</span>
                            </div>
                            <div class="d-flex gap-2 mb-2 flex-wrap">
                                <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="selectAllAssignmentScopes({{ $locations->pluck('id')->values()->toJson() }})">Select all</button>
                                <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="clearAssignmentScopes">Clear</button>
                            </div>
                            <div class="ui-checklist-scroll">
                                @foreach($locations as $location)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="assignmentScopeIds" value="{{ $location->id }}" id="att-loc-{{ $location->id }}">
                                        <label class="form-check-label text-sm" for="att-loc-{{ $location->id }}">{{ $location->location_name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif($assignmentScopeType === 'department')
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Departments</label>
                                <span class="ui-badge ui-badge--neutral">{{ count($assignmentScopeIds) }} selected</span>
                            </div>
                            <div class="d-flex gap-2 mb-2 flex-wrap">
                                <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="selectAllAssignmentScopes({{ $departments->pluck('id')->values()->toJson() }})">Select all</button>
                                <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="clearAssignmentScopes">Clear</button>
                            </div>
                            <div class="ui-checklist-scroll">
                                @foreach($departments as $department)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="assignmentScopeIds" value="{{ $department->id }}" id="att-dept-{{ $department->id }}">
                                        <label class="form-check-label text-sm" for="att-dept-{{ $department->id }}">{{ $department->department_name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif($assignmentScopeType === 'designation')
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Designations</label>
                                <span class="ui-badge ui-badge--neutral">{{ count($assignmentScopeIds) }} selected</span>
                            </div>
                            <div class="d-flex gap-2 mb-2 flex-wrap">
                                <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="selectAllAssignmentScopes({{ $designations->pluck('id')->values()->toJson() }})">Select all</button>
                                <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="clearAssignmentScopes">Clear</button>
                            </div>
                            <div class="ui-checklist-scroll">
                                @foreach($designations as $designation)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="assignmentScopeIds" value="{{ $designation->id }}" id="att-des-{{ $designation->id }}">
                                        <label class="form-check-label text-sm" for="att-des-{{ $designation->id }}">{{ $designation->designation_name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif($assignmentScopeType === 'employee')
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Employees</label>
                                <span class="ui-badge ui-badge--neutral">{{ count($assignmentScopeIds) }} selected</span>
                            </div>
                            <div class="ui-checklist-scroll">
                                @foreach($allEmployees as $employee)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="assignmentScopeIds" value="{{ $employee->id }}" id="att-emp-{{ $employee->id }}">
                                        <label class="form-check-label text-sm" for="att-emp-{{ $employee->id }}">{{ $employee->employee_name }} ({{ $employee->employee_code }})</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @error('assignment_scope_ids') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    <div class="mb-3">
                        <label class="form-label">Policy</label>
                        <select wire:model="assignmentPolicyId" class="form-control" @if(!$this->canManage()) disabled @endif>
                            <option value="">Select policy</option>
                            @foreach($policies->where('is_active', true) as $policy)
                                <option value="{{ $policy->id }}">{{ $policy->policy_name }} ({{ str_replace('_', ' ', $policy->attendance_mode->value) }})</option>
                            @endforeach
                        </select>
                        @error('assignment_policy_id') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Effective From</label>
                            <input type="date" wire:model="assignmentEffectiveFrom" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Effective To</label>
                            <input type="date" wire:model="assignmentEffectiveTo" class="form-control">
                        </div>
                    </div>
                    @if(!empty($inheritanceChain))
                        <div class="ui-info-banner text-sm mb-3">
                            <strong>Policy inheritance preview:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($inheritanceChain as $level => $item)
                                    <li>{{ ucfirst($level) }}: {{ $item['policy_name'] ?? '(none)' }} @if($item) <span class="text-muted">({{ str_replace('_', ' ', $item['mode']) }})</span> @endif</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if($this->canManage())
                        <button type="button" class="ui-btn-primary w-100" wire:click="saveAssignment">Save Assignment</button>
                    @endif
                    @error('assignment_bulk') <div class="text-danger text-sm mt-2">{{ $message }}</div> @enderror
                </div>
            </section>
            <section class="ui-card">
                <div class="ui-card-header">
                    <h2 class="ui-card-title mb-0">Active Assignments</h2>
                </div>
                <div class="ui-card-body p-0">
                    <div class="ui-data-table-wrap">
                        <table class="ui-data-table">
                            <thead>
                                <tr>
                                    <th>Scope</th>
                                    <th>Target</th>
                                    <th>Policy</th>
                                    <th>Mode</th>
                                    <th>Effective</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignments as $assignment)
                                    <tr>
                                        <td>{{ ucfirst($assignment->scope_type->value) }}</td>
                                        <td>{{ $assignmentScopeNames[$assignment->id] ?? '—' }}</td>
                                        <td>{{ $assignment->policy?->policy_name }}</td>
                                        <td><span class="ui-badge ui-badge--info">{{ str_replace('_', ' ', $assignment->policy?->attendance_mode->value ?? '') }}</span></td>
                                        <td class="text-sm">{{ $assignment->effective_from->format('d M Y') }} — {{ $assignment->effective_to?->format('d M Y') ?? 'Open' }}</td>
                                        <td class="text-end">
                                            @if($this->canManage())
                                                <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="deleteAssignment({{ $assignment->id }})" onclick="return confirm('Remove assignment?')">Remove</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No assignments yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    @endif
    {{-- Holidays Tab --}}
    @if($activeTab === 'holidays')
        <div class="ui-two-col-layout">
            <section class="ui-card">
                <div class="ui-card-header">
                    <h2 class="ui-card-title mb-0">{{ $editingHolidayId ? 'Edit Holiday' : 'Add Holiday' }}</h2>
                </div>
                <div class="ui-card-body ui-form-body">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" wire:model="holidayDate" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" wire:model="holidayName" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Source</label>
                        <select wire:model="holidaySourceType" class="form-control">
                            <option value="public">Public Holiday</option>
                            <option value="company">Company Holiday</option>
                            <option value="regional">Regional Holiday</option>
                            <option value="emergency">Emergency Closure</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location (optional)</label>
                        <select wire:model="holidayLocationId" class="form-control">
                            <option value="">All Locations</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-3 mb-3 flex-wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model="holidayIsPaid" id="holPaid">
                            <label class="form-check-label" for="holPaid">Paid</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model="holidayIsActive" id="holActive">
                            <label class="form-check-label" for="holActive">Active</label>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @if($this->canManage())
                            <button type="button" class="ui-btn-primary" wire:click="saveHoliday">Save Holiday</button>
                            @if($editingHolidayId)
                                <button type="button" class="ui-btn-secondary" wire:click="resetHolidayForm">Cancel</button>
                            @endif
                        @endif
                    </div>
                </div>
            </section>
            <section class="ui-card">
                <div class="ui-card-toolbar">
                    <div>
                        <h2 class="ui-card-title mb-1">Holiday Calendar</h2>
                        <p class="ui-card-desc mb-0">Browse and manage holidays for the selected period.</p>
                    </div>
                    <div class="ui-filters">
                        <select wire:model.live="holidayFilterMonth" class="form-control form-control-sm ui-filter--month">
                            @for($m=1; $m<=12; $m++)
                                <option value="{{ $m }}">{{ DateTime::createFromFormat('!m', $m)->format('M') }}</option>
                            @endfor
                        </select>
                        <input type="number" wire:model.live="holidayFilterYear" class="form-control form-control-sm ui-filter--year" min="2000">
                    </div>
                </div>
                <div class="ui-card-body p-0">
                    <div class="ui-data-table-wrap">
                        <table class="ui-data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Source</th>
                                    <th>Location</th>
                                    <th>Paid</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($holidays as $holiday)
                                    <tr>
                                        <td>{{ $holiday->holiday_date->format('d M Y') }}</td>
                                        <td class="text-name">{{ $holiday->name }}</td>
                                        <td><span class="ui-badge ui-badge--info">{{ $holiday->source_type->label() }}</span></td>
                                        <td>{{ $holiday->location?->location_name ?? 'All' }}</td>
                                        <td>{{ $holiday->is_paid ? 'Yes' : 'No' }}</td>
                                        <td class="text-end">
                                            @if($this->canManage())
                                                <button type="button" class="ui-link-btn" wire:click="editHoliday({{ $holiday->id }})">Edit</button>
                                                <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="deleteHoliday({{ $holiday->id }})" onclick="return confirm('Delete this holiday?')">Delete</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No holidays for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    @endif
    {{-- Leave Types Tab --}}
    @if($activeTab === 'leave_types')
        <section class="ui-card">
            <div class="ui-card-header">
                <div>
                    <h2 class="ui-card-title mb-0">Leave Types</h2>
                    <p class="ui-card-desc mb-0">Configure leave codes, quotas, and paid/unpaid rules.</p>
                </div>
                @if($this->canManage())
                    <button type="button" class="ui-btn-primary ui-btn-primary--sm" wire:click="openLeaveTypeModal">Add Leave Type</button>
                @endif
            </div>
            <div class="ui-card-body p-0">
                <div class="ui-data-table-wrap">
                    <table class="ui-data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Paid</th>
                                <th>Annual Quota</th>
                                <th>Active</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adminLeaveTypes as $lt)
                                <tr>
                                    <td class="text-code">{{ $lt->code }}</td>
                                    <td class="text-name">{{ $lt->name }}</td>
                                    <td>{{ $lt->is_paid ? 'Yes' : 'No' }}</td>
                                    <td>{{ $lt->annual_quota }}</td>
                                    <td>
                                        <span class="ui-badge {{ $lt->is_active ? 'ui-badge--success' : 'ui-badge--neutral' }}">
                                            {{ $lt->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if($this->canManage())
                                            <button type="button" class="ui-link-btn" wire:click="openLeaveTypeModal({{ $lt->id }})">Edit</button>
                                            @if($lt->is_active)
                                                <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="deactivateLeaveType({{ $lt->id }})" onclick="return confirm('Deactivate this leave type?')">Deactivate</button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No leave types configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    {{-- Exception Rules Tab --}}
    @if($activeTab === 'exception_rules')
        <section class="ui-card">
            <div class="ui-card-header">
                <div>
                    <h2 class="ui-card-title mb-0">Leave Exception Rules</h2>
                    <p class="ui-card-desc mb-0">Cap LWP/CL usage and define on-exceed behavior.</p>
                </div>
                @if($this->canManage())
                    <button type="button" class="ui-btn-primary ui-btn-primary--sm" wire:click="openExceptionRuleModal">Add Rule</button>
                @endif
            </div>
            <div class="ui-card-body p-0">
                <div class="ui-data-table-wrap">
                    <table class="ui-data-table">
                        <thead>
                            <tr>
                                <th>Policy</th>
                                <th>Leave Type</th>
                                <th>Max/Month</th>
                                <th>Max/Year</th>
                                <th>On Exceed</th>
                                <th>Active</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($exceptionRules as $rule)
                                <tr>
                                    <td>{{ $rule->policy?->policy_name ?? 'All policies' }}</td>
                                    <td>{{ $rule->leaveType?->code ?? 'All types' }}</td>
                                    <td>{{ $rule->max_days_per_month ?? '—' }}</td>
                                    <td>{{ $rule->max_days_per_year ?? '—' }}</td>
                                    <td>{{ str_replace('_', ' ', $rule->on_exceed->value) }}</td>
                                    <td>
                                        <span class="ui-badge {{ $rule->is_active ? 'ui-badge--success' : 'ui-badge--neutral' }}">
                                            {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if($this->canManage())
                                            <button type="button" class="ui-link-btn" wire:click="openExceptionRuleModal({{ $rule->id }})">Edit</button>
                                            <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="deleteExceptionRule({{ $rule->id }})" onclick="return confirm('Delete this rule?')">Delete</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No exception rules configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif
    {{-- Monthly Entry Tab --}}
    @if($activeTab === 'monthly')
        <section class="ui-card">
            <div class="ui-card-header">
                <div>
                    <h2 class="ui-card-title mb-0">Monthly Summary Entry</h2>
                    <p class="ui-card-desc mb-0">For employees on monthly summary policies.</p>
                </div>
            </div>
            <div class="ui-card-body ui-form-body">
                <div class="ui-filter-grid">
                    <div>
                        <label class="form-label">Month</label>
                        <select wire:model.live="month" class="form-control">
                            @for($m=1; $m<=12; $m++)
                                <option value="{{ $m }}">{{ DateTime::createFromFormat('!m', $m)->format('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Year</label>
                        <input type="number" wire:model.live="year" class="form-control" min="2000">
                    </div>
                    <div>
                        <label class="form-label">Location</label>
                        <select wire:model.live="selectedLocation" class="form-control">
                            <option value="">All</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Department</label>
                        <select wire:model.live="selectedDepartment" class="form-control">
                            <option value="">All</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Designation</label>
                        <select wire:model.live="selectedDesignation" class="form-control">
                            <option value="">All</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}">{{ $designation->designation_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if($this->canManage())
                    <div class="ui-import-row">
                        <form wire:submit.prevent="importExcel" class="d-flex flex-wrap gap-2 flex-grow-1">
                            <input type="file" class="form-control" wire:model="excel_file" accept=".xlsx,.xls">
                            <button class="ui-btn-primary" type="submit">Import Excel</button>
                            <button type="button" class="ui-btn-secondary" wire:click.prevent="downloadTemplate">Template</button>
                        </form>
                        <div class="ui-toolbar-actions">
                            @if(!$monthlyEditMode)
                                <button type="button" class="ui-btn-primary" wire:click="toggleMonthlyEditMode">Edit Attendance</button>
                            @else
                                <button type="button" class="ui-btn-secondary" wire:click="toggleMonthlyEditMode">Cancel Editing</button>
                            @endif
                        </div>
                    </div>
                @endif

                @include('livewire.partials.monthly-attendance-grid', [
                    'monthlyMatrix' => $monthlyMatrix,
                ])
            </div>
        </section>
    @endif
    {{-- Daily Marking Tab --}}
    @if($activeTab === 'daily')
        <section class="ui-card">
            <div class="ui-card-header">
                <div>
                    <h2 class="ui-card-title mb-0">Daily Attendance Marking</h2>
                    <p class="ui-card-desc mb-0">Employee-day sheet for employees on daily marking policies.</p>
                </div>
            </div>
            <div class="ui-card-body ui-form-body">
                <div class="ui-filter-grid">
                    <div>
                        <label class="form-label">Month</label>
                        <select wire:model.live="dailyMonth" class="form-control">
                            @for($m=1; $m<=12; $m++)
                                <option value="{{ $m }}">{{ DateTime::createFromFormat('!m', $m)->format('M') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Year</label>
                        <input type="number" wire:model.live="dailyYear" class="form-control" min="2000">
                    </div>
                    <div>
                        <label class="form-label">From Day</label>
                        <input type="number" wire:model.live="dailyRangeStart" class="form-control" min="1" max="31">
                    </div>
                    <div>
                        <label class="form-label">To Day</label>
                        <input type="number" wire:model.live="dailyRangeEnd" class="form-control" min="1" max="31">
                    </div>
                    <div>
                        <label class="form-label">Bulk Status</label>
                        <select wire:model="bulkStatus" class="form-control">
                            @foreach($statusOptions as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex align-items-end">
                        @if($this->canManage())
                            <div class="ui-toolbar-actions w-100">
                                @if(!$dailyEditMode)
                                    <button type="button" class="ui-btn-primary" wire:click="toggleDailyEditMode">Edit Daily</button>
                                @else
                                    <button type="button" class="ui-btn-primary" wire:click="saveDaily">Save & Aggregate</button>
                                    <button type="button" class="ui-btn-secondary" wire:click="applyCalendarToDaily">Apply Calendar</button>
                                    <button type="button" class="ui-btn-secondary" wire:click="toggleDailyEditMode">Cancel</button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="ui-filters mb-3">
                    <select wire:model.live="dailyDepartment" class="form-control form-control-sm ui-filter--department">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="dailyDesignation" class="form-control form-control-sm ui-filter--designation">
                        <option value="">All Designations</option>
                        @foreach($designations as $designation)
                            <option value="{{ $designation->id }}">{{ $designation->designation_name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="dailyLocation" class="form-control form-control-sm ui-filter--department">
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ui-daily-grid-wrap">
                    <table class="ui-daily-grid">
                        <thead>
                            <tr>
                                <th class="ui-sticky-col">Employee</th>
                                @foreach($dailyDates as $date)
                                    @php $dayNum = (int) date('j', strtotime($date)); @endphp
                                    <th class="text-center">
                                        <div>{{ $dayNum }}</div>
                                        <small class="text-muted">{{ date('D', strtotime($date)) }}</small>
                                        @if($dailyEditMode)
                                            <button type="button" class="ui-link-btn d-block mx-auto" wire:click="applyBulkStatusToColumn('{{ $date }}')" title="Apply bulk status to column">Fill</button>
                                        @endif
                                    </th>
                                @endforeach
                                @if($dailyEditMode)
                                    <th>Row</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyEmployees as $employee)
                                @if(!isset($dailyMatrix[$employee->id]))
                                    @continue
                                @endif
                                <tr>
                                    <td class="ui-sticky-col">
                                        <span class="text-sm text-name">{{ $employee->employee_code }}</span><br>
                                        <small>{{ $employee->employee_name }}</small>
                                    </td>
                                    @foreach($dailyDates as $date)
                                        @php
                                            $cell = $dailyMatrix[$employee->id][$date] ?? [];
                                            $status = $cell['attendance_status'] ?? '';
                                            $isAuto = $cell['is_auto'] ?? false;
                                        @endphp
                                        <td class="p-1 {{ $isAuto ? 'ui-cell-auto' : '' }}">
                                            <select class="form-select form-select-sm"
                                                wire:model="dailyMatrix.{{ $employee->id }}.{{ $date }}.attendance_status"
                                                @if(!$dailyEditMode) disabled @endif>
                                                <option value="">—</option>
                                                @foreach($statusOptions as $opt)
                                                    <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                                @endforeach
                                            </select>
                                            @if(in_array($status, ['leave', 'half_day']))
                                                <select class="form-select form-select-sm mt-1"
                                                    wire:model="dailyMatrix.{{ $employee->id }}.{{ $date }}.leave_type_id"
                                                    @if(!$dailyEditMode) disabled @endif>
                                                    <option value="">Leave type</option>
                                                    @foreach($leaveTypes as $lt)
                                                        <option value="{{ $lt->id }}">{{ $lt->code }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                            @if($status === 'half_day' && $dailyEditMode)
                                                <div class="d-flex gap-1 mt-1 flex-wrap">
                                                    <select class="form-select form-select-sm" wire:model="dailyMatrix.{{ $employee->id }}.{{ $date }}.first_half_status">
                                                        <option value="present">1st: Present</option>
                                                        <option value="leave">1st: Leave</option>
                                                        <option value="absent">1st: Absent</option>
                                                    </select>
                                                    <select class="form-select form-select-sm" wire:model="dailyMatrix.{{ $employee->id }}.{{ $date }}.second_half_status">
                                                        <option value="present">2nd: Present</option>
                                                        <option value="leave">2nd: Leave</option>
                                                        <option value="absent">2nd: Absent</option>
                                                    </select>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                    @if($dailyEditMode)
                                        <td>
                                            <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="applyBulkStatusToEmployee({{ $employee->id }})">Fill row</button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($dailyEmployees->isEmpty())
                    <p class="text-muted text-center py-4">No employees on daily marking policies match the current filters.</p>
                @endif
                <p class="ui-grid-help mb-0">Scroll horizontally to view all days. Employee names stay pinned on the left.</p>
                <div class="ui-pagination-wrap">{{ $dailyEmployees->links() }}</div>
            </div>
        </section>
    @endif
    {{-- Policy Modal --}}
    @if($showPolicyModal)
        <div class="ui-modal-backdrop" tabindex="-1">
            <div class="ui-modal-dialog ui-modal-dialog--wide">
                <div class="ui-modal-content">
                    <div class="ui-modal-header">
                        <h2 class="ui-modal-title">{{ $editingPolicyId ? 'Edit Policy' : 'Create Policy' }}</h2>
                        <button type="button" class="btn-close" wire:click="$set('showPolicyModal', false)"></button>
                    </div>
                    <div class="ui-modal-body ui-form-body">                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Policy Name</label>
                                <input type="text" wire:model="policyName" class="form-control">
                                @error('policy_policy_name') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Attendance Capture Method</label>
                                <select wire:model="attendanceMode" class="form-control">
                                    <option value="monthly_summary">Manual Monthly Entry</option>
                                    <option value="daily_marking">Daily Present/Absent Marking</option>
                                    <option value="clock_in_out" disabled>Clock In/Out (coming soon)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Working Days Basis</label>
                                <select wire:model="workingDaysBasis" class="form-control">
                                    <option value="fixed_26">Fixed 26</option>
                                    <option value="calendar_days">Calendar Days</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            @if($workingDaysBasis === 'custom')
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Custom Working Days</label>
                                    <input type="number" step="0.5" wire:model="customWorkingDays" class="form-control">
                                </div>
                            @endif
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Weekly Off Pattern</label>
                                <select wire:model="weeklyOffRule" class="form-control">
                                    <option value="sunday">Sunday Only</option>
                                    <option value="sat_sun">Saturday & Sunday</option>
                                    <option value="alternate_saturday">Alternate Saturdays</option>
                                    <option value="custom">Custom Pattern</option>
                                </select>
                            </div>
                            @if($weeklyOffRule === 'alternate_saturday')
                                <div class="col-12 mb-3">
                                    <label class="form-label">Off Saturday Weeks (of month)</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach([1,2,3,4,5] as $week)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" wire:model="alternateSaturdayWeeks" value="{{ $week }}" id="alt-sat-{{ $week }}">
                                                <label class="form-check-label" for="alt-sat-{{ $week }}">{{ $week }}{{ ['st','nd','rd','th','th'][$week-1] ?? 'th' }} Sat</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if($weeklyOffRule === 'custom')
                                <div class="col-12 mb-3">
                                    <label class="form-label">Custom Weekly Off Days</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $i => $label)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" wire:model="customWeeklyOffDays" value="{{ $i }}" id="woff-{{ $i }}">
                                                <label class="form-check-label" for="woff-{{ $i }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <div class="col-12 mb-2"><h6 class="ui-section-label">Holiday & Weekly Off Rules</h6></div>
                            <div class="col-12 mb-3 d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="paidHolidays" id="paidHol">
                                    <label class="form-check-label" for="paidHol">Paid Holidays</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="paidWeeklyOffs" id="paidWo">
                                    <label class="form-check-label" for="paidWo">Paid Weekly Offs</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="requireAttendanceBeforeHoliday" id="reqBefore">
                                    <label class="form-check-label" for="reqBefore">Attendance Required Before Holiday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="requireAttendanceAfterHoliday" id="reqAfter">
                                    <label class="form-check-label" for="reqAfter">Attendance Required After Holiday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="compOffEnabled" id="compOff">
                                    <label class="form-check-label" for="compOff">Comp-off Eligibility</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="autoApplyWeekOffs" id="autoWo">
                                    <label class="form-check-label" for="autoWo">Auto-apply Week-offs in Daily Grid</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="autoApplyHolidays" id="autoHol">
                                    <label class="form-check-label" for="autoHol">Auto-apply Holidays in Daily Grid</label>
                                </div>
                            </div>
                            <div class="col-12 mb-2"><h6 class="ui-section-label">Holiday Sources</h6></div>
                            <div class="col-12 mb-3 d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="includePublicHolidays" id="incPub">
                                    <label class="form-check-label" for="incPub">Public Holidays</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="includeRegionalHolidays" id="incReg">
                                    <label class="form-check-label" for="incReg">Regional Holidays</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="includeEmergencyHolidays" id="incEmer">
                                    <label class="form-check-label" for="incEmer">Emergency Closures</label>
                                </div>
                            </div>
                            <div class="col-12 mb-2"><h6 class="ui-section-label">Leave Integration</h6></div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Leave Balance Priority</label>
                                <select wire:model="leaveBalancePriorityMode" class="form-control">
                                    <option value="policy_order">Policy Order (by priority)</option>
                                    <option value="strict_lwp_fallback">Strict LWP Fallback</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3 d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="autoAdjustApprovedLeave" id="autoLeave">
                                    <label class="form-check-label" for="autoLeave">Auto-adjust Approved Leave</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="sandwichLeaveEnabled" id="sandwich">
                                    <label class="form-check-label" for="sandwich">Sandwich Leave Policy</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Grace (minutes)</label>
                                <input type="number" wire:model="graceMinutes" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Min Half Day (min)</label>
                                <input type="number" wire:model="minHalfDayMinutes" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Min Full Day (min)</label>
                                <input type="number" wire:model="minFullDayMinutes" class="form-control">
                            </div>
                            <div class="col-12 mb-3 d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="allowOvertime" id="allowOt">
                                    <label class="form-check-label" for="allowOt">Allow Overtime</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="allowHalfDay" id="allowHd">
                                    <label class="form-check-label" for="allowHd">Allow Half Day</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="allowNegativeLeaveBalance" id="allowNeg">
                                    <label class="form-check-label" for="allowNeg">Allow Negative Leave</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="policyIsActive" id="policyActive">
                                    <label class="form-check-label" for="policyActive">Active</label>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <h6 class="ui-section-label mb-0">Leave Quotas</h6>
                            <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="addPolicyLeaveRow">Add Leave</button>
                        </div>                        @foreach($policyLeaveRows as $index => $row)
                            <div class="row align-items-end mb-2">
                                <div class="col-md-4">
                                    <select wire:model="policyLeaveRows.{{ $index }}.leave_type_id" class="form-control form-control-sm">
                                        <option value="">Leave type</option>
                                        @foreach($leaveTypes as $lt)
                                            <option value="{{ $lt->id }}">{{ $lt->code }} — {{ $lt->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" step="0.5" placeholder="Quota" wire:model="policyLeaveRows.{{ $index }}.annual_quota" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" step="0.5" placeholder="CF limit" wire:model="policyLeaveRows.{{ $index }}.carry_forward_limit" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" step="1" placeholder="Priority" wire:model="policyLeaveRows.{{ $index }}.deduction_priority" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="policyLeaveRows.{{ $index }}.encashable">
                                        <label class="form-check-label text-sm">Encashable</label>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="removePolicyLeaveRow({{ $index }})">Remove</button>
                                </div>                            </div>
                        @endforeach
                    </div>
                    <div class="ui-modal-footer">
                        <button type="button" class="ui-btn-secondary" wire:click="$set('showPolicyModal', false)">Cancel</button>
                        <button type="button" class="ui-btn-primary" wire:click="savePolicy">Save Policy</button>
                    </div>                </div>
            </div>
        </div>
    @endif

    @if($showLeaveTypeModal)
        <div class="ui-modal-backdrop" tabindex="-1">
            <div class="ui-modal-dialog">
                <div class="ui-modal-content">
                    <div class="ui-modal-header">
                        <h2 class="ui-modal-title">{{ $editingLeaveTypeId ? 'Edit Leave Type' : 'Add Leave Type' }}</h2>
                        <button type="button" class="btn-close" wire:click="$set('showLeaveTypeModal', false)"></button>
                    </div>
                    <div class="ui-modal-body ui-form-body">                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" wire:model="leaveTypeName" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" wire:model="leaveTypeCode" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Annual Quota</label>
                            <input type="number" step="0.5" wire:model="leaveTypeAnnualQuota" class="form-control">
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check"><input class="form-check-input" type="checkbox" wire:model="leaveTypeIsPaid" id="ltPaid"><label class="form-check-label" for="ltPaid">Paid</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" wire:model="leaveTypeCarryForward" id="ltCf"><label class="form-check-label" for="ltCf">Carry Forward</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" wire:model="leaveTypeEncashable" id="ltEnc"><label class="form-check-label" for="ltEnc">Encashable</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" wire:model="leaveTypeIsActive" id="ltActive"><label class="form-check-label" for="ltActive">Active</label></div>
                        </div>
                    </div>
                    <div class="ui-modal-footer">
                        <button type="button" class="ui-btn-secondary" wire:click="$set('showLeaveTypeModal', false)">Cancel</button>
                        <button type="button" class="ui-btn-primary" wire:click="saveLeaveType">Save</button>
                    </div>                </div>
            </div>
        </div>
    @endif

    @if($showExceptionRuleModal)
        <div class="ui-modal-backdrop" tabindex="-1">
            <div class="ui-modal-dialog ui-modal-dialog--wide">
                <div class="ui-modal-content">
                    <div class="ui-modal-header">
                        <h2 class="ui-modal-title">{{ $editingExceptionRuleId ? 'Edit Exception Rule' : 'Add Exception Rule' }}</h2>
                        <button type="button" class="btn-close" wire:click="$set('showExceptionRuleModal', false)"></button>
                    </div>
                    <div class="ui-modal-body ui-form-body">                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Policy (optional)</label>
                                <select wire:model="exceptionPolicyId" class="form-control">
                                    <option value="">All policies</option>
                                    @foreach($policies as $policy)
                                        <option value="{{ $policy->id }}">{{ $policy->policy_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Leave Type (optional)</label>
                                <select wire:model="exceptionLeaveTypeId" class="form-control">
                                    <option value="">All leave types</option>
                                    @foreach($leaveTypes as $lt)
                                        <option value="{{ $lt->id }}">{{ $lt->code }} — {{ $lt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Days / Month</label>
                                <input type="number" step="0.5" wire:model="exceptionMaxPerMonth" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Days / Year</label>
                                <input type="number" step="0.5" wire:model="exceptionMaxPerYear" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">On Exceed</label>
                                <select wire:model="exceptionOnExceed" class="form-control">
                                    <option value="block">Block</option>
                                    <option value="warn">Warn</option>
                                    <option value="route_to_lwp">Route to LWP</option>
                                    <option value="require_override">Require Override</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payroll Action</label>
                                <select wire:model="exceptionPayrollAction" class="form-control">
                                    <option value="none">None</option>
                                    <option value="lop_only">LOP Only</option>
                                    <option value="prorate_only">Prorate Only</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="exceptionIsActive" id="exActive">
                                    <label class="form-check-label" for="exActive">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ui-modal-footer">
                        <button type="button" class="ui-btn-secondary" wire:click="$set('showExceptionRuleModal', false)">Cancel</button>
                        <button type="button" class="ui-btn-primary" wire:click="saveExceptionRule">Save</button>
                    </div>                </div>
            </div>
        </div>
    @endif
</div>
</main>
