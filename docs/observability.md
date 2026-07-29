# Observability Contract

Enterprise observability for the payroll application. Application instrumentation is OpenTelemetry-first and vendor-neutral. Domain audits in MySQL remain authoritative for compliance; logs/metrics/traces carry references and aggregates only.

## Environment tiers

| Tier | Purpose | Debug | Default log level | Paging |
|------|---------|-------|-------------------|--------|
| `local` | Developer machines | allowed | `debug` | none |
| `staging` | Pre-production validation | off | `info` | warning Slack/email |
| `production` | Customer workloads | off | `info` (errors always retained) | page on SEV1/SEV2 |

Owners:
- Platform / SRE: stack uptime, collectors, storage, alert routing
- Payroll engineering: payroll/report/attendance business telemetry
- Security: authn/authz denials, export downloads, settings changes

## Telemetry envelope (required fields)

Every structured log, span attribute set, and domain-audit reference includes applicable fields:

| Field | Description |
|-------|-------------|
| `timestamp` | ISO-8601 UTC |
| `severity` | `debug`, `info`, `warning`, `error`, `critical` |
| `service.name` | Always `payroll-app` |
| `service.version` | App release / git SHA |
| `deployment.environment` | `local` / `staging` / `production` |
| `event.name` | Lowercase dotted verb, e.g. `payroll.run.started` |
| `event.category` | `application`, `security`, `audit`, `business`, `integration` |
| `event.outcome` | `success`, `failure`, `denied` |
| `event.schema_version` | Integer; bump on incompatible schema changes |
| `request.id` | Correlation ID (`X-Request-ID`) |
| `trace.id` / `span.id` | W3C trace context |
| `company.id` | Tenant scope when known |
| `actor.user_id` / `actor.role` | Authenticated actor when known |
| Workflow IDs | `payroll.run_id`, `report.run_id`, `attendance.month`, `job.batch_id`, etc. |
| `error.type` / `error.code` | Sanitized failure classification |

## Safety rules (non-negotiable)

Never emit in logs, metrics labels, or traces:

- Passwords, tokens, cookies, session payloads
- Email, phone, address, bank/tax identifiers
- Employee names or free-text leave reasons
- Salary amounts, gross/net, statutory wage figures
- Raw spreadsheet rows, AI prompts/responses, raw OpenRouter bodies

Allowed instead: immutable IDs, counts, durations, status enums, route templates, HTTP status classes, job class names, tool names.

Unknown context keys are **dropped**, not serialized. Free-text passes through `TelemetryRedactor`.

## Cardinality rules

Metric labels may only use bounded values:

- `environment`, `route_template`, `status_class`, `queue`, `job_class`, `workflow_status`, `provider`, `tool_name`, `event_name`, `outcome`

Never use IDs, emails, exception messages, or request IDs as labels.

## Volume rules

- One lifecycle summary per request / job / import / export
- Sample successful high-volume request traces (default 10% outside errors)
- Always retain: errors, security events, payroll lifecycle transitions, regulated export/download audits

## Retention

| Signal | Hot | Warm / archive |
|--------|-----|----------------|
| Application logs (Elastic) | 14 days | 90 days (security channel 180 days) |
| Metrics (Prometheus) | 15 days | optional remote write |
| Traces (Tempo) | 7 days | — |
| Domain audits (MySQL) | Policy-driven (default 365 days) | immutable backups |

## Event catalogue

See [docs/observability/event-catalogue.md](observability/event-catalogue.md).

## Adding logs (for developers / interns)

- How to emit events: [docs/observability/adding-logs-guide.md](observability/adding-logs-guide.md)
- Core HRMS modules and required logs: [docs/observability/core-hrms-modules-logging.md](observability/core-hrms-modules-logging.md)

## Alert ownership

See [docs/observability/alert-ownership.md](observability/alert-ownership.md).

## Runbooks

See [docs/runbooks/](runbooks/).
