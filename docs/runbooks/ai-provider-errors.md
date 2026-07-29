# Runbook: AI Provider Errors

## Symptoms
- Alert `OpenRouterErrorSpike`
- AI assistant degraded; mutating tools may fail

## Investigation
1. Check `ai.request.completed` outcomes and status classes.
2. Confirm API key validity without logging the secret.
3. Verify network egress to OpenRouter.

## Mitigation
1. Increase retry backoff temporarily.
2. Disable AI assistant feature flag if available.
3. Communicate degraded UX to operators.
