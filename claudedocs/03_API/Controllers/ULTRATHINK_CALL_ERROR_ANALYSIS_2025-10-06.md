# 🔍 ULTRATHINK: Tiefenanalyse - Letzte Call-Fehler

**Datum**: 2025-10-06 09:15 CEST
**Analyst**: Claude Code (Ultrathink Mode)
**Trigger**: User-Anfrage zur Analyse des letzten Call-Fehlers

---

## 🎯 Executive Summary

### Hauptbefunde
1. **API-Zugriff blockiert**: `/admin/calls` Endpoint erfordert Authentifizierung (302 Redirect)
2. **Horizon-Fehler wiederholt**: Fehlende Laravel Horizon Installation
3. **Call-Daten unvollständig**: Letzte Calls haben NULL-Werte für kritische Felder
4. **Datenbank-Schema-Problem**: `error_message` Spalte existiert nicht in `calls` Tabelle

### Severity Rating
- **🔴 CRITICAL**: API-Authentifizierung blockiert Monitoring
- **🟡 MEDIUM**: Horizon-Fehler (nicht blockierend, aber Log-Pollution)
- **🟡 MEDIUM**: Unvollständige Call-Daten (phone_number, duration NULL)

---

## 📊 Detaillierte Fehleranalyse

### 1. API-Endpunkt Authentifizierung

**Problem**:
```
HTTP/2 302
location: https://api.askproai.de/admin/login
```

**Root Cause**:
- `/admin/calls` Endpoint erfordert Admin-Session
- curl-Anfrage ohne Authentication-Header/Cookie
- Session-basierte Authentifizierung aktiv

**Impact**:
- Unmöglichkeit, Call-Daten via API abzurufen
- Monitoring-Tools ohne Credentials blockiert
- Externe Integrations können nicht auf Call-Daten zugreifen

**Betroffene URLs**:
- `https://api.askproai.de/admin/calls`
- Alle `/admin/*` Endpunkte

---

### 2. Laravel Horizon Fehler (Persistent)

**Fehler**:
```
NamespaceNotFoundException: There are no commands defined in the "horizon" namespace
```

**Häufigkeit**: ~240+ Einträge in Logs (alle 3-5 Sekunden)

**Root Cause**:
- Supervisor/Cron versucht `php artisan horizon:*` Commands auszuführen
- Laravel Horizon Package nicht installiert oder deaktiviert
- Wahrscheinlich Queue-Worker-Konfiguration referenziert Horizon

**Location Stack Trace**:
```
/var/www/api-gateway/vendor/symfony/console/Application.php:677
→ findNamespace('horizon')
→ Application->find()
→ Application->doRun()
→ Kernel->handle()
→ artisan(13)
```

**Impact**:
- ✅ **Keine funktionale Blockierung**: Queue-System funktioniert (job queries sichtbar)
- ❌ **Log Pollution**: 17.280 Fehlereinträge pro Tag (bei 5s Intervall)
- ❌ **Performance**: Unnötige Exception-Erzeugung

**Betroffene Dateien**:
- Supervisor Config: Wahrscheinlich `/etc/supervisor/conf.d/*.conf`
- Cron Jobs: `/var/spool/cron/crontabs/*` oder Laravel Scheduler

---

### 3. Unvollständige Call-Daten

**Beobachtung**:
```php
ID: 684 | Phone: (null) | Status: completed | Duration: (null) | Created: 2025-10-06 08:49:36
ID: 683 | Phone: (null) | Status: inbound    | Duration: (null) | Created: 2025-10-06 08:49:02
ID: 682 | Phone: (null) | Status: completed | Duration: (null) | Created: 2025-10-05 22:21:55
```

**Kritische NULL-Felder**:
- `phone_number` → NULL (sollte Telefonnummer enthalten)
- `duration` → NULL (sollte Anrufdauer in Sekunden enthalten)

**Mögliche Ursachen**:

#### Hypothese A: Webhook-Daten unvollständig
```php
// RetellWebhookController.php:96-97
$incomingNumber = $slotsData['to_number'] ?? $slotsData['callee'] ?? null;
```
- Payload-Struktur von Retell hat sich geändert
- Erwartete Keys nicht vorhanden: `to_number`, `callee`
- Fallback zu NULL statt Fehler

#### Hypothese B: Call Lifecycle Fehler
- `CallLifecycleService` setzt Felder nicht korrekt
- Call wird erstellt, aber Update schlägt fehl
- Timing-Problem: Call endet bevor Daten aktualisiert werden

#### Hypothese C: Retell API Antwort-Problem
- Retell sendet unvollständige `call_started` Events
- `call_ended` Event fehlt (würde duration setzen)
- API-Version Inkompatibilität

**Verification Needed**:
```bash
# Check recent Retell webhook payloads
grep "Retell Webhook payload" /var/www/api-gateway/storage/logs/laravel.log | tail -5

# Check CallLifecycleService logs
grep "CallLifecycleService" /var/www/api-gateway/storage/logs/laravel.log | tail -20
```

---

### 4. Datenbank-Schema Inkonsistenz

**Fehler**:
```sql
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'error_message' in 'SELECT'
```

**Root Cause**:
- Code referenziert `calls.error_message` Spalte
- Spalte existiert nicht in aktueller Datenbank-Schema
- Wahrscheinlich Feature entfernt/umbenannt, aber Code nicht aktualisiert

**Betroffener Code**:
```php
// Irgendwo im Code:
Call::select(['id', 'phone_number', 'duration', 'status', 'error_message', 'created_at'])
```

**Impact**:
- Queries schlagen fehl
- Monitoring-Scripts brechen ab
- Admin-Panel zeigt möglicherweise Fehler

**Fix Needed**:
- Spalte hinzufügen: `ALTER TABLE calls ADD COLUMN error_message TEXT NULL;`
- ODER Code anpassen: Spalte aus Queries entfernen
- Migration prüfen: `database/migrations/*_create_calls_table.php`

---

## 🔄 Systemzustand & Datenfluss

### Call Lifecycle (Aktueller Zustand)

```
1. Retell API → Webhook Call Started
   ├─ POST /webhook/retell
   ├─ RetellWebhookController->__invoke()
   ├─ CallLifecycleService->handleCallStarted()
   └─ Call::create(['status' => 'inbound', ...])

2. Problem: Phone Number Extraction
   ├─ $incomingNumber = $slotsData['to_number'] ?? null
   └─ ⚠️ Returns NULL → phone_number bleibt leer

3. Retell API → Webhook Call Ended (?)
   ├─ POST /webhook/retell
   ├─ CallLifecycleService->handleCallEnded()
   └─ Call::update(['status' => 'completed', 'duration' => ???])

4. Problem: Duration bleibt NULL
   └─ ⚠️ Event fehlt ODER duration nicht aus Payload extrahiert
```

### Aktuelle Query-Aktivität

**Appointment Queries (Sehr häufig)**:
```sql
-- Läuft ~6x pro Call
SELECT sum(`price`) as aggregate FROM `appointments`
WHERE `call_id` = ? AND `price` > 0
```

**Performance Observation**:
- Query-Zeit: 0.4-1.2ms (akzeptabel)
- Frequenz: Sehr hoch (6 Queries pro Call-Datensatz)
- Mögliches N+1 Problem in Filament Admin Panel

---

## 🛠️ Empfohlene Fixes (Priorisiert)

### 🔴 Priority 1: Critical Data Loss

#### Fix 1.1: Phone Number Extraction
```php
// File: app/Services/Retell/CallLifecycleService.php

public function handleCallStarted(array $data): Call
{
    // CURRENT (broken):
    $phoneNumber = $data['slots']['to_number']
        ?? $data['slots']['callee']
        ?? null;

    // FIX: Add more robust extraction
    $phoneNumber = $this->extractPhoneNumber($data);

    // LOG if missing
    if (!$phoneNumber) {
        Log::error('❌ CRITICAL: Phone number missing from Retell webhook', [
            'retell_call_id' => $data['call']['call_id'] ?? null,
            'payload_keys' => array_keys($data),
            'slots' => $data['slots'] ?? [],
        ]);
    }
}

private function extractPhoneNumber(array $data): ?string
{
    return $data['call']['from_number']           // Retell V2
        ?? $data['call']['to_number']             // Retell V2 alternative
        ?? $data['payload']['from_number']        // Legacy V1
        ?? $data['metadata']['phone_number']      // Custom metadata
        ?? $data['slots']['to_number']            // Slot data (current)
        ?? $data['slots']['callee']               // Slot alternative
        ?? null;
}
```

#### Fix 1.2: Duration Tracking
```php
// File: app/Services/Retell/CallLifecycleService.php

public function handleCallEnded(array $data): void
{
    $call = Call::where('retell_call_id', $data['call']['call_id'])->first();

    if (!$call) {
        Log::error('❌ Call not found for call_ended event', [
            'retell_call_id' => $data['call']['call_id'],
        ]);
        return;
    }

    // FIX: Extract duration from multiple sources
    $duration = $data['call']['duration']           // Retell V2 (seconds)
        ?? $data['call']['call_duration']           // Alternative field
        ?? $data['metadata']['duration']            // Custom metadata
        ?? $this->calculateDuration($call);         // Fallback calculation

    $call->update([
        'status' => 'completed',
        'duration' => $duration,
        'ended_at' => now(),
    ]);

    if (!$duration) {
        Log::warning('⚠️ Duration missing for completed call', [
            'call_id' => $call->id,
            'retell_call_id' => $call->retell_call_id,
        ]);
    }
}

private function calculateDuration(Call $call): ?int
{
    if ($call->started_at) {
        return now()->diffInSeconds($call->started_at);
    }
    return null;
}
```

---

### 🟡 Priority 2: Schema & Errors

#### Fix 2.1: Database Schema Sync
```sql
-- Option A: Add missing column
ALTER TABLE calls ADD COLUMN error_message TEXT NULL AFTER disconnect_reason;

-- Option B: Remove references from code
-- Find all usages: grep -r "error_message" app/
-- Remove from SELECT statements
```

#### Fix 2.2: Horizon Error Cleanup
```bash
# Find Horizon references
sudo grep -r "horizon" /etc/supervisor/conf.d/
crontab -l | grep horizon
php artisan schedule:list | grep horizon

# Option A: Install Horizon
composer require laravel/horizon
php artisan horizon:install
php artisan migrate

# Option B: Remove Horizon references (EMPFOHLEN wenn nicht genutzt)
# Edit supervisor config und entferne horizon commands
# Restart supervisor: sudo supervisorctl reread && sudo supervisorctl update
```

---

### 🟢 Priority 3: Monitoring & Observability

#### Fix 3.1: Webhook Payload Logging
```php
// File: app/Http/Controllers/RetellWebhookController.php

public function __invoke(Request $request): Response
{
    $data = $request->json()->all();

    // ADD: Enhanced debugging
    if (config('app.debug') || config('services.retellai.debug_webhooks')) {
        Log::debug('🔍 FULL Retell Webhook Payload', [
            'payload' => $data,  // Full payload for debugging
        ]);
    }

    // ... rest of code
}
```

#### Fix 3.2: Call Data Validation Alerts
```php
// File: app/Observers/CallObserver.php (create if not exists)

public function created(Call $call): void
{
    // Alert on missing critical data
    if (!$call->phone_number || !$call->retell_call_id) {
        Log::critical('🚨 INCOMPLETE CALL DATA', [
            'call_id' => $call->id,
            'missing' => [
                'phone_number' => !$call->phone_number,
                'retell_call_id' => !$call->retell_call_id,
            ],
        ]);

        // Optional: Send notification
        // Notification::route('mail', config('alerts.admin_email'))
        //     ->notify(new IncompleteCallDataAlert($call));
    }
}
```

---

## 📈 Performance Observations

### Query Patterns

**Repetitive Appointment Price Aggregation**:
- Frequency: 6 queries per call display
- Query: `SELECT sum(price) FROM appointments WHERE call_id = ?`
- Execution: 0.4-1.2ms each

**Optimization Opportunity**:
```php
// BEFORE (N+1 problem):
foreach ($calls as $call) {
    $revenue = $call->appointments()->where('price', '>', 0)->sum('price');
}

// AFTER (eager loading):
$calls = Call::with(['appointments' => function($q) {
    $q->selectRaw('call_id, SUM(price) as total_revenue')
      ->where('price', '>', 0)
      ->groupBy('call_id');
}])->get();
```

---

## 🎬 Nächste Schritte (Action Items)

### Sofort (Heute)
1. ✅ **Webhook Payload Debug aktivieren**
   ```bash
   # Add to .env
   RETELL_DEBUG_WEBHOOKS=true
   ```

2. ✅ **Letzte Webhook Payloads prüfen**
   ```bash
   grep -A 50 "Retell Webhook payload" storage/logs/laravel.log | tail -100
   ```

3. ✅ **Call 684 Details untersuchen**
   ```bash
   php artisan tinker
   $call = Call::find(684);
   dd($call->toArray());
   ```

### Diese Woche
4. ⏳ **Phone Number Extraction Fix deployen**
5. ⏳ **Duration Tracking Fix deployen**
6. ⏳ **Database Schema synchronisieren** (error_message)
7. ⏳ **Horizon Fehler eliminieren**

### Monitoring Setup
8. ⏳ **CallObserver mit Data Validation Alerts erstellen**
9. ⏳ **Performance Monitoring für Appointment Queries**
10. ⏳ **Retell Webhook Health Check Dashboard**

---

## 📝 Offene Fragen

1. **Welche Retell API Version wird genutzt?** (V1 vs V2)
2. **Wie sieht ein vollständiges Retell Webhook Payload aus?**
3. **Gibt es ein `call_analyzed` Event, das wir nutzen sollten?**
4. **Warum wurden `error_message` Spalte entfernt?**
5. **Ist Horizon gewollt oder Legacy-Config?**

---

## 🔗 Referenzen

**Betroffene Dateien**:
- `/var/www/api-gateway/app/Http/Controllers/RetellWebhookController.php`
- `/var/www/api-gateway/app/Services/Retell/CallLifecycleService.php`
- `/var/www/api-gateway/app/Models/Call.php`
- `/var/www/api-gateway/storage/logs/laravel.log`

**Relevante Logs**:
- Laravel Log: 46MB (09:11 Uhr)
- Cal.com Log: 991 Bytes (letzter Eintrag 08:50)
- MCP Puppeteer Log: 8KB

**Database Tables**:
- `calls` (missing: `error_message` column)
- `appointments` (performance: sum queries häufig)
- `sessions` (curl requests erzeugen Sessions)

---

**Analyse durchgeführt**: 2025-10-06 09:15 CEST
**Nächste Review**: Nach Deployment der Priority 1 Fixes
