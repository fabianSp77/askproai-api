# 🔍 RETELL MCP TROUBLESHOOTING GUIDE

## ✅ Was funktioniert:

### MCP Endpoint ist voll funktionsfähig:
```bash
# Main endpoint
https://api.askproai.de/api/v2/hair-salon-mcp/mcp

# Method-specific endpoints (auch funktionsfähig)
https://api.askproai.de/api/v2/hair-salon-mcp/mcp/list_services
https://api.askproai.de/api/v2/hair-salon-mcp/mcp/check_availability
https://api.askproai.de/api/v2/hair-salon-mcp/mcp/book_appointment
```

### Unterstützte Request-Formate:
1. **JSON-RPC 2.0** (Standard MCP)
```json
{
  "jsonrpc": "2.0",
  "id": "123",
  "method": "list_services",
  "params": {"company_id": 1}
}
```

2. **Tool Format** (Alternative)
```json
{
  "tool": "list_services",
  "arguments": {"company_id": 1}
}
```

### Verfügbare Services:
- 15 Services aktiv, inkl.:
  - Herrenhaarschnitt (35€)
  - Damenhaarschnitt (55€)
  - Färbung komplett (85€)
  - Foliensträhnen (95€)
  - Balayage (150€)

## ❌ Problem: Retell sendet keine Requests

Während deiner Testanrufe:
- **KEINE** eingehenden Requests von Retell
- **KEINE** Webhooks empfangen
- **KEINE** MCP Tool Calls
- System ist bereit aber wird nicht kontaktiert

## 🔧 Mögliche Konfigurationsprobleme in Retell:

### 1. MCP URL Format
Retell könnte erwarten:
- ❌ OHNE `/api/` prefix? → `https://api.askproai.de/v2/hair-salon-mcp/mcp`
- ❌ MIT trailing slash? → `https://api.askproai.de/api/v2/hair-salon-mcp/mcp/`
- ❌ HTTP statt HTTPS? → `http://api.askproai.de/api/v2/hair-salon-mcp/mcp`

### 2. Authentication
Unser Endpoint benötigt KEINE Authentication, aber vielleicht:
- Retell sendet Auth Header die wir ablehnen?
- Retell erwartet einen API Key Response?

### 3. Tool Discovery
```bash
# Tool Discovery funktioniert via initialize:
curl -X POST https://api.askproai.de/api/v2/hair-salon-mcp/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":"1","method":"initialize","params":{}}'
```

## 📋 Checkliste für Retell Dashboard:

### Agent Configuration:
- [ ] MCP enabled: `true`
- [ ] MCP URL: `https://api.askproai.de/api/v2/hair-salon-mcp/mcp`
- [ ] MCP version: `2024-11-05`
- [ ] Default params: `{"company_id": 1}`

### Tools/Functions:
- [ ] Auto-discovery enabled?
- [ ] Oder manuell konfiguriert:
  - `list_services`
  - `check_availability`
  - `book_appointment`
  - `schedule_callback`

### Phone Number:
- [ ] +49 30 33081738 → Linked to correct agent?
- [ ] Agent active?
- [ ] Test mode disabled?

### Webhooks (falls benötigt):
- [ ] Webhook URL: `https://api.askproai.de/api/v2/hair-salon-mcp/retell-webhook`

## 🧪 Test-Befehle:

### 1. Test ob MCP erreichbar ist:
```bash
curl -I https://api.askproai.de/api/v2/hair-salon-mcp/mcp/health
```

### 2. Test Services abrufen:
```bash
curl -X POST https://api.askproai.de/api/v2/hair-salon-mcp/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":"test","method":"list_services","params":{"company_id":1}}'
```

### 3. Monitor starten:
```bash
php /var/www/api-gateway/monitor-retell-calls.php
```

### 4. Simulation laufen lassen:
```bash
php /var/www/api-gateway/simulate-retell-call.php
```

## 🎯 Vermutung:

Da die MCP URL und Nummer laut dir konfiguriert sind, könnte es sein dass:

1. **Retell nutzt einen anderen URL-Path** - Prüfe ob in Retell vielleicht ein anderer Path eingetragen ist
2. **Retell Agent ist nicht aktiv** - Ist der Agent enabled?
3. **Retell kann HTTPS nicht erreichen** - SSL Zertifikat Problem?
4. **Retell sendet an andere IP** - DNS Problem?

## 📞 Debug-Strategie:

1. **Prüfe Retell Call Logs** - Gibt es dort Fehler?
2. **Prüfe Retell Agent Logs** - Werden Tools aufgerufen?
3. **Teste mit HTTP statt HTTPS** - Falls SSL Problem
4. **Kontaktiere Retell Support** - Falls alles richtig konfiguriert

---
*Das System ist 100% bereit und funktionsfähig. Das Problem liegt in der Verbindung zwischen Retell und unserem System.*