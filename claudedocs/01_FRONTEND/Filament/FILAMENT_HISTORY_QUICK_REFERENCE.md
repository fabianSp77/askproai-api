# Filament Appointment History - Quick Reference Guide

**Quick navigation for implementing customer history views**

---

## Visual Overview

```
Customer Detail Page
├─ Customer Timeline Widget ────────────────────┐
│  ┌────────────────────────────────────────┐  │
│  │ 🕐 Kundenhistorie                      │  │
│  ├────────────────────────────────────────┤  │
│  │ ● 10.10 14:30 📞 ANRUF               │  │
│  │   ↳ 🔗 Termin erstellt               │  │
│  │ ● 08.10 09:15 📅 TERMIN VERSCHOBEN    │  │
│  │ ● 01.10 11:00 📅 TERMIN ✅            │  │
│  └────────────────────────────────────────┘  │
└───────────────────────────────────────────────┘

├─ Appointments RelationManager ────────────────┐
│  Table with lifecycle indicators              │
│  [Expand row] → Full modification history     │
└───────────────────────────────────────────────┘

├─ Calls RelationManager ───────────────────────┐
│  Shows appointments created by each call       │
└───────────────────────────────────────────────┘

Appointment Detail Page
├─ Enhanced Infolist ───────────────────────────┐
│  📝 Änderungshistorie                         │
│  ├─ Current Status                            │
│  ├─ Booking Source & Type                     │
│  ├─ Call Origin (if from call)                │
│  └─ Metadata Display                          │
└───────────────────────────────────────────────┘
```

---

## 1. Customer Timeline Widget

### File Structure
```
app/Filament/Resources/CustomerResource/Widgets/CustomerTimelineWidget.php
resources/views/filament/resources/customer-resource/widgets/customer-timeline.blade.php
```

### Widget Class (Complete)
```php
<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Customer;
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
        // Eager load to prevent N+1
        $customer = $this->record->load([
            'calls' => fn($q) => $q->with('appointments')->orderBy('created_at', 'desc'),
            'appointments' => fn($q) => $q->with(['service', 'staff', 'call'])->orderBy('updated_at', 'desc'),
        ]);

        $events = collect();

        // Add call events
        foreach ($customer->calls as $call) {
            $events->push([
                'type' => 'call',
                'timestamp' => $call->created_at,
                'data' => $call,
                'icon' => 'heroicon-o-phone',
                'color' => 'blue',
            ]);
        }

        // Add appointment events
        foreach ($customer->appointments as $apt) {
            $isModified = $apt->created_at->ne($apt->updated_at);

            $events->push([
                'type' => 'appointment',
                'subtype' => $this->getAppointmentSubtype($apt),
                'timestamp' => $apt->updated_at,
                'data' => $apt,
                'icon' => 'heroicon-o-calendar',
                'color' => $this->getAppointmentColor($apt),
            ]);
        }

        return $events->sortByDesc('timestamp')->take(50);
    }

    protected function getAppointmentSubtype($appointment): string
    {
        if ($appointment->status === 'cancelled') {
            return 'cancelled';
        }

        if ($appointment->status === 'completed') {
            return 'completed';
        }

        if ($appointment->created_at->ne($appointment->updated_at)) {
            return 'rescheduled';
        }

        return 'created';
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

    public function formatDuration(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        return $minutes > 0 ? "{$minutes}m {$secs}s" : "{$secs}s";
    }

    public function formatOutcome(?string $outcome): string
    {
        return match($outcome) {
            'appointment_scheduled' => 'Termin vereinbart',
            'appointment_cancelled' => 'Termin abgesagt',
            'appointment_rescheduled' => 'Termin verschoben',
            'callback_requested' => 'Rückruf erwünscht',
            'info_provided' => 'Info gegeben',
            default => $outcome ?? 'Keine Aktion',
        };
    }

    public function getAppointmentEventTitle($appointment): string
    {
        return match($this->getAppointmentSubtype($appointment)) {
            'completed' => 'TERMIN ABGESCHLOSSEN ✅',
            'cancelled' => 'TERMIN STORNIERT ❌',
            'rescheduled' => 'TERMIN VERSCHOBEN 🔄',
            'created' => 'TERMIN ERSTELLT 📅',
            default => 'TERMIN',
        };
    }

    public function getBorderColorClass(string $color): string
    {
        return match($color) {
            'success' => 'border-green-500',
            'info' => 'border-blue-500',
            'warning' => 'border-yellow-500',
            'danger' => 'border-red-500',
            'blue' => 'border-blue-400',
            default => 'border-gray-300',
        };
    }

    public function getIconColorClass(string $color): string
    {
        return match($color) {
            'success' => 'text-green-600',
            'info' => 'text-blue-600',
            'warning' => 'text-yellow-600',
            'danger' => 'text-red-600',
            'blue' => 'text-blue-500',
            default => 'text-gray-500',
        };
    }
}
```

### Blade View (Complete)
```blade
{{-- resources/views/filament/resources/customer-resource/widgets/customer-timeline.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section
        heading="Kundenhistorie"
        description="Chronologische Übersicht aller Interaktionen"
        icon="heroicon-o-clock"
        collapsible
    >
        <div class="space-y-3">
            @forelse($timeline as $event)
                <div class="flex gap-3 border-l-4 pl-4 py-3 {{ $this->getBorderColorClass($event['color']) }} hover:bg-gray-50 dark:hover:bg-gray-800 transition">

                    {{-- Timestamp --}}
                    <div class="flex-shrink-0 w-16 md:w-20">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $event['timestamp']->format('d.m.Y') }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $event['timestamp']->format('H:i') }}
                        </div>
                    </div>

                    {{-- Icon --}}
                    <div class="flex-shrink-0 mt-1">
                        <x-filament::icon
                            :icon="$event['icon']"
                            class="w-5 h-5 {{ $this->getIconColorClass($event['color']) }}"
                        />
                    </div>

                    {{-- Event Content --}}
                    <div class="flex-grow min-w-0">
                        @if($event['type'] === 'call')
                            {{-- Call Event --}}
                            <div class="font-semibold text-gray-900 dark:text-gray-100">
                                📞 ANRUF ({{ $this->formatDuration($event['data']->duration_sec) }})
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                                Ergebnis: {{ $this->formatOutcome($event['data']->session_outcome) }}
                            </div>

                            {{-- Linked Appointments --}}
                            @if($event['data']->appointments && $event['data']->appointments->count() > 0)
                                <div class="mt-2 space-y-1">
                                    @foreach($event['data']->appointments as $apt)
                                        <div class="ml-4 flex items-center gap-2 text-sm">
                                            <x-filament::icon
                                                icon="heroicon-o-link"
                                                class="w-4 h-4 text-blue-500 flex-shrink-0"
                                            />
                                            <a href="{{ route('filament.admin.resources.appointments.view', ['record' => $apt->id]) }}"
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 hover:underline truncate">
                                                🔗 Termin erstellt: {{ $apt->starts_at->format('d.m.Y H:i') }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                        @elseif($event['type'] === 'appointment')
                            {{-- Appointment Event --}}
                            <div class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->getAppointmentEventTitle($event['data']) }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-0.5 truncate">
                                {{ $event['data']->service->name }} • {{ $event['data']->staff->name }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2 flex-wrap">
                                <span>
                                    {{ $event['data']->starts_at->format('d.m.Y H:i') }} - {{ $event['data']->ends_at->format('H:i') }}
                                </span>

                                @if($event['data']->source)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700">
                                        {{ match($event['data']->source) {
                                            'phone' => '📞 Telefon',
                                            'online' => '💻 Online',
                                            'ai_assistant' => '🤖 KI',
                                            default => $event['data']->source,
                                        } }}
                                    </span>
                                @endif

                                @if($event['data']->call_id)
                                    <a href="{{ route('filament.admin.resources.calls.view', ['record' => $event['data']->call_id]) }}"
                                       class="inline-flex items-center gap-1 text-blue-600 hover:underline">
                                        <x-filament::icon icon="heroicon-o-phone-arrow-down-left" class="w-3 h-3" />
                                        Via Anruf
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <x-filament::icon
                        icon="heroicon-o-clock"
                        class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-3"
                    />
                    <p class="text-gray-500 dark:text-gray-400">
                        Keine Historie vorhanden
                    </p>
                </div>
            @endforelse
        </div>

        @if($timeline->count() >= 50)
            <div class="mt-4 text-center">
                <x-filament::badge color="info">
                    Zeige die letzten 50 Ereignisse
                </x-filament::badge>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
```

### Register Widget
```php
// In CustomerResource.php

public static function getWidgets(): array
{
    return [
        Widgets\CustomerTimelineWidget::class, // Add this
        Widgets\CustomerOverview::class,
        Widgets\CustomerJourneyFunnel::class,
        Widgets\CustomerRiskAlerts::class,
    ];
}
```

---

## 2. Enhanced Appointment Infolist

### Add to AppointmentResource::infolist()

```php
// In AppointmentResource.php, add to infolist() schema array:

InfoSection::make('Buchungsdetails')
    ->schema([
        InfoGrid::make(3)
            ->schema([
                TextEntry::make('source')
                    ->label('Buchungsquelle')
                    ->badge()
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->color('info')
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
                    ->color('gray')
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

        // Call Origin Link
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
                "📞 Anruf vom " . $record->call->created_at->format('d.m.Y H:i')
            ),

        // Modification Status
        InfoGrid::make(2)
            ->schema([
                TextEntry::make('lifecycle_status')
                    ->label('Lebenszyklus')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->created_at->eq($record->updated_at)) {
                            return 'Unverändert';
                        }

                        return match($record->status) {
                            'cancelled' => '❌ Storniert',
                            'completed' => '✅ Abgeschlossen',
                            default => '🔄 Geändert',
                        };
                    })
                    ->color(fn ($record) => match($record->status) {
                        'cancelled' => 'danger',
                        'completed' => 'success',
                        default => $record->created_at->eq($record->updated_at) ? 'gray' : 'warning',
                    }),

                TextEntry::make('updated_at')
                    ->label('Letzte Änderung')
                    ->dateTime('d.m.Y H:i')
                    ->icon('heroicon-o-clock')
                    ->description(fn ($record) =>
                        $record->created_at->ne($record->updated_at)
                            ? $record->updated_at->diffForHumans($record->created_at)
                            : null
                    ),
            ]),

        // Metadata Display
        TextEntry::make('metadata')
            ->label('Zusätzliche Informationen')
            ->columnSpanFull()
            ->formatStateUsing(function ($state) {
                if (empty($state) || !is_array($state)) {
                    return null;
                }

                $items = [];
                foreach ($state as $key => $value) {
                    $formattedKey = str_replace('_', ' ', ucfirst($key));
                    $formattedValue = is_array($value) ? json_encode($value) : $value;
                    $items[] = "**{$formattedKey}**: {$formattedValue}";
                }

                return implode("  \n", $items);
            })
            ->markdown()
            ->visible(fn ($state) => !empty($state)),
    ])
    ->icon('heroicon-o-information-circle')
    ->collapsible(),
```

---

## 3. Call Impact View (CallsRelationManager)

### Add to table() columns

```php
// In CustomerResource/RelationManagers/CallsRelationManager.php

Tables\Columns\TextColumn::make('appointments_impact')
    ->label('Termine')
    ->getStateUsing(function ($record) {
        $count = $record->appointments()->count();

        if ($count === 0) {
            return match($record->session_outcome) {
                'appointment_cancelled' => '❌ Storniert',
                'callback_requested' => '📞 Rückruf',
                default => '—',
            };
        }

        return "{$count} " . ($count === 1 ? 'Termin' : 'Termine');
    })
    ->badge()
    ->color(fn ($record) => $record->appointments()->count() > 0 ? 'success' : 'gray')
    ->icon(fn ($record) => $record->appointments()->count() > 0 ? 'heroicon-o-check-circle' : null)
    ->description(fn ($record) =>
        $record->appointment_made && $record->appointments()->count() > 0
            ? '✅ Erfolgreich gebucht'
            : null
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
        'no_action' => 'Keine Aktion',
        default => $state ?? '—',
    })
    ->color(fn ($state) => match($state) {
        'appointment_scheduled' => 'success',
        'appointment_cancelled' => 'danger',
        'appointment_rescheduled' => 'warning',
        'callback_requested' => 'info',
        default => 'gray',
    }),
```

### Add expand action to view appointments

```php
// In CallsRelationManager::table()

->actions([
    Tables\Actions\Action::make('viewAppointments')
        ->label('Termine anzeigen')
        ->icon('heroicon-o-calendar')
        ->color('info')
        ->visible(fn ($record) => $record->appointments()->count() > 0)
        ->modalHeading('Termine aus diesem Anruf')
        ->modalContent(fn ($record) => view(
            'filament.tables.call-appointments-modal',
            ['appointments' => $record->appointments]
        ))
        ->modalSubmitAction(false)
        ->modalCancelActionLabel('Schließen'),

    Tables\Actions\EditAction::make(),
    // ... existing actions
])
```

### Modal View
```blade
{{-- resources/views/filament/tables/call-appointments-modal.blade.php --}}

<div class="space-y-3">
    @foreach($appointments as $apt)
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800">
            <div class="flex justify-between items-start">
                <div class="flex-grow">
                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ $apt->service->name }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $apt->starts_at->format('d.m.Y H:i') }} - {{ $apt->ends_at->format('H:i') }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        Mitarbeiter: {{ $apt->staff->name }}
                    </div>
                </div>

                <div class="flex flex-col items-end gap-2">
                    <x-filament::badge
                        :color="match($apt->status) {
                            'completed' => 'success',
                            'confirmed' => 'info',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }"
                    >
                        {{ match($apt->status) {
                            'completed' => '✅ Abgeschlossen',
                            'confirmed' => '✓ Bestätigt',
                            'cancelled' => '❌ Storniert',
                            'no_show' => '👻 Nicht erschienen',
                            default => $apt->status,
                        } }}
                    </x-filament::badge>

                    <a href="{{ route('filament.admin.resources.appointments.view', ['record' => $apt->id]) }}"
                       class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                        Details anzeigen
                        <x-filament::icon icon="heroicon-o-arrow-right" class="w-4 h-4" />
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>
```

---

## 4. Appointment Lifecycle Indicators

### Add to AppointmentsRelationManager

```php
// In CustomerResource/RelationManagers/AppointmentsRelationManager.php

Tables\Columns\TextColumn::make('lifecycle_badges')
    ->label('Status')
    ->getStateUsing(function ($record) {
        $badges = [];

        // Call origin
        if ($record->call_id) {
            $badges[] = '<span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" /></svg>
                Via Call
            </span>';
        }

        // Modified status
        if ($record->created_at->ne($record->updated_at)) {
            if ($record->status === 'cancelled') {
                $badges[] = '<span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">
                    ❌ Storniert
                </span>';
            } else {
                $badges[] = '<span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                    🔄 Geändert
                </span>';
            }
        }

        return implode(' ', $badges);
    })
    ->html()
    ->searchable(false)
    ->sortable(false),

Tables\Columns\TextColumn::make('modification_timestamp')
    ->label('Änderungen')
    ->getStateUsing(function ($record) {
        if ($record->created_at->eq($record->updated_at)) {
            return '—';
        }

        return "Geändert: " . $record->updated_at->diffForHumans();
    })
    ->description(fn ($record) =>
        $record->created_at->ne($record->updated_at)
            ? "Erstellt: " . $record->created_at->format('d.m.Y H:i')
            : null
    )
    ->toggleable()
    ->size('sm')
    ->color('gray'),
```

---

## Color & Icon Reference

### Status Color Mapping
```php
// Use throughout all views
$statusColors = [
    // Appointments
    'completed' => 'success',  // Green
    'confirmed' => 'info',     // Blue
    'pending' => 'warning',    // Yellow
    'cancelled' => 'danger',   // Red
    'no_show' => 'gray',      // Gray

    // Calls
    'answered' => 'success',
    'missed' => 'danger',

    // Modifications
    'created' => 'success',
    'rescheduled' => 'warning',
    'cancelled' => 'danger',
];
```

### Icon Mapping
```php
$eventIcons = [
    'call' => 'heroicon-o-phone',
    'appointment' => 'heroicon-o-calendar',
    'created' => 'heroicon-o-plus-circle',
    'modified' => 'heroicon-o-arrow-path',
    'cancelled' => 'heroicon-o-x-circle',
    'completed' => 'heroicon-o-check-circle',
];
```

---

## Testing Checklist

- [ ] Customer timeline shows calls and appointments chronologically
- [ ] Call events show linked appointments
- [ ] Appointment events show booking source
- [ ] Click links navigate to correct detail pages
- [ ] Color coding is consistent across views
- [ ] Mobile responsive design works
- [ ] Performance is acceptable (no N+1 queries)
- [ ] Empty states display correctly
- [ ] Metadata displays correctly when present

---

## Files Created/Modified Summary

### New Files
```
✅ app/Filament/Resources/CustomerResource/Widgets/CustomerTimelineWidget.php
✅ resources/views/filament/resources/customer-resource/widgets/customer-timeline.blade.php
✅ resources/views/filament/tables/call-appointments-modal.blade.php
```

### Modified Files
```
✅ app/Filament/Resources/CustomerResource.php (register widget)
✅ app/Filament/Resources/AppointmentResource.php (enhance infolist)
✅ app/Filament/Resources/CustomerResource/RelationManagers/AppointmentsRelationManager.php (lifecycle indicators)
✅ app/Filament/Resources/CustomerResource/RelationManagers/CallsRelationManager.php (appointment impact)
```

---

## Next Steps

1. Create CustomerTimelineWidget class and blade view
2. Test timeline widget with sample data
3. Enhance AppointmentResource infolist
4. Add lifecycle indicators to AppointmentsRelationManager
5. Add appointment impact view to CallsRelationManager
6. Test all views together
7. Optimize performance with eager loading
8. Polish responsive design
