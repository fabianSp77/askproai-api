# Composite Appointment UI/UX - State-of-the-Art Design

**Date**: 2025-11-24
**Goal**: **Perfekte Darstellung** von Composite-Terminen (Dauerwelle, Ansatzfärbung, etc.) im Admin Portal
**Principle**: **Bestehende Spalten optimieren**, KEINE neuen Spalten hinzufügen

---

## 🎯 User Requirements

> "In der Übersicht der Daten... die vorhandenen Spalten analysieren und weiterverwenden... die Detailinformationen zur z.B. Dauerwelle und den Segmenten dort angezeigt werden, gerne darunter inklusive der Zuordnung der Mitarbeiter... Muss state-of-the-art UI und UX sein für schnelles Verständnis."

**Kern-Anforderungen:**
1. ✅ **Bestehende Spalten** nutzen (KEINE neuen hinzufügen)
2. ✅ Segment-Details **innerhalb** bestehender Spalten zeigen
3. ✅ Mitarbeiter-Zuordnung klar darstellen
4. ✅ State-of-the-art UI/UX für schnelles Verständnis
5. ✅ Mouseover für Details, direkte Anzeige für Essentials

---

## Part 1: CallResource - Anrufübersicht

### 1.1 Bestehende Spalten-Struktur

**Aktuelle Spalten (Classic View)**:
1. **Ereignis / Zeit / Dauer** (ViewColumn: `status-time-duration`)
2. **Unternehmen/Filiale** (ViewColumn: `company-phone`)
3. **Anrufer** (ViewColumn: `anrufer-3lines`)
4. **Aktion (Legacy)** (TextColumn: `call_type`) - Hidden
5. **Termin & Mitarbeiter** (ViewColumn: `call-relationships`)

**Strategie**: Spalten 1, 3, 5 **erweitern** für Composite-Darstellung

---

### 1.2 Enhanced: "Ereignis / Zeit / Dauer" Spalte

**Aktuell**: Zeigt `Gebucht` Badge wenn `$record->appointment` (singular) existiert

**ENHANCEMENT für Composite**:

```blade
@php
    $record = $getRecord();
    $status = $record->status ?? 'unknown';
    $isLive = in_array($status, ['ongoing', 'in_progress', 'active', 'ringing']);

    // Get ALL appointments (not just one)
    $appointments = $record->appointments;
    $hasAppointments = $appointments->isNotEmpty();

    if ($isLive) {
        // LIVE logic unchanged
        $displayText = 'LIVE';
        $badgeColor = 'danger';
        $isPulse = true;
    } else {
        $isPulse = false;

        // Check appointment status
        $activeAppointments = $appointments->whereIn('status', ['scheduled', 'confirmed', 'booked', 'pending']);
        $cancelledAppointments = $appointments->where('status', 'cancelled');

        if ($activeAppointments->isNotEmpty()) {
            // Check if ANY appointment is composite
            $hasComposite = $activeAppointments->first(fn($appt) => $appt->service->isComposite());

            if ($hasComposite) {
                $displayText = '✅ Gebucht (Compound)';  // NEW: Indicator for composite
                $badgeColor = 'success';
                $accentColor = '#22c55e';
            } else {
                $displayText = 'Gebucht';
                $badgeColor = 'success';
                $accentColor = '#22c55e';
            }

        } elseif ($cancelledAppointments->isNotEmpty()) {
            $displayText = 'Storniert';
            $badgeColor = 'warning';
            $accentColor = '#f97316';
        } else {
            $displayText = 'Offen';
            $badgeColor = 'danger';
            $accentColor = '#64748b';
        }
    }

    // Enhanced tooltip for composite appointments
    $badgeTooltip = '';
    if ($hasAppointments && !$isLive) {
        $tooltipLines = [];

        foreach ($activeAppointments as $appt) {
            $serviceName = $appt->service->name;
            $startTime = $appt->starts_at?->format('d.m.Y H:i');

            if ($appt->service->isComposite()) {
                // Show segment count
                $phaseCount = $appt->phases()->where('staff_required', true)->count();
                $tooltipLines[] = "📦 {$serviceName} ({$phaseCount} Segmente)";
                $tooltipLines[] = "   ⏰ {$startTime}";

                // Show first 3 segments
                $phases = $appt->phases()->where('staff_required', true)->orderBy('sequence_order')->limit(3)->get();
                foreach ($phases as $phase) {
                    $tooltipLines[] = "   → {$phase->segment_name} ({$phase->duration_minutes}min)";
                }

                if ($phaseCount > 3) {
                    $tooltipLines[] = "   ... +" . ($phaseCount - 3) . " weitere";
                }
            } else {
                $tooltipLines[] = "📅 {$serviceName}";
                $tooltipLines[] = "   ⏰ {$startTime}";
            }

            $tooltipLines[] = ''; // blank line
        }

        $badgeTooltip = implode("\n", $tooltipLines);
    }
@endphp

<!-- Render badge (same structure as before) -->
<div style="display: flex; flex-direction: column; gap: 0.25rem;">
    <div style="display: flex; align-items: center;" title="{{ $badgeTooltip }}">
        <x-filament::badge
            :color="$badgeColor"
            class="ereignis-badge {{ $isPulse ? 'ereignis-badge-pulse' : '' }}"
            :style="'border-left: 3px solid ' . $accentColor . '; cursor: pointer;'"
        >
            {{ $displayText }}
        </x-filament::badge>
    </div>

    <!-- Date & Duration unchanged -->
    ...
</div>
```

**Result**: Badge zeigt "(Compound)" Indicator + Mouseover zeigt Segment-Details

---

### 1.3 Enhanced: "Termin & Mitarbeiter" Spalte

**Aktuell**: ViewColumn `call-relationships` zeigt Appointments

**Datei**: `resources/views/filament/columns/call-relationships.blade.php`

**ENHANCEMENT für Composite**:

```blade
@php
    $record = $getRecord();
    $appointments = $record->appointments;

    if ($appointments->isEmpty()) {
        $displayHtml = '<span style="color: #9ca3af;">Kein Termin</span>';
    } else {
        $lines = [];

        foreach ($appointments as $appt) {
            $service = $appt->service;
            $staff = $appt->staff;

            if (!$service) continue;

            // Build display line
            $serviceIcon = $service->isComposite() ? '📦' : '📅';
            $serviceName = $service->name;
            $staffName = $staff?->name ?? 'Unbekannt';
            $startTime = $appt->starts_at?->format('d.m. H:i');

            // Main line
            if ($service->isComposite()) {
                $phaseCount = $appt->phases()->where('staff_required', true)->count();
                $lines[] = "<strong>{$serviceIcon} {$serviceName}</strong> ({$phaseCount} Segmente)";
            } else {
                $lines[] = "<strong>{$serviceIcon} {$serviceName}</strong>";
            }

            // Sub-line: Staff + Time
            $lines[] = "<small style='color: #6b7280; margin-left: 1.5rem;'>👤 {$staffName} • 🕐 {$startTime}</small>";

            // For composite: Show segment breakdown
            if ($service->isComposite()) {
                $phases = $appt->phases()->where('staff_required', true)->orderBy('sequence_order')->get();

                foreach ($phases as $phase) {
                    $syncStatus = match($phase->calcom_sync_status) {
                        'synced' => '✅',
                        'failed' => '❌',
                        'pending' => '⏳',
                        default => '❓'
                    };

                    $lines[] = "<small style='color: #9ca3af; margin-left: 2.5rem;'>{$syncStatus} {$phase->segment_name} ({$phase->duration_minutes}min)</small>";
                }
            }

            $lines[] = ''; // blank line between appointments
        }

        $displayHtml = implode('<br>', $lines);
    }
@endphp

<div style="line-height: 1.5;">
    {!! $displayHtml !!}
</div>
```

**Result**:

```
📦 Dauerwelle (4 Segmente)
   👤 Fabian Spitzer • 🕐 28.11. 10:00
   ✅ Wickeln (30min)
   ✅ Dauerwellflüssigkeit auftragen (20min)
   ✅ Auswaschen & Pflege (20min)
   ✅ Föhnen & Styling (40min)
```

**UX**:
- ✅ Alle Infos **in einer Spalte** (keine neue Spalte)
- ✅ Segment-Status auf einen Blick (✅❌⏳)
- ✅ Mitarbeiter-Zuordnung klar sichtbar
- ✅ Zeiten direkt ersichtlich

---

## Part 2: CallResource - Anruf Details (Infolist)

### 2.1 Bestehende Struktur

**Aktuelle Sections**:
1. Anruf Basisinformationen
2. Gesprächsdetails
3. Transkript
4. Metadata

**Strategie**: **NEUE Section** hinzufügen: "Gebuchte Termine & Segmente"

---

### 2.2 New Section: "Gebuchte Termine & Segmente"

**Datei**: `app/Filament/Resources/CallResource.php` (in `infolist()` method)

```php
public static function infolist(Infolist $infolist): Infolist
{
    return $infolist
        ->schema([
            // Existing sections unchanged
            ...

            // NEW: Appointment & Segments Section
            InfoSection::make('Gebuchte Termine & Segmente')
                ->schema([
                    RepeatableEntry::make('appointments')
                        ->label('')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('service.name')
                                        ->label('Service')
                                        ->badge()
                                        ->color('primary')
                                        ->icon(fn($record) => $record->service->isComposite() ? 'heroicon-o-cube' : 'heroicon-o-calendar'),

                                    TextEntry::make('staff.name')
                                        ->label('Mitarbeiter')
                                        ->icon('heroicon-o-user'),

                                    TextEntry::make('starts_at')
                                        ->label('Start')
                                        ->dateTime('d.m.Y H:i'),

                                    TextEntry::make('ends_at')
                                        ->label('Ende')
                                        ->dateTime('d.m.Y H:i'),
                                ]),

                            // Conditional: Show phases only for composite services
                            Grid::make(1)
                                ->schema([
                                    // Custom View for Timeline Visualization
                                    ViewEntry::make('phases_timeline')
                                        ->view('filament.infolists.phases-timeline-compact')
                                        ->label('Segment-Timeline'),

                                    // Detailed Segment Table
                                    RepeatableEntry::make('phases')
                                        ->label('Alle Segmente')
                                        ->schema([
                                            Grid::make(5)
                                                ->schema([
                                                    TextEntry::make('segment_key')
                                                        ->label('Seg')
                                                        ->badge()
                                                        ->color(fn($record) => $record->staff_required ? 'success' : 'gray'),

                                                    TextEntry::make('segment_name')
                                                        ->label('Bezeichnung')
                                                        ->columnSpan(2),

                                                    TextEntry::make('start_time')
                                                        ->label('Beginn')
                                                        ->dateTime('H:i')
                                                        ->color('gray'),

                                                    TextEntry::make('calcom_sync_status')
                                                        ->label('Cal.com')
                                                        ->badge()
                                                        ->color(fn($state) => match($state) {
                                                            'synced' => 'success',
                                                            'pending' => 'warning',
                                                            'failed' => 'danger',
                                                            default => 'gray'
                                                        })
                                                        ->formatStateUsing(fn($state) => match($state) {
                                                            'synced' => '✓ Sync',
                                                            'pending' => '⏳ Pending',
                                                            'failed' => '✗ Failed',
                                                            default => '-'
                                                        }),

                                                    // Error message (only if failed)
                                                    TextEntry::make('sync_error_message')
                                                        ->label('Fehlerdetails')
                                                        ->color('danger')
                                                        ->visible(fn($record) => $record->calcom_sync_status === 'failed')
                                                        ->columnSpan(5),

                                                    // Booking IDs (only if synced)
                                                    Grid::make(2)
                                                        ->schema([
                                                            TextEntry::make('calcom_booking_id')
                                                                ->label('Booking ID')
                                                                ->copyable()
                                                                ->placeholder('-'),

                                                            TextEntry::make('calcom_booking_uid')
                                                                ->label('Booking UID')
                                                                ->copyable()
                                                                ->limit(15)
                                                                ->tooltip(fn($record) => $record->calcom_booking_uid)
                                                                ->placeholder('-'),
                                                        ])
                                                        ->visible(fn($record) => $record->staff_required && $record->calcom_sync_status === 'synced')
                                                        ->columnSpan(5),
                                                ]),
                                        ])
                                        ->visible(fn($record) => $record->phases->isNotEmpty()),
                                ])
                                ->visible(fn($record) => $record->service->isComposite()),
                        ])
                ])
                ->visible(fn($record) => $record->appointments->isNotEmpty())
                ->collapsed(false), // Open by default for quick visibility
        ]);
}
```

---

### 2.3 Custom Timeline View (Compact)

**Datei**: `resources/views/filament/infolists/phases-timeline-compact.blade.php`

```blade
@php
    $appointment = $getRecord();
    $phases = $appointment->phases()->orderBy('sequence_order')->get();

    if ($phases->isEmpty()) {
        echo '<span style="color: #9ca3af;">Keine Phasen vorhanden</span>';
        return;
    }

    $totalDuration = $appointment->service->getTotalDuration();
@endphp

<div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem; border: 1px solid #e5e7eb;">
    <!-- Timeline Header -->
    <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.75rem; color: #374151;">
        Timeline ({{ $totalDuration }} Min gesamt)
    </div>

    <!-- Timeline Bars -->
    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
        @foreach ($phases as $phase)
            @php
                $widthPercent = ($phase->duration_minutes / $totalDuration) * 100;
                $bgColor = $phase->staff_required ? '#3b82f6' : '#94a3b8'; // blue or slate
                $statusIcon = match($phase->calcom_sync_status) {
                    'synced' => '✓',
                    'failed' => '✗',
                    'pending' => '⏳',
                    default => ''
                };
            @endphp

            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <!-- Time -->
                <div style="width: 3.5rem; font-size: 0.75rem; color: #6b7280; text-align: right;">
                    {{ \Carbon\Carbon::parse($phase->start_time)->format('H:i') }}
                </div>

                <!-- Bar -->
                <div style="flex: 1; position: relative; height: 2rem; background: {{ $bgColor }}; border-radius: 0.25rem; display: flex; align-items: center; padding: 0 0.5rem; box-shadow: 0 1px 2px rgba(0,0,0,0.1); width: {{ $widthPercent }}%;">
                    <span style="font-size: 0.75rem; color: white; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $phase->segment_name }} @if($statusIcon) {{ $statusIcon }} @endif
                    </span>
                </div>

                <!-- Duration -->
                <div style="width: 3rem; font-size: 0.75rem; color: #6b7280;">
                    {{ $phase->duration_minutes }} min
                </div>
            </div>
        @endforeach
    </div>

    <!-- Legend -->
    <div style="margin-top: 0.75rem; display: flex; gap: 1rem; font-size: 0.75rem; color: #6b7280;">
        <div style="display: flex; align-items: center; gap: 0.25rem;">
            <div style="width: 1rem; height: 1rem; background: #3b82f6; border-radius: 0.125rem;"></div>
            <span>Friseur erforderlich</span>
        </div>
        <div style="display: flex; align-items: center; gap: 0.25rem;">
            <div style="width: 1rem; height: 1rem; background: #94a3b8; border-radius: 0.125rem;"></div>
            <span>Einwirkzeit</span>
        </div>
    </div>
</div>
```

**Result**:

```
Timeline (130 Min gesamt)

10:00 ████████████████ Wickeln ✓                    30 min
10:30 ██████████ Dauerwellflüssigkeit ✓              20 min
10:50 ████████████ Einwirkzeit                       25 min
11:15 ██████████ Auswaschen ✓                        20 min
11:35 ████████████████████ Föhnen & Styling ✓       40 min

■ Friseur erforderlich  □ Einwirkzeit
```

---

## Part 3: AppointmentResource - Terminübersicht

### 3.1 Bestehende Spalten-Struktur

**Aktuelle Spalten**:
1. ID / Status
2. Service
3. Kunde
4. Mitarbeiter
5. Start / Ende
6. Aktionen

**Strategie**: Spalte 2 (Service) **erweitern** für Composite-Indicator

---

### 3.2 Enhanced: "Service" Spalte

**Datei**: `app/Filament/Resources/AppointmentResource.php`

```php
Tables\Columns\TextColumn::make('service.name')
    ->label('Service')
    ->formatStateUsing(function ($record) {
        $service = $record->service;
        $name = $service->name;

        if ($service->isComposite()) {
            $phaseCount = $record->phases()->where('staff_required', true)->count();
            return "{$name} ({$phaseCount} Segmente)";
        }

        return $name;
    })
    ->description(function ($record) {
        // Show segment summary in description (below service name)
        if (!$record->service->isComposite()) {
            return null;
        }

        $phases = $record->phases()->where('staff_required', true)->orderBy('sequence_order')->get();
        $segmentNames = $phases->pluck('segment_name')->take(2)->toArray();

        if (count($segmentNames) === 0) {
            return null;
        }

        $summary = implode(' → ', $segmentNames);

        if ($phases->count() > 2) {
            $summary .= ' → ...';
        }

        return $summary;
    })
    ->searchable()
    ->sortable(),

// Add Badge Column for quick visual identification
Tables\Columns\BadgeColumn::make('service_type')
    ->label('Typ')
    ->getStateUsing(fn($record) => $record->service->isComposite() ? 'Compound' : 'Standard')
    ->color(fn($state) => $state === 'Compound' ? 'warning' : 'secondary')
    ->icon(fn($state) => $state === 'Compound' ? 'heroicon-o-cube' : 'heroicon-o-calendar'),
```

**Result**:

```
Service                                    | Typ
-------------------------------------------+----------
Dauerwelle (4 Segmente)                    | 🧱 Compound
  Wickeln → Dauerwellfl. auftragen → ...  |
Herrenhaarschnitt                          | 📅 Standard
```

**UX**:
- ✅ Compound-Badge für schnelle Erkennung
- ✅ Segment-Anzahl direkt sichtbar
- ✅ Erste 2 Segmente als Preview (description)
- ✅ **Keine neue Spalte** nötig

---

## Part 4: AppointmentResource - Termin Details

### 4.1 Bestehende Struktur

**Aktuelle Sections**:
1. Termindetails
2. Kundendaten
3. Notizen

**Strategie**: **NEUE Section** "Compound-Service Details" (conditional, nur bei composite)

---

### 4.2 New Section: "Compound-Service Details"

**Datei**: `app/Filament/Resources/AppointmentResource.php` (in `infolist()` method)

```php
public static function infolist(Infolist $infolist): Infolist
{
    return $infolist
        ->schema([
            // Existing sections unchanged
            ...

            // NEW: Compound-Service Details (conditional)
            InfoSection::make('Compound-Service Details')
                ->description('Dieser Termin besteht aus mehreren aufeinanderfolgenden Segmenten')
                ->schema([
                    // Service Info Grid
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('service.name')
                                ->label('Service')
                                ->badge()
                                ->color('primary')
                                ->icon('heroicon-o-cube'),

                            TextEntry::make('phases_count')
                                ->label('Anzahl Segmente')
                                ->getStateUsing(fn($record) => $record->phases()->where('staff_required', true)->count())
                                ->badge()
                                ->color('info'),

                            TextEntry::make('total_duration')
                                ->label('Gesamtdauer')
                                ->getStateUsing(fn($record) => $record->service->getTotalDuration() . ' Minuten')
                                ->suffix(' Min')
                                ->icon('heroicon-o-clock'),
                        ]),

                    // Timeline Visualization (full-width)
                    ViewEntry::make('phases_timeline_full')
                        ->view('filament.infolists.phases-timeline-full')
                        ->label(''),

                    // Detailed Segment Table
                    RepeatableEntry::make('phases')
                        ->label('Segment-Details')
                        ->schema([
                            Grid::make(6)
                                ->schema([
                                    // Segment Key Badge
                                    TextEntry::make('segment_key')
                                        ->label('Seg')
                                        ->badge()
                                        ->color(fn($record) => $record->staff_required ? 'success' : 'gray')
                                        ->icon(fn($record) => $record->staff_required ? 'heroicon-o-user' : 'heroicon-o-pause'),

                                    // Segment Name
                                    TextEntry::make('segment_name')
                                        ->label('Bezeichnung')
                                        ->weight('medium')
                                        ->columnSpan(2),

                                    // Start Time
                                    TextEntry::make('start_time')
                                        ->label('Start')
                                        ->dateTime('H:i')
                                        ->icon('heroicon-o-play'),

                                    // End Time
                                    TextEntry::make('end_time')
                                        ->label('Ende')
                                        ->dateTime('H:i')
                                        ->icon('heroicon-o-stop'),

                                    // Duration
                                    TextEntry::make('duration_minutes')
                                        ->label('Dauer')
                                        ->suffix(' min')
                                        ->color('gray'),

                                    // Staff Required Indicator
                                    TextEntry::make('staff_required')
                                        ->label('Friseur')
                                        ->formatStateUsing(fn($state) => $state ? 'Erforderlich' : 'Einwirkzeit')
                                        ->badge()
                                        ->color(fn($state) => $state ? 'success' : 'secondary')
                                        ->columnSpan(2),

                                    // Cal.com Sync Status
                                    TextEntry::make('calcom_sync_status')
                                        ->label('Cal.com Status')
                                        ->badge()
                                        ->color(fn($state) => match($state) {
                                            'synced' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger',
                                            default => 'gray'
                                        })
                                        ->icon(fn($state) => match($state) {
                                            'synced' => 'heroicon-o-check-circle',
                                            'pending' => 'heroicon-o-clock',
                                            'failed' => 'heroicon-o-x-circle',
                                            default => 'heroicon-o-question-mark-circle'
                                        })
                                        ->visible(fn($record) => $record->staff_required),

                                    // Booking ID (copyable)
                                    TextEntry::make('calcom_booking_id')
                                        ->label('Booking ID')
                                        ->copyable()
                                        ->icon('heroicon-o-clipboard-document')
                                        ->placeholder('-')
                                        ->visible(fn($record) => $record->staff_required && $record->calcom_booking_id),

                                    // Booking UID (copyable, truncated)
                                    TextEntry::make('calcom_booking_uid')
                                        ->label('Booking UID')
                                        ->copyable()
                                        ->limit(20)
                                        ->tooltip(fn($record) => $record->calcom_booking_uid)
                                        ->icon('heroicon-o-clipboard-document-check')
                                        ->placeholder('-')
                                        ->visible(fn($record) => $record->staff_required && $record->calcom_booking_uid),

                                    // Error Message (full-width, only if failed)
                                    TextEntry::make('sync_error_message')
                                        ->label('Fehlerdetails')
                                        ->color('danger')
                                        ->icon('heroicon-o-exclamation-triangle')
                                        ->visible(fn($record) => $record->calcom_sync_status === 'failed' && $record->sync_error_message)
                                        ->columnSpan(6),
                                ]),
                        ]),

                    // Summary Stats
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('synced_phases_count')
                                ->label('Erfolgreich synchronisiert')
                                ->getStateUsing(function($record) {
                                    return $record->phases()
                                        ->where('staff_required', true)
                                        ->where('calcom_sync_status', 'synced')
                                        ->count();
                                })
                                ->badge()
                                ->color('success')
                                ->icon('heroicon-o-check-circle'),

                            TextEntry::make('failed_phases_count')
                                ->label('Synchronisation fehlgeschlagen')
                                ->getStateUsing(function($record) {
                                    return $record->phases()
                                        ->where('staff_required', true)
                                        ->where('calcom_sync_status', 'failed')
                                        ->count();
                                })
                                ->badge()
                                ->color('danger')
                                ->icon('heroicon-o-x-circle')
                                ->visible(fn($record) => $record->phases()->where('calcom_sync_status', 'failed')->count() > 0),

                            TextEntry::make('pending_phases_count')
                                ->label('Synchronisation ausstehend')
                                ->getStateUsing(function($record) {
                                    return $record->phases()
                                        ->where('staff_required', true)
                                        ->where('calcom_sync_status', 'pending')
                                        ->count();
                                })
                                ->badge()
                                ->color('warning')
                                ->icon('heroicon-o-clock')
                                ->visible(fn($record) => $record->phases()->where('calcom_sync_status', 'pending')->count() > 0),
                        ]),
                ])
                ->visible(fn($record) => $record->service->isComposite())
                ->collapsible()
                ->collapsed(false), // Open by default
        ]);
}
```

---

### 4.3 Full Timeline View (Detailed)

**Datei**: `resources/views/filament/infolists/phases-timeline-full.blade.php`

```blade
@php
    $appointment = $getRecord();
    $phases = $appointment->phases()->orderBy('sequence_order')->get();

    if ($phases->isEmpty()) {
        echo '<div style="color: #9ca3af; padding: 1rem;">Keine Phasen vorhanden</div>';
        return;
    }

    $totalDuration = $appointment->service->getTotalDuration();
    $startTime = $appointment->starts_at;
@endphp

<div style="padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <!-- Timeline Header -->
    <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; color: white;">
        📅 Service-Timeline: {{ $appointment->service->name }}
    </div>

    <div style="font-size: 0.875rem; color: rgba(255,255,255,0.9); margin-bottom: 1.5rem;">
        Gesamtdauer: {{ $totalDuration }} Minuten | Start: {{ $startTime->format('d.m.Y H:i') }} Uhr
    </div>

    <!-- Timeline Bars -->
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        @foreach ($phases as $index => $phase)
            @php
                $widthPercent = ($phase->duration_minutes / $totalDuration) * 100;

                // Color based on status
                if ($phase->staff_required) {
                    $bgColor = match($phase->calcom_sync_status) {
                        'synced' => '#10b981', // green
                        'failed' => '#ef4444', // red
                        'pending' => '#f59e0b', // amber
                        default => '#6366f1' // indigo
                    };
                } else {
                    $bgColor = '#94a3b8'; // slate (gap)
                }

                $statusIcon = match($phase->calcom_sync_status) {
                    'synced' => '✓',
                    'failed' => '✗',
                    'pending' => '⏳',
                    default => ''
                };

                $statusText = $phase->staff_required
                    ? match($phase->calcom_sync_status) {
                        'synced' => 'Synchronisiert',
                        'failed' => 'Fehler',
                        'pending' => 'Ausstehend',
                        default => ''
                    }
                    : 'Einwirkzeit';
            @endphp

            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <!-- Segment Number Badge -->
                <div style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: white; color: #667eea; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.875rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    {{ $phase->segment_key }}
                </div>

                <!-- Bar Container -->
                <div style="flex: 1; display: flex; flex-direction: column; gap: 0.25rem;">
                    <!-- Time -->
                    <div style="font-size: 0.75rem; color: rgba(255,255,255,0.8);">
                        {{ \Carbon\Carbon::parse($phase->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($phase->end_time)->format('H:i') }} Uhr
                    </div>

                    <!-- Bar -->
                    <div style="position: relative; height: 3rem; background: {{ $bgColor }}; border-radius: 0.5rem; display: flex; align-items: center; justify-content: space-between; padding: 0 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.2); width: {{ $widthPercent }}%; min-width: 15rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem; color: white; font-weight: 600;">
                                {{ $phase->segment_name }}
                            </span>
                            @if($statusIcon)
                                <span style="font-size: 1rem;">{{ $statusIcon }}</span>
                            @endif
                        </div>

                        <div style="display: flex; flex-direction: column; align-items: flex-end;">
                            <span style="font-size: 0.75rem; color: rgba(255,255,255,0.9);">
                                {{ $phase->duration_minutes }} Min
                            </span>
                            @if($statusText)
                                <span style="font-size: 0.625rem; color: rgba(255,255,255,0.7);">
                                    {{ $statusText }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Legend -->
    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.2); display: flex; gap: 1.5rem; font-size: 0.75rem; color: rgba(255,255,255,0.9);">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <div style="width: 1.25rem; height: 1.25rem; background: #10b981; border-radius: 0.25rem;"></div>
            <span>✓ Synchronisiert</span>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <div style="width: 1.25rem; height: 1.25rem; background: #f59e0b; border-radius: 0.25rem;"></div>
            <span>⏳ Ausstehend</span>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <div style="width: 1.25rem; height: 1.25rem; background: #ef4444; border-radius: 0.25rem;"></div>
            <span>✗ Fehler</span>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <div style="width: 1.25rem; height: 1.25rem; background: #94a3b8; border-radius: 0.25rem;"></div>
            <span>Einwirkzeit</span>
        </div>
    </div>
</div>
```

**Result**: **Visuell beeindruckende** Timeline mit:
- 🎨 Gradient-Background (Purple)
- ⚫ Segment-Nummern in Kreisen
- 📊 Farbcodierte Status (Grün/Gelb/Rot)
- ⏱️ Exakte Zeitangaben
- 📏 Proportionale Balkenbreiten

---

## Part 5: Implementation Checklist

### Phase 1: CallResource List View (1 Stunde)
- [ ] `status-time-duration.blade.php` erweitern (Composite-Badge)
- [ ] `call-relationships.blade.php` erweitern (Segment-Breakdown)
- [ ] Testing mit Dauerwelle-Termin

### Phase 2: CallResource Detail View (1 Stunde)
- [ ] Neue Section "Gebuchte Termine & Segmente"
- [ ] `phases-timeline-compact.blade.php` erstellen
- [ ] Testing

### Phase 3: AppointmentResource List View (30 Min)
- [ ] Service-Spalte erweitern (Composite-Indicator)
- [ ] Typ-Badge hinzufügen
- [ ] Testing

### Phase 4: AppointmentResource Detail View (2 Stunden)
- [ ] Neue Section "Compound-Service Details"
- [ ] `phases-timeline-full.blade.php` erstellen (State-of-the-art Design)
- [ ] Segment-Table mit allen Details
- [ ] Testing

### Phase 5: Testing & QA (1 Stunde)
- [ ] Responsive Check (Desktop/Tablet/Mobile)
- [ ] Dark Mode Check
- [ ] UX Flow Test (Admin-Perspektive)
- [ ] Performance Check (N+1 Queries)

**Total**: ~5.5 Stunden

---

## Part 6: Event Type Problem - Separate Behandlung

Das `managedEventConfig` Problem ist **unabhängig** von der UI/UX-Darstellung.

**Zwei Parallelspuren**:
1. ✅ **UI/UX Implementation** (dieser Plan) - unabhängig von Event Types
2. 🔧 **Event Type Fix** - muss separat gelöst werden (Cal.com UI oder API)

**Temporary Workaround für Testing**:
- Bestehende funktionierende Event Types (Dauerwelle, etc.) verwenden für Tests
- Neue Services (Ansatzfärbung, etc.) erst nach Event Type Fix

**Langfristige Lösung**:
- Cal.com UI: Event Types neu erstellen **ohne** Managed-Option
- Oder: Cal.com Support kontaktieren für Metadata-Cleanup

---

## Conclusion

Dieser Plan bietet:
1. ✅ **Keine neuen Spalten** - nur bestehende erweitern
2. ✅ **State-of-the-art UI** - visuell beeindruckend & informativ
3. ✅ **Schnelles Verständnis** - alle Infos auf einen Blick
4. ✅ **Mouseover für Details** - essentials direkt sichtbar
5. ✅ **Mitarbeiter-Zuordnung** - klar & prominent
6. ✅ **Cal.com Sync-Status** - transparent dargestellt

**UX Principle**: "Information Hierarchy" - wichtigste Infos direkt sichtbar, Details via Mouseover/Expansion.

**Nächster Schritt**: Phase 1 starten (CallResource List View)?
