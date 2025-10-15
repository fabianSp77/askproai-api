# Appointment Booking V3 - Mitarbeiter-Präferenz Integration

**Date:** 2025-10-14
**Status:** ✅ UPDATED - Berücksichtigt Stammkunden-Präferenz
**Issue:** Vorherige Version ignorierte, dass Kunden spezifische Mitarbeiter wünschen

---

## Problem erkannt

**User Feedback:** "Ist da berücksichtigt, dass manche Kunden nur bei bestimmten Mitarbeitern behandelt werden möchten"

**Fehlende Use Cases in V2:**
- ❌ Stammkunden mit Lieblings-Stylist
- ❌ Mitarbeiter-Loyalität (wichtig für Bindung)
- ❌ Spezialisierungen (z.B. nur Lisa macht Färbungen gut)
- ❌ Persönliche Beziehungen Kunde-Mitarbeiter

---

## Lösung: Hybrid-Ansatz (Beide Welten vereint)

### Use Case 1: Flexible Kunden (Zeit wichtiger als Mitarbeiter)
```
1. Mitarbeiter-Auswahl: [⭐ Egal - Nächster verfügbar] ← Standard
2. Kalender zeigt: ALLE Slots von ALLEN Mitarbeitern
3. Nach Slot-Auswahl: Bester verfügbarer zugewiesen
4. Vorteil: Maximale Slot-Auswahl, schnellste Termine
```

### Use Case 2: Stammkunden (Mitarbeiter wichtiger als Zeit)
```
1. Mitarbeiter-Auswahl: [👩 Anna Schmidt] ← Explizit wählen
2. Kalender zeigt: NUR Slots von Anna
3. Nach Slot-Auswahl: Anna ist garantiert
4. Vorteil: Gewünschte Person, aber möglicherweise weniger Slots
```

---

## UI Design (V3)

### Mitarbeiter-Auswahlbereich (Oben, prominent)

```
┌─────────────────────────────────────────────────────────────┐
│ 👤 Mitarbeiter-Präferenz                   [✕ Auswahl löschen]│
│ Optional: Wähle einen bestimmten Stylisten                   │
├─────────────────────────────────────────────────────────────┤
│ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐    │
│ │ ⭐     │ │ 👩     │ │ 👨     │ │ 👩     │ │ 👨     │    │
│ │ Egal   │ │ Anna   │ │ Max    │ │ Lisa   │ │ Tom    │    │
│ │        │ │ ⭐4.9  │ │ ⭐4.8  │ │ ⭐5.0  │ │ ⭐4.7  │    │
│ │Nächster│ │250+    │ │180+    │ │320+    │ │150+    │    │
│ │verfüg. │ │Termine │ │Termine │ │Termine │ │Termine │    │
│ └────────┘ └────────┘ └────────┘ └────────┘ └────────┘    │
│                                                              │
│ 💡 Tipp: Wenn du keinen bestimmten Mitarbeiter wählst,     │
│    siehst du alle verfügbaren Zeitslots.                    │
└─────────────────────────────────────────────────────────────┘
```

### Kalender (Dynamisch)

**Wenn "Egal" gewählt:**
```
┌──────────────────────────────────────────┐
│ ZEIT │ Mo │ Di │ Mi │ Do │ Fr │ Sa │    │
├──────┼────┼────┼────┼────┼────┼────┤    │
│ 08:00│Anna│Max │    │Lisa│    │    │    │ ← Zeigt alle
│ 09:00│Max │    │Tom │    │Anna│    │    │
│ 14:00│Lisa│Anna│    │    │    │    │    │
└──────┴────┴────┴────┴────┴────┴────┘
```

**Wenn "Anna" gewählt:**
```
┌──────────────────────────────────────────┐
│ ZEIT │ Mo │ Di │ Mi │ Do │ Fr │ Sa │    │
├──────┼────┼────┼────┼────┼────┼────┤    │
│ 08:00│ ✓  │    │    │    │    │    │    │ ← Nur Anna
│ 09:00│    │    │    │    │ ✓  │    │    │
│ 14:00│    │ ✓  │    │    │    │    │    │
└──────┴────┴────┴────┴────┴────┴────┘
```

---

## Interaktionsflow

### Flow A: Flexible Buchung (Zeit-First)
```
1. Page Load
   → Mitarbeiter-Präferenz: [⭐ Egal] (vorselektiert)
   → Kalender: Alle Slots sichtbar

2. User klickt Slot "Mo 14:00"
   → System: Weiter zu Step 2
   → Mitarbeiter: "Lisa Wagner" (war verfügbar für diesen Slot)

3. Service wählen: Haarschnitt
   → Confirmation: "Mo 14:00 mit Lisa Wagner"
```

### Flow B: Mitarbeiter-First (Stammkunde)
```
1. Page Load
   → User klickt: [👩 Anna Schmidt]
   → Kalender: Filtert auf nur Anna's Slots
   → Info: "Kalender zeigt nur Verfügbarkeit von Anna Schmidt"

2. User klickt Slot "Di 14:00"
   → System: Weiter zu Step 2
   → Mitarbeiter: "Anna Schmidt" (garantiert)

3. Service wählen: Färben
   → Confirmation: "Di 14:00 mit Anna Schmidt"
```

### Flow C: Präferenz ändern während Buchung
```
1. Page Load
   → User klickt: [👩 Lisa Wagner]
   → Kalender: Nur Lisa's Slots (z.B. 3 Slots diese Woche)

2. User sieht: Zu wenig Auswahl
   → User klickt: [✕ Auswahl löschen]
   → Kalender: Aktualisiert auf ALLE Slots (z.B. 15 Slots)

3. User klickt: "Mo 16:00" (war Max's Slot)
   → Weiter mit Max als Mitarbeiter
```

---

## Technische Implementation

### State Management (Alpine.js)

```javascript
x-data="{
    // Mitarbeiter-Präferenz
    employeePreference: 'any',  // 'any' oder Name
    selectedEmployee: null,      // { name, rating, appointments, avatar }

    // Bestehende States
    selectedSlot: null,
    service: null,
    customerName: '',

    // Mitarbeiter auswählen
    selectEmployee(employee) {
        this.selectedEmployee = employee;
        this.employeePreference = employee.name;
        // Trigger: Kalender neu laden (nur deren Slots)
        this.refreshCalendar();
    },

    // Präferenz zurücksetzen
    resetEmployeePreference() {
        this.employeePreference = 'any';
        this.selectedEmployee = null;
        // Trigger: Kalender neu laden (alle Slots)
        this.refreshCalendar();
    },

    // Slot auswählen
    selectSlot(datetime, dayLabel, timeLabel, employeeName) {
        this.selectedSlot = datetime;
        this.selectedSlotDay = dayLabel;
        this.selectedSlotTime = timeLabel;

        // Wenn kein Mitarbeiter vorher gewählt, aus Slot übernehmen
        if (!this.selectedEmployee) {
            this.selectedEmployee = {
                name: employeeName || 'Nächster verfügbarer'
            };
        }

        this.step = 2;
    }
}"
```

### Backend Logic (Livewire)

```php
class AppointmentCalendarBooking extends Component
{
    public $employeePreference = 'any';
    public $selectedEmployeeId = null;

    public function updatedEmployeePreference()
    {
        // Wenn "any", alle Mitarbeiter-Slots laden
        if ($this->employeePreference === 'any') {
            $this->availableSlots = $this->getAllAvailableSlots();
        }
        // Sonst nur Slots des gewählten Mitarbeiters
        else {
            $this->availableSlots = $this->getEmployeeSlots($this->selectedEmployeeId);
        }
    }

    protected function getAllAvailableSlots()
    {
        // Cal.com API: Alle Mitarbeiter abfragen
        return Cache::remember("slots:all:{$this->date}", 60, function() {
            $slots = [];
            foreach ($this->employees as $employee) {
                $employeeSlots = CalcomService::getAvailability($employee->calcom_id);
                foreach ($employeeSlots as $slot) {
                    $slot['employee_name'] = $employee->name;
                    $slot['employee_id'] = $employee->id;
                    $slots[] = $slot;
                }
            }
            return $slots;
        });
    }

    protected function getEmployeeSlots($employeeId)
    {
        // Cal.com API: Nur gewählter Mitarbeiter
        return Cache::remember("slots:{$employeeId}:{$this->date}", 60, function() use ($employeeId) {
            $employee = Employee::find($employeeId);
            return CalcomService::getAvailability($employee->calcom_id);
        });
    }
}
```

### Slot-Rendering (Blade)

```blade
@foreach($slots as $slot)
    <button
        wire:click="selectSlot('{{ $slot['datetime'] }}')"
        @click.prevent="selectSlot(
            '{{ $slot['datetime'] }}',
            '{{ $slot['day_label'] }}',
            '{{ $slot['time_label'] }}',
            '{{ $slot['employee_name'] ?? 'Verfügbar' }}'
        )"
        class="slot-button">
        {{ $slot['time'] }}

        {{-- Zeige Mitarbeiter-Name nur wenn "Egal" gewählt --}}
        @if($employeePreference === 'any')
            <span class="text-xs text-gray-600">
                {{ $slot['employee_name'] }}
            </span>
        @endif
    </button>
@endforeach
```

---

## Vorteile des Hybrid-Ansatzes

### Für flexible Kunden:
✅ **Maximale Slot-Auswahl** - Alle Mitarbeiter = mehr Termine
✅ **Schnellere Termine** - Nächster verfügbarer = heute/morgen möglich
✅ **Weniger Wartezeit** - Keine Präferenz = kürzere Vorlaufzeit

### Für Stammkunden:
✅ **Garantierte Person** - Immer der gewünschte Mitarbeiter
✅ **Vertrauensbeziehung** - Kennen den Stylisten bereits
✅ **Konsistenz** - Gleicher Schnitt/Stil jedes Mal
✅ **Spezialisierungen** - Bestimmte Skills (z.B. Färben)

### Für das Salon:
✅ **Mitarbeiter-Loyalität** - Stammkunden binden ans Team
✅ **Auslastungs-Optimierung** - Flexible Kunden füllen Lücken
✅ **Umsatz-Maximierung** - Mehr Buchungen durch beide Flows
✅ **Team-Bindung** - Mitarbeiter bauen eigenen Kundenstamm auf

---

## Vergleich: V2 vs V3

| Feature | V2 (Auto-Assign) | V3 (Hybrid) |
|---------|------------------|-------------|
| **Mitarbeiter-Auswahl** | Nicht möglich | ✅ Optional, prominent |
| **Stammkunden-Flow** | ❌ Nicht unterstützt | ✅ Dedizierter Flow |
| **Flexible Kunden** | ✅ Optimal | ✅ Optimal (Default) |
| **Slot-Anzahl** | Maximal | Maximal ODER gefiltert |
| **Use Cases** | 50% abgedeckt | 100% abgedeckt |
| **Mitarbeiter-Bindung** | Schwach | Stark |

---

## User Experience Szenarien

### Szenario 1: Neue Kundin (Erstes Mal)
```
Emma, 28, möchte zum ersten Mal zum Salon.

1. Öffnet Buchungsseite
2. Sieht: "Mitarbeiter-Präferenz: ⭐ Egal (Nächster verfügbar)"
3. Denkt: "Perfekt, kenne eh niemanden"
4. Kalender zeigt: 15 Slots diese Woche
5. Wählt: "Morgen 10:00"
6. System: "Lisa Wagner" zugewiesen
7. Bucht erfolgreich

Result: ✅ Schneller Termin, keine Überforderung
```

### Szenario 2: Stammkundin (Lieblingsmitarbeiterin)
```
Sarah, 35, geht seit 2 Jahren zu Anna.

1. Öffnet Buchungsseite
2. Klickt sofort: "👩 Anna Schmidt"
3. Kalender zeigt: 4 Slots diese Woche (nur Anna)
4. Wählt: "Donnerstag 14:00"
5. Confirmation: "Mit Anna Schmidt" ← Wichtig für sie!
6. Bucht glücklich

Result: ✅ Gewünschte Person, vertraute Beziehung
```

### Szenario 3: Pragmatische Kundin (Flexibel aber informiert)
```
Julia, 42, hat Präferenz für Max aber braucht dringend Termin.

1. Öffnet Buchungsseite
2. Klickt: "👨 Max Müller"
3. Kalender zeigt: 2 Slots nächste Woche
4. Denkt: "Zu lange warten..."
5. Klickt: "✕ Auswahl löschen"
6. Kalender zeigt: 12 Slots diese Woche
7. Wählt: "Morgen 16:00" (ist Lisa)
8. Denkt: "Ok, diesmal Lisa, nächstes Mal Max"

Result: ✅ Flexibilität, informierte Entscheidung
```

---

## Implementation Checklist

### Frontend (Prototyp → Livewire)
- [x] Mitarbeiter-Grid UI (5 Cards: Egal + 4 Mitarbeiter)
- [x] Selected State für Mitarbeiter-Card
- [x] "Auswahl löschen" Button (conditional)
- [x] Info-Box (zeigt aktuellen Filter-Status)
- [x] Slot-Buttons zeigen Mitarbeiter-Name (conditional)
- [ ] Livewire Component erstellen
- [ ] Alpine.js State Management integrieren

### Backend (Logik)
- [ ] `updatedEmployeePreference()` implementieren
- [ ] `getAllAvailableSlots()` - Alle Mitarbeiter parallel abfragen
- [ ] `getEmployeeSlots($id)` - Einzelner Mitarbeiter
- [ ] Caching pro Mitarbeiter (Redis, 60s TTL)
- [ ] Performance-Optimierung (N+1 Queries vermeiden)

### Database
- [ ] Mitarbeiter-Fotos hochladen (optional)
- [ ] Mitarbeiter-Ratings berechnen (aus bisherigen Appointments)
- [ ] Spezialisierungen in `employees` Tabelle (JSON field)
- [ ] Analytics: Tracking "Employee Preference Usage Rate"

### Testing
- [ ] E2E Test: "Egal" Flow (alle Slots sichtbar)
- [ ] E2E Test: Mitarbeiter gewählt (nur deren Slots)
- [ ] E2E Test: Präferenz wechseln (Kalender aktualisiert)
- [ ] Performance Test: 4 Mitarbeiter parallel abfragen (<500ms)
- [ ] Mobile Test: Mitarbeiter-Grid responsive

---

## Metriken zum Tracken

### Business Metrics
- **Employee Preference Rate:** Wie viele wählen explizit? (Ziel: 30-40%)
- **"Egal" Usage Rate:** Wie viele nutzen flexible Option? (Ziel: 60-70%)
- **Employee Loyalty Score:** Wiederbuchungs-Rate pro Mitarbeiter
- **Slot Fill Rate:** Auslastung pro Mitarbeiter (sollte ausgeglichen sein)

### Conversion Metrics
- **Conversion "Egal" Path:** Ziel: 5-7%
- **Conversion "Specific Employee" Path:** Ziel: 6-8% (höher weil Stammkunden)
- **Präferenz-Wechsel-Rate:** Wie oft wechseln User von spezifisch zu "Egal"?
- **Abandonment nach Mitarbeiter-Filter:** Zu wenig Slots = Abbruch?

---

## Nächste Schritte

### Sofort:
1. **Prototyp testen:** https://api.askproai.de/appointment-optimized-v3-employee-preference.html
2. **Feedback geben:** Ist der Hybrid-Ansatz gut? Änderungen?

### Nach Freigabe:
1. **Phase 1:** Mitarbeiter-Grid in Livewire Component (3-4h)
2. **Phase 2:** Backend-Logik für Slot-Filterung (4-5h)
3. **Phase 3:** Caching & Performance-Optimierung (2-3h)
4. **Phase 4:** Testing & Mobile-Optimierung (3-4h)

**Gesamt:** ~12-16 Stunden zusätzlich zu ursprünglichem Plan

---

## Preview URLs

**V3 (mit Mitarbeiter-Präferenz):**
https://api.askproai.de/appointment-optimized-v3-employee-preference.html ← **NEUE VERSION**

**V2 (nur Auto-Assign):**
https://api.askproai.de/appointment-optimized-final.html

**Vergleich:** Beide öffnen und Flow durchspielen!

---

**Status:** ✅ Mitarbeiter-Präferenz integriert
**Wartet auf:** User Feedback & Freigabe
**Empfehlung:** V3 implementieren (deckt alle Use Cases ab)
