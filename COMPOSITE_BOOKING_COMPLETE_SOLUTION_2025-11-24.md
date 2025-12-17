# Composite Booking - Complete Solution Strategy

**Date**: 2025-11-24
**Status**: 🔴 **CRITICAL** - System Down for Composite Services
**Prepared by**: Claude Code with --ultrathink analysis

---

## Executive Summary

**Problem**: Neu erstellte Event Types (3982562, 3982564, etc.) haben `managedEventConfig` Metadata-Feld und können NICHT gebucht werden.

**Root Cause**: Event Types wurden als "MANAGED" Event Types erstellt (vermutlich via Cal.com UI-Option), was Cal.com dazu bringt Child Event Type IDs zu erwarten, die nicht existieren.

**Discovery**: Bestehende funktionierende Event Types (z.B. 3757759 für Dauerwelle) haben **leere Metadata** `[]` und funktionieren einwandfrei mit mehreren Hosts.

**Solution**: Event Types ersetzen durch neue ohne `managedEventConfig`.

**Impact**: Alle 3 betroffenen Services (Ansatzfärbung, Ansatz + Längenausgleich, Komplette Umfärbung) können wieder synchronisiert werden.

---

## Part 1: Cal.com Event Type Problem & Solution

### 1.1 Problem Analysis

**Vergleich:**

| Aspekt | Funktionierend (3757759) | Kaputt (3982562) |
|--------|--------------------------|-------------------|
| Service | Dauerwelle (ALT) | Ansatzfärbung (NEU) |
| Hosts | 2 (1414768, 1346408) | 2 (1346408, 1414768) |
| Metadata | `[]` (LEER) | `{"managedEventConfig":[]}` |
| Slots Query | ✅ Funktioniert | ✅ Funktioniert |
| Direkt Buchbar | ✅ **JA** | ❌ **NEIN** (400 Error) |

**Cal.com Error Message:**
```
Event type with id=3982562 is the parent managed event type that can't be booked.
You have to provide the child event type id aka id of event type that has been
assigned to one of the users.
```

**Analysis:**
- `managedEventConfig` Feld markiert Event Type als "MANAGED"
- MANAGED Event Types erwarten Child Event Type IDs pro Host
- Diese Children wurden nie erstellt (und können via API auch nicht erstellt werden)
- Das Feld kann via API nicht entfernt werden (getestet)

### 1.2 Solution: Replace Event Types

**Strategie**: Neue Event Types erstellen OHNE managed config.

**Zwei Ansätze:**

#### Option A: Multi-Host Event Types (wie bestehende)
- **Vorteil**: Weniger Event Types (12 statt 24)
- **Vorteil**: Automatische Host-Auswahl durch Cal.com
- **Risiko**: Muss korrekt erstellt werden (ohne `managedEventConfig`)
- **Empfehlung**: ⚠️ Mittel

#### Option B: Single-Host Event Types ⭐ EMPFOHLEN
- **Vorteil**: Garantiert funktioniert (keine Komplikationen)
- **Vorteil**: Klare Zuordnung staff → event type
- **Vorteil**: Unabhängige Konfiguration pro Staff
- **Nachteil**: Mehr Event Types (24 statt 12)
- **Empfehlung**: ✅ **Sehr empfohlen**

**Entscheidung**: Option B (Single-Host Event Types)

### 1.3 Implementation Plan

#### Phase 1: Neue Event Types erstellen

**Für jeden Staff separat:**
- Staff ID `9f47fda1-977c-47aa-a87a-0e8cbeaeb119` (Fabian askproai)
- Staff ID `6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe` (Fabian fabianspitzer)

**Event Types to create** (12 pro Staff = 24 total):

**Service 440: Ansatzfärbung**
1. Ansatzfärbung auftragen (Segment A) - 30 min
2. Auswaschen (Segment B) - 20 min
3. Formschnitt (Segment C) - 40 min
4. Föhnen & Styling (Segment D) - 40 min

**Service 442: Ansatz + Längenausgleich**
1. Ansatzfärbung & Längenausgleich auftragen (Segment A) - 30 min
2. Auswaschen (Segment B) - 30 min
3. Formschnitt (Segment C) - 45 min
4. Föhnen & Styling (Segment D) - 40 min

**Service 444: Komplette Umfärbung/Blondierung**
1. Ansatzfärbung & Blondierung auftragen (Segment A) - 35 min
2. Auswaschen (Segment B) - 35 min
3. Formschnitt (Segment C) - 50 min
4. Föhnen & Styling (Segment D) - 40 min

**Creation Method**:
```php
// Via Cal.com API - ensure NO managedEventConfig
$client->createEventType([
    'lengthInMinutes' => 30,
    'title' => 'Ansatzfärbung: Ansatzfärbung auftragen',
    'slug' => 'ansatzfarbung-auftragen-staff-1',
    'description' => 'Segment A - Ansatzfärbung auftragen',
    'locations' => [
        ['type' => 'address', 'address' => 'Vor Ort']
    ],
    'hosts' => [
        ['userId' => 1414768] // SINGLE host only
    ],
    'metadata' => [] // EMPTY - no managedEventConfig!
]);
```

#### Phase 2: CalcomEventMaps aktualisieren

```sql
-- Update für Staff 1 (9f47fda1-977c-47aa-a87a-0e8cbeaeb119)
UPDATE calcom_event_map
SET event_type_id = NEW_ID_A1,
    child_event_type_id = NEW_ID_A1
WHERE service_id = 440
  AND segment_key = 'A'
  AND staff_id = '9f47fda1-977c-47aa-a87a-0e8cbeaeb119';

-- ... repeat for all 12 segments per staff
```

#### Phase 3: Alte Event Types aufräumen

⚠️ **WICHTIG**: Erst NACH erfolgreicher Verifikation löschen!

```
1. Verifikation:
   - Test-Buchung für alle neuen Event Types
   - Sync-Test für alle Services
   - Produktions-Test mit realem Anruf

2. Cleanup:
   - Alte Event Types (3982562, 3982564, etc.) in Cal.com UI löschen
   - Oder deaktivieren als Backup
```

---

## Part 2: Composite Booking Synchronization Flow

### 2.1 Current Implementation

Der Code in `SyncAppointmentToCalcomJob.php` ist bereits **sehr gut strukturiert**:

```
handle()
  ↓
isComposite? → syncCreateComposite()
              ↓
              For each Phase (staff_required=true):
                1. Lookup CalcomEventMap
                2. Resolve child_event_type_id (or use from map)
                3. Build booking payload
                4. Create booking via Cal.com API
                5. Store calcom_booking_id + uid in phase
              ↓
              Update appointment with sync status
```

**Parallel Execution** (Optional, Feature Flag):
- Alle Phase-Bookings parallel via Guzzle Promises
- 70% schneller als sequentiell
- Aktiviert via `config('features.parallel_calcom_booking', true)`

### 2.2 Was funktioniert bereits

✅ **Correct Architecture**:
- Separate CalcomEventMaps pro Service/Segment/Staff
- Phase-based booking (nur staff_required=true Phasen)
- Metadata tracking (crm_appointment_id, crm_phase_id, segment_key)
- Error handling pro Phase
- Partial success handling

✅ **Data Storage**:
- `appointment_phases` Tabelle mit Feldern:
  - `calcom_booking_id` (integer)
  - `calcom_booking_uid` (string)
  - `calcom_sync_status` ('pending', 'synced', 'failed')
  - `sync_error_message` (text)

✅ **Aggregation**:
- Appointment-Level Status = aggregiert aus allen Phasen
- `calcom_v2_booking_id` = erste Phase's Booking ID (backward compat)

### 2.3 Was aktuell fehlt

❌ **Event Type Problem**: Gelöst durch Part 1

❌ **Post-Sync Verification** (teilweise):
- Existiert für simple appointments
- Muss für composite erweitert werden

❌ **UI Display** (komplett):
- Admin sieht nur Appointment, nicht die einzelnen Phasen
- Keine Visualisierung der Segment-Buchungen
- Keine Cal.com Booking IDs/UIDs sichtbar

### 2.4 Enhancement: Post-Sync Verification für Composite

**Erweitern** `SyncAppointmentToCalcomJob.php`:

```php
/**
 * Verify composite bookings exist in Cal.com
 * Used for false-negative error recovery
 */
private function verifyCompositeBookingsInCalcom(): bool
{
    $phases = $this->appointment->phases()
        ->where('staff_required', true)
        ->get();

    $allVerified = true;

    foreach ($phases as $phase) {
        if (!$phase->calcom_booking_uid) {
            // Phase was supposed to sync but has no UID
            $allVerified = false;
            continue;
        }

        // Query Cal.com to verify booking exists
        try {
            $response = $client->getBooking($phase->calcom_booking_uid);

            if ($response->successful()) {
                $this->safeInfo("✅ Phase {$phase->segment_key} verified in Cal.com", [
                    'phase_id' => $phase->id,
                    'booking_uid' => $phase->calcom_booking_uid
                ]);

                // Update phase if it was marked as failed
                if ($phase->calcom_sync_status === 'failed') {
                    $phase->update([
                        'calcom_sync_status' => 'synced',
                        'sync_error_message' => null
                    ]);
                }
            } else {
                $allVerified = false;
            }
        } catch (\Exception $e) {
            $allVerified = false;
        }
    }

    return $allVerified;
}
```

**Integration** in `handle()` Exception Handler:

```php
catch (\Exception $e) {
    if ($this->attempts() >= $this->tries) {
        // Composite appointment?
        if ($this->appointment->service->isComposite()) {
            $this->safeInfo('🔍 POST-SYNC VERIFICATION (Composite)...');
            sleep(2);

            if ($this->verifyCompositeBookingsInCalcom()) {
                $this->safeInfo('✅ All phases verified despite error');
                return;
            }
        }

        // Mark for manual review
        $this->appointment->update([
            'requires_manual_review' => true,
            'calcom_sync_status' => 'failed'
        ]);
    }
}
```

---

## Part 3: Datensammlung und -speicherung

### 3.1 Aktuelle Datenstruktur

**Datenbank Schema** (appointment_phases):

```sql
CREATE TABLE appointment_phases (
    id BIGINT UNSIGNED PRIMARY KEY,
    appointment_id BIGINT UNSIGNED,
    segment_key VARCHAR(10),             -- 'A', 'B', 'C', 'D', 'GAP_A'
    segment_name VARCHAR(255),           -- 'Ansatzfärbung auftragen'
    start_time DATETIME,
    end_time DATETIME,
    duration_minutes INT,
    staff_required BOOLEAN,
    sequence_order INT,
    calcom_booking_id BIGINT,            -- ✅ Cal.com Booking ID
    calcom_booking_uid VARCHAR(255),     -- ✅ Cal.com Booking UID
    calcom_sync_status ENUM,             -- ✅ 'pending', 'synced', 'failed'
    sync_error_message TEXT,             -- ✅ Error details
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Was bereits gesammelt wird:**
✅ Booking ID (integer) pro Phase
✅ Booking UID (string) pro Phase
✅ Sync Status pro Phase
✅ Error Message pro Phase

### 3.2 Was zusätzlich gesammelt werden sollte

#### Option 1: Erweiterte Metadata in appointment_phases

**Neue Spalten:**
```sql
ALTER TABLE appointment_phases
ADD COLUMN calcom_event_type_id INT,              -- Event Type verwendet
ADD COLUMN calcom_host_user_id INT,               -- Host User zugewiesen
ADD COLUMN calcom_booking_created_at TIMESTAMP,   -- Cal.com Creation Time
ADD COLUMN calcom_attendee_email VARCHAR(255),    -- Attendee Email
ADD COLUMN calcom_attendee_name VARCHAR(255);     -- Attendee Name
```

**Vorteile:**
- Komplette Nachverfolgbarkeit
- Debugging einfacher
- Reports möglich

**Nachteile:**
- Schema-Migration erforderlich
- Mehr Speicherplatz

#### Option 2: JSON Metadata Column

**Spalte:**
```sql
ALTER TABLE appointment_phases
ADD COLUMN calcom_metadata JSON;
```

**Inhalt:**
```json
{
  "event_type_id": 3982562,
  "host_user_id": 1414768,
  "booking_created_at": "2025-11-24T15:00:00Z",
  "attendee": {
    "name": "Paul Klaus",
    "email": "paul@example.com"
  },
  "sync_details": {
    "attempt": 1,
    "latency_ms": 342,
    "api_version": "2024-08-13"
  }
}
```

**Vorteile:**
- Flexibel (keine Schema-Änderungen für neue Felder)
- Alles an einem Ort

**Nachteile:**
- Queries komplexer
- Keine DB-Level Constraints

#### Empfehlung: Option 2 (JSON Metadata)

**Kompromiss**: Wichtigste Felder als Spalten + JSON für Details:

```sql
-- Keep existing columns
calcom_booking_id
calcom_booking_uid
calcom_sync_status
sync_error_message

-- Add new
calcom_event_type_id INT             -- Direct column (für joins)
calcom_metadata JSON                  -- Flexible storage
```

### 3.3 Datensammlung im Sync-Code

**Erweitern** `syncCreateComposite()`:

```php
// After successful booking
$responseData = $response->json('data', []);
$bookingId = $responseData['id'] ?? null;
$bookingUid = $responseData['uid'] ?? null;

// NEW: Collect extended metadata
$metadata = [
    'event_type_id' => $childEventTypeId,
    'parent_event_type_id' => $mapping->event_type_id,
    'host_user_id' => $this->getHostUserIdFromStaff($this->appointment->staff_id),
    'booking_created_at' => $responseData['createdAt'] ?? now()->toIso8601String(),
    'attendee' => [
        'name' => $responseData['attendeeName'] ?? $this->appointment->customer->name,
        'email' => $responseData['attendeeEmail'] ?? $this->appointment->customer->email,
    ],
    'sync_details' => [
        'attempt' => $this->attempts(),
        'synced_at' => now()->toIso8601String(),
        'api_version' => config('services.calcom.api_version'),
    ]
];

// Update phase with ALL data
$phase->update([
    'calcom_booking_id' => $bookingId,
    'calcom_booking_uid' => $bookingUid,
    'calcom_event_type_id' => $childEventTypeId,        // NEW
    'calcom_metadata' => $metadata,                      // NEW
    'calcom_sync_status' => 'synced',
    'sync_error_message' => null
]);
```

---

## Part 4: Admin Portal UI-Darstellung

### 4.1 User Requirements (vom User)

> "Im Admin Portal... an den jeweiligen Punkten auch sauber hinterlegt werden und gespeichert werden:
> - **Anrufübersicht**: Welche Einzelsegmente wurden gebucht, von wann bis wann
> - **Anruf Details**: Segmente mit Zeitangaben
> - **Terminübersicht**: Composite-Termin Kennzeichnung
> - **Termin Detailseite**: Komplette Segment-Auflistung"

### 4.2 Call Resource (app/Filament/Resources/CallResource.php)

#### Anrufübersicht (List View)

**Current**: Zeigt nur Appointments count

**Enhancement**: Composite-Indikator hinzufügen

```php
Tables\Columns\TextColumn::make('appointments')
    ->label('Termine')
    ->formatStateUsing(function ($record) {
        $appointments = $record->appointments;
        if ($appointments->isEmpty()) {
            return '-';
        }

        $output = [];
        foreach ($appointments as $appt) {
            if ($appt->service->isComposite()) {
                $phases = $appt->phases()->where('staff_required', true)->count();
                $output[] = "{$appt->service->name} ({$phases} Segmente)";
            } else {
                $output[] = $appt->service->name;
            }
        }

        return implode(', ', $output);
    })
    ->wrap(),
```

#### Anruf Details (View/Infolist)

**Neue Section**: Gebuchte Segmente

```php
Infolists\Components\Section::make('Gebuchte Segmente')
    ->schema([
        Infolists\Components\RepeatableEntry::make('appointments')
            ->label('')
            ->schema([
                Infolists\Components\TextEntry::make('service.name')
                    ->label('Service'),

                Infolists\Components\Grid::make(1)
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('phases')
                            ->label('Segmente')
                            ->schema([
                                Infolists\Components\Grid::make(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('segment_key')
                                            ->label('Segment')
                                            ->badge()
                                            ->color('primary'),

                                        Infolists\Components\TextEntry::make('segment_name')
                                            ->label('Bezeichnung'),

                                        Infolists\Components\TextEntry::make('start_time')
                                            ->label('Von')
                                            ->dateTime('H:i'),

                                        Infolists\Components\TextEntry::make('end_time')
                                            ->label('Bis')
                                            ->dateTime('H:i'),

                                        Infolists\Components\TextEntry::make('calcom_sync_status')
                                            ->label('Cal.com Status')
                                            ->badge()
                                            ->color(fn ($state) => match($state) {
                                                'synced' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                default => 'secondary'
                                            }),

                                        Infolists\Components\TextEntry::make('calcom_booking_id')
                                            ->label('Booking ID')
                                            ->copyable()
                                            ->visible(fn ($state) => !empty($state)),
                                    ]),
                            ])
                            ->visible(fn ($record) => $record->service->isComposite()),
                    ]),
            ])
    ])
    ->visible(fn ($record) => $record->appointments->isNotEmpty())
    ->collapsible(),
```

### 4.3 Appointment Resource (app/Filament/Resources/AppointmentResource.php)

#### Terminübersicht (List View)

**Enhancement**: Composite-Badge hinzufügen

```php
Tables\Columns\TextColumn::make('service.name')
    ->label('Service')
    ->formatStateUsing(function ($record) {
        $name = $record->service->name;

        if ($record->service->isComposite()) {
            $phaseCount = $record->phases()->where('staff_required', true)->count();
            return "{$name} ({$phaseCount} Segmente)";
        }

        return $name;
    })
    ->searchable()
    ->sortable(),

// Neue Badge-Spalte
Tables\Columns\BadgeColumn::make('is_composite')
    ->label('Typ')
    ->getStateUsing(fn ($record) => $record->service->isComposite() ? 'Compound' : 'Standard')
    ->color(fn ($state) => $state === 'Compound' ? 'warning' : 'secondary'),
```

#### Termin Detailseite (View/Infolist)

**Neue Section**: Segment-Details

```php
Infolists\Components\Section::make('Segment-Details')
    ->schema([
        Infolists\Components\Grid::make(1)
            ->schema([
                // Timeline Visualization
                Infolists\Components\ViewEntry::make('phases_timeline')
                    ->view('filament.infolists.phases-timeline')
                    ->label(''),

                // Segment Table
                Infolists\Components\RepeatableEntry::make('phases')
                    ->label('Alle Segmente')
                    ->schema([
                        Infolists\Components\Grid::make(6)
                            ->schema([
                                Infolists\Components\TextEntry::make('segment_key')
                                    ->label('Segment')
                                    ->badge()
                                    ->color(fn ($record) => $record->staff_required ? 'primary' : 'secondary'),

                                Infolists\Components\TextEntry::make('segment_name')
                                    ->label('Bezeichnung')
                                    ->columnSpan(2),

                                Infolists\Components\TextEntry::make('start_time')
                                    ->label('Start')
                                    ->dateTime('d.m.Y H:i'),

                                Infolists\Components\TextEntry::make('end_time')
                                    ->label('Ende')
                                    ->dateTime('d.m.Y H:i'),

                                Infolists\Components\TextEntry::make('duration_minutes')
                                    ->label('Dauer')
                                    ->suffix(' min'),

                                Infolists\Components\TextEntry::make('staff_required')
                                    ->label('Friseur erforderlich')
                                    ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein (Einwirkzeit)')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'secondary'),

                                // Cal.com Sync Info (nur für staff_required Phasen)
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('calcom_sync_status')
                                            ->label('Cal.com Status')
                                            ->badge()
                                            ->color(fn ($state) => match($state) {
                                                'synced' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                default => 'secondary'
                                            }),

                                        Infolists\Components\TextEntry::make('calcom_booking_id')
                                            ->label('Booking ID')
                                            ->copyable()
                                            ->placeholder('-'),

                                        Infolists\Components\TextEntry::make('calcom_booking_uid')
                                            ->label('Booking UID')
                                            ->copyable()
                                            ->placeholder('-')
                                            ->limit(20),
                                    ])
                                    ->visible(fn ($record) => $record->staff_required)
                                    ->columnSpan(6),

                                // Error Message
                                Infolists\Components\TextEntry::make('sync_error_message')
                                    ->label('Fehler')
                                    ->color('danger')
                                    ->visible(fn ($record) => !empty($record->sync_error_message))
                                    ->columnSpan(6),
                            ]),
                    ]),
            ]),
    ])
    ->visible(fn ($record) => $record->service->isComposite())
    ->collapsible()
    ->collapsed(false),
```

**Custom Blade View** `resources/views/filament/infolists/phases-timeline.blade.php`:

```blade
@php
    $phases = $getRecord()->phases()->orderBy('sequence_order')->get();
    $totalDuration = $getRecord()->service->getTotalDuration();
@endphp

<div class="w-full p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
    <div class="text-sm font-medium mb-2">Timeline ({{ $totalDuration }} Minuten gesamt)</div>

    <div class="relative">
        @foreach ($phases as $index => $phase)
            <div class="flex items-center mb-2">
                {{-- Time --}}
                <div class="w-20 text-xs text-gray-600 dark:text-gray-400">
                    {{ $phase->start_time->format('H:i') }}
                </div>

                {{-- Bar --}}
                <div class="flex-1 ml-2">
                    <div class="relative h-8 rounded"
                         style="background-color: {{ $phase->staff_required ? '#3b82f6' : '#94a3b8' }}; width: {{ ($phase->duration_minutes / $totalDuration) * 100 }}%;">

                        <div class="absolute inset-0 flex items-center px-2">
                            <span class="text-xs text-white font-medium truncate">
                                {{ $phase->segment_name }}
                                @if ($phase->staff_required && $phase->calcom_sync_status === 'synced')
                                    ✓
                                @elseif ($phase->staff_required && $phase->calcom_sync_status === 'failed')
                                    ✗
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Duration --}}
                <div class="w-16 ml-2 text-xs text-gray-600 dark:text-gray-400 text-right">
                    {{ $phase->duration_minutes }} min
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-3 flex gap-4 text-xs">
        <div class="flex items-center gap-1">
            <div class="w-3 h-3 rounded" style="background-color: #3b82f6;"></div>
            <span>Friseur erforderlich</span>
        </div>
        <div class="flex items-center gap-1">
            <div class="w-3 h-3 rounded" style="background-color: #94a3b8;"></div>
            <span>Einwirkzeit</span>
        </div>
    </div>
</div>
```

### 4.4 Migration für neue Spalten

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_phases', function (Blueprint $table) {
            $table->unsignedBigInteger('calcom_event_type_id')->nullable()->after('calcom_booking_uid');
            $table->json('calcom_metadata')->nullable()->after('calcom_event_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_phases', function (Blueprint $table) {
            $table->dropColumn(['calcom_event_type_id', 'calcom_metadata']);
        });
    }
};
```

---

## Part 5: Keine Doppelbuchungen - Garantie

### 5.1 Aktuelle Sicherheitsmaßnahmen

✅ **Redis Slot Locking** (bereits implementiert):
```php
// OptimisticReservationService
$lockKey = "slot_lock:{$staffId}:{$startTime}:{$serviceId}";
$lockAcquired = Redis::set($lockKey, $callId, 'EX', 300, 'NX');
```

✅ **Availability Overlap Check**:
```php
// ProcessingTimeAvailabilityService
// ALWAYS check full-duration overlaps FIRST
if ($this->hasOverlappingAppointments($staffId, $startTime, $endTime)) {
    return false;
}

// ADDITIONALLY check phase-aware conflicts
if ($service->hasProcessingTime()) {
    foreach ($proposedPhases as $phase) {
        if ($phase['staff_required']) {
            if ($this->hasOverlappingBusyPhases($staffId, $phase['start_time'], $phase['end_time'])) {
                return false;
            }
        }
    }
}
```

✅ **Database-Level Checks**:
- Query existing appointments for overlap
- Check appointment_phases for staff availability

### 5.2 Cal.com-Level Sicherheit

**Problem**: Wenn Cal.com Sync fehlschlägt, wird der Slot in Cal.com nicht blockiert.

**Lösung A**: Post-Sync Verification (bereits besprochen in Part 2.4)

**Lösung B**: Preventive Slot Check

```php
// BEFORE creating appointment in our DB, check Cal.com availability
public function ensureCalcomSlotAvailable(Service $service, Staff $staff, Carbon $startTime): bool
{
    $phases = $service->generatePhases($startTime);
    $client = new CalcomV2Client($this->company);

    foreach ($phases as $phase) {
        if (!($phase['staff_required'] ?? true)) {
            continue; // Skip GAP phases
        }

        // Get mapping
        $mapping = CalcomEventMap::where('service_id', $service->id)
            ->where('segment_key', $phase['segment_key'])
            ->where('staff_id', $staff->id)
            ->first();

        if (!$mapping) {
            return false; // No mapping = can't book
        }

        // Check slots in Cal.com
        $slotStart = Carbon::parse($phase['start_time']);
        $slotEnd = $slotStart->copy()->addMinutes($phase['duration']);

        $response = $client->getAvailableSlots(
            $mapping->event_type_id,
            $slotStart,
            $slotEnd
        );

        if (!$response->successful()) {
            return false;
        }

        $slots = $response->json('data.slots', []);
        $targetSlot = $slotStart->toIso8601String();

        if (!in_array($targetSlot, $slots)) {
            Log::warning('Cal.com slot not available', [
                'segment_key' => $phase['segment_key'],
                'start_time' => $targetSlot,
                'available_slots' => $slots
            ]);
            return false;
        }
    }

    return true;
}
```

**Integration in AppointmentCreationService**:

```php
// BEFORE creating appointment
if (!$this->ensureCalcomSlotAvailable($service, $staff, $startTime)) {
    throw new \RuntimeException('Slot not available in Cal.com');
}

// CREATE appointment
$appointment = Appointment::create([...]);

// SYNC to Cal.com
SyncAppointmentToCalcomJob::dispatch($appointment, 'create');
```

### 5.3 Monitoring & Alerts

**Metric**: Sync Success Rate

```php
// In SyncAppointmentToCalcomJob
if ($response->successful()) {
    Metrics::increment('calcom.sync.success', [
        'service_type' => $this->appointment->service->isComposite() ? 'composite' : 'simple',
        'action' => $this->action
    ]);
} else {
    Metrics::increment('calcom.sync.failure', [
        'service_type' => $this->appointment->service->isComposite() ? 'composite' : 'simple',
        'action' => $this->action,
        'http_status' => $response->status()
    ]);
}
```

**Alert**: Sync-Fehler-Rate > 5%

```php
// Prometheus Alert Rule
alert: HighCalcomSyncFailureRate
expr: |
  rate(calcom_sync_failure_total[5m])
  / rate(calcom_sync_attempts_total[5m])
  > 0.05
for: 10m
annotations:
  summary: "High Cal.com sync failure rate"
  description: "{{ $value }}% of Cal.com syncs are failing"
```

---

## Part 6: Implementierungs-Roadmap

### Phase 1: Event Type Fix (KRITISCH - 2-3 Stunden)

**Ziel**: Neue Event Types erstellen, System funktionsfähig machen

**Tasks**:
1. ✅ Script erstellen: `create_single_host_event_types.php`
   - 24 Event Types via Cal.com API erstellen
   - Jeweils 1 Host pro Event Type
   - Metadata: `[]` (LEER, kein `managedEventConfig`)

2. ✅ CalcomEventMaps aktualisieren
   - Script: `update_calcom_event_maps_with_new_ids.php`
   - Mapping: staff_id → neue Event Type IDs

3. ✅ Verifikation
   - Test-Buchung für alle 24 Event Types
   - Sync-Test für alle 3 Services
   - Produktions-Testanruf

4. ✅ Cleanup
   - Alte Event Types (3982562, etc.) löschen

**Deliverable**: System funktioniert wieder für Composite Services

---

### Phase 2: Erweiterte Datensammlung (1-2 Stunden)

**Ziel**: Mehr Informationen für Debugging und UI speichern

**Tasks**:
1. ✅ Migration erstellen und ausführen
   - `calcom_event_type_id` Column
   - `calcom_metadata` JSON Column

2. ✅ Sync-Code erweitern
   - `syncCreateComposite()` updated um Metadata zu sammeln

3. ✅ Testen
   - Test-Buchung
   - Verify Metadata in DB

**Deliverable**: Vollständige Nachverfolgbarkeit aller Bookings

---

### Phase 3: Post-Sync Verification (1 Stunde)

**Ziel**: False-Negative Fehler automatisch recovern

**Tasks**:
1. ✅ `verifyCompositeBookingsInCalcom()` implementieren

2. ✅ Integration in Exception Handler

3. ✅ Test False-Negative Scenario

**Deliverable**: System recovery automatisiert

---

### Phase 4: Admin UI Enhancement (3-4 Stunden)

**Ziel**: Composite-Termine perfekt darstellen

**Tasks**:
1. ✅ CallResource - Anrufübersicht
   - Composite-Indikator in List View
   - Segment-Details in View

2. ✅ AppointmentResource - Terminübersicht
   - Badge für Composite in List View
   - Segment-Details Section in View
   - Timeline Visualization (Blade View)

3. ✅ Testing
   - UI Test für alle Views
   - Responsive Check
   - Dark Mode Check

**Deliverable**: Admin sieht alle relevanten Informationen

---

### Phase 5: Cal.com Preventive Check (Optional, 2 Stunden)

**Ziel**: Doppelbuchungen komplett verhindern

**Tasks**:
1. ✅ `ensureCalcomSlotAvailable()` implementieren

2. ✅ Integration in AppointmentCreationService

3. ✅ Performance-Test (Latenz-Auswirkung)

**Deliverable**: 100% Doppelbuchungs-Prävention

---

### Phase 6: Monitoring & Metrics (1 Stunde)

**Ziel**: Produktionsüberwachung

**Tasks**:
1. ✅ Metrics in SyncAppointmentToCalcomJob

2. ✅ Prometheus Alert Rules

3. ✅ Grafana Dashboard

**Deliverable**: Proactive error detection

---

## Part 7: Testing-Strategie

### 7.1 Unit Tests

```php
// tests/Unit/SyncAppointmentToCalcomJobTest.php

test('composite appointment creates bookings for all staff-required phases', function () {
    $appointment = Appointment::factory()
        ->forCompositeService()
        ->create();

    $job = new SyncAppointmentToCalcomJob($appointment, 'create');
    $job->handle();

    $phases = $appointment->phases()->where('staff_required', true)->get();

    foreach ($phases as $phase) {
        expect($phase->calcom_booking_id)->not()->toBeNull();
        expect($phase->calcom_booking_uid)->not()->toBeNull();
        expect($phase->calcom_sync_status)->toBe('synced');
    }
});

test('gap phases are not synced to calcom', function () {
    $appointment = Appointment::factory()
        ->forCompositeService()
        ->create();

    $job = new SyncAppointmentToCalcomJob($appointment, 'create');
    $job->handle();

    $gapPhases = $appointment->phases()->where('staff_required', false)->get();

    foreach ($gapPhases as $phase) {
        expect($phase->calcom_booking_id)->toBeNull();
        expect($phase->calcom_booking_uid)->toBeNull();
    }
});
```

### 7.2 Integration Tests

```php
// tests/Integration/CompositeBookingFlowTest.php

test('end-to-end composite booking with calcom sync', function () {
    // 1. Create composite appointment
    $service = Service::where('composite', true)->first();
    $staff = Staff::first();
    $customer = Customer::factory()->create();

    $appointment = app(AppointmentCreationService::class)->createCompositeAppointment([
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'customer_id' => $customer->id,
        'starts_at' => now()->addDay()->setTime(10, 0),
    ]);

    // 2. Verify phases created
    expect($appointment->phases()->count())->toBeGreaterThan(0);

    // 3. Trigger sync
    SyncAppointmentToCalcomJob::dispatchSync($appointment, 'create');

    // 4. Verify all staff-required phases synced
    $staffPhases = $appointment->phases()->where('staff_required', true)->get();
    foreach ($staffPhases as $phase) {
        expect($phase->calcom_sync_status)->toBe('synced');
        expect($phase->calcom_booking_id)->not()->toBeNull();
    }

    // 5. Verify in Cal.com
    $client = new CalcomV2Client(Company::first());
    foreach ($staffPhases as $phase) {
        $response = $client->getBooking($phase->calcom_booking_uid);
        expect($response->successful())->toBeTrue();
    }
});
```

### 7.3 Manual Test Plan

#### Test 1: Ansatzfärbung Booking

```
1. Retell Anruf starten
2. Service auswählen: "Ansatzfärbung"
3. Zeit wählen: Morgen 10:00
4. Bestätigen

Expected:
- Appointment ID erstellt
- 4 Phasen (A, B, C, D) erstellt
- 4 Cal.com Bookings erstellt
- Status: synced
- In Cal.com sichtbar (4 separate Events)
```

#### Test 2: UI Darstellung

```
1. Admin Panel → Anrufe
2. Testanruf öffnen
3. Scroll to "Gebuchte Segmente"

Expected:
- Alle 4 Segmente sichtbar
- Start/End Zeiten korrekt
- Cal.com Sync Status: synced
- Booking IDs angezeigt
- Timeline Visualization zeigt 4 Balken
```

#### Test 3: Reschedule

```
1. Appointment in Admin öffnen
2. Verschieben auf anderen Tag
3. Speichern

Expected:
- Alte Cal.com Bookings cancelled
- Neue Cal.com Bookings erstellt
- Alle Phasen updated
```

#### Test 4: Cancel

```
1. Appointment in Admin öffnen
2. Stornieren

Expected:
- Alle Cal.com Bookings cancelled
- Status: cancelled
- Sync Status: synced (cancellation synced)
```

---

## Part 8: Rollback Plan

Falls etwas schiefgeht:

### Rollback Event Types

```
1. Alte Event Types (3982562, etc.) NICHT löschen → behalten als Backup
2. CalcomEventMaps zurücksetzen:
   UPDATE calcom_event_map
   SET event_type_id = 3982562  -- old ID
   WHERE service_id = 440 AND segment_key = 'A' AND staff_id = '...';
```

### Rollback Code

```bash
git checkout HEAD~1 -- app/Jobs/SyncAppointmentToCalcomJob.php
git checkout HEAD~1 -- app/Filament/Resources/CallResource.php
git checkout HEAD~1 -- app/Filament/Resources/AppointmentResource.php
```

### Rollback Migration

```bash
php artisan migrate:rollback --step=1
```

---

## Part 9: Success Criteria

### Definition of Done

✅ **Event Types**:
- 24 neue Event Types erstellt
- Alle direktbuchbar (keine managedEventConfig)
- CalcomEventMaps aktualisiert

✅ **Sync**:
- Composite Appointments syncen erfolgreich
- Alle staff_required Phasen haben calcom_booking_id
- Sync Success Rate >95%

✅ **UI**:
- CallResource zeigt Segmente
- AppointmentResource zeigt Timeline
- Alle Booking IDs/UIDs sichtbar
- Sync Status sichtbar

✅ **Doppelbuchungen**:
- Keine Doppelbuchungen in Tests
- Redis Locking funktioniert
- Cal.com Overlaps werden erkannt

✅ **Testing**:
- Unit Tests pass
- Integration Tests pass
- Manual Test Plan abgeschlossen

---

## Conclusion

Diese Lösung adressiert **alle** vom User genannten Anforderungen:

1. ✅ **Keine Doppelbuchungen**: Redis Locking + Overlap Checks + Optional Cal.com Preventive Check
2. ✅ **Klare Zuordnung**: Jede Phase hat eigene Booking ID/UID
3. ✅ **Erfolgs-Prüfung**: Post-Sync Verification
4. ✅ **Datensammlung**: Extended Metadata in appointment_phases
5. ✅ **UI-Darstellung**: Comprehensive in allen relevanten Views

**Nächster Schritt**: Phase 1 Implementation starten (Event Type Fix).

**Estimated Total Time**: 10-15 Stunden Development + Testing

**Priority**: 🔴 **CRITICAL** - System aktuell down für neue Services

---

**Prepared by**: Claude Code
**Analysis Mode**: --ultrathink
**Date**: 2025-11-24 09:00 CET
