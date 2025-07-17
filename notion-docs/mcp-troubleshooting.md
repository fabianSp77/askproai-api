## MCP Troubleshooting

### 🔍 Häufige Probleme

#### 1. "MCP Server not found"
```bash
# Prüfen ob Server registriert ist
php artisan mcp:list

# Server manuell registrieren
php artisan mcp:register MyCustomMCPServer

# Cache leeren
php artisan cache:clear
```

#### 2. "Rate limit exceeded"
- **Ursache**: Zu viele Requests in kurzer Zeit
- **Lösung**: 
  - Warte die angegebene Zeit
  - Erhöhe Rate Limits in `config/mcp.php`
  - Nutze Batch-Operations

#### 3. "Circuit breaker open"
- **Ursache**: Service hat zu viele Fehler produziert
- **Lösung**:
  ```bash
  # Status prüfen
  php artisan mcp:health retell
  
  # Circuit Breaker zurücksetzen
  php artisan circuit-breaker:reset retell
  ```

#### 4. "Tenant not found"
- **Ursache**: Fehlende Tenant-ID oder falscher Scope
- **Lösung**:
  ```php
  // Explizit Tenant setzen
  $mcp->executeForTenant(
      tenantId: Auth::user()->company_id,
      // ...
  );
  ```

### 📊 Debugging Tools

#### MCP Debug Mode
```bash
# In .env
MCP_DEBUG=true
MCP_LOG_LEVEL=debug

# Logs anzeigen
tail -f storage/logs/mcp.log
```

#### Performance Profiling
```php
// Enable profiling
config(['mcp.profiling' => true]);

// Execute with profiling
$result = $mcp->executeWithProfiling(
    service: 'appointment',
    tool: 'create_appointment',
    arguments: [...]
);

// View profile data
dd($result['profile']);
```

#### Health Checks
```bash
# Alle Services prüfen
php artisan mcp:health

# Einzelnen Service prüfen
php artisan mcp:health calcom

# Mit Details
php artisan mcp:health --verbose
```

### 🚨 Error Codes

| Code | Bedeutung | Lösung |
|------|-----------|--------|
| MCP001 | Server nicht gefunden | Server registrieren |
| MCP002 | Tool nicht gefunden | Tool-Name prüfen |
| MCP003 | Validation Error | Argumente prüfen |
| MCP004 | Rate Limit | Warten oder Limit erhöhen |
| MCP005 | Circuit Breaker | Service prüfen |
| MCP006 | Timeout | Timeout erhöhen |
| MCP007 | External API Error | API-Status prüfen |

### 🛠️ Recovery Procedures

#### Service Neustart
```bash
# Einzelnen Service
php artisan mcp:restart retell

# Alle Services
php artisan mcp:restart --all

# Mit Health Check
php artisan mcp:restart retell --check
```

#### Cache Problems
```bash
# MCP Cache leeren
php artisan cache:forget mcp:*

# Kompletter Cache Reset
php artisan optimize:clear
```

#### Database Connection Pool
```bash
# Pool Status
php artisan db:pool:status

# Pool Reset
php artisan db:pool:reset

# Connections prüfen
php artisan db:show
```

### 📝 Logging

#### Log Channels
- `mcp` - Allgemeine MCP Logs
- `mcp-errors` - Nur Fehler
- `mcp-performance` - Performance Metriken
- `mcp-external` - Externe Server Logs

#### Log Analyse
```bash
# Fehler der letzten Stunde
grep "ERROR" storage/logs/mcp.log | tail -100

# Slow Queries
grep "duration_ms.*[0-9]{4}" storage/logs/mcp-performance.log

# Failed Jobs
php artisan queue:failed --queue=mcp
```

### 🔧 Erweiterte Diagnostik

#### MCP Inspector
```bash
# Interaktive Diagnose
php artisan mcp:inspect appointment

# Mit Test-Execution
php artisan mcp:inspect appointment --test

# Export Diagnostics
php artisan mcp:inspect --export=diagnostics.json
```

#### Metrics Dashboard
Zugriff: https://api.askproai.de/admin/mcp-metrics

Features:
- Request/Response Times
- Error Rates
- Circuit Breaker Status
- Rate Limit Usage
- Queue Depths

### 💡 Best Practices zur Fehlervermeidung

1. **Immer Error Handling implementieren**
2. **Rate Limits beachten**
3. **Timeouts angemessen setzen**
4. **Circuit Breaker Patterns nutzen**
5. **Logging und Monitoring aktivieren**
6. **Regelmäßige Health Checks**
7. **Batch-Operations für viele Requests**
8. **Caching wo möglich**

### 🐛 Spezifische Service-Probleme

#### CalcomMCP
**Problem**: "No available slots"
```bash
# Event Types neu synchronisieren
php artisan calcom:sync-event-types

# Cache leeren
php artisan cache:forget calcom:*
```

#### RetellMCP
**Problem**: "Calls not importing"
```bash
# Webhook Status prüfen
php artisan retell:check-webhook

# Manueller Import
php artisan retell:import-calls --force
```

#### StripeMCP
**Problem**: "Invalid API key"
```bash
# Test Mode prüfen
grep STRIPE .env

# Webhook Secret validieren
php artisan stripe:verify-webhook
```

### 📞 Notfall-Kontakte

Bei kritischen Problemen:

1. **Logs sammeln**:
   ```bash
   php artisan mcp:collect-logs --last-hour
   ```

2. **System Snapshot**:
   ```bash
   php artisan mcp:snapshot
   ```

3. **Kontakt**:
   - Dev Team: dev@askproai.de
   - On-Call: +49 xxx xxx xxxx
   - Slack: #mcp-emergency

### 🔄 Rollback Procedures

#### Service Version Rollback
```bash
# Zur vorherigen Version
php artisan mcp:rollback retell

# Zu spezifischer Version
php artisan mcp:rollback retell --version=1.2.3
```

#### Configuration Rollback
```bash
# Backup erstellen
php artisan config:backup

# Rollback durchführen
php artisan config:rollback --timestamp=2025-01-19-10-30
```

### 🎯 Performance Tuning

#### Slow Response Times
1. Enable query logging
2. Check connection pool usage
3. Review caching strategy
4. Analyze external API latency

#### High Memory Usage
1. Check for memory leaks
2. Review batch sizes
3. Enable garbage collection
4. Monitor queue workers

#### Database Bottlenecks
1. Add missing indexes
2. Optimize queries
3. Increase connection pool
4. Enable query caching