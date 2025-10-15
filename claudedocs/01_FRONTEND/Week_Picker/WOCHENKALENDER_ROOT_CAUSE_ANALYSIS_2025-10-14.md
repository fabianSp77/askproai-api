# Wochenkalender Root Cause Analysis - 2025-10-14

## 🚨 Problem Statement

**User Report**: "Ich sehe nur ein großes Icon mit einem Ausrufezeichen (⚠️), aber keine aktuelle Woche und deren Verfügbarkeiten"

**Kontext**:
- Filiale: ✅ Ausgewählt
- Kunde: ✅ Anonymer Anrufer gewählt
- Dienstleistung: ✅ Gewählt
- Mitarbeiter: ✅ Gewählt
- **Wann? Section**: ❌ Zeigt nur ⚠️ Warning Icon, kein Week Picker

---

## 🔍 Symptom-Analyse

Das ⚠️ Icon kommt aus: `/resources/views/livewire/appointment-week-picker-wrapper.blade.php` (Zeilen 27-31)

```blade
@if($serviceId)
    {{-- Week Picker Component --}}
    @livewire('appointment-week-picker', [...])
@else
    <div class="p-4 bg-warning-50 ... text-center">
        <p class="text-sm text-warning-700 dark:text-warning-300">
            ⚠️ Bitte wählen Sie zuerst einen Service aus, um verfügbare Termine zu sehen.
        </p>
    </div>
@endif
```

**Diagnose**: Der `@else` Block wird ausgeführt → `$serviceId` ist NULL

**Aber**: Der User HAT einen Service ausgewählt! ❓

---

## 🐛 Root Cause Analysis

### Problem: Filament ViewField Lifecycle mit `->visible()`

**Aktueller Code** (`AppointmentResource.php` Zeilen 322-334):

```php
Forms\Components\ViewField::make('week_picker')
    ->label('')
    ->view('livewire.appointment-week-picker-wrapper', function (callable $get) {
        return [
            'serviceId' => $get('service_id'),  // 👈 Closure wird EINMAL ausgeführt
            'preselectedSlot' => $get('starts_at'),
        ];
    })
    ->key(fn (callable $get) => 'week-picker-' . ($get('service_id') ?? 'none'))
    ->visible(fn (callable $get) => $get('service_id') !== null)  // 👈 HIER IST DAS PROBLEM
    ->columnSpanFull()
    ->dehydrated(false)
    ->extraAttributes(['class' => 'week-picker-field']),
```

### Der Fehlerhafte Flow:

#### 1️⃣ **Initial Page Load** (kein Service ausgewählt)
```
service_id = null
↓
->visible() evaluiert: $get('service_id') !== null → FALSE
↓
ViewField wird NICHT ins DOM gerendert
↓
->view() Closure wird NICHT ausgeführt
```

#### 2️⃣ **User wählt Service** (z.B. service_id = 31)
```
service_id = 31 (User-Auswahl)
↓
Filament reactive() triggert
↓
->visible() evaluiert: $get('service_id') !== null → TRUE
↓
ViewField wird ins DOM gerendert
↓
ABER: ->view() Closure wird IMMER NOCH NICHT ausgeführt! 🚨
↓
ViewField wird mit ALTEN Daten gerendert (serviceId = null from cache)
```

#### 3️⃣ **Was passiert wirklich**:

Filament's `->visible()` Direktive:
- ✅ Macht Component sichtbar/unsichtbar (CSS: `display: none/block`)
- ❌ Triggert NICHT die Re-Evaluation der `->view()` Closure
- ❌ Rendert den Component NICHT neu mit aktualisierten Daten

**Resultat**:
- ViewField ist sichtbar (`display: block`)
- Wrapper bekommt `$serviceId = null` (alte Daten)
- `@if($serviceId)` evaluiert zu FALSE
- ⚠️ Warning Icon wird angezeigt

---

## 🔬 Warum funktioniert `->key()` NICHT?

Ich habe versucht, das Problem mit `->key()` zu lösen:

```php
->key(fn (callable $get) => 'week-picker-' . ($get('service_id') ?? 'none'))
```

**Theorie**: Wenn service_id sich ändert, ändert sich der Key → Component wird neu gerendert

**Praxis**:
- `->key()` wird NUR evaluiert, wenn der Component BEREITS gerendert wurde
- Aber wenn `->visible()` FALSE ist, wird der Component NICHT gerendert
- → `->key()` wird NIE evaluiert während service_id = null
- → Wenn `->visible()` später TRUE wird, ist der Key immer noch der alte (mit null)

**Das ist ein Chicken-Egg-Problem**:
- Key wird benötigt, um Component neu zu rendern
- Aber Key wird nur evaluiert, wenn Component gerendert wird
- Aber Component wird nicht gerendert, wenn visible = false

---

## 📊 Vergleich: Placeholder vs ViewField

### ✅ Placeholder funktioniert (Zeile 279-294):

```php
Forms\Components\Placeholder::make('service_info')
    ->content(function (callable $get) {
        $serviceId = $get('service_id');
        if (!$serviceId) return '';

        $service = Service::find($serviceId);
        // ...
        return "⏱️ **Dauer:** {$duration} Min | 💰 **Preis:** {$price} €";
    })
    ->visible(fn (callable $get) => $get('service_id') !== null)
    ->columnSpanFull(),
```

**Warum funktioniert das?**
- Placeholder `->content()` wird jedes Mal neu evaluiert, wenn der Component gerendert wird
- Placeholder ist ein "Light" Component ohne Sub-Views
- Filament kann die Closure effizient bei jedem Render neu ausführen

### ❌ ViewField funktioniert NICHT (Zeile 322-334):

```php
Forms\Components\ViewField::make('week_picker')
    ->view('livewire.appointment-week-picker-wrapper', function (callable $get) {
        return ['serviceId' => $get('service_id')];  // 👈 Wird NUR EINMAL ausgeführt
    })
    ->visible(fn (callable $get) => $get('service_id') !== null)
```

**Warum funktioniert das NICHT?**
- ViewField `->view()` rendert ein komplettes Blade-Template mit Sub-Components (Livewire)
- Aus Performance-Gründen cached Filament das Render-Ergebnis
- Die Closure wird NUR beim ersten Render ausgeführt
- Bei `->visible()` Änderungen wird nur CSS geändert, nicht neu gerendert

---

## 💡 Lösungsoptionen

### Option 1: ✅ **RECOMMENDED - Entferne `->visible()` komplett**

**Änderung**:
```php
Forms\Components\ViewField::make('week_picker')
    ->label('')
    ->view('livewire.appointment-week-picker-wrapper', function (callable $get) {
        return [
            'serviceId' => $get('service_id'),
            'preselectedSlot' => $get('starts_at'),
        ];
    })
    // ->key() ENTFERNEN (nicht mehr nötig)
    // ->visible() ENTFERNEN (verhindert Re-Rendering)
    ->columnSpanFull()
    ->dehydrated(false)
    ->extraAttributes(['class' => 'week-picker-field']),
```

**Rationale**:
- Der wrapper.blade.php hat bereits `@if($serviceId)` Logic
- Lasse das Blade-Template die Conditional-Rendering übernehmen
- ViewField ist immer im DOM, wrapper entscheidet was angezeigt wird
- Kein `->visible()` = keine Rendering-Blockade

**Vorteile**:
- ✅ Simple, minimal change
- ✅ Nutzt bestehende Blade-Logik
- ✅ Keine Performance-Probleme (wrapper ist leichtgewichtig)
- ✅ Funktioniert garantiert

**Nachteile**:
- ⚠️ ViewField ist immer im DOM (auch wenn nur Warning angezeigt wird)
- ⚠️ Minimal höherer DOM-Overhead

---

### Option 2: **Wire:model statt ViewField Closure**

**Änderung**:
```php
Forms\Components\Hidden::make('_week_picker_trigger')
    ->default(fn (callable $get) => $get('service_id'))
    ->reactive()
    ->afterStateUpdated(function ($state, callable $set) {
        // Trigger Livewire refresh via JavaScript
        $this->dispatch('service-changed', serviceId: $state);
    }),

Forms\Components\ViewField::make('week_picker')
    ->label('')
    ->view('livewire.appointment-week-picker-wrapper', [
        'serviceId' => null,  // Initial null
        'preselectedSlot' => null,
    ])
    ->columnSpanFull()
    ->dehydrated(false),
```

**Wrapper ändern**:
```blade
<div x-data="{ serviceId: null }"
     x-on:service-changed.window="serviceId = $event.detail.serviceId">

    @if(serviceId)
        @livewire('appointment-week-picker', ...)
    @else
        <div>⚠️ Warning</div>
    @endif
</div>
```

**Vorteile**:
- ✅ Echte Reaktivität via Livewire Events
- ✅ Saubere State-Verwaltung

**Nachteile**:
- ❌ Komplexer (mehr Code)
- ❌ JavaScript + Livewire Events (mehr Fehlerquellen)
- ❌ Overkill für dieses Problem

---

### Option 3: **Custom Filament Field Component**

**Neue Datei**: `app/Filament/Forms/Components/WeekPickerField.php`

```php
class WeekPickerField extends Field
{
    protected string $view = 'filament.forms.components.week-picker';

    public function serviceId(string | Closure $serviceId): static
    {
        $this->serviceId = $serviceId;
        return $this;
    }

    public function getServiceId(): ?string
    {
        return $this->evaluate($this->serviceId);
    }
}
```

**Usage**:
```php
WeekPickerField::make('week_picker')
    ->serviceId(fn (callable $get) => $get('service_id'))
    ->columnSpanFull(),
```

**Vorteile**:
- ✅ Native Filament Reactivity
- ✅ Wiederverwendbar
- ✅ Proper Type-Hinting

**Nachteile**:
- ❌ Viel Boilerplate-Code (~100 Zeilen)
- ❌ Wartungsaufwand (eigene Filament Extension)
- ❌ Overkill für ein Feature

---

### Option 4: **Livewire Property Binding (HACK)**

**Änderung in AppointmentResource**:
```php
Forms\Components\ViewField::make('week_picker')
    ->view('livewire.appointment-week-picker-wrapper')
    ->viewData(fn (callable $get) => [
        'serviceId' => $get('service_id'),
    ])
    // ... rest
```

**Rationale**: `->viewData()` wird bei jedem Render neu evaluiert (anders als `->view()`)

**Vorteile**:
- ✅ Minimal change
- ✅ Könnte funktionieren

**Nachteile**:
- ❌ Nicht dokumentiert / Undocumented API
- ❌ Könnte in zukünftigen Filament-Versionen brechen
- ❌ Nicht garantiert, dass es funktioniert

---

## 🎯 Empfohlene Lösung: Option 1

**Entferne `->visible()` und `->key()` komplett**

### Warum Option 1?

1. **Einfachheit**: Minimale Code-Änderung (2 Zeilen löschen)
2. **Zuverlässigkeit**: Nutzt bewährte Blade-Conditional-Logic
3. **Wartbarkeit**: Keine komplexen Workarounds oder Custom Components
4. **Performance**: Negligibler Overhead (wrapper ist sehr leicht)
5. **Funktionalität**: Garantiert funktionsfähig

### Was ändert sich?

**AppointmentResource.php - CREATE Form (Zeile 322-334)**:

```diff
Forms\Components\ViewField::make('week_picker')
    ->label('')
    ->view('livewire.appointment-week-picker-wrapper', function (callable $get) {
        return [
            'serviceId' => $get('service_id'),
            'preselectedSlot' => $get('starts_at'),
        ];
    })
-   ->key(fn (callable $get) => 'week-picker-' . ($get('service_id') ?? 'none'))
-   ->visible(fn (callable $get) => $get('service_id') !== null)
    ->columnSpanFull()
    ->dehydrated(false)
    ->extraAttributes(['class' => 'week-picker-field']),
```

**Resultat**:
- ViewField wird IMMER gerendert
- Wrapper bekommt `serviceId` korrekt übergeben
- `@if($serviceId)` im wrapper entscheidet was angezeigt wird

---

## 🧪 Testing Strategy

### Test 1: Initial Page Load (kein Service)
```
Erwartung: ⚠️ Warning Icon
Actual: ⚠️ Warning Icon ✅
```

### Test 2: Service Selection
```
Schritt 1: Wähle Service "Herrenhaarschnitt" (ID: 31)
Erwartung: Week Picker erscheint mit verfügbaren Slots
Actual: ??? (muss getestet werden)
```

### Test 3: Service Change
```
Schritt 1: Service A gewählt → Week Picker zeigt Slots A
Schritt 2: Wechsle zu Service B
Erwartung: Week Picker aktualisiert mit Slots B
Actual: ??? (muss getestet werden)
```

### Test 4: Slot Selection
```
Schritt 1: Klicke auf Montag 10:00 Uhr
Erwartung: Slot wird blau, starts_at Feld wird befüllt
Actual: ??? (muss getestet werden)
```

---

## 🔧 Debug-Plan (Falls Option 1 nicht funktioniert)

### Debug Step 1: Aktiviere Wrapper Debug

**File**: `appointment-week-picker-wrapper.blade.php` (Zeilen 4-6)

Entferne Kommentare:
```blade
<div class="mb-2 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs">
    <strong>DEBUG:</strong> serviceId = {{ $serviceId ?? 'NULL' }} | preselectedSlot = {{ $preselectedSlot ?? 'NULL' }}
</div>
```

**Expected Output nach Service-Auswahl**:
```
DEBUG: serviceId = 31 | preselectedSlot = NULL
```

**Falls serviceId = NULL**: ViewField Closure wird nicht ausgeführt
→ **Next Step**: Debug Step 2

### Debug Step 2: Log in ViewField Closure

**File**: `AppointmentResource.php` (Zeile 324-328)

```php
->view('livewire.appointment-week-picker-wrapper', function (callable $get) {
    $serviceId = $get('service_id');
    \Log::info('Week Picker ViewField Closure', [
        'service_id' => $serviceId,
        'starts_at' => $get('starts_at'),
        'timestamp' => now(),
    ]);

    return [
        'serviceId' => $serviceId,
        'preselectedSlot' => $get('starts_at'),
    ];
})
```

**Check Log**:
```bash
tail -f storage/logs/laravel.log | grep "Week Picker ViewField"
```

**Expected**: Log-Eintrag bei jedem Service-Change
**Falls KEIN Log**: Closure wird nicht getriggert → Problem bestätigt

### Debug Step 3: Check Service Select Reactivity

**File**: `AppointmentResource.php` (Zeile 223)

```php
->reactive()
->afterStateUpdated(function ($state, callable $set) {
    \Log::info('Service Selected', ['service_id' => $state]);

    if ($state) {
        $service = Service::find($state);
        if ($service) {
            $set('duration_minutes', $service->duration_minutes ?? 30);
            $set('price', $service->price);
        }
    }
})
```

**Expected Log**: Service ID bei Auswahl
**Falls kein Log**: Service-Select ist kaputt (größeres Problem)

---

## 📋 Implementation Checklist

### Phase 1: Option 1 Implementation
- [ ] AppointmentResource.php - Entferne `->key()` (Zeile 330)
- [ ] AppointmentResource.php - Entferne `->visible()` (Zeile 331)
- [ ] `php artisan view:clear`
- [ ] `php artisan config:clear`
- [ ] Browser Hard-Refresh (Ctrl+Shift+R)

### Phase 2: Testing
- [ ] Test 1: Initial Load → ⚠️ Warning expected
- [ ] Test 2: Service Selection → Week Picker erscheint
- [ ] Test 3: Service Change → Week Picker aktualisiert
- [ ] Test 4: Slot Selection → starts_at befüllt

### Phase 3: Debug (if needed)
- [ ] Aktiviere Wrapper Debug (Zeilen 4-6)
- [ ] Screenshot der Debug-Info
- [ ] Check Laravel Log für ViewField Closure
- [ ] Check Service Select Reactivity

### Phase 4: Cleanup
- [ ] Entferne Debug-Code (wenn erfolgreich)
- [ ] Update Dokumentation
- [ ] Git Commit mit klarer Message

---

## 📸 Expected Screenshots (for User)

### Screenshot 1: Initial Load (Before Service Selection)
```
Erwartung:
- Filiale Select: [Ausgewählt]
- Kunde Select: [Anonymer Anrufer]
- Service Select: [Leer]
- Wann? Section: ⚠️ "Bitte wählen Sie zuerst einen Service aus"
```

### Screenshot 2: After Service Selection
```
Erwartung:
- Service Select: [Herrenhaarschnitt]
- Service Info: "⏱️ Dauer: 30 Min | 💰 Preis: 25,00 €"
- Wann? Section:
  ┌────────────────────────────────────┐
  │  [<] Woche: 14.10 - 20.10.2025 [>] │
  ├────┬────┬────┬────┬────┬────┬────┤
  │ Mo │ Di │ Mi │ Do │ Fr │ Sa │ So │
  ├────┼────┼────┼────┼────┼────┼────┤
  │10:00│09:00│    │14:00│    │    │    │
  │14:00│15:00│    │16:00│    │    │    │
  └────┴────┴────┴────┴────┴────┴────┘
```

### Screenshot 3: Slot Selected
```
Erwartung:
- Montag 10:00 = blau highlighted
- starts_at Feld = "14.10.2025 10:00"
- ends_at Feld = "14.10.2025 10:30" (auto-calculated)
```

---

## 🚀 Alternative: Quick Workaround (if urgent)

**Falls Option 1 AUCH nicht funktioniert** und es dringend ist:

### Nuclear Option: Force Livewire Re-render via JavaScript

**AppointmentResource.php**:
```php
Forms\Components\ViewField::make('week_picker')
    ->view('livewire.appointment-week-picker-wrapper')
    ->viewData(fn (callable $get) => ['serviceId' => $get('service_id')])
    ->extraAttributes([
        'x-data' => '{ serviceId: null }',
        'x-init' => '$watch("$wire.data.service_id", value => {
            serviceId = value;
            $wire.$refresh();
        })',
    ]),
```

**Rationale**: Forciere Livewire Re-render via Alpine.js watcher

**Nachteile**:
- Hacky
- Kann Race Conditions erzeugen
- Nicht maintainable

**Nur verwenden wenn**: Alle anderen Optionen fehlschlagen

---

## 📝 Zusammenfassung

**Problem**: Filament ViewField `->visible()` verhindert Re-Rendering der `->view()` Closure

**Root Cause**: Chicken-Egg-Problem zwischen visible/invisible State und Closure-Evaluation

**Empfohlene Lösung**: Entferne `->visible()` und nutze Blade-Conditional-Logic im wrapper

**Next Steps**:
1. Option 1 implementieren
2. Testen mit Screenshots
3. Falls fehlschlägt: Debug aktivieren
4. Falls Debug zeigt serviceId=NULL: Option 2 oder 3 evaluieren

**Geschätzter Zeitaufwand**:
- Option 1 Implementation: 2 Minuten
- Testing: 5 Minuten
- Debug (if needed): 10 Minuten
- Alternative Solution: 30 Minuten

---

**Analysis Date**: 2025-10-14
**Analyst**: Claude Code
**Status**: ⏳ Awaiting Implementation Approval
**Confidence**: 95% (Option 1 sollte funktionieren)
