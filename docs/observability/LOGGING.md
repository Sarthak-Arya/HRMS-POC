# Logging & Observability — What’s Implemented and How to Run

This guide covers the HRMS structured logging work: what events exist, how the stack runs, and where to view logs in the UI.

---

## Quick start (run everything)

Prerequisites: Docker Desktop, PHP app running (`php artisan serve`), MySQL seeded.

```bash
# 1) From repo root — app env (already in .env.example)
# LOG_CHANNEL=stack
# LOG_STACK=stderr_json,daily
# LOG_LEVEL=debug
# OTEL_ENABLED=false   # set true after stack is healthy

# 2) Ensure log dir exists (Filebeat mounts this)
mkdir -p storage/logs

# 3) Start observability stack
docker compose -f docker-compose.observability.yml up -d

# 4) Provision Kibana data views + verify Grafana datasources
bash scripts/setup-observability-ui.sh

# 5) Smoke-test the app
curl http://127.0.0.1:8000/healthz
curl http://127.0.0.1:8000/readyz
curl http://127.0.0.1:8000/metrics | head
```

Demo login (after `php artisan db:seed`):

- Email: `admin@softui.com`
- Password: `secret`

---

## Where to view logs / metrics

| What | URL | Notes |
|------|-----|--------|
| **Application Logs** (Grafana) | http://localhost:3000/d/payroll-application-logs/application-logs | Live log stream from Elasticsearch |
| **Business & Security** (Grafana) | http://localhost:3000/d/payroll-business-security/payroll-business-security | Prometheus counters / rates |
| **Service Health** (Grafana) | http://localhost:3000/d/payroll-service-health/service-health | HTTP rate / latency |
| Grafana login | http://localhost:3000 | `admin` / `changeme` |
| **Kibana Discover** | http://localhost:5601/app/discover | Data view: **Payroll All Logs** |
| Prometheus | http://localhost:9090 | Raw metric queries |
| Local files | `storage/logs/*.log` | JSON lines (always available) |

```bash
# Tail without Docker
tail -f storage/logs/security*.log
tail -f storage/logs/payroll*.log
tail -f storage/logs/application*.log
```

**Tip:** Grafana home is empty by design. Open **Dashboards → Payroll Observability**, or the Application Logs link above. Set time range to **Last 1 hour** / **Last 24 hours**.

---

## Architecture (mental model)

```
Browser / Livewire
        │
        ▼
Laravel (DomainTelemetry → JSON logs + MetricsRegistry)
        │
        ├── storage/logs/*.log  ──Filebeat──► Elasticsearch ◄── Kibana / Grafana Explore
        │
        ├── GET /metrics  ──Prometheus scrape──► Grafana metric dashboards
        │
        └── OTLP :4318 (optional) ──► OTel Collector ──► Tempo (traces)
```

- **Logs** = privacy-safe structured events (`event.name`, `company.id`, counts).
- **Metrics** = Prometheus counters (`payroll_business_events_total`, HTTP, etc.).
- **MySQL audits** = compliance before/after (separate from Elastic).

Never log employee names, emails, salaries, or raw Excel rows.

---

## What’s implemented

### How events are emitted

Use `App\Services\Observability\DomainTelemetry::emit()`:

```php
$this->telemetry->emit(
    'employee.import.completed',  // event.name
    'business',                   // category: business|audit|security|application|integration
    $failed > 0 ? 'failure' : 'success',
    [
        'company.id' => $companyId,
        'processed_count' => $ok,
        'failed_count' => $failed,
    ],
    $failed > 0 ? 'warning' : 'info'
);
```

Core helpers:

| Piece | Path |
|-------|------|
| Emit API | `app/Services/Observability/DomainTelemetry.php` |
| JSON format / channels | `app/Support/Observability/Telemetry.php` |
| PII redaction | `app/Support/Observability/TelemetryRedactor.php` |
| Prometheus registry | `app/Support/Observability/MetricsRegistry.php` (persisted under `storage/framework/cache/`) |
| Request IDs | `app/Http/Middleware/RequestContextMiddleware.php` |
| Health / metrics | `/healthz`, `/readyz`, `/metrics` |

Log channels (see `config/logging.php`): `stderr_json`, `daily`, `application`, `payroll`, `security`, `integration`.

### Module coverage

| Module | Status | Key events |
|--------|--------|------------|
| Auth / access | Done | `auth.login.*`, `access.*.denied` |
| Employees | Done | `employee.created/updated`, `save.failed`, `import.*`, `template.downloaded`, `status_changed` |
| Company / org | Done | `company.created/updated`, `save.failed`, `import.completed`, `settings.organization.changed` |
| Compensation | Done | `component/structure/assignment/override.changed`, `import.*`, `adjustment.applied` |
| Attendance | Done / Partial | daily/monthly/lock via audit; plus `import.completed`, `policy.assigned`, `compoff`, leave type/exception, `lock_failed` |
| Payroll | Done | run lifecycle, queue batch, validation_failed, cancelled, job failed, payslip/salary-sheet exports |
| Reports | Done | `template.changed`, preview, export started/completed |
| Settings | Done | `section.changed`, `section.save_failed` |
| AI | Done | `ai.request.completed`, `ai.tool.mutating_invoked` |

Still optional / no clean write path yet:

- `company.setup.step_completed`
- `compensation.statutory.failed`

Full names: [event-catalogue.md](event-catalogue.md). Per-module checklist: [core-hrms-modules-logging.md](core-hrms-modules-logging.md). Coding guide: [adding-logs-guide.md](adding-logs-guide.md).

---

## Env vars (app)

```env
LOG_CHANNEL=stack
LOG_STACK=stderr_json,daily
LOG_LEVEL=debug

OTEL_ENABLED=false
OTEL_SERVICE_NAME=payroll-app
OTEL_EXPORTER_OTLP_ENDPOINT=http://127.0.0.1:4318
OTEL_TRACES_SAMPLER_RATIO=0.1
OTEL_EXPORT_TIMEOUT=0.5
OTEL_EXPORT_DISABLED=false
METRICS_TOKEN=
```

Turn `OTEL_ENABLED=true` only after `docker compose … up -d` is healthy. Export is best-effort and must not break payroll if the collector is down.

Optional Grafana password when starting compose:

```bash
GRAFANA_ADMIN_PASSWORD='your-strong-password' docker compose -f docker-compose.observability.yml up -d
```

---

## Day-to-day commands

```bash
# Start / stop stack
docker compose -f docker-compose.observability.yml up -d
docker compose -f docker-compose.observability.yml ps
docker compose -f docker-compose.observability.yml stop
docker compose -f docker-compose.observability.yml down        # keep volumes
docker compose -f docker-compose.observability.yml down -v     # wipe Elastic/Prometheus/Grafana data

# Re-provision Kibana / check datasources
bash scripts/setup-observability-ui.sh

# Confirm Prometheus sees app metrics
curl -s 'http://127.0.0.1:9090/api/v1/query?query=payroll_http_requests_total'
```

Prometheus scrapes Laravel at `host.docker.internal:8000/metrics` (see `observability/prometheus/prometheus.yml`). Keep `php artisan serve` (or equivalent) on port **8000**.

---

## Verify end-to-end

1. Stack healthy: `docker compose -f docker-compose.observability.yml ps`
2. App responds: `curl http://127.0.0.1:8000/healthz` → JSON + `X-Request-ID`
3. `/metrics` shows `payroll_*` lines after a few requests
4. Log in / save an employee / run payroll
5. Check:
   - `storage/logs/` for JSON with `event.name`
   - Grafana **Application Logs** for the same events
   - Grafana **Business & Security** for counters (may need ~30s after traffic)

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Grafana “No data” on Business & Security | That’s **metrics**, not logs. Use Application Logs for lines. Hard-refresh; set Last 1h; generate app traffic; confirm `/metrics` and Prometheus target `payroll-app` is **up**. |
| No logs in Kibana/Grafana | Ensure `storage/logs` has files; `docker compose … logs filebeat`; re-run `scripts/setup-observability-ui.sh`. |
| Login fails for admin | Seed DB: `php artisan db:seed` → `admin@softui.com` / `secret`. |
| OTEL errors in silence | Set `OTEL_ENABLED=false` until collector is healthy. |
| Metrics empty under `artisan serve` | Registry persists to `storage/framework/cache/observability-metrics.json` — ensure that path is writable. |
| Ports busy | Free 3000, 4317/4318, 5601, 8889, 9090, 9093, 9200, 3200. |

---

## Related docs

| Doc | Purpose |
|-----|---------|
| [README.md](README.md) | Short checklist |
| [adding-logs-guide.md](adding-logs-guide.md) | How to emit events in code |
| [core-hrms-modules-logging.md](core-hrms-modules-logging.md) | What each HRMS module should log |
| [event-catalogue.md](event-catalogue.md) | Canonical event names |
| [stack-operations.md](stack-operations.md) | Ops / production notes |
| [../observability.md](../observability.md) | Contract, PII rules, retention |
