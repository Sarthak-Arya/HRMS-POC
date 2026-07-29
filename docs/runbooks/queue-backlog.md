# Runbook: Queue Backlog

## Symptoms
- Alert `QueueDepthHigh` or `QueueJobAgeHigh`
- Horizon / Redis queue depth rising

## Investigation
1. Confirm worker processes are running.
2. Check Redis connectivity via `/readyz`.
3. Look for long-running payroll jobs and failed retries.

## Mitigation
1. Scale workers.
2. Pause non-critical queues.
3. Clear poison messages after capturing `job.class` and exception type.
