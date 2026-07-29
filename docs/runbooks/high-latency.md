# Runbook: Collector Down / High Latency

## Collector down
1. `docker compose -f docker-compose.observability.yml ps`
2. Restart `otel-collector`; verify OTLP ports 4317/4318.
3. Confirm app continues serving (telemetry is best-effort).

## High latency
1. Inspect p95 by route template.
2. Check DB slow queries and queue wait.
3. Sample Tempo traces for the slow route.
