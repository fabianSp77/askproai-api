# Dauerwelle Composite Service Fix - 2025-11-13

**Problem**: Dauerwelle-Buchung schlägt fehl - Cal.com Booking erfolgreich, aber NICHT in DB gespeichert
**Root Cause**: Email UNIQUE constraint violation in `createAnonymousCustomer` Funktion
**Solution**: NULL statt empty string für Email (GLEICHER Fix wie vorhin, aber andere Funktion!)
**Status**: ✅ FIXED

---

## Problem Analysis

### User Report
1. Testanruf für Dauerwelle (Composite Service)
2. Agent sagte: "Es gab ein technisches Problem"
3. **ABER**: E-Mail wurde empfangen mit Cal.com Booking ✉️
4. Vermutung: Termin NICHT in unserer DB gespeichert

### Investigation

#### Call Details
```
Call ID: call_80f38b317318a93b3f2988738d4
Service: Dauerwelle (Composite Service)
Agent: agent_45daa54928c5768b52ba3db736 (V114)
Zeit: 18:00 morgen (2025-11-14 18:00)
```

#### Function Calls
```
✅ get_current_context: SUCCESS
✅ check_customer: SUCCESS (new_customer)
✅ check_availability_v17: SUCCESS (Alternativen angeboten)
❌ start_booking: FAILED
```

#### Error Message
```
"Die Terminbuchung wurde im Kalender erstellt, aber es gab ein Problem beim Speichern.
Bitte kontaktieren Sie uns direkt zur Bestätigung. Booking-ID: [REDACTED]"
```

### Root Cause Found

**Error Log** (`/var/www/api-gateway/storage/logs/BOOKING_ERROR.txt`):
```
Error: SQLSTATE[23000]: Integrity constraint violation: 1062
Duplicate entry '' for key 'customers_email_unique'

SQL: insert into `customers` (..., email, ...) values (..., , ...)

File: AppointmentCustomerResolver.php:159
Function: createAnonymousCustomer()
```

**Das Problem:**
- Agent ruft `start_booking` auf für anonymen Anrufer (from_number: "anonymous")
- Cal.com Booking wird ERFOLGREICH erstellt → E-Mail versendet ✅
- Dann versucht System, Customer in DB zu speichern
- `email` ist empty string `''` → UNIQUE constraint violation ❌
- Transaction schlägt fehl → Kein Appointment in DB gespeichert ❌

---

## Why Previous Fix Didn't Work

Wir hatten vorhin einen Email-NULL-Fix gemacht in **Zeile 197-209**:
```php
// AppointmentCustomerResolver.php Lines 197-209
private function ensureCustomerFromCall(Call $call, string $name, ?string $email): Customer
{
    $emailValue = (!empty($email) && $email !== '') ? $email : null;  // ✅ Fix here
    ...
}
```

**ABER**: Anonyme Anrufer verwenden eine ANDERE Funktion:
```php
// AppointmentCustomerResolver.php Lines 141-159
private function createAnonymousCustomer(Call $call, string $name, ?string $email): Customer
{
    $customer->forceFill([
        'email' => $email,  // ❌ No fix here - still uses empty string!
        ...
    ]);
}
```

**Resultat:**
✅ Normale Anrufer (mit Telefonnummer): Fix funktioniert
❌ Anonyme Anrufer (`from_number: "anonymous"`): Fix NICHT angewendet → **Fehler tritt auf!**

---

## Solution Implemented

### Fix #7: Email NULL in createAnonymousCustomer
**File**: `/var/www/api-gateway/app/Services/Retell/AppointmentCustomerResolver.php` (Lines 141-158)

```php
private function createAnonymousCustomer(Call $call, string $name, ?string $email): Customer
{
    // Generate unique phone placeholder
    $uniquePhone = 'anonymous_' . time() . '_' . substr(md5($name . $call->id), 0, 8);

    // 🔧 FIX 2025-11-13: Use NULL instead of empty string for email (UNIQUE constraint)
    $emailValue = (!empty($email) && $email !== '') ? $email : null;

    $customer = new Customer();
    $customer->company_id = $call->company_id;
    $customer->forceFill([
        'name' => $name,
        'email' => $emailValue,  // NULL instead of empty string
        'phone' => $uniquePhone,
        'source' => 'retell_webhook_anonymous',
        'status' => 'active',
        'notes' => '⚠️ Created from anonymous call - phone number unknown'
    ]);

    $customer->save();
    ...
}
```

---

## Composite Service Handling

### Was ist ein Composite Service?

**Dauerwelle** ist ein mehrstufiger Service mit Wartezeiten:

```
Dauerwelle (~2.5h gesamt):
1. Phase: Farbe auftragen (30min) - 👤 Staff Required
2. WAIT: Einwirkzeit (30min) - ⏳ No Staff Required
3. Phase: Waschen (15min) - 👤 Staff Required
4. Phase: Schneiden (30min) - 👤 Staff Required
5. WAIT: Trocknen (15min) - ⏳ No Staff Required
6. Phase: Föhnen (30min) - 👤 Staff Required
```

### Composite Service in DB

**Service Table:**
```php
composite: true
segments: JSON [
    {name: "Farbe auftragen", duration: 30, staff_required: true},
    {name: "Einwirkzeit", duration: 30, staff_required: false},
    ...
]
```

**After Booking → appointment_phases Table:**
```
appointment_id | segment_name      | duration_minutes | staff_required | starts_at | ends_at   | sequence_order
1819          | Farbe auftragen   | 30              | 1              | 18:00    | 18:30     | 1
1819          | Einwirkzeit       | 30              | 0              | 18:30    | 19:00     | 2
1819          | Waschen           | 15              | 1              | 19:00    | 19:15     | 3
...
```

### Composite Service Code Flow

```php
// Check if service is composite
if ($service->composite && !empty($service->segments)) {
    // Phase-aware availability checking
    // Cal.com API call with proper duration calculation
    ...
}

// After successful Cal.com booking
if ($service->composite && !empty($service->segments)) {
    // Create appointment_phases records
    foreach ($service->segments as $index => $segment) {
        AppointmentPhase::create([
            'appointment_id' => $appointment->id,
            'segment_name' => $segment['name'],
            'duration_minutes' => $segment['duration_minutes'],
            'staff_required' => $segment['staff_required'],
            'sequence_order' => $index + 1,
            'starts_at' => ...,
            'ends_at' => ...
        ]);
    }
}
```

**Status**: ✅ Code vorhanden für Composite Services

---

## Testing

### Test Scenario
```
Service: Dauerwelle (Composite)
Datum: morgen (2025-11-14)
Zeit: 18:00
Anrufer: Hans Schuster
Telefon: anonymous (Testanruf)
```

### Expected Result (After Fix)

1. ✅ Cal.com Booking erstellt
2. ✅ Customer in DB gespeichert (mit email: NULL)
3. ✅ Appointment in DB erstellt
4. ✅ appointment_phases erstellt (6 Phasen für Dauerwelle)
5. ✅ E-Mail versendet
6. ✅ Agent sagt: "Perfekt! Ihr Termin ist gebucht."

### Verification Checklist

Nach erfolgreicher Buchung überprüfen:

```sql
-- 1. Check Appointment
SELECT id, service_id, starts_at, ends_at, duration_minutes, status, calcom_v2_booking_id
FROM appointments
WHERE call_id = (SELECT id FROM calls WHERE retell_call_id = 'call_80f38b317318a93b3f2988738d4')
LIMIT 1;

-- 2. Check Composite Phases
SELECT segment_name, duration_minutes, staff_required, starts_at, ends_at, sequence_order
FROM appointment_phases
WHERE appointment_id = [APPOINTMENT_ID]
ORDER BY sequence_order;

-- 3. Check Customer
SELECT id, name, email, phone, source
FROM customers
WHERE phone LIKE 'anonymous_%'
ORDER BY created_at DESC
LIMIT 1;
```

**Expected Phases:**
```
1. Farbe auftragen   30min  staff:YES  18:00-18:30
2. Einwirkzeit       30min  staff:NO   18:30-19:00
3. Waschen           15min  staff:YES  19:00-19:15
4. Schneiden         30min  staff:YES  19:15-19:45
5. Trocknen          15min  staff:NO   19:45-20:00
6. Föhnen            30min  staff:YES  20:00-20:30
```

---

## All Fixes Summary (Session 2025-11-13)

1. ✅ **German Date Parsing** (DateTimeParser.php:105-121)
2. ✅ **Parameter Name Mapping** (RetellFunctionCallHandler.php:1244-1251)
3. ✅ **Email NULL Constraint #1** (AppointmentCustomerResolver.php:197-209) - für normale Anrufer
4. ✅ **Phone Number Assignment** (Manual Retell Dashboard)
5. ✅ **Route Alias: current-context** (routes/api.php:89-95)
6. ✅ **Route Alias: check-customer** (routes/api.php:97-103)
7. ✅ **Email NULL Constraint #2** (AppointmentCustomerResolver.php:141-158) ← DIESER FIX - für anonyme Anrufer

---

## Next Steps

### Sofort Testen
**Mach einen neuen Testanruf mit Dauerwelle!**

**Erwartetes Verhalten:**
1. ✅ Agent versteht "Dauerwelle morgen 18 Uhr"
2. ✅ Agent prüft Verfügbarkeit
3. ✅ Agent bucht Termin
4. ✅ Agent sagt: "Perfekt! Ihr Termin ist gebucht."
5. ✅ Du bekommst E-Mail mit allen Phasen
6. ✅ Termin in DB mit 6 Phasen gespeichert
7. ✅ Cal.com zeigt korrekten Zeitblock (2.5h)

### Verify in DB
```bash
php /tmp/analyze_latest_dauerwelle_call.php
```

Sollte zeigen:
- ✅ Appointment gefunden
- ✅ 6 Phasen korrekt erstellt
- ✅ Gesamt: 150 Minuten (2.5h)
- ✅ Mit Personal: 105 Minuten
- ✅ Wartezeit: 45 Minuten

---

## Known Issues

### Warum bekam ich trotzdem E-Mail?

**Cal.com sendet E-Mail VOR unserer DB-Speicherung:**

```
Flow:
1. Cal.com API → Booking erstellen ✅
2. Cal.com → E-Mail senden ✅
3. Unser Code → Customer in DB speichern ❌ (Fehler!)
4. Unser Code → Appointment in DB speichern ⏹️ (nie erreicht)
```

**Resultat:**
- ✅ Cal.com hat Booking + E-Mail gesendet
- ❌ Wir haben KEINEN Appointment-Record in DB
- ❌ Keine Phasen in DB

**Nach dem Fix:**
- ✅ Cal.com hat Booking + E-Mail gesendet
- ✅ Wir haben Appointment-Record in DB
- ✅ Phasen korrekt in DB gespeichert

---

## Deployment

```bash
# Fix angewendet - keine weiteren Schritte nötig
# Code ist sofort aktiv
```

---

**Fix abgeschlossen**: 2025-11-13 12:15 CET
**Fixed by**: Claude Code
**Test Result**: ⏳ WAITING FOR USER TEST
**Status**: ✅ **READY FOR TESTING**

---

## Summary

**Problem**: Email UNIQUE constraint violation bei anonymen Anrufern
**Ursache**: `createAnonymousCustomer` verwendete empty string statt NULL
**Lösung**: NULL statt empty string (gleicher Fix wie vorhin, andere Funktion)
**Impact**: Dauerwelle + alle anderen Composite Services sollten jetzt funktionieren

**Bitte teste nochmal mit Dauerwelle!** ☎️
