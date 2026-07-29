@error('adjustmentMatrix')<div class="alert alert-danger py-2 text-sm mb-3">{{ $message }}</div>@enderror

<div wire:ignore id="payroll-adjustment-grid-root">
    <div class="ui-card-toolbar">
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <div class="ui-search-field">
                <span class="material-symbols-outlined">search</span>
                <input type="search"
                       id="payroll-adjustment-quick-filter"
                       class="ui-search-input"
                       placeholder="Search employees...">
            </div>
            <p class="ui-meta mb-0">
                {{ count($adjustmentMatrix['rowData']) }} Employees
                <span class="ui-meta-divider">·</span>
                {{ count($adjustmentMatrix['components']) }} Adjustments
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(!$run->isLocked())
                <button type="button" id="payroll-adjustment-save" class="ui-btn-primary">
                    Save Changes
                </button>
            @endif
        </div>
    </div>

    <div class="d-flex ui-data-grid-wrap overflow-hidden">
        <div class="flex-grow-1">
            <div id="payroll-adjustment-grid" class="ag-theme-alpine"></div>
        </div>
        <div class="ui-column-panel border-start">
            <div class="p-3 border-bottom">
                <h6 class="mb-2">Visible Columns</h6>
                <div class="ui-search-field">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search"
                           id="payroll-adjustment-column-search"
                           class="ui-search-input"
                           placeholder="Filter columns...">
                </div>
            </div>
            <div id="payroll-adjustment-column-list" class="py-1"></div>
        </div>
    </div>

    <p class="ui-grid-help">
        Click adjustment column group arrows to expand or collapse. Use the Columns panel to show or hide adjustment types.
        @if($adjustmentMatrix['editable'])
            Edit amounts inline, then click <strong>Save Changes</strong>. Clear a cell to remove an adjustment.
        @else
            This run is locked; amounts are read-only.
        @endif
    </p>
</div>
