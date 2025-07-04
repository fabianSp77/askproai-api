# 🚀 Meine wichtigsten Befehle - AskProAI

> **Zweck**: Sammlung der besten und häufigsten Befehle
> **Tipp**: Nutze Cmd+F für schnelle Suche!
> 
> ⭐ **NEU**: Siehe auch [TOP_10_COMMANDS.md](./TOP_10_COMMANDS.md) für die absoluten Power-Befehle!

## 🔥 TOP 10 - Täglich genutzt

### 1. MCP Auto-Discovery (NEU 2025!)
```bash
# Besten MCP-Server für Aufgabe finden
php artisan mcp:discover "kunde anlegen"
php artisan mcp:discover "termin buchen" --execute
```

### 2. Quick Debug Combo
```bash
# Wenn etwas nicht funktioniert
rm -f bootstrap/cache/config.php && php artisan config:cache && php artisan horizon
```

### 3. Datenbank-Zugriff
```bash
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db
```

### 4. Impact Analysis vor Deployment
```bash
php artisan analyze:impact --git
php artisan analyze:component App\\Services\\BookingService
```

### 5. Code Quality Check
```bash
composer quality  # Alles auf einmal
```

### 6. Retell Calls Import
```bash
php artisan horizon  # Erst Queue starten
# Dann im Admin: "Anrufe abrufen" Button
```

### 7. Documentation Health
```bash
php artisan docs:check-updates --auto-fix
```

### 8. Test mit Coverage
```bash
php artisan test --parallel --coverage
```

### 9. Data Flow Debugging
```bash
php artisan dataflow:list
php artisan dataflow:diagram <correlation-id>
```

### 10. Cache Clear All
```bash
php artisan optimize:clear
```

## 💎 EXTREM GUT - Produktivitäts-Booster

### Claude Self-Update
```bash
# Claude auf neuesten Stand bringen
echo "Claude, lies CLAUDE_CONTEXT_SUMMARY.md und update dein Wissen über die Best Practices 2025"
```

### Full System Check
```bash
# Einmal alles prüfen
php artisan health:check && \
php artisan mcp:health && \
php artisan docs:health && \
composer quality
```

### Smart Migration
```bash
# Migration mit Impact Analysis
php artisan migrate:smart --analyze
php artisan migrate:smart --online  # Zero downtime
```

### Debug Mode Combo
```bash
# Für tiefes Debugging
export BOOKING_DEBUG=true && \
export LOG_LEVEL=debug && \
tail -f storage/logs/laravel.log | grep -E "(ERROR|WARNING|correlation_id)"
```

### Git Productivity
```bash
# Schneller Feature Branch
git checkout -b feature/$(date +%Y%m%d)-description

# Smart Commit mit Auto-Checks
git add . && git commit -m "feat: neue funktion" # Hooks machen den Rest!
```

## 🎯 PROJEKT-SPEZIFISCH

### Webhook Test
```bash
# Retell Webhook testen
curl -X POST http://localhost:8000/api/retell/webhook \
  -H "Content-Type: application/json" \
  -H "x-retell-signature: test" \
  -d '{"event_type":"call_ended","call_id":"test-123"}'
```

### Calcom Sync
```bash
# Event Types synchronisieren
php artisan calcom:sync-event-types
php artisan circuit-breaker:reset calcom  # Bei Problemen
```

### Performance Check
```bash
# Langsame Queries finden
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "
SELECT query_time, sql_text 
FROM mysql.slow_log 
WHERE query_time > 1 
ORDER BY query_time DESC 
LIMIT 10;"
```

## 🛠️ ENTWICKLUNG

### Neuer Service mit MCP
```php
// In jeden neuen Service!
use App\Traits\UsesMCPServers;

class MyService {
    use UsesMCPServers;
    
    public function doSomething($data) {
        return $this->executeMCPTask('task description', $data);
    }
}
```

### Filament Resource
```bash
# Komplett generieren
php artisan make:filament-resource Customer --generate --view
```

### Test Einzeln
```bash
# Nur einen Test
php artisan test --filter=BookingFlowTest::test_can_book_appointment
```

## 🚨 NOTFALL

### System Down
```bash
# 1. Logs checken
tail -n 1000 storage/logs/laravel.log | grep ERROR

# 2. Services neu starten
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
php artisan horizon:terminate

# 3. Cache leeren
php artisan optimize:clear
```

### Rollback
```bash
# Code rollback
git reset --hard HEAD~1

# Database rollback
php artisan migrate:rollback --step=1

# Deploy vorherige Version
./deploy.sh rollback
```

## 📝 EIGENE BEFEHLE HINZUFÜGEN

```bash
# Befehl hier einfügen mit Beschreibung
# Kategorie: [TOP10|EXTREM_GUT|PROJEKT|ENTWICKLUNG|NOTFALL]
# Datum: YYYY-MM-DD
# Beispiel:

### Mein Super Befehl
\```bash
# Beschreibung was er macht
dein-befehl --parameter
\```
```

---
💡 **Tipp**: Erstelle Bash Aliases für die häufigsten Befehle in ~/.bashrc:
```bash
alias mcp-find='php artisan mcp:discover'
alias quality='composer quality'
alias impact='php artisan analyze:impact --git'
```