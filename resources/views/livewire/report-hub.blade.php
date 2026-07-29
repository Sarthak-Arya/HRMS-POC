<main class="main-content ui-page">
    <div class="container-fluid py-4">
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Reports</h1>
                <p class="ui-page-subtitle">
                    {{ $companyName }}<span class="ui-page-subtitle-sep">|</span>
                    Access payroll registers, attendance summaries, and employee exports with live preview.
                </p>
            </div>
            @if($activeTab === 'run' && $this->canManageTemplates)
                <div class="ui-page-actions">
                    <button type="button" class="ui-btn-secondary" wire:click="setActiveTab('templates')">
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">tune</span>
                        Manage Templates
                    </button>
                </div>
            @endif
        </section>

        <nav class="ui-tab-bar">
            <button type="button"
                class="ui-tab-btn {{ $activeTab === 'run' ? 'active' : '' }}"
                wire:click="setActiveTab('run')">
                Run Reports
            </button>
            @if($this->canManageTemplates)
                <button type="button"
                    class="ui-tab-btn {{ $activeTab === 'templates' ? 'active' : '' }}"
                    wire:click="setActiveTab('templates')">
                    Templates
                </button>
            @endif
        </nav>

        @if($activeTab === 'run')
            <div class="ui-filter-pills">
                @foreach(['all' => 'All Reports', 'payroll' => 'Payroll', 'attendance' => 'Attendance', 'employee' => 'Employee'] as $key => $label)
                    <button type="button"
                        class="ui-filter-pill {{ $filterCategory === $key ? 'active' : '' }}"
                        wire:click="setFilterCategory('{{ $key }}')">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if($featuredTemplates->isNotEmpty())
                <div class="ui-report-featured-grid">
                    @foreach($featuredTemplates as $template)
                        @php $meta = \App\Http\Livewire\ReportHub::templateMeta($template->data_source); @endphp
                        <article class="ui-report-featured-card {{ $selectedTemplateId === $template->id ? 'is-selected' : '' }}"
                            wire:click="selectTemplate({{ $template->id }})"
                            role="button" tabindex="0">
                            <div>
                                <div class="ui-report-featured-icon ui-report-featured-icon--{{ $meta['accent'] }}">
                                    <span class="material-symbols-outlined">{{ $meta['icon'] }}</span>
                                </div>
                                <h3 class="ui-report-featured-title">{{ $template->name }}</h3>
                                <p class="ui-report-featured-desc">{{ $meta['description'] }}</p>
                            </div>
                            <div class="ui-report-featured-footer">
                                @php $lastRun = $lastRuns->get($template->id); @endphp
                                <span class="ui-meta">
                                    @if($lastRun?->completed_at)
                                        Last generated {{ $lastRun->completed_at->format('M j, Y') }}
                                    @else
                                        Not generated yet
                                    @endif
                                </span>
                                <button type="button" class="ui-link-btn"
                                    wire:click.stop="quickPreview({{ $template->id }})"
                                    wire:loading.attr="disabled">
                                    Generate Now
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            <div class="ui-two-col-layout mb-4">
                <section class="ui-card" id="report-setup-panel">
                    <div class="ui-card-header">
                        <div>
                            <h2 class="ui-card-title mb-0">Report Setup</h2>
                            <p class="ui-card-desc mb-0">Choose a template and configure parameters before preview or export.</p>
                        </div>
                    </div>
                    <div class="ui-form-body">
                        <div class="mb-3">
                            <label class="form-label">Report Template</label>
                            <select class="form-control" wire:model="selectedTemplateId">
                                <option value="">Select a template</option>
                                @foreach($filteredTemplates as $template)
                                    <option value="{{ $template->id }}">
                                        {{ $template->name }}
                                        @if($template->company_id)
                                            (Custom)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if($selectedTemplateId)
                            @foreach($this->activeParameters as $parameter)
                                @if($parameter['type'] === 'month')
                                    <div class="mb-3">
                                        <label class="form-label">{{ $parameter['label'] }}</label>
                                        <select class="form-control" wire:model="month">
                                            @for($m = 1; $m <= 12; $m++)
                                                <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                            @endfor
                                        </select>
                                        @error('month') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                                    </div>
                                @elseif($parameter['type'] === 'year')
                                    <div class="mb-3">
                                        <label class="form-label">{{ $parameter['label'] }}</label>
                                        <input type="number" class="form-control" wire:model="year" min="2000" max="2100">
                                        @error('year') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                                    </div>
                                @elseif($parameter['type'] === 'department')
                                    <div class="mb-3">
                                        <label class="form-label">{{ $parameter['label'] }}</label>
                                        <select class="form-control" wire:model="departmentId">
                                            <option value="">All Departments</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                            @endforeach
                                        </select>
                                        @error('department_id') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                                    </div>
                                @elseif($parameter['type'] === 'payroll_run')
                                    <div class="mb-3">
                                        <label class="form-label">{{ $parameter['label'] }}</label>
                                        <select class="form-control" wire:model="payrollRunId">
                                            <option value="">Select payroll run</option>
                                            @foreach($payrollRuns as $run)
                                                <option value="{{ $run->id }}">
                                                    {{ date('F', mktime(0, 0, 0, $run->month, 1)) }} {{ $run->year }}
                                                    ({{ $run->status->value ?? $run->status }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('payroll_run_id') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                                    </div>
                                @elseif($parameter['type'] === 'group_by')
                                    <div class="mb-3">
                                        <label class="form-label">{{ $parameter['label'] }}</label>
                                        <select class="form-control" wire:model="groupBy">
                                            <option value="company">Company-wise (single file)</option>
                                            <option value="department">Department-wise (ZIP per department)</option>
                                            <option value="location">Location-wise (ZIP per location)</option>
                                        </select>
                                        @error('group_by') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                                    </div>
                                @elseif($parameter['type'] === 'export_format')
                                    <div class="mb-3">
                                        <label class="form-label">{{ $parameter['label'] }}</label>
                                        <select class="form-control" wire:model="exportFormat">
                                            <option value="pdf">PDF (salary register layout)</option>
                                            <option value="xlsx">Excel (.xlsx)</option>
                                        </select>
                                        @error('format') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                                    </div>
                                @endif
                            @endforeach
                        @endif

                        <div class="d-grid gap-2">
                            <button type="button" class="ui-btn-secondary w-100 justify-content-center"
                                wire:click="previewReport" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="previewReport,quickPreview">Preview Report</span>
                                <span wire:loading wire:target="previewReport,quickPreview">Loading...</span>
                            </button>
                            @can('reports.run')
                                <button type="button" class="ui-btn-primary w-100 justify-content-center"
                                    wire:click="downloadReport" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="downloadReport,quickDownload">
                                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">download</span>
                                        {{ $this->downloadButtonLabel }}
                                    </span>
                                    <span wire:loading wire:target="downloadReport,quickDownload">Generating...</span>
                                </button>
                            @endcan
                        </div>
                    </div>
                </section>

                <section class="ui-card">
                    <div class="ui-card-toolbar">
                        <div>
                            <h2 class="ui-card-title mb-1">Preview</h2>
                            <p class="ui-card-desc mb-0">Sample output for the selected template and parameters.</p>
                        </div>
                        @if($hasPreview)
                            <span class="ui-meta">Showing {{ count($previewRows) }} of {{ $previewTotal }} rows</span>
                        @endif
                    </div>
                    @if($hasPreview && count($previewHeadings) > 0)
                        <div class="ui-data-table-wrap">
                            <table class="ui-data-table">
                                <thead>
                                    <tr>
                                        @foreach($previewHeadings as $heading)
                                            <th>{{ $heading }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($previewRows as $row)
                                        <tr>
                                            @foreach($row as $cell)
                                                <td>{{ $cell ?? '—' }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($previewHeadings) }}" class="text-center text-muted py-4">
                                                No data found for the selected parameters.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="ui-preview-empty">
                            <span class="material-symbols-outlined">visibility</span>
                            <p>Select a template and click Preview Report to see sample output.</p>
                        </div>
                    @endif
                </section>
            </div>

            <section class="ui-card mb-4">
                <div class="ui-card-toolbar">
                    <div>
                        <h2 class="ui-card-title mb-1">All Available Reports</h2>
                        <p class="ui-card-desc mb-0">Browse every template available for this company.</p>
                    </div>
                    <span class="ui-meta">{{ $filteredTemplates->count() }} of {{ $templates->count() }} reports</span>
                </div>
                <div class="ui-report-list">
                    @forelse($filteredTemplates as $template)
                        @php $meta = \App\Http\Livewire\ReportHub::templateMeta($template->data_source); @endphp
                        <div class="ui-report-list-item {{ $selectedTemplateId === $template->id ? 'is-selected' : '' }}"
                            wire:click="selectTemplate({{ $template->id }})"
                            role="button" tabindex="0">
                            <div class="ui-report-list-main">
                                <div class="ui-report-list-icon">
                                    <span class="material-symbols-outlined">{{ $meta['icon'] }}</span>
                                </div>
                                <div>
                                    <h3 class="ui-report-list-title">{{ $template->name }}</h3>
                                    <p class="ui-report-list-desc">{{ $meta['description'] }}</p>
                                    @if($template->company_id)
                                        <span class="ui-badge ui-badge--info mt-1">Custom</span>
                                    @endif
                                </div>
                            </div>
                            <div class="ui-report-list-side">
                                @php $lastRun = $lastRuns->get($template->id); @endphp
                                <div class="ui-report-list-meta d-none d-md-block">
                                    <p class="ui-meta mb-1">Generated</p>
                                    <p class="mb-0">
                                        {{ $lastRun?->completed_at?->format('M j, Y') ?? '—' }}
                                    </p>
                                </div>
                                <div class="ui-report-list-actions">
                                    <button type="button" class="ui-icon-btn" title="Preview"
                                        wire:click.stop="quickPreview({{ $template->id }})"
                                        wire:loading.attr="disabled">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    @can('reports.run')
                                        <button type="button" class="ui-icon-btn" title="Download"
                                            wire:click.stop="quickDownload({{ $template->id }})"
                                            wire:loading.attr="disabled">
                                            <span class="material-symbols-outlined">download</span>
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="ui-preview-empty">
                            <p>No reports match the selected category.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <div class="ui-insight-grid">
                <section class="ui-insight-card">
                    <div class="ui-insight-card-body">
                        <h3 class="ui-insight-card-title">Report Generation Activity</h3>
                        <p class="ui-insight-card-desc">
                            {{ $completedRunsCount }} completed export{{ $completedRunsCount === 1 ? '' : 's' }} for this company.
                            @if($selectedTemplateId && $lastRuns->has($selectedTemplateId))
                                Latest run finished {{ $lastRuns->get($selectedTemplateId)->completed_at?->diffForHumans() }}.
                            @endif
                        </p>
                        <div class="ui-insight-badges">
                            <span class="ui-badge ui-badge--success">Ready to export</span>
                            <span class="ui-meta">{{ $templates->count() }} templates available</span>
                        </div>
                    </div>
                    <span class="ui-insight-watermark material-symbols-outlined">assessment</span>
                </section>
                <section class="ui-insight-card">
                    <div class="ui-insight-card-body">
                        <h3 class="ui-insight-card-title">Template Coverage</h3>
                        <p class="ui-insight-card-desc mb-3">
                            {{ $templates->whereNotNull('company_id')->count() }} custom template{{ $templates->whereNotNull('company_id')->count() === 1 ? '' : 's' }}
                            alongside {{ $templates->whereNull('company_id')->count() }} system default{{ $templates->whereNull('company_id')->count() === 1 ? '' : 's' }}.
                        </p>
                        <div class="ui-insight-progress">
                            @php
                                $customRatio = $templates->isEmpty()
                                    ? 0
                                    : round(($templates->whereNotNull('company_id')->count() / $templates->count()) * 100);
                            @endphp
                            <div class="ui-insight-progress-bar" style="width: {{ max($customRatio, 4) }}%;"></div>
                        </div>
                        @if($this->canManageTemplates)
                            <button type="button" class="ui-link-btn mt-3" wire:click="setActiveTab('templates')">
                                Manage Templates
                                <span class="material-symbols-outlined" style="font-size: 1rem;">arrow_forward</span>
                            </button>
                        @endif
                    </div>
                    <span class="ui-insight-watermark material-symbols-outlined">cloud_done</span>
                </section>
            </div>
        @else
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="ui-meta mb-0">System templates are read-only. Create your own custom reports or start from a system template.</p>
                <button type="button" class="ui-btn-primary" wire:click="openCreateModal">
                    <span class="material-symbols-outlined" style="font-size: 1.125rem;">add</span>
                    Create Custom Report
                </button>
            </div>

            <div class="ui-split-grid">
                <section class="ui-card">
                    <div class="ui-card-header">
                        <div>
                            <h2 class="ui-card-title mb-0">System Templates</h2>
                            <p class="ui-card-desc mb-0">Read-only defaults. Use as a starting point for a company-owned copy.</p>
                        </div>
                    </div>
                    <div class="ui-data-table-wrap">
                        <table class="ui-data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Source</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($systemTemplates as $template)
                                    <tr>
                                        <td class="text-name">{{ $template->name }}</td>
                                        <td><span class="ui-badge ui-badge--neutral">{{ $template->data_source }}</span></td>
                                        <td class="text-end">
                                            <button type="button" class="ui-btn-secondary ui-btn-secondary--sm"
                                                wire:click="cloneTemplate({{ $template->id }})"
                                                wire:loading.attr="disabled">
                                                Start from this
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No system templates available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="ui-card">
                    <div class="ui-card-header">
                        <div>
                            <h2 class="ui-card-title mb-0">Company Templates</h2>
                            <p class="ui-card-desc mb-0">Edit or archive your company's custom report templates.</p>
                        </div>
                    </div>
                    <div class="ui-data-table-wrap">
                        <table class="ui-data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Source</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($companyTemplates as $template)
                                    <tr>
                                        <td class="text-name">{{ $template->name }}</td>
                                        <td><span class="ui-badge ui-badge--info">{{ $template->data_source }}</span></td>
                                        <td>
                                            <span class="ui-badge {{ $template->is_active ? 'ui-badge--success' : 'ui-badge--neutral' }}">
                                                {{ $template->is_active ? 'Active' : 'Archived' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($template->is_active)
                                                <button type="button" class="ui-link-btn me-2"
                                                    wire:click="openEditModal({{ $template->id }})">
                                                    Edit
                                                </button>
                                                <button type="button" class="ui-link-btn ui-link-btn--danger"
                                                    wire:click="archiveTemplate({{ $template->id }})"
                                                    onclick="return confirm('Archive this template?')">
                                                    Archive
                                                </button>
                                            @else
                                                <span class="ui-meta">Archived</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No company templates yet. Create a custom report or start from a system template.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        @endif

        @if($showEditModal)
            @php $builderMeta = $this->builderMetadata; @endphp
            <div class="ui-modal-backdrop" tabindex="-1">
                <div class="ui-modal-dialog ui-modal-dialog--wide">
                    <div class="ui-modal-content">
                        <div class="ui-modal-header">
                            <h2 class="ui-modal-title">
                                {{ $isCreating ? 'Create Custom Report' : 'Edit Company Template' }}
                            </h2>
                            <button type="button" class="btn-close" wire:click="closeEditModal"></button>
                        </div>
                        <div class="ui-modal-body ui-form-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="mb-3">
                                <label class="form-label">Template Name</label>
                                <input type="text" class="form-control" wire:model.defer="editName">
                                @error('editName') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Data Source</label>
                                @if($isCreating)
                                    <select class="form-control" wire:model="editDataSource">
                                        @foreach($this->availableDataSources as $source)
                                            <option value="{{ $source['key'] }}">{{ $source['label'] }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control" value="{{ $editDataSource }}" disabled>
                                    <p class="ui-meta mt-1 mb-0">Data source cannot be changed after creation.</p>
                                @endif
                                @error('editDataSource') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                                @error('data_source') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>

                            @if(! $isCreating)
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model.defer="editIsActive" id="editIsActive">
                                        <label class="form-check-label" for="editIsActive">Active</label>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Default Export Format</label>
                                <select class="form-control" wire:model.defer="editDefaultFormat">
                                    <option value="xlsx">Excel (.xlsx)</option>
                                    @if($editDataSource === 'salary_sheet')
                                        <option value="pdf">PDF</option>
                                    @endif
                                </select>
                            </div>

                            @if(! empty($builderMeta['group_by_options']))
                                <div class="mb-3">
                                    <label class="form-label">Default Group By</label>
                                    <select class="form-control" wire:model.defer="editGroupBy">
                                        @foreach($builderMeta['group_by_options'] as $option)
                                            <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                                        @endforeach
                                    </select>
                                    @error('layout.group_by') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Columns</label>
                                <p class="ui-meta mb-2">Select, reorder, and optionally relabel approved fields.</p>
                                <div class="row">
                                    @foreach($this->editAvailableColumns as $columnKey => $columnLabel)
                                        @php
                                            $isMandatory = in_array($columnKey, $builderMeta['mandatory_columns'] ?? [], true);
                                            $isSelected = in_array($columnKey, $editColumns, true);
                                        @endphp
                                        <div class="col-12 mb-2">
                                            <div class="d-flex align-items-start gap-2 p-2 border rounded">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox"
                                                        value="{{ $columnKey }}"
                                                        wire:model="editColumns"
                                                        id="col_{{ md5($columnKey) }}"
                                                        @if($isMandatory) onclick="return false;" @endif>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <label class="form-check-label fw-semibold" for="col_{{ md5($columnKey) }}">
                                                        {{ $columnLabel }}
                                                        @if($isMandatory)
                                                            <span class="ui-badge ui-badge--neutral">Required</span>
                                                        @endif
                                                    </label>
                                                    @if($isSelected || $isMandatory)
                                                        <div class="row g-2 mt-1">
                                                            <div class="col-md-6">
                                                                <input type="text" class="form-control form-control-sm"
                                                                    placeholder="Custom label (optional)"
                                                                    wire:model.defer="editColumnLabels.{{ $columnKey }}">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <select class="form-control form-control-sm"
                                                                    wire:model.defer="editColumnFormats.{{ $columnKey }}">
                                                                    @foreach($this->availableFormats as $format)
                                                                        <option value="{{ $format }}">{{ ucfirst($format) }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-2 d-flex gap-1">
                                                                <button type="button" class="ui-icon-btn" title="Move up"
                                                                    wire:click="moveColumnUp('{{ $columnKey }}')">
                                                                    <span class="material-symbols-outlined" style="font-size: 1rem;">arrow_upward</span>
                                                                </button>
                                                                <button type="button" class="ui-icon-btn" title="Move down"
                                                                    wire:click="moveColumnDown('{{ $columnKey }}')">
                                                                    <span class="material-symbols-outlined" style="font-size: 1rem;">arrow_downward</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('editColumns') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                                @error('columns') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Filters</label>
                                    <button type="button" class="ui-link-btn" wire:click="addFilter">Add filter</button>
                                </div>
                                @forelse($editFilters as $index => $filter)
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-md-4">
                                            <select class="form-control form-control-sm" wire:model.defer="editFilters.{{ $index }}.field">
                                                @foreach($builderMeta['filters'] as $field)
                                                    <option value="{{ $field }}">{{ $field }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-control form-control-sm" wire:model.defer="editFilters.{{ $index }}.op">
                                                @foreach($builderMeta['operators'] as $op)
                                                    <option value="{{ $op }}">{{ $op }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <select class="form-control form-control-sm" wire:model.defer="editFilters.{{ $index }}.param">
                                                @foreach($builderMeta['parameters'] as $parameter)
                                                    <option value="{{ $parameter['key'] }}">{{ $parameter['label'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="ui-icon-btn" wire:click="removeFilter({{ $index }})">
                                                <span class="material-symbols-outlined" style="font-size: 1rem;">close</span>
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <p class="ui-meta mb-0">No filters configured.</p>
                                @endforelse
                                @error('filters') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Sorting</label>
                                <div class="row g-2">
                                    <div class="col-md-8">
                                        <select class="form-control" wire:model.defer="editSortField">
                                            <option value="">Default</option>
                                            @foreach($builderMeta['sortable'] as $field)
                                                <option value="{{ $field }}">{{ $field }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-control" wire:model.defer="editSortDir">
                                            <option value="asc">Ascending</option>
                                            <option value="desc">Descending</option>
                                        </select>
                                    </div>
                                </div>
                                @error('sort') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Runtime Parameters</label>
                                <div class="row">
                                    @foreach($builderMeta['parameters'] as $parameter)
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    value="{{ $parameter['key'] }}"
                                                    wire:model="editParameters"
                                                    id="param_{{ $parameter['key'] }}"
                                                    @if(!empty($parameter['required'])) onclick="return false;" @endif>
                                                <label class="form-check-label" for="param_{{ $parameter['key'] }}">
                                                    {{ $parameter['label'] }}
                                                    @if(!empty($parameter['required']))
                                                        <span class="ui-meta">(required)</span>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('parameters') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="ui-modal-footer">
                            <button type="button" class="ui-btn-secondary" wire:click="closeEditModal">Cancel</button>
                            <button type="button" class="ui-btn-primary" wire:click="saveTemplate">
                                {{ $isCreating ? 'Create Template' : 'Save Template' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</main>
