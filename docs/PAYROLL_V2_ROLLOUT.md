# Payroll V2 Rollout Guide

## Overview

Payroll V2 introduces a run-based payroll model alongside the existing legacy (V1) tables. V1 remains read-only historical data; all new payroll processing should use V2 tables.

## Table responsibilities

| V1 (legacy) | V2 (new) | Purpose |
|-------------|----------|---------|
| `payroll_header` | `employee_payrolls` | Per-employee payslip header for a month |
| `payroll_earnings` + `payroll_deductions` + `earnings` + `deductions` | `employee_payroll_lines` | Unified component lines |
| *(none)* | `payroll_runs` | Company-wide monthly payroll batch |
| *(none)* | `payroll_adjustments` | Manual arrears, bonuses, recoveries |
| *(none)* | `employee_loans` + `employee_loan_installments` | Loan EMI tracking |
| *(none)* | `audit_logs` | Centralized change log |
| *(none)* | `payroll_run_history`, `employee_payroll_history`, `employee_payroll_line_history` | Immutable version snapshots |

## Entity relationship map

```
company
  └── payroll_runs (company_id, month, year)
        ├── employee_payrolls (payroll_run_id, employee_id)
        │     ├── employee_payroll_lines (employee_payroll_id, component_id)
        │     ├── attendance (attendance_summary_id)
        │     └── employee_compensation_history (employee_compensation_id)
        ├── payroll_adjustments (payroll_run_id, employee_id)
        └── employee_loan_installments (payroll_run_id, loan_id)

employees
  ├── employee_loans
  └── monthly attendance (attendance table via MonthlyAttendance)
```

## Foreign key checklist

| Table | Column | References |
|-------|--------|------------|
| `payroll_runs` | `company_id` | `company.id` |
| `payroll_runs` | `processed_by` | `users.id` |
| `employee_payrolls` | `payroll_run_id` | `payroll_runs.id` |
| `employee_payrolls` | `employee_id` | `employees.id` |
| `employee_payrolls` | `attendance_summary_id` | `attendance.id` |
| `employee_payrolls` | `employee_compensation_id` | `employee_compensation_history.id` |
| `employee_payroll_lines` | `employee_payroll_id` | `employee_payrolls.id` |
| `employee_payroll_lines` | `component_id` | `compensation_components.id` (nullable) |
| `payroll_adjustments` | `payroll_run_id` | `payroll_runs.id` |
| `payroll_adjustments` | `employee_id` | `employees.id` |
| `payroll_adjustments` | `component_id` | `compensation_components.id` (nullable) |
| `payroll_adjustments` | `created_by` | `users.id` |
| `employee_loans` | `employee_id` | `employees.id` |
| `employee_loan_installments` | `loan_id` | `employee_loans.id` |
| `employee_loan_installments` | `payroll_run_id` | `payroll_runs.id` |

## Lifecycle rules

Implemented in `App\Services\Payroll\PayrollRunLifecycle` and enforced on model save/delete for `EmployeePayroll`, `EmployeePayrollLine`, and `PayrollAdjustment`.

### Payroll run status transitions

| From | Allowed to |
|------|------------|
| `DRAFT` | `PROCESSING` |
| `PROCESSING` | `DRAFT`, `COMPLETED` |
| `COMPLETED` | `LOCKED`, `PROCESSING` |
| `LOCKED` | *(none)* |

### Employee payroll status transitions

| From | Allowed to |
|------|------------|
| `DRAFT` | `APPROVED` |
| `APPROVED` | `PAID`, `DRAFT` |
| `PAID` | *(none)* |

### Lock behavior

- When a payroll run is `LOCKED`, no writes are allowed to child `employee_payrolls`, `employee_payroll_lines`, or `payroll_adjustments`.
- Corrections after lock must be recorded as adjustments in a future payroll run.

### Cross-company integrity

Before creating an `employee_payroll`:

1. `employee.company_id` must equal `payroll_run.company_id`.
2. `attendance.employee_id` must equal `employee.id`.
3. `attendance.company_id` must equal `payroll_run.company_id`.
4. `attendance.month/year` must match `payroll_run.month/year`.

Use `PayrollRunLifecycle::assertEmployeeBelongsToRunCompany()` and `assertAttendanceBelongsToEmployee()`.

## Audit strategy

### Centralized audit (`audit_logs`)

Use `App\Services\Payroll\PayrollAuditLogger` to record:

- `CREATE`, `UPDATE`, `DELETE`, `STATUS_CHANGE`, `CALCULATION` events
- `old_values` / `new_values` JSON payloads
- `changed_by`, `changed_at`, optional `request_id` and `source`

### Immutable history snapshots

Use `App\Services\Payroll\PayrollHistoryRecorder` before status changes or recalculations:

- `payroll_run_history`
- `employee_payroll_history`
- `employee_payroll_line_history`

Each row stores an incrementing `version_no` and full `snapshot_json`.

## Migrations

Run:

```bash
php artisan migrate
```

Migration files (in order):

1. `2026_06_17_000001_create_payroll_runs_table.php`
2. `2026_06_17_000002_create_employee_payrolls_table.php`
3. `2026_06_17_000003_create_employee_payroll_lines_table.php`
4. `2026_06_17_000004_create_payroll_adjustments_table.php`
5. `2026_06_17_000005_create_employee_loans_table.php`
6. `2026_06_17_000006_create_employee_loan_installments_table.php`
7. `2026_06_17_000007_create_audit_logs_table.php`
8. `2026_06_17_000008_create_payroll_history_tables.php`

## Seed data

After running base seeders:

```bash
php artisan db:seed --class=PayrollV2Seeder
```

Creates a sample June 2026 payroll run for the first company/employee with lines, an adjustment, and a loan.

## Optional V1 backfill (phase 2)

When ready to migrate historical data:

1. For each `payroll_header` row, create or find `payroll_runs` by `(company_id, month, year)` from the employee's company.
2. Map `payroll_header` → `employee_payrolls` using `attendance_id` as `attendance_summary_id`.
3. Resolve `employee_compensation_id` from `employee_compensation_history` effective on pay month.
4. Map `payroll_earnings` / `payroll_deductions` amounts into `employee_payroll_lines` with `component_name` from linked `earnings`/`deductions` rows.
5. Mark backfilled runs as `LOCKED` to prevent accidental edits.
6. Leave V1 tables untouched for audit trail.

## PHP enums

| Enum | Values |
|------|--------|
| `PayrollRunStatus` | `DRAFT`, `PROCESSING`, `COMPLETED`, `LOCKED` |
| `EmployeePayrollStatus` | `DRAFT`, `APPROVED`, `PAID` |
| `PayrollAdjustmentType` | `ADDITION`, `DEDUCTION` |
| `EmployeeLoanStatus` | `ACTIVE`, `CLOSED`, `HOLD` |
| `PayrollLineComponentType` | `EARNING`, `DEDUCTION`, `EMPLOYER_CONTRIBUTION` |
| `AuditEventType` | `CREATE`, `UPDATE`, `DELETE`, `STATUS_CHANGE`, `CALCULATION` |

MySQL deployments also enforce matching `CHECK` constraints on status/type columns.
