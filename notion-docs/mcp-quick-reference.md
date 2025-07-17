## MCP Quick Reference

### 🚀 Sofort-Befehle

```bash
# Termine
mcp book                    # Neuer Termin
mcp cancel <id>            # Stornieren
mcp reschedule <id>        # Verschieben

# Kunden
mcp find <phone/name>      # Kunde suchen
mcp history <id>           # Historie

# Anrufe
mcp calls                  # Import letzte 50
mcp call <id>             # Details

# System
mcp health                # Status Check
mcp sync                  # Alle Sync
mcp report               # Tagesreport
```

### 📊 Dashboard URLs

| Funktion | URL |
|----------|-----|
| MCP Monitor | `/admin/mcp-dashboard` |
| Server Status | `/admin/mcp-servers` |
| Control Center | `/admin/mcp-control-center` |
| Metrics | `/admin/mcp-metrics` |
| Queue Monitor | `/admin/horizon` |

### 🔑 Wichtige Konfigurationsdateien

```
config/
├── mcp.php              # Haupt-Config
├── mcp-servers.php      # Server Registry
├── mcp-shortcuts.php    # Shortcuts
├── mcp-external.php     # Externe Server
└── mcp-security.php     # Security Settings
```

### 💡 Umgebungsvariablen

```env
# MCP Core
MCP_ENABLED=true
MCP_DEBUG=false
MCP_LOG_LEVEL=info

# External Servers
MCP_SEQUENTIAL_THINKING_ENABLED=true
MCP_NOTION_ENABLED=true
MCP_GITHUB_ENABLED=true

# API Keys
NOTION_API_KEY=xxx
GITHUB_TOKEN=xxx
APIDOG_API_KEY=xxx

# Performance
MCP_CONCURRENT_CALLS=200
MCP_QUEUE_WORKERS=50
```

### 🛡️ Security Headers

```php
// Für MCP API Calls
$headers = [
    'X-Tenant-ID' => $tenantId,
    'X-MCP-Version' => '1.0',
    'Authorization' => 'Bearer ' . $token,
];
```

### 📈 Performance Limits

| Metric | Limit | Configurable |
|--------|-------|--------------|
| Requests/Min | 1000 | ✅ |
| Concurrent Ops | 50 | ✅ |
| Timeout | 30s | ✅ |
| Max Payload | 10MB | ✅ |
| Cache TTL | 300s | ✅ |

### 🚨 Notfall-Befehle

```bash
# System Reset
php artisan mcp:emergency-reset

# Kill All Jobs
php artisan queue:flush

# Circuit Breaker Reset
php artisan circuit-breaker:reset --all

# Cache Clear
php artisan cache:clear
redis-cli FLUSHDB

# Restart Services
supervisorctl restart all
```

### 📱 Mobile/API Endpoints

```
POST /api/v2/mcp/execute
{
    "service": "appointment",
    "tool": "create_appointment",
    "arguments": {...}
}

GET /api/v2/mcp/health
GET /api/v2/mcp/servers
GET /api/v2/mcp/metrics
```

### 🔍 Debug Mode

```php
// Enable detailed logging
\Log::pushProcessor(function ($record) {
    $record['extra']['mcp_trace'] = true;
    return $record;
});

// Or via tinker
app('mcp.debug')->enable();
```

### 📞 Support Kontakte

- **Dev Team**: dev@askproai.de
- **On-Call**: +49 xxx xxx xxxx
- **Slack**: #mcp-support
- **Wiki**: wiki.askproai.de/mcp

### 🏃 Cheat Sheet

```
book     → b    reschedule → rs
cancel   → c    sync       → s
find     → f    health     → h
import   → i    report     → r
```

### 🎯 Häufigste Use Cases

#### Termin für morgen buchen
```bash
php artisan mcp:discover "book appointment tomorrow 14:00"
```

#### Kunde mit Telefonnummer finden
```bash
php artisan mcp find "+49 123 456789"
```

#### Tagesstatistik abrufen
```bash
php artisan mcp daily-report --format=json
```

#### Alle Integrationen prüfen
```bash
php artisan mcp check-integrations
```

### 🔧 Tool Discovery

```bash
# Alle Tools eines Servers anzeigen
php artisan mcp:tools appointment

# Tool-Schema anzeigen
php artisan mcp:schema appointment create_appointment

# Beispiel für Tool generieren
php artisan mcp:example appointment create_appointment
```

### 📊 Quick Stats Commands

```bash
# Server-Statistiken
php artisan mcp:stats

# Error Summary
php artisan mcp:errors --last-hour

# Performance Report
php artisan mcp:performance --service=calcom
```

### 🚦 Status Indicators

- 🟢 **Healthy**: Alles funktioniert
- 🟡 **Degraded**: Teilweise Probleme
- 🔴 **Down**: Service nicht verfügbar
- ⚪ **Unknown**: Status unbekannt

### 🗂️ Log Locations

```
storage/logs/
├── mcp.log              # General logs
├── mcp-errors.log       # Errors only
├── mcp-performance.log  # Performance metrics
├── mcp-external.log     # External servers
└── mcp-security.log     # Security events
```

### ⚡ Performance Tips

1. **Use shortcuts** statt volle Commands
2. **Cache results** bei wiederholten Queries
3. **Batch operations** für mehrere Items
4. **Async processing** für non-critical tasks
5. **Monitor rate limits** um Throttling zu vermeiden

---
*Letzte Aktualisierung: Januar 2025*