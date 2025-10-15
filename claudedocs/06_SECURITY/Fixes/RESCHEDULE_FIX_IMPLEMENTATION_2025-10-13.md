# RESCHEDULE FUNCTION FIX - Call 855 Root Cause
**Datum:** 2025-10-13
**Problem:** Verschiebung scheiterte (0% Success in Call 855)
**Impact:** User Experience "Rufen Sie uns an" Eskalation

---

## 🚨 PROBLEM ANALYSIS

### **Call 855 Scenario:**
```
User: "Termin um 16:00 gebucht" ✅
User: "Auf 16:30 verschieben" ❌ SCHEITERT
Agent: "Online-Verschiebung fehlgeschlagen, rufen Sie 030 123456 an"

User: "Termin 2025-10-15 10:00" (anderer Termin)
User: "Auf 11:00 verschieben" ❌ SCHEITERT
Agent: "Kann nicht online verschieben, rufen Sie an"
```

### **Root Causes:**

**RC-1: findAppointmentFromCall() findet Termin NICHT**
```php
// Line 2175-2228: findAppointmentFromCall()
$appointment = Appointment::where('call_id', $call->id)
    ->whereDate('starts_at', $date)  // ❌ Wenn $date fehlt/falsch → Termin nicht gefunden!
    ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
    ->first();
```

**Problem:**
- Agent übergibt `old_date` nicht korrekt (z.B. "heute" statt "2025-10-13")
- Datum-Parsing schlägt fehl
- `whereDate()` findet nichts

**RC-2: Availability-Check kommt ZU SPÄT**
```php
// Line 1947: Policy-Check ZUERST
$policyResult = $policyEngine->canReschedule($appointment);

// Line 2047: Availability-Check DANACH
$slotsResponse = $calcomService->getAvailableSlots(...);
```

**Problem:**
- Policy sagt "OK, kostet 15€"
- DANN prüfen wir Verfügbarkeit
- Slot ist belegt → Fehler statt Alternativen!

**RC-3: Keine Alternativen bei Nicht-Verfügbarkeit**
```php
// Line 2066-2080: Wenn nicht verfügbar
if (!$isAvailable) {
    // ❌ KEINE Alternativen angeboten!
    return response()->json([
        'success' => false,
        'status' => 'not_available',
        'message' => "Der Termin um {$newTime} ist leider nicht verfügbar."
    ], 200);
}
```

**Problem:**
- User bekommt nur "nicht verfügbar"
- Keine Alternative-Slots
- Frustrierendes UX

---

## ✅ SOLUTIONS

### **Fix #1: Bessere Termin-Suche (findAppointmentFromCall)**

**Problem:** `old_date` fehlt → Termin nicht gefunden

**Lösung:** Fallback-Strategien hinzufügen

```php
// NEW: Zeile 2203 (nach alter Strategy 1)

// Strategy 0: SAME-CALL Detection (<5 Minuten alt)
// Wenn User GERADE EBEN gebucht hat und sofort verschieben will
if (!$dateString || $dateString === 'heute' || $dateString === 'today') {
    $recentAppointment = Appointment::where('call_id', $call->id)
        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
        ->where('created_at', '>=', now()->subMinutes(5))  // Letzte 5 Min
        ->orderBy('created_at', 'desc')
        ->first();

    if ($recentAppointment) {
        Log::info('✅ Found SAME-CALL appointment (booked <5min ago)', [
            'appointment_id' => $recentAppointment->id,
            'created_at' => $recentAppointment->created_at->toIso8601String(),
            'age_seconds' => $recentAppointment->created_at->diffInSeconds(now())
        ]);
        return $recentAppointment;
    }
}

// Strategy 1-4: Bisherige Strategien bleiben...

// NEW Strategy 5: FALLBACK - Liste ALLE Termine des Kunden
if (!$appointment && $call->customer_id) {
    $customerAppointments = Appointment::where('customer_id', $call->customer_id)
        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
        ->where('starts_at', '>=', now())  // Nur zukünftige
        ->orderBy('starts_at', 'asc')
        ->get();

    if ($customerAppointments->count() === 1) {
        // Nur 1 Termin → automatisch nehmen
        Log::info('✅ Found single upcoming appointment for customer', [
            'appointment_id' => $customerAppointments->first()->id
        ]);
        return $customerAppointments->first();
    } elseif ($customerAppointments->count() > 1) {
        // Multiple Termine → frage nach
        Log::info('⚠️ Multiple appointments found, need clarification', [
            'count' => $customerAppointments->count(),
            'dates' => $customerAppointments->pluck('starts_at')->toArray()
        ]);
        // ❌ NICHT hier returnieren, sondern NULL (Code unten behandelt das)
        return null;  // Wird in handleRescheduleAttempt behandelt
    }
}
```

**File:** `app/Http/Controllers/RetellFunctionCallHandler.php:2203`

---

### **Fix #2: Availability-Check ZUERST**

**Problem:** Policy-Check vor Availability-Check → User erfährt Gebühr, dann "nicht verfügbar"

**Lösung:** Reihenfolge umdrehen

```php
// Line 1935-2034: handleRescheduleAttempt()

// ALTE Reihenfolge:
// 1. Find appointment
// 2. Check policy ❌ ZU FRÜH!
// 3. Parse new date
// 4. Check availability

// NEUE Reihenfolge:
// 1. Find appointment
// 2. Parse new date
// 3. Check availability ZUERST ✅
// 4. DANN Policy-Check

// Code:
// 2. Find appointment (bleibt)
$appointment = $this->findAppointmentFromCall($call, ['appointment_date' => $oldDate]);

if (!$appointment) {
    // NEW: Biete verfügbare Termine zur Auswahl
    if ($call->customer_id) {
        $upcomingAppointments = Appointment::where('customer_id', $call->customer_id)
            ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at', 'asc')
            ->limit(3)
            ->get();

        if ($upcomingAppointments->count() > 0) {
            $appointments_list = $upcomingAppointments->map(function($apt) {
                return $apt->starts_at->format('d.m.Y \u\m H:i \U\h\r');
            })->join(', ');

            return response()->json([
                'success' => false,
                'status' => 'multiple_found',
                'message' => "Ich habe mehrere Termine für Sie gefunden: {$appointments_list}. Welchen möchten Sie verschieben?",
                'appointments' => $upcomingAppointments->map(function($apt) {
                    return [
                        'id' => $apt->id,
                        'date' => $apt->starts_at->format('Y-m-d'),
                        'time' => $apt->starts_at->format('H:i'),
                        'formatted' => $apt->starts_at->format('d.m.Y H:i')
                    ];
                })
            ], 200);
        }
    }

    $dateStr = $oldDate ?? 'dem gewünschten Datum';
    return response()->json([
        'success' => false,
        'status' => 'not_found',
        'message' => "Ich konnte keinen Termin am {$dateStr} finden. Könnten Sie das Datum noch einmal nennen?"
    ], 200);
}

// 3. Parse new date (bleibt)
$newDate = $params['new_date'] ?? null;
$newTime = $params['new_time'] ?? null;

if (!$newDate || !$newTime) {
    // User hat neuen Termin noch nicht genannt
    return response()->json([
        'success' => true,
        'status' => 'ready_to_reschedule',
        'message' => "Ihr Termin kann umgebucht werden. Wann möchten Sie den neuen Termin?",
        'current_appointment' => [
            'date' => $appointment->starts_at->format('d.m.Y'),
            'time' => $appointment->starts_at->format('H:i')
        ]
    ], 200);
}

$newDateParsed = $this->parseDateString($newDate);
if (!$newDateParsed) {
    return response()->json([
        'success' => false,
        'status' => 'invalid_date',
        'message' => "Das Datum konnte nicht verstanden werden."
    ], 200);
}

list($hour, $minute) = explode(':', $newTime);
$newDateTime = $newDateParsed->setTime($hour, $minute);

// 4. Check availability ZUERST ✅
$companyId = $callContext['company_id'];
$branchId = $callContext['branch_id'];
$service = $this->serviceSelector->getDefaultService($companyId, $branchId);

if (!$service || !$service->calcom_event_type_id) {
    return response()->json([
        'success' => false,
        'status' => 'error',
        'message' => 'Service-Konfiguration fehlt.'
    ], 200);
}

// Check availability
$calcomService = app(\App\Services\CalcomService::class);
$slotsResponse = $calcomService->getAvailableSlots(
    $service->calcom_event_type_id,
    $newDateTime->format('Y-m-d'),
    $newDateTime->format('Y-m-d')
);

$isAvailable = false;
if ($slotsResponse->successful()) {
    $slots = $slotsResponse->json()['data']['slots'][$newDateTime->format('Y-m-d')] ?? [];
    foreach ($slots as $slot) {
        $slotTime = Carbon::parse($slot['time']);
        if ($slotTime->format('H:i') === $newDateTime->format('H:i')) {
            $isAvailable = true;
            break;
        }
    }
}

// NEW: Wenn NICHT verfügbar → Alternativen anbieten SOFORT
if (!$isAvailable) {
    Log::warning('⚠️ Requested time not available, finding alternatives', [
        'requested_time' => $newDateTime->toIso8601String(),
        'call_id' => $callId
    ]);

    // Finde Alternativen (+/- 2 Stunden)
    $startSearch = $newDateTime->copy()->subHours(2);
    $endSearch = $newDateTime->copy()->addHours(2);

    $alternativeSlotsResponse = $calcomService->getAvailableSlots(
        $service->calcom_event_type_id,
        $startSearch->format('Y-m-d'),
        $endSearch->format('Y-m-d')
    );

    $alternatives = [];
    if ($alternativeSlotsResponse->successful()) {
        $allSlots = $alternativeSlotsResponse->json()['data']['slots'] ?? [];
        foreach ($allSlots as $date => $slots) {
            foreach ($slots as $slot) {
                $slotTime = Carbon::parse($slot['time']);
                // Nur Slots in +/- 2h Range
                if ($slotTime->between($startSearch, $endSearch)) {
                    $alternatives[] = [
                        'date' => $slotTime->format('Y-m-d'),
                        'time' => $slotTime->format('H:i'),
                        'formatted' => $slotTime->format('d.m. H:i \U\h\r')
                    ];
                }

                if (count($alternatives) >= 2) break 2;  // Max 2 Alternativen
            }
        }
    }

    $alternativesText = count($alternatives) > 0
        ? "Alternativ habe ich " . implode(' oder ', array_column($alternatives, 'formatted')) . " frei."
        : "Leider habe ich in diesem Zeitraum keine Alternativen frei.";

    return response()->json([
        'success' => false,
        'status' => 'not_available',
        'message' => "Der Termin um {$newTime} Uhr ist leider nicht verfügbar. {$alternativesText}",
        'alternatives' => $alternatives,
        'requested_time' => $newDateTime->format('Y-m-d H:i')
    ], 200);
}

// 5. JETZT ERST Policy-Check (wenn verfügbar!) ✅
$policyEngine = app(\App\Services\Policies\AppointmentPolicyEngine::class);
$policyResult = $policyEngine->canReschedule($appointment);

if (!$policyResult->allowed) {
    // Policy verbietet Verschiebung
    // (Rest bleibt gleich...)
}

// 6. Reschedule durchführen (bleibt gleich)
```

**File:** `app/Http/Controllers/RetellFunctionCallHandler.php:1935-2080`

---

### **Fix #3: Alternativen-Finder Service**

**NEW FILE:** `app/Services/Retell/AppointmentAlternativesFinder.php`

```php
<?php

namespace App\Services\Retell;

use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentAlternativesFinder
{
    private CalcomService $calcomService;

    public function __construct()
    {
        $this->calcomService = new CalcomService();
    }

    /**
     * Find alternative time slots near requested time
     *
     * @param int $eventTypeId Cal.com event type ID
     * @param Carbon $requestedTime Desired time
     * @param int $maxAlternatives Max number of alternatives (default: 2)
     * @param int $searchRangeHours Search range in hours +/- (default: 2)
     * @return array ['time' => Carbon, 'formatted' => string]
     */
    public function findAlternatives(
        int $eventTypeId,
        Carbon $requestedTime,
        int $maxAlternatives = 2,
        int $searchRangeHours = 2
    ): array {
        $startSearch = $requestedTime->copy()->subHours($searchRangeHours);
        $endSearch = $requestedTime->copy()->addHours($searchRangeHours);

        Log::info('🔍 Finding alternative slots', [
            'requested_time' => $requestedTime->toIso8601String(),
            'search_start' => $startSearch->toIso8601String(),
            'search_end' => $endSearch->toIso8601String(),
            'event_type_id' => $eventTypeId
        ]);

        $slotsResponse = $this->calcomService->getAvailableSlots(
            $eventTypeId,
            $startSearch->format('Y-m-d'),
            $endSearch->format('Y-m-d')
        );

        $alternatives = [];

        if ($slotsResponse->successful()) {
            $allSlots = $slotsResponse->json()['data']['slots'] ?? [];

            foreach ($allSlots as $date => $slots) {
                foreach ($slots as $slot) {
                    $slotTime = Carbon::parse($slot['time']);

                    // Skip if outside search range
                    if (!$slotTime->between($startSearch, $endSearch)) {
                        continue;
                    }

                    // Skip if same as requested time
                    if ($slotTime->format('Y-m-d H:i') === $requestedTime->format('Y-m-d H:i')) {
                        continue;
                    }

                    $alternatives[] = [
                        'time' => $slotTime,
                        'date' => $slotTime->format('Y-m-d'),
                        'time_only' => $slotTime->format('H:i'),
                        'formatted' => $slotTime->format('d.m. H:i \U\h\r'),
                        'distance_minutes' => abs($slotTime->diffInMinutes($requestedTime))
                    ];

                    if (count($alternatives) >= $maxAlternatives) {
                        break 2;  // Exit both loops
                    }
                }
            }

            // Sort by distance to requested time
            usort($alternatives, function($a, $b) {
                return $a['distance_minutes'] <=> $b['distance_minutes'];
            });
        }

        Log::info('✅ Found alternative slots', [
            'count' => count($alternatives),
            'alternatives' => array_column($alternatives, 'formatted')
        ]);

        return $alternatives;
    }

    /**
     * Format alternatives for German text response
     */
    public function formatAlternativesText(array $alternatives): string
    {
        if (empty($alternatives)) {
            return "Leider habe ich in diesem Zeitraum keine Alternativen frei.";
        }

        if (count($alternatives) === 1) {
            return "Alternativ habe ich " . $alternatives[0]['formatted'] . " frei.";
        }

        $last = array_pop($alternatives);
        $text = "Alternativ habe ich " . implode(', ', array_column($alternatives, 'formatted'));
        $text .= " oder " . $last['formatted'] . " frei.";

        return $text;
    }
}
```

---

## 📊 EXPECTED IMPACT

### **Before Fix:**
```
Verschiebungs-Success: 0% (Call 855)
User Experience: "Rufen Sie uns an" Eskalation
Latenz: +2s (Policy-Check → Availability-Check → Fehler)
```

### **After Fix:**
```
Verschiebungs-Success: >90%
User Experience: "Alternativ habe ich 16:00 oder 17:00 frei"
Latenz: -1s (Availability-Check ZUERST, kein Policy-Check bei Nicht-Verfügbarkeit)
```

---

## 🧪 TEST CASES

### **Test 1: SAME-CALL Reschedule**
```
1. User bucht Termin 16:00 ✅
2. User sagt SOFORT: "Auf 16:30 verschieben"
3. System findet Termin via Strategy 0 (SAME-CALL <5min) ✅
4. System prüft Verfügbarkeit 16:30 ✅
5. Wenn frei: Verschieben ✅
6. Wenn belegt: "16:00 oder 17:00 frei" ✅
```

### **Test 2: Kein Datum angegeben**
```
1. User: "Ich möchte meinen Termin verschieben" (kein Datum!)
2. System findet 1 zukünftigen Termin via Strategy 5 ✅
3. System: "Ihr Termin am 15.10. um 10:00? Wann möchten Sie ihn verschieben?" ✅
```

### **Test 3: Multiple Termine**
```
1. User: "Termin verschieben"
2. System findet 3 zukünftige Termine
3. System: "Ich habe mehrere Termine: 15.10 10:00, 18.10 14:00, 20.10 16:00. Welchen möchten Sie verschieben?" ✅
```

### **Test 4: Zielslot belegt**
```
1. User: "Termin auf 16:30 verschieben"
2. System prüft 16:30 → belegt ❌
3. System findet Alternativen 16:00, 17:00 ✅
4. System: "16:30 ist belegt. Alternativ habe ich 16:00 oder 17:00 frei." ✅
```

---

## 📋 IMPLEMENTATION CHECKLIST

- [ ] Fix #1: SAME-CALL Detection in findAppointmentFromCall (Strategy 0)
- [ ] Fix #1: FALLBACK Strategy in findAppointmentFromCall (Strategy 5)
- [ ] Fix #2: Availability-Check VOR Policy-Check in handleRescheduleAttempt
- [ ] Fix #2: Alternativen anbieten bei Nicht-Verfügbarkeit
- [ ] Fix #3: AppointmentAlternativesFinder Service erstellen
- [ ] Test Case 1: SAME-CALL Reschedule
- [ ] Test Case 2: Kein Datum angegeben
- [ ] Test Case 3: Multiple Termine
- [ ] Test Case 4: Zielslot belegt → Alternativen

---

## 🚀 DEPLOYMENT

1. Code-Review des Fixes
2. Unit-Tests für findAppointmentFromCall (5 Strategien)
3. Integration-Test mit Cal.com API
4. Staging-Deployment + 10 Test-Calls
5. Production-Deployment
6. Monitoring für 1 Woche (Verschiebungs-Success-Rate)

**Expected Timeline:** 1 Tag
