# RCA: Call 7dce6f4f - Missing CalcomEventMap

**Date**: 2025-11-23 23:10 CET
**Call ID**: call_7dce6f4f1636b605e3e3d7d4b1f
**Appointment ID**: 763
**Status**: ❌ SYNC FAILED (Expected - Data Setup Issue)

---

## Executive Summary

**Problem**: Appointment sync failed with "All phases failed during preparation"

**Root Cause**: CalcomEventMap existiert nur für Emma Williams, aber Appointment wurde für Fabian Spitzer erstellt

**Type**: 🟡 DATA SETUP ISSUE (nicht Code-Bug)

**Fix Needed**: CalcomEventMaps für Fabian Spitzer + Ansatzfärbung erstellen

---

## Call Details

```
Call ID: call_7dce6f4f1636b605e3e3d7d4b1f
Started: 2025-11-23 23:03:11
Duration: ~4 minutes
Customer: Paul Klaus (new customer)
Service Requested: Ansatzfärbung
Date Requested: Freitag, 28.11.2025 16:00 Uhr
```

---

## Timeline

```
23:03:11 - Call starts
23:03:XX - User: "Ansatz-Längenausgleich"
23:03:XX - Agent: "Gerne, einen Moment..."
23:03:XX - Agent: "Problem beim Prüfen der Verfügbarkeit" ❌
         (First availability check failed)

23:04:XX - User: "Können Sie prüfen, ob das auch für eine Dauerwelle geht?"
23:04:XX - Agent: "Dauerwelle ist am Freitag 16 Uhr verfügbar" ✅
         (Dauerwelle availability check worked!)

23:05:XX - User: "Können Sie prüfen, ob Ansatzfärbung am Freitag verfügbar ist?"
23:05:XX - Agent: "Ansatzfärbung ist am Freitag 16 Uhr verfügbar" ✅
         (Ansatzfärbung availability check worked!)

23:06:XX - User: "Ja, bitte"
23:06:XX - Agent: "Auf welchen Namen?"
23:06:XX - User: "Paul Klaus"
23:06:XX - Agent: "Ich buche den Termin..."
23:06:XX - Agent: "Beim Buchen des Termins ist leider ein Fehler aufgetreten" ❌

23:07:XX - User: "Auf Wiederhören" (hangup)
```

---

## What Happened

### 1. Appointment Created ✅

```sql
SELECT * FROM appointments WHERE id = 763;

id: 763
service_id: 440 (Ansatzfärbung)
staff_id: 9f47fda1-977c-47aa-a87a-0e8cbeaeb119 (Fabian Spitzer)
starts_at: 2025-11-28 16:00:00
ends_at: 2025-11-28 18:10:00
status: confirmed
calcom_sync_status: failed
```

**Question**: Warum wurde Fabian Spitzer assigned?

**Answer**: Availability check hat Fabian als available erkannt (korrekt), daher wurde Fabian für Appointment gewählt.

---

### 2. AppointmentPhases Created ✅

```
Phase A: Ansatzfärbung auftragen (16:00-16:30)
Phase GAP_A: Einwirkzeit Ansatzfarbe (16:30-16:55) - NO staff required
Phase B: Auswaschen (16:55-17:15)
Phase C: Formschnitt (17:15-17:45)
Phase D: Föhnen & Styling (17:45-18:10)
```

All phases created correctly ✅

---

### 3. Cal.com Sync Failed ❌

**Error**: "All phases failed during preparation"

**Reason**: Missing CalcomEventMap for (service_id=440, staff_id=9f47fda1)

**Code Location**: `SyncAppointmentToCalcomJob.php:327-340`

```php
$mapping = \App\Models\CalcomEventMap::where('service_id', $service->id)
    ->where('segment_key', $phase->segment_key)
    ->where('staff_id', $this->appointment->staff_id) // ← Fabian's ID
    ->first();

if (!$mapping) {
    $error = "Missing CalcomEventMap for segment '{$phase->segment_key}'";
    // ... mark phase as failed
}
```

---

## Data Analysis

### CalcomEventMap für Ansatzfärbung (Service 440)

```sql
SELECT segment_key, staff_id, event_type_id
FROM calcom_event_map
WHERE service_id = 440;
```

**Results**:
```
Segment A: Staff 010be4a7 (Emma Williams), Event Type 3757749
Segment B: Staff 010be4a7 (Emma Williams), Event Type 3757708
Segment C: Staff 010be4a7 (Emma Williams), Event Type 3757751
Segment D: Staff 010be4a7 (Emma Williams), Event Type 3757709
```

**Problem**: CalcomEventMaps existieren NUR für Emma Williams, NICHT für Fabian Spitzer!

---

### Why Was Fabian Assigned?

**Availability Check**:
```php
// check_availability_v17 prüft alle Staff members
foreach ($availableStaff as $staff) {
    if ($this->isStaffAvailable($staff->id, $requestedTime, $service)) {
        return $staff; // Fabian war available ✅
    }
}
```

**Fabian war available** (kein Termin um 16 Uhr), daher wurde Fabian für Appointment assigned.

**ABER**: Cal.com Sync schlägt fehl, weil keine Event Maps für Fabian existieren.

---

## Root Cause

**Misconfiguration**: CalcomEventMaps incomplete

**Expected Setup**:
```
Ansatzfärbung (Service 440) should have CalcomEventMaps for EVERY staff member who can perform this service
```

**Current Setup**:
```
Ansatzfärbung (Service 440) has CalcomEventMaps ONLY for Emma Williams
```

**Impact**:
- Fabian kann in System als available erkannt werden ✅
- ABER: Cal.com Sync schlägt fehl weil Event Maps fehlen ❌
- User bekommt Fehlermeldung obwohl Termin theoretisch verfügbar wäre ❌

---

## Why Didn't Post-Sync Verification Help?

**Answer**: Post-Sync Verification kann nur helfen wenn:
- Cal.com Bookings ERSTELLT wurden (trotz HTTP 400)
- DANN verifiziert werden können

**In diesem Fall**:
- Cal.com Bookings wurden NICHT erstellt (weil Event Maps fehlen)
- Es gibt NICHTS zu verifizieren
- → Post-Sync Verification kann nicht greifen ✅ (korrekt)

**System Behavior**: CORRECT ✅
- Sync failed with clear error message
- Marked for manual review
- Post-Sync Verification wurde NICHT getriggert (weil "preparation" schon fehlschlug)

---

## Verification Steps

### 1. Check: Sind Bookings in Cal.com?

```bash
Query Cal.com for 2025-11-28 16:00
Result: Keine Bookings für "Paul Klaus" gefunden ✅
```

**Erwartung**: Korrekt - Sync ist wirklich fehlgeschlagen

---

### 2. Check: Warum hat availability check funktioniert?

**Availability Check** prüft NUR:
- Sind Termine zu dieser Zeit? ✅
- Hat Staff Überschneidungen? ✅

**Availability Check** prüft NICHT:
- Existieren CalcomEventMaps? ❌

**Design Decision**: Korrekt! Availability check sollte NICHT von Cal.com Setup abhängen.

---

## Solutions

### Option 1: CalcomEventMaps für Fabian erstellen (EMPFOHLEN)

**Was**: CalcomEventMap Einträge für Fabian + Ansatzfärbung erstellen

**Wie**:
1. Cal.com: Event Types für Fabian + Ansatzfärbung Segmente erstellen
2. In DB: CalcomEventMap Einträge erstellen

**Impact**: Fabian kann zukünftig Ansatzfärbung Termine syncen ✅

---

### Option 2: Fabian von Ansatzfärbung Service entfernen

**Was**: Fabian aus `service_staff` für Ansatzfärbung entfernen

**Impact**: Fabian wird nicht mehr als available für Ansatzfärbung erkannt

**ABER**: Nur wenn Fabian Ansatzfärbung NICHT machen soll!

---

### Option 3: Validation in check_availability

**Was**: Availability check prüft auch CalcomEventMaps

**Code**:
```php
// In check_availability_v17
if (staff is available) {
    // NEW: Check if CalcomEventMaps exist
    $hasEventMaps = CalcomEventMap::where('service_id', $service->id)
        ->where('staff_id', $staff->id)
        ->exists();

    if (!$hasEventMaps) {
        continue; // Skip this staff, no Cal.com setup
    }

    return $staff;
}
```

**Pro**: Verhindert Buchung für Staff ohne Cal.com Setup
**Contra**: Koppelt Availability an Cal.com (nicht ideal)

---

## Recommendation

### Short-term: CalcomEventMaps erstellen ✅

**Für welche Services fehlen CalcomEventMaps?**

```sql
-- Find services where staff can perform but no CalcomEventMaps exist
SELECT
    s.id,
    s.name,
    st.id as staff_id,
    st.name as staff_name
FROM services s
JOIN service_staff ss ON s.id = ss.service_id
JOIN staff st ON ss.staff_id = st.id
WHERE s.is_composite = true
  AND ss.is_active = true
  AND NOT EXISTS (
      SELECT 1
      FROM calcom_event_map cem
      WHERE cem.service_id = s.id
        AND cem.staff_id = st.id
  )
ORDER BY s.name, st.name;
```

**Expected**: Liste von Services + Staff Kombinationen wo Event Maps fehlen

---

### Long-term: Validation Layer ✅

**Add to check_availability_v17**:
1. Prüfe Verfügbarkeit (wie jetzt) ✅
2. **NEU**: Prüfe CalcomEventMap exists
3. NUR Staff mit CalcomEventMaps returnen

**Benefit**: Keine Fehlermeldungen an User mehr wegen fehlender Event Maps

---

## Impact on Today's Fixes

### Post-Sync Verification ✅ CORRECT

**Did NOT trigger** because:
- Sync failed during "preparation" phase (before API call)
- No Cal.com requests were made
- Nothing to verify

**System Behavior**: ✅ CORRECT

**Expected**: Post-Sync Verification NUR bei:
- Cal.com HTTP 400 NACH successful API call
- NICHT bei preparation errors

---

### Other Fixes ✅ WORKING

**Call ID Detection**: ✅ (not relevant in this call)
**Availability Overlap Detection**: ✅ (worked correctly - Fabian was available)

---

## Conclusion

**Type**: 🟡 DATA SETUP ISSUE

**Not a Code Bug**: System verhält sich korrekt ✅
- Fabian war available → korrekt identifiziert
- CalcomEventMaps fehlen → korrekte Fehlermeldung
- Sync failed → korrekt markiert
- Manual Review flagged → korrekt

**Fix Needed**: CalcomEventMaps für fehlende Staff/Service Kombinationen erstellen

**Code Changes**: Optional - Validation in check_availability (Long-term improvement)

---

**Status**: ✅ ANALYZED - Not a regression
**Priority**: 🟡 MEDIUM - Data setup issue
**Action**: Create missing CalcomEventMaps
