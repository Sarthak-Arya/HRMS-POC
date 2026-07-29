# Failure Drills Checklist

Run quarterly in staging. Confirm the app keeps serving payroll traffic and telemetry never blocks requests.

| Drill | Steps | Expected |
|-------|-------|----------|
| DB unavailable | Stop MySQL; hit `/readyz` and a protected page | Readiness 503; liveness still OK; errors classified |
| Redis/worker stopped | Stop Redis/workers with `QUEUE_CONNECTION=redis` | Queue alerts fire; sync fallback documented |
| Elasticsearch down | Stop ES/Filebeat | App unaffected; LogShipperSilent alert |
| Rejected AI call | Force 429/5xx from OpenRouter mock | `ai.request.completed` failure; no raw body logged |
| Payroll batch failure | Inject failing employee job | `PayrollBatchFailed` alert; safe runbook link |
| Unauthorized export | User without permission downloads payslip | `access.permission.denied` or 403; no salary in logs |

Record findings in the SRE wiki and tune alert thresholds afterward.
