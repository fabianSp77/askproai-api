# Composite Services - Complete Analysis & Implementation Report

**Date**: 2025-10-23
**Request**: Überprüfung und Konfiguration von Composite Services (Dienstleistungen mit Arbeitspausen)
**Status**: ✅ **PHASE 1 ABGESCHLOSSEN** | ⚠️ **PHASE 2 AUSSTEHEND** (Voice AI Integration)

---

## 🎯 Zusammenfassung

Zwei neue Friseur-Services wurden mit **Composite Service** Funktionalität konfiguriert. Diese Services bestehen aus mehreren Arbeits-Segmenten mit Pausen dazwischen, während denen die Mitarbeiter für andere Buchungen verfügbar sind.

**Services**:
- **Ansatzfärbung, waschen, schneiden, föhnen** (ID: 177) - €85, 2.5h brutto
- **Ansatz, Längenausgleich, waschen, schneiden, föhnen** (ID: 178) - €85, 2.8h brutto

**Ergebnis**:
- ✅ Datenbank-Konfiguration komplett
- ✅ Cal.com Event Types aktualisiert
- ✅ Admin Portal zeigt Segmente an
- ✅ Web-Buchungen funktionieren (CompositeBookingService)
- ❌ Voice AI kann diese Services NOCH NICHT buchen

---

## 🔍 Was wurde analysiert?

### 1. Bestehende Infrastructure

**✅ Datenbank-Schema** (vollständig implementiert):
```sql
services Table:
  - composite (boolean)
  - segments (jsonb)
  - pause_bookable_policy (enum: 'free', 'blocked', 'flexible', 'never')
  - min_staff_required (integer)
```

**✅ Admin Portal UI** (`app/Filament/Resources/ServiceResource.php:144-425`):
- Toggle für Composite-Aktivierung
- Repeater für Segment-Bearbeitung (Name, Dauer, Pause)
- 5 vorgefertigte Templates
- Echtzeit-Dauer-Kalkulation
- Pause-Policy-Auswahl

**✅ Backend Booking Service** (`app/Services/Booking/CompositeBookingService.php`):
- `findCompositeSlots()` - Findet verfügbare Zeitfenster für alle Segmente
- `bookComposite()` - Erstellt Multi-Segment Buchungen mit SAGA Compensation
- `rescheduleComposite()` - Atomare Umterminierung aller Segmente
- `cancelComposite()` - Atomare Stornierung aller Segmente
- Distributed Locking (RC4 Fix) - Verhindert Race Conditions

**✅ Web API Integration** (`app/Http/Controllers/Api/V2/BookingController.php`):
- Automatische Erkennung von Composite Services (Zeile 50: `$service->isComposite()`)
- Routing zu `createCompositeBooking()` vs. `createSimpleBooking()`
- Segment-Aufbau aus Service-Definition

**❌ Voice AI Integration** (`app/Services/Retell/AppointmentCreationService.php`):
- **KEINE Composite-Logik vorhanden**
- Verwendet nur einfache Buchungs-API
- Kann Composite Services nicht buchen

### 2. Referenz-Services (bereits mit Composite)

**Damenhaarschnitt** (ID: 41):
```json
{
  "composite": true,
  "duration_minutes": 120,
  "pause_bookable_policy": "free",
  "segments": [
    {"key": "A", "name": "Hair Preparation", "duration": 30, "gap_after": 15},
    {"key": "B", "name": "Treatment Application", "duration": 20, "gap_after": 30},
    {"key": "C", "name": "Final Styling", "duration": 25, "gap_after": 0}
  ]
}
```

**Herrenhaarschnitt** (ID: 42):
```json
{
  "composite": true,
  "duration_minutes": 150,
  "pause_bookable_policy": "blocked",  // ← Staff NICHT verfügbar während Pausen
  "segments": [
    {"key": "A", "name": "Initial Assessment", "duration": 45, "gap_after": 10},
    {"key": "B", "name": "Main Therapy", "duration": 60, "gap_after": 15},
    {"key": "C", "name": "Review & Planning", "duration": 20, "gap_after": 0}
  ]
}
```

---

## 🔧 Was wurde konfiguriert?

### Service 177: Ansatzfärbung, waschen, schneiden, föhnen

**Vor der Konfiguration**:
```
composite: FALSE
segments: NULL
pause_bookable_policy: "never"
duration_minutes: 120
```

**Nach der Konfiguration**:
```json
{
  "composite": true,
  "pause_bookable_policy": "free",
  "duration_minutes": 150,
  "segments": [
    {
      "key": "A",
      "name": "Ansatzfärbung auftragen",
      "duration": 30,
      "gap_after": 30,
      "preferSameStaff": true
    },
    {
      "key": "B",
      "name": "Auswaschen",
      "duration": 15,
      "gap_after": 0,
      "preferSameStaff": true
    },
    {
      "key": "C",
      "name": "Schneiden",
      "duration": 30,
      "gap_after": 15,
      "preferSameStaff": true
    },
    {
      "key": "D",
      "name": "Föhnen & Styling",
      "duration": 30,
      "gap_after": 0,
      "preferSameStaff": true
    }
  ]
}
```

**Zeitrechnung**:
- Arbeitszeit (netto): **105 min**
- Pausen (Staff verfügbar): **45 min**
- Gesamt (brutto): **150 min** (2.5h)

**Cal.com Event Type** (ID: 3719865):
- Dauer aktualisiert: 120 min → **150 min** ✅

---

### Service 178: Ansatz, Längenausgleich, waschen, schneiden, föhnen

**Nach der Konfiguration**:
```json
{
  "composite": true,
  "pause_bookable_policy": "free",
  "duration_minutes": 170,
  "segments": [
    {
      "key": "A",
      "name": "Ansatzfärbung & Längenausgleich auftragen",
      "duration": 40,
      "gap_after": 30,
      "preferSameStaff": true
    },
    {
      "key": "B",
      "name": "Auswaschen",
      "duration": 15,
      "gap_after": 0,
      "preferSameStaff": true
    },
    {
      "key": "C",
      "name": "Schneiden mit Längenausgleich",
      "duration": 40,
      "gap_after": 15,
      "preferSameStaff": true
    },
    {
      "key": "D",
      "name": "Föhnen & Styling",
      "duration": 30,
      "gap_after": 0,
      "preferSameStaff": true
    }
  ]
}
```

**Zeitrechnung**:
- Arbeitszeit (netto): **125 min**
- Pausen (Staff verfügbar): **45 min**
- Gesamt (brutto): **170 min** (2.8h)

**Cal.com Event Type** (ID: 3719866):
- Dauer aktualisiert: 120 min → **170 min** ✅

---

## ⚙️ Wie funktionieren Composite Services?

### Konzept

**Problem**: Friseur-Services wie Färbungen haben Wartezeiten (z.B. Farbe einwirken lassen), während denen:
- Kunde wartet
- Mitarbeiter NICHTS zu tun hat
- Mitarbeiter könnte andere Kunden bedienen (z.B. Schnitt)

**Lösung**: Service in Segmente aufteilen mit Pausen dazwischen.

### Technische Implementierung

```
┌─────────────────────────────────────────────────────────────┐
│  COMPOSITE SERVICE: Ansatzfärbung (150 min brutto)          │
└─────────────────────────────────────────────────────────────┘

Zeitstrahl:
10:00 ─────────────────────────────────────────────────> 12:30

├─ Segment A ─┤── Pause ──├─ B ─├─ C ─┤─ Pause ─├─ D ─┤
   30 min        30 min     15min  30min   15 min   30min
   (Färben)      (Staff     (Wash) (Cut)   (Staff   (Dry)
                 frei!)                     frei!)

Cal.com Bookings:
  → Booking 1: 10:00 - 10:30 (Segment A)
  → [10:30 - 11:00: Staff verfügbar für anderen Kunden]
  → Booking 2: 11:00 - 11:15 (Segment B)
  → Booking 3: 11:15 - 11:45 (Segment C)
  → [11:45 - 12:00: Staff verfügbar]
  → Booking 4: 12:00 - 12:30 (Segment D)

Datenbank:
  → 1 Appointment Record mit composite_group_uid
  → segments Array mit allen Segment-Details
  → is_composite: true
```

### Pause Bookable Policy

**"free"** (gewählt für Ansatz-Services):
- Staff ist **verfügbar** für andere Buchungen während Pausen
- Beispiel: Während Farbe einwirkt → anderer Kunde bekommt Schnitt

**"blocked"**:
- Staff ist **nicht verfügbar** während Pausen
- Beispiel: Therapeutische Behandlung → Staff bleibt beim Kunden

**"flexible"**:
- System entscheidet basierend auf Verfügbarkeit

### Booking-Flow (Web API)

```php
// app/Http/Controllers/Api/V2/BookingController.php

public function create(CreateBookingRequest $request)
{
    $service = Service::find($request->service_id);

    if ($service->isComposite()) {
        // Route zu CompositeBookingService
        return $this->createCompositeBooking($service, ...);
    } else {
        // Normale Buchung
        return $this->createSimpleBooking($service, ...);
    }
}

private function createCompositeBooking(Service $service, ...)
{
    // 1. Segment-Struktur aus Service-Definition bauen
    $segments = $this->buildSegmentsFromService($service, $data);

    // 2. CompositeBookingService verwenden
    $appointment = $this->compositeService->bookComposite([
        'segments' => $segments,
        'customer' => $customer,
        // ...
    ]);

    // 3. SAGA Compensation bei Fehler
    // 4. Atomare Buchung aller Segmente
    // 5. Distributed Locking (RC4 Fix)

    return $appointment;
}
```

### SAGA Compensation Pattern

**Problem**: Was, wenn Segment C nicht verfügbar ist, aber A und B schon gebucht?

**Lösung** (app/Services/Booking/CompositeBookingService.php:127-253):
```php
public function bookComposite(array $data): Appointment
{
    $bookings = [];

    try {
        // Reverse order booking (B → A) für einfacheres Rollback
        foreach (array_reverse($segments) as $segment) {
            $booking = $this->calcom->createBooking(...);
            $bookings[] = $booking;
        }

        // Alle erfolgreich → Appointment erstellen
        return Appointment::create([...]);

    } catch (Exception $e) {
        // SAGA Compensation: Rollback aller bisherigen Bookings
        foreach ($bookings as $booking) {
            $this->calcom->cancelBooking($booking['id']);
        }

        throw $e; // Re-throw für User Feedback
    }
}
```

---

## 📊 Was funktioniert jetzt?

### ✅ Admin Portal

**Location**: https://api.askproai.de/admin/services

**Funktionen**:
1. Service bearbeiten → "Composite" Tab
2. Segmente ansehen und bearbeiten
3. Neue Segmente hinzufügen (Repeater UI)
4. Pause-Policy ändern
5. Templates verwenden (5 vorgefertigte)

**Sichtbar**:
- Composite: TRUE
- 4 Segmente pro Service
- Gesamt-Dauer (150/170 min)
- Pause Policy: free

### ✅ Cal.com Dashboard

**Location**: https://app.cal.com/event-types?teamId=34209

**Sichtbar**:
- Event Type "Ansatzfärbung..." - 150 Minuten
- Event Type "Ansatz, Längenausgleich..." - 170 Minuten
- Scheduling Type: Round Robin
- Hosts: 2 Mitarbeiter

**WICHTIG**: Cal.com zeigt nur Gesamt-Dauer, keine Segment-Details!

### ✅ Web API Buchungen

**Endpoint**: `POST /api/v2/bookings`

**Request**:
```json
{
  "service_id": 177,
  "customer": {"name": "Max Mustermann", "email": "max@example.com"},
  "start": "2025-10-25T10:00:00+01:00",
  "branch_id": "...",
  "timeZone": "Europe/Berlin"
}
```

**Prozess**:
1. BookingController erkennt Composite Service
2. `buildSegmentsFromService()` erstellt Segment-Array
3. CompositeBookingService erstellt 4 Cal.com Bookings
4. 1 Appointment Record mit `composite_group_uid`
5. Bei Fehler: SAGA Compensation rollback

**Response**:
```json
{
  "appointment_id": "...",
  "composite_uid": "abc123...",
  "status": "booked",
  "starts_at": "2025-10-25T10:00:00+01:00",
  "ends_at": "2025-10-25T12:30:00+01:00",
  "segments": [
    {"key": "A", "starts_at": "10:00", "ends_at": "10:30", "staff_id": "..."},
    {"key": "B", "starts_at": "11:00", "ends_at": "11:15", "staff_id": "..."},
    {"key": "C", "starts_at": "11:15", "ends_at": "11:45", "staff_id": "..."},
    {"key": "D", "starts_at": "12:00", "ends_at": "12:30", "staff_id": "..."}
  ]
}
```

---

## ❌ Was funktioniert NOCH NICHT?

### Voice AI (Retell) Buchungen

**Problem**: `app/Services/Retell/AppointmentCreationService.php` hat KEINE Composite-Logik

**Aktuelles Verhalten**:
```php
// AppointmentCreationService.php (vereinfacht)

public function createAppointment(array $data)
{
    // Verwendet nur CalcomService::createSingleBooking()
    // Keine Prüfung auf $service->isComposite()
    // Erstellt nur 1 Booking, nicht mehrere Segmente

    return $this->calcomService->createBooking([
        'eventTypeId' => $service->calcom_event_type_id,
        'start' => $start,
        'end' => $end,  // ← Nur 1 End-Zeit, keine Segmente!
        // ...
    ]);
}
```

**Was passiert, wenn Voice AI eine Ansatzfärbung bucht?**
- ❌ Erstellt nur 1 Cal.com Booking für 150 Minuten
- ❌ Keine Segmente
- ❌ Staff NICHT verfügbar während Pausen
- ❌ Kein composite_group_uid
- ❌ Funktioniert nicht wie intended

---

## 🚀 Phase 2: Voice AI Integration (TODO)

### Was muss gemacht werden?

**1. AppointmentCreationService erweitern** (45-60 min):

```php
// app/Services/Retell/AppointmentCreationService.php

public function createAppointment(array $data)
{
    $service = Service::find($data['service_id']);

    // NEU: Composite-Prüfung
    if ($service->isComposite()) {
        return $this->createCompositeAppointment($service, $data);
    }

    // Alte Logik für einfache Services
    return $this->createSimpleAppointment($service, $data);
}

private function createCompositeAppointment(Service $service, array $data)
{
    // Integration mit CompositeBookingService
    $compositeService = app(CompositeBookingService::class);

    // Segment-Struktur bauen (wie BookingController)
    $segments = $this->buildSegmentsFromService($service, $data);

    // Composite-Buchung erstellen
    return $compositeService->bookComposite([
        'company_id' => $data['company_id'],
        'branch_id' => $data['branch_id'],
        'service_id' => $service->id,
        'customer_id' => $data['customer_id'],
        'segments' => $segments,
        'timeZone' => 'Europe/Berlin',
        'source' => 'retell_ai'
    ]);
}

private function buildSegmentsFromService(Service $service, array $data)
{
    // Code von BookingController übernehmen
    // Baut Segment-Array aus Service-Definition
}
```

**2. Retell Agent Prompt aktualisieren** (15-30 min):

Agent muss wissen, dass Composite Services Wartezeiten haben:

```
System Prompt Extension:

WICHTIG - Composite Services (Dienstleistungen mit Wartezeiten):

Einige Services bestehen aus mehreren Arbeitsschritten mit Wartezeiten dazwischen:

- "Ansatzfärbung, waschen, schneiden, föhnen" (€85, ca. 2.5 Stunden)
  → 30 Min Färben, 30 Min warten (Farbe einwirken), 15 Min waschen, 30 Min schneiden, 30 Min föhnen
  → Kunde wartet während Einwirkzeit im Salon

- "Ansatz, Längenausgleich, waschen, schneiden, föhnen" (€85, ca. 3 Stunden)
  → Ähnlich, aber längere Schnitt-Phase

Beim Buchen:
- Erkläre dem Kunden die Wartezeiten
- "Es gibt Wartezeiten, während die Farbe einwirkt. Sie bleiben im Salon und können z.B. Zeitschriften lesen."
- Bestätige die Gesamt-Dauer (2.5-3 Stunden)

function_calls:
- book_appointment: Funktioniert automatisch für Composite Services
- check_availability: Sucht nach Zeitfenstern für die gesamte Dauer
```

**3. Testing** (30 min):

```bash
# Test 1: Voice AI Buchung
./scripts/monitoring/voice_call_monitoring.sh

Test-Anruf:
"Ich möchte eine Ansatzfärbung mit Schnitt und Föhnen buchen"

Erwartetes Verhalten:
1. Service wird erkannt (ServiceNameExtractor)
2. Agent erklärt Wartezeiten
3. check_availability → findet 150-min Slot
4. book_appointment → erstellt 4 Segmente
5. Bestätigung mit composite_group_uid

# Test 2: Admin Portal Verification
https://api.askproai.de/admin/appointments
→ Appointment anzeigen
→ Segmente sichtbar
→ composite_group_uid vorhanden

# Test 3: Staff Availability während Pausen
Während Segment A läuft (10:00-10:30):
→ Andere Buchung für 10:45 sollte möglich sein (während Pause)
```

---

## 📝 Lessons Learned

### 1. Cal.com zeigt nur Gesamt-Dauer

**Erkenntnis**: Cal.com Event Types haben KEINE Segment-Funktion

**Lösung**:
- Cal.com: Gesamt-Dauer (150 min)
- Backend: Segment-Logik (4× separate Bookings)
- CompositeBookingService koordiniert alles

### 2. pause_bookable_policy ist kritisch

**"free"** vs. **"blocked"** macht großen Unterschied:
- **free**: Staff kann 2 Kunden gleichzeitig bedienen (mit Wartezeiten)
- **blocked**: Staff bei einem Kunden, auch während Pausen

Für Friseur: **"free"** ist richtig (Farbe einwirken → anderer Kunde Schnitt)

### 3. SAGA Compensation ist notwendig

Ohne SAGA Pattern:
- Segment A gebucht
- Segment B schlägt fehl
- Segment A bleibt gebucht → inkonsistent!

Mit SAGA:
- Bei Fehler: Rollback aller bisherigen Bookings
- Atomare Operation: Alles oder nichts

### 4. Voice AI benötigt separate Integration

**Fehlannahme**: "BookingController funktioniert → Voice AI auch"

**Realität**: AppointmentCreationService ist separater Code-Pfad, braucht eigene Composite-Logik

---

## 📊 Statistik

**Services Total**: 18 (Friseur 1)
- Alte Services (mit Composite): 2 (IDs 41, 42)
- Neue Composite Services: 2 (IDs 177, 178) ← **NEU KONFIGURIERT**
- Einfache Services: 14

**Datenbank Updates**:
- Services aktualisiert: 2
- Felder gesetzt: composite, segments, pause_bookable_policy, duration_minutes

**Cal.com Updates**:
- Event Types aktualisiert: 2
- Neue Dauern: 150 min, 170 min

**Code Locations**:
- Service Model: `app/Models/Service.php:306-317`
- Admin UI: `app/Filament/Resources/ServiceResource.php:144-425`
- Booking Service: `app/Services/Booking/CompositeBookingService.php`
- Web API: `app/Http/Controllers/Api/V2/BookingController.php:50-104`
- **TODO**: `app/Services/Retell/AppointmentCreationService.php` (keine Composite-Logik)

---

## 📞 Testing & Verification

### Admin Portal Check

```bash
1. https://api.askproai.de/admin/services öffnen
2. Service ID 177 oder 178 anklicken
3. "Composite Services" Tab sichtbar
4. 4 Segmente angezeigt
5. Pause Policy: "free"
6. Gesamt-Dauer korrekt (150/170 min)
```

### Database Verification

```bash
php verify_composite_config.php
```

Erwartetes Ergebnis:
```
✅ composite: TRUE
✅ 4 Segmente mit korrekten Dauern
✅ pause_bookable_policy: free
✅ Zeitrechnung: 105/125 min work + 45 min gaps
```

### Cal.com Verification

```bash
https://app.cal.com/event-types?teamId=34209
```

Suche nach:
- "Ansatzfärbung..." → 150 Minuten
- "Ansatz, Längenausgleich..." → 170 Minuten

### Web API Test (funktioniert jetzt)

```bash
curl -X POST https://api.askproai.de/api/v2/bookings \
  -H "Authorization: Bearer ..." \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": 177,
    "customer": {"name": "Test User", "email": "test@example.com"},
    "start": "2025-10-26T10:00:00+01:00",
    "branch_id": "...",
    "timeZone": "Europe/Berlin"
  }'
```

Erwartete Response:
```json
{
  "success": true,
  "data": {
    "appointment_id": "...",
    "composite_uid": "...",
    "segments": [...] // 4 Segmente
  }
}
```

### Voice AI Test (funktioniert NOCH NICHT)

```bash
# Wird in Phase 2 entwickelt
./scripts/monitoring/voice_call_monitoring.sh
```

Aktuell: ❌ Erstellt nur einfache Buchung (nicht Composite)

---

## 🔄 Nächste Schritte

### Sofort verfügbar (Phase 1 ✅):
- ✅ Admin Portal: Segmente bearbeiten
- ✅ Web API: Composite-Buchungen erstellen
- ✅ Cal.com: Korrekte Dauern angezeigt

### Phase 2 (TODO):
1. **AppointmentCreationService erweitern** (45-60 min)
2. **Retell Agent Prompt aktualisieren** (15-30 min)
3. **End-to-End Tests** (30 min)
4. **Dokumentation finalisieren** (15 min)

**Gesamtaufwand Phase 2**: 1.5-2 Stunden

---

## 📖 Dokumentation & Support

**Erstellt von**: Claude Code
**Datum**: 2025-10-23
**Scripts**:
- `configure_composite_services.php` - DB Konfiguration
- `update_calcom_composite_durations.php` - Cal.com Update
- `verify_composite_config.php` - Verification

**Related Docs**:
- `CONSISTENCY_CHECK_REPORT_2025-10-23.md` - Service Consistency Report
- `SERVICE_CREATION_INDEX.md` - Service Creation Guide
- `claudedocs/02_BACKEND/Services/` - Service Architecture

**Bei Fragen**:
- Admin Portal: https://api.askproai.de/admin/services
- Cal.com: https://app.cal.com/event-types?teamId=34209
- Code: `app/Services/Booking/CompositeBookingService.php`

---

**Report Status**: ✅ Phase 1 Complete | ⚠️ Phase 2 Pending
**Quality**: Production Ready (Web API) | Development Needed (Voice AI)
**System**: Partial Deployment 🚧
