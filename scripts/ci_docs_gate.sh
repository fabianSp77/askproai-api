#!/usr/bin/env bash
set -euo pipefail
fail(){ echo "FAIL: $*"; exit 1; }
ok(){ echo "OK: $*"; }

# API-Doku vorhanden
[ -f docs/API.md ] || [ -d docs/api ] || fail "API docs missing (docs/API.md or docs/api/*)"
# Auth/Headers
grep -Rqi 'Authorization:\s*Bearer' docs || fail "Bearer examples missing in docs"
grep -Rqi 'cal-api-version' docs || fail "cal-api-version header missing in docs"
# Restore-Doku (gunzip -c ... | mysql ...)
grep -Rqi 'gunzip -c .*\.sql\.gz | mysql' docs || fail "Restore command with gunzip -c missing"
# DSGVO TOMs
grep -Rqi 'TOM' docs/compliance 2>/dev/null || fail "DSGVO TOM missing in docs/compliance"
# KPI SQL-Beispiele
grep -Rqi '\bSELECT\b' docs/analytics 2>/dev/null || fail "KPI SQL examples missing in docs/analytics"

ok "docs-gates passed"
