#!/usr/bin/env bash
# Smoke-test the running stack-parser service.
#   ./etests-service/smoke-test.sh [BASE_URL] [EXPRESSION]
# Defaults: BASE_URL=http://localhost:8080, EXPRESSION="1/sqrt(2)*(ket(00)+ket(01))"
set -euo pipefail

BASE_URL="${1:-http://localhost:8080}"
EXPRESSION="${2:-1/sqrt(2)*(ket(00)+ket(01))}"
HERE="$(cd "$(dirname "$0")" && pwd)"
XML_FILE="$HERE/../api/public/question.xml"

echo "== /health =="
curl -fsS "$BASE_URL/health"; echo

echo "== /parse (expression: $EXPRESSION) =="
# Build the JSON body with jq so expression + XML are safely escaped.
jq -n --arg expr "$EXPRESSION" --rawfile xml "$XML_FILE" \
    '{expression: $expr, questionXml: $xml}' \
  | curl -fsS -X POST "$BASE_URL/parse" \
      -H 'Content-Type: application/json' \
      --data-binary @- \
  | jq .
