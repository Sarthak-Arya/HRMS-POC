# Service Domain & Audit Events Registry

This document lists the domain events triggered internally by the backend services in `app/Services`. Since this application does not use native Laravel Event broadcasting (`Event::dispatch()`), important lifecycle operations and domain state changes are captured as explicit Audit Logs through various Logger services.

---

## 1. Payroll & Report Audit Events (`AuditEventType`)

These events are captured in the standard `AuditLog` table. They track payroll lifecycle and report generation.

### `AuditEventType::CREATE`
* **Triggered by**:
  * [`ReportRunnerService`](file:///c:/Java/Payroll%20Software/Payroll%20POC/soft-ui-dashboard-livewire-master/app/Services/Reports/ReportRunnerService.php#L95) when a standard or salary sheet report run is successfully executed and exported.
  * [`PayrollGenerationService`](file:///c:/Java/Payroll%20Software/Payroll%20POC/soft-ui-dashboard-laravel-livewire-master/app/Services/Payroll/PayrollGenerationService.php#L51) when a new `PayrollRun` is initialized or a new `EmployeePayroll` is drafted.
* **Payload Highlights**: Includes `template_slug`, `parameters`, `row_count` (for reports) or full model snapshots (for payroll).

### `AuditEventType::UPDATE`
* **Triggered by**:
  * [`ReportAuditLogger`](file:///c:/Java/Payroll%20Software/Payroll%20POC/soft-ui-dashboard-livewire-master/app/Services/Reports/ReportAuditLogger.php#L47) during template lifecycle modifications.
  * [`PayrollGenerationService`](file:///c:/Java/Payroll%20Software/Payroll%20POC/soft-ui-dashboard-laravel-livewire-master/app/Services/Payroll/PayrollGenerationService.php#L215) when an existing drafted `EmployeePayroll` is recalculated.
* **Payload Highlights**: Captures `old_values` and `new_values` for diff tracking.

### `AuditEventType::STATUS_CHANGE`
* **Triggered by**:
  * [`PayrollRunManager`](file:///c:/Java/Payroll%20Software/Payroll%20POC/soft-ui-dashboard-laravel-livewire-master/app/Services/Payroll/PayrollRunManager.php#L43) when a `PayrollRun` moves between statuses (e.g., `DRAFT` ➔ `PROCESSING` ➔ `COMPLETED`).
  * [`PayrollRunManager`](file:///c:/Java/Payroll%20Software/Payroll%20POC/soft-ui-dashboard-laravel-livewire-master/app/Services/Payroll/PayrollRunManager.php#L74) when an `EmployeePayroll` changes status (e.g., `DRAFT` ➔ `APPROVED` ➔ `PAID`).
* **Payload Highlights**: Logs the old and new `status` enum values.

### `AuditEventType::CALCULATION`
* **Triggered by**: [`PayrollGenerationService`](file:///c:/Java/Payroll%20Software/Payroll%20POC/soft-ui-dashboard-laravel-livewire-master/app/Services/Payroll/PayrollGenerationService.php#L187) during the payroll calculation step.
* **Payload Highlights**: Logged for each individual `EmployeePayrollLine` (Earnings, Deductions, Taxes). It captures the calculated amount, the basis of calculation, and the component type.

---

## 2. Attendance Audit Events (`AttendanceAuditService`)

These events track modifications to attendance data and are logged into the dedicated `AttendanceAuditLog` table.

### `daily_saved`
* **Entity Type**: `employee_attendance`
* **Triggered by**: [`AttendanceCommandService`](file:///c:/Java/Payroll%20Software/Payroll%20POC/soft-ui-dashboard-laravel-livewire-master/app/Services/Attendance/AttendanceCommandService.php#L58)
* **Condition**: Fired when daily attendance rows are saved or overridden manually for an employee.
* **Payload**: Before/after snapshot of the employee's daily attendance records and `['month' => $month, 'year' => $year, 'rows' => $count]`.

### `monthly_created` / `monthly_saved`
* **Entity Type**: `employee_attendance_summary`
* **Triggered by**: [`AttendanceCommandService`](file:///c:/Java/Payroll%20Software/Payroll%20POC/soft-ui-dashboard-laravel-livewire-master/app/Services/Attendance/AttendanceCommandService.php#L194)
* **Condition**: Fired when a monthly summary is aggregated, created, or updated due to imports or daily overrides.
* **Payload**: Before/after snapshot of the monthly summary data.

### `summary_locked`
* **Entity Type**: `employee_attendance_summary`
* **Triggered by**: [`MonthLockAndReconciliationService`](file:///c:/Java/Payroll%20Software/Payroll%20POC/soft-ui-dashboard-laravel-livewire-master/app/Services/Attendance/MonthLockAndReconciliationService.php#L48)
* **Condition**: Fired when a month's attendance is locked to prevent further modifications before or during payroll generation.
* **Payload**: Snapshots of the summary and `['month' => $month, 'year' => $year]`.

---

## 3. Settings Audit Events (`CompanySettingsAuditService`)

The settings subsystem logs changes to company configuration values.

### `CREATE` / `UPDATE`
* **Triggered by**: `CompanySettingsAuditService::log()` which is called from various settings update flows (e.g., `CompanySettingsService`).
* **Payload Highlights**: Captures `section` (settings category), `before_json`, `after_json`, `company_id`, `actor_user_id`, and optional `reason`.

---

## 4. Ops Telemetry Events (Elastic / Grafana)

Structured, privacy-safe events are also emitted via `App\Services\Observability\DomainTelemetry` and `App\Support\Observability\Telemetry`.

See the canonical catalogue in [`docs/observability/event-catalogue.md`](../docs/observability/event-catalogue.md). Domain audit tables remain authoritative for compliance snapshots; ops events carry IDs, counts, and outcomes only.

