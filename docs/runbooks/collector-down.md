# Runbook: Observability Collector Down

## Symptoms
- Alert `ObservabilityCollectorDown`
- Missing metrics/traces in Grafana

## Impact
Reduced visibility only — application traffic must continue.

## Mitigation
1. `docker compose -f docker-compose.observability.yml restart otel-collector`
2. Verify `http://localhost:4318` accepts OTLP.
3. Confirm Prometheus still scrapes `:8889`.
4. Do **not** restart the payroll app solely for collector recovery.
