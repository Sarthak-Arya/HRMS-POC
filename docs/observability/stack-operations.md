# Observability Stack Operations

## Local startup

```bash
# From repository root
cp .env.example .env   # if needed
docker compose -f docker-compose.observability.yml up -d
```

Optional env vars (export or place in a local `.env` next to compose):

| Variable | Default | Purpose |
|----------|---------|---------|
| `GRAFANA_ADMIN_USER` | `admin` | Grafana bootstrap admin |
| `GRAFANA_ADMIN_PASSWORD` | `changeme` | Must rotate in shared environments |
| `MYSQL_ANALYTICS_*` | host.docker.internal / grafana_ro | Read-only MySQL for audit dashboards |
| `ALERT_EMAIL_TO` | — | Alertmanager email target |
| `ALERT_WEBHOOK_URL` | — | ChatOps webhook |

## Application wiring

```env
LOG_CHANNEL=stderr_json
LOG_LEVEL=info
OTEL_ENABLED=true
OTEL_SERVICE_NAME=payroll-app
OTEL_EXPORTER_OTLP_ENDPOINT=http://127.0.0.1:4318
OTEL_TRACES_SAMPLER_RATIO=0.1
QUEUE_CONNECTION=redis
METRICS_TOKEN=generate-a-long-random-token
```

Ship `storage/logs` via Filebeat (mounted in compose). Prefer `stderr_json` in containers so the platform log driver can also collect stdout/stderr.

## Production notes

- Bind ports to private networks / reverse proxy only; do not expose Elasticsearch publicly.
- Enable TLS and auth for Elastic/Grafana in production (compose baseline disables Elastic security for local POC simplicity).
- Supply secrets from a secret manager; never commit production passwords.
- Create MySQL user `grafana_ro` with SELECT-only grants on audit tables.
- Back up Grafana provisioning (already in Git) and Elasticsearch snapshots quarterly.

## Failure isolation

If Elasticsearch, Tempo, Prometheus, or the OTel collector are unavailable, the Laravel app must continue serving traffic. Telemetry export is best-effort and non-blocking.
