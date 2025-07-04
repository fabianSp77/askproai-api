#!/bin/bash
# Synchronisiert Dokumentationsdateien vom Root nach docs_build für MkDocs

echo "📚 Synchronisiere Dokumentation für MkDocs..."

# Liste der zu synchronisierenden Dateien
DOCS=(
    "index.md"
    "5-MINUTEN_ONBOARDING_PLAYBOOK.md"
    "CLAUDE_QUICK_REFERENCE.md"
    "CUSTOMER_SUCCESS_RUNBOOK.md"
    "EMERGENCY_RESPONSE_PLAYBOOK.md"
    "ERROR_PATTERNS.md"
    "CLAUDE.md"
    "DEPLOYMENT_CHECKLIST.md"
    "TROUBLESHOOTING_DECISION_TREE.md"
    "KPI_DASHBOARD_TEMPLATE.md"
    "INTEGRATION_HEALTH_MONITOR.md"
    "PHONE_TO_APPOINTMENT_FLOW.md"
)

# Erstelle docs_build falls nicht vorhanden
mkdir -p docs_build

# Synchronisiere jede Datei
for doc in "${DOCS[@]}"; do
    if [ -f "$doc" ]; then
        echo "✅ Kopiere $doc"
        cp "$doc" docs_build/
    else
        echo "❌ Warnung: $doc nicht gefunden"
    fi
done

echo ""
echo "🔨 Baue MkDocs..."
mkdocs build

echo ""
echo "✅ Dokumentation synchronisiert und gebaut!"
echo "🌐 Verfügbar unter: https://api.askproai.de/mkdocs/"