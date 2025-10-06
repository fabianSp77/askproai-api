# 🔍 ADMIN PORTAL SETUP ANALYSE
**Datum**: 2025-09-30
**Analysetyp**: ULTRATHINK - Datenmodell, Anrufverarbeitung, Konfiguration

---

## 📊 EXECUTIVE SUMMARY

### Gesamtstatus: ⚠️ **TEILWEISE FUNKTIONSFÄHIG**

**Kritische Findings**:
- ✅ **Grundstruktur korrekt**: Company → PhoneNumber → Service Hierarchie funktioniert
- ⚠️ **Branch-Isolation fehlt**: Branch-spezifische Services werden nicht korrekt verwendet
- 🚨 **Telefonnummer-Matching fehlerhaft**: PhoneNumber Lookup schlägt oft fehl
- 🚨 **Service-Auswahl inkonsistent**: Hardcoded Fallbacks und fehlende Branch-Filter
- ⚠️ **Call-Tracking unvollständig**: branch_id wird nicht gesetzt

---

## 🏗️ DATENMODELL-ARCHITEKTUR

### Ist-Zustand der Hierarchie

```
Company (Mandant)
├── 📞 PhoneNumbers (1:N)
│   ├── number: string
│   ├── company_id: FK → companies.id ✅
│   ├── branch_id: FK → branches.id (optional) ⚠️ NICHT GENUTZT
│   └── retell_agent_id: string
│
├── 🏢 Branches (1:N)
│   ├── company_id: FK → companies.id ✅
│   ├── calcom_event_type_id: int
│   └── Many-to-Many → Services (branch_service)
│
├── 🛠️ Services (1:N)
│   ├── company_id: FK → companies.id ✅
│   ├── branch_id: FK → branches.id ⚠️ DOPPELTE ZUORDNUNG
│   ├── calcom_event_type_id: int ✅
│   ├── duration: int ✅
│   └── Many-to-Many ← Branches (branch_service) ⚠️
│
└── 📞 Calls (1:N)
    ├── company_id: FK → companies.id ✅
    ├── phone_number_id: FK → phone_numbers.id ✅
    ├── to_number: string ✅
    └── branch_id: FEHLT ❌
```

### ✅ Was funktioniert

1. **Company-PhoneNumber Zuordnung**
   - PhoneNumber hat `company_id` korrekt gesetzt
   - Company → PhoneNumbers Beziehung definiert (Company.php:107-110)

2. **PhoneNumber-Call Verknüpfung**
   - Call hat `phone_number_id` und wird korrekt erstellt
   - Beziehung definiert (Call.php:143-146)

3. **Service-Company Zuordnung**
   - Services haben `company_id` und `calcom_event_type_id`
   - Beziehung korrekt (Service.php:90-93)

### ⚠️ Problembereiche

#### Problem 1: Branch-Isolation nicht implementiert

**Symptom**: Branch-spezifische Telefonnummern verwenden nicht branch-spezifische Services

**Details**:
- PhoneNumber hat `branch_id` Feld → aber Service-Auswahl ignoriert dies komplett
- Service-Auswahl in `RetellFunctionCallHandler.php:698-712`:
  ```php
  $service = Service::where('company_id', $companyId)  // ✅ Company-Filter
      ->where('is_active', true)
      ->whereNotNull('calcom_event_type_id')
      ->first();
  // ❌ FEHLT: ->where('branch_id', $branchId)
  ```

**Impact**:
- Branch A Anruf kann Service von Branch B bekommen
- Multi-Standort Setup funktioniert nicht korrekt
- Falsche Kalender-Verfügbarkeiten werden angezeigt

**Beispiel-Szenario**:
```
Company: "Friseursalon Meier GmbH"
├── Branch 1: "Salon Mitte" (PhoneNumber: +4930111111, Service: "Herrenhaarschnitt 30min")
└── Branch 2: "Salon West" (PhoneNumber: +4930222222, Service: "Herrenhaarschnitt 45min")

Anruf an +4930111111 (Branch 1)
→ Könnte Service von Branch 2 bekommen ❌
→ Buchung im falschen Kalender ❌
```

#### Problem 2: Service hat doppelte Branch-Zuordnung

**Symptom**: Service verwendet zwei verschiedene Mechanismen für Branch-Zuordnung

**Details**:
1. **Direkte Spalte**: `services.branch_id` (Service.php:17)
2. **Pivot-Tabelle**: `branch_service` (Service.php:72-84, 265-277)

**Impact**:
- Inkonsistente Daten möglich
- Unklar welcher Mechanismus Vorrang hat
- Admin-Interface könnte beide verwenden

**Code-Nachweis**:
```php
// Service.php:17 - Direktes Feld
'branch_id',

// Service.php:72-84 - Pivot-Relationship
public function services(): BelongsToMany {
    return $this->belongsToMany(Service::class, 'branch_service')
        ->withPivot(['is_active']);
}
```

#### Problem 3: Call Model fehlt branch_id

**Symptom**: Calls können nicht branch-spezifisch ausgewertet werden

**Details**:
- PhoneNumber hat `branch_id` → wird aber nicht zu Call weitergegeben
- Call-Erstellung in `RetellWebhookController.php:171-185` setzt kein `branch_id`
- Analytics/Reporting nach Branch unmöglich

**Code-Location**: RetellWebhookController.php:171-185
```php
$call = Call::create([
    'phone_number_id' => $phoneNumberRecord->id,
    'company_id' => $companyId,
    // ❌ FEHLT: 'branch_id' => $phoneNumberRecord->branch_id
]);
```

---

## 📞 ANRUFVERARBEITUNG: Prozess-Analyse

### Aktueller Ablauf

```
1️⃣ WEBHOOK EMPFANG
   ↓ to_number: "+493083793369"

2️⃣ PHONE NUMBER LOOKUP (RetellWebhookController.php:128-155)
   ├─ Exact Match: WHERE number = '+493083793369'
   ├─ Partial Match: WHERE number LIKE '%3083793369%'
   └─ Ergebnis: PhoneNumber(id, company_id, branch_id)

3️⃣ COMPANY EXTRACTION
   ├─ IF found: company_id = phoneNumber->company_id ✅
   └─ ELSE: company_id = 1 (DEFAULT) ⚠️

4️⃣ CALL CREATION
   Call(company_id, phone_number_id, to_number, from_number)
   ❌ branch_id wird NICHT gesetzt

5️⃣ SERVICE SELECTION (RetellFunctionCallHandler.php:674-750)
   ├─ Lookup: WHERE company_id = X AND is_active = 1
   ├─ ❌ FEHLT: Branch-Filter
   └─ Fallback auf hardcoded Service IDs (38, 40, 45)

6️⃣ CAL.COM BOOKING
   └─ Payload: { eventTypeId: service->calcom_event_type_id }
```

### 🚨 Kritische Probleme

#### Problem 1: Telefonnummer-Matching fehlerhaft

**Root Cause**: Format-Inkonsistenzen zwischen Webhook und Datenbank

**Beispiele**:
| Webhook Format       | DB Format            | Match? |
|---------------------|----------------------|--------|
| `+493083793369`     | `+493083793369`      | ✅ Ja  |
| `+493083793369`     | `+49 30 83793369`    | ❌ Nein |
| `+493083793369`     | `030 83793369`       | ❌ Nein |
| `493083793369`      | `+493083793369`      | ❌ Nein |

**Code-Problem**: RetellWebhookController.php:130
```php
$cleanedNumber = preg_replace('/[^0-9+]/', '', $toNumber);
// Entfernt Leerzeichen/Bindestriche, aber behält "+"
// Problem: DB hat vielleicht kein "+" oder umgekehrt
```

**Partial Match Problem**: Zeile 137-139
```php
->where('number', 'LIKE', '%' . substr($cleanedNumber, -10))
// Sucht nur letzte 10 Ziffern
// Deutsche Nummern: 030 (3) + 83793369 (8) = 11 Ziffern!
```

**Impact**:
- PhoneNumber wird nicht gefunden → company_id = 1 (DEFAULT)
- Falsche Company wird verwendet
- Service-Auswahl schlägt fehl

#### Problem 2: Default Company ID = 1 ist gefährlich

**Location**: RetellWebhookController.php:126

```php
$companyId = 1; // Default company ID ⚠️
```

**Probleme**:
1. Company ID 1 könnte nicht existieren
2. Company ID 1 könnte falsche Services haben
3. Keine Validierung dass PhoneNumber gefunden wurde
4. Stille Fehler - kein Alert bei Fallback

**Impact**:
- Calls werden falscher Company zugeordnet
- Buchungen landen im falschen Kalender
- Kein Fehler-Tracking möglich

#### Problem 3: Service-Auswahl mit Hardcoded Fallbacks

**Location**: RetellFunctionCallHandler.php:722-750

```php
Log::warning('⚠️ No service found, trying hardcoded fallback');

// Fallback auf specific IDs
$fallbackServiceIds = [38, 40, 45];
```

**Probleme**:
1. IDs 38, 40, 45 sind **company-spezifisch**
2. Existieren möglicherweise nicht in Produktion
3. Können zu anderer Company gehören
4. Keine dynamische Konfiguration

**Impact**:
- Service-Auswahl schlägt fehl mit "Service not found"
- Cal.com Buchungen fehlschlagen
- Keine klare Fehlerbehandlung

#### Problem 4: Race Condition - Webhook Reihenfolge

**Symptom**: `collectAppointment` wird manchmal VOR `call_inbound` aufgerufen

**Details**:
- Retell sendet Webhooks parallel: `call_inbound`, `call_started`, `function_call`
- Keine Reihenfolge-Garantie
- `collectAppointment` erwartet existierenden Call-Record

**Code-Flow**:
```
NORMAL (funktioniert):
call_inbound → Call erstellt mit phone_number_id + company_id
  ↓
collectAppointment → Findet Call, verwendet company_id ✅

RACE CONDITION (bricht):
collectAppointment → Call nicht gefunden, erstellt neuen
  ↓ OHNE phone_number_id
  ↓ company_id = recent call oder NULL
call_inbound → Call existiert bereits, kein Update ❌
```

**Impact**:
- Call ohne `phone_number_id`
- `company_id` = 1 (fallback)
- Service-Auswahl fehlerhaft

#### Problem 5: Fehlende branch_id Verwendung

**Locations**:
1. PhoneNumber.branch_id wird bei Call-Erstellung ignoriert (RetellWebhookController.php:177)
2. Service-Auswahl filtert nicht nach branch_id (RetellFunctionCallHandler.php:698)

**Impact**:
- Multi-Standort Szenarien funktionieren nicht
- Branch-spezifische Services werden nicht verwendet
- Falsche Kalender-Verfügbarkeiten

---

## 🔧 LÖSUNGSVORSCHLÄGE

### Fix 1: Telefonnummer-Normalisierung (PRIORITÄT: 🔴 HOCH)

**Problem**: PhoneNumber Lookup schlägt wegen Format-Unterschieden fehl

**Lösung**: Beide Seiten (Webhook + DB) auf gleiche Normalisierung

```php
// RetellWebhookController.php:128-155
private function findPhoneNumber(string $toNumber): ?PhoneNumber
{
    // Normalisierung: Nur Ziffern, kein "+"
    $cleanedNumber = preg_replace('/[^0-9]/', '', $toNumber);

    // 1. Exact match auf normalisierte Nummer
    $phoneNumber = PhoneNumber::whereRaw(
        "REPLACE(REPLACE(REPLACE(number, '+', ''), ' ', ''), '-', '') = ?",
        [$cleanedNumber]
    )->first();

    if ($phoneNumber) {
        return $phoneNumber;
    }

    // 2. Partial match - letzten 8-11 Ziffern (deutsche Nummern)
    for ($length = 11; $length >= 8; $length--) {
        $suffix = substr($cleanedNumber, -$length);
        $phoneNumber = PhoneNumber::whereRaw(
            "REPLACE(REPLACE(REPLACE(number, '+', ''), ' ', ''), '-', '') LIKE ?",
            ['%' . $suffix]
        )->first();

        if ($phoneNumber) {
            Log::info("✅ PhoneNumber gefunden via {$length}-Ziffer Partial Match", [
                'suffix' => $suffix,
                'phone_id' => $phoneNumber->id
            ]);
            return $phoneNumber;
        }
    }

    Log::error("❌ PhoneNumber nicht gefunden", [
        'to_number' => $toNumber,
        'cleaned' => $cleanedNumber,
        'all_numbers' => PhoneNumber::pluck('number')
    ]);

    return null;
}
```

**Alternative**: Migration - Normalisierte Spalte hinzufügen
```php
// Migration: add_normalized_number_to_phone_numbers
Schema::table('phone_numbers', function (Blueprint $table) {
    $table->string('number_normalized')->after('number')->index();
});

// Model: PhoneNumber.php
protected static function boot()
{
    parent::boot();

    static::saving(function ($phoneNumber) {
        $phoneNumber->number_normalized = preg_replace('/[^0-9]/', '', $phoneNumber->number);
    });
}

// Controller: Lookup via normalized
$phoneNumber = PhoneNumber::where('number_normalized', $cleanedNumber)->first();
```

---

### Fix 2: Branch-spezifische Service-Auswahl (PRIORITÄT: 🟡 MITTEL)

**Problem**: Services werden ohne Branch-Filter ausgewählt

**Lösung**: Branch-Kontext durchgängig verwenden

```php
// RetellFunctionCallHandler.php:674-750
private function getServiceForCall(Call $call): ?Service
{
    $companyId = $call->company_id;
    $branchId = $call->phone_number?->branch_id; // Hole Branch aus PhoneNumber

    if (!$companyId) {
        Log::error('❌ Call hat keine company_id', ['call_id' => $call->id]);
        return null;
    }

    Log::info('🔍 Service-Suche', [
        'company_id' => $companyId,
        'branch_id' => $branchId
    ]);

    // Basis-Query
    $query = Service::where('company_id', $companyId)
        ->where('is_active', true)
        ->whereNotNull('calcom_event_type_id');

    // Branch-Filter wenn vorhanden
    if ($branchId) {
        // OPTION A: Direktes branch_id
        $query->where(function($q) use ($branchId) {
            $q->where('branch_id', $branchId)
              ->orWhereNull('branch_id'); // Globale Services erlauben
        });

        // OPTION B: Via branch_service Pivot
        // $query->whereHas('branches', function($q) use ($branchId) {
        //     $q->where('branches.id', $branchId)
        //       ->where('branch_service.is_active', true);
        // });
    } else {
        // Nur company-weite Services (kein branch_id)
        $query->whereNull('branch_id');
    }

    // Sortierung: is_default > priority
    $service = $query->orderByRaw('
        CASE
            WHEN is_default = 1 THEN 0
            WHEN branch_id IS NOT NULL THEN 1
            WHEN priority IS NOT NULL THEN priority + 10
            ELSE 999
        END
    ')->first();

    if (!$service) {
        Log::error('❌ Kein Service gefunden', [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'available_services' => Service::where('company_id', $companyId)
                ->pluck('id', 'name')
        ]);
        return null;
    }

    Log::info('✅ Service gefunden', [
        'service_id' => $service->id,
        'service_name' => $service->name,
        'event_type_id' => $service->calcom_event_type_id,
        'branch_specific' => $service->branch_id ? 'Ja' : 'Nein'
    ]);

    return $service;
}
```

---

### Fix 3: Call Model um branch_id erweitern (PRIORITÄT: 🟡 MITTEL)

**Problem**: Calls haben kein `branch_id`, können nicht branch-spezifisch ausgewertet werden

**Lösung**: Migration + Call-Erstellung anpassen

```php
// Migration: add_branch_id_to_calls_table.php
public function up()
{
    Schema::table('calls', function (Blueprint $table) {
        $table->string('branch_id')->nullable()->after('phone_number_id');
        $table->foreign('branch_id')->references('id')->on('branches')
              ->onDelete('set null');
        $table->index('branch_id');
    });
}

// RetellWebhookController.php:171-185
$call = Call::create([
    'retell_call_id' => $tempId,
    'from_number' => $fromNumber,
    'to_number' => $toNumber,
    'phone_number_id' => $phoneNumberRecord->id,
    'company_id' => $companyId,
    'branch_id' => $phoneNumberRecord->branch_id, // ✅ NEU
    'agent_id' => $phoneNumberRecord->agent_id,
    // ... weitere Felder
]);
```

**Bonus**: Analytics Queries

```php
// Calls pro Branch
$callsByBranch = Call::select('branch_id', DB::raw('COUNT(*) as count'))
    ->where('company_id', $companyId)
    ->groupBy('branch_id')
    ->get();

// Services pro Branch Performance
$branchPerformance = Service::with(['calls' => function($q) use ($startDate, $endDate) {
        $q->whereBetween('created_at', [$startDate, $endDate]);
    }])
    ->where('company_id', $companyId)
    ->get()
    ->groupBy('branch_id');
```

---

### Fix 4: Default Company Validation (PRIORITÄT: 🔴 HOCH)

**Problem**: Fallback auf `company_id = 1` ohne Validierung

**Lösung**: Exception statt stiller Fallback

```php
// RetellWebhookController.php:124-155
$phoneNumberRecord = $this->findPhoneNumber($toNumber);

if (!$phoneNumberRecord) {
    Log::critical('🚨 CRITICAL: PhoneNumber nicht gefunden - Anruf kann nicht zugeordnet werden', [
        'to_number' => $toNumber,
        'cleaned' => preg_replace('/[^0-9]/', '', $toNumber),
        'webhook_data' => $callData
    ]);

    // OPTION A: Anruf ablehnen
    return response()->json([
        'error' => 'phone_number_not_configured',
        'message' => 'Diese Telefonnummer ist nicht konfiguriert'
    ], 400);

    // OPTION B: In "unassigned" Queue mit Alert
    $call = Call::create([
        'to_number' => $toNumber,
        'from_number' => $fromNumber,
        'company_id' => null, // Explizit NULL statt falscher ID
        'phone_number_id' => null,
        'status' => 'unassigned',
        'metadata' => ['error' => 'phone_number_not_found']
    ]);

    // Alert senden
    \Illuminate\Support\Facades\Notification::route('slack', config('services.slack.alert_webhook'))
        ->notify(new \App\Notifications\PhoneNumberNotFoundAlert($toNumber));

    return response()->json(['received' => true], 200);
}

$companyId = $phoneNumberRecord->company_id;
$branchId = $phoneNumberRecord->branch_id;
```

---

### Fix 5: Race Condition - Idempotente Call-Erstellung (PRIORITÄT: 🟡 MITTEL)

**Problem**: `collectAppointment` kann vor `call_inbound` aufgerufen werden

**Lösung**: Immer PhoneNumber-Lookup durchführen + updateOrCreate

```php
// RetellFunctionCallHandler.php:478-625
private function ensureCallWithContext(string $callId, array $data): Call
{
    // 1. Extrahiere to_number aus allen möglichen Quellen
    $toNumber = $data['call']['to_number']
             ?? $data['to_number']
             ?? $this->extractToNumberFromArgs($data)
             ?? null;

    // 2. Lookup PhoneNumber IMMER
    $phoneNumberRecord = $toNumber ? $this->findPhoneNumber($toNumber) : null;

    // 3. updateOrCreate für Idempotenz
    $call = Call::updateOrCreate(
        ['retell_call_id' => $callId],
        [
            'to_number' => $toNumber,
            'from_number' => $data['call']['from_number'] ?? null,
            'phone_number_id' => $phoneNumberRecord?->id,
            'company_id' => $phoneNumberRecord?->company_id,
            'branch_id' => $phoneNumberRecord?->branch_id,
            'agent_id' => $phoneNumberRecord?->agent_id,
            'retell_agent_id' => $data['call']['agent_id'] ?? null,
            'updated_at' => now()
        ]
    );

    Log::info('✅ Call ensured with context', [
        'call_id' => $call->id,
        'retell_call_id' => $callId,
        'phone_number_found' => $phoneNumberRecord ? 'Ja' : 'Nein',
        'company_id' => $call->company_id,
        'branch_id' => $call->branch_id
    ]);

    return $call;
}
```

---

## ✅ VALIDIERUNGS-CHECKLISTE

### Vor dem nächsten Test prüfen:

#### 1. PhoneNumbers Konfiguration
```sql
-- Alle Phone Numbers mit Company/Branch Zuordnung
SELECT
    pn.id,
    pn.number,
    pn.company_id,
    c.name AS company_name,
    pn.branch_id,
    b.name AS branch_name,
    pn.retell_agent_id,
    pn.is_active
FROM phone_numbers pn
LEFT JOIN companies c ON pn.company_id = c.id
LEFT JOIN branches b ON pn.branch_id = b.id
WHERE pn.is_active = 1
ORDER BY c.name, b.name;

-- ❌ Prüfen auf fehlende company_id
SELECT * FROM phone_numbers WHERE company_id IS NULL;

-- ⚠️ Prüfen auf Format-Inkonsistenzen
SELECT
    number,
    CASE
        WHEN number LIKE '+49%' THEN 'Mit +'
        WHEN number LIKE '049%' THEN 'Mit 0'
        WHEN number LIKE ' %' THEN 'Mit Leerzeichen'
        ELSE 'Anderes Format'
    END AS format_typ,
    COUNT(*) as anzahl
FROM phone_numbers
GROUP BY format_typ;
```

#### 2. Services Konfiguration
```sql
-- Alle aktiven Services mit Cal.com Event Types
SELECT
    s.id,
    s.name,
    s.company_id,
    c.name AS company_name,
    s.branch_id,
    b.name AS branch_name,
    s.calcom_event_type_id,
    s.duration,
    s.is_active,
    s.is_default
FROM services s
LEFT JOIN companies c ON s.company_id = c.id
LEFT JOIN branches b ON s.branch_id = b.id
WHERE s.is_active = 1
ORDER BY c.name, b.name, s.is_default DESC, s.name;

-- ❌ Services OHNE Cal.com Event Type ID
SELECT id, name, company_id FROM services
WHERE is_active = 1 AND calcom_event_type_id IS NULL;

-- ⚠️ Doppelte Service-Branch Zuordnung prüfen
SELECT
    s.id,
    s.name,
    s.branch_id AS direct_branch_id,
    GROUP_CONCAT(bs.branch_id) AS pivot_branch_ids
FROM services s
LEFT JOIN branch_service bs ON s.id = bs.service_id
WHERE s.is_active = 1
GROUP BY s.id
HAVING direct_branch_id IS NOT NULL AND pivot_branch_ids IS NOT NULL;
```

#### 3. Companies Konfiguration
```sql
-- Alle aktiven Companies mit Konfiguration
SELECT
    id,
    name,
    calcom_team_id,
    calcom_api_key IS NOT NULL AS has_calcom_key,
    retell_api_key IS NOT NULL AS has_retell_key,
    is_active,
    (SELECT COUNT(*) FROM phone_numbers WHERE company_id = companies.id) AS phone_count,
    (SELECT COUNT(*) FROM services WHERE company_id = companies.id AND is_active = 1) AS service_count,
    (SELECT COUNT(*) FROM branches WHERE company_id = companies.id AND is_active = 1) AS branch_count
FROM companies
WHERE is_active = 1;

-- ❌ Companies OHNE Services
SELECT c.id, c.name
FROM companies c
LEFT JOIN services s ON c.id = s.company_id AND s.is_active = 1
WHERE c.is_active = 1
GROUP BY c.id
HAVING COUNT(s.id) = 0;

-- ❌ Companies OHNE PhoneNumbers
SELECT c.id, c.name
FROM companies c
LEFT JOIN phone_numbers pn ON c.id = pn.company_id AND pn.is_active = 1
WHERE c.is_active = 1
GROUP BY c.id
HAVING COUNT(pn.id) = 0;
```

#### 4. Recent Calls Analyse
```sql
-- Letzte 20 Calls mit Context
SELECT
    c.id,
    c.retell_call_id,
    c.to_number,
    c.from_number,
    c.phone_number_id,
    pn.number AS phone_number,
    c.company_id,
    co.name AS company_name,
    c.branch_id,
    b.name AS branch_name,
    c.status,
    c.created_at
FROM calls c
LEFT JOIN phone_numbers pn ON c.phone_number_id = pn.id
LEFT JOIN companies co ON c.company_id = co.id
LEFT JOIN branches b ON c.branch_id = b.id
ORDER BY c.created_at DESC
LIMIT 20;

-- ❌ Calls OHNE phone_number_id
SELECT id, retell_call_id, to_number, company_id, created_at
FROM calls
WHERE phone_number_id IS NULL
ORDER BY created_at DESC
LIMIT 10;

-- ⚠️ Calls mit company_id = 1 (Default Fallback)
SELECT id, retell_call_id, to_number, from_number, created_at
FROM calls
WHERE company_id = 1
ORDER BY created_at DESC
LIMIT 10;
```

---

## 🎯 PRIORITÄTEN & ROADMAP

### Phase 1: KRITISCHE FIXES (Heute)
**Geschätzte Zeit**: 2-3 Stunden

- [ ] **Fix PhoneNumber Lookup** (45 min)
  - Normalisierungsfunktion implementieren
  - Tests mit verschiedenen Formaten

- [ ] **Default Company Validation** (30 min)
  - Exception statt Fallback
  - Alert-System bei fehlenden PhoneNumbers

- [ ] **Service Selection Logging** (15 min)
  - Strukturiertes Logging bei Service-Auswahl
  - Fehler-Details für Debugging

- [ ] **Validierung vor Tests** (30 min)
  - SQL Queries ausführen
  - Konfiguration überprüfen
  - Dokumentation aktualisieren

### Phase 2: BRANCH-SUPPORT (Morgen)
**Geschätzte Zeit**: 3-4 Stunden

- [ ] **Branch-spezifische Service-Auswahl** (90 min)
  - Service Selection um Branch-Filter erweitern
  - Tests mit Multi-Branch Setup

- [ ] **Call Model Migration** (45 min)
  - `branch_id` Spalte hinzufügen
  - Call-Erstellung anpassen

- [ ] **End-to-End Test** (60 min)
  - Branch A → Service A
  - Branch B → Service B
  - Validierung der Zuordnung

### Phase 3: ARCHITEKTUR CLEANUP (Diese Woche)
**Geschätzte Zeit**: 4-6 Stunden

- [ ] **Service-Branch Relationship vereinheitlichen** (2h)
  - Entscheidung: Direkt vs. Pivot
  - Migration für Cleanup
  - Admin Interface anpassen

- [ ] **PhoneNumber Model Enhancement** (1h)
  - `number_normalized` Spalte
  - Automatische Normalisierung
  - Format Validation

- [ ] **Comprehensive Monitoring** (1h)
  - Dashboard für PhoneNumber Matches
  - Service Selection Success Rate
  - Branch Distribution Analytics

---

## 📝 TEST-SZENARIEN

### Szenario 1: Einfache Buchung (Single Branch)

**Setup**:
```sql
Company: "Test Friseur GmbH" (ID: 15)
PhoneNumber: "+493083793369" (company_id: 15, branch_id: NULL)
Service: "Beratungsgespräch" (company_id: 15, calcom_event_type_id: 2563193)
```

**Test**:
1. Anruf an +493083793369
2. Webhook: to_number = "+493083793369"
3. ✅ Erwartung: PhoneNumber gefunden, company_id = 15
4. ✅ Erwartung: Service "Beratungsgespräch" ausgewählt
5. ✅ Erwartung: Cal.com Buchung mit Event Type 2563193

### Szenario 2: Multi-Branch Setup

**Setup**:
```sql
Company: "Friseursalon Meier" (ID: 20)

Branch 1: "Salon Mitte" (ID: branch_1)
├─ PhoneNumber: "+4930111111" (branch_id: branch_1)
└─ Service: "Herrenhaarschnitt 30min" (branch_id: branch_1, event_type: 1000)

Branch 2: "Salon West" (ID: branch_2)
├─ PhoneNumber: "+4930222222" (branch_id: branch_2)
└─ Service: "Herrenhaarschnitt 45min" (branch_id: branch_2, event_type: 2000)
```

**Test A - Branch 1**:
1. Anruf an +4930111111
2. ✅ Erwartung: PhoneNumber mit branch_id = branch_1
3. ✅ Erwartung: Service "30min" (branch_1)
4. ✅ Erwartung: Cal.com Event Type 1000

**Test B - Branch 2**:
1. Anruf an +4930222222
2. ✅ Erwartung: PhoneNumber mit branch_id = branch_2
3. ✅ Erwartung: Service "45min" (branch_2)
4. ✅ Erwartung: Cal.com Event Type 2000

### Szenario 3: Telefonnummer nicht konfiguriert

**Test**:
1. Anruf an +491234567890 (nicht in DB)
2. ❌ Aktuell: Fallback company_id = 1
3. ✅ Erwartung: Fehler-Response + Alert
4. ✅ Erwartung: Call mit status = 'unassigned'

---

## 🔍 MONITORING & ALERTS

### Empfohlene Metriken

```sql
-- PhoneNumber Match Rate (letzten 24h)
SELECT
    COUNT(*) as total_calls,
    SUM(CASE WHEN phone_number_id IS NOT NULL THEN 1 ELSE 0 END) as matched,
    ROUND(SUM(CASE WHEN phone_number_id IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as match_rate
FROM calls
WHERE created_at >= NOW() - INTERVAL 24 HOUR;

-- Service Selection Success Rate
SELECT
    COUNT(*) as total_attempts,
    SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as successful_bookings,
    ROUND(SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as success_rate
FROM calls
WHERE created_at >= NOW() - INTERVAL 24 HOUR
AND appointment_made = 1;

-- Branch Distribution (für Multi-Branch Companies)
SELECT
    c.name as company_name,
    b.name as branch_name,
    COUNT(ca.id) as call_count
FROM calls ca
JOIN companies c ON ca.company_id = c.id
LEFT JOIN branches b ON ca.branch_id = b.id
WHERE ca.created_at >= NOW() - INTERVAL 7 DAY
GROUP BY c.id, b.id
ORDER BY c.name, b.name;
```

### Alert-Triggers

1. **PhoneNumber Match Rate < 95%**: Email an Admin
2. **Service Selection Failures > 5%**: Slack Alert
3. **company_id = 1 Fallback verwendet**: Immediate Alert
4. **Cal.com Booking Fehler > 10%**: Escalation

---

## ✅ FAZIT & NÄCHSTE SCHRITTE

### Was gut funktioniert:
✅ Company-PhoneNumber-Service Grundstruktur
✅ Cal.com Integration technisch korrekt
✅ Call-Tracking und Logging vorhanden

### Kritische Lücken:
🚨 PhoneNumber Lookup unzuverlässig
🚨 Branch-Isolation nicht implementiert
🚨 Default Fallbacks ohne Validierung

### Sofort-Maßnahmen:
1. PhoneNumber Normalisierung implementieren
2. Validierungs-Queries ausführen
3. Default Company Fallback durch Exception ersetzen
4. Test mit aktuellem Setup durchführen

### Mittelfristig:
1. Branch-spezifische Service-Auswahl
2. Call Model um branch_id erweitern
3. Monitoring Dashboard aufsetzen

---

**Erstellt**: 2025-09-30
**Nächstes Review**: Nach Implementation der kritischen Fixes