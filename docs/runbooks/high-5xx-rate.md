# Runbook: High 5xx Rate

## Symptoms
- Alert `PayrollAppHigh5xxRate`
- Grafana Service Health dashboard shows elevated 5xx

## Impact
Customer-facing failures; payroll and attendance workflows may be unavailable.

## Investigation
1. Open Grafana → Service Health → filter last 30m.
2. Correlate with Tempo traces using a failing `request.id`.
3. Check Elastic `payroll-logs-*` for `event.name:exception.reported`.
4. Verify `/readyz` and dependency health (MySQL, Redis/cache).

## Mitigation
1. Roll back recent deploy if error rate started after release.
2. Scale workers / fix DB saturation if queue + DB related.
3. Disable non-critical integrations (AI) if OpenRouter storms cascade.

## Escalation
SEV1 → Platform on-call, then Payroll eng if domain-specific.
