#!/usr/bin/env bash
set -euo pipefail

# E2E ID Consistency Verifier
# Prüft 1:1-Mappings: Phone↔Agent↔Branch, Service↔Event, Staff↔CalcomUser

echo "═══════════════════════════════════════════════════"
echo "GATE 0: ID-Konsistenz-Prüfung für Friseur 1"
echo "═══════════════════════════════════════════════════"
echo ""

COMPANY_ID="${1:-5}"
EXIT_CODE=0

# Funktion für Fehlerausgabe
fail() {
  echo "❌ $1"
  EXIT_CODE=1
}

success() {
  echo "✅ $1"
}

warning() {
  echo "⚠️  $1"
}

# 1. Phone ↔ Agent ↔ Branch
echo "[1/4] Prüfe Phone ↔ Agent ↔ Branch Mapping..."
PHONE_COUNT=$(psql -Atc "SELECT COUNT(*) FROM phone_numbers WHERE company_id = $COMPANY_ID")
if [ "$PHONE_COUNT" -eq 0 ]; then
  fail "Keine Telefonnummern für Company $COMPANY_ID gefunden"
else
  success "$PHONE_COUNT Telefonnummer(n) gefunden"

  # Prüfe auf NULL-Werte
  NULL_AGENTS=$(psql -Atc "SELECT COUNT(*) FROM phone_numbers WHERE company_id = $COMPANY_ID AND retell_agent_id IS NULL")
  NULL_BRANCHES=$(psql -Atc "SELECT COUNT(*) FROM phone_numbers WHERE company_id = $COMPANY_ID AND branch_id IS NULL")

  if [ "$NULL_AGENTS" -gt 0 ]; then
    fail "$NULL_AGENTS Telefonnummer(n) ohne Agent ID"
  else
    success "Alle Telefonnummern haben Agent ID"
  fi

  if [ "$NULL_BRANCHES" -gt 0 ]; then
    fail "$NULL_BRANCHES Telefonnummer(n) ohne Branch ID"
  else
    success "Alle Telefonnummern haben Branch ID"
  fi

  # Ausgabe der Mappings
  psql -c "SELECT phone_number, substring(retell_agent_id from 32 for 6) as agent, b.name as branch
           FROM phone_numbers pn
           LEFT JOIN branches b ON b.id = pn.branch_id
           WHERE pn.company_id = $COMPANY_ID" 2>/dev/null || true
fi

echo ""

# 2. Service ↔ Cal.com Event ID
echo "[2/4] Prüfe Service ↔ Cal.com Event ID Mapping..."
SERVICE_COUNT=$(psql -Atc "SELECT COUNT(*) FROM services WHERE company_id = $COMPANY_ID")
if [ "$SERVICE_COUNT" -eq 0 ]; then
  fail "Keine Services für Company $COMPANY_ID gefunden"
else
  success "$SERVICE_COUNT Services gefunden"

  # Extrahiere Event IDs aus DB
  psql -Atc "SELECT s.name, s.settings->>'calcom_event_type_id'
             FROM services s
             WHERE s.company_id = $COMPANY_ID
             ORDER BY s.name" > /tmp/services_events_$COMPANY_ID.txt

  # Prüfe auf NULL Event IDs
  NULL_EVENTS=$(psql -Atc "SELECT COUNT(*) FROM services
                            WHERE company_id = $COMPANY_ID
                            AND (settings->>'calcom_event_type_id' IS NULL
                                 OR settings->>'calcom_event_type_id' = '')")

  if [ "$NULL_EVENTS" -gt 0 ]; then
    warning "$NULL_EVENTS Services ohne Cal.com Event ID"
  else
    success "Alle Services haben Event ID"
  fi

  # Prüfe gegen Cal.com API (wenn CALCOM_API_KEY gesetzt)
  if [ -n "${CALCOM_API_KEY:-}" ]; then
    echo "  → Verifiziere gegen Cal.com API..."

    # Hole Event Types von Cal.com
    curl -s "https://api.cal.com/v2/event-types?teamId=34209" \
      -H "Authorization: Bearer $CALCOM_API_KEY" 2>/dev/null \
      | jq -r '.data[]?.id // empty' 2>/dev/null \
      | sort > /tmp/calcom_events.txt || true

    if [ -f /tmp/calcom_events.txt ] && [ -s /tmp/calcom_events.txt ]; then
      # Extrahiere nur Event IDs aus DB
      awk -F'|' '{print $2}' /tmp/services_events_$COMPANY_ID.txt | sed '/^$/d' | sort > /tmp/db_events.txt

      # Finde Events in DB aber nicht in Cal.com
      MISSING=$(comm -23 /tmp/db_events.txt /tmp/calcom_events.txt | wc -l)

      if [ "$MISSING" -gt 0 ]; then
        fail "$MISSING Event IDs in DB, aber nicht in Cal.com:"
        comm -23 /tmp/db_events.txt /tmp/calcom_events.txt | head -5
      else
        success "Alle Event IDs existieren in Cal.com"
      fi
    else
      warning "Cal.com API nicht erreichbar oder keine Event Types gefunden"
    fi
  else
    warning "CALCOM_API_KEY nicht gesetzt - überspringe Cal.com API-Check"
  fi
fi

echo ""

# 3. Staff ↔ Cal.com User ID
echo "[3/4] Prüfe Staff ↔ Cal.com User ID Mapping..."
STAFF_COUNT=$(psql -Atc "SELECT COUNT(*) FROM staff WHERE company_id = $COMPANY_ID")
if [ "$STAFF_COUNT" -eq 0 ]; then
  fail "Keine Staff-Einträge für Company $COMPANY_ID gefunden"
else
  success "$STAFF_COUNT Staff-Mitglieder gefunden"

  # Prüfe auf NULL Cal.com User IDs
  NULL_USERS=$(psql -Atc "SELECT COUNT(*) FROM staff
                           WHERE company_id = $COMPANY_ID
                           AND calcom_user_id IS NULL")

  if [ "$NULL_USERS" -gt 0 ]; then
    fail "$NULL_USERS Staff ohne Cal.com User ID (BLOCKER!)"
    echo ""
    echo "   Staff ohne Cal.com User ID:"
    psql -c "SELECT name, email FROM staff
             WHERE company_id = $COMPANY_ID AND calcom_user_id IS NULL"
    echo ""
    echo "   FIX: Führe aus um zu mappen:"
    echo "   psql -f scripts/e2e/fix-staff-calcom-ids.sql"
  else
    success "Alle Staff haben Cal.com User ID"
  fi
fi

echo ""

# 4. Branch ↔ Cal.com Team ID
echo "[4/4] Prüfe Branch ↔ Cal.com Team ID Mapping..."
BRANCH_COUNT=$(psql -Atc "SELECT COUNT(*) FROM branches WHERE company_id = $COMPANY_ID")
if [ "$BRANCH_COUNT" -eq 0 ]; then
  fail "Keine Branches für Company $COMPANY_ID gefunden"
else
  success "$BRANCH_COUNT Branch(es) gefunden"

  # Prüfe auf NULL Team IDs
  NULL_TEAMS=$(psql -Atc "SELECT COUNT(*) FROM branches
                           WHERE company_id = $COMPANY_ID
                           AND (settings->>'calcom_team_id' IS NULL
                                OR settings->>'calcom_team_id' = '')")

  if [ "$NULL_TEAMS" -gt 0 ]; then
    warning "$NULL_TEAMS Branch(es) ohne Cal.com Team ID"
  else
    success "Alle Branches haben Team ID"
  fi

  # Ausgabe der Mappings
  psql -c "SELECT name, settings->>'calcom_team_id' as team_id
           FROM branches
           WHERE company_id = $COMPANY_ID" 2>/dev/null || true
fi

echo ""
echo "═══════════════════════════════════════════════════"
if [ $EXIT_CODE -eq 0 ]; then
  echo "✅ GATE 0 BESTANDEN: Alle ID-Mappings konsistent"
else
  echo "❌ GATE 0 FAILED: ID-Mappings inkonsistent"
  echo ""
  echo "Behebe die Fehler bevor du fortfährst!"
fi
echo "═══════════════════════════════════════════════════"

exit $EXIT_CODE
