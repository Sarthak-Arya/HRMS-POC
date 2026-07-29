{{-- Livewire v2 requires a single root element; without one, embedded actions bind to SettingsHub. --}}
<div class="{{ ($embeddedInSettings ?? false) ? 'ui-settings-embedded-compensation' : 'main-content ui-page' }}">
@if(!($embeddedInSettings ?? false))
    <div class="container-fluid py-4">
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Compensation</h1>
                <p class="ui-page-subtitle">{{ $companyName }}<span class="ui-page-subtitle-sep">|</span>Manage components, structures, assignments, and overrides</p>
            </div>
        </section>
@endif

        @php
            $compensationTabHelp = [
                'components' => [
                    'title' => 'Salary Components',
                    'text' => 'Individual pay heads such as Basic, HRA, PF, or benefits. Define the name, type (earning, deduction, benefit), and default calculation once—then reuse them in every salary structure and on payslips.',
                ],
                'structures' => [
                    'title' => 'Compensation Structures',
                    'text' => 'Salary templates that combine components into a full CTC breakdown (for example, Basic at 50% of CTC plus HRA at 40% of Basic). Structures are reusable blueprints, not tied to a specific employee until assigned.',
                ],
                'assignments' => [
                    'title' => 'Structure Assignments',
                    'text' => 'Decide which structure applies to whom—company-wide, by location, department, or individual employee. Payroll uses the most specific assignment when calculating pay.',
                ],
                'overrides' => [
                    'title' => 'Compensation Overrides',
                    'text' => 'Exceptions to a structure for a specific scope without editing the master template—for example, a higher conveyance allowance for one location or a one-off component change for an employee.',
                ],
            ];
            $activeTabHelp = $compensationTabHelp[$activeTab] ?? null;
        @endphp

        <nav class="ui-tab-bar">
            @foreach(['components' => 'Components', 'structures' => 'Structures', 'assignments' => 'Assignments', 'overrides' => 'Overrides'] as $tab => $label)
                <button type="button" class="ui-tab-btn {{ $activeTab === $tab ? 'active' : '' }}" wire:click="setTab('{{ $tab }}')">{{ $label }}</button>
            @endforeach
        </nav>

        @if($activeTabHelp)
            <div class="ui-compensation-help mb-4" role="note">
                <span class="material-symbols-outlined ui-compensation-help-icon" aria-hidden="true">info</span>
                <div>
                    <p class="ui-compensation-help-title mb-1">{{ $activeTabHelp['title'] }}</p>
                    <p class="ui-compensation-help-text mb-0">{{ $activeTabHelp['text'] }}</p>
                </div>
            </div>
        @endif

        {{-- Components Tab --}}
        @if($activeTab === 'components')
            <section class="ui-card">
                <div class="ui-card-header">
                    <div>
                        <h2 class="ui-card-title mb-0">Compensation Components</h2>
                        <p class="ui-card-desc mb-0">Master list of pay heads used in structures and payroll.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="ui-btn-secondary ui-btn-secondary--sm" wire:click="openComponentModal(null, true)">Add Adjustment Type</button>
                        <button class="ui-btn-primary ui-btn-primary--sm" wire:click="openComponentModal">Add Structure Component</button>
                    </div>
                </div>
                <div class="ui-card-body p-0">
                    <div class="ui-data-table-wrap">
                        <table class="ui-data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Usage</th>
                                    <th>Calculation</th>
                                    <th>Statutory</th>
                                    <th>PF Wage</th>
                                    <th>ESI Wage</th>
                                    <th>Taxable</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($components as $component)
                                    <tr>
                                        <td>{{ $component->component_name }}</td>
                                        <td>
                                            <span class="ui-badge ui-badge--{{ $component->component_type->value === 'EARNING' ? 'success' : ($component->component_type->value === 'DEDUCTION' ? 'danger' : 'info') }}">
                                                {{ $component->component_type->value }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($component->is_payroll_adjustment)
                                                <span class="ui-badge ui-badge--info">Payroll Adjustment</span>
                                            @else
                                                <span class="ui-badge ui-badge--neutral">Structure</span>
                                            @endif
                                        </td>
                                        <td>{{ $component->default_calculation_type->value }}</td>
                                        <td>
                                            @if($component->component_type->value === 'BENEFIT')
                                                {{ $component->benefit_plan ?: 'Benefit' }}
                                            @else
                                                {{ $component->statutory_component?->value ?? '—' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($component->component_type->value === 'EARNING' && !$component->is_payroll_adjustment)
                                                {{ $component->included_in_pf_wages ? 'Yes' : 'No' }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if($component->component_type->value === 'EARNING' && !$component->is_payroll_adjustment)
                                                {{ $component->included_in_esi_wages ? 'Yes' : 'No' }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $component->is_taxable ? 'Yes' : 'No' }}</td>
                                        <td>{{ $component->is_active ? 'Active' : 'Inactive' }}</td>
                                        <td class="text-end">
                                            <button class="ui-link-btn" wire:click="openComponentModal({{ $component->id }})">Edit</button>
                                            @if($component->is_active)
                                                <button class="ui-link-btn ui-link-btn--danger" wire:click="deactivateComponent({{ $component->id }})">Deactivate</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-center text-muted py-4">No components yet. Add salary structure components or payroll adjustment types.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        {{-- Structures Tab --}}
        @if($activeTab === 'structures')
            @if($this->canManage())
                <section class="ui-card mb-3">
                    <div class="ui-card-header">
                        <h2 class="ui-card-title mb-0">Bulk Upload Structures</h2>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="downloadStructureTemplate">
                                Download Template
                            </button>
                            <button type="button" class="ui-btn-primary ui-btn-primary--sm" wire:click="importStructuresFromExcel" @if($isImportingStructures) disabled @endif>
                                {{ $isImportingStructures ? 'Uploading...' : 'Upload Excel' }}
                            </button>
                        </div>
                    </div>
                    <div class="ui-card-body">
                        <div class="row align-items-end">
                            <div class="col-md-8">
                                <label class="form-label">Upload File</label>
                                <input type="file" class="form-control" wire:model="structureImportFile" accept=".xlsx,.xls,.csv">
                                @error('structureImportFile') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        @if($structureImportMessage !== '')
                            <div class="alert alert-success mt-3 mb-0">{{ $structureImportMessage }}</div>
                        @endif

                        @if($structureImportError !== '')
                            <div class="alert alert-warning mt-3 mb-0">
                                <strong>Import notes:</strong>
                                <pre class="mb-0 mt-2" style="white-space: pre-wrap;">{{ $structureImportError }}</pre>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            <div class="ui-card-grid">
                @forelse($structures as $structure)
                    <section class="ui-card h-100">
                        <div class="ui-card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <h2 class="ui-card-title h6 mb-0">{{ $structure->structure_name }}</h2>
                                @if($structure->is_default)
                                    <span class="ui-badge ui-badge--info">Default</span>
                                @endif
                            </div>
                            <p class="ui-card-desc">{{ $structure->description ?: 'No description' }}</p>
                            <div class="ui-meta mb-2">
                                {{ $structure->structure_components_count }} components
                                @if($structure->effective_from)
                                    · From {{ $structure->effective_from->format('d M Y') }}
                                @endif
                            </div>
                            <span class="ui-badge {{ $structure->is_active ? 'ui-badge--success' : 'ui-badge--neutral' }}">
                                {{ $structure->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <div class="d-flex gap-2 flex-wrap mt-3">
                                <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="openStructureModal({{ $structure->id }})">Edit</button>
                                <button type="button" class="ui-btn-secondary ui-btn-secondary--sm ui-link-btn--danger" wire:click="deleteStructure({{ $structure->id }})" onclick="return confirm('Delete this structure?')">Delete</button>
                            </div>
                        </div>
                    </section>
                @empty
                    <div class="text-center py-5">
                        <p class="text-muted mb-0">No compensation structures yet.</p>
                    </div>
                @endforelse
            </div>
            @if($this->canManage())
                <div class="text-end">
                    <button type="button" class="ui-btn-primary" wire:click="openStructureModal">Add Structure</button>
                </div>
            @endif
        @endif

        {{-- Assignments Tab --}}
        @if($activeTab === 'assignments')
            <div class="ui-two-col-layout">
                <section class="ui-card">
                    <div class="ui-card-header">
                        <div>
                            <h2 class="ui-card-title mb-0">Assign Structure</h2>
                            <p class="ui-card-desc mb-0">Link a structure to company, location, department, or employee.</p>
                        </div>
                    </div>
                    <div class="ui-card-body ui-form-body">
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
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Locations</label>
                                    <span class="ui-badge ui-badge--neutral">{{ count($assignmentScopeIds) }} selected</span>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="selectAllAssignmentScopes({{ $locations->pluck('id')->values()->toJson() }})">Select all</button>
                                    <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="clearAssignmentScopes">Clear</button>
                                </div>
                                <div class="ui-checklist-scroll">
                                    @foreach($locations as $location)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" wire:model="assignmentScopeIds" value="{{ $location->id }}" id="assign-loc-{{ $location->id }}">
                                            <label class="form-check-label text-sm" for="assign-loc-{{ $location->id }}">{{ $location->location_name }}</label>
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
                                <div class="d-flex gap-2 mb-2">
                                    <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="selectAllAssignmentScopes({{ $departments->pluck('id')->values()->toJson() }})">Select all</button>
                                    <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="clearAssignmentScopes">Clear</button>
                                </div>
                                <div class="ui-checklist-scroll">
                                    @foreach($departments as $department)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" wire:model="assignmentScopeIds" value="{{ $department->id }}" id="assign-dept-{{ $department->id }}">
                                            <label class="form-check-label text-sm" for="assign-dept-{{ $department->id }}">{{ $department->department_name }}</label>
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
                                <div class="d-flex gap-2 mb-2">
                                    <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="selectAllAssignmentScopes({{ $employees->pluck('id')->values()->toJson() }})">Select all</button>
                                    <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="clearAssignmentScopes">Clear</button>
                                </div>
                                <div class="ui-checklist-scroll">
                                    @foreach($employees as $employee)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" wire:model="assignmentScopeIds" value="{{ $employee->id }}" id="assign-emp-{{ $employee->id }}">
                                            <label class="form-check-label text-sm" for="assign-emp-{{ $employee->id }}">{{ $employee->employee_name }} ({{ $employee->employee_code }})</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @error('assignment_scope_ids') <span class="text-danger text-sm">{{ $message }}</span> @enderror
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
                            <div class="ui-info-banner text-sm">
                                <strong>Inheritance chain:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($inheritanceChain as $level => $item)
                                        <li>{{ ucfirst(str_replace('_', ' ', $level)) }}: {{ $item['structure_name'] ?? '(none)' }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @error('assignment_bulk') <span class="text-danger text-sm d-block mb-2">{{ $message }}</span> @enderror
                        <button type="button" class="ui-btn-primary" wire:click="saveAssignment">Save Assignment</button>
                    </div>
                </section>

                <div>
                    @if($assignmentStructureId !== '')
                        <section class="ui-card mb-4">
                            <div class="ui-card-header">
                                <h2 class="ui-card-title mb-0">Structure Components</h2>
                                @if(!$assignmentStructureEditing)
                                    <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="startAssignmentStructureEdit">Edit Structure</button>
                                @endif
                            </div>
                            <div class="ui-card-body ui-form-body">
                                @if($assignmentStructureEditing)
                                    @include('livewire.partials.structure-component-editor', [
                                        'rows' => $assignmentStructureRows,
                                        'rowsProperty' => 'assignmentStructureRows',
                                        'wireKeyPrefix' => 'assignment-structure-row',
                                        'addRowMethod' => 'addAssignmentStructureRow',
                                        'removeRowMethod' => 'removeAssignmentStructureRow',
                                        'previewCtcProperty' => 'assignmentPreviewCtc',
                                        'errorField' => 'assignment_components',
                                        'components' => $structureComponents,
                                        'preview' => $assignmentStructurePreview,
                                        'previewSummary' => $assignmentStructurePreviewSummary,
                                    ])
                                    <div class="d-flex gap-2 mt-3">
                                        <button type="button" class="ui-btn-secondary ui-btn-secondary--sm" wire:click="cancelAssignmentStructureEdit">Cancel</button>
                                        <button type="button" class="ui-btn-primary ui-btn-primary--sm" wire:click="confirmSaveAssignmentStructure">Save Structure</button>
                                    </div>
                                    @error('assignment_components') <div class="text-danger text-sm mt-2">{{ $message }}</div> @enderror
                                @else
                                    @include('livewire.partials.structure-component-viewer', [
                                        'summary' => $assignmentStructurePreviewSummary,
                                        'previewCtcProperty' => 'assignmentPreviewCtc',
                                        'components' => $structureComponents,
                                        'rows' => $assignmentStructureRows,
                                    ])
                                @endif
                            </div>
                        </section>
                    @else
                        <section class="ui-card mb-4">
                            <div class="ui-card-body text-center py-5">
                                <p class="text-muted mb-0">Select a structure to view its components and monthly breakdown.</p>
                            </div>
                        </section>
                    @endif

                    <section class="ui-card">
                        <div class="ui-card-header">
                            <h2 class="ui-card-title mb-0">Current Assignments</h2>
                        </div>
                        <div class="ui-card-body p-0">
                            <div class="ui-data-table-wrap">
                                <table class="ui-data-table">
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
                                                <td>{{ ucfirst($assignment->scope_type->value) }}: {{ $assignmentScopeNames[$assignment->id] ?? '—' }}</td>
                                                <td>{{ $assignment->structure->structure_name ?? '—' }}</td>
                                                <td>{{ $assignment->effective_from->format('d M Y') }}</td>
                                                <td>{{ $assignment->effective_to?->format('d M Y') ?? 'Open' }}</td>
                                                <td class="text-end">
                                                    <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="deleteAssignment({{ $assignment->id }})">Remove</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-4">No assignments configured.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        @endif

        {{-- Overrides Tab --}}
        @if($activeTab === 'overrides')
            <div class="ui-two-col-layout">
                <section class="ui-card">
                    <div class="ui-card-header">
                        <div>
                            <h2 class="ui-card-title mb-0">Scope</h2>
                            <p class="ui-card-desc mb-0">Choose where this override applies before adding component changes.</p>
                        </div>
                    </div>
                    <div class="ui-card-body ui-form-body">
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
                        <button type="button" class="ui-btn-primary" wire:click="openOverrideModal">Add Override</button>
                    </div>
                </section>

                <div class="ui-split-grid">
                    <section class="ui-card">
                        <div class="ui-card-header">
                            <h2 class="ui-card-title mb-0">Inherited Preview</h2>
                        </div>
                        <div class="ui-card-body p-0">
                            @if($resolvedPreview)
                                <div class="ui-data-table-wrap">
                                    <table class="ui-data-table">
                                        <thead><tr><th>Component</th><th>Source</th><th class="text-end">Monthly</th></tr></thead>
                                        <tbody>
                                            @foreach($resolvedPreview as $line)
                                                <tr>
                                                    <td>{{ $line->componentName }}</td>
                                                    <td><span class="ui-badge ui-badge--neutral">{{ $line->source }}</span></td>
                                                    <td class="text-end">{{ number_format($line->monthlyAmount ?? 0, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted text-sm p-3 mb-0">Select a structure to preview resolved components.</p>
                            @endif
                        </div>
                    </section>

                    <section class="ui-card">
                        <div class="ui-card-header">
                            <h2 class="ui-card-title mb-0">Overrides at Scope</h2>
                        </div>
                        <div class="ui-card-body p-0">
                            <div class="ui-data-table-wrap">
                                <table class="ui-data-table">
                                    <thead><tr><th>Component</th><th>Type</th><th>Value</th><th></th></tr></thead>
                                    <tbody>
                                        @forelse($scopeOverrides as $override)
                                            <tr>
                                                <td>{{ $override->component->component_name ?? '—' }}</td>
                                                <td>{{ $override->override_type->value }}</td>
                                                <td>{{ $override->value ?? '—' }}</td>
                                                <td class="text-end">
                                                    <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="deleteOverride({{ $override->id }})">Remove</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-muted text-sm p-3">No overrides at this scope.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        @endif
@if(!($embeddedInSettings ?? false))
    </div>{{-- container-fluid --}}
@endif

    {{-- Component Modal --}}
    @if($showComponentModal)
        <div class="ui-modal-backdrop" tabindex="-1">
            <div class="ui-modal-dialog">
                <div class="ui-modal-content">
                    <div class="ui-modal-header">
                        <h2 class="ui-modal-title">{{ $editingComponentId ? 'Edit' : 'Add' }} {{ $componentIsPayrollAdjustment ? 'Adjustment Type' : 'Structure Component' }}</h2>
                        <button type="button" class="btn-close" wire:click="$set('showComponentModal', false)"></button>
                    </div>
                    <div class="ui-modal-body ui-form-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" wire:model.defer="componentName" class="form-control">
                            @error('component_name') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" wire:model.defer="componentIsPayrollAdjustment" id="componentIsPayrollAdjustment">
                            <label class="form-check-label" for="componentIsPayrollAdjustment">Use for payroll adjustments only</label>
                        </div>
                        @if(!$componentIsPayrollAdjustment && $componentType !== 'BENEFIT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Default Calculation</label>
                                <select wire:model.defer="defaultCalculationType" class="form-control">
                                    <option value="FIXED">Fixed</option>
                                    <option value="PERCENT_BASIC">% of Basic</option>
                                    <option value="PERCENT_CTC">% of CTC</option>
                                    <option value="FORMULA">Formula</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Default Value</label>
                                <input type="number" step="0.01" wire:model.defer="defaultValue" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
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
                        @elseif($componentIsPayrollAdjustment)
                        <p class="text-sm text-muted mb-3">Adjustment types are picked during payroll runs. Amount is entered per employee each month.</p>
                        @endif
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type</label>
                                <select wire:model.defer="componentType" class="form-control">
                                    <option value="EARNING">Addition (Earning)</option>
                                    <option value="DEDUCTION">Deduction</option>
                                    <option value="BENEFIT">Benefit</option>
                                </select>
                            </div>
                        </div>
                        @if($componentType === 'BENEFIT' && !$componentIsPayrollAdjustment)
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Benefit Plan</label>
                                <select wire:model.defer="benefitPlan" class="form-control">
                                    <option value="">Select a Benefit Plan</option>
                                    <option value="NPS">National Pension Scheme (NPS)</option>
                                    <option value="80C">Section 80C</option>
                                    <option value="80D">Section 80D</option>
                                    <option value="80DD">Section 80DD</option>
                                    <option value="80DDB">Section 80DDB</option>
                                    <option value="80GGC">Section 80GGC</option>
                                    <option value="OTHER">Other Benefit</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Associate this benefit with</label>
                                <select wire:model.defer="associateInvestment" class="form-control">
                                    <option value="">Select an Investment</option>
                                    <option value="NPS">National Pension Scheme (NPS)</option>
                                    <option value="80C">80C Investment</option>
                                    <option value="80D">80D Insurance</option>
                                    <option value="80DD">80DD Dependent Disability</option>
                                    <option value="80DDB">80DDB Specified Diseases</option>
                                    <option value="80GGC">80GGC Political Contribution</option>
                                    <option value="OTHER">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" wire:model.defer="includeEmployerContribution" id="includeEmployerContribution">
                            <label class="form-check-label" for="includeEmployerContribution">Include employer's contribution in employee's salary structure.</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" wire:model.defer="isSuperannuation" id="isSuperannuation">
                            <label class="form-check-label" for="isSuperannuation">Consider this a superannuation fund</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" wire:model.defer="proRataBasis" id="proRataBasis">
                            <label class="form-check-label" for="proRataBasis">Calculate on pro-rata basis</label>
                            <p class="text-sm text-muted mb-0">Pay will be adjusted based on employee working days.</p>
                        </div>
                        @if($benefitPlan === 'NPS')
                        <div class="alert alert-warning mt-3 mb-3" role="alert">
                            If the employer's total contribution towards EPF, EPS, NPS and superannuation fund exceeds Rs. 7.5 lakh in a financial year, the excess amount will be treated as a taxable perquisite for the employee.
                        </div>
                        @endif
                        <div class="alert alert-warning mt-2 mb-3" role="alert">
                            Note: Once you associate this benefit with an employee, you will only be able to edit the Name in Payslip. The change will be reflected in both new and existing employees.
                        </div>
                        @endif
                        @if($componentType === 'EARNING' && !$componentIsPayrollAdjustment)
                        <p class="text-sm text-muted mb-2">Select which earnings count toward PF/ESI wage bases. Contribution rates are configured in Company Settings.</p>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" wire:model.defer="includedInPfWages" id="includedInPfWages">
                            <label class="form-check-label" for="includedInPfWages">Include in PF wages</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" wire:model.defer="includedInEsiWages" id="includedInEsiWages">
                            <label class="form-check-label" for="includedInEsiWages">Include in ESI wages</label>
                        </div>
                        @endif
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" wire:model.defer="isTaxable" id="isTaxable">
                            <label class="form-check-label" for="isTaxable">Taxable</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model.defer="componentIsActive" id="componentIsActive">
                            <label class="form-check-label" for="componentIsActive">Active</label>
                        </div>
                    </div>
                    <div class="ui-modal-footer">
                        <button class="ui-btn-secondary" wire:click="$set('showComponentModal', false)">Cancel</button>
                        <button class="ui-btn-primary" wire:click="saveComponent">Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Structure Modal --}}
    @if($showStructureModal)
        <div class="ui-modal-backdrop" tabindex="-1">
            <div class="ui-modal-dialog ui-modal-dialog--wide">
                <div class="ui-modal-content">
                    <div class="ui-modal-header">
                        <h2 class="ui-modal-title">{{ $editingStructureId ? 'Edit' : 'Add' }} Structure</h2>
                        <button type="button" class="btn-close" wire:click="$set('showStructureModal', false)"></button>
                    </div>
                    <div class="ui-modal-body ui-form-body">
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
                        @include('livewire.partials.structure-component-editor', [
                            'rows' => $structureRows,
                            'rowsProperty' => 'structureRows',
                            'wireKeyPrefix' => 'structure-row',
                            'addRowMethod' => 'addStructureRow',
                            'removeRowMethod' => 'removeStructureRow',
                            'previewCtcProperty' => 'previewAnnualCtc',
                            'errorField' => 'components',
                            'components' => $structureComponents,
                            'preview' => $structurePreview,
                        ])
                    </div>
                    <div class="ui-modal-footer">
                        <button class="ui-btn-secondary" wire:click="$set('showStructureModal', false)">Cancel</button>
                        <button class="ui-btn-primary" wire:click="saveStructure">Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Assignment Structure Save Confirmation --}}
    @if($showAssignmentStructureSaveConfirm)
        <div class="ui-modal-backdrop" tabindex="-1">
            <div class="ui-modal-dialog">
                <div class="ui-modal-content">
                    <div class="ui-modal-header">
                        <h2 class="ui-modal-title">Save Structure Changes</h2>
                        <button type="button" class="btn-close" wire:click="$set('showAssignmentStructureSaveConfirm', false)"></button>
                    </div>
                    <div class="ui-modal-body ui-form-body">
                        <p class="mb-2">You are about to update this compensation structure.</p>
                        <div class="ui-alert-banner text-sm mb-0">
                            <strong>Company-wide impact:</strong> These changes will apply to every employee and assignment that uses this structure across the company, not only the scopes you are assigning now.
                        </div>
                    </div>
                    <div class="ui-modal-footer">
                        <button class="ui-btn-secondary" wire:click="$set('showAssignmentStructureSaveConfirm', false)">Cancel</button>
                        <button class="ui-btn-primary" wire:click="saveAssignmentStructure">Yes, Save Structure</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Override Modal --}}
    @if($showOverrideModal)
        <div class="ui-modal-backdrop" tabindex="-1">
            <div class="ui-modal-dialog">
                <div class="ui-modal-content">
                    <div class="ui-modal-header">
                        <h2 class="ui-modal-title">Add Override</h2>
                        <button type="button" class="btn-close" wire:click="$set('showOverrideModal', false)"></button>
                    </div>
                    <div class="ui-modal-body ui-form-body">
                        <div class="mb-3">
                            <label class="form-label">Component</label>
                            <select wire:model.defer="overrideComponentId" class="form-control">
                                <option value="">Select component</option>
                                @foreach($structureComponents as $component)
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
                    <div class="ui-modal-footer">
                        <button class="ui-btn-secondary" wire:click="$set('showOverrideModal', false)">Cancel</button>
                        <button class="ui-btn-primary" wire:click="saveOverride">Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>{{-- Livewire root --}}
