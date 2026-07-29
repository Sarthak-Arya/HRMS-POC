# Intern Guide: Adding Logs to Modules

This guide shows how to add **structured, privacy-safe logs** when you work on payroll, attendance, reports, auth, settings, or AI code.

Read this first: [observability.md](../observability.md) (rules) and [event-catalogue.md](event-catalogue.md) (event names).

---

## 1. What to use (and what not to)

| Goal | Use this | Do not use |
|------|----------|------------|
| Business / security / audit lifecycle event | `App\Services\Observability\DomainTelemetry` | `Log::info('Employee '.$name.'...')` |
| Compliance snapshot in MySQL | Existing audit services (`PayrollAuditLogger`, `AttendanceAuditService`, etc.) | Dumping full models into `Log::` |
| Quick local debug only | Temporary `Log::debug` — remove before merge | Leaving PII in debug logs |
| HTTP / exceptions | Already handled by middleware + `Handler` | Re-logging every request yourself |

**Rule of thumb:** Prefer `DomainTelemetry::emit()` for anything an operator might search in Grafana/Elastic. Prefer audit loggers when finance/compliance needs a durable before/after record in MySQL.

You often do **both**: write the audit row (source of truth), then emit a short telemetry summary (IDs + counts only).

---

## 2. Where to put the call

Add logging in **service / middleware / controller** boundaries — not deep inside every helper.

Good places:

- Start / end of a workflow (import finished, payroll queued, report exported)
- Status transitions (draft → processing → completed)
- Security denials (403 permission / company access)
- External API outcomes (OpenRouter status class + latency)
- Catch blocks for failures that affect users

Avoid:

- Logging inside tight loops (e.g. every payroll line / every day cell)
- Logging validation messages that include user input with PII
- Logging full Eloquent `toArray()` with salary fields

---

## 3. Inject `DomainTelemetry`

### Constructor injection (preferred in services)

```php
use App\Services\Observability\DomainTelemetry;

class MyService
{
    public function __construct(
        private readonly DomainTelemetry $telemetry,
    ) {}

    public function doSomething(int $companyId, int $runId): void
    {
        $this->telemetry->emit(
            'payroll.run.processing',  // event.name — see catalogue
            'business',                // category
            'success',                 // outcome: success | failure | denied
            [
                'company.id' => $companyId,
                'payroll.run_id' => $runId,
            ],
            'info'                     // severity: info | warning | error
        );
    }
}
```

### Method injection (controllers / Livewire)

```php
public function download(string $company_id, DomainTelemetry $telemetry)
{
    // ...
    $telemetry->emit('export.payslip.downloaded', 'audit', 'success', [
        'company.id' => (int) $company_id,
        'payroll.run_id' => $runId,
        'artifact_type' => 'payslip',
        'format' => 'pdf',
        'row_count' => 1,
    ]);
}
```

### Resolve from container (closures / batch callbacks)

```php
app(DomainTelemetry::class)->emit('queue.batch.failed', 'business', 'failure', [
    'payroll.run_id' => $run->id,
    'failed_count' => $batch->failedJobs,
], 'error');
```

### Security denials helper

```php
app(DomainTelemetry::class)->securityDenied('access.permission.denied', [
    'permission' => 'payroll.manage',
    'route.template' => '/{company_id}/payroll',
    'policy' => 'permission',
]);
```

---

## 4. Event naming

Use **lowercase dotted verbs**:

```text
{domain}.{entity}.{action}
```

Examples:

| Module | Good event name |
|--------|-----------------|
| Payroll | `payroll.run.created`, `payroll.run.failed` |
| Attendance | `attendance.month.locked`, `attendance.daily.saved` |
| Reports | `report.export.completed`, `report.preview.requested` |
| Auth | `auth.login.failed`, `auth.logout` |
| Security | `access.company.denied` |
| Exports | `export.salary_sheet.downloaded` |
| AI | `ai.request.completed`, `ai.tool.mutating_invoked` |
| Compensation | `compensation.import.completed` |

If the name is new, **add it to** [event-catalogue.md](event-catalogue.md) in the same PR.

### Categories

| Category | When |
|----------|------|
| `application` | HTTP, queues, health, generic exceptions |
| `business` | Payroll / attendance / report workflows |
| `audit` | Regulated actions (exports, settings, role changes) |
| `security` | Login, denials, rate limits |
| `integration` | OpenRouter / external HTTP |

### Outcomes

- `success` — completed as intended  
- `failure` — error / partial failure  
- `denied` — authz / access blocked  

### Severity

- `info` — normal success path  
- `warning` — denied access, soft failures, retries  
- `error` — user-visible or data-integrity failures  

---

## 5. Safe context fields (allowlist)

Only these kinds of keys survive redaction. Unknown keys are **dropped**.

Common safe fields:

```php
[
    'company.id' => 12,
    'actor.user_id' => 44,          // often filled automatically from auth
    'payroll.run_id' => 1001,
    'report.run_id' => 55,
    'attendance.month' => 7,
    'attendance.year' => 2026,
    'job.batch_id' => 'uuid...',
    'job.class' => 'ProcessEmployeePayroll',
    'job.attempts' => 2,
    'route.template' => '/{company_id}/payroll/{run}',
    'http.status' => 403,
    'http.status_class' => '4xx',
    'duration_ms' => 320,
    'processed_count' => 40,
    'failed_count' => 2,
    'skipped_count' => 1,
    'row_count' => 100,
    'artifact_type' => 'payslip',
    'format' => 'pdf',
    'from_status' => 'draft',
    'to_status' => 'processing',
    'permission' => 'reports.export',
    'policy' => 'canAccessCompany',
    'section' => 'statutory',
    'provider' => 'openrouter',
    'tool_name' => 'attendance.save_daily',
    'error.type' => ValidationException::class,
]
```

### Never log

- Employee names, emails, phones, addresses  
- Passwords, tokens, cookies  
- Salary / gross / net / tax amounts  
- Full import spreadsheet rows  
- AI prompts, chat responses, raw OpenRouter bodies  
- Leave reason free text  

**Bad:**

```php
Log::info('Saved attendance for '.$employee->employee_name, $row->toArray());
```

**Good:**

```php
$this->telemetry->emit('attendance.daily.saved', 'business', 'success', [
    'company.id' => $companyId,
    'attendance.month' => $month,
    'attendance.year' => $year,
    'row_count' => $count,
]);
```

---

## 6. Module cheat sheet

### Payroll (`app/Services/Payroll/`)

Emit on run create, status change, queue/batch start/complete/fail.

Reference: `PayrollGenerationService`, `PayrollRunManager`.

Also keep writing MySQL audits via `PayrollAuditLogger` — pass IDs/status only in `old_values` / `new_values` when possible (avoid full salary snapshots in new code).

### Attendance (`app/Services/Attendance/`)

Prefer extending `AttendanceAuditService` (it already emits telemetry). If you add a new action string, map it to an event name there and document it in the catalogue.

### Reports (`app/Services/Reports/`)

Emit `report.preview.requested`, `report.export.started`, `report.export.completed` (success and failure). Include `company.id`, `artifact_type`, `format`, `duration_ms`, `row_count` when known.

### Auth / access

- Login: `app/Http/Livewire/Auth/Login.php`  
- Denials: middleware under `app/Http/Middleware/` using `securityDenied()`  

Do **not** log the email or password on failed login — outcome only.

### Exports / downloads

Controllers such as `PayslipController`, `SalarySheetController`: emit audit events with `artifact_type`, `format`, `row_count`, `payroll.run_id`.

### AI (`app/Services/Ai/`)

Use `DomainTelemetry` + `recordHttpClient('openrouter', $status, $seconds)`. Log status class and latency only — never request/response bodies.

### Settings

`CompanySettingsAuditService` already emits `settings.section.changed` with `section` + `company.id`. Detailed before/after stays in MySQL only.

---

## 7. Success + failure pattern

Always emit a failure event when you catch something important:

```php
$started = microtime(true);

try {
    // ... work ...
    $this->telemetry->emit('report.export.completed', 'business', 'success', [
        'company.id' => $companyId,
        'format' => $format,
        'duration_ms' => (int) round((microtime(true) - $started) * 1000),
    ]);
} catch (\Throwable $e) {
    $this->telemetry->emit('report.export.completed', 'business', 'failure', [
        'company.id' => $companyId,
        'format' => $format,
        'error.type' => $e::class,
        'duration_ms' => (int) round((microtime(true) - $started) * 1000),
    ], 'error');
    throw $e;
}
```

Re-throw after logging unless your method is designed to swallow errors.

---

## 8. Queued jobs

If work continues in a job, pass correlation IDs so logs stay linked:

```php
new ProcessEmployeePayroll(
    $run->id,
    $employee->id,
    TelemetryContext::requestId(),
    TelemetryContext::traceId(),
);
```

Inside the job, restore context before work (see `ProcessEmployeePayroll`).

---

## 9. How to verify locally

1. Ensure logging is on (`.env`):

```env
LOG_CHANNEL=stack
LOG_STACK=stderr_json,daily
LOG_LEVEL=debug
```

2. Trigger your action in the app (e.g. save attendance, run payroll).

3. Check files under `storage/logs/` (`payroll.log`, `security.log`, `application.log`, etc.) — lines should be JSON with `event.name`, `request.id`, `company.id`.

4. Optional: start the observability stack and open Grafana:

```bash
docker compose -f docker-compose.observability.yml up -d
```

Grafana: http://localhost:3000 (`admin` / `changeme` unless overridden).

5. Hit `/healthz` and confirm `X-Request-ID` is returned — your events should share that id for the same browser request.

---

## 10. PR checklist

Before you open a pull request:

- [ ] Used `DomainTelemetry` (or an existing audit service that already emits)
- [ ] Event name follows `domain.entity.action` and is in [event-catalogue.md](event-catalogue.md)
- [ ] Category / outcome / severity are correct
- [ ] Context uses allowlisted keys only (IDs, counts, statuses)
- [ ] No PII, salary amounts, prompts, or raw import rows
- [ ] Failure path emits an event (not only the happy path)
- [ ] Not logging inside a hot loop
- [ ] Verified a JSON line appears in `storage/logs/` for your action

---

## 11. Copy-paste templates by module

### New payroll lifecycle event

```php
$this->telemetry->emit('payroll.run.completed', 'business', 'success', [
    'company.id' => $run->company_id,
    'payroll.run_id' => $run->id,
    'processed_count' => $processed,
    'failed_count' => $failed,
    'duration_ms' => $durationMs,
]);
```

### New attendance action

```php
$this->telemetry->emit('attendance.import.completed', 'business', 'success', [
    'company.id' => $companyId,
    'attendance.month' => $month,
    'attendance.year' => $year,
    'row_count' => $ok,
    'failed_count' => $failed,
]);
```

### New compensation import summary

```php
$this->telemetry->emit('compensation.import.completed', 'business', 'success', [
    'company.id' => $companyId,
    'processed_count' => $ok,
    'failed_count' => $failed,
]);
```

### New settings change (if not going through `CompanySettingsAuditService`)

```php
$this->telemetry->emit('settings.section.changed', 'audit', 'success', [
    'company.id' => $companyId,
    'section' => $section->value,
]);
```

---

## 12. Who to ask

- **Event naming / redaction rules** → see [observability.md](../observability.md) or ask Payroll eng  
- **Grafana dashboards / alerts** → Platform / SRE  
- **Security events** → do not invent new PII fields; ask before logging anything identity-related  

Questions while coding: prefer asking early over shipping a `Log::info` with employee or salary data.
