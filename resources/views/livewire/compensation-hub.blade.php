<main class="main-content">
    <div class="container-fluid py-4">
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0">Compensation</h5>
                <p class="text-sm text-muted mb-0">Manage components, structures, assignments, and overrides.</p>
            </div>
        </div>

        <ul class="nav nav-pills nav-fill mb-4">
            @foreach(['components' => 'Components', 'structures' => 'Structures', 'assignments' => 'Assignments', 'overrides' => 'Overrides'] as $tab => $label)
                <li class="nav-item">
                    <button type="button"
                        class="nav-link {{ $activeTab === $tab ? 'active bg-gradient-dark' : '' }}"
                        wire:click="setTab('{{ $tab }}')">
                        {{ $label }}
                    </button>
                </li>
            @endforeach
        </ul>

        {{-- Components Tab --}}
        @if($activeTab === 'components')
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Compensation Components</h6>
                    <button class="btn btn-sm bg-gradient-dark" wire:click="openComponentModal">Add Component</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Calculation</th>
                                    <th>Statutory</th>
                                    <th>Taxable</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($components as $component)
                                    <tr>
                                        <td>{{ $component->component_name }}</td>
                                        <td><span class="badge bg-gradient-{{ $component->component_type->value === 'EARNING' ? 'success' : 'danger' }}">{{ $component->component_type->value }}</span></td>
                                        <td>{{ $component->default_calculation_type->value }}</td>
                                        <td>{{ $component->statutory_component?->value ?? '—' }}</td>
                                        <td>{{ $component->is_taxable ? 'Yes' : 'No' }}</td>
                                        <td>{{ $component->is_active ? 'Active' : 'Inactive' }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-link text-dark" wire:click="openComponentModal({{ $component->id }})">Edit</button>
                                            @if($component->is_active)
                                                <button class="btn btn-link text-danger" wire:click="deactivateComponent({{ $component->id }})">Deactivate</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">No components yet. Add your first earning or deduction component.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Structures Tab --}}
        @if($activeTab === 'structures')
            <div class="row">
                @forelse($structures as $structure)
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <h6>{{ $structure->structure_name }}</h6>
                                    @if($structure->is_default)
                                        <span class="badge bg-gradient-info">Default</span>
                                    @endif
                                </div>
                                <p class="text-sm text-muted">{{ $structure->description ?: 'No description' }}</p>
                                <div class="text-xs text-muted mb-2">
                                    {{ $structure->structure_components_count }} components
                                    @if($structure->effective_from)
                                        · From {{ $structure->effective_from->format('d M Y') }}
                                    @endif
                                </div>
                                <span class="badge {{ $structure->is_active ? 'bg-gradient-success' : 'bg-secondary' }}">
                                    {{ $structure->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-outline-dark" wire:click="openStructureModal({{ $structure->id }})">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="deleteStructure({{ $structure->id }})" onclick="return confirm('Delete this structure?')">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">No compensation structures yet.</p>
                    </div>
                @endforelse
            </div>
            <div class="text-end">
                <button class="btn bg-gradient-dark" wire:click="openStructureModal">Add Structure</button>
            </div>
        @endif

        {{-- Assignments Tab --}}
        @if($activeTab === 'assignments')
            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0">Assign Structure</h6></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Scope</label>
                                <select wire:model="assignmentScopeType" class="form-control">
                                    <option value="company">Company</option>
                                    <option value="location">Location</option>
                                    <option value="department">Department</option>
                                    <option value="employee">Employee</option>
                                </select>
                            </div>
                            @if($assignmentScopeType === 'location')
                                <div class="mb-3">
                                    <label class="form-label">Location</label>
                                    <select wire:model="assignmentScopeId" class="form-control">
                                        <option value="">Select location</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif($assignmentScopeType === 'department')
                                <div class="mb-3">
                                    <label class="form-label">Department</label>
                                    <select wire:model="assignmentScopeId" class="form-control">
                                        <option value="">Select department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif($assignmentScopeType === 'employee')
                                <div class="mb-3">
                                    <label class="form-label">Employee</label>
                                    <select wire:model="assignmentScopeId" wire:change="loadInheritancePreview" class="form-control">
                                        <option value="">Select employee</option>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}">{{ $employee->employee_name }} ({{ $employee->employee_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">Structure</label>
                                <select wire:model="assignmentStructureId" class="form-control">
                                    <option value="">Select structure</option>
                                    @foreach($structures as $structure)
                                        <option value="{{ $structure->id }}">{{ $structure->structure_name }}</option>
                                    @endforeach
                                </select>
                                @error('assignment_structure_id') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Effective From</label>
                                    <input type="date" wire:model="assignmentEffectiveFrom" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Effective To</label>
                                    <input type="date" wire:model="assignmentEffectiveTo" class="form-control">
                                </div>
                            </div>
                            @if(!empty($inheritanceChain))
                                <div class="alert alert-light text-sm">
                                    <strong>Inheritance chain:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach($inheritanceChain as $level => $item)
                                            <li>{{ ucfirst(str_replace('_', ' ', $level)) }}: {{ $item['structure_name'] ?? '(none)' }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <button class="btn bg-gradient-dark" wire:click="saveAssignment">Save Assignment</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 mb-4">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0">Current Assignments</h6></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Scope</th>
                                            <th>Structure</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($assignments as $assignment)
                                            <tr>
                                                <td>{{ ucfirst($assignment->scope_type->value) }} {{ $assignment->scope_id ? '#'.$assignment->scope_id : '' }}</td>
                                                <td>{{ $assignment->structure->structure_name ?? '—' }}</td>
                                                <td>{{ $assignment->effective_from->format('d M Y') }}</td>
                                                <td>{{ $assignment->effective_to?->format('d M Y') ?? 'Open' }}</td>
                                                <td class="text-end">
                                                    <button class="btn btn-link text-danger text-sm" wire:click="deleteAssignment({{ $assignment->id }})">Remove</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-4">No assignments configured.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Overrides Tab --}}
        @if($activeTab === 'overrides')
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0">Scope</h6></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Scope Type</label>
                                <select wire:model="overrideScopeType" class="form-control">
                                    <option value="company">Company</option>
                                    <option value="location">Location</option>
                                    <option value="department">Department</option>
                                    <option value="employee">Employee</option>
                                </select>
                            </div>
                            @if($overrideScopeType === 'location')
                                <select wire:model="overrideScopeId" class="form-control mb-3">
                                    <option value="">Select location</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                                    @endforeach
                                </select>
                            @elseif($overrideScopeType === 'department')
                                <select wire:model="overrideScopeId" class="form-control mb-3">
                                    <option value="">Select department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                    @endforeach
                                </select>
                            @elseif($overrideScopeType === 'employee')
                                <select wire:model="overrideScopeId" class="form-control mb-3">
                                    <option value="">Select employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->employee_name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">Preview Structure</label>
                                <select wire:model="overrideStructureId" class="form-control">
                                    <option value="">Select structure for preview</option>
                                    @foreach($structures as $structure)
                                        <option value="{{ $structure->id }}">{{ $structure->structure_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Preview Annual CTC</label>
                                <input type="number" wire:model="overridePreviewCtc" class="form-control">
                            </div>
                            <button class="btn bg-gradient-dark" wire:click="openOverrideModal">Add Override</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 mb-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header"><h6 class="mb-0">Inherited Preview</h6></div>
                                <div class="card-body p-0">
                                    @if($resolvedPreview)
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>Component</th><th>Source</th><th class="text-end">Monthly</th></tr></thead>
                                            <tbody>
                                                @foreach($resolvedPreview as $line)
                                                    <tr>
                                                        <td>{{ $line->componentName }}</td>
                                                        <td><span class="badge bg-secondary">{{ $line->source }}</span></td>
                                                        <td class="text-end">{{ number_format($line->monthlyAmount ?? 0, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <p class="text-muted text-sm p-3 mb-0">Select a structure to preview resolved components.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header"><h6 class="mb-0">Overrides at Scope</h6></div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Component</th><th>Type</th><th>Value</th><th></th></tr></thead>
                                        <tbody>
                                            @forelse($scopeOverrides as $override)
                                                <tr>
                                                    <td>{{ $override->component->component_name ?? '—' }}</td>
                                                    <td>{{ $override->override_type->value }}</td>
                                                    <td>{{ $override->value ?? '—' }}</td>
                                                    <td class="text-end">
                                                        <button class="btn btn-link text-danger text-sm" wire:click="deleteOverride({{ $override->id }})">Remove</button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="text-muted text-sm p-3">No overrides at this scope.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Component Modal --}}
    @if($showComponentModal)
        <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $editingComponentId ? 'Edit' : 'Add' }} Component</h5>
                        <button type="button" class="btn-close" wire:click="$set('showComponentModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" wire:model.defer="componentName" class="form-control">
                            @error('component_name') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type</label>
                                <select wire:model.defer="componentType" class="form-control">
                                    <option value="EARNING">Earning</option>
                                    <option value="DEDUCTION">Deduction</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Default Calculation</label>
                                <select wire:model.defer="defaultCalculationType" class="form-control">
                                    <option value="FIXED">Fixed</option>
                                    <option value="PERCENT_BASIC">% of Basic</option>
                                    <option value="PERCENT_CTC">% of CTC</option>
                                    <option value="FORMULA">Formula</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Default Value</label>
                                <input type="number" step="0.01" wire:model.defer="defaultValue" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Statutory</label>
                                <select wire:model.defer="statutoryComponent" class="form-control">
                                    <option value="">None</option>
                                    <option value="PF">PF</option>
                                    <option value="ESIC">ESIC</option>
                                    <option value="PT">PT</option>
                                    <option value="LWF">LWF</option>
                                    <option value="TDS">TDS</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" wire:model.defer="isTaxable" id="isTaxable">
                            <label class="form-check-label" for="isTaxable">Taxable</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model.defer="componentIsActive" id="componentIsActive">
                            <label class="form-check-label" for="componentIsActive">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="$set('showComponentModal', false)">Cancel</button>
                        <button class="btn bg-gradient-dark" wire:click="saveComponent">Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Structure Modal --}}
    @if($showStructureModal)
        <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $editingStructureId ? 'Edit' : 'Add' }} Structure</h5>
                        <button type="button" class="btn-close" wire:click="$set('showStructureModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Structure Name</label>
                                        <input type="text" wire:model.defer="structureName" class="form-control">
                                        @error('structure_name') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Description</label>
                                        <input type="text" wire:model.defer="structureDescription" class="form-control">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Effective From</label>
                                        <input type="date" wire:model.defer="structureEffectiveFrom" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Effective To</label>
                                        <input type="date" wire:model.defer="structureEffectiveTo" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-3 d-flex align-items-end gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" wire:model.defer="structureIsActive" id="structureIsActive">
                                            <label class="form-check-label" for="structureIsActive">Active</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" wire:model.defer="structureIsDefault" id="structureIsDefault">
                                            <label class="form-check-label" for="structureIsDefault">Default</label>
                                        </div>
                                    </div>
                                </div>
                                <h6 class="mt-2">Components</h6>
                                @foreach($structureRows as $index => $row)
                                    <div class="row align-items-end mb-2" wire:key="structure-row-{{ $index }}">
                                        <div class="col-md-4">
                                            <select wire:model="structureRows.{{ $index }}.component_id" class="form-control form-control-sm">
                                                <option value="">Component</option>
                                                @foreach($components->where('is_active', true) as $component)
                                                    <option value="{{ $component->id }}">{{ $component->component_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select wire:model="structureRows.{{ $index }}.calculation_type" class="form-control form-control-sm">
                                                <option value="">Default</option>
                                                <option value="FIXED">Fixed</option>
                                                <option value="PERCENT_BASIC">% Basic</option>
                                                <option value="PERCENT_CTC">% CTC</option>
                                                <option value="FORMULA">Formula</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" step="0.01" wire:model="structureRows.{{ $index }}.value" class="form-control form-control-sm" placeholder="Value">
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" wire:model="structureRows.{{ $index }}.is_mandatory">
                                                <label class="form-check-label text-sm">Mandatory</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeStructureRow({{ $index }})">Remove</button>
                                        </div>
                                    </div>
                                @endforeach
                                <button type="button" class="btn btn-sm btn-outline-dark" wire:click="addStructureRow">Add Row</button>
                                @error('components') <div class="text-danger text-sm">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6>Preview</h6>
                                        <div class="mb-3">
                                            <label class="form-label text-sm">Annual CTC</label>
                                            <input type="number" wire:model="previewAnnualCtc" class="form-control form-control-sm">
                                        </div>
                                        @if($structurePreview && $structurePreview->isNotEmpty())
                                            <table class="table table-sm mb-0">
                                                @foreach($structurePreview as $line)
                                                    <tr>
                                                        <td>{{ $line['name'] }}</td>
                                                        <td class="text-end">{{ number_format($line['amount'], 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        @else
                                            <p class="text-muted text-sm mb-0">Add components to see preview.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="$set('showStructureModal', false)">Cancel</button>
                        <button class="btn bg-gradient-dark" wire:click="saveStructure">Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Override Modal --}}
    @if($showOverrideModal)
        <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Override</h5>
                        <button type="button" class="btn-close" wire:click="$set('showOverrideModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Component</label>
                            <select wire:model.defer="overrideComponentId" class="form-control">
                                <option value="">Select component</option>
                                @foreach($components as $component)
                                    <option value="{{ $component->id }}">{{ $component->component_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Override Type</label>
                            <select wire:model.defer="overrideType" class="form-control">
                                <option value="REPLACE">Replace</option>
                                <option value="ADD">Add</option>
                                <option value="REMOVE">Remove</option>
                            </select>
                        </div>
                        @if($overrideType !== 'REMOVE')
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Value</label>
                                    <input type="number" step="0.01" wire:model.defer="overrideValue" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Calculation</label>
                                    <select wire:model.defer="overrideCalculationType" class="form-control">
                                        <option value="FIXED">Fixed</option>
                                        <option value="PERCENT_BASIC">% Basic</option>
                                        <option value="PERCENT_CTC">% CTC</option>
                                        <option value="FORMULA">Formula</option>
                                    </select>
                                </div>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Effective From</label>
                            <input type="date" wire:model.defer="overrideEffectiveFrom" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="$set('showOverrideModal', false)">Cancel</button>
                        <button class="btn bg-gradient-dark" wire:click="saveOverride">Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</main>
