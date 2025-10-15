# Wochenkalender Reschedule Modal - Detaillierte Analyse 2025-10-14

## 🔍 Problem Context

**User-Report** (GitHub Issue #697):
- User klickt auf 3 Punkte in Terminübersicht
- Wählt "Verschieben" Action
- Popup Modal öffnet sich
- **Problem**: Etwas wird nicht korrekt angezeigt (Screenshot in Issue #697)

---

## 📊 Datenbank-Status

**Appointment Statistik**:
```
Total Appointments: 162
├─ WITH service_id: 60 (37%)
└─ WITHOUT service_id: 102 (63%)
```

**Beispiel Appointment OHNE service_id**:
```
ID: 15
Service ID: NULL ❌
Service Name: NO SERVICE
Starts At: 2025-07-08T09:00:00+02:00 ✅
```

**Beispiel Appointment MIT service_id**:
```
ID: 560
Service ID: 32 ✅
Service Name: Herren: Waschen, Schneiden, Styling ✅
Starts At: 2025-09-27T10:00:00+02:00 ✅
```

---

## 🔬 Code-Analyse: Reschedule Modal

**Location**: `app/Filament/Resources/AppointmentResource.php` (Zeilen 796-892)

### Vollständiger Code-Flow:

```php
Tables\Actions\Action::make('reschedule')
    ->label('Verschieben')
    ->icon('heroicon-m-calendar')
    ->color('warning')
    ->modalWidth('7xl')
    ->modalHeading('Termin verschieben - Wochenansicht')
    ->modalSubmitActionLabel('Verschieben')
    ->modalCancelActionLabel('Abbrechen')
    ->form(function ($record) {
        // ========================================
        // GUARD CLAUSE: Check if service_id exists
        // ========================================
        if (!$record->service_id) {
            return [
                Forms\Components\Placeholder::make('error')
                    ->label('')
                    ->content('⚠️ Termin hat keinen Service zugeordnet. Bitte bearbeiten Sie den Termin.')
                    ->columnSpanFull(),
            ];
        }

        // ========================================
        // MAIN FORM: If service_id exists
        // ========================================
        return [
            // 1. Service Info Display
            Forms\Components\Placeholder::make('service_info')
                ->label('Service')
                ->content(fn() => $record->service->name . " ({$record->service->duration_minutes} min)")
                ->columnSpanFull(),

            // 2. Week Picker Component
            Forms\Components\ViewField::make('week_picker')
                ->label('')
                ->view('livewire.appointment-week-picker-wrapper', [
                    'serviceId' => $record->service_id,  // 👈 Direct value, not closure
                    'preselectedSlot' => $record->starts_at->toIso8601String(),
                ])
                ->columnSpanFull()
                ->dehydrated(false)
                ->extraAttributes(['class' => 'week-picker-field']),

            // 3. Hidden field for selected datetime
            Forms\Components\Hidden::make('starts_at')
                ->required(),
        ];
    })
    ->action(function ($record, array $data) {
        // Conflict check + Update logic
        // ...
    }),
```

---

## 🧪 Drei Hypothesen

### Hypothese 1: User klickte auf Appointment OHNE service_id

**Erwartetes Verhalten**:
```
Guard Clause greift (Zeile 805)
↓
Modal zeigt:
┌──────────────────────────────────────────────┐
│ Termin verschieben - Wochenansicht           │
├──────────────────────────────────────────────┤
│ ⚠️ Termin hat keinen Service zugeordnet.     │
│    Bitte bearbeiten Sie den Termin.          │
└──────────────────────────────────────────────┘
```

**Falls Screenshot anders aussieht**:
- Guard Clause funktioniert NICHT
- → BUG im Guard Clause Code

**Test**:
```sql
-- Find appointment WITHOUT service_id
SELECT id, service_id, starts_at FROM appointments WHERE service_id IS NULL LIMIT 1;
-- Result: ID 15, service_id = NULL
```

---

### Hypothese 2: User klickte auf Appointment MIT service_id

**Erwartetes Verhalten**:
```
Guard Clause wird NICHT ausgeführt (service_id = 32)
↓
Main Form wird gerendert
↓
ViewField bekommt:
  ['serviceId' => 32, 'preselectedSlot' => '2025-09-27T10:00:00+02:00']
↓
wrapper.blade.php evaluiert:
  @if($serviceId) → TRUE (32 ist truthy)
↓
Week Picker wird geladen
```

**Falls Screenshot ⚠️ Warning zeigt**:
- ViewField übergibt `serviceId` nicht korrekt
- ODER wrapper.blade.php bekommt `$serviceId = NULL`
- → BUG im ViewField Data Passing

**Möglicher Sub-Bug**:
```php
->view('livewire.appointment-week-picker-wrapper', [
    'serviceId' => $record->service_id,  // Was wenn $record nicht gebunden ist?
])
```

Falls Filament `$record` im Action-Kontext nicht korrekt bindet:
- `$record->service_id` könnte NULL sein, auch wenn DB-Eintrag service_id hat
- ViewField würde `['serviceId' => null]` bekommen
- Wrapper zeigt ⚠️ Warning

---

### Hypothese 3: starts_at Serialization Problem

**Potenzieller Error**:
```php
'preselectedSlot' => $record->starts_at->toIso8601String(),
```

**Falls `$record->starts_at` NULL ist**:
```
Fatal Error: Call to a member function toIso8601String() on null
```

**Aber**: Datenbank-Check zeigt, dass starts_at gesetzt ist.

**JEDOCH**: Falls Guard Clause NICHT greift und service_id NULL ist:
```php
->content(fn() => $record->service->name . " ({$record->service->duration_minutes} min)")
```

**Würde crashen mit**:
```
Error: Trying to get property 'name' of null
```

Weil `$record->service` NULL ist, wenn service_id NULL ist.

---

## 🔍 Root Cause Kandidaten

### Kandidat 1: Guard Clause Bypass (WAHRSCHEINLICH)

**Problem**:
```php
if (!$record->service_id) {
    return [...];  // Guard Clause
}

return [
    Forms\Components\Placeholder::make('service_info')
        ->content(fn() => $record->service->name . " ...")  // 💥 CRASH if service_id NULL
```

**Szenario**:
1. User öffnet Reschedule Modal für Appointment mit service_id = NULL
2. Guard Clause sollte greifen
3. ABER: Guard Clause wird aus irgendeinem Grund nicht ausgeführt?
4. Main Form wird gerendert
5. `$record->service->name` crashed → Fatal Error
6. ODER: Filament fängt Error ab und zeigt generischen Warning?

**Verifikation nötig**:
- Wird Guard Clause wirklich ausgeführt?
- Oder gibt es einen Filament-Bug, der Guard Clause umgeht?

---

### Kandidat 2: ViewField Data Binding Issue

**Problem**:
```php
->form(function ($record) {  // 👈 $record Parameter
    return [
        Forms\Components\ViewField::make('week_picker')
            ->view('livewire.appointment-week-picker-wrapper', [
                'serviceId' => $record->service_id,  // 👈 $record Scope-Problem?
            ])
    ];
})
```

**Szenario**:
1. Filament Table Action `->form()` bekommt `$record` als Parameter
2. ViewField wird mit Array `['serviceId' => $record->service_id]` initialisiert
3. **ABER**: Array wird beim Definition-Zeitpunkt evaluiert, nicht beim Render-Zeitpunkt
4. Falls `$record` in einem anderen Kontext gebunden wird → `$record->service_id` = NULL

**Ähnlich wie Create Form Problem**:
- Create Form: Closure wird nicht bei reactivity neu ausgeführt
- Edit Form: Array wird möglicherweise beim falschen Zeitpunkt evaluiert

**Verifikation nötig**:
- Wird `$record->service_id` korrekt in den Array übergeben?
- Oder ist `$record` zum Evaluations-Zeitpunkt leer/falsch?

---

### Kandidat 3: Wrapper Blade Variable Scope

**Problem**:
```blade
@if($serviceId)  {{-- $serviceId aus ViewField --}}
    @livewire('appointment-week-picker', [
        'serviceId' => $serviceId,  {{-- Wird nochmal übergeben --}}
        'preselectedSlot' => $preselectedSlot ?? null,
    ])
@else
    <div>⚠️ Bitte wählen Sie zuerst einen Service aus</div>
@endif
```

**Szenario**:
1. ViewField übergibt `['serviceId' => 32]` korrekt
2. Blade Template sollte `$serviceId = 32` haben
3. ABER: Variable nicht im Scope verfügbar?
4. `@if($serviceId)` evaluiert zu FALSE, obwohl Daten übergeben wurden

**Unwahrscheinlich**, weil:
- Blade Templates bekommen ViewField-Daten automatisch als Variablen
- Das ist Standard Filament/Blade Verhalten

---

## 🔧 Debug-Strategie

### Phase 1: Screenshot-Inhalt verstehen

**KRITISCH**: Ich kann den Screenshot-Inhalt nicht sehen!

**Benötigte Information vom User**:

1. **Was GENAU wird im Modal angezeigt?**
   - [ ] ⚠️ "Termin hat keinen Service zugeordnet. Bitte bearbeiten Sie den Termin." (Guard Clause Message)
   - [ ] ⚠️ "Bitte wählen Sie zuerst einen Service aus, um verfügbare Termine zu sehen." (Wrapper Message)
   - [ ] Week Picker mit Slots
   - [ ] Fatal Error / Exception
   - [ ] Anderes?

2. **Welcher Appointment wurde ausgewählt?**
   - Appointment ID?
   - Hat dieser Appointment eine service_id?
   - Check: `SELECT id, service_id, starts_at FROM appointments WHERE id = [USER_ID];`

3. **Browser Console Errors?**
   - F12 → Console Tab
   - Irgendwelche JavaScript/Livewire Errors?

---

### Phase 2: Aktiviere Debug-Modus

**1. Wrapper Debug aktivieren**:

File: `/var/www/api-gateway/resources/views/livewire/appointment-week-picker-wrapper.blade.php`

Zeilen 4-6 - Entferne Kommentare:

```blade
<div class="mb-2 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs">
    <strong>DEBUG:</strong> serviceId = {{ $serviceId ?? 'NULL' }} | preselectedSlot = {{ $preselectedSlot ?? 'NULL' }}
</div>
```

**2. Guard Clause Debug hinzufügen**:

File: `/var/www/api-gateway/app/Filament/Resources/AppointmentResource.php`

Zeile 805-812:

```php
->form(function ($record) {
    // DEBUG: Log what we receive
    \Log::info('Reschedule Modal Form', [
        'appointment_id' => $record->id,
        'service_id' => $record->service_id,
        'starts_at' => $record->starts_at?->toIso8601String(),
        'has_service_relation' => $record->service !== null,
    ]);

    if (!$record->service_id) {
        \Log::warning('Reschedule: No service_id - showing guard clause');
        return [
            Forms\Components\Placeholder::make('error')
                ->label('')
                ->content('⚠️ Termin hat keinen Service zugeordnet. Bitte bearbeiten Sie den Termin.')
                ->columnSpanFull(),
        ];
    }

    \Log::info('Reschedule: Has service_id - rendering week picker');

    return [
        // ... rest of form
    ];
})
```

**3. Test Reschedule Modal**:
```bash
tail -f storage/logs/laravel.log | grep -i "reschedule"
```

Dann Modal öffnen und Log prüfen.

---

### Phase 3: Systematische Tests

#### Test 1: Appointment WITHOUT service_id

```bash
php artisan tinker
```

```php
$apt = App\Models\Appointment::whereNull('service_id')->first();
echo "Testing Appointment ID: {$apt->id}\n";
echo "Service ID: " . ($apt->service_id ?? 'NULL') . "\n";
echo "Opens reschedule modal and check...\n";
```

**Expected**:
- Guard Clause Message: "⚠️ Termin hat keinen Service zugeordnet"

**If shows Wrapper Warning instead**:
- Guard Clause wird umgangen → BUG

---

#### Test 2: Appointment WITH service_id

```bash
php artisan tinker
```

```php
$apt = App\Models\Appointment::whereNotNull('service_id')->with('service')->first();
echo "Testing Appointment ID: {$apt->id}\n";
echo "Service ID: {$apt->service_id}\n";
echo "Service Name: {$apt->service->name}\n";
echo "Opens reschedule modal and check...\n";
```

**Expected**:
- Service Info: "Herren: Waschen, Schneiden, Styling (30 min)"
- Week Picker mit verfügbaren Slots

**If shows Wrapper Warning**:
- ViewField Data Binding Problem → BUG

---

## 🎯 Mögliche Lösungen

### Lösung 1: Guard Clause verstärken

**Falls Guard Clause umgangen wird**:

```php
->form(function ($record) {
    // STRONGER Guard Clause
    if (!$record->service_id || !$record->service) {
        return [
            Forms\Components\Placeholder::make('error')
                ->label('')
                ->content('⚠️ Termin hat keinen Service zugeordnet. Bitte bearbeiten Sie den Termin.')
                ->columnSpanFull(),
        ];
    }

    // Safe access with null coalescing
    $serviceName = $record->service?->name ?? 'Unbekannter Service';
    $serviceDuration = $record->service?->duration_minutes ?? 30;

    return [
        Forms\Components\Placeholder::make('service_info')
            ->label('Service')
            ->content("{$serviceName} ({$serviceDuration} min)")
            ->columnSpanFull(),

        Forms\Components\ViewField::make('week_picker')
            ->view('livewire.appointment-week-picker-wrapper', [
                'serviceId' => $record->service_id,
                'preselectedSlot' => $record->starts_at?->toIso8601String() ?? null,
            ])
            ->columnSpanFull()
            ->dehydrated(false),
    ];
})
```

**Änderungen**:
- ✅ Null-safe operator `?->` für safety
- ✅ Check `$record->service` zusätzlich zu `service_id`
- ✅ Fallback-Werte falls Daten fehlen

---

### Lösung 2: ViewField mit Closure (wie Create Form)

**Falls Array-Binding nicht funktioniert**:

```php
->form(function ($record) {
    if (!$record->service_id) {
        return [...];  // Guard Clause
    }

    $appointmentId = $record->id;  // Capture in closure scope

    return [
        Forms\Components\Placeholder::make('service_info')
            ->content(fn() => $record->service->name . " ({$record->service->duration_minutes} min)")
            ->columnSpanFull(),

        // CHANGED: Use closure like in Create Form
        Forms\Components\ViewField::make('week_picker')
            ->label('')
            ->view('livewire.appointment-week-picker-wrapper', function () use ($record) {
                return [
                    'serviceId' => $record->service_id,
                    'preselectedSlot' => $record->starts_at->toIso8601String(),
                ];
            })
            ->columnSpanFull()
            ->dehydrated(false),
    ];
})
```

**Rationale**:
- Closure wird beim Render-Zeitpunkt ausgeführt
- Garantiert aktuellen `$record` Scope
- Ähnlich wie Create Form Fix

---

### Lösung 3: Disable Reschedule für Appointments ohne Service

**Präventive Lösung**:

```php
Tables\Actions\Action::make('reschedule')
    ->label('Verschieben')
    ->icon('heroicon-m-calendar')
    ->color('warning')
    ->visible(fn ($record) => $record->service_id !== null)  // 👈 Hide if no service
    ->modalWidth('7xl')
    ->modalHeading('Termin verschieben - Wochenansicht')
    // ... rest
```

**Vorteile**:
- ✅ User kann Reschedule nicht für ungültige Appointments klicken
- ✅ Verhindert Problem proaktiv

**Nachteile**:
- ⚠️ User kann nicht sehen WARUM Reschedule nicht verfügbar ist
- ⚠️ Might be confusing UX

---

## 📝 Zusammenfassung

### Was wir wissen:

1. ✅ 102 von 162 Appointments haben KEINE service_id
2. ✅ Reschedule Modal hat Guard Clause für fehlende service_id
3. ✅ ViewField verwendet Array-Binding (nicht Closure) im Edit-Kontext
4. ✅ Wrapper-Template hat korrekte `@if($serviceId)` Logik
5. ❓ Screenshot-Inhalt unbekannt

### Was wir NICHT wissen:

1. ❓ Welche Message zeigt der Screenshot genau?
2. ❓ Wurde Appointment MIT oder OHNE service_id getestet?
3. ❓ Gibt es Browser Console Errors?
4. ❓ Funktioniert Guard Clause wie erwartet?
5. ❓ Bekommt wrapper.blade.php die Daten korrekt?

### Nächste Schritte:

#### Schritt 1: User-Feedback benötigt

**FRAGEN AN USER**:

1. **Was GENAU steht im Modal?**
   - "Termin hat keinen Service zugeordnet" → Guard Clause funktioniert
   - "Bitte wählen Sie zuerst einen Service aus" → ViewField Problem
   - Anderer Text → Screenshot-Screenshot

2. **Für welchen Appointment?**
   - Appointment ID?
   - Hat service_id oder nicht?

3. **Browser Console Errors?**
   - Screenshot von F12 → Console

#### Schritt 2: Debug aktivieren (wenn User-Feedback unklar)

1. Wrapper Debug einschalten
2. Log in Guard Clause hinzufügen
3. Reschedule Modal öffnen
4. Log prüfen
5. Screenshot schicken

#### Schritt 3: Implementiere Fix (basierend auf Findings)

- **Falls Guard Clause umgangen wird** → Lösung 1
- **Falls ViewField Binding Problem** → Lösung 2
- **Präventiv** → Lösung 3

---

**Analysis Date**: 2025-10-14
**Analyst**: Claude Code
**Status**: ⏳ Awaiting Screenshot Details & User Feedback
**Confidence**: 70% (brauche Screenshot-Inhalt für 100%)

---

## 🔬 Appendix: Code-Referenzen

### AppointmentResource.php - Reschedule Action
```
Location: app/Filament/Resources/AppointmentResource.php
Lines: 796-892

Key Components:
- Guard Clause: 805-812
- Service Info Placeholder: 816-819
- Week Picker ViewField: 822-830
- Hidden starts_at field: 833-834
```

### Wrapper Template
```
Location: resources/views/livewire/appointment-week-picker-wrapper.blade.php
Lines: 1-33

Key Logic:
- @if($serviceId): Line 8
- Week Picker: Line 21-24
- Warning Message: Line 27-31
```

### Livewire Component
```
Location: app/Livewire/AppointmentWeekPicker.php
Mount: Lines 75-96

Required Props:
- serviceId: string (required)
- preselectedSlot: string|null (optional)
```
