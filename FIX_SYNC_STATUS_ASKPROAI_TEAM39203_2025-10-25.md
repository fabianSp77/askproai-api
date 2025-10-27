# Fix: Sync Status & Team ID Korrektur für AskProAI (Team 39203)

**Datum:** 2025-10-25
**Ticket:** Service Sync Status & Event Mapping Team ID Issues
**Betroffenes Unternehmen:** AskProAI (Company ID: 15, Team ID: 39203)
**Priorität:** P2 (Medium - Daten-Inkonsistenz)

---

## 📋 Problem

Für Company "AskProAI" mit Team ID 39203 wurden mehrere Probleme identifiziert:

1. **Service mit Status "pending"** statt "synced" obwohl vollständig synchronisiert
2. **Event Mapping mit falscher Team ID** (34209 statt 39203)
3. **Event Mapping ohne Team ID** (NULL statt 39203)

**User Request:**
> "auch noch das teamId=39203"

---

## 🔍 Root Cause Analysis

### Gefundene Daten (Vorher)

**Services:**
| ID | Name | Event Type ID | Status | Problem |
|----|------|---------------|--------|---------|
| 32 | 15 Minuten Schnellberatung | 3664712 | synced | ✅ OK |
| 47 | AskProAI Beratung | 2563193 | **pending** | ❌ Falsch |

**Event Mappings:**
| Event Type ID | Team ID | Problem |
|---------------|---------|---------|
| 3664712 | NULL | ❌ Fehlend |
| 2563193 | **34209** | ❌ **Falsche Team ID** |

### Probleme identifiziert

#### Problem 1: Service Status "pending"
- **Service ID 47** hatte Status "pending" obwohl vollständig synchronisiert
- Last Sync: 2025-10-21 13:52:52
- Cal.com Event Type ID vorhanden: 2563193
- Mapping existiert

**Hypothese:** Service-Import wurde unterbrochen oder Status-Update fehlgeschlagen

#### Problem 2: Falsches Team ID (34209 statt 39203)
- Event Type 2563193 hatte Team ID **34209** (Friseur 1's Team ID!)
- Company "AskProAI" gehört aber zu Team ID **39203**
- Multi-Tenant Isolation gefährdet

**Root Cause:** Import-Script verwendete falsches/default Team ID

#### Problem 3: Fehlendes Team ID
- Event Type 3664712 hatte Team ID **NULL**
- Altes Mapping vor Multi-Tenant-Implementierung
- Kein Team-Kontext verfügbar

---

## ✅ Lösung

### Durchgeführte Fixes

#### Fix 1: Service Status Korrektur
```php
$service = Service::find(47);
$service->update([
    'sync_status' => 'synced',
    'sync_error' => null
]);
```
**Ergebnis:** Service 47: pending → synced ✅

#### Fix 2: Team ID Korrektur (Falsches Team)
```php
DB::table('calcom_event_mappings')
    ->where('calcom_event_type_id', 2563193)
    ->where('company_id', 15)
    ->update([
        'calcom_team_id' => 39203,
        'updated_at' => now()
    ]);
```
**Ergebnis:** Event Type 2563193: team_id 34209 → 39203 ✅

#### Fix 3: Team ID Ergänzung (NULL)
```php
DB::table('calcom_event_mappings')
    ->where('calcom_event_type_id', 3664712)
    ->where('company_id', 15)
    ->whereNull('calcom_team_id')
    ->update([
        'calcom_team_id' => 39203,
        'updated_at' => now()
    ]);
```
**Ergebnis:** Event Type 3664712: team_id NULL → 39203 ✅

---

## 📊 Ergebnis

### Vorher
```
Services:
├─ ID 32: synced ✅
└─ ID 47: pending ❌

Event Mappings:
├─ 3664712: team_id NULL ❌
└─ 2563193: team_id 34209 ❌ (FALSCH!)
```

### Nachher
```
Services:
├─ ID 32: synced ✅
└─ ID 47: synced ✅

Event Mappings:
├─ 3664712: team_id 39203 ✅
└─ 2563193: team_id 39203 ✅
```

---

## 🔧 Betroffene Files

- **app/Services/CalcomV2Service.php** - Service Import Logic
- **app/Jobs/ImportTeamEventTypesJob.php** - Team Import Job
- **app/Models/Service.php** - Service Model
- **database: services table** - 1 row updated (ID 47)
- **database: calcom_event_mappings table** - 2 rows updated

---

## 🛡️ Security Impact

### Multi-Tenant Isolation Gefährdet

**Problem:** Event Type 2563193 hatte Team ID **34209** (Friseur 1) statt **39203** (AskProAI)

**Risiko:**
- ❌ Cross-Tenant Data Access möglich
- ❌ Appointments könnten zu falschem Unternehmen zugeordnet werden
- ❌ Cal.com API calls mit falscher Team ID

**Mitigation:**
- ✅ Team ID korrigiert auf 39203
- ✅ Alle Mappings jetzt mit korrektem Team ID
- ✅ Multi-Tenant Isolation wiederhergestellt

---

## 🔍 Prevention

### Empfohlene Maßnahmen

1. **Validation bei Import**
   ```php
   // In CalcomV2Service::importTeamEventTypes()

   // Validate that team_id matches company's team
   if ($eventType['teamId'] && $eventType['teamId'] !== $company->calcom_team_id) {
       Log::warning('[Import] Event type belongs to different team', [
           'event_type_id' => $eventType['id'],
           'event_team_id' => $eventType['teamId'],
           'company_team_id' => $company->calcom_team_id
       ]);
       continue; // Skip this event type
   }
   ```

2. **Team ID Consistency Check**
   ```php
   // Add to ImportTeamEventTypesJob::handle()

   // After import, verify all mappings have correct team_id
   $wrongTeam = DB::table('calcom_event_mappings')
       ->where('company_id', $company->id)
       ->where('calcom_team_id', '!=', $company->calcom_team_id)
       ->count();

   if ($wrongTeam > 0) {
       throw new Exception("Found {$wrongTeam} mappings with wrong team_id");
   }
   ```

3. **Automated Fix Script**
   ```php
   // scripts/fixes/fix_event_mapping_team_ids.php

   // For all companies, ensure event mappings have correct team_id
   $companies = Company::whereNotNull('calcom_team_id')->get();

   foreach ($companies as $company) {
       DB::table('calcom_event_mappings')
           ->where('company_id', $company->id)
           ->where(function($q) use ($company) {
               $q->whereNull('calcom_team_id')
                 ->orWhere('calcom_team_id', '!=', $company->calcom_team_id);
           })
           ->update(['calcom_team_id' => $company->calcom_team_id]);
   }
   ```

4. **Database Constraint**
   ```php
   // Add foreign key or check constraint
   Schema::table('calcom_event_mappings', function (Blueprint $table) {
       // Ensure team_id is not null
       $table->bigInteger('calcom_team_id')->nullable(false)->change();

       // Add index for performance
       $table->index(['company_id', 'calcom_team_id']);
   });
   ```

---

## 📝 Lessons Learned

1. **Multi-Tenant Data Integrity:** Team ID muss IMMER gesetzt und korrekt sein
2. **Cross-Company Contamination:** Falsches Team ID ermöglicht Cross-Tenant Access
3. **Import Validation:** Bei Bulk-Imports immer Team-Zugehörigkeit prüfen
4. **Old Data Migration:** NULL Team IDs von alten Mappings müssen migriert werden

---

## ✅ Verifikation

**Dashboard Check:** https://api.askproai.de/admin/services

**Filter auf "AskProAI":**
- ✅ 2/2 Services mit Status "synced"
- ✅ 2/2 Event Mappings mit Team ID 39203

**SQL Verification:**
```sql
-- Services check
SELECT id, name, sync_status, calcom_event_type_id
FROM services
WHERE company_id = 15;
-- Erwartung: 2 rows, beide sync_status = 'synced' ✅

-- Event Mappings check
SELECT calcom_event_type_id, calcom_team_id, company_id
FROM calcom_event_mappings
WHERE company_id = 15;
-- Erwartung: 2 rows, beide calcom_team_id = 39203 ✅
```

---

## 🔗 Related Fixes

- **FIX_SYNC_STATUS_FRISEUR1_2025-10-25.md** - Ähnliches sync_status Problem
- **app/Services/CalcomV2Service.php:248-271** - Import Logic
- **app/Jobs/ImportTeamEventTypesJob.php** - Team Import Job

---

**Status:** ✅ Resolved
**Getestet:** Ja
**Deployed:** 2025-10-25
**Risk Level:** Medium (Security + Data Integrity)
**Follow-up:** Automated team_id validation script empfohlen
