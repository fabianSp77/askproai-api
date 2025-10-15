# Available Slot Picker - Implementation Options

**Problem:** Aktuell kann man JEDE Zeit buchen, auch wenn der Slot schon belegt ist. Keine Anzeige verfügbarer Slots.

**Bereits vorhanden:** `findAvailableSlots()` Methode (Line 1256-1321) funktioniert bereits!
- Prüft Staff-Verfügbarkeit
- Arbeitszeiten: 9-17 Uhr, 15-Min Slots
- Conflict Detection
- Sucht bis 14 Tage in die Zukunft

---

## Option 1: Radio Buttons mit verfügbaren Slots ⭐ EMPFOHLEN

**Wie Filament Examples Tutorial**

### UX Flow:
```
1. User wählt Datum
2. System lädt verfügbare Slots für diesen Tag
3. Radio-Buttons zeigen NUR verfügbare Zeiten
4. User klickt auf gewünschten Slot
```

### Implementierung:
```php
DatePicker::make('appointment_date')
    ->label('Datum')
    ->minDate(now())
    ->maxDate(now()->addWeeks(2))
    ->required()
    ->reactive()
    ->afterStateUpdated(fn (callable $set) => $set('starts_at', null)),

Radio::make('starts_at')
    ->label('Verfügbare Zeitfenster')
    ->options(function (callable $get) {
        $date = $get('appointment_date');
        $staffId = $get('staff_id');
        $duration = $get('duration_minutes') ?? 30;

        if (!$date || !$staffId) {
            return [];
        }

        // Hole verfügbare Slots für diesen Tag
        $allSlots = self::findAvailableSlots($staffId, $duration, 50);
        $daySlots = collect($allSlots)
            ->filter(fn ($slot) => $slot->isSameDay(Carbon::parse($date)))
            ->mapWithKeys(fn ($slot) => [
                $slot->toDateTimeString() => $slot->format('H:i') . ' Uhr'
            ])
            ->toArray();

        return $daySlots ?: ['no_slots' => 'Keine freien Zeitfenster'];
    })
    ->hidden(fn (callable $get) => !$get('appointment_date') || !$get('staff_id'))
    ->required()
    ->helperText('Wählen Sie einen verfügbaren Zeitslot'),
```

### ✅ Vorteile:
- Einfach zu implementieren (2-3h)
- User sieht NUR verfügbare Slots
- Keine Konflikt-Fehler möglich
- State-of-the-Art (wie Calendly)

### ⚠️ Nachteile:
- Nur ein Tag auf einmal sichtbar
- User muss Datum erst wählen

**Aufwand:** ⏱️ 2-3 Stunden

---

## Option 2: Dropdown + Validation mit Conflict Warning

**Kompromiss-Lösung**

### UX Flow:
```
1. User wählt frei Datum + Zeit (wie jetzt)
2. System prüft bei Änderung auf Konflikte
3. Warnung wenn Slot belegt: "⚠️ Dieser Zeitslot ist bereits belegt!"
4. Vorschlag: "Nächster freier Slot: 14:30 Uhr"
```

### Implementierung:
```php
DateTimePicker::make('starts_at')
    ->label('Beginn')
    ->reactive()
    ->afterStateUpdated(function ($state, callable $get, callable $set) {
        if (!$state) return;

        $staffId = $get('staff_id');
        $duration = $get('duration_minutes') ?? 30;
        $slotStart = Carbon::parse($state);
        $slotEnd = $slotStart->copy()->addMinutes($duration);

        // Prüfe auf Konflikte
        $hasConflict = Appointment::where('staff_id', $staffId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($slotStart, $slotEnd) {
                $q->whereBetween('starts_at', [$slotStart, $slotEnd])
                  ->orWhereBetween('ends_at', [$slotStart, $slotEnd])
                  ->orWhere(function ($q2) use ($slotStart, $slotEnd) {
                      $q2->where('starts_at', '<=', $slotStart)
                         ->where('ends_at', '>=', $slotEnd);
                  });
            })
            ->exists();

        if ($hasConflict) {
            // Finde nächsten freien Slot
            $nextSlots = self::findAvailableSlots($staffId, $duration, 3);
            $nextSlot = $nextSlots[0] ?? null;

            Notification::make()
                ->warning()
                ->title('⚠️ Zeitslot bereits belegt')
                ->body($nextSlot
                    ? "Nächster freier Slot: {$nextSlot->format('d.m.Y H:i')} Uhr"
                    : "Keine freien Slots in den nächsten 2 Wochen"
                )
                ->persistent()
                ->actions([
                    Action::make('use_next')
                        ->label('Nächsten Slot verwenden')
                        ->button()
                        ->close()
                        ->action(fn () => $nextSlot ? $set('starts_at', $nextSlot) : null)
                ])
                ->send();
        }
    })
```

### ✅ Vorteile:
- Flexibilität bleibt (User kann eigene Zeit wählen)
- Sofortige Warnung bei Konflikten
- Quick-Fix: "Nächsten Slot verwenden" Button

### ⚠️ Nachteile:
- User kann trotzdem Konflikt-Slot buchen (nur Warnung)
- Nicht preventiv (erst nach Auswahl)

**Aufwand:** ⏱️ 1-2 Stunden

---

## Option 3: Visual Calendar Widget (FullCalendar)

**Premium-Lösung wie Cal.com**

### Installation:
```bash
composer require saade/filament-fullcalendar
```

### UX Flow:
```
1. User sieht Kalender mit allen bestehenden Terminen
2. Verfügbare Slots sind grün markiert
3. Belegte Slots sind rot/grau
4. Click auf verfügbaren Slot → Zeit wird gesetzt
```

### Implementierung:
```php
// Custom Livewire Component
public function render() {
    $staffId = $this->form->getState()['staff_id'];
    $duration = $this->form->getState()['duration_minutes'] ?? 30;

    // Hole verfügbare Slots
    $availableSlots = AppointmentResource::findAvailableSlots($staffId, $duration, 100);

    // Bestehende Termine
    $existingAppointments = Appointment::where('staff_id', $staffId)
        ->where('status', '!=', 'cancelled')
        ->get();

    $events = [
        // Verfügbare Slots (grün)
        ...$availableSlots->map(fn ($slot) => [
            'title' => '✓ Verfügbar',
            'start' => $slot->toIso8601String(),
            'end' => $slot->copy()->addMinutes($duration)->toIso8601String(),
            'color' => '#10b981', // green
            'classNames' => ['available-slot'],
        ]),

        // Belegte Slots (rot)
        ...$existingAppointments->map(fn ($appt) => [
            'title' => '✗ Belegt',
            'start' => $appt->starts_at,
            'end' => $appt->ends_at,
            'color' => '#ef4444', // red
            'classNames' => ['booked-slot'],
        ]),
    ];

    return view('filament.calendar-slot-picker', [
        'events' => $events
    ]);
}
```

### ✅ Vorteile:
- **Visually stunning** wie Cal.com
- Übersicht über ganze Woche/Monat
- Drag & Drop möglich
- Professional appearance

### ⚠️ Nachteile:
- Komplexe Integration (5-8h)
- Zusätzliches Plugin erforderlich
- Performance bei vielen Slots

**Aufwand:** ⏱️ 5-8 Stunden

---

## Option 4: Hybrid - "Next Available Slots" Panel

**Best of Both Worlds**

### UX Flow:
```
1. User sieht Panel mit nächsten 10 verfügbaren Slots
2. Quick-Click auf einen Slot → Sofort gebucht
3. ODER: Manual DateTimePicker für eigene Zeit (mit Validation)
```

### Implementierung:
```php
Grid::make(2)->schema([
    // Linke Spalte: Verfügbare Slots
    Section::make('⚡ Schnellauswahl')
        ->schema([
            Placeholder::make('quick_slots')
                ->content(function (callable $get) {
                    $staffId = $get('staff_id');
                    $duration = $get('duration_minutes') ?? 30;

                    if (!$staffId) {
                        return '⚠️ Bitte zuerst Mitarbeiter wählen';
                    }

                    $slots = self::findAvailableSlots($staffId, $duration, 10);

                    $html = '<div class="space-y-2">';
                    foreach ($slots as $slot) {
                        $html .= '
                            <button
                                type="button"
                                wire:click="selectSlot(\'' . $slot->toDateTimeString() . '\')"
                                class="w-full px-4 py-2 text-left border rounded hover:bg-green-50"
                            >
                                ' . $slot->format('D, d.m.Y H:i') . ' Uhr
                            </button>
                        ';
                    }
                    $html .= '</div>';

                    return new HtmlString($html);
                })
        ]),

    // Rechte Spalte: Manual Input
    Section::make('🔧 Manuelle Eingabe')
        ->schema([
            DateTimePicker::make('starts_at')
                ->label('Oder eigene Zeit wählen')
                // + Validation wie Option 2
        ])
])
```

### ✅ Vorteile:
- **Hybrid**: Quick für 80%, Manual für 20%
- Übersichtlich: Nächste 10 Slots auf einen Blick
- Flexibel: Eigene Zeit trotzdem möglich
- Mittelweg zwischen einfach & luxuriös

### ⚠️ Nachteile:
- Mehr Platz im Form benötigt
- Zwei Eingabe-Methoden können verwirren

**Aufwand:** ⏱️ 3-4 Stunden

---

## Vergleich & Empfehlung

| Option | Aufwand | UX-Qualität | Flexibilität | State-of-the-Art |
|--------|---------|-------------|--------------|------------------|
| **1. Radio Buttons** | ⏱️⏱️ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ✅ Wie Calendly |
| 2. Validation Warning | ⏱️ | ⭐⭐ | ⭐⭐⭐⭐⭐ | ❌ Reaktiv statt preventiv |
| 3. Visual Calendar | ⏱️⏱️⏱️⏱️ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ✅ Wie Cal.com |
| **4. Hybrid Panel** | ⏱️⏱️⏱️ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ✅ Best of Both |

---

## Meine Empfehlung: Option 1 ODER Option 4

### **Option 1** (Radio Buttons) wenn:
- ✅ Schnelle Implementierung gewünscht (2-3h)
- ✅ User soll NUR verfügbare Slots buchen
- ✅ Standard Booking-Flow wie Calendly

### **Option 4** (Hybrid Panel) wenn:
- ✅ Flexibilität für Admins wichtig (auch außerhalb Slots buchen)
- ✅ Quick Actions UX gewünscht
- ✅ Best of Both Worlds

---

## Was ich empfehle zu implementieren:

**Phase A (Sofort):** Option 1 - Radio Buttons
- 2-3 Stunden Aufwand
- 100% Konflikt-Prevention
- User sieht nur verfügbare Slots
- State-of-the-Art wie Calendly

**Phase B (Optional später):** Upgrade zu Option 4 - Hybrid
- Zusätzlich 1-2h Aufwand
- Admin bekommt "Manual Override" Option
- Behält Radio Buttons für Standard-Flow

---

## Implementation Plan - Option 1

1. **Neue Felder hinzufügen:**
   - `appointment_date` (DatePicker)
   - `available_slots` (Radio - ersetzt starts_at Picker)

2. **Logik anpassen:**
   - `findAvailableSlots()` nach Datum filtern
   - Radio Options dynamisch laden
   - `starts_at` aus Radio-Selection setzen

3. **UX-Flow:**
   ```
   Service wählen → Duration wird gesetzt
   ↓
   Mitarbeiter wählen → Staff verfügbar
   ↓
   Datum wählen → Verfügbare Slots für Tag werden geladen
   ↓
   Slot aus Radio wählen → starts_at + ends_at automatisch gesetzt
   ```

4. **Error Handling:**
   - Kein Datum → Radio hidden
   - Kein Staff → Radio hidden
   - Keine Slots → "Keine freien Zeitfenster an diesem Tag"

**Ready to implement?**
