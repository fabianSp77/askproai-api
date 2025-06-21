# MCP (Model Context Protocol) Setup Guide für AskProAI

## 🚀 Vollständige Einrichtungsanleitung

### 1. Voraussetzungen ✅
- PHP 8.3 installiert
- Laravel Loop installiert
- Custom MCP Server implementiert

### 2. API Token für MCP erstellen

Erstellen Sie ein API Token für die MCP-Authentifizierung:

```bash
php artisan tinker
```

In Tinker:
```php
$user = User::where('email', 'admin@askproai.de')->first();
$token = $user->createToken('mcp-access', ['*'])->plainTextToken;
echo "Ihr MCP Token: " . $token;
```

### 3. Claude Code konfigurieren

#### Option A: Laravel Loop (Empfohlen)
```bash
claude mcp add laravel-loop-mcp php /var/www/api-gateway/artisan loop:mcp:start
```

#### Option B: HTTP MCP Server
Fügen Sie in Claude Code hinzu:
```json
{
  "mcpServers": {
    "askproai": {
      "url": "https://api.askproai.de/api/mcp",
      "headers": {
        "Authorization": "Bearer YOUR_MCP_TOKEN",
        "Accept": "application/json"
      }
    }
  }
}
```

### 4. Verfügbare MCP Funktionen

#### Database MCP
- **Schema abrufen**: `GET /api/mcp/database/schema`
- **Query ausführen**: `POST /api/mcp/database/query`
- **Suchen**: `POST /api/mcp/database/search`
- **Fehlerhafte Termine**: `GET /api/mcp/database/failed-appointments`
- **Anrufstatistiken**: `GET /api/mcp/database/call-stats`

#### Cal.com MCP
- **Event Types**: `GET /api/mcp/calcom/event-types?company_id=XXX`
- **Verfügbarkeit**: `POST /api/mcp/calcom/availability`
- **Buchungen**: `GET /api/mcp/calcom/bookings?company_id=XXX`
- **Synchronisieren**: `POST /api/mcp/calcom/sync`

#### Retell.ai MCP
- **Agent Info**: `GET /api/mcp/retell/agent/{companyId}`
- **Anrufstatistiken**: `GET /api/mcp/retell/call-stats`
- **Letzte Anrufe**: `GET /api/mcp/retell/recent-calls`
- **Telefonnummern**: `GET /api/mcp/retell/phone-numbers/{companyId}`

#### Sentry MCP
- **Fehler anzeigen**: `GET /api/mcp/sentry/issues`
- **Fehlerdetails**: `GET /api/mcp/sentry/issues/{issueId}`
- **Performance**: `GET /api/mcp/sentry/performance`

### 5. Beispiel-Anfragen an Claude

Mit den MCP Servern können Sie Claude fragen:

**System-Status:**
```
"Claude, wie ist der aktuelle System-Status?"
"Zeige mir alle fehlgeschlagenen Termine der letzten 24 Stunden"
"Wie viele Anrufe hatten wir heute?"
```

**Debugging:**
```
"Warum ist die Buchung für Kunde Schmidt fehlgeschlagen?"
"Analysiere die Performance der letzten Woche"
"Zeige mir alle Fehler im Booking Flow"
```

**Management:**
```
"Synchronisiere die Cal.com Event Types"
"Überprüfe die Retell.ai Verbindung"
"Liste alle konfigurierten Telefonnummern"
```

### 6. Laravel Loop Befehle

Mit Laravel Loop können Sie direkt Artisan Commands ausführen:

```
"Claude, führe php artisan queue:monitor aus"
"Zeige mir alle Routes mit php artisan route:list"
"Lösche den Cache"
"Führe die Migrations aus"
```

### 7. Sicherheit

#### API Token Management
- Tokens regelmäßig rotieren
- Nur notwendige Permissions vergeben
- Tokens niemals im Code committen

#### Rate Limiting
- API hat automatisches Rate Limiting
- Cache wird für häufige Anfragen genutzt

#### Logging
- Alle MCP Anfragen werden geloggt
- Monitoring über Sentry verfügbar

### 8. Troubleshooting

**"Unauthorized" Fehler:**
- Prüfen Sie das API Token
- Stellen Sie sicher, dass der User die richtigen Permissions hat

**"Company not found":**
- Überprüfen Sie die company_id
- Stellen Sie sicher, dass der User Zugriff auf die Company hat

**Keine Daten:**
- Cache leeren: `POST /api/mcp/{service}/cache/clear`
- Logs prüfen: `storage/logs/laravel.log`

### 9. Best Practices

1. **Spezifische Fragen stellen**
   - ✅ "Fehler der letzten 24 Stunden"
   - ❌ "Alle Fehler"

2. **Company Context angeben**
   - Immer company_id für mandanten-spezifische Daten

3. **Services kombinieren**
   - Database + Sentry für vollständige Fehleranalyse
   - Cal.com + Retell für Booking-Debugging

4. **Cache beachten**
   - Daten werden 5 Minuten gecacht
   - Bei Bedarf Cache explizit leeren

### 10. Monitoring & Logs

Überwachen Sie die MCP-Nutzung:
```sql
-- MCP Anfragen der letzten 24 Stunden
SELECT endpoint, COUNT(*) as requests, AVG(response_time_ms) as avg_ms
FROM api_call_logs 
WHERE endpoint LIKE '/api/mcp/%'
AND created_at > NOW() - INTERVAL 24 HOUR
GROUP BY endpoint
ORDER BY requests DESC;
```

### 11. Erweiterte Nutzung

**Custom Queries:**
```json
POST /api/mcp/database/query
{
  "sql": "SELECT * FROM appointments WHERE status = ? AND created_at > ?",
  "bindings": ["failed", "2024-01-01"]
}
```

**Batch-Operationen:**
Kombinieren Sie mehrere MCP-Calls für komplexe Analysen.

### 12. Nächste Schritte

1. ✅ API Token erstellen
2. ✅ Claude Code konfigurieren
3. ✅ Erste Test-Anfragen durchführen
4. ✅ Team schulen
5. ✅ Monitoring einrichten

---

## Quick Start Commands

```bash
# Laravel Loop starten
php artisan loop:mcp:start

# Test MCP Endpoint
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://api.askproai.de/api/mcp/info

# Cache leeren
curl -X POST -H "Authorization: Bearer YOUR_TOKEN" \
  https://api.askproai.de/api/mcp/database/cache/clear
```

## Support

Bei Fragen oder Problemen:
- Logs prüfen: `tail -f storage/logs/laravel.log`
- Sentry Dashboard checken
- Laravel Loop Docs: https://github.com/kirschbaum-development/laravel-loop