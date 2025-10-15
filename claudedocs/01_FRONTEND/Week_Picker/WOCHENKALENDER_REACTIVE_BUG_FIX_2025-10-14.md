# Wochenkalender Reactive Bug Fix - 2025-10-14

## Problem Report

**User-Beschreibung**: "Ich sehe nur ein großes Icon da, wo der Kalender angezeigt werden sollte"

**Root Cause**: Filament's `ViewField` mit Closures wird nicht reaktiv neu gerendert, wenn sich Abhängigkeiten (wie `service_id`) ändern.

## Symptom-Analyse

Das "große Icon" ist das ⚠️ Warning-Icon aus `appointment-week-picker-wrapper.blade.php` (Zeilen 27-31):

```blade
<div class="p-4 bg-warning-50 ... text-center">
    <p class="text-sm text-warning-700 dark:text-warning-300">
        ⚠️ Bitte wählen Sie zuerst einen Service aus, um verfügbare Termine zu sehen.
    </p>
</div>
```

Dieses Icon wird angezeigt, wenn `$serviceId` null oder leer ist.

## Ursache

### Vorher (BROKEN):

`AppointmentResource.php` Zeile 322-333:

```php
Forms\Components\ViewField::make('week_picker')
    ->label('')
    ->view('livewire.appointment-week-picker-wrapper', function (callable $get) {
        return [
            'serviceId' => $get('service_id'),
            'preselectedSlot' => $get('starts_at'),
        ];
    })
    ->visible(fn (callable $get) => $get('service_id') !== null)
    ->columnSpanFull()
    ->reactive()
    ->extraAttributes(['class' => 'week-picker-field']),
```

**Problem**:
1. Die Closure `function (callable $get)` wird nur beim initialen Render ausgeführt
2. Wenn der User einen Service auswählt, wird `->reactive()` zwar getriggert, aber die Closure wird NICHT neu ausgeführt
3. Ergebnis: `$serviceId` bleibt null, Week Picker wird nie geladen

**Filament Limitation**: ViewField mit Closures hat keine automatische Dependency-Tracking für reaktive Updates.

## Solution

### Fix #1: Dynamic Key (Forces Re-render)

```php
->key(fn (callable $get) => 'week-picker-' . ($get('service_id') ?? 'none'))
```

**Was macht das?**
- Ändert den Component Key basierend auf `service_id`
- Livewire/Filament erkennt Key-Änderung als neuen Component
- Erzwingt vollständige Neu-Renderung inkl. Closure-Ausführung

### Fix #2: Dehydration Prevention

```php
->dehydrated(false)
```

**Was macht das?**
- Verhindert, dass ViewField versucht, Daten beim Form-Submit zu speichern
- Der Livewire Component (`AppointmentWeekPicker`) übernimmt das Speichern via `@this.set('starts_at', ...)`
- Vermeidet Konflikte zwischen ViewField und Livewire Component

### Fix #3: Remove Redundant reactive()

Die `->reactive()` Direktive wurde entfernt auf Create-Seite, da `->key()` die reaktive Neu-Renderung übernimmt.

## Implementierte Änderungen

### 1. AppointmentResource.php - Create Form (Zeile 322-334)

**Vorher**:
```php
Forms\Components\ViewField::make('week_picker')
    ->label('')
    ->view('livewire.appointment-week-picker-wrapper', function (callable $get) {
        return [
            'serviceId' => $get('service_id'),
            'preselectedSlot' => $get('starts_at'),
        ];
    })
    ->visible(fn (callable $get) => $get('service_id') !== null)
    ->columnSpanFull()
    ->reactive()
    ->extraAttributes(['class' => 'week-picker-field']),
```

**Nachher**:
```php
Forms\Components\ViewField::make('week_picker')
    ->label('')
    ->view('livewire.appointment-week-picker-wrapper', function (callable $get) {
        return [
            'serviceId' => $get('service_id'),
            'preselectedSlot' => $get('starts_at'),
        ];
    })
    ->key(fn (callable $get) => 'week-picker-' . ($get('service_id') ?? 'none'))
    ->visible(fn (callable $get) => $get('service_id') !== null)
    ->columnSpanFull()
    ->dehydrated(false)
    ->extraAttributes(['class' => 'week-picker-field']),
```

**Änderungen**:
- ✅ `->key()` hinzugefügt (Zeile 330)
- ✅ `->reactive()` entfernt (nicht mehr nötig)
- ✅ `->dehydrated(false)` hinzugefügt (Zeile 333)

### 2. AppointmentResource.php - Edit Modal (Zeile 824-832)

**Vorher**:
```php
Forms\Components\ViewField::make('week_picker')
    ->label('')
    ->view('livewire.appointment-week-picker-wrapper', [
        'serviceId' => $record->service_id,
        'preselectedSlot' => $record->starts_at->toIso8601String(),
    ])
    ->columnSpanFull()
    ->reactive()
    ->extraAttributes(['class' => 'week-picker-field']),
```

**Nachher**:
```php
Forms\Components\ViewField::make('week_picker')
    ->label('')
    ->view('livewire.appointment-week-picker-wrapper', [
        'serviceId' => $record->service_id,
        'preselectedSlot' => $record->starts_at->toIso8601String(),
    ])
    ->columnSpanFull()
    ->dehydrated(false)
    ->extraAttributes(['class' => 'week-picker-field']),
```

**Änderungen**:
- ✅ `->reactive()` entfernt (nicht nötig in Edit-Kontext)
- ✅ `->dehydrated(false)` hinzugefügt (Zeile 831)

### 3. appointment-week-picker-wrapper.blade.php (Zeilen 3-6)

**Hinzugefügt**: Debug-Informationen (auskommentiert)

```blade
{{-- DEBUG: Uncomment to see what's being passed --}}
{{-- <div class="mb-2 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs">
    <strong>DEBUG:</strong> serviceId = {{ $serviceId ?? 'NULL' }} | preselectedSlot = {{ $preselectedSlot ?? 'NULL' }}
</div> --}}
```

**Usage**: Entferne die `{{--` und `--}}` um die Debug-Info anzuzeigen

## Testing Instructions

### Test 1: Termin Erstellen (Create Form)

1. Navigiere zu **Termine** → **Neuer Termin**
2. **Wähle Filiale** (z.B. "Hauptfiliale")
3. **Wähle Service** (z.B. "Herrenhaarschnitt")
4. **Erwartung**: Week Picker erscheint sofort mit verfügbaren Slots
5. **Fehlerfall**: Falls weiterhin ⚠️ Icon erscheint → Debug aktivieren

### Test 2: Service Wechsel (Reactive Update)

1. Öffne **Neuer Termin**
2. Wähle Service A (z.B. "Herrenhaarschnitt")
3. **Erwartung**: Week Picker zeigt Slots für Service A
4. Ändere zu Service B (z.B. "Bartpflege")
5. **Erwartung**: Week Picker aktualisiert sich automatisch mit Slots für Service B
6. **Fehlerfall**: Falls Week Picker nicht aktualisiert → Debug aktivieren

### Test 3: Termin Verschieben (Edit Modal)

1. Öffne einen bestehenden Termin in der Tabelle
2. Klicke auf **Terminverschiebung** Action
3. **Erwartung**: Week Picker erscheint mit aktuellem Slot vorausgewählt (blau)
4. Wähle neuen Slot
5. Speichere
6. **Erwartung**: Termin wird verschoben, Success-Notification erscheint

### Debug Aktivierung

Falls das Problem weiterhin besteht:

1. Editiere `/var/www/api-gateway/resources/views/livewire/appointment-week-picker-wrapper.blade.php`
2. Entferne die `{{--` und `--}}` in Zeilen 4 und 6:

**Vorher**:
```blade
{{-- <div class="mb-2 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs">
    <strong>DEBUG:</strong> serviceId = {{ $serviceId ?? 'NULL' }} | preselectedSlot = {{ $preselectedSlot ?? 'NULL' }}
</div> --}}
```

**Nachher**:
```blade
<div class="mb-2 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs">
    <strong>DEBUG:</strong> serviceId = {{ $serviceId ?? 'NULL' }} | preselectedSlot = {{ $preselectedSlot ?? 'NULL' }}
</div>
```

3. Refresh die Seite
4. **Mache Screenshot** von der Debug-Info
5. Schicke Screenshot

### Expected Debug Output

**Korrekt** (Service ausgewählt):
```
DEBUG: serviceId = 9d12345a-1234-1234-1234-123456789abc | preselectedSlot = NULL
```

**Fehlerfall** (Service nicht übergeben):
```
DEBUG: serviceId = NULL | preselectedSlot = NULL
```

## Expected Behavior After Fix

### ✅ Korrektes Verhalten:

1. **Initial Load**: ⚠️ Warning Icon erscheint (kein Service ausgewählt) ✅
2. **Service Selection**: Week Picker erscheint sofort mit Slots ✅
3. **Service Change**: Week Picker aktualisiert sich automatisch ✅
4. **Slot Selection**: Slot wird blau highlighted, `starts_at` wird befüllt ✅
5. **Form Submit**: Termin wird mit ausgewähltem Slot gespeichert ✅

### ❌ Fehlerverhalten (Falls weiterhin Problem):

- **Symptom**: ⚠️ Warning Icon bleibt auch nach Service-Auswahl
- **Debug zeigt**: `serviceId = NULL`
- **Ursache**: Service-Select gibt keine UUID zurück oder Filament Form-Kontext ist kaputt
- **Nächster Schritt**: Service Model und Relationship prüfen

## Technical Deep Dive

### Warum funktioniert ->key() ?

Livewire/Filament verwendet Keys für Component Identity:

```php
// Ohne key(): Component wird wiederverwendet (same identity)
<div wire:id="component-xyz">...</div>

// Mit key(): Component wird neu erstellt (different identity)
<div wire:id="component-xyz-service-123">...</div>  // Service 123
<div wire:id="component-xyz-service-456">...</div>  // Service 456 (NEW!)
```

Wenn der Key sich ändert:
1. Livewire destroyed den alten Component
2. Livewire erstellt einen neuen Component
3. Closure wird neu ausgeführt mit aktuellem `$get('service_id')`
4. Livewire Component `AppointmentWeekPicker` wird mit neuer serviceId gemountet

### Alternative Lösungen (nicht implementiert)

#### Option A: Custom Filament Component
```php
class WeekPickerField extends Field
{
    protected string $view = 'filament.forms.components.week-picker';

    public function serviceId(string | Closure $serviceId): static
    {
        $this->serviceId = $serviceId;
        return $this;
    }
}
```

**Vorteile**: Native Filament reactivity
**Nachteile**: Mehr Boilerplate Code, Wartungsaufwand

#### Option B: JavaScript Bridge
```javascript
document.addEventListener('livewire:updated', () => {
    // Manually re-render week picker
    Livewire.find('week-picker').call('$refresh');
});
```

**Vorteile**: No PHP changes
**Nachteile**: Fragile, Race Conditions, Event Soup

#### Option C: Wire Model (Chosen Solution ✅)
- `->key()` für automatische Neu-Renderung
- `->dehydrated(false)` für saubere State-Verwaltung
- Clean, Laravel-idiomatisch, wartbar

## Deployment Checklist

- [x] AppointmentResource.php - Create Form fixed
- [x] AppointmentResource.php - Edit Modal fixed
- [x] appointment-week-picker-wrapper.blade.php - Debug hinzugefügt
- [x] php artisan view:clear
- [x] php artisan cache:clear
- [x] php artisan config:clear
- [ ] User Testing - Create Form
- [ ] User Testing - Edit Modal
- [ ] User Testing - Service Change Reactivity
- [ ] Debug aktivieren falls Problem bleibt
- [ ] Screenshot von funktionierendem Week Picker

## Additional Notes

### Filament ViewField Limitations

ViewField ist für **statische Views** designed, nicht für **reactive Livewire Components**. Die offizielle Empfehlung ist:

1. Für statische Content: ViewField ✅
2. Für reactive Components: Custom Field Class ⚠️
3. Für embedded Livewire: Workarounds wie `->key()` 🔧

Unsere Lösung mit `->key()` ist ein **pragmatischer Workaround**, der funktioniert, aber nicht die "ideale" Filament-Lösung.

### Future Improvements

**Phase 2**: Custom Filament Component
- Erstelle `WeekPickerField` extends `Field`
- Native Filament Reactivity ohne Workarounds
- Bessere Type-Hinting und IDE-Support
- Wiederverwendbar in anderen Resources

---

**Fix Date**: 2025-10-14
**Fix Author**: Claude Code
**Testing Status**: ⏳ Awaiting User Verification
**Estimated Fix Time**: 5 minutes
