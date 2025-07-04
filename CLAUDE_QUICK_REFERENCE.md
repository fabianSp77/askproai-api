# 🚀 AskProAI Quick Reference

> **Letztes Update**: 2025-06-27 | **Ziel**: Alle kritischen Infos in < 2 Min finden

## 🔴 KRITISCH - Sofort-Hilfe

### Database Access
```bash
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db
# Root: mysql -u root -p'V9LGz2tdR5gpDQz'
```

### Häufigste Probleme & Fixes
```bash
# "Access denied for user" → Config Cache Problem
rm -f bootstrap/cache/config.php && php artisan config:cache

# "Keine Anrufe werden angezeigt" → Queue läuft nicht
php artisan horizon
# Dann: Admin Panel → "Anrufe abrufen" Button

# "Webhook failed" → Signature Check
tail -f storage/logs/laravel.log | grep -i retell
```

## 🟡 TÄGLICH - Essential Commands

### Development
```bash
php artisan serve              # Local server starten
npm run dev                    # Vite dev server
php artisan tinker            # Interactive shell
```

### Testing & Quality
```bash
php artisan test              # Alle Tests
php artisan test --parallel   # Schneller mit Parallel
php artisan test --filter=FeatureName  # Specific test
```

### Cache & Performance
```bash
php artisan optimize:clear    # ALLE Caches leeren
php artisan horizon          # Queue Worker starten
php artisan queue:work       # Alternative zu Horizon
```

## 🟢 MCP-SERVER - Quick Usage

### 🆕 NEU: Automatische MCP-Nutzung (2025)
```bash
# Bester MCP-Server für Aufgabe finden
php artisan mcp:discover "kunde anlegen"

# Direkt ausführen
php artisan mcp:discover "termin buchen" --execute

# In Code nutzen (mit UsesMCPServers trait)
$this->executeMCPTask('book appointment', $data);
```

### Verfügbare Tools
- **Doku abrufen**: `mcp__context7__get-library-docs` (Laravel, Filament, etc.)
- **Task Management**: `TodoWrite` / `TodoRead`
- **Complex Tasks**: Set `MCP_TASKMASTER_ENABLED=true` in .env

### Top 5 Interne MCP-Server
1. **DatabaseMCP** - Sichere DB-Queries
2. **RetellMCP** - AI-Telefonie Integration  
3. **CalcomMCP** - Kalender-Operationen
4. **WebhookMCP** - Event Processing
5. **QueueMCP** - Job Management

## ⚡ WORKFLOWS - Copy & Paste

### 🆕 Best Practice Workflow (2025)
```bash
# VOR jeder Aufgabe
php artisan mcp:discover "deine aufgabe"           # MCP finden
php artisan analyze:understand App\\Service        # Code verstehen
php artisan dataflow:start                         # Tracking starten

# WÄHREND Development
composer pint                                      # Code formatieren
php artisan analyze:impact --component=Service    # Impact prüfen

# VOR Commit
composer quality                                   # Alle Checks
php artisan docs:check-updates                    # Doku prüfen
```

### Neues Feature entwickeln
```bash
# 1. Branch erstellen
git checkout -b feature/new-feature

# 2. Filament Resource
php artisan make:filament-resource ModelName --generate

# 3. Migration
php artisan make:migration create_table_name

# 4. Tests schreiben & ausführen
php artisan test --filter=ModelName
```

### Deployment Vorbereitung
```bash
# 1. Tests & Linting
php artisan test && npm run build

# 2. Cache optimieren
php artisan optimize

# 3. Migrations prüfen
php artisan migrate:status
```

## 🔧 ENV - Kritische Variablen
```env
# Retell.ai
RETELL_TOKEN=key_xxx
RETELL_WEBHOOK_SECRET=key_xxx

# Cal.com  
DEFAULT_CALCOM_API_KEY=
DEFAULT_CALCOM_TEAM_SLUG=

# Queue
QUEUE_CONNECTION=redis
```

## 📞 SUPPORT - Wer kann helfen?
- **Retell Issues**: Check RetellMCPServer + Webhook logs
- **Cal.com Sync**: CalcomV2Service + Circuit Breaker status
- **DB Problems**: DatabaseMCPServer + Connection Pool
- **Quick Debug**: `php artisan tinker` → Test direkt!

---
💡 **Pro-Tip**: Nutze `Cmd+F` für Suche | Weitere Details in CLAUDE.md

## 🆕 NEU 2025: Best Practices Links
- **Implementation Guide**: [BEST_PRACTICES_IMPLEMENTATION.md](./BEST_PRACTICES_IMPLEMENTATION.md)
- **Context Summary**: [CLAUDE_CONTEXT_SUMMARY.md](./CLAUDE_CONTEXT_SUMMARY.md)
- **Error Patterns**: [ERROR_PATTERNS.md](./ERROR_PATTERNS.md)
- **Full Documentation**: [CLAUDE.md](./CLAUDE.md)