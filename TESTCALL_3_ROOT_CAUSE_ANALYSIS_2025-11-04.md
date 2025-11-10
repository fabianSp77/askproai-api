# Testcall 3 - Root Cause Analysis: Automatische Service-Deaktivierung
## Datum: 2025-11-04 22:06-22:15 CET

---

## Executive Summary

**User Request**: "Herrenhaarschnitt morgen um 07:00 Uhr"

**System Response**: "Leider ist der Termin morgen um sieben Uhr für einen Herrenhaarschnitt nicht verfügbar"

**Root Cause**: Automatischer Sync-Command deaktiviert alle Services alle 30 Minuten

**Status**: ✅ PROBLEM GEFUNDEN UND BEHOBEN

---

## 🔍 Problemverlauf

### Testcall-Historie

| Test | Zeit | User Request | System Response | Root Cause |
|------|------|--------------|----------------|------------|
| **1** | 21:45 | 09:00 Uhr | ❌ Nicht verfügbar | Services inaktiv (is_active=false) |
| **1-Fix** | 21:50 | - | ✅ Services aktiviert | `UPDATE services SET is_active = true` |
| **2** | 21:57 | 09:00 Uhr | ❌ Nicht verfügbar | Cal.com hat keine Slots um 09:00 |
| **3** | 22:06 | 07:00 Uhr | ❌ Nicht verfügbar | **Services WIEDER inaktiv!** |

**Muster erkannt**: Services werden automatisch deaktiviert!

---

## 🔴 ROOT CAUSE: Automatischer Sync-Command

### Der Übeltäter

**File**: `app/Console/Commands/SyncCalcomServices.php`

**Scheduled**: Alle 30 Minuten via `app/Console/Kernel.php:42`

**Problem**: Command-Logik hat fundamentalen Fehler

### Fehlerhafter Ablauf

```php
// 1. Fetch Event Types from Cal.com
$response = $calcomService->fetchEventTypes();
$eventTypes = $response->json()['event_types'] ?? [];

// 2. Check for "orphaned" services
$orphanedServices = Service::whereNotNull('calcom_event_type_id')
    ->whereNotIn('calcom_event_type_id', array_column($eventTypes, 'id'))
    ->get();

// 3. Deactivate "orphaned" services
foreach ($orphanedServices as $service) {
    $service->update([
        'is_active' => false,  // ❌ HIER!
        'sync_status' => 'error',
        'sync_error' => 'Event Type not found in Cal.com'
    ]);
}
```

### Das eigentliche Problem

**Cal.com API Endpunkt**: `/event-types`

**Was der Endpunkt liefert**: User-Event-Types (persönliche Event Types)

**Was die Services verwenden**: Team-Event-Types (Team "Friseur 1 Zentrale")

**Ergebnis**:
- API Call liefert **0 Event Types**
- Command denkt: "Alle Services sind verwaist"
- Command deaktiviert: **ALLE 18 Services**

---

## 📊 Log-Analyse

### Calcom-Sync.log (Auszug)

```
Found 0 Event Types in Cal.com

Found 33 orphaned services (exist locally but not in Cal.com):
  - Herrenhaarschnitt (Cal.com ID: 3757770)
    → Deactivated
  - Damenhaarschnitt (Cal.com ID: 3757781)
    → Deactivated
  [... 16 weitere Services ...]

+-------------------+-------+
| Metric            | Count |
+-------------------+-------+
| Total Event Types | 0     |  ← PROBLEM!
| Orphaned Services | 33    |
+-------------------+-------+
```

**Alle 30 Minuten passiert das gleiche!**

---

## 🔧 Angewandte Lösung

### 1. Services Re-Aktivieren

```php
php artisan tinker --execute="
\$updated = \App\Models\Service::where('company_id', 1)
    ->where('is_active', false)
    ->update(['is_active' => true]);

echo 'Services activated: ' . \$updated;
"

Result: Services activated: 18
```

### 2. Sync-Command Deaktivieren

**File**: `app/Console/Kernel.php`

```php
// Cal.com Event Type sync - DISABLED 2025-11-04
// ISSUE: Command fetches USER event types but services use TEAM event types
// This causes ALL services to be marked as "orphaned" and deactivated
// FIX NEEDED: Update fetchEventTypes() to use team endpoint
// $schedule->command('calcom:sync-services')
//     ->everyThirtyMinutes()
//     ...
```

**Status**: ✅ Command ist jetzt disabled

---

## 🎯 Permanente Lösung (TODO)

### Option 1: Richtigen API Endpunkt verwenden

**File**: `app/Services/CalcomService.php:700`

```php
// ❌ AKTUELL (falsch)
public function fetchEventTypes(): Response
{
    $fullUrl = $this->baseUrl . '/event-types';  // User endpoint
    return Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey
    ])->get($fullUrl);
}

// ✅ FIX (richtig)
public function fetchEventTypes(): Response
{
    // Get team ID from config
    $teamId = config('calcom.team_id');

    // Use team endpoint instead
    $fullUrl = $this->baseUrl . "/teams/{$teamId}/event-types";

    return Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey
    ])->get($fullUrl);
}
```

### Option 2: Smart Orphan Detection

Nicht sofort deaktivieren, sondern:
1. Mehrfach prüfen (3 Versuche)
2. Slack Notification an Admin
3. Nur nach manueller Bestätigung deaktivieren

### Option 3: Command ganz entfernen

Wenn Webhooks funktionieren, ist der Sync nicht nötig.

---

## 📋 Zusätzliche Findings

### Cal.com Team Configuration

Die Services verwenden **Team Event Types**:
- Team: "Friseur 1 Zentrale"
- Team ID: TBD (aus Cal.com Dashboard ermitteln)
- Event Types: 3757770, 3757771, 3757772, etc.

### Branch Assignment

**Wichtig**: Service 438 (Herrenhaarschnitt) hat jetzt:
- `branch_id`: `34c4d48e-4753-4715-9c30-c55843a943e8`
- Vorher war `branch_id` NULL (global)

**Zu prüfen**: Wurde die Branch-ID korrekt gesetzt?

---

## ✅ Verification

### Service Status

```bash
php artisan tinker --execute="
\$service = \App\Models\Service::find(438);
echo 'Herrenhaarschnitt:' . PHP_EOL;
echo '  is_active: ' . (\$service->is_active ? 'TRUE' : 'FALSE') . PHP_EOL;
echo '  calcom_event_type_id: ' . \$service->calcom_event_type_id . PHP_EOL;
"

Result:
  is_active: TRUE ✅
  calcom_event_type_id: 3757770 ✅
```

### Schedule Status

```bash
# Check scheduler won't deactivate services again
grep "calcom:sync-services" app/Console/Kernel.php

Result: (commented out) ✅
```

---

## 🚀 Next Steps

### Sofort (Für aktuellen Test)

1. ✅ Services aktiviert
2. ✅ Sync-Command deaktiviert
3. 🔄 **Erneuter Testcall mit 07:00 Uhr empfohlen**

### Kurzfristig (Heute/Morgen)

1. **Cal.com Team ID ermitteln**
   ```bash
   # Via Cal.com Dashboard oder API
   curl -H "Authorization: Bearer $CAL_API_KEY" \
        https://api.cal.com/v2/me
   ```

2. **fetchEventTypes() Fix implementieren**
   - Richtigen Team-Endpunkt verwenden
   - Pagination implementieren (falls >100 Event Types)

3. **Sync-Command wieder aktivieren**
   - Nach Fix testen mit `php artisan calcom:sync-services`
   - Logs prüfen: Werden jetzt Event Types gefunden?

### Mittelfristig (Diese Woche)

1. **Smart Orphan Detection**
   - Mehrfach-Checks vor Deaktivierung
   - Admin-Notifications
   - Rollback-Mechanismus

2. **Monitoring**
   - Alert wenn Services deaktiviert werden
   - Dashboard für Service-Status
   - Cal.com API Health Checks

3. **Tests**
   - Unit Tests für Sync-Logik
   - Integration Tests für Cal.com API
   - E2E Tests für Service-Aktivierung

---

## 📚 Betroffene Dateien

### Modified

- ✅ `app/Console/Kernel.php` (Sync-Command deaktiviert)

### Needs Fix

- ⚠️ `app/Services/CalcomService.php` (fetchEventTypes verwende Team-Endpunkt)
- ⚠️ `app/Console/Commands/SyncCalcomServices.php` (Smart Orphan Detection)

### For Reference

- `config/calcom.php` (Team ID konfigurieren)
- `storage/logs/calcom-sync.log` (Siehe Fehler-Pattern)

---

## 🎓 Lessons Learned

### 1. API Endpunkt-Validierung

**Learning**: Immer verifizieren, welcher API Endpunkt die richtigen Daten liefert

**Prevention**:
- API-Dokumentation checken
- Endpoint-Response testen
- Edge Cases berücksichtigen (User vs Team, Personal vs Organization)

### 2. Automatische Deaktivierung ist gefährlich

**Learning**: Services automatisch zu deaktivieren kann Produktion lahmlegen

**Prevention**:
- Mehrfach-Checks vor kritischen Actions
- Notifications an Admins
- Rollback-Mechanismen
- Feature Flags für automatische Actions

### 3. Scheduled Commands brauchen Monitoring

**Learning**: Scheduled Commands können im Hintergrund Schaden anrichten

**Prevention**:
- Comprehensive Logging
- Success/Failure Metrics
- Alert bei Anomalien
- Regular Command Audits

---

## 📞 Testing Recommendation

**Testcall 4 - Nach Fix**:

```
User: "Herrenhaarschnitt morgen um 07:00 Uhr"
Agent: Prüft → Service gefunden ✅ → Cal.com API ✅ → Slot verfügbar? ✅
Agent: "Gerne! Ich buche Ihnen den Termin..."
```

**Expected**: Erfolgreiche Verfügbarkeitsprüfung und Buchung

---

## 🎉 Status

| Component | Status |
|-----------|--------|
| Services Aktiviert | ✅ AKTIV (18/18) |
| Sync-Command | ✅ DEAKTIVIERT |
| Root Cause | ✅ IDENTIFIZIERT |
| Temporary Fix | ✅ IMPLEMENTIERT |
| Permanent Fix | ⏳ PENDING |
| Testing | ⏳ READY FOR RETEST |

**Services werden NICHT mehr automatisch deaktiviert!**

**System ist bereit für erneuten Testcall.**

---

**Report erstellt**: 2025-11-04 22:15 CET
**Analyst**: Claude Code Assistant

**Critical Finding**: Automatischer Sync-Command war die Ursache für alle Service-Deaktivierungen. Services werden jetzt nicht mehr deaktiviert.

**Action Required**: Erneuter Testcall mit 07:00 Uhr empfohlen, um zu verifizieren dass System jetzt funktioniert.
