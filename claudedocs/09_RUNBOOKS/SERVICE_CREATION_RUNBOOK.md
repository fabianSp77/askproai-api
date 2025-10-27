# Service Creation Runbook

**Purpose**: Wiederverwendbare Anleitung zum Anlegen neuer Services mit Cal.com Integration

**Last Updated**: 2025-10-23
**Tested**: ✅ 16 Services erfolgreich erstellt (Friseur 1)

---

## 🎯 Übersicht

Dieser Runbook beschreibt den **standardisierten Prozess** zum Anlegen neuer Services für eine Company, inklusive:
1. Cal.com Event Type Erstellung
2. Service Datenbank-Eintrag
3. Event Mapping Verknüpfung
4. Testing & Verification

**Erfolgsrate**: 100% bei korrekter Ausführung
**Dauer**: ~5 Minuten für 10-20 Services

---

## 📋 Voraussetzungen

### 1. Company & Branch Identifikation

```bash
# Company finden
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$companies = \Illuminate\Support\Facades\DB::table('companies')->get();
foreach (\$companies as \$c) {
    echo \"ID: {\$c->id} | Name: {\$c->name}\" . PHP_EOL;
}
"
```

**Notieren:**
- `COMPANY_ID` = ?
- `COMPANY_NAME` = ?

### 2. Branch finden

```bash
# Branch für Company finden
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$companyId = 1; // ANPASSEN!

\$branches = \Illuminate\Support\Facades\DB::table('branches')
    ->where('company_id', \$companyId)
    ->get();

foreach (\$branches as \$b) {
    echo \"ID: {\$b->id} | Name: {\$b->name}\" . PHP_EOL;
}
"
```

**Notieren:**
- `BRANCH_ID` = ?
- `BRANCH_NAME` = ?

### 3. Cal.com Team ID finden

```bash
# Cal.com Team für Company finden
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$companyId = 1; // ANPASSEN!

\$mappings = \Illuminate\Support\Facades\DB::table('calcom_host_mappings')
    ->where('company_id', \$companyId)
    ->get();

foreach (\$mappings as \$m) {
    echo \"Team ID: {\$m->calcom_team_id} | Team Name: {\$m->team_name}\" . PHP_EOL;
}
"
```

**Notieren:**
- `CALCOM_TEAM_ID` = ?
- `CALCOM_TEAM_NAME` = ?

---

## 🛠️ Service Creation Script

### Template verwenden

```bash
# 1. Template kopieren
cp scripts/services/create_services_template.php scripts/services/create_services_NEW_COMPANY.php

# 2. Template anpassen (siehe unten)

# 3. Script ausführen
php scripts/services/create_services_NEW_COMPANY.php
```

### Script Template Anpassungen

Öffne `scripts/services/create_services_NEW_COMPANY.php` und passe folgendes an:

```php
// === CONFIGURATION ===
$companyId = 1;                                          // ← ANPASSEN
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';    // ← ANPASSEN
$calcomTeamId = 34209;                                   // ← ANPASSEN

// === SERVICES DEFINITION ===
$services = [
    [
        'name' => 'Service Name',           // ← Service-Name
        'duration' => 30,                   // ← Dauer in Minuten
        'price' => 25.00,                   // ← Preis in EUR
        'category' => 'Kategorie',          // ← Kategorie
        'description' => 'Beschreibung',    // ← Beschreibung
        'notes' => null,                    // ← Optional: Notizen
    ],
    // Weitere Services hier...
];
```

**Kategorien (standardisiert):**
- `Schnitt` - Haarschnitte
- `Färben` - Färbungen, Strähnchen
- `Pflege` - Treatments, Pflege
- `Styling` - Waschen, Föhnen, Styling
- `Beratung` - Beratungsgespräche
- `Sonstiges` - Alles andere

---

## ⚙️ Ausführung

### Schritt 1: Services erstellen

```bash
php scripts/services/create_services_NEW_COMPANY.php
```

**Erwartete Output:**
```
=== SERVICES & EVENT TYPES ERSTELLEN ===
Team ID: 34209
Anzahl: 16

[1/16] Service Name...
  ✅ Event Type: 3719738
  ✅ Service DB: 167
  ✅ Mapping erstellt

...

=== ZUSAMMENFASSUNG ===
✅ Erfolgreich: 16
❌ Fehler: 0
```

### Schritt 2: Verification

```bash
# Services prüfen
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$companyId = 1; // ANPASSEN!

\$services = \Illuminate\Support\Facades\DB::table('services')
    ->where('company_id', \$companyId)
    ->orderBy('id')
    ->get();

echo 'Total Services: ' . count(\$services) . PHP_EOL;
foreach (\$services as \$s) {
    echo \"  ✓ {\$s->name} | €{\$s->price} | Event Type: {\$s->calcom_event_type_id}\" . PHP_EOL;
}
"
```

---

## 🧪 Testing

### Test 1: ServiceNameExtractor

```bash
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Retell\ServiceNameExtractor;

\$extractor = new ServiceNameExtractor();
\$companyId = 1; // ANPASSEN!

// Test-Fälle mit echten Service-Namen
\$testCases = [
    'Ich möchte einen SERVICE_NAME',  // ← ANPASSEN
    'Ich brauche SERVICE_NAME',       // ← ANPASSEN
];

foreach (\$testCases as \$transcript) {
    echo \"Test: \\\"{$transcript}\\\"\" . PHP_EOL;
    \$result = \$extractor->extractService(\$transcript, \$companyId);

    if (\$result['service']) {
        echo \"  ✅ {\$result['service']->name} ({\$result['confidence']}%)\" . PHP_EOL;
    } else {
        echo \"  ❌ Kein Service gefunden\" . PHP_EOL;
    }
    echo PHP_EOL;
}
"
```

### Test 2: Admin Portal

```
https://api.askproai.de/admin/services
```

**Prüfen:**
- ✅ Alle Services sichtbar
- ✅ Cal.com Event Type IDs vorhanden
- ✅ Preise korrekt
- ✅ Status: Aktiv + Online

### Test 3: Cal.com Dashboard

```
https://app.cal.com/event-types?teamId=YOUR_TEAM_ID
```

**Prüfen:**
- ✅ Event Types im Team sichtbar
- ✅ schedulingType = COLLECTIVE
- ✅ Duration korrekt

### Test 4: Voice AI (End-to-End)

**Terminal 1 - Monitoring:**
```bash
tail -f storage/logs/laravel.log | grep -E "Service extraction|SAGA|lock|appointment"
```

**Test-Anruf:**
1. Retell Dashboard öffnen
2. Test-Anruf starten
3. Service nennen: "Ich möchte SERVICE_NAME"
4. Datum/Zeit angeben
5. Buchung bestätigen

**Erwartetes Log:**
```
✅ Service extraction complete: SERVICE_NAME (confidence: 100%)
✅ Distributed lock acquired
✅ Cal.com booking successful
✅ Local appointment record created
```

---

## 🚨 Troubleshooting

### Problem: "Column not found: ai_prompt_context"

**Ursache:** Veraltetes Script verwendet nicht-existente Spalte

**Lösung:** Script-Template verwenden (verwendet `assignment_notes` statt `ai_prompt_context`)

### Problem: "schedulingType must be one of..."

**Ursache:** `schedulingType` fehlt im Cal.com API Call

**Lösung:** Im Template ist bereits `'schedulingType' => 'COLLECTIVE'` enthalten

### Problem: "Cannot POST /v2/event-types"

**Ursache:** Falscher Cal.com API Endpoint

**Lösung:** Template verwendet korrekten Endpoint: `/v2/teams/{teamId}/event-types`

### Problem: Service wird nicht erkannt (Voice AI)

**Diagnose:**
```bash
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Retell\ServiceNameExtractor;
\$result = (new ServiceNameExtractor())->extractService('USER INPUT', COMPANY_ID);
echo 'Service: ' . (\$result['service']->name ?? 'NONE') . PHP_EOL;
echo 'Confidence: ' . \$result['confidence'] . '%' . PHP_EOL;
"
```

**Lösung:**
- Confidence <60% → Service-Namen anpassen
- Service nicht gefunden → Service-Namen zu komplex/unklar

### Problem: Cal.com Event Type existiert nicht

**Diagnose:**
```bash
# Event Type in Cal.com prüfen
# → https://app.cal.com/event-types?teamId=TEAM_ID
```

**Lösung:**
- Event Type manuell in Cal.com erstellen
- Event Type ID in DB updaten

---

## 📊 Success Metrics

Nach Service-Erstellung prüfen:

```bash
# 1. Anzahl Services
mysql -u root -p -e "SELECT COUNT(*) as total FROM api_gateway.services WHERE company_id = COMPANY_ID;"

# 2. Services ohne Event Type (sollte 0 sein)
mysql -u root -p -e "SELECT COUNT(*) as missing FROM api_gateway.services
WHERE company_id = COMPANY_ID AND calcom_event_type_id IS NULL;"

# 3. Event Mappings
mysql -u root -p -e "SELECT COUNT(*) as mappings FROM api_gateway.calcom_event_mappings
WHERE company_id = COMPANY_ID;"

# 4. Service Extraction Rate (nach ersten Tests)
grep "Service extraction complete" storage/logs/laravel.log | tail -20
```

**Targets:**
- ✅ Alle Services haben Event Type ID
- ✅ Alle Services haben Event Mapping
- ✅ Service Extraction ≥95% Confidence
- ✅ Keine Fehler in Logs

---

## 📁 File Locations

### Scripts
- **Template**: `scripts/services/create_services_template.php`
- **Beispiel**: `scripts/services/create_services_friseur1.php` (Friseur 1, 2025-10-23)

### Dokumentation
- **Runbook**: `claudedocs/09_RUNBOOKS/SERVICE_CREATION_RUNBOOK.md` (diese Datei)
- **Success Story**: `SERVICE_CREATION_SUCCESS_2025-10-23.md` (Beispiel Friseur 1)

### Logs
- **Laravel Log**: `storage/logs/laravel.log`
- **Monitoring Script**: `scripts/monitoring/test_monitoring.sh`

---

## 🔄 Workflow Summary

```
1. Identifikation
   ├─ Company ID finden
   ├─ Branch ID finden
   └─ Cal.com Team ID finden

2. Script Vorbereitung
   ├─ Template kopieren
   ├─ Configuration anpassen
   └─ Services definieren

3. Ausführung
   ├─ Script ausführen
   └─ Output prüfen (✅ Erfolgreich: N, ❌ Fehler: 0)

4. Verification
   ├─ DB Services prüfen
   ├─ Event Mappings prüfen
   └─ Admin Portal checken

5. Testing
   ├─ ServiceNameExtractor testen
   ├─ Cal.com Dashboard prüfen
   └─ Voice AI End-to-End Test

6. Monitoring (24h)
   ├─ Service Extraction Rate
   ├─ Orphaned Bookings (=0)
   ├─ Double Bookings (=0)
   └─ SAGA Compensations (=0)
```

---

## ✅ Checkliste

**Vor dem Start:**
- [ ] Company ID bekannt
- [ ] Branch ID bekannt
- [ ] Cal.com Team ID bekannt
- [ ] Service-Liste vorbereitet (Name, Dauer, Preis, Kategorie)

**Während Ausführung:**
- [ ] Script ohne Fehler durchgelaufen
- [ ] Alle Services in DB vorhanden
- [ ] Alle Event Mappings erstellt
- [ ] Cal.com Event Types existieren

**Nach Erstellung:**
- [ ] Admin Portal: Services sichtbar
- [ ] Cal.com Dashboard: Event Types sichtbar
- [ ] ServiceNameExtractor Test: ≥95% Recognition
- [ ] Voice AI Test: Buchung erfolgreich
- [ ] 24h Monitoring: Keine Fehler

---

## 📞 Support

Bei Problemen:
1. **Logs prüfen**: `tail -f storage/logs/laravel.log`
2. **Troubleshooting**: Siehe Abschnitt oben
3. **Dokumentation**: `SERVICE_CREATION_SUCCESS_2025-10-23.md`
4. **P0 Fixes**: `DETAILLIERTE_VERIFIZIERUNG_2025-10-23.md`

---

**Version**: 1.0
**Getestet mit**: Friseur 1 (16 Services, 2025-10-23)
**Success Rate**: 100%
