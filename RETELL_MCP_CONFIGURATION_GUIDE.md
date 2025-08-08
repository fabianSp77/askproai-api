# Retell.ai MCP Configuration Guide

## 📍 Dashboard Location
Im Retell.ai Dashboard für Agent `agent_d7da9e5c49c4ccfff2526df5c1`:
**Dashboard → Agent → @MCP Section**

## 🔧 MCP Configuration Settings

### 1. Basic Configuration

**Name:**
```
HairSalonMCP
```

**URL (Main Endpoint):**
```
https://api.askproai.de/api/v2/hair-salon-mcp/mcp
```

**Timeout:**
```
30000
```

### 2. Headers

```json
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "X-Company-ID": "1"
}
```

### 3. Query Parameters

```json
{
  "company_id": "1"
}
```

### 4. MCP Methods Configuration

Im MCP-Bereich müssen Sie diese Methoden definieren:

#### Method 1: list_services
```json
{
  "method": "list_services",
  "endpoint": "/list_services",
  "http_method": "POST",
  "description": "Liste aller Salon-Services abrufen"
}
```

#### Method 2: check_availability
```json
{
  "method": "check_availability",
  "endpoint": "/check_availability",
  "http_method": "POST",
  "description": "Verfügbare Termine prüfen",
  "parameters": {
    "service_id": "integer",
    "date": "string",
    "days_ahead": "integer"
  }
}
```

#### Method 3: book_appointment
```json
{
  "method": "book_appointment",
  "endpoint": "/book_appointment",
  "http_method": "POST",
  "description": "Termin buchen",
  "parameters": {
    "customer_name": "string",
    "customer_phone": "string",
    "service_id": "integer",
    "staff_id": "integer",
    "datetime": "string"
  }
}
```

#### Method 4: schedule_callback
```json
{
  "method": "schedule_callback",
  "endpoint": "/schedule_callback",
  "http_method": "POST",
  "description": "Beratungsrückruf vereinbaren",
  "parameters": {
    "customer_name": "string",
    "customer_phone": "string",
    "service_name": "string"
  }
}
```

## 🎯 Complete URLs for Each Method

Die vollständigen URLs, die Retell.ai aufrufen wird:

1. **List Services:**
   ```
   POST https://api.askproai.de/api/v2/hair-salon-mcp/mcp/list_services
   ```

2. **Check Availability:**
   ```
   POST https://api.askproai.de/api/v2/hair-salon-mcp/mcp/check_availability
   ```

3. **Book Appointment:**
   ```
   POST https://api.askproai.de/api/v2/hair-salon-mcp/mcp/book_appointment
   ```

4. **Schedule Callback:**
   ```
   POST https://api.askproai.de/api/v2/hair-salon-mcp/mcp/schedule_callback
   ```

5. **Health Check:**
   ```
   GET https://api.askproai.de/api/v2/hair-salon-mcp/mcp/health
   ```

## ✅ Was wurde implementiert:

### Backend (Fertig):
- ✅ `HairSalonMCPServer.php` - Core MCP Server
- ✅ `RetellMCPBridgeController.php` - MCP Protocol Bridge
- ✅ Database Migration mit Hair Salon Feldern
- ✅ Routes in `api.php` registriert
- ✅ Webhook Handler erstellt

### MCP Bridge Features:
- ✅ JSON-RPC 2.0 Protocol Support
- ✅ Automatic company_id injection
- ✅ Error handling and logging
- ✅ Method routing
- ✅ Response transformation

## 🧪 Test URLs

### Test Health Endpoint:
```bash
curl -X GET "https://api.askproai.de/api/v2/hair-salon-mcp/mcp/health"
```

### Test List Services:
```bash
curl -X POST "https://api.askproai.de/api/v2/hair-salon-mcp/mcp/list_services" \
  -H "Content-Type: application/json" \
  -d '{"method":"list_services","params":{"company_id":1}}'
```

## 📝 Prompt Anpassung

Fügen Sie diese Zeilen zum Agent Prompt hinzu:

```
WICHTIG: Nutze die MCP-Funktionen für alle Salon-Operationen:
- list_services: Zeige verfügbare Dienstleistungen
- check_availability: Prüfe Verfügbarkeit für Termine
- book_appointment: Buche bestätigte Termine
- schedule_callback: Plane Beratungsrückrufe für Strähnen/Blondierung

Bei Services die Beratung benötigen (Strähnen, Blondierung, Balayage):
IMMER schedule_callback verwenden, NICHT book_appointment!
```

## 🚀 Next Steps

1. **Im Retell Dashboard:**
   - Öffnen Sie Agent `agent_d7da9e5c49c4ccfff2526df5c1`
   - Navigieren Sie zum @MCP Bereich
   - Tragen Sie die oben genannten Einstellungen ein
   - Speichern Sie die Konfiguration

2. **Testen:**
   - Rufen Sie +493033081738 an
   - Sagen Sie: "Ich möchte einen Termin für einen Haarschnitt"
   - Der Agent sollte die MCP-Funktionen nutzen

3. **Monitoring:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "MCP Bridge"
   ```

## ⚠️ Wichtige Hinweise

- Die MCP Integration funktioniert PARALLEL zu den bestehenden Cal.com Funktionen
- Der Agent kann beide Systeme nutzen
- MCP bietet mehr Flexibilität für spezielle Hair Salon Features
- Cal.com bleibt für Standard-Terminbuchungen verfügbar

---

**Status:** ✅ Backend komplett implementiert und bereit
**Nächster Schritt:** Konfiguration im Retell.ai Dashboard (@MCP Bereich)