# Observability Quick Start

1. Read the contract: [docs/observability.md](../observability.md)
2. **Interns — how to add logs:** [adding-logs-guide.md](adding-logs-guide.md)
3. **Interns — core HRMS modules & required logs:** [core-hrms-modules-logging.md](core-hrms-modules-logging.md)
4. Start the stack: `docker compose -f docker-compose.observability.yml up -d`
5. Wire app env (see `.env.example` observability section)
6. Hit `/healthz`, `/readyz`, and `/metrics`
7. Open Grafana at http://localhost:3000

Full operations notes: [stack-operations.md](stack-operations.md)
