# Runbook: Payroll Batch Failed

## Symptoms
- Alert `PayrollBatchFailed`
- `payroll_batch_failed_total` increased
- Payroll run stuck in PROCESSING or incomplete employee totals

## Impact
Payroll not generated for one or more employees; data integrity risk if partially paid.

## Investigation
1. Identify `payroll.run_id` and `job.batch_id` from logs (`event.name:queue.batch.failed`).
2. Inspect `failed_jobs` and `job_batches` tables.
3. Confirm attendance readiness and compensation prerequisites for failed employees (IDs only).
4. Check Redis/queue workers are running when `QUEUE_CONNECTION=redis`.

## Mitigation
1. Re-queue failed jobs after fixing root cause.
2. Do not unlock a locked run without finance approval.
3. Document employee IDs requiring recalculation.

## Escalation
SEV1 → Payroll eng primary; Platform if infrastructure.
