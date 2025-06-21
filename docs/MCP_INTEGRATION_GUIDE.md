# MCP (Model Context Protocol) Integration Guide für AskProAI

## Übersicht

Diese Anleitung beschreibt die Nutzung der MCP Server Integration in AskProAI. MCP ermöglicht es AI-Assistenten wie Claude, direkt mit Ihrer Anwendung zu interagieren.

## Verfügbare MCP Server

### 1. Database MCP Server
Ermöglicht Read-Only Zugriff auf die Datenbank für Debugging und Analyse.

**Endpoints:**
- `GET /api/mcp/database/schema` - Datenbankschema abrufen
- `POST /api/mcp/database/query` - SQL-Abfragen ausführen (nur SELECT)
- `POST /api/mcp/database/search` - Suche in Tabellen
- `GET /api/mcp/database/failed-appointments` - Fehlgeschlagene Termine
- `GET /api/mcp/database/call-stats` - Anrufstatistiken
- `GET /api/mcp/database/tenant-stats` - Mandanten-Statistiken

**Beispiel-Anfragen an Claude:**
- "Zeige mir alle fehlgeschlagenen Termine der letzten 24 Stunden"
- "Wie viele Anrufe hatten wir diese Woche?"
- "Suche nach Kunden mit der Telefonnummer 0170..."

### 2. Cal.com MCP Server
Integration mit Cal.com für Kalender-Management.

**Endpoints:**
- `GET /api/mcp/calcom/event-types?company_id=XXX` - Event Types abrufen
- `POST /api/mcp/calcom/availability` - Verfügbarkeit prüfen
- `GET /api/mcp/calcom/bookings?company_id=XXX` - Buchungen abrufen
- `GET /api/mcp/calcom/assignments/{companyId}` - Event Type Zuordnungen
- `POST /api/mcp/calcom/sync` - Event Types synchronisieren
- `GET /api/mcp/calcom/test/{companyId}` - Verbindung testen

**Beispiel-Anfragen an Claude:**
- "Synchronisiere die Event Types für Firma XYZ"
- "Zeige mir die verfügbaren Termine für morgen"
- "Welche Mitarbeiter sind welchen Event Types zugeordnet?"

### 3. Retell.ai MCP Server
Integration mit Retell.ai für Telefon-AI Management.

**Endpoints:**
- `GET /api/mcp/retell/agent/{companyId}` - Agent-Informationen
- `GET /api/mcp/retell/agents/{companyId}` - Alle Agents auflisten
- `GET /api/mcp/retell/call-stats?company_id=XXX` - Anrufstatistiken
- `GET /api/mcp/retell/recent-calls?company_id=XXX` - Letzte Anrufe
- `GET /api/mcp/retell/call/{callId}` - Anrufdetails
- `POST /api/mcp/retell/search-calls` - Anrufe suchen
- `GET /api/mcp/retell/phone-numbers/{companyId}` - Telefonnummern
- `GET /api/mcp/retell/test/{companyId}` - Verbindung testen

**Beispiel-Anfragen an Claude:**
- "Wie viele Anrufe hatten wir heute?"
- "Zeige mir Details zum letzten Anruf"
- "Welche Telefonnummern sind konfiguriert?"

### 4. Sentry MCP Server (bereits implementiert)
Error-Tracking und Performance-Monitoring.

**Endpoints:**
- `GET /api/mcp/sentry/issues` - Aktuelle Fehler
- `GET /api/mcp/sentry/issues/{issueId}` - Fehlerdetails
- `POST /api/mcp/sentry/issues/search` - Fehler suchen
- `GET /api/mcp/sentry/performance` - Performance-Daten

## Authentifizierung

Alle MCP Endpoints sind durch Sanctum Authentication geschützt. Sie benötigen ein gültiges API Token.

## Nutzung mit Claude

### In Claude Desktop
1. Öffnen Sie Claude Desktop
2. Navigieren Sie zu den MCP Server Einstellungen
3. Fügen Sie die AskProAI MCP Server URL hinzu: `https://api.askproai.de/api/mcp`
4. Konfigurieren Sie Ihr API Token

### Beispiel-Konversationen

**Fehleranalyse:**
```
Claude: "Was sind die häufigsten Fehler beim Booking Flow?"
→ MCP nutzt Database + Sentry Server für Analyse
```

**Status-Check:**
```
Claude: "Wie ist der aktuelle System-Status?"
→ MCP prüft Cal.com + Retell.ai Verbindungen
```

**Debugging:**
```
Claude: "Warum ist der Termin für Kunde Schmidt fehlgeschlagen?"
→ MCP durchsucht Calls, Appointments und Error Logs
```

## Sicherheit

### Read-Only Zugriff
- Database MCP erlaubt nur SELECT Queries
- Keine Datenänderungen möglich
- Sensitive Spalten (Passwörter etc.) werden gefiltert

### Tenant Isolation
- Alle Abfragen beachten die Multi-Tenancy
- Company ID muss bei relevanten Anfragen angegeben werden

### Rate Limiting
- API Endpoints haben Rate Limits
- Cache wird für häufige Anfragen genutzt

## Erweiterte Nutzung

### Custom Queries
Sie können eigene SQL-Abfragen ausführen:
```json
POST /api/mcp/database/query
{
    "sql": "SELECT COUNT(*) as total FROM appointments WHERE status = ?",
    "bindings": ["completed"]
}
```

### Cache Management
Cache für jeden Service kann geleert werden:
```
POST /api/mcp/{service}/cache/clear
```
Verfügbare Services: database, calcom, retell, sentry

## Troubleshooting

### "Company not found"
- Stellen Sie sicher, dass die company_id korrekt ist
- Prüfen Sie, ob der API Key Zugriff auf diese Company hat

### "Only SELECT queries are allowed"
- Database MCP erlaubt nur lesende Zugriffe
- Für Änderungen nutzen Sie die reguläre API

### "Cal.com not configured"
- Prüfen Sie, ob die Company einen Cal.com API Key hat
- Testen Sie die Verbindung mit `/api/mcp/calcom/test/{companyId}`

## Best Practices

1. **Nutzen Sie spezifische Fragen**: "Zeige Fehler der letzten 24 Stunden" statt "Zeige alle Fehler"
2. **Kombinieren Sie MCP Server**: Nutzen Sie Database + Sentry für vollständige Fehleranalyse
3. **Cache beachten**: Daten werden 5 Minuten gecacht - bei Bedarf Cache leeren
4. **Company Context**: Geben Sie immer die company_id an für mandanten-spezifische Daten

## Zukünftige Erweiterungen

- **Redis MCP**: Cache und Queue Monitoring
- **Horizon MCP**: Queue Job Details
- **Stripe MCP**: Billing und Payment Informationen
- **Custom Business Logic MCP**: Spezifische Geschäftslogik-Operationen