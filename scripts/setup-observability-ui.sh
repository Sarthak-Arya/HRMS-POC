#!/usr/bin/env bash
# Provision Kibana data views + verify Grafana can see Elastic logs.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

KIBANA_URL="${KIBANA_URL:-http://127.0.0.1:5601}"
ES_URL="${ES_URL:-http://127.0.0.1:9200}"
GRAFANA_URL="${GRAFANA_URL:-http://127.0.0.1:3000}"
GRAFANA_USER="${GRAFANA_ADMIN_USER:-admin}"
GRAFANA_PASS="${GRAFANA_ADMIN_PASSWORD:-changeme}"

echo "==> Waiting for Elasticsearch..."
for i in $(seq 1 60); do
  if curl -fsS "$ES_URL/_cluster/health" >/dev/null 2>&1; then
    break
  fi
  sleep 2
  if [[ $i -eq 60 ]]; then
    echo "Elasticsearch not ready at $ES_URL" >&2
    exit 1
  fi
done

echo "==> Waiting for Kibana..."
for i in $(seq 1 90); do
  status="$(curl -fsS "$KIBANA_URL/api/status" 2>/dev/null | python3 -c 'import sys,json; print(json.load(sys.stdin)["status"]["overall"]["level"])' 2>/dev/null || true)"
  if [[ "$status" == "available" ]]; then
    break
  fi
  sleep 2
  if [[ $i -eq 90 ]]; then
    echo "Kibana not ready at $KIBANA_URL" >&2
    exit 1
  fi
done

create_data_view() {
  local id="$1"
  local title="$2"
  local name="$3"

  # Delete existing view with same id (ignore errors)
  curl -fsS -X DELETE "$KIBANA_URL/api/data_views/data_view/$id" \
    -H 'kbn-xsrf: true' >/dev/null 2>&1 || true

  curl -fsS -X POST "$KIBANA_URL/api/data_views/data_view" \
    -H 'kbn-xsrf: true' \
    -H 'Content-Type: application/json' \
    -d "$(python3 - <<PY
import json
print(json.dumps({
  "data_view": {
    "id": "$id",
    "title": "$title",
    "name": "$name",
    "timeFieldName": "@timestamp",
    "allowNoIndex": True,
  },
  "override": True,
}))
PY
)" >/dev/null

  echo "    created data view: $name ($title)"
}

echo "==> Creating Kibana data views..."
create_data_view "payroll-logs" "payroll-logs-*" "Payroll Logs"
create_data_view "payroll-security" "payroll-security-*" "Payroll Security"
create_data_view "payroll-all" "payroll-logs-*,payroll-security-*" "Payroll All Logs"

# Set default data view for Discover
curl -fsS -X POST "$KIBANA_URL/api/kibana/settings" \
  -H 'kbn-xsrf: true' \
  -H 'Content-Type: application/json' \
  -d '{"changes":{"defaultIndex":"payroll-all"}}' >/dev/null 2>&1 || true

echo "==> Checking Elasticsearch doc counts..."
curl -fsS "$ES_URL/_cat/indices/payroll-*?v&h=index,docs.count,store.size"

echo "==> Checking Grafana datasources..."
curl -fsS -u "$GRAFANA_USER:$GRAFANA_PASS" "$GRAFANA_URL/api/datasources" \
  | python3 -c 'import sys,json; ds=json.load(sys.stdin); print("\n".join("    %s (%s)" % (d["name"], d["type"]) for d in ds))'

echo
echo "Done. Open:"
echo "  Grafana Application Logs: $GRAFANA_URL/d/payroll-application-logs/application-logs"
echo "  Grafana dashboards:       $GRAFANA_URL/dashboards"
echo "  Kibana Discover:          $KIBANA_URL/app/discover#/?_a=(index:payroll-all)"
echo "  Login Grafana:            $GRAFANA_USER / $GRAFANA_PASS"
