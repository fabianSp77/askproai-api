# Filament Admin Panel - Customer Appointment History Design

**Date**: 2025-10-10
**Purpose**: Design comprehensive customer timeline views showing calls, appointments, and modifications

## Executive Summary

Design Filament admin panel views to display complete customer appointment history with:
- Chronological timeline of calls + appointments
- Full appointment lifecycle tracking (created → rescheduled → cancelled)
- Call impact visualization (which appointments were created/modified)
- Metadata display (created_by, booking_source, etc.)

## Current State Analysis

### Existing Resources
- **CustomerResource**: Has AppointmentsRelationManager and CallsRelationManager
- **AppointmentResource**: Has basic infolist with metadata section
- **Database Fields Available**:
  - Appointments: `created_at`, `updated_at`, `status`, `source`, `booking_type`, `metadata`, `call_id`
  - Calls: `created_at`, `session_outcome`, `appointment_made`, `metadata`, `customer_id`
  - Relationships: `appointments.call_id` links to originating call

### Current Gaps
1. No unified timeline view combining calls + appointments
2. Appointment history doesn't show modification tracking
3. Call detail view doesn't show appointment impact
4. No visual indicators for appointment lifecycle events
5. Metadata fields not prominently displayed

---

## Design Solutions

## 1. Customer Timeline Widget

**Location**: `/var/www/api-gateway/app/Filament/Resources/CustomerResource/Widgets/CustomerTimelineWidget.php`

**Purpose**: Unified chronological view of all customer interactions

### Features
```php
// Widget Configuration
protected static ?int $sort = 1;
protected static ?string $heading = 'Kundenhistorie';
protected static ?string $icon = 'heroicon-o-clock';
```

### Timeline View Design
```
┌─────────────────────────────────────────────────────────────┐
│ 🕐 Kundenhistorie                                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ ● 10.10.2025 14:30                                         │
│   📞 ANRUF (3m 45s)                                        │
│   Ergebnis: Termin vereinbart                              │
│   ↳ 🔗 Termin erstellt: 15.10.2025 10:00                  │
│                                                             │
│ ● 08.10.2025 09:15                                         │
│   📅 TERMIN VERSCHOBEN                                     │
│   Von: 10.10.2025 → Nach: 15.10.2025                      │
│   Grund: Kundenwunsch via Telefon                          │
│   Geändert von: AI Assistant                               │
│                                                             │
│ ● 05.10.2025 16:20                                         │
│   📞 ANRUF (1m 12s)                                        │
│   Ergebnis: Termin verschieben                             │
│   ↳ 🔗 Termin #123 verschoben                             │
│                                                             │
│ ● 01.10.2025 11:00                                         │
│   📅 TERMIN ABGESCHLOSSEN ✅                               │
│   Service: Massage (60 Min)                                 │
│   Mitarbeiter: Anna Schmidt                                 │
│   Preis: 80,00 €                                           │
│                                                             │
│ ● 25.09.2025 13:45                                         │
│   📞 ANRUF (5m 30s)                                        │
│   Ergebnis: Termin vereinbart                              │
│   ↳ 🔗 Termin erstellt: 01.10.2025 11:00                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Color Coding
- 🟢 Green: Appointment completed successfully
- 🟡 Yellow: Appointment rescheduled
- 🔴 Red: Appointment cancelled
- 🔵 Blue: Call completed
- ⚪ Gray: Upcoming appointment

### Data Structure
```php
// Fetch merged timeline
$timeline = collect()
    ->merge($this->record->calls()->get()->map(fn($call) => [
        'type' => 'call',
        'timestamp' => $call->created_at,
        'icon' => 'heroicon-o-phone',
        'color' => 'blue',
        'title' => "Anruf ({$this->formatDuration($call->duration_sec)})",
        'description' => $call->session_outcome,
        'metadata' => [
            'duration' => $call->duration_sec,
            'outcome' => $call->session_outcome,
            'appointment_made' => $call->appointment_made,
        ],
        'related_appointments' => $call->appointments,
    ]))
    ->merge($this->record->appointments()->get()->map(fn($apt) => [
        'type' => 'appointment',
        'timestamp' => $apt->updated_at,
        'icon' => 'heroicon-o-calendar',
        'color' => $this->getStatusColor($apt->status),
        'title' => $this->getEventTitle($apt),
        'description' => "{$apt->service->name} - {$apt->staff->name}",
        'metadata' => [
            'status' => $apt->status,
            'source' => $apt->source,
            'created_by' => $apt->created_by,
            'call_id' => $apt->call_id,
        ],
    ]))
    ->sortByDesc('timestamp');
```

### Implementation Code Snippet
```php
<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class CustomerTimelineWidget extends Widget
{
    protected static string $view = 'filament.resources.customer-resource.widgets.customer-timeline';
    protected static ?int $sort = 1;

    public Customer $record;

    protected function getViewData(): array
    {
        return [
            'timeline' => $this->buildTimeline(),
        ];
    }

    protected function buildTimeline(): Collection
    {
        $events = collect();

        // Add calls
        $this->record->calls()
            ->with('appointments')
            ->orderBy('created_at', 'desc')
            ->get()
            ->each(function ($call) use ($events) {
                $events->push([
                    'type' => 'call',
                    'timestamp' => $call->created_at,
                    'data' => $call,
                    'icon' => 'heroicon-o-phone',
                    'color' => 'blue',
                ]);
            });

        // Add appointments with modification tracking
        $this->record->appointments()
            ->with(['service', 'staff', 'call'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->each(function ($apt) use ($events) {
                // Check if this is a modification
                $isModified = $apt->created_at->ne($apt->updated_at);

                $events->push([
                    'type' => 'appointment',
                    'subtype' => $isModified ? 'modified' : 'created',
                    'timestamp' => $apt->updated_at,
                    'data' => $apt,
                    'icon' => 'heroicon-o-calendar',
                    'color' => $this->getAppointmentColor($apt),
                ]);
            });

        return $events->sortByDesc('timestamp');
    }

    protected function getAppointmentColor($appointment): string
    {
        return match($appointment->status) {
            'completed' => 'success',
            'confirmed' => 'info',
            'cancelled' => 'danger',
            'no_show' => 'warning',
            default => 'gray',
        };
    }
}
```

---

## 2. Appointment History Section (Enhanced Infolist)

**Location**: Add to `AppointmentResource::infolist()`

**Purpose**: Complete lifecycle view of single appointment

### Enhanced Metadata Display
```
┌─────────────────────────────────────────────────────────────┐
│ 📝 Änderungshistorie                                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ ✅ Termin abgeschlossen                                    │
│    10.10.2025 11:00 - 12:00                                │
│    Durchgeführt von: Anna Schmidt                           │
│                                                             │
│ 🔄 Termin verschoben                                       │
│    08.10.2025 14:30                                        │
│    Von: 10.10.2025 10:00                                   │
│    Nach: 10.10.2025 11:00                                  │
│    Geändert via: AI Assistant                              │
│    Grund: Kundenwunsch                                      │
│                                                             │
│ ✅ Termin bestätigt                                        │
│    05.10.2025 09:15                                        │
│    Bestätigt via: SMS                                       │
│                                                             │
│ 📅 Termin erstellt                                         │
│    01.10.2025 16:45                                        │
│    Erstellt via: Telefon-KI                                 │
│    Call ID: #retell_abc123                                 │
│    Buchungsquelle: AI Assistant                             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Implementation
```php
// Add to AppointmentResource::infolist()
InfoSection::make('Änderungshistorie')
    ->schema([
        // Current Status
        InfoGrid::make(2)
            ->schema([
                TextEntry::make('status')
                    ->label('Aktueller Status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'completed' => 'success',
                        'confirmed' => 'info',
                        'cancelled' => 'danger',
                        'no_show' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'completed' => '✅ Abgeschlossen',
                        'confirmed' => '✓ Bestätigt',
                        'cancelled' => '❌ Storniert',
                        'no_show' => '👻 Nicht erschienen',
                        default => $state,
                    }),

                TextEntry::make('updated_at')
                    ->label('Letzte Änderung')
                    ->dateTime('d.m.Y H:i')
                    ->icon('heroicon-o-clock'),
            ]),

        // Booking Origin
        InfoGrid::make(3)
            ->schema([
                TextEntry::make('source')
                    ->label('Buchungsquelle')
                    ->badge()
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'phone' => '📞 Telefon',
                        'online' => '💻 Online',
                        'walk_in' => '🚶 Walk-In',
                        'app' => '📱 App',
                        'ai_assistant' => '🤖 KI-Assistent',
                        default => $state ?? 'Unbekannt',
                    }),

                TextEntry::make('booking_type')
                    ->label('Buchungstyp')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'single' => 'Einzeltermin',
                        'series' => 'Serie',
                        'group' => 'Gruppe',
                        'package' => 'Paket',
                        default => $state ?? 'Standard',
                    }),

                TextEntry::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->icon('heroicon-o-calendar-days'),
            ]),

        // Call Origin (if from call)
        TextEntry::make('call.retell_call_id')
            ->label('Erstellt durch Anruf')
            ->icon('heroicon-o-phone-arrow-down-left')
            ->badge()
            ->color('info')
            ->url(fn ($record) => $record->call_id
                ? route('filament.admin.resources.calls.view', ['record' => $record->call_id])
                : null
            )
            ->visible(fn ($record) => $record->call_id !== null)
            ->formatStateUsing(fn ($state, $record) =>
                "Anruf vom " . $record->call->created_at->format('d.m.Y H:i')
            ),

        // Modification Metadata
        TextEntry::make('metadata')
            ->label('Zusätzliche Informationen')
            ->columnSpanFull()
            ->formatStateUsing(function ($state) {
                if (empty($state) || !is_array($state)) {
                    return 'Keine zusätzlichen Informationen';
                }

                $output = [];
                foreach ($state as $key => $value) {
                    $output[] = "**{$key}**: {$value}";
                }

                return implode("\n", $output);
            })
            ->markdown()
            ->visible(fn ($state) => !empty($state)),
    ])
    ->icon('heroicon-o-clock-arrow-path')
    ->collapsible()
    ->collapsed(false),
```

---

## 3. Call Impact View

**Location**: Add to `CallsRelationManager` or create dedicated view

**Purpose**: Show appointments created/modified by each call

### Table Enhancement
```php
// Add to CallsRelationManager::table()
Tables\Columns\TextColumn::make('appointments_count')
    ->label('Termine')
    ->counts('appointments')
    ->badge()
    ->color('success')
    ->icon('heroicon-o-calendar')
    ->description(fn ($record) =>
        $record->appointment_made
            ? '✅ Termin vereinbart'
            : ($record->session_outcome === 'appointment_cancelled'
                ? '❌ Termin storniert'
                : '—')
    ),

Tables\Columns\TextColumn::make('session_outcome')
    ->label('Anrufergebnis')
    ->badge()
    ->formatStateUsing(fn ($state) => match($state) {
        'appointment_scheduled' => '📅 Termin vereinbart',
        'appointment_cancelled' => '❌ Termin abgesagt',
        'appointment_rescheduled' => '🔄 Termin verschoben',
        'callback_requested' => '📞 Rückruf erwünscht',
        'info_provided' => 'ℹ️ Info gegeben',
        default => $state ?? 'Keine Aktion',
    })
    ->color(fn ($state) => match($state) {
        'appointment_scheduled' => 'success',
        'appointment_cancelled' => 'danger',
        'appointment_rescheduled' => 'warning',
        default => 'gray',
    }),
```

### Call Detail Infolist Addition
```php
// Add new section to show appointments created by this call
InfoSection::make('Termine aus diesem Anruf')
    ->schema([
        RepeatableEntry::make('appointments')
            ->schema([
                TextEntry::make('starts_at')
                    ->label('Termin')
                    ->dateTime('d.m.Y H:i')
                    ->icon('heroicon-o-calendar'),

                TextEntry::make('service.name')
                    ->label('Service'),

                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'completed' => 'success',
                        'confirmed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextEntry::make('id')
                    ->label('Aktionen')
                    ->formatStateUsing(fn () => 'Details anzeigen')
                    ->url(fn ($record) => route('filament.admin.resources.appointments.view',
                        ['record' => $record->id]
                    ))
                    ->icon('heroicon-o-arrow-right'),
            ])
            ->columns(4)
            ->grid(1),
    ])
    ->icon('heroicon-o-link')
    ->collapsible()
    ->visible(fn ($record) => $record->appointments()->exists()),
```

---

## 4. Enhanced Appointment RelationManager

**Location**: `CustomerResource/RelationManagers/AppointmentsRelationManager.php`

### Table Enhancements
```php
// Add lifecycle indicators
Tables\Columns\TextColumn::make('lifecycle_status')
    ->label('Lebenszyklus')
    ->getStateUsing(function ($record) {
        $isModified = $record->created_at->ne($record->updated_at);
        $hasCall = $record->call_id !== null;

        $badges = [];
        if ($hasCall) {
            $badges[] = '📞 Via Call';
        }
        if ($isModified && $record->status === 'cancelled') {
            $badges[] = '❌ Storniert';
        } elseif ($isModified) {
            $badges[] = '🔄 Geändert';
        }

        return implode(' ', $badges);
    })
    ->html(),

// Add modification tracking
Tables\Columns\TextColumn::make('modification_info')
    ->label('Änderungen')
    ->getStateUsing(function ($record) {
        if ($record->created_at->eq($record->updated_at)) {
            return '—';
        }

        $diff = $record->updated_at->diffForHumans($record->created_at);
        return "Geändert: {$diff}";
    })
    ->description(fn ($record) =>
        $record->created_at->ne($record->updated_at)
            ? "Erstellt: " . $record->created_at->format('d.m.Y H:i')
            : null
    )
    ->toggleable(),
```

### Expand Row for Details
```php
// Add expandable row to show full modification history
public function table(Table $table): Table
{
    return $table
        // ... existing columns ...
        ->expandableRows()
        ->expandableRowContent(fn ($record) => view(
            'filament.tables.appointment-history-expansion',
            [
                'appointment' => $record,
                'modifications' => $this->getModificationHistory($record),
            ]
        ));
}

protected function getModificationHistory($appointment): Collection
{
    return collect([
        [
            'event' => 'created',
            'timestamp' => $appointment->created_at,
            'details' => [
                'source' => $appointment->source,
                'booking_type' => $appointment->booking_type,
                'call_id' => $appointment->call_id,
            ],
        ],
        ...$this->getStatusChanges($appointment),
        [
            'event' => 'last_updated',
            'timestamp' => $appointment->updated_at,
            'details' => [
                'current_status' => $appointment->status,
            ],
        ],
    ])->sortByDesc('timestamp');
}
```

---

## 5. Blade View Templates

### Customer Timeline View
**File**: `/var/www/api-gateway/resources/views/filament/resources/customer-resource/widgets/customer-timeline.blade.php`

```blade
<x-filament-widgets::widget>
    <x-filament::section
        heading="Kundenhistorie"
        icon="heroicon-o-clock"
        collapsible
    >
        <div class="space-y-4">
            @forelse($timeline as $event)
                <div class="flex gap-4 border-l-4 pl-4 py-2 {{ $this->getBorderColorClass($event['color']) }}">
                    <!-- Icon & Timestamp -->
                    <div class="flex-shrink-0 w-20">
                        <div class="text-sm text-gray-500">
                            {{ $event['timestamp']->format('d.m.Y') }}
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $event['timestamp']->format('H:i') }}
                        </div>
                    </div>

                    <!-- Event Icon -->
                    <div class="flex-shrink-0">
                        <x-filament::icon
                            :icon="$event['icon']"
                            class="w-6 h-6 {{ $this->getIconColorClass($event['color']) }}"
                        />
                    </div>

                    <!-- Event Details -->
                    <div class="flex-grow">
                        @if($event['type'] === 'call')
                            <div class="font-semibold text-gray-900 dark:text-gray-100">
                                Anruf ({{ $this->formatDuration($event['data']->duration_sec) }})
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Ergebnis: {{ $this->formatOutcome($event['data']->session_outcome) }}
                            </div>

                            @if($event['data']->appointments->count() > 0)
                                <div class="mt-2 ml-4 text-sm">
                                    @foreach($event['data']->appointments as $apt)
                                        <div class="flex items-center gap-2 text-blue-600">
                                            <x-filament::icon icon="heroicon-o-link" class="w-4 h-4" />
                                            <a href="{{ route('filament.admin.resources.appointments.view', $apt->id) }}"
                                               class="hover:underline">
                                                Termin erstellt: {{ $apt->starts_at->format('d.m.Y H:i') }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                        @elseif($event['type'] === 'appointment')
                            <div class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->getAppointmentEventTitle($event['data']) }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $event['data']->service->name }} - {{ $event['data']->staff->name }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $event['data']->starts_at->format('d.m.Y H:i') }} - {{ $event['data']->ends_at->format('H:i') }}
                            </div>

                            @if($event['data']->call_id)
                                <div class="mt-1 text-xs text-gray-500">
                                    <x-filament::icon icon="heroicon-o-phone" class="w-3 h-3 inline" />
                                    Via Anruf erstellt
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">
                    Keine Historie vorhanden
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
```

### Appointment History Expansion Row
**File**: `/var/www/api-gateway/resources/views/filament/tables/appointment-history-expansion.blade.php`

```blade
<div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
    <h4 class="text-sm font-semibold mb-3 text-gray-900 dark:text-gray-100">
        Änderungshistorie
    </h4>

    <div class="space-y-2">
        @foreach($modifications as $mod)
            <div class="flex items-start gap-3 text-sm">
                <div class="flex-shrink-0 w-24 text-gray-500">
                    {{ $mod['timestamp']->format('d.m.Y H:i') }}
                </div>

                <div class="flex-shrink-0">
                    @if($mod['event'] === 'created')
                        <x-filament::badge color="success">Erstellt</x-filament::badge>
                    @elseif($mod['event'] === 'cancelled')
                        <x-filament::badge color="danger">Storniert</x-filament::badge>
                    @elseif($mod['event'] === 'rescheduled')
                        <x-filament::badge color="warning">Verschoben</x-filament::badge>
                    @else
                        <x-filament::badge color="gray">{{ $mod['event'] }}</x-filament::badge>
                    @endif
                </div>

                <div class="flex-grow text-gray-600 dark:text-gray-400">
                    @foreach($mod['details'] as $key => $value)
                        <div>
                            <span class="font-medium">{{ $key }}:</span> {{ $value }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
```

---

## 6. Mobile Responsiveness

### Responsive Design Principles

1. **Timeline on Mobile**: Stack vertically with compact spacing
```blade
<div class="grid grid-cols-1 md:grid-cols-[auto_auto_1fr] gap-2 md:gap-4">
    <!-- Adapts to single column on mobile -->
</div>
```

2. **Table Columns**: Use `toggleable(isToggledHiddenByDefault: true)` for secondary info
```php
Tables\Columns\TextColumn::make('modification_info')
    ->toggleable(isToggledHiddenByDefault: true), // Hidden on mobile
```

3. **Info Sections**: Collapse by default on mobile
```php
InfoSection::make('Änderungshistorie')
    ->collapsed(fn () => request()->header('User-Agent') &&
        preg_match('/Mobile/', request()->header('User-Agent'))
    ),
```

---

## 7. Performance Considerations

### Eager Loading Strategy
```php
// In CustomerResource::getEloquentQuery()
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with([
            'appointments' => fn($q) => $q->with(['service', 'staff', 'call']),
            'calls' => fn($q) => $q->with(['appointments']),
        ]);
}

// In timeline widget
protected function buildTimeline(): Collection
{
    // Prevent N+1 by eager loading in single query
    $customer = $this->record->load([
        'appointments.service',
        'appointments.staff',
        'appointments.call',
        'calls.appointments',
    ]);

    // Build timeline from loaded relationships
    return $this->mergeTimelineEvents($customer);
}
```

### Pagination for Large Histories
```php
// Add pagination to timeline widget
protected int $itemsPerPage = 20;

protected function getViewData(): array
{
    return [
        'timeline' => $this->buildTimeline()->take($this->itemsPerPage),
        'hasMore' => $this->buildTimeline()->count() > $this->itemsPerPage,
    ];
}
```

---

## 8. Color Scheme Reference

### Status Colors
```php
protected function getStatusColors(): array
{
    return [
        // Appointments
        'completed' => 'success',      // Green
        'confirmed' => 'info',         // Blue
        'pending' => 'warning',        // Yellow
        'cancelled' => 'danger',       // Red
        'no_show' => 'gray',          // Gray

        // Calls
        'answered' => 'success',       // Green
        'missed' => 'danger',          // Red
        'voicemail' => 'warning',      // Yellow

        // Modifications
        'created' => 'success',        // Green
        'rescheduled' => 'warning',    // Yellow
        'cancelled' => 'danger',       // Red
    ];
}
```

### Icon Mapping
```php
protected function getEventIcons(): array
{
    return [
        'call' => 'heroicon-o-phone',
        'appointment_created' => 'heroicon-o-calendar-plus',
        'appointment_rescheduled' => 'heroicon-o-arrow-path',
        'appointment_cancelled' => 'heroicon-o-x-circle',
        'appointment_completed' => 'heroicon-o-check-circle',
        'note_added' => 'heroicon-o-document-text',
    ];
}
```

---

## 9. Implementation Priority

### Phase 1: Essential Views (Week 1)
1. ✅ Enhanced Appointment Infolist with metadata display
2. ✅ Call impact view in CallsRelationManager
3. ✅ Appointment lifecycle indicators in tables

### Phase 2: Timeline Widget (Week 2)
1. ✅ Customer Timeline Widget with basic events
2. ✅ Blade view template with responsive design
3. ✅ Event merging and sorting logic

### Phase 3: Advanced Features (Week 3)
1. ✅ Expandable row details for appointments
2. ✅ Modification history tracking
3. ✅ Performance optimization with eager loading

### Phase 4: Polish (Week 4)
1. ✅ Mobile responsiveness testing
2. ✅ Color scheme consistency
3. ✅ User experience testing and refinement

---

## 10. File Locations Summary

### New Files to Create
```
app/Filament/Resources/CustomerResource/Widgets/
  └─ CustomerTimelineWidget.php

resources/views/filament/resources/customer-resource/widgets/
  └─ customer-timeline.blade.php

resources/views/filament/tables/
  └─ appointment-history-expansion.blade.php
```

### Files to Modify
```
app/Filament/Resources/AppointmentResource.php
  → Enhance infolist() method

app/Filament/Resources/CustomerResource/RelationManagers/AppointmentsRelationManager.php
  → Add lifecycle columns and expansion

app/Filament/Resources/CustomerResource/RelationManagers/CallsRelationManager.php
  → Add appointment impact view

app/Filament/Resources/CustomerResource.php
  → Register new widget in getWidgets()
```

---

## 11. Usage Examples

### Admin Viewing Customer History
1. Navigate to Customers → [Customer Name]
2. See Customer Timeline Widget at top showing chronological events
3. Scroll through appointments table with lifecycle indicators
4. Expand appointment rows to see full modification history
5. Click on call reference to see which call created the appointment

### Admin Viewing Appointment Details
1. Navigate to Appointments → [Appointment ID]
2. View "Änderungshistorie" section showing:
   - Booking source (AI Assistant, Phone, etc.)
   - Creation timestamp
   - Call origin (if applicable)
   - Modification timestamps
   - Status changes

### Admin Viewing Call Impact
1. Navigate to Customers → [Customer] → Calls tab
2. See "Termine" column showing count of appointments from each call
3. View session outcome badges
4. Expand call details to see linked appointments

---

## Conclusion

This design provides comprehensive customer appointment history tracking with:

- ✅ Unified timeline combining calls + appointments
- ✅ Full appointment lifecycle visibility
- ✅ Call impact tracking
- ✅ Prominent metadata display
- ✅ Mobile-responsive design
- ✅ Performance-optimized queries
- ✅ Consistent color coding

**Next Steps**: Implement Phase 1 files and test with production data.
