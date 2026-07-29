# Runbook: Auth Denial Spike

## Symptoms
- Alert `AuthDenialSpike`
- Rise in `access.permission.denied` / `access.company.denied`

## Investigation
1. Query Elastic security indices for denial events (IDs only).
2. Check for role/permission misconfiguration after a deploy.
3. Look for credential stuffing via `auth.login.failed` volume.

## Mitigation
1. Restore role matrix if accidental permission change.
2. Rate-limit / block abusive IPs at the edge.
3. Notify Security for forensic review.
