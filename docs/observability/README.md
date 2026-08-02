# Observability Quick Start

**Full guide (implemented logs + how to run/view):** [LOGGING.md](LOGGING.md)

1. Read the contract: [docs/observability.md](../observability.md)
2. **Interns — how to add logs:** [adding-logs-guide.md](adding-logs-guide.md)
3. **Interns — core HRMS modules & required logs:** [core-hrms-modules-logging.md](core-hrms-modules-logging.md)
4. Start the stack: `docker compose -f docker-compose.observability.yml up -d`
5. Wire app env (see `.env.example` observability section)
6. Provision UI indexes / data views: `bash scripts/setup-observability-ui.sh`
7. Hit `/healthz`, `/readyz`, and `/metrics`
8. Open logs UI:
   - **Grafana Application Logs:** http://localhost:3000/d/payroll-application-logs/application-logs (`admin` / `changeme`)
   - **Grafana folder:** Dashboards → **Payroll Observability**
   - **Kibana Discover:** http://localhost:5601/app/discover (data view **Payroll All Logs**)

Full operations notes: [stack-operations.md](stack-operations.md)
