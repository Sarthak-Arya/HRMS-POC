# Event Catalogue

Schema version: `1`. Event names are lowercase dotted verbs.

## Platform / reliability

| Event | Category | Outcome | Notes |
|-------|----------|---------|-------|
| `http.request.completed` | application | success/failure | method, route template, status, duration_ms |
| `exception.reported` | application | failure | error.type, fingerprint |
| `database.slow_query` | application | success | duration only |
| `database.connection_failed` | application | failure | |
| `cache.operation_failed` | application | failure | |
| `mail.send_failed` | integration | failure | |
| `http.client.completed` | integration | success/failure | provider, status_class, latency |
| `queue.job.started` | application | success | job_class |
| `queue.job.completed` | application | success | duration_ms |
| `queue.job.failed` | application | failure | attempts |
| `queue.job.retried` | application | failure | |
| `queue.batch.started` | business | success | batch_id |
| `queue.batch.completed` | business | success | totals |
| `queue.batch.failed` | business | failure | |
| `scheduler.heartbeat` | application | success | |
| `health.liveness` | application | success/failure | |
| `health.readiness` | application | success/failure | dependency checks |
| `app.booted` | application | success | |

## Security / access

| Event | Category | Outcome |
|-------|----------|---------|
| `auth.login.succeeded` | security | success |
| `auth.login.failed` | security | failure |
| `auth.logout` | security | success |
| `auth.signup.completed` | security | success |
| `auth.password_reset.requested` | security | success |
| `auth.password_reset.completed` | security | success |
| `auth.session.invalidated` | security | success |
| `access.permission.denied` | security | denied |
| `access.role.denied` | security | denied |
| `access.company.denied` | security | denied |
| `access.rate_limited` | security | denied |
| `export.payslip.downloaded` | audit | success/failure |
| `export.payslip_bulk.downloaded` | audit | success/failure |
| `export.salary_sheet.downloaded` | audit | success/failure |
| `export.report.completed` | audit | success/failure |
| `admin.role.changed` | audit | success |

## Payroll / compensation

| Event | Category | Outcome |
|-------|----------|---------|
| `payroll.run.created` | business | success |
| `payroll.run.validation_failed` | business | failure |
| `payroll.run.queued` | business | success |
| `payroll.run.processing` | business | success |
| `payroll.run.completed` | business | success |
| `payroll.run.failed` | business | failure |
| `payroll.run.cancelled` | business | success |
| `payroll.run.status_changed` | business | success |
| `payroll.employee.status_changed` | business | success |
| `payroll.job.failed` | business | failure |
| `compensation.component.changed` | audit | success |
| `compensation.import.started` | business | success |
| `compensation.import.completed` | business | success |
| `compensation.import.failed` | business | failure |
| `compensation.statutory.failed` | business | failure |
| `compensation.adjustment.applied` | business | success |

## Attendance / reports / settings / AI

| Event | Category | Outcome |
|-------|----------|---------|
| `attendance.policy.changed` | audit | success |
| `attendance.policy.assigned` | audit | success |
| `attendance.import.completed` | business | success/failure |
| `attendance.daily.saved` | business | success |
| `attendance.monthly.saved` | business | success |
| `attendance.month.locked` | business | success |
| `attendance.compoff.changed` | business | success |
| `attendance.leave_exception.changed` | business | success |
| `report.template.changed` | audit | success |
| `report.preview.requested` | business | success |
| `report.export.started` | business | success |
| `report.export.completed` | business | success/failure |
| `settings.section.changed` | audit | success |
| `settings.organization.changed` | audit | success |
| `ai.request.completed` | integration | success/failure |
| `ai.tool.mutating_invoked` | audit | success/failure |

Payloads for all events above must contain IDs and counts only — never salary figures, PII, prompts, or raw import rows.
