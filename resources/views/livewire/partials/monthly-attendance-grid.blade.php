@error('monthlyMatrix')<div class="alert alert-danger py-2 text-sm mb-3">{{ $message }}</div>@enderror

<div id="attendance-monthly-grid-root">
    <div class="ui-card-toolbar">
        <div class="d-flex flex-wrap gap-3 align-items-center flex-grow-1">
            <div class="ui-search-field">
                <span class="material-symbols-outlined">search</span>
                <input type="search"
                       id="attendance-monthly-quick-filter"
                       class="ui-search-input"
                       placeholder="Search employees, departments...">
            </div>
            <p class="ui-meta mb-0" id="attendance-monthly-grid-status">
                {{ count($monthlyMatrix['rowData']) }} employees
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" id="attendance-monthly-reset-filters" class="ui-btn-secondary ui-btn-secondary--sm">
                Reset Filters
            </button>
            <button type="button" id="attendance-monthly-export-csv" class="ui-btn-secondary ui-btn-secondary--sm">
                Export CSV
            </button>
            @if($monthlyMatrix['editable'])
                <button type="button" id="attendance-monthly-save" class="ui-btn-primary">
                    Save Changes
                </button>
            @endif
        </div>
    </div>

    <div class="d-flex ui-data-grid-wrap overflow-hidden">
        <div class="flex-grow-1" wire:ignore>
            <div id="attendance-monthly-grid" class="ag-theme-alpine"></div>
        </div>
        <div class="ui-column-panel border-start" wire:ignore>
            <div class="p-3 border-bottom">
                <h6 class="mb-2">Visible Columns</h6>
                <div class="ui-search-field">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search"
                           id="attendance-monthly-column-search"
                           class="ui-search-input"
                           placeholder="Filter columns...">
                </div>
            </div>
            <div id="attendance-monthly-column-list" class="py-1"></div>
        </div>
    </div>

    <p class="ui-grid-help">
        Sort any column, use floating filters under headers, or search across all fields.
        Click column group arrows to expand or collapse leave and deduction sections.
        @if($monthlyMatrix['editable'])
            Edit cells inline, then click <strong>Save Changes</strong>.
        @else
            Click <strong>Edit Attendance</strong> above to enable inline editing.
        @endif
    </p>

    <div id="attendance-monthly-matrix-data" class="d-none" aria-hidden="true">{!! json_encode($monthlyMatrix, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</div>
</div>
