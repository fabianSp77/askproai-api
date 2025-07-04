# 📚 MkDocs URL Struktur - Korrekte Links

## ✅ Funktionierende URLs nach MkDocs Build:

### Hauptdokumentation:
- **Start**: `/mkdocs/`
- **CLAUDE.md**: `/mkdocs/CLAUDE/`
- **Quick Reference**: `/mkdocs/CLAUDE_QUICK_REFERENCE/`

### Schnellstart:
- **5-Minuten Onboarding**: `/mkdocs/5-MINUTEN_ONBOARDING_PLAYBOOK/`

### Betrieb:
- **Customer Success**: `/mkdocs/CUSTOMER_SUCCESS_RUNBOOK/`
- **Emergency Response**: `/mkdocs/EMERGENCY_RESPONSE_PLAYBOOK/`
- **Error Patterns**: `/mkdocs/ERROR_PATTERNS/`
- **Troubleshooting**: `/mkdocs/TROUBLESHOOTING_DECISION_TREE/`
- **Deployment Checklist**: `/mkdocs/DEPLOYMENT_CHECKLIST/`

### Monitoring:
- **KPI Dashboard**: `/mkdocs/KPI_DASHBOARD_TEMPLATE/`
- **Health Monitor**: `/mkdocs/INTEGRATION_HEALTH_MONITOR/`
- **Phone Flow**: `/mkdocs/PHONE_TO_APPOINTMENT_FLOW/`

### Architektur:
- **Übersicht**: `/mkdocs/architecture/overview/`
- **Services**: `/mkdocs/architecture/services/`
- **System Design**: `/mkdocs/architecture/system-design/`
- **MCP Architecture**: `/mkdocs/architecture/mcp-architecture/`
- **Database Schema**: `/mkdocs/architecture/database-schema/`
- **Security**: `/mkdocs/architecture/security/`
- **Performance**: `/mkdocs/architecture/performance/`

### API:
- **Webhooks**: `/mkdocs/api/webhooks/`
- **Authentication**: `/mkdocs/api/authentication/`
- **MCP Endpoints**: `/mkdocs/api/mcp-endpoints/`
- **Models**: `/mkdocs/api/models/`

### Features:
- **Appointment Booking**: `/mkdocs/features/appointment-booking/`
- **Multi-Tenancy**: `/mkdocs/features/multi-tenancy/`
- **Knowledge Base**: `/mkdocs/features/knowledge-base/`
- **Phone System**: `/mkdocs/features/phone-system/`

## ⚠️ URLs die NICHT funktionieren:
Diese sind in der Navigation referenziert, aber die Dateien fehlen:
- `/mkdocs/api/overview/`
- `/mkdocs/api/endpoints/`
- `/mkdocs/features/call-management/`
- `/mkdocs/features/customer-portal/`
- `/mkdocs/development/setup/`
- `/mkdocs/development/testing/`
- `/mkdocs/development/code-standards/`
- `/mkdocs/deployment/overview/`
- `/mkdocs/deployment/production/`
- `/mkdocs/deployment/environment-variables/`

## 📝 Wichtige Hinweise:

1. **URL Format**: MkDocs generiert für jede .md Datei ein Verzeichnis mit index.html
   - `CLAUDE.md` → `/mkdocs/CLAUDE/index.html` → URL: `/mkdocs/CLAUDE/`

2. **Unterverzeichnisse**: Bleiben erhalten
   - `architecture/services.md` → `/mkdocs/architecture/services/`

3. **Case Sensitive**: URLs behalten Groß-/Kleinschreibung bei
   - `CLAUDE.md` → `/mkdocs/CLAUDE/` (nicht `/mkdocs/claude/`)

## 🔧 So werden URLs generiert:

```yaml
# In mkdocs.yml:
nav:
  - Titel: dateiname.md

# Generiert:
/mkdocs/dateiname/
```