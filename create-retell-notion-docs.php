<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $notionMCP = new \App\Services\MCP\NotionMCPServer();
    
    // Parent page ID for AskProAI Platform Documentation
    $parentId = '205aba11-76e2-8052-a79d-c0feb2093cad';
    
    echo "📝 Creating Retell.ai Integration Documentation...\n\n";
    
    // Create main Retell.ai documentation page
    $mainPageContent = <<<MARKDOWN
# Retell.ai Integration Documentation

> 🚨 **KRITISCH**: Vollständig behoben am 2025-07-02. Diese Dokumentation enthält alle wichtigen Informationen zur Retell.ai Integration mit AskProAI.

## 📊 Aktueller Status

### ✅ Was funktioniert
- Webhook-Struktur-Änderung behoben (nested "call" object handling)
- Timestamp-Format flexibel (ISO 8601 + numeric milliseconds)
- TenantScope Webhook-Bypass implementiert
- Zeitzonenkonvertierung (UTC → Berlin Zeit)
- Branch-Zuordnung funktioniert
- Automatischer Import alle 15 Minuten
- Live-Anzeige aktiver Anrufe im Dashboard

### 🔧 Kritische Konfiguration

**Webhook URL in Retell.ai Dashboard:**
```
https://api.askproai.de/api/retell/webhook-simple
```

**Automatische Prozesse (via Cron):**
```bash
# Anrufe importieren (alle 15 Minuten)
*/15 * * * * /usr/bin/php /var/www/api-gateway/manual-retell-import.php

# Alte in_progress Anrufe bereinigen (alle 5 Minuten)
*/5 * * * * /usr/bin/php /var/www/api-gateway/cleanup-stale-calls.php
```

### 🚀 Quick Test bei Problemen
```bash
# Test mit aktueller Retell-Struktur
php test-retell-real-data.php

# Horizon Status
php artisan horizon:status

# Logs prüfen
tail -f storage/logs/laravel.log | grep -i retell
```

## 📚 Dokumentationsübersicht

| Dokumentation | Beschreibung | Status |
|---------------|--------------|--------|
| [Overview & Architecture](#overview--architecture) | Systemübersicht und Architektur | ✅ Aktuell |
| [Setup Guide](#setup-guide) | Schritt-für-Schritt Einrichtung | ✅ Aktuell |
| [Operations Manual](#operations-manual) | Täglicher Betrieb und Wartung | ✅ Aktuell |
| [Troubleshooting Guide](#troubleshooting-guide) | Problemlösung und Debugging | ✅ Aktuell |
| [API Reference](#api-reference) | API Endpoints und Datenstrukturen | ✅ Aktuell |
| [Critical Fixes](#critical-fixes) | Wichtige Fixes und Patches | 🚨 Kritisch |

## Overview & Architecture

Die Retell.ai Integration ermöglicht es, dass eingehende Anrufe automatisch von einem KI-Agenten beantwortet werden, der Termine buchen kann.

### Datenfluss
1. Kunde ruft an → Retell.ai Agent antwortet
2. Agent sammelt Termindaten
3. call_ended Event → Webhook an /api/retell/webhook
4. WebhookProcessor → ProcessRetellCallEndedJob
5. Job erstellt Appointment

### Kritische Dateien
- `/app/Http/Controllers/Api/RetellWebhookWorkingController.php` - Webhook-Verarbeitung
- `/app/Helpers/RetellDataExtractor.php` - Datenextraktion
- `/app/Scopes/TenantScope.php` - Tenant-Isolation
- `/app/Services/PhoneNumberResolver.php` - Company/Branch Auflösung

## Setup Guide

### 1. Retell Account Setup
1. Sign up at [retellai.com](https://retellai.com)
2. Get API credentials from Dashboard → API Keys
3. Purchase German phone number (+49)

### 2. Agent Configuration
Import the provided agent configuration with:
- German language settings
- Appointment booking functions
- Optimized voice parameters

### 3. Webhook Integration
Configure webhook in Retell Dashboard:
- URL: `https://api.askproai.de/api/retell/webhook-simple`
- Method: POST
- Events: All call events

### 4. Environment Variables
```bash
RETELL_TOKEN=your_api_key
RETELL_WEBHOOK_SECRET=same_as_api_key
RETELL_BASE=https://api.retellai.com
DEFAULT_RETELL_AGENT_ID=your_agent_id
```

## Operations Manual

### Tägliche Aufgaben
- Monitor error rates
- Check webhook processing
- Review failed calls

### Wöchentliche Aufgaben
- Analyze call patterns
- Update prompts based on feedback
- Test all functions

### Monitoring Commands
```bash
# Check recent calls
mysql -u askproai_user -p'password' askproai_db -e "SELECT * FROM calls ORDER BY created_at DESC LIMIT 10;"

# Check webhook events
tail -f storage/logs/laravel.log | grep -i retell

# Queue status
php artisan horizon:status
```

## Troubleshooting Guide

### Common Issues

#### "No appointment data found"
**Symptom**: Webhook processes but no appointment is created
**Solution**: Ensure Retell agent has collect_appointment function configured

#### "No company context found"
**Symptom**: Tenant scope errors
**Solution**: Ensure phone number exists in phone_numbers table with company_id and branch_id

#### Webhook Not Processing
**Check**:
- Webhook URL in Retell dashboard
- SSL certificate
- Horizon running

### Debug Commands
```bash
# Test phone resolution
php artisan tinker
>>> \$resolver = app(\App\Services\PhoneNumberResolver::class);
>>> \$resolver->resolve('+49 30 837 93 369');

# Clear cache
php artisan optimize:clear

# Restart services
supervisorctl restart horizon
```

## API Reference

### Webhook Endpoint
`POST /api/retell/webhook-simple`

Expected payload structure:
```json
{
  "event": "call_ended",
  "call": {
    "call_id": "string",
    "from_number": "string",
    "to_number": "string",
    "start_timestamp": "2025-07-02T20:51:03.000Z"
  }
}
```

### Custom Functions

#### collect_appointment_data
Collects appointment data from caller:
```json
{
  "name": "collect_appointment_data",
  "parameters": {
    "datum": "25.06.2025",
    "uhrzeit": "14:30",
    "name": "Customer Name",
    "telefonnummer": "+49123456789",
    "dienstleistung": "Service Type"
  }
}
```

## Critical Fixes

### 2025-07-02: Webhook Structure Change
Retell.ai changed their webhook data structure from flat to nested format.

**Fix implemented in:**
- `RetellWebhookWorkingController.php` - Flattens nested structure
- `RetellDataExtractor.php` - Flexible timestamp parsing
- `TenantScope.php` - Webhook bypass for API routes

**WICHTIG**: Diese Dateien NIEMALS ändern ohne diese Fixes zu beachten!

### Deployment Checklist
- [ ] RetellWebhookWorkingController has structure flatten code
- [ ] RetellDataExtractor has parseTimestamp method
- [ ] TenantScope has webhook bypass
- [ ] Horizon is running
- [ ] No config cache issues

## Support & Contact

Bei Problemen:
1. Logs prüfen: `storage/logs/laravel.log`
2. Debug-Scripts ausführen
3. Retell.ai Support für Agent-Konfiguration
4. AskProAI Development Team für Backend-Issues
MARKDOWN;

    $result = $notionMCP->executeTool('create_page', [
        'parent_id' => $parentId,
        'title' => '📞 Retell.ai Integration',
        'content' => $mainPageContent
    ]);
    
    if ($result['success']) {
        echo "✅ Successfully created main documentation page!\n";
        echo "📄 Page ID: " . $result['data']['page_id'] . "\n";
        echo "🔗 URL: " . $result['data']['url'] . "\n\n";
        
        $mainPageId = $result['data']['page_id'];
        
        // Create sub-pages for detailed documentation
        $subPages = [
            [
                'title' => '🔧 Setup Guide',
                'content' => file_get_contents('RETELL_SETUP_GUIDE.md')
            ],
            [
                'title' => '🐛 Troubleshooting Guide',
                'content' => file_get_contents('RETELL_TROUBLESHOOTING_GUIDE.md')
            ],
            [
                'title' => '🚨 Critical Fixes',
                'content' => file_get_contents('RETELL_WEBHOOK_FIX_2025-07-02.md')
            ],
            [
                'title' => '📊 Integration Status',
                'content' => file_get_contents('RETELL_INTEGRATION_CRITICAL.md')
            ]
        ];
        
        foreach ($subPages as $subPage) {
            if (file_exists($subPage['content'])) {
                echo "📝 Creating sub-page: {$subPage['title']}...\n";
                
                $subResult = $notionMCP->executeTool('create_page', [
                    'parent_id' => $mainPageId,
                    'title' => $subPage['title'],
                    'content' => $subPage['content']
                ]);
                
                if ($subResult['success']) {
                    echo "✅ Created: {$subPage['title']}\n";
                    echo "   URL: " . $subResult['data']['url'] . "\n\n";
                } else {
                    echo "❌ Failed to create {$subPage['title']}: " . $subResult['error'] . "\n\n";
                }
            }
        }
        
        echo "\n🎉 Documentation creation complete!\n";
        echo "📚 Main documentation URL: " . $result['data']['url'] . "\n";
        
    } else {
        echo "❌ Failed to create main page: " . $result['error'] . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}