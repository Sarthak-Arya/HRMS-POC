# Alert Ownership & Severity

## Severity policy

| Severity | Meaning | Response |
|----------|---------|----------|
| SEV1 | Customer-impacting outage or payroll data integrity risk | Page on-call immediately |
| SEV2 | Degraded service; payroll/report workflows impacted | Page within 15 minutes |
| SEV3 | Elevated errors / business anomaly | Ticket / Slack warning |
| SEV4 | Informational drift | Dashboard only |

## Alert catalogue

| Alert | Severity | Owner | Condition (summary) |
|-------|----------|-------|---------------------|
| `PayrollAppHigh5xxRate` | SEV1 | Platform | 5xx rate > 5% for 5m |
| `PayrollAppHighLatencyP95` | SEV2 | Platform | HTTP p95 > 2s for 10m |
| `PayrollAppReadinessFailed` | SEV1 | Platform | `/readyz` failing |
| `ObservabilityCollectorDown` | SEV2 | Platform | OTel collector scrape missing 5m |
| `LogShipperSilent` | SEV2 | Platform | No Filebeat docs for 15m |
| `ElasticsearchDiskHigh` | SEV2 | Platform | Disk > 85% |
| `QueueDepthHigh` | SEV2 | Payroll eng | Redis/queue depth > threshold 10m |
| `QueueJobAgeHigh` | SEV2 | Payroll eng | Oldest job age > 15m |
| `PayrollBatchFailed` | SEV1 | Payroll eng | Batch failed_jobs > 0 |
| `PayrollBatchStalled` | SEV1 | Payroll eng | Run PROCESSING > 30m without progress |
| `ReportExportFailures` | SEV3 | Payroll eng | Export failure rate spike |
| `OpenRouterErrorSpike` | SEV3 | Payroll eng | 429/5xx rate elevated |
| `AuthDenialSpike` | SEV2 | Security | Permission/company denials spike |
| `ExportDownloadAnomaly` | SEV3 | Security | Unusual export volume |

Paging is enabled only for production SEV1/SEV2 after staging tune-in. Staging uses warning notifications only.

## Contact points

Configured in Grafana / Alertmanager via environment:

- `ALERT_EMAIL_TO` — primary on-call distribution
- `ALERT_WEBHOOK_URL` — optional ChatOps webhook
- `ALERT_SLACK_WEBHOOK_URL` — staging/warning channel

Secrets must never be committed to Git.
