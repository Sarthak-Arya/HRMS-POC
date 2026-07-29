# RBAC, Access Reviews & Retention

## Grafana RBAC

| Role | Access |
|------|--------|
| Viewer | Read dashboards (ops + business) |
| Editor | Edit dashboards in staging only |
| Admin | Datasources, alerts, users (platform team) |
| Security Analyst | Security dashboards + Elastic security indices |

Production Grafana must use SSO when available; local admin password only for bootstrap and must be rotated.

## Elasticsearch / Kibana

- Application indices: `payroll-logs-*`
- Security indices: `payroll-security-*` (longer retention, restricted role)
- No write access from Grafana users; Filebeat is the sole writer

## MySQL audit analytics

Grafana uses a **read-only** MySQL user with SELECT on:

- `audit_logs`
- `attendance_audit_logs`
- `company_settings_audit_logs`
- `report_runs`
- `failed_jobs`
- `job_batches`
- `payroll_runs` (status/timing columns only via views if available)

Never grant INSERT/UPDATE/DELETE to the analytics user.

## Access reviews

- Quarterly review of Grafana, Kibana, and MySQL analytics accounts
- Remove leavers within 24 hours
- Alert rule ownership reviewed with on-call rotation changes

## Retention targets

| Store | Retention |
|-------|-----------|
| Prometheus TSDB | 15d |
| Tempo | 7d |
| Elastic `payroll-logs-*` | hot 14d / delete 90d |
| Elastic `payroll-security-*` | hot 30d / delete 180d |
| MySQL domain audits | 365d default (policy override allowed) |

Volumes should be encrypted at rest in production. Backup/restore of Grafana provisioning and Elastic snapshots is tested quarterly.
