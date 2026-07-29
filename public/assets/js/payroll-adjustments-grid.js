(function () {
    'use strict';

    var AG_GRID_URL = 'https://cdn.jsdelivr.net/npm/ag-grid-community@32.3.3/dist/ag-grid-community.min.js';

    var state = {
        gridApi: null,
        originalRowData: [],
        components: [],
        editable: false,
        livewireComponent: null,
    };

    function currencyFormatter(params) {
        if (params.value === null || params.value === undefined || params.value === '') {
            return '';
        }

        return Number(params.value).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function buildColumnDefs(components, editable) {
        var employeeColumns = [
            {
                field: 'employee_code',
                headerName: 'Code',
                pinned: 'left',
                width: 110,
                filter: 'agTextColumnFilter',
                floatingFilter: true,
            },
            {
                field: 'employee_name',
                headerName: 'Name',
                pinned: 'left',
                minWidth: 180,
                filter: 'agTextColumnFilter',
                floatingFilter: true,
            },
            {
                field: 'department',
                headerName: 'Department',
                pinned: 'left',
                minWidth: 150,
                filter: 'agTextColumnFilter',
                floatingFilter: true,
            },
        ];

        var adjustmentColumns = components.map(function (component) {
            var field = 'adj_' + component.id;
            var isDeduction = component.type === 'DEDUCTION';

            return {
                field: field,
                colId: field,
                headerName: component.name,
                headerTooltip: isDeduction ? 'Deduction' : 'Addition',
                minWidth: 150,
                type: 'numericColumn',
                filter: 'agNumberColumnFilter',
                floatingFilter: true,
                editable: editable,
                cellClass: isDeduction ? 'adj-cell-deduction' : 'adj-cell-addition',
                headerClass: isDeduction ? 'adj-header-deduction' : 'adj-header-addition',
                valueFormatter: currencyFormatter,
                valueParser: function (params) {
                    if (params.newValue === null || params.newValue === undefined || params.newValue === '') {
                        return null;
                    }

                    var parsed = Number(params.newValue);
                    return Number.isFinite(parsed) ? parsed : null;
                },
            };
        });

        return [
            {
                headerName: 'Employee',
                marryChildren: true,
                children: employeeColumns,
            },
            {
                headerName: 'Adjustments',
                marryChildren: true,
                openByDefault: true,
                children: adjustmentColumns,
            },
        ];
    }

    function renderColumnPanel(gridApi, components) {
        var list = document.getElementById('payroll-adjustment-column-list');
        var search = document.getElementById('payroll-adjustment-column-search');

        if (!list || !gridApi) {
            return;
        }

        list.innerHTML = '';

        components.forEach(function (component) {
            var field = 'adj_' + component.id;
            var column = gridApi.getColumn(field);
            var wrapper = document.createElement('label');
            wrapper.className = 'd-flex align-items-center gap-2 py-1 px-2 ui-column-item';

            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.checked = column ? column.isVisible() : true;
            checkbox.dataset.field = field;
            checkbox.addEventListener('change', function () {
                gridApi.setColumnsVisible([field], checkbox.checked);
            });

            var text = document.createElement('span');
            text.className = 'text-sm';
            text.textContent = component.name;

            wrapper.appendChild(checkbox);
            wrapper.appendChild(text);
            wrapper.dataset.name = component.name.toLowerCase();
            list.appendChild(wrapper);
        });

        if (search) {
            search.oninput = function () {
                var query = search.value.trim().toLowerCase();
                list.querySelectorAll('.ui-column-item').forEach(function (item) {
                    item.classList.toggle('d-none', query !== '' && item.dataset.name.indexOf(query) === -1);
                });
            };
        }
    }

    function collectChanges(originalRows, currentRows, components) {
        var changes = [];
        var originalByEmployee = {};

        originalRows.forEach(function (row) {
            originalByEmployee[row.employee_id] = row;
        });

        currentRows.forEach(function (row) {
            var original = originalByEmployee[row.employee_id] || {};

            components.forEach(function (component) {
                var field = 'adj_' + component.id;
                var newValue = Object.prototype.hasOwnProperty.call(row, field) ? row[field] : null;
                var oldValue = Object.prototype.hasOwnProperty.call(original, field) ? original[field] : null;

                if (newValue === oldValue) {
                    return;
                }

                changes.push({
                    employee_id: row.employee_id,
                    component_id: component.id,
                    amount: newValue,
                });
            });
        });

        return changes;
    }

    function applyThemeClass(gridEl) {
        if (!gridEl) {
            return;
        }

        var isDark = document.documentElement.getAttribute('data-theme') === 'dark'
            || document.body.classList.contains('theme-dark');

        gridEl.classList.remove('ag-theme-alpine', 'ag-theme-alpine-dark');
        gridEl.classList.add(isDark ? 'ag-theme-alpine-dark' : 'ag-theme-alpine');
    }

    function destroyGrid() {
        if (state.gridApi && typeof state.gridApi.destroy === 'function') {
            state.gridApi.destroy();
        }

        state.gridApi = null;
    }

    function resolveLivewireComponent(root) {
        var host = root ? root.closest('[wire\\:id]') : null;
        if (!host || !window.Livewire) {
            return null;
        }

        return Livewire.find(host.getAttribute('wire:id'));
    }

    function parseMatrix() {
        var dataEl = document.getElementById('payroll-adjustment-matrix-data');
        var raw = dataEl ? dataEl.textContent.trim() : null;

        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            console.error('Could not parse payroll adjustment matrix JSON.', error);
            return null;
        }
    }

    function initGrid(root, matrix, livewireComponent) {
        if (!root || !matrix || !window.agGrid || typeof window.agGrid.createGrid !== 'function') {
            return false;
        }

        var gridEl = root.querySelector('#payroll-adjustment-grid');
        if (!gridEl) {
            return false;
        }

        destroyGrid();
        gridEl.innerHTML = '';

        state.components = matrix.components || [];
        state.editable = !!matrix.editable;
        state.originalRowData = JSON.parse(JSON.stringify(matrix.rowData || []));
        state.livewireComponent = livewireComponent || resolveLivewireComponent(root);

        applyThemeClass(gridEl);

        var gridOptions = {
            columnDefs: buildColumnDefs(state.components, state.editable),
            rowData: matrix.rowData || [],
            defaultColDef: {
                sortable: true,
                resizable: true,
                filter: true,
            },
            animateRows: true,
            suppressMovableColumns: false,
            enableCellTextSelection: true,
            stopEditingWhenCellsLoseFocus: true,
        };

        try {
            state.gridApi = window.agGrid.createGrid(gridEl, gridOptions);
        } catch (error) {
            console.error('Failed to initialize payroll adjustments grid.', error);
            return false;
        }

        renderColumnPanel(state.gridApi, state.components);

        var quickFilter = document.getElementById('payroll-adjustment-quick-filter');
        if (quickFilter) {
            quickFilter.oninput = function () {
                if (state.gridApi) {
                    state.gridApi.setGridOption('quickFilterText', quickFilter.value);
                }
            };
        }

        var saveButton = document.getElementById('payroll-adjustment-save');
        if (saveButton) {
            saveButton.onclick = function () {
                if (!state.gridApi || !state.livewireComponent || !state.editable) {
                    return;
                }

                var currentRows = [];
                state.gridApi.forEachNode(function (node) {
                    currentRows.push(JSON.parse(JSON.stringify(node.data)));
                });

                var changes = collectChanges(state.originalRowData, currentRows, state.components);
                if (changes.length === 0) {
                    window.alert('No changes to save.');
                    return;
                }

                state.livewireComponent.call('saveAdjustmentMatrix', changes);
            };
        }

        root.dataset.initialized = '1';
        return true;
    }

    function refreshGrid(matrix) {
        if (!state.gridApi || !matrix) {
            return;
        }

        state.originalRowData = JSON.parse(JSON.stringify(matrix.rowData || []));
        state.gridApi.setGridOption('rowData', matrix.rowData || []);
        renderColumnPanel(state.gridApi, state.components);

        var dataEl = document.getElementById('payroll-adjustment-matrix-data');
        if (dataEl) {
            dataEl.textContent = JSON.stringify(matrix);
        }
    }

    function ensureAgGridLoaded(callback) {
        if (window.agGrid && typeof window.agGrid.createGrid === 'function') {
            callback();
            return;
        }

        if (window.__payrollAgGridLoading) {
            window.__payrollAgGridCallbacks = window.__payrollAgGridCallbacks || [];
            window.__payrollAgGridCallbacks.push(callback);
            return;
        }

        window.__payrollAgGridLoading = true;
        window.__payrollAgGridCallbacks = [callback];

        var script = document.createElement('script');
        script.src = AG_GRID_URL;
        script.onload = function () {
            window.__payrollAgGridLoading = false;
            (window.__payrollAgGridCallbacks || []).forEach(function (cb) {
                cb();
            });
            window.__payrollAgGridCallbacks = [];
        };
        script.onerror = function () {
            window.__payrollAgGridLoading = false;
            console.error('Failed to load AG Grid for payroll adjustments.');
        };
        document.body.appendChild(script);
    }

    function tryMountGrid() {
        var root = document.getElementById('payroll-adjustment-grid-root');
        if (!root || root.dataset.initialized === '1') {
            return;
        }

        var matrix = parseMatrix();
        if (!matrix) {
            return;
        }

        ensureAgGridLoaded(function () {
            initGrid(root, matrix, resolveLivewireComponent(root));
        });
    }

    function registerGridHooks() {
        if (window.Livewire && !window.__payrollAdjustmentGridHookRegistered) {
            window.__payrollAdjustmentGridHookRegistered = true;
            Livewire.hook('message.processed', function () {
                window.setTimeout(tryMountGrid, 0);
            });
        }

        tryMountGrid();
    }

    window.PayrollAdjustmentsGrid = {
        mount: initGrid,
        refresh: refreshGrid,
        destroy: destroyGrid,
        tryMount: tryMountGrid,
    };

    window.addEventListener('adjustment-matrix-saved', function (event) {
        if (event.detail && event.detail.matrix) {
            refreshGrid(event.detail.matrix);
        }
    });

    window.addEventListener('payroll-adjustments-tab-shown', function () {
        window.setTimeout(tryMountGrid, 50);
    });

    document.addEventListener('livewire:load', registerGridHooks);
    registerGridHooks();
})();
