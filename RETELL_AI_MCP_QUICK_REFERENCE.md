# Retell AI MCP Server - Quick Reference Guide

## 🚀 Sofort-Befehle nach System-Start

```bash
# 1. Health Check
php artisan retell:health-check

# 2. Demo-Daten prüfen
php artisan tinker --execute="Company::where('slug', 'LIKE', 'demo-%')->count()"

# 3. Horizon starten (falls nicht läuft)
php artisan horizon

# 4. Test-Anruf
php artisan retell:test-call +491234567890
```

## 📁 Wichtigste Dateien

### Core Implementation
- `/app/Services/MCP/RetellAIBridgeMCPServer.php` - Hauptklasse
- `/app/Services/MCP/RetellAIBridgeMCPServerEnhanced.php` - Mit Circuit Breaker
- `/app/Services/AgentSelectionService.php` - Agent-Auswahl-Logik

### Admin UI
- `/app/Filament/Admin/Pages/AICallCenter.php` - Admin Page
- `/app/Filament/Admin/Resources/RetellAgentResource.php` - Agent Management

### Jobs
- `/app/Jobs/ProcessRetellAICampaignBatchJob.php` - Batch Orchestrator
- `/app/Jobs/ProcessRetellAICallJob.php` - Individual Call Processor

### Models
- `/app/Models/RetellAgent.php`
- `/app/Models/AgentAssignment.php`
- `/app/Models/RetellAICallCampaign.php`

## 🔧 Häufige Aufgaben

### Demo-Daten neu erstellen
```bash
# Alte Daten löschen
php artisan db:seed --class=RetellAIMCPDemoCleanupSeeder --force

# Neue Daten erstellen
php artisan db:seed --class=RetellAIMCPDemoSeeder --force
```

### Campaign Status prüfen
```bash
php artisan retell:monitor-campaigns
```

### Manueller API Test
```bash
php artisan tinker
>>> $bridge = app(RetellAIBridgeMCPServer::class);
>>> $bridge->getAgentStatus(['agent_id' => 'agent_medical_xxx']);
```

## 🐛 Troubleshooting

### "No agents found"
```bash
# Prüfe ob Agents existieren
php artisan tinker --execute="RetellAgent::count()"

# Erstelle Demo Agents
php artisan db:seed --class=RetellAIMCPDemoSeeder --force
```

### "Circuit breaker is open"
```bash
# Reset Circuit Breaker
php artisan tinker
>>> app('circuit-breaker')->reset('retell_api');
```

### Queue nicht verarbeitet
```bash
# Horizon Status
php artisan horizon:status

# Horizon neu starten
php artisan horizon:terminate
php artisan horizon
```

## 📊 Admin Dashboard URLs

- **AI Call Center**: `/admin/a-i-call-center`
- **Agents Management**: `/admin/retell-agents`
- **Campaigns**: Über AI Call Center Page

## ⚡ Performance Tips

1. **Batch Size anpassen**: 
   ```php
   // config/retell-mcp.php
   'batch_processing' => [
       'chunk_size' => 50, // Reduzieren bei Timeouts
   ]
   ```

2. **Rate Limits prüfen**:
   ```bash
   php artisan tinker
   >>> Cache::get('retell_rate_limit:' . $companyId);
   ```

3. **Queue Workers optimieren**:
   ```bash
   # Mehr Worker für campaigns queue
   php artisan queue:work --queue=campaigns --max-jobs=1000
   ```

## 🔐 API Keys prüfen

```bash
# Company API Keys anzeigen
php artisan tinker
>>> Company::find(1)->retell_api_key;

# Default Key aus Config
>>> config('services.retell.api_key');
```

---
**Tipp**: Diese Datei in VS Code offen lassen für schnellen Zugriff!