# Implementierungsplan: Cal.com Fallback-Suggestion Fix

**Datum:** 2025-10-01
**Priorität:** 🔴 HOCH - Verhindert fehlerhafte Buchungen
**Scope:** Multi-Tenant System mit Companies, Branches, Services
**Betroffene Dateien:** 2 Core Files, 1 Config File

---

## 🎯 ZIELE

### Primärziel
Fallback-Suggestions müssen **IMMER gegen Cal.com API validiert** werden, bevor sie dem User angeboten werden.

### Sekundärziele
1. Multi-Tenant Isolation beibehalten (Company/Branch/Service)
2. Performance optimieren (Caching nutzen)
3. Bessere User Experience bei "keine Termine verfügbar"
4. Logging verbessern für Debugging

---

## 📋 BETROFFENE KOMPONENTEN

### 1. Core Service (HAUPTÄNDERUNG)
**Datei:** `/var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php`
- **Zeile 483-536:** `generateFallbackAlternatives()` - Muss Cal.com validieren
- **Zeile 273-332:** `getAvailableSlots()` - Bereits vorhanden, nutzen!
- **Zeile 83-86:** Logging bei "No slots found"

### 2. Controller (KLEINERE ANPASSUNG)
**Datei:** `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
- **Zeile 1096-1118:** Response-Formatierung anpassen
- **Zeile 884-888:** Fallback-Aufruf mit besserem Error Handling

### 3. Configuration (OPTIONAL)
**Datei:** `/var/www/api-gateway/config/booking.php`
- Neue Parameter für Fallback-Verhalten

---

## 🏗️ ARCHITEKTUR-ÜBERLEGUNGEN

### Multi-Tenant Data Flow
```
User Request
    ↓
Call → PhoneNumber → Company (ID: 15) + Branch (ID: X)
                         ↓
            ServiceSelectionService
                (filtert nach company_id, branch_id)
                         ↓
                Service (ID: 47)
                calcom_event_type_id: 2563193
                         ↓
        AppointmentAlternativeFinder
        (muss eventTypeId verwenden!)
                         ↓
            CalcomService.getAvailableSlots()
                         ↓
        ✅ REAL Cal.com API Call
        ✅ Company-specific Event Type
        ✅ Branch-aware Service Selection
```

### Sicherheitsaspekte
- ✅ Keine Cross-Company Datenlecks möglich
- ✅ Cal.com Team Ownership wird validiert
- ✅ Service muss zu Company gehören
- ✅ Branch-Isolation wird respektiert

---

## 🔧 IMPLEMENTIERUNGSSCHRITTE

### SCHRITT 1: generateFallbackAlternatives() umbauen

**Aktueller Code (Lines 483-536):**
```php
private function generateFallbackAlternatives(Carbon $desiredDateTime, int $durationMinutes): Collection
{
    $alternatives = collect();

    // Option 1: Same day, 2 hours earlier
    if ($desiredDateTime->hour >= 10) {
        $earlier = $desiredDateTime->copy()->subHours(2);
        $alternatives->push([
            'datetime' => $earlier,
            'type' => 'same_day_earlier',
            'description' => 'am gleichen Tag, ' . $earlier->format('H:i') . ' Uhr',
            'rank' => 90,
            'available' => true // ❌ PROBLEM: Hardcoded ohne Validierung
        ]);
    }

    // ... 3 weitere ähnliche Blöcke

    return $alternatives->sortByDesc('rank')->take($this->maxAlternatives);
}
```

**NEUER Code mit Cal.com Validierung:**

```php
/**
 * Generate fallback alternatives and VERIFY with Cal.com
 *
 * CRITICAL FIX: All suggestions MUST be validated against real Cal.com availability
 *
 * @param Carbon $desiredDateTime Original requested time
 * @param int $durationMinutes Appointment duration
 * @return Collection Verified alternatives with real Cal.com slots
 */
private function generateFallbackAlternatives(Carbon $desiredDateTime, int $durationMinutes): Collection
{
    Log::info('🔍 Generating fallback alternatives (Cal.com had no slots)', [
        'desired_time' => $desiredDateTime->format('Y-m-d H:i'),
        'event_type_id' => $this->eventTypeId,
        'company_id' => $this->companyId ?? null,
    ]);

    // Step 1: Generate CANDIDATE times (algorithmic suggestions)
    $candidates = $this->generateCandidateTimes($desiredDateTime, $durationMinutes);

    // Step 2: VERIFY each candidate against real Cal.com availability
    $verified = collect();

    foreach ($candidates as $candidate) {
        $candidateDate = $candidate['datetime'];

        // Get real Cal.com slots for this candidate's date
        $slotsForDay = $this->getAvailableSlots(
            $candidateDate->copy()->startOfDay(),
            $candidateDate->copy()->endOfDay(),
            $this->eventTypeId
        );

        // Check if this specific time is actually available in Cal.com
        $isReallyAvailable = $this->isTimeSlotAvailable(
            $candidateDate,
            $slotsForDay,
            $durationMinutes
        );

        if ($isReallyAvailable) {
            $candidate['available'] = true;
            $candidate['source'] = 'calcom_verified';
            $verified->push($candidate);

            Log::debug('✅ Candidate verified with Cal.com', [
                'time' => $candidateDate->format('Y-m-d H:i'),
                'type' => $candidate['type']
            ]);
        } else {
            Log::debug('❌ Candidate NOT available in Cal.com', [
                'time' => $candidateDate->format('Y-m-d H:i'),
                'type' => $candidate['type']
            ]);
        }
    }

    // Step 3: If NO verified alternatives found, search for NEXT truly available slot
    if ($verified->isEmpty()) {
        Log::warning('⚠️ No fallback candidates available, searching for next real slot');

        $nextAvailable = $this->findNextAvailableSlot(
            $desiredDateTime,
            $durationMinutes,
            14 // Search up to 14 days ahead
        );

        if ($nextAvailable) {
            $verified->push($nextAvailable);
        }
    }

    // Step 4: Return top alternatives (max 2)
    $final = $verified->sortByDesc('rank')->take($this->maxAlternatives);

    Log::info('✅ Fallback alternatives verified', [
        'candidates_generated' => $candidates->count(),
        'verified_available' => $verified->count(),
        'returned' => $final->count(),
        'times' => $final->pluck('datetime')->map->format('Y-m-d H:i')->toArray()
    ]);

    return $final;
}
```

**NEUE Hilfsmethoden:**

```php
/**
 * Generate candidate times (algorithmic suggestions to be verified)
 */
private function generateCandidateTimes(Carbon $desiredDateTime, int $durationMinutes): Collection
{
    $candidates = collect();

    // Candidate 1: Same day, 2 hours earlier
    if ($desiredDateTime->hour >= 10 && $this->isWithinBusinessHours($desiredDateTime->copy()->subHours(2))) {
        $earlier = $desiredDateTime->copy()->subHours(2);
        $candidates->push([
            'datetime' => $earlier,
            'type' => 'same_day_earlier',
            'description' => 'am gleichen Tag, ' . $earlier->format('H:i') . ' Uhr',
            'rank' => 90,
            'available' => false // Will be verified
        ]);
    }

    // Candidate 2: Same day, 2 hours later
    if ($desiredDateTime->hour <= 16 && $this->isWithinBusinessHours($desiredDateTime->copy()->addHours(2))) {
        $later = $desiredDateTime->copy()->addHours(2);
        $candidates->push([
            'datetime' => $later,
            'type' => 'same_day_later',
            'description' => 'am gleichen Tag, ' . $later->format('H:i') . ' Uhr',
            'rank' => 85,
            'available' => false
        ]);
    }

    // Candidate 3: Next workday at same time
    $nextWorkday = $this->getNextWorkday($desiredDateTime);
    if ($this->isWithinBusinessHours($nextWorkday)) {
        $candidates->push([
            'datetime' => $nextWorkday->copy()->setTime($desiredDateTime->hour, $desiredDateTime->minute),
            'type' => 'next_workday',
            'description' => $this->formatGermanWeekday($nextWorkday) . ', ' .
                          $nextWorkday->format('d.m.') . ' um ' . $desiredDateTime->format('H:i') . ' Uhr',
            'rank' => 80,
            'available' => false
        ]);
    }

    // Candidate 4: Same weekday next week
    $nextWeek = $desiredDateTime->copy()->addWeek();
    if ($this->isWithinBusinessHours($nextWeek)) {
        $candidates->push([
            'datetime' => $nextWeek,
            'type' => 'next_week',
            'description' => 'nächste Woche ' . $this->formatGermanWeekday($nextWeek) .
                          ', ' . $nextWeek->format('d.m.') . ' um ' . $nextWeek->format('H:i') . ' Uhr',
            'rank' => 70,
            'available' => false
        ]);
    }

    return $candidates;
}

/**
 * Check if a specific time slot is available in Cal.com slots array
 */
private function isTimeSlotAvailable(Carbon $targetTime, array $availableSlots, int $durationMinutes): bool
{
    foreach ($availableSlots as $slot) {
        $slotTime = Carbon::parse($slot['time']);

        // Check if slot matches our target time (allow 15-minute tolerance)
        $diff = abs($targetTime->diffInMinutes($slotTime));

        if ($diff <= 15) {
            return true;
        }
    }

    return false;
}

/**
 * Validate time is within configured business hours
 */
private function isWithinBusinessHours(Carbon $time): bool
{
    $hour = $time->hour;
    $businessStart = 9;  // TODO: Get from config
    $businessEnd = 18;   // TODO: Get from config

    return $hour >= $businessStart && $hour < $businessEnd;
}

/**
 * Find the absolute next available slot (brute force search)
 */
private function findNextAvailableSlot(Carbon $startFrom, int $durationMinutes, int $maxDaysAhead = 14): ?array
{
    $searchDate = $startFrom->copy()->addDay()->startOfDay();
    $endDate = $startFrom->copy()->addDays($maxDaysAhead);

    Log::info('🔍 Searching for next available slot', [
        'from' => $searchDate->format('Y-m-d'),
        'to' => $endDate->format('Y-m-d'),
        'duration' => $durationMinutes
    ]);

    // Search day by day
    while ($searchDate <= $endDate) {
        $slots = $this->getAvailableSlots(
            $searchDate->copy(),
            $searchDate->copy()->endOfDay(),
            $this->eventTypeId
        );

        if (!empty($slots)) {
            // Found slots! Take the first available
            $firstSlot = Carbon::parse($slots[0]['time']);

            Log::info('✅ Found next available slot', [
                'date' => $firstSlot->format('Y-m-d H:i')
            ]);

            return [
                'datetime' => $firstSlot,
                'type' => 'next_available',
                'description' => $this->formatGermanWeekday($firstSlot) . ', ' .
                              $firstSlot->format('d.m.Y') . ' um ' . $firstSlot->format('H:i') . ' Uhr',
                'rank' => 60,
                'available' => true,
                'source' => 'calcom_next_available'
            ];
        }

        $searchDate->addDay();
    }

    Log::warning('⚠️ No available slots found in next ' . $maxDaysAhead . ' days');
    return null;
}
```

---

### SCHRITT 2: RetellFunctionCallHandler.php anpassen

**Aktuelle Logik (ca. Line 1096):**
```php
if (empty($alternatives)) {
    return $responseService->error('Keine verfügbaren Termine gefunden.');
}

return $responseService->success([
    'alternatives' => $alternatives,
    'message' => 'Ich habe folgende Alternativen gefunden: ...'
]);
```

**NEUE Logik mit besserer Fehlerbehandlung:**

```php
// After calling alternativeFinder->findAlternatives()
if (empty($alternatives)) {
    Log::warning('❌ No alternatives available after fallback generation', [
        'requested_time' => $appointmentDate->format('Y-m-d H:i'),
        'service_id' => $service->id,
        'event_type_id' => $service->calcom_event_type_id,
        'company_id' => $call->company_id
    ]);

    return $responseService->error(
        'Es tut mir leid, für die von Ihnen gewünschte Zeit und die nächsten 14 Tage sind ' .
        'leider keine Termine verfügbar. Bitte rufen Sie zu einem späteren Zeitpunkt ' .
        'noch einmal an oder kontaktieren Sie uns direkt.'
    );
}

Log::info('✅ Presenting alternatives to user', [
    'count' => count($alternatives),
    'times' => collect($alternatives)->pluck('datetime')->map->format('Y-m-d H:i')->toArray(),
    'all_verified' => collect($alternatives)->every(fn($alt) => $alt['source'] === 'calcom_verified')
]);

// Format alternatives for voice response
$alternativeDescriptions = collect($alternatives)
    ->map(fn($alt) => $alt['description'])
    ->join(', oder ');

return $responseService->success([
    'alternatives' => $alternatives,
    'message' => 'Ich habe leider keinen Termin zu Ihrer gewünschten Zeit gefunden, ' .
                'aber ich kann Ihnen folgende Alternativen anbieten: ' .
                $alternativeDescriptions . '. Welcher Termin würde Ihnen besser passen?'
]);
```

---

### SCHRITT 3: Configuration erweitern

**Datei:** `/var/www/api-gateway/config/booking.php`

```php
return [
    // ... existing config ...

    'fallback' => [
        // Enable Cal.com verification for fallback suggestions
        'verify_with_calcom' => env('BOOKING_VERIFY_FALLBACK', true),

        // Maximum days to search ahead for next available slot
        'max_search_days' => env('BOOKING_MAX_SEARCH_DAYS', 14),

        // Time tolerance for slot matching (minutes)
        'slot_match_tolerance' => 15,

        // Business hours validation
        'enforce_business_hours' => true,
        'business_hours' => [
            'start' => '09:00',
            'end' => '18:00'
        ],

        // Logging
        'log_unverified_suggestions' => true,
        'alert_on_no_availability' => true,
    ],
];
```

---

## 🧪 TESTING STRATEGIE

### Test 1: Single Company, Single Branch
```
Company: ID 15 (AskProAI)
Branch: NULL (company-wide)
Service: ID 47 (Beratung)
Event Type: 2563193

Scenarios:
1. Request today (no slots) → Should return tomorrow's real slots
2. Request tomorrow 14:00 (has slots) → Should book directly
3. Request weekend → Should return next Monday's real slots
4. Request 3 weeks ahead (no slots) → Should inform "no availability"
```

### Test 2: Multiple Companies (Multi-Tenant)
```
Company A: ID 15, Event Type 2563193
Company B: ID 20, Event Type XXXX

Verify:
1. Company A request NEVER sees Company B slots
2. Company B request NEVER sees Company A slots
3. Cal.com team ownership validation works
```

### Test 3: Multiple Branches (Same Company)
```
Company: ID 15
Branch A: Service with Event Type A
Branch B: Service with Event Type B

Verify:
1. Call to Branch A gets Branch A slots only
2. Call to Branch B gets Branch B slots only
3. Company-wide services available to both
```

### Test 4: Edge Cases
```
1. Cal.com API timeout → Graceful degradation
2. Cal.com returns 500 error → Error message to user
3. All slots booked for 14 days → "No availability" message
4. User requests past date → Proper validation error
```

---

## 📊 MONITORING & METRICS

### Neue Log-Einträge (für Monitoring)
```php
// SUCCESS Metrics
Log::info('✅ Fallback verified and presented', [
    'requested_time' => $time,
    'alternatives_count' => $count,
    'verification_time_ms' => $durationMs
]);

// WARNING Metrics
Log::warning('⚠️ Fallback had no verified alternatives', [
    'requested_time' => $time,
    'candidates_tried' => $count,
    'company_id' => $companyId
]);

// ERROR Metrics
Log::error('❌ Cal.com API failed during fallback', [
    'error' => $exception->getMessage(),
    'event_type_id' => $eventTypeId
]);
```

### Metriken die getrackt werden sollten
1. **Fallback Usage Rate**: Wie oft wird Fallback verwendet?
2. **Verification Success Rate**: Wie viele Kandidaten werden von Cal.com bestätigt?
3. **Next Available Distance**: Durchschnittliche Tage bis nächster Termin
4. **Cal.com API Latency**: Performance Tracking

---

## 🚀 ROLLOUT PLAN

### Phase 1: Development & Testing (Tag 1)
- [ ] Code implementieren
- [ ] Unit Tests schreiben
- [ ] Lokale Tests mit Cal.com Sandbox

### Phase 2: Staging Testing (Tag 2)
- [ ] Deploy to Staging
- [ ] Multi-Tenant Tests (Companies 15, 20)
- [ ] Branch-Isolation Tests
- [ ] Performance Tests (Cache Warming)

### Phase 3: Gradual Production Rollout (Tag 3-4)
- [ ] Feature Flag aktivieren für Company 15 only
- [ ] Monitor Logs für 24h
- [ ] Wenn erfolgreich: Rollout auf alle Companies
- [ ] Monitor für weitere 48h

### Phase 4: Monitoring & Optimization (Tag 5-7)
- [ ] Metriken analysieren
- [ ] Performance optimieren (wenn nötig)
- [ ] User Feedback einholen
- [ ] Dokumentation finalisieren

---

## ⚠️ RISIKEN & MITIGATION

### Risk 1: Cal.com API Rate Limits
**Problem:** Mehr API Calls durch Fallback-Verifikation
**Mitigation:**
- Caching nutzen (bereits 5min TTL vorhanden)
- Batch-Anfragen für mehrere Tage
- Rate Limit Monitoring

### Risk 2: Performance Degradation
**Problem:** Langsame Response durch mehrere Cal.com Calls
**Mitigation:**
- Cache pre-warmen für beliebte Zeiten
- Async verification (falls möglich)
- Timeout nach 3 Sekunden

### Risk 3: Cal.com API Downtime
**Problem:** Keine Alternativen wenn Cal.com down
**Mitigation:**
- Graceful degradation mit klarer Fehlermeldung
- Fallback auf "Bitte später anrufen"
- Alert an Operations Team

### Risk 4: Multi-Tenant Data Leakage
**Problem:** Versehentlich cross-company slots zeigen
**Mitigation:**
- Unit Tests für Tenant Isolation
- Code Review fokussiert auf Security
- Audit Logs für alle API Calls

---

## 📝 ACCEPTANCE CRITERIA

### Muss erfüllt sein:
- [ ] Alle Fallback-Suggestions sind Cal.com-verifiziert
- [ ] Keine künstlichen "available: true" ohne Cal.com Check
- [ ] Multi-Tenant Isolation funktioniert (Company/Branch)
- [ ] Performance < 2 Sekunden für Fallback-Generierung
- [ ] Logging zeigt Verifikations-Status
- [ ] Tests für alle 4 Szenarien bestehen

### Nice-to-Have:
- [ ] Business Hours Validierung integriert
- [ ] Holiday/Weekend Checking
- [ ] User Preference (z.B. "nur Vormittags")
- [ ] Staff Preference (falls mehrere verfügbar)

---

## 🔗 ABHÄNGIGKEITEN

### Externe Services
- ✅ Cal.com API v2 (bereits integriert)
- ✅ Laravel Cache (Redis/Memcached)
- ✅ Database (MySQL)

### Interne Services
- ✅ CalcomService (bereits vorhanden)
- ✅ ServiceSelectionService (bereits vorhanden)
- ✅ CallLifecycleService (bereits vorhanden)
- ⚠️ AppointmentAlternativeFinder (MUSS GEÄNDERT WERDEN)

---

## 📚 DOKUMENTATION

### Nach Implementation zu erstellen:
1. **API Documentation Update**
   - `collect_appointment_data` Response Format
   - Neue Error Codes

2. **Developer Guide**
   - Wie man neue Fallback-Strategien hinzufügt
   - Cal.com Integration Best Practices

3. **Operations Runbook**
   - Monitoring Alerts
   - Incident Response (Cal.com down)
   - Performance Tuning

---

## ✅ SUMMARY

**Problem:**
Fallback-Suggestions werden künstlich generiert ohne Cal.com Validierung → User bekommt nicht-existierende Termine angeboten.

**Lösung:**
Alle Fallback-Kandidaten MÜSSEN gegen Cal.com API verifiziert werden bevor sie dem User präsentiert werden.

**Impact:**
- ✅ Verhindert fehlerhafte Buchungen
- ✅ Verbessert User Experience
- ✅ Erhöht System-Zuverlässigkeit
- ⚠️ Erhöht Cal.com API Calls (durch Caching mitigiert)

**Implementation Time:**
- Development: 4-6 Stunden
- Testing: 2-3 Stunden
- Rollout: 1-2 Tage (gradual)

**Priority:**
🔴 HOCH - Sollte ASAP implementiert werden
