(function () {
    "use strict";

    var AG_GRID_URL =
        "https://cdn.jsdelivr.net/npm/ag-grid-community@32.3.3/dist/ag-grid-community.min.js";

    var state = {
        gridApi: null,
        matrix: null,
        livewireComponent: null,
        schemaKey: null,
    };

    function decimalFormatter(params) {
        if (
            params.value === null ||
            params.value === undefined ||
            params.value === ""
        ) {
            return "";
        }

        var num = Number(params.value);
        if (!Number.isFinite(num)) {
            return "";
        }

        return Number.isInteger(num)
            ? String(num)
            : num.toFixed(2).replace(/\.?0+$/, function (match) {
                  return match === ".00" ? "" : match.replace(/0+$/, "");
              });
    }

    function numericParser(params) {
        if (
            params.newValue === null ||
            params.newValue === undefined ||
            params.newValue === ""
        ) {
            return null;
        }

        var parsed = Number(params.newValue);
        return Number.isFinite(parsed) ? parsed : null;
    }

    function buildNumericColumn(field, headerName, editable, extra) {
        var column = {
            field: field,
            headerName: headerName,
            type: "numericColumn",
            filter: "agNumberColumnFilter",
            floatingFilter: true,
            editable: editable,
            minWidth: 96,
            maxWidth: 140,
            valueFormatter: decimalFormatter,
            valueParser: numericParser,
        };

        if (extra) {
            Object.keys(extra).forEach(function (key) {
                column[key] = extra[key];
            });
        }

        return column;
    }

    function computePresentDays(data, leaveTypes) {
        var workingDays = Number(data.working_days) || 0;
        var esiLeave = Number(data.esi_leave) || 0;
        var holiday = Number(data.holiday) || 0;
        var unpaidLeave = 0;

        leaveTypes.forEach(function (leaveType) {
            if (!leaveType.is_paid) {
                unpaidLeave += Number(data["leave_" + leaveType.index]) || 0;
            }
        });

        return Math.max(0, workingDays - unpaidLeave - esiLeave - holiday);
    }

    function buildColumnDefs(matrix) {
        var editable = !!matrix.editable;
        var leaveTypes = matrix.leaveTypes || [];

        var employeeColumns = [
            {
                field: "employee_code",
                headerName: "Code",
                pinned: "left",
                width: 108,
                filter: "agTextColumnFilter",
                floatingFilter: true,
                editable: false,
            },
            {
                field: "employee_name",
                headerName: "Name",
                pinned: "left",
                minWidth: 160,
                filter: "agTextColumnFilter",
                floatingFilter: true,
                editable: false,
            },
            {
                field: "department",
                headerName: "Department",
                pinned: "left",
                minWidth: 130,
                filter: "agTextColumnFilter",
                floatingFilter: true,
                editable: false,
            },
            {
                field: "designation",
                headerName: "Designation",
                minWidth: 130,
                filter: "agTextColumnFilter",
                floatingFilter: true,
                editable: false,
            },
        ];

        var leaveColumns = leaveTypes.map(function (leaveType) {
            return buildNumericColumn(
                "leave_" + leaveType.index,
                leaveType.code,
                editable,
                {
                    headerTooltip: leaveType.name,
                    headerClass: "att-leave-header",
                },
            );
        });

        return [
            {
                headerName: "Employee",
                marryChildren: true,
                children: employeeColumns,
            },
            {
                headerName: "Attendance",
                marryChildren: true,
                children: [
                    buildNumericColumn("working_days", "Working Days", editable, {
                        editable: function (params) {
                            return editable && !params.data.policy_resolved;
                        },
                    }),
                ],
            },
            {
                headerName: "Leave",
                marryChildren: true,
                openByDefault: true,
                children: leaveColumns,
            },
            {
                headerName: "Summary",
                marryChildren: true,
                children: [
                    buildNumericColumn("present_days", "Present Days", false, {
                        editable: false,
                        valueGetter: function (params) {
                            return computePresentDays(
                                params.data || {},
                                leaveTypes,
                            );
                        },
                        cellClass: "att-present-cell",
                    }),
                    buildNumericColumn("esi_leave", "ESI Leave", editable),
                    buildNumericColumn("holiday", "Holiday", editable),
                    buildNumericColumn("tot_dys", "Total Days", editable, {
                        cellClass: "att-total-cell",
                    }),
                ],
            },
        ];
    }

    function schemaKey(matrix) {
        if (!matrix) {
            return "";
        }

        return [
            matrix.editable ? "1" : "0",
            (matrix.leaveTypes || [])
                .map(function (lt) {
                    return lt.code + (lt.is_paid ? "P" : "U");
                })
                .join(","),
            matrix.period ? matrix.period.month + "-" + matrix.period.year : "",
        ].join("|");
    }

    function renderColumnPanel(gridApi, matrix) {
        var list = document.getElementById("attendance-monthly-column-list");
        var search = document.getElementById(
            "attendance-monthly-column-search",
        );

        if (!list || !gridApi) {
            return;
        }

        list.innerHTML = "";

        var togglableFields = ["designation"];
        (matrix.leaveTypes || []).forEach(function (leaveType) {
            togglableFields.push("leave_" + leaveType.index);
        });
        ["present_days", "esi_leave", "holiday", "tot_dys"].forEach(function (field) {
            togglableFields.push(field);
        });

        togglableFields.forEach(function (field) {
            var column = gridApi.getColumn(field);
            if (!column) {
                return;
            }

            var wrapper = document.createElement("label");
            wrapper.className =
                "d-flex align-items-center gap-2 py-1 px-2 ui-column-item";

            var checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.checked = column.isVisible();
            checkbox.dataset.field = field;
            checkbox.addEventListener("change", function () {
                gridApi.setColumnsVisible([field], checkbox.checked);
            });

            var text = document.createElement("span");
            text.className = "text-sm";
            text.textContent = column.getColDef().headerName || field;

            wrapper.appendChild(checkbox);
            wrapper.appendChild(text);
            wrapper.dataset.name = text.textContent.toLowerCase();
            list.appendChild(wrapper);
        });

        if (search) {
            search.oninput = function () {
                var query = search.value.trim().toLowerCase();
                list.querySelectorAll(".ui-column-item").forEach(
                    function (item) {
                        item.classList.toggle(
                            "d-none",
                            query !== "" &&
                                item.dataset.name.indexOf(query) === -1,
                        );
                    },
                );
            };
        }
    }

    function updateStatus(gridApi) {
        var statusEl = document.getElementById(
            "attendance-monthly-grid-status",
        );
        if (!statusEl || !gridApi) {
            return;
        }

        var total = gridApi.getDisplayedRowCount();
        var filtered = 0;
        gridApi.forEachNodeAfterFilter(function () {
            filtered++;
        });

        statusEl.textContent =
            filtered === total
                ? total + " employees"
                : filtered + " of " + total + " employees";
    }

    function applyThemeClass(gridEl) {
        if (!gridEl) {
            return;
        }

        var isDark =
            document.documentElement.getAttribute("data-theme") === "dark" ||
            document.body.classList.contains("theme-dark");

        gridEl.classList.remove("ag-theme-alpine", "ag-theme-alpine-dark");
        gridEl.classList.add(
            isDark ? "ag-theme-alpine-dark" : "ag-theme-alpine",
        );
    }

    function isGridMounted(root) {
        if (!root) {
            return false;
        }

        var gridEl = root.querySelector("#attendance-monthly-grid");
        return !!(gridEl && gridEl.querySelector(".ag-root-wrapper"));
    }

    function destroyGrid() {
        if (state.gridApi && typeof state.gridApi.destroy === "function") {
            try {
                state.gridApi.destroy();
            } catch (error) {
                console.warn("Attendance monthly grid destroy failed.", error);
            }
        }

        state.gridApi = null;
        state.schemaKey = null;

        var root = document.getElementById("attendance-monthly-grid-root");
        if (root) {
            root.dataset.initialized = "0";
        }
    }

    function resolveLivewireComponent(root) {
        var host = root ? root.closest("[wire\\:id]") : null;
        if (!host || !window.Livewire) {
            return null;
        }

        return Livewire.find(host.getAttribute("wire:id"));
    }

    function parseMatrix() {
        var dataEl = document.getElementById("attendance-monthly-matrix-data");
        var raw = dataEl ? dataEl.textContent.trim() : null;

        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            console.error(
                "Could not parse attendance monthly matrix JSON.",
                error,
            );
            return null;
        }
    }

    function bindToolbar(matrix) {
        var quickFilter = document.getElementById(
            "attendance-monthly-quick-filter",
        );
        if (quickFilter) {
            quickFilter.oninput = function () {
                if (state.gridApi) {
                    state.gridApi.setGridOption(
                        "quickFilterText",
                        quickFilter.value,
                    );
                }
            };
        }

        var resetFilters = document.getElementById(
            "attendance-monthly-reset-filters",
        );
        if (resetFilters) {
            resetFilters.onclick = function () {
                if (!state.gridApi) {
                    return;
                }

                state.gridApi.setFilterModel(null);
                state.gridApi.setGridOption("quickFilterText", "");
                if (quickFilter) {
                    quickFilter.value = "";
                }
            };
        }

        var exportButton = document.getElementById(
            "attendance-monthly-export-csv",
        );
        if (exportButton) {
            exportButton.onclick = function () {
                if (!state.gridApi) {
                    return;
                }

                var period = matrix.period || {};
                state.gridApi.exportDataAsCsv({
                    fileName:
                        "monthly-attendance-" +
                        (period.year || "") +
                        "-" +
                        String(period.month || "").padStart(2, "0") +
                        ".csv",
                });
            };
        }

        var saveButton = document.getElementById("attendance-monthly-save");
        if (saveButton) {
            saveButton.onclick = function () {
                if (
                    !state.gridApi ||
                    !state.livewireComponent ||
                    !matrix.editable
                ) {
                    return;
                }

                var rows = [];
                state.gridApi.forEachNode(function (node) {
                    if (node.data) {
                        rows.push(JSON.parse(JSON.stringify(node.data)));
                    }
                });

                if (rows.length === 0) {
                    window.alert("No rows to save.");
                    return;
                }

                state.livewireComponent.call("saveMonthlyMatrix", rows);
            };
        }
    }

    function initGrid(root, matrix, livewireComponent) {
        if (
            !root ||
            !matrix ||
            !window.agGrid ||
            typeof window.agGrid.createGrid !== "function"
        ) {
            return false;
        }

        var gridEl = root.querySelector("#attendance-monthly-grid");
        if (!gridEl) {
            return false;
        }

        destroyGrid();
        gridEl.innerHTML = "";

        state.matrix = matrix;
        state.livewireComponent =
            livewireComponent || resolveLivewireComponent(root);
        state.schemaKey = schemaKey(matrix);

        applyThemeClass(gridEl);

        var gridOptions = {
            columnDefs: buildColumnDefs(matrix),
            rowData: matrix.rowData || [],
            defaultColDef: {
                sortable: true,
                resizable: true,
                filter: true,
                suppressHeaderMenuButton: false,
            },
            getRowId: function (params) {
                return String(params.data.employee_id);
            },
            animateRows: true,
            enableCellTextSelection: true,
            stopEditingWhenCellsLoseFocus: true,
            pagination: true,
            paginationPageSize: 25,
            paginationPageSizeSelector: [10, 25, 50, 100],
            suppressDragLeaveHidesColumns: true,
            onFilterChanged: function () {
                updateStatus(state.gridApi);
            },
            onModelUpdated: function () {
                updateStatus(state.gridApi);
            },
        };

        try {
            state.gridApi = window.agGrid.createGrid(gridEl, gridOptions);
        } catch (error) {
            console.error(
                "Failed to initialize attendance monthly grid.",
                error,
            );
            return false;
        }

        window.setTimeout(function () {
            if (
                state.gridApi &&
                typeof state.gridApi.sizeColumnsToFit === "function"
            ) {
                state.gridApi.sizeColumnsToFit();
            }
        }, 0);

        renderColumnPanel(state.gridApi, matrix);
        bindToolbar(matrix);
        updateStatus(state.gridApi);

        root.dataset.initialized = "1";
        root.dataset.schemaKey = state.schemaKey;
        return true;
    }

    function refreshGrid(matrix) {
        if (!matrix) {
            return;
        }

        var root = document.getElementById("attendance-monthly-grid-root");
        if (!root) {
            return;
        }

        var nextSchemaKey = schemaKey(matrix);
        if (!state.gridApi || state.schemaKey !== nextSchemaKey) {
            root.dataset.initialized = "0";
            initGrid(root, matrix, resolveLivewireComponent(root));
            return;
        }

        state.matrix = matrix;
        state.gridApi.setGridOption("rowData", matrix.rowData || []);
        state.gridApi.setGridOption("columnDefs", buildColumnDefs(matrix));
        renderColumnPanel(state.gridApi, matrix);
        bindToolbar(matrix);
        updateStatus(state.gridApi);

        var dataEl = document.getElementById("attendance-monthly-matrix-data");
        if (dataEl) {
            dataEl.textContent = JSON.stringify(matrix);
        }
    }

    function ensureAgGridLoaded(callback) {
        if (window.agGrid && typeof window.agGrid.createGrid === "function") {
            callback();
            return;
        }

        if (window.__attendanceMonthlyAgGridLoading) {
            window.__attendanceMonthlyAgGridCallbacks =
                window.__attendanceMonthlyAgGridCallbacks || [];
            window.__attendanceMonthlyAgGridCallbacks.push(callback);
            return;
        }

        window.__attendanceMonthlyAgGridLoading = true;
        window.__attendanceMonthlyAgGridCallbacks = [callback];

        var script = document.createElement("script");
        script.src = AG_GRID_URL;
        script.onload = function () {
            window.__attendanceMonthlyAgGridLoading = false;
            (window.__attendanceMonthlyAgGridCallbacks || []).forEach(
                function (cb) {
                    cb();
                },
            );
            window.__attendanceMonthlyAgGridCallbacks = [];
        };
        script.onerror = function () {
            window.__attendanceMonthlyAgGridLoading = false;
            console.error(
                "Failed to load AG Grid for attendance monthly entry.",
            );
        };
        document.body.appendChild(script);
    }

    function tryMountGrid() {
        var root = document.getElementById("attendance-monthly-grid-root");
        if (!root) {
            return;
        }

        var matrix = parseMatrix();
        if (!matrix) {
            return;
        }

        var nextSchemaKey = schemaKey(matrix);

        if (
            isGridMounted(root) &&
            root.dataset.schemaKey === nextSchemaKey &&
            state.gridApi
        ) {
            refreshGrid(matrix);
            return;
        }

        root.dataset.initialized = "0";
        destroyGrid();

        ensureAgGridLoaded(function () {
            initGrid(root, matrix, resolveLivewireComponent(root));
        });
    }

    var mountRetryTimer = null;

    function scheduleMountRetries() {
        if (mountRetryTimer) {
            window.clearInterval(mountRetryTimer);
        }

        var attempts = 0;
        mountRetryTimer = window.setInterval(function () {
            attempts += 1;
            tryMountGrid();

            var root = document.getElementById("attendance-monthly-grid-root");
            if (isGridMounted(root) || attempts >= 24) {
                window.clearInterval(mountRetryTimer);
                mountRetryTimer = null;
            }
        }, 200);
    }

    function registerGridHooks() {
        if (window.Livewire && !window.__attendanceMonthlyGridHookRegistered) {
            window.__attendanceMonthlyGridHookRegistered = true;
            Livewire.hook("message.processed", function () {
                window.setTimeout(tryMountGrid, 50);
            });
        }

        tryMountGrid();
        scheduleMountRetries();
    }

    window.AttendanceMonthlyGrid = {
        mount: initGrid,
        refresh: refreshGrid,
        destroy: destroyGrid,
        tryMount: tryMountGrid,
    };

    window.addEventListener("attendance-monthly-matrix-saved", function () {
        window.setTimeout(function () {
            destroyGrid();
            tryMountGrid();
            scheduleMountRetries();
        }, 50);
    });

    window.addEventListener("attendance-monthly-tab-shown", function () {
        window.setTimeout(function () {
            tryMountGrid();
            scheduleMountRetries();
        }, 50);
    });

    document.addEventListener("livewire:load", registerGridHooks);
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", registerGridHooks);
    } else {
        registerGridHooks();
    }
})();
