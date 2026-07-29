# Core HRMS Modules — What Logs to Add

Audience: interns adding observability to **core HRMS** features.

How to emit events (injection, naming, PII rules): see [adding-logs-guide.md](adding-logs-guide.md).  
Full event name list: [event-catalogue.md](event-catalogue.md).

This document answers: **which module, which actions, which logs, and what’s already done.**

---

## Scope (core HRMS only)

| # | Module | Main code areas |
|---|--------|-----------------|
| 1 | Company & organization | `AddCompanyDetails`, company list/view, `OrganizationStructureService`, setup |
| 2 | Employees | `EmployeeService`, `AddEmployeeDetails`, employee list/import |
| 3 | Attendance | `AttendanceHub`, `AttendanceCommandService`, policies, month lock, leave/comp-off |
| 4 | Compensation | `CompensationHub`, component/structure/assignment/import services |
| 5 | Payroll | `PayrollGenerationService`, `PayrollRunManager`, payslip/salary-sheet controllers |
| 6 | Reports | `ReportHub`, `ReportRunnerService`, `ReportTemplateService` |
| 7 | Company settings | `SettingsHub`, `CompanySettingsService` |

Out of scope for this doc (later): AI assistant, infra/health, pure platform metrics.

**Always use** `DomainTelemetry::emit()` with IDs/counts only — never employee names, emails, salaries, or raw Excel rows.

---

## Status legend

| Status | Meaning |
|--------|---------|
| Done | Structured telemetry already wired — extend carefully if you change the flow |
| Partial | Audit DB row exists and/or some events exist — fill gaps listed below |
| Todo | Intern should add these when touching the module |

---

## 1. Company & organization

**What it does:** Create/edit companies, departments, designations, and onboarding/setup progress.

**Key paths:** `app/Http/Livewire/AddCompanyDetails.php`, `ViewCompanies.php`, `app/Services/Settings/OrganizationStructureService.php`, `app/Services/Setup/CompanySetupProgressService.php`

| Action | Event name | Category | Outcome | Context (safe) | Status |
|--------|------------|----------|---------|----------------|--------|
| Company created | `company.created` | audit | success | `company.id` | Todo |
| Company updated | `company.updated` | audit | success | `company.id` | Todo |
| Company create/update failed | `company.save.failed` | business | failure | `company.id` (if any), `error.type` | Todo |
| Company Excel import finished | `company.import.completed` | business | success/failure | `processed_count`, `failed_count` | Todo |
| Org structure changed (dept/designation) | `settings.organization.changed` | audit | success | `company.id`, `section` | Todo (catalogue exists) |
| Setup step completed | `company.setup.step_completed` | business | success | `company.id`, `status` (step key) | Todo |

**Do not log:** company address free text, GST/PAN values, contact emails/phones from import rows.

**Where to put it:** Prefer the service that persists the company/org change, not every Livewire keystroke.

---

## 2. Employees

**What it does:** Add/edit employees, bulk import/export, list and view details.

**Key paths:** `app/Services/Employee/EmployeeService.php`, `app/Http/Livewire/AddEmployeeDetails.php`, `EmployeeList.php`

| Action | Event name | Category | Outcome | Context (safe) | Status |
|--------|------------|----------|---------|----------------|--------|
| Employee created | `employee.created` | business | success | `company.id` | Todo |
| Employee updated | `employee.updated` | business | success | `company.id` | Todo |
| Employee save failed | `employee.save.failed` | business | failure | `company.id`, `error.type` | Todo (replace noisy `Log::error` with PII) |
| Import started | `employee.import.started` | business | success | `company.id` | Todo |
| Import completed | `employee.import.completed` | business | success/failure | `company.id`, `processed_count`, `failed_count`, `skipped_count` | Todo |
| Import template downloaded | `employee.template.downloaded` | audit | success | `company.id`, `artifact_type` | Todo |
| Employee deactivated / exited | `employee.status_changed` | business | success | `company.id`, `from_status`, `to_status` | Todo |

**Do not log:** employee name, email, phone, bank account, Aadhaar/PAN, full import row JSON.

**Today:** `AddEmployeeDetails` / `EmployeeService` still use ad-hoc `Log::info` / `Log::error`. When you touch imports, replace those with the events above.

**Pattern:**

```php
$this->telemetry->emit('employee.import.completed', 'business', $failed > 0 ? 'failure' : 'success', [
    'company.id' => $companyId,
    'processed_count' => $ok,
    'failed_count' => $failed,
    'skipped_count' => $skipped,
], $failed > 0 ? 'warning' : 'info');
```

---

## 3. Attendance

**What it does:** Policies, assignments, daily/monthly entry, Excel import, month lock, holidays, leave types/balances, exceptions, comp-off.

**Key paths:** `AttendanceCommandService`, `AttendancePolicyService`, `MonthLockAndReconciliationService`, `AttendanceHub`, leave/comp-off services

| Action | Event name | Category | Outcome | Context (safe) | Status |
|--------|------------|----------|---------|----------------|--------|
| Daily attendance saved | `attendance.daily.saved` | business | success | `company.id`, month/year, `row_count` | Partial (via `AttendanceAuditService`) |
| Monthly summary saved/created | `attendance.monthly.saved` | business | success | `company.id`, month/year | Partial (via audit) |
| Month locked | `attendance.month.locked` | business | success | `company.id`, month/year | Partial (via audit) |
| Policy created/updated/deactivated | `attendance.policy.changed` | audit | success | `company.id` | Partial (via audit) |
| Policy assigned to scope | `attendance.policy.assigned` | audit | success | `company.id` | Todo |
| Attendance Excel import finished | `attendance.import.completed` | business | success/failure | `company.id`, month/year, counts | Todo |
| Comp-off granted/used/adjusted | `attendance.compoff.changed` | business | success | `company.id` | Todo |
| Leave exception rule changed | `attendance.leave_exception.changed` | business | success | `company.id` | Todo |
| Leave type created/updated | `attendance.leave_type.changed` | audit | success | `company.id` | Todo |
| Lock/reconcile failed | `attendance.month.lock_failed` | business | failure | `company.id`, month/year, `error.type` | Todo |

**How this module works:** Prefer calling `AttendanceAuditService::log(...)` for mutations that need MySQL audit. That service already emits matching telemetry for known actions. For **new** actions, extend its `match` map and the event catalogue.

**Do not log:** leave reason text, day-by-day matrices, employee names.

**Volume rule:** One summary per save/import/lock — never one log line per calendar day cell.

---

## 4. Compensation

**What it does:** Salary components, structures, employee assignments/overrides, structure Excel import, statutory helpers used at resolve time.

**Key paths:** `CompensationComponentService`, `CompensationStructureService`, `EmployeeCompensationService`, `CompensationStructureImportService`, `CompensationHub`

| Action | Event name | Category | Outcome | Context (safe) | Status |
|--------|------------|----------|---------|----------------|--------|
| Component created/updated | `compensation.component.changed` | audit | success | `company.id`, `status` (component type/code if non-PII) | Todo |
| Structure created/updated | `compensation.structure.changed` | audit | success | `company.id` | Todo |
| Employee compensation assigned/changed | `compensation.assignment.changed` | audit | success | `company.id` | Todo |
| Override applied | `compensation.override.changed` | audit | success | `company.id` | Todo |
| Import started | `compensation.import.started` | business | success | `company.id` | Todo |
| Import completed | `compensation.import.completed` | business | success/failure | `company.id`, counts | Todo |
| Import failed hard | `compensation.import.failed` | business | failure | `company.id`, `error.type` | Todo |
| Statutory calculation failed | `compensation.statutory.failed` | business | failure | `company.id`, `error.type` | Todo |

**Do not log:** salary amounts, CTC, component amounts, PF/ESI wage figures.

**Today:** Almost no `DomainTelemetry` in compensation services — this is a high-priority intern backlog when working on Compensation Hub.

---

## 5. Payroll

**What it does:** Create payroll runs, calculate employees, approve/pay status, batch jobs, payslips, salary sheets.

**Key paths:** `PayrollGenerationService`, `PayrollRunManager`, `ProcessEmployeePayroll`, `PayslipController`, `SalarySheetController`

| Action | Event name | Category | Outcome | Context (safe) | Status |
|--------|------------|----------|---------|----------------|--------|
| Run created | `payroll.run.created` | business | success | `company.id`, `payroll.run_id`, month/year | Done |
| Run processing / queued | `payroll.run.processing` / `payroll.run.queued` | business | success | run + counts | Done |
| Run completed / failed (sync) | `payroll.run.completed` / `payroll.run.failed` | business | success/failure | counts, `duration_ms` | Done |
| Run status transition | `payroll.run.status_changed` | business | success | `from_status`, `to_status` | Done |
| Employee payroll status | `payroll.employee.status_changed` | business | success | run id, statuses | Done |
| Batch started/completed/failed | `queue.batch.*` | business | * | `job.batch_id`, counts | Done |
| Per-employee job failed | `payroll.job.failed` | business | failure | `payroll.run_id`, `error.type` | Done |
| Readiness/validation failed before run | `payroll.run.validation_failed` | business | failure | `company.id`, `payroll.run_id` | Todo |
| Run cancelled | `payroll.run.cancelled` | business | success | `payroll.run_id` | Todo |
| Adjustment applied | `compensation.adjustment.applied` or `payroll.adjustment.applied` | business | success | `payroll.run_id` | Todo |
| Payslip download | `export.payslip.downloaded` | audit | success/failure | run id, `row_count` | Done |
| Bulk payslip download | `export.payslip_bulk.downloaded` | audit | success/failure | run id, `row_count` | Done |
| Salary sheet download | `export.salary_sheet.downloaded` | audit | success/failure | run id, `format` | Done |

**Do not log:** gross/net, line amounts, or full `EmployeePayroll::toArray()`.

**Intern focus:** Prefer fixing gaps (validation failed, cancel, adjustments). Do not add per-line `CALCULATION` telemetry — MySQL audit already covers that and it is high volume.

---

## 6. Reports

**What it does:** Custom report templates, preview, Excel/PDF-style exports, salary-sheet-backed reports.

**Key paths:** `ReportRunnerService`, `ReportTemplateService`, `ReportHub`

| Action | Event name | Category | Outcome | Context (safe) | Status |
|--------|------------|----------|---------|----------------|--------|
| Template created/updated/deleted | `report.template.changed` | audit | success | `company.id`, `artifact_type` | Partial (DB audit via `ReportAuditLogger`; add emit if missing) |
| Preview requested | `report.preview.requested` | business | success | `company.id`, `artifact_type` | Done |
| Export started | `report.export.started` | business | success | `company.id`, `format` | Done |
| Export completed/failed | `report.export.completed` | business | success/failure | `duration_ms`, `format` | Done |

**Do not log:** preview row cell values (may contain salary/PII).

**Intern focus:** Ensure template CRUD emits `report.template.changed` with template id/slug only (no config blobs with sensitive formulas if they embed amounts).

---

## 7. Company settings

**What it does:** Per-company configuration sections (profile, statutory, tax, attendance defaults, compensation defaults, reports).

**Key paths:** `CompanySettingsService`, `CompanySettingsAuditService`, `SettingsHub`

| Action | Event name | Category | Outcome | Context (safe) | Status |
|--------|------------|----------|---------|----------------|--------|
| Section saved | `settings.section.changed` | audit | success | `company.id`, `section` | Done (via audit service) |
| Save failed | `settings.section.save_failed` | business | failure | `company.id`, `section`, `error.type` | Todo |

**Do not log:** before/after setting JSON in Elastic (that stays in MySQL `company_settings_audit_logs`). Telemetry = section name + company id only.

---

## Cross-cutting (still required for HRMS)

Not a “module,” but every HRMS screen sits behind auth/access. Already largely Done:

| Action | Event | Status |
|--------|-------|--------|
| Login success/failure | `auth.login.*` | Done |
| Permission denied | `access.permission.denied` | Done |
| Company access denied | `access.company.denied` | Done |
| Role denied | `access.role.denied` | Done |

If you add signup, logout, or password-reset flows, add the catalogue events (`auth.signup.completed`, `auth.logout`, etc.).

---

## Suggested intern backlog (priority)

Work in this order when asked to “add logs to HRMS”:

1. **Employees** — import started/completed + save failed (replace unsafe `Log::` calls)  
2. **Compensation** — component/structure/assignment changes + import summaries  
3. **Company** — create/update + import summary  
4. **Attendance gaps** — import completed, policy assignment, comp-off, leave exception  
5. **Payroll gaps** — validation failed, cancel, adjustments  
6. **Reports** — confirm template change emits structured event  

---

## Minimum fields every HRMS event should carry

| Field | Required when |
|-------|----------------|
| `event.name` / category / outcome | Always (via `emit`) |
| `company.id` | Almost always |
| `payroll.run_id` | Payroll / payslip / salary sheet |
| `attendance.month` + `attendance.year` | Attendance month-scoped actions |
| `processed_count` / `failed_count` / `row_count` | Imports, batch jobs, exports |
| `from_status` / `to_status` | Status transitions |
| `error.type` | Failures |
| `duration_ms` | Long operations (export, full run) |

`request.id`, `actor.user_id`, and service metadata are attached automatically when the HTTP middleware ran.

---

## Quick decision tree

```text
Is this a user-visible HRMS action (save/import/export/status change)?
  └─ Yes → DomainTelemetry::emit(...) with catalogue event name
       └─ Also needs compliance before/after in MySQL?
            └─ Yes → use existing *AuditService / *AuditLogger as well
  └─ No (internal helper / loop) → do not log

Does the payload include name, email, salary, or Excel row?
  └─ Yes → remove it; keep IDs and counts only
```

---

## Related docs

- [adding-logs-guide.md](adding-logs-guide.md) — how to code the emit  
- [event-catalogue.md](event-catalogue.md) — add new event names in the same PR  
- [observability.md](../observability.md) — safety and retention rules  
