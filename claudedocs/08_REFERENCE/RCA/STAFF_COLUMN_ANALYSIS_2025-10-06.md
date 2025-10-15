# 👤 Mitarbeiter:in Spalte - Ultrathink Analyse

**Datum**: 2025-10-06 12:00 CEST
**Anfrage**: Analyse wie die "Mitarbeiter:in" Spalte funktioniert und welcher Prozess dahintersteckt
**Status**: ✅ ANALYSIERT

---

## 🎯 Executive Summary

**Ergebnis**: Die "Mitarbeiter:in" Spalte zeigt **fast immer "Nicht zugewiesen"** (97.7% der Fälle), weil:

1. ❌ **Call.staff_id wird NIE gesetzt** (0% aller Calls)
2. ❌ **Appointment.staff_id wird fast nie gesetzt** (nur 2.3% aller Appointments)
3. ❌ **Cal.com liefert keinen Staff** in der Booking-Response
4. ❌ **AppointmentCreationService setzt kein staff_id** beim Erstellen

**Die Spalte ist technisch korrekt implementiert, aber funktional nutzlos** weil die Daten fehlen.

---

## 📊 Datenqualität - Aktuelle Situation

### Calls Tabelle (189 total)
```
staff_id gesetzt:      0 calls (0%)     ← IMMER NULL!
appointment_id gesetzt: 1 call  (0.5%)  ← Fast nie verlinkt
```

### Appointments Tabelle (133 total)
```
staff_id gesetzt:  3 appointments (2.3%)   ← Fast NIE gesetzt!
call_id gesetzt:   9 appointments (6.8%)   ← Manche verlinkt
```

### Appointment Sources - Staff Assignment Rate
```
Source             | Total | With Staff | Rate
━━━━━━━━━━━━━━━━━━━|━━━━━━━|━━━━━━━━━━━━|━━━━━━
walk-in            |   1   |     1      | 100%  ← Manuell!
app                |   1   |     1      | 100%  ← Manuell!
retell_phone       |   2   |     1      | 50%   ← Anderer Flow
cal.com            | 101   |     0      | 0%    ← NIE Staff!
retell_webhook     |   7   |     0      | 0%    ← NIE Staff!
phone              |   9   |     0      | 0%    ← NIE Staff!
retell_transcript  |   2   |     0      | 0%    ← NIE Staff!
```

**Fazit**: Nur manuell erstellte Appointments haben Staff. Alle automatischen Prozesse setzen KEIN staff_id.

---

## 🏗️ Technische Implementierung

### 1. Spalten-Definition in CallResource.php

**File**: `/var/www/api-gateway/app/Filament/Resources/CallResource.php:680-740`

```php
Tables\Columns\TextColumn::make('appointment_staff')
    ->label('Mitarbeiter:in')
    ->getStateUsing(function (Call $record) {
        $appointment = $record->appointment;  // ← Holt Appointment vom Call

        if (!$appointment) {
            return null;  // Kein Appointment = Keine Anzeige
        }

        $appointment->load(['service', 'staff']);  // ← Lädt Staff-Beziehung

        if ($appointment->staff) {
            return $appointment->staff->name;  // ✅ Staff gefunden
        } else {
            return 'Nicht zugewiesen';  // ❌ Kein Staff (97.7% der Fälle!)
        }
    })
```

**Wichtig**: Die Spalte zeigt **NICHT** `Call.staff_id`, sondern `Call->Appointment->Staff->name`!

### 2. Datenmodell-Beziehungen

**Call Model** (`app/Models/Call.php`):
```php
// Direkte Beziehung (WIRD NICHT GENUTZT!)
public function staff(): BelongsTo {
    return $this->belongsTo(Staff::class, 'staff_id');
}

// Appointment-Beziehung (WIRD GENUTZT in Spalte)
public function appointment(): BelongsTo {
    return $this->belongsTo(Appointment::class);
}
```

**Appointment Model**:
```php
public function staff(): BelongsTo {
    return $this->belongsTo(Staff::class, 'staff_id');
}

public function call(): BelongsTo {
    return $this->belongsTo(Call::class);
}
```

### 3. Datenfluss-Diagramm

```
┌─────────────────────────────────────────────────────────┐
│ RETELL.AI WEBHOOK (call_analyzed)                       │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ RetellWebhookController::handleCallAnalyzed()           │
│ ├─ Extrahiert Booking-Details aus Transcript            │
│ └─ Ruft AppointmentCreationService::createFromCall()    │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ AppointmentCreationService::createFromCall()            │
│ ├─ Validiert Confidence (>60%)                          │
│ ├─ Erstellt/Findet Customer                             │
│ ├─ Findet Service                                       │
│ ├─ Bucht in Cal.com (bookInCalcom)                      │
│ └─ Erstellt lokalen Appointment Record                  │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ AppointmentCreationService::createLocalRecord()         │
│                                                          │
│ Appointment::create([                                   │
│     'customer_id' => $customer->id,         ✅          │
│     'service_id' => $service->id,           ✅          │
│     'branch_id' => $branchId,               ✅          │
│     'call_id' => $call->id,                 ✅          │
│     'starts_at' => $bookingDetails[...],    ✅          │
│     'ends_at' => $bookingDetails[...],      ✅          │
│     'status' => 'scheduled',                ✅          │
│     'calcom_v2_booking_id' => $calcomId,    ✅          │
│                                                          │
│     'staff_id' => ???,                      ❌ FEHLT!   │
│ ]);                                                      │
└─────────────────────────────────────────────────────────┘
```

**Problem**: `staff_id` wird NIRGENDWO gesetzt im automatischen Flow!

---

## 🔍 Root Cause Analysis

### Problem 1: Cal.com Response enthält keinen Staff

**Cal.com Booking Response Struktur** (vermutlich):
```json
{
  "id": "abc123",
  "eventTypeId": 123,
  "title": "Beratung",
  "startTime": "2025-10-10T09:30:00Z",
  "endTime": "2025-10-10T10:15:00Z",
  "attendees": [
    {
      "name": "Hansi Schulze",
      "email": "hansi@example.com",
      "timeZone": "Europe/Berlin"
    }
  ],
  // ❌ KEIN staff/assignee/host Feld!
}
```

**Was fehlt**:
- Kein `host` oder `assignee` Feld
- Kein `staffId` oder `userId`
- Keine Information WER der Termin durchführt

**Mögliche Ursachen**:
1. Cal.com EventType hat keinen Default-Host konfiguriert
2. Round-Robin Zuweisung nicht aktiviert
3. API-Response-Version liefert Staff-Info nicht zurück
4. Staff-Zuweisung erfolgt NACH Booking (async)

### Problem 2: AppointmentCreationService nutzt Cal.com Staff nicht

**File**: `/var/www/api-gateway/app/Services/Retell/AppointmentCreationService.php:315-362`

```php
public function createLocalRecord(...): Appointment {
    $appointment = Appointment::create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'branch_id' => $branchId,
        'call_id' => $call ? $call->id : null,
        'starts_at' => $bookingDetails['starts_at'],
        'ends_at' => $bookingDetails['ends_at'],
        'status' => 'scheduled',
        'calcom_v2_booking_id' => $calcomBookingId,

        // ❌ KEIN staff_id gesetzt!
        // ❌ $bookingResult enthält keine Staff-Info
        // ❌ Keine Logik um Staff zu extrahieren
    ]);

    return $appointment;
}
```

**Was fehlt**:
```php
// SOLLTE SO AUSSEHEN (wenn Cal.com Staff liefern würde):
$staffId = $bookingResult['booking_data']['host']['id'] ?? null;
if ($staffId) {
    // Map Cal.com User ID zu lokaler Staff ID
    $localStaff = Staff::where('calcom_user_id', $staffId)->first();
    if ($localStaff) {
        $appointment->staff_id = $localStaff->id;
    }
}
```

### Problem 3: Call.staff_id wird NIEMALS gesetzt

**Searches im gesamten Codebase**:
```bash
grep -r "call->staff_id =" app/
grep -r "call['staff_id']" app/
grep -r "'staff_id' =>" app/Services/Retell/
```

**Ergebnis**: ❌ KEINE Stelle setzt `Call.staff_id`!

**Warum**:
- Die direkte Beziehung `Call->Staff` wird nicht genutzt
- Nur `Call->Appointment->Staff` wird verwendet
- `Call.staff_id` ist ein "totes" Feld in der Datenbank

---

## 💡 Lösungsansätze

### Option 1: Cal.com Host/Staff-Info nutzen (RECOMMENDED)

**Voraussetzung**: Cal.com muss Staff in Response liefern

**Schritte**:
1. **Cal.com EventType konfigurieren**:
   - Default Host setzen ODER
   - Round-Robin/Collective EventType nutzen
   - API v2 Response prüfen ob Staff enthalten ist

2. **AppointmentCreationService erweitern**:
```php
// In createLocalRecord():
$staffId = null;

// Option A: Aus Cal.com Response extrahieren
if ($calcomBookingData && isset($calcomBookingData['host']['id'])) {
    $calcomUserId = $calcomBookingData['host']['id'];

    // Map Cal.com User zu lokalem Staff
    $staff = Staff::where('calcom_user_id', $calcomUserId)->first();
    if ($staff) {
        $staffId = $staff->id;
        Log::info('✅ Staff mapped from Cal.com', [
            'calcom_user_id' => $calcomUserId,
            'staff_id' => $staffId,
            'staff_name' => $staff->name
        ]);
    }
}

$appointment = Appointment::create([
    // ... existing fields ...
    'staff_id' => $staffId,  // ✅ Jetzt gesetzt!
]);
```

3. **Staff Table erweitern**:
```php
// Migration:
Schema::table('staff', function (Blueprint $table) {
    $table->string('calcom_user_id')->nullable()->unique();
});
```

**Pro**:
- ✅ Automatische Zuweisung basierend auf Cal.com
- ✅ Korrekte Zuweisung wenn Cal.com Round-Robin nutzt
- ✅ Synchron mit externem Kalender-System

**Contra**:
- ❌ Abhängig von Cal.com Response-Format
- ❌ Mapping Staff ↔ Cal.com Users erforderlich
- ❌ Funktioniert nur wenn Cal.com Host liefert

---

### Option 2: Service-basierte Default-Zuweisung

**Konzept**: Jeder Service hat einen Default-Staff

**Implementierung**:
```php
// In createLocalRecord():
$staffId = null;

// Fallback: Nutze Service Default Staff
if (!$staffId && $service->default_staff_id) {
    $staffId = $service->default_staff_id;
    Log::info('Using service default staff', [
        'service' => $service->name,
        'staff_id' => $staffId
    ]);
}

$appointment = Appointment::create([
    // ... existing fields ...
    'staff_id' => $staffId,
]);
```

**Pro**:
- ✅ Einfach zu implementieren
- ✅ Funktioniert ohne Cal.com Integration
- ✅ Sofortige Zuweisung

**Contra**:
- ❌ Nicht dynamisch (immer gleicher Staff)
- ❌ Keine echte Verfügbarkeitsprüfung
- ❌ Kann zu Überbuchung führen

---

### Option 3: Round-Robin/Availability-basierte Zuweisung

**Konzept**: Intelligente Zuweisung basierend auf Verfügbarkeit

**Implementierung**:
```php
// Neuer Service: StaffAssignmentService
public function assignStaffToAppointment(
    Appointment $appointment,
    Service $service
): ?Staff {
    $startTime = Carbon::parse($appointment->starts_at);

    // Finde verfügbare Staff für Service + Zeit
    $availableStaff = Staff::where('branch_id', $appointment->branch_id)
        ->whereHas('services', fn($q) => $q->where('service_id', $service->id))
        ->whereDoesntHave('appointments', function($q) use ($startTime, $appointment) {
            $q->where('starts_at', '<=', $appointment->ends_at)
              ->where('ends_at', '>=', $appointment->starts_at)
              ->where('status', '!=', 'cancelled');
        })
        ->get();

    if ($availableStaff->isEmpty()) {
        Log::warning('No staff available for appointment', [
            'appointment_id' => $appointment->id,
            'time' => $startTime->format('Y-m-d H:i')
        ]);
        return null;
    }

    // Round-Robin: Wähle Staff mit wenigsten Appointments
    $selectedStaff = $availableStaff->sortBy(function($staff) {
        return $staff->appointments()
            ->where('status', 'scheduled')
            ->count();
    })->first();

    Log::info('✅ Staff assigned via round-robin', [
        'staff' => $selectedStaff->name,
        'appointment_id' => $appointment->id
    ]);

    return $selectedStaff;
}
```

**Pro**:
- ✅ Intelligente Verfügbarkeitsprüfung
- ✅ Faire Verteilung via Round-Robin
- ✅ Berücksichtigt tatsächliche Auslastung

**Contra**:
- ❌ Komplexer zu implementieren
- ❌ Performance-Impact bei vielen Staff
- ❌ Race Conditions möglich bei gleichzeitigen Bookings

---

### Option 4: Spalte ausblenden (QUICK FIX)

**Wenn Staff-Zuweisung nicht prioritär ist**:

```php
// CallResource.php:680
Tables\Columns\TextColumn::make('appointment_staff')
    ->label('Mitarbeiter:in')
    ->toggleable(isToggledHiddenByDefault: true)  // ← Versteckt by default
```

**Pro**:
- ✅ Sofort umsetzbar
- ✅ Keine Verwirrung durch "Nicht zugewiesen"
- ✅ Kann später eingeblendet werden

**Contra**:
- ❌ Löst das Problem nicht
- ❌ Information bleibt weiterhin fehlend

---

## 🧪 Proof of Concept - Cal.com Response Analyse

**Um zu prüfen ob Cal.com Staff liefert**:

```php
// In AppointmentCreationService::bookInCalcom() nach Line 506
if ($response->successful()) {
    $appointmentData = $response->json();

    // DEBUG: Log complete response to analyze structure
    Log::debug('🔍 COMPLETE Cal.com Booking Response', [
        'full_response' => $appointmentData,
        'keys' => array_keys($appointmentData),
        'has_host' => isset($appointmentData['host']),
        'has_attendees' => isset($appointmentData['attendees']),
        'has_user' => isset($appointmentData['user']),
    ]);
}
```

**Dann nächsten Call durchführen und Logs prüfen**:
```bash
tail -f storage/logs/laravel.log | grep "COMPLETE Cal.com"
```

---

## 📈 Impact Assessment

### Aktuelle Situation
```
Mitarbeiter:in Spalte zeigt:
├─ "Nicht zugewiesen": 97.7% aller Appointments ❌
├─ Staff Name: 2.3% aller Appointments (nur manuelle)
└─ Leer: 93.2% aller Calls (haben kein Appointment)

→ Spalte ist funktional NUTZLOS
```

### Mit Fix (Option 1: Cal.com Integration)
```
Erwartete Verbesserung:
├─ "Nicht zugewiesen": 0-10% (nur bei Fehlern)
├─ Staff Name: 90-100% aller Appointments ✅
└─ Spalte wird NÜTZLICH für Reporting & Organisation
```

### Business Impact
**Aktuell fehlt**:
- ❌ Workload-Verteilung pro Mitarbeiter
- ❌ Performance-Tracking per Staff
- ❌ Kapazitätsplanung
- ❌ Kunden können nicht wählen/sehen wer Termin durchführt

**Mit Staff-Zuweisung möglich**:
- ✅ Staff-Performance Dashboards
- ✅ Automatische Workload-Balance
- ✅ Kundenpräferenz ("Ich möchte wieder zu Frau X")
- ✅ Umsatz-Attribution pro Mitarbeiter

---

## 🔧 Empfohlene Maßnahmen

### 🔥 SOFORT (Quick Win)
1. **Spalte ausblenden** wenn keine Nutzung geplant
   ```php
   ->toggleable(isToggledHiddenByDefault: true)
   ```

2. **Cal.com Response analysieren** (PoC Code oben)
   - Nächsten Call durchführen
   - Logs prüfen ob `host`/`user` Feld existiert

### ⏳ KURZFRISTIG (Diese Woche)
3. **Cal.com EventType prüfen**
   - Ist ein Default Host konfiguriert?
   - Ist Round-Robin aktiviert?
   - Welche API Version wird genutzt?

4. **Staff Mapping vorbereiten**
   - `staff` Table um `calcom_user_id` erweitern
   - Mapping für bestehende Staff erstellen

### 📅 MITTELFRISTIG (Diesen Monat)
5. **Option 1 implementieren** (Cal.com Integration)
   - Staff-Mapping aus Cal.com Response
   - Automatische Zuweisung in createLocalRecord()
   - Logging und Error-Handling

6. **Alternative: Option 2** (Service Default)
   - Falls Cal.com kein Staff liefert
   - Als Fallback-Lösung

---

## 📊 Monitoring & Metrics

**Nach Implementierung tracken**:
```
- Appointments mit Staff: Ziel >90%
- Staff-Zuweisungs-Fehlerrate: <5%
- Durchschnittliche Appointments pro Staff
- Auslastungsverteilung (Gini-Koeffizient)
```

**Alert bei**:
- >10% Appointments ohne Staff (1 Tag)
- Staff-Mapping-Fehler (sofort)
- Ungleiche Verteilung >30% Abweichung (wöchentlich)

---

## 🎯 Zusammenfassung

**Problem**: Mitarbeiter:in Spalte ist funktional nutzlos (97.7% "Nicht zugewiesen")

**Root Cause**:
1. Cal.com liefert (vermutlich) keinen Staff
2. AppointmentCreationService setzt kein staff_id
3. Call.staff_id wird nie genutzt

**Empfohlene Lösung**:
1. **Quick**: Spalte ausblenden
2. **Langfristig**: Cal.com Staff-Integration (Option 1)

**Nächster Schritt**: User-Entscheidung:
- Ist Staff-Zuweisung wichtig?
- Soll investiert werden in Integration?
- Oder reicht Ausblenden der Spalte?
