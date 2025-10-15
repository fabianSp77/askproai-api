# 📅 Master-Plan: Wochenkalender für Terminbuchung

**Datum**: 2025-10-14
**Feature**: Calendly-Style Wochenkalender mit Echtzeit-Verfügbarkeiten
**Use-Case**: Friseur bucht Termine für Kunden (am Telefon/Tisch)
**Ziel**: Blitzschnelle, intuitive Terminbuchung (<1 Sekunde)

---

## 🎯 Executive Summary

### Problem
**Aktuell**: Modal mit Liste von 5 Slots (nicht intuitiv, keine Übersicht)
**Kunde-Request**: "Wochenansicht Montag-Sonntag, wo man sieht wann Verfügbarkeiten sind"

### Lösung
**Calendly-Style Unified Calendar**:
- **Links**: Wochenübersicht (Mo-So) mit Verfügbarkeits-Badges
- **Rechts**: Verfügbare Zeitslots des gewählten Tags (15-Min-Raster)
- **Performance**: <1 Sekunde von Klick bis interaktiver Kalender
- **Integration**: Nahtlos in bestehendes Filament Modal

### Aufwand & Timeline
- **MVP (Phase 1)**: 4-5 Arbeitstage
- **Production-Ready (Phase 2)**: +2-3 Tage
- **Optimierung (Phase 3)**: +1-2 Tage
- **Gesamt**: ~2 Wochen für vollständige Implementierung

---

## 📊 Agent-Analysen Zusammenfassung

### 🎨 Frontend-Architect Agent
**Empfehlung**: **Hybrid Livewire + Alpine.js**
- Livewire: Server-seitige Slot-Berechnung (Security)
- Alpine.js: Client-seitige Interaktivität (Speed)
- Layout: Desktop = Horizontal Split (Woche | Slots), Mobile = Vertical Stack
- Performance: Virtual Scrolling für 480 Slots → <100ms Rendering

### 🏗️ Backend-Architect Agent
**Optimierung**: **Hash-Map-Algorithmus + Redis-Cache**
- O(1) Konflikt-Check statt O(n²)
- Single DB-Query für 1 Woche (statt N Queries)
- Redis-Cache: 5ms statt 200ms (95% schneller)
- Event-Driven Cache-Invalidierung

### ⚡ Performance-Engineer Agent
**Critical Fix**: **Redis-Cache aktivieren** (70% Performance-Gewinn)
- `.env`: `CACHE_STORE=redis` (aktuell: `file`)
- Virtual Scrolling: 480 Slots → nur 50-80 sichtbar
- Response-Compression: 96KB → 20KB (80% kleiner)

### 🔍 Deep-Research Agent
**Best Practices**: **Unified Month+Day View** (Calendly-Standard)
- Cal.com: 90% Performance-Verbesserung (20s → 2s)
- Reservation-Pattern: 10-Minuten Timeout verhindert Double-Bookings
- Optimistic Locking: Besser als Pessimistic für Booking-Systeme

---

## 🏗️ Architektur-Design

### Komponenten-Hierarchie

```
📦 AppointmentSlotPickerComponent (Livewire)
 ├─ 📅 WeekCalendarGrid (Blade + Alpine)
 │   ├─ WeekNavigator (◀ Zurück | Heute | Weiter ▶)
 │   ├─ DayHeaders (Mo, Di, Mi, Do, Fr, Sa, So)
 │   └─ DayCards (7 Karten mit Verfügbarkeits-Info)
 │
 ├─ ⏰ TimeSlotPanel (Blade + Alpine)
 │   ├─ SelectedDateHeader ("Montag, 14. Oktober")
 │   ├─ SlotList (09:00, 09:15, 09:30, ...)
 │   └─ NoSlotsMessage ("⚠️ Keine freien Slots")
 │
 └─ 🔧 HiddenFormFields (Filament Integration)
     ├─ starts_at (Hidden Input)
     └─ ends_at (Hidden Input)
```

### Datei-Struktur

```
app/
├─ Livewire/
│  └─ AppointmentSlotPicker.php (Hauptkomponente)
│
├─ Services/
│  ├─ WeeklySlotService.php (DB-Queries)
│  ├─ SlotGeneratorService.php (Slot-Algorithmus)
│  └─ SlotCacheService.php (Redis-Cache)
│
resources/views/
├─ livewire/
│  └─ appointment-slot-picker.blade.php (Main Template)
│
├─ components/
│  ├─ week-calendar-grid.blade.php (Wochenansicht)
│  └─ time-slot-panel.blade.php (Zeitslots)
│
database/
└─ migrations/
   └─ add_appointment_indexes.php (Performance)
```

---

## 🎨 UI/UX Design

### Desktop-Layout (1920×1080)

```
┌────────────────────────────────────────────────────────────────┐
│  📅 Termin-Zeitpunkt auswählen                    [✕ Schließen]│
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────┬──────────────────────────┐│
│  │  📅 14.10. - 20.10.2025         │  ⏰ Montag, 14. Oktober  ││
│  │  [◀] [Heute] [▶]                │  12 freie Zeitfenster    ││
│  ├─────────────────────────────────┤                          ││
│  │  ┌──┐ ┌──┐ ┌──┐ ┌──┐ ┌──┐     │  ○ 09:00 Uhr            ││
│  │  │Mo│ │Di│ │Mi│ │Do│ │Fr│     │  ○ 09:15 Uhr            ││
│  │  │14│ │15│ │16│ │17│ │18│     │  ○ 09:30 Uhr            ││
│  │  └━━┘ └──┘ └──┘ └──┘ └──┘     │  ○ 10:00 Uhr            ││
│  │                                  │  ○ 10:15 Uhr            ││
│  │  12 frei  8 frei  5 frei       │  ○ 10:30 Uhr            ││
│  │                                  │  ...                    ││
│  │  ┌──┐ ┌──┐                     │                          ││
│  │  │Sa│ │So│                     │  [🔄 Andere Woche]       ││
│  │  │19│ │20│                     │                          ││
│  │  └──┘ └──┘                     │                          ││
│  │                                  │                          ││
│  │  Geschlossen                    │                          ││
│  └─────────────────────────────────┴──────────────────────────┘│
│                                                                 │
│  Ausgewählt: Mo, 14.10.2025 um 09:30 Uhr (30 Min)             │
│  [✅ Termin mit dieser Zeit buchen]  [Abbrechen]               │
└────────────────────────────────────────────────────────────────┘
```

### Mobile-Layout (<768px)

```
┌─────────────────────────────┐
│  📅 Woche wählen           │
├─────────────────────────────┤
│  [◀] 14.-20.10. [▶]        │
│                             │
│  Mo Di Mi Do Fr Sa So      │
│  14 15 16 17 18 19 20      │
│  ━━ ── ── ── ── ── ──      │
│                             │
│  ▼ Montag, 14. Oktober      │
│  12 freie Slots             │
│                             │
│  ⏰ Verfügbare Zeiten:      │
│  ┌───────────────────────┐ │
│  │ ○ 09:00 Uhr           │ │
│  │ ○ 09:15 Uhr           │ │
│  │ ○ 09:30 Uhr           │ │
│  │ ○ 10:00 Uhr           │ │
│  └───────────────────────┘ │
│                             │
│  [✅ 09:30 auswählen]      │
└─────────────────────────────┘
```

---

## ⚡ Performance-Optimierung

### Performance-Budget

| Schritt | Aktuell | Ziel | Optimierung |
|---------|---------|------|-------------|
| Modal-Öffnung | ~100ms | <200ms | ✅ OK |
| Slot-Daten laden | 300-800ms | <500ms | Redis-Cache |
| UI-Rendering | 200-500ms | <100ms | Virtual Scrolling |
| Interaktion | <50ms | <50ms | ✅ OK |
| **GESAMT** | 650-1450ms | **<1000ms** | ✅ Erreichbar! |

### Quick Wins (70% Performance-Gewinn)

#### 1. Redis-Cache aktivieren (CRITICAL)
```bash
# .env
CACHE_STORE=redis  # statt "file"
```
**Impact**: 50-200ms → <5ms (95% schneller)

#### 2. Database-Indexes
```sql
CREATE INDEX idx_appointments_week_slots
ON appointments (staff_id, starts_at, ends_at, status)
WHERE status != 'cancelled';
```

#### 3. Virtual Scrolling (Frontend)
```javascript
// Nur 50-80 sichtbare Slots rendern (statt 480)
visibleSlots = allSlots.slice(startIdx, endIdx);
```

---

## 🔄 Implementierungs-Roadmap

### 📍 Phase 1: MVP (4-5 Tage) - EMPFOHLEN ZU STARTEN

#### Tag 1: Backend-Services (6-8h)
```bash
# Services erstellen
app/Services/WeeklySlotService.php      # DB-Queries
app/Services/SlotGeneratorService.php   # Slot-Algorithmus
app/Services/SlotCacheService.php       # Redis-Cache

# Migration
database/migrations/add_appointment_indexes.php

# Tests
php artisan migrate
php artisan test --filter=WeeklySlotTest
```

**Deliverables**:
- ✅ `WeeklySlotService::getWeekAppointments()` - Single Query für 1 Woche
- ✅ `SlotGeneratorService::generateWeekSlots()` - O(1) Konflikt-Check
- ✅ `SlotCacheService` mit Redis-Integration
- ✅ Database-Indexes für Performance

---

#### Tag 2: Livewire Component (6-8h)
```bash
# Livewire Component
php artisan make:livewire AppointmentSlotPicker

# Blade Templates
resources/views/livewire/appointment-slot-picker.blade.php
resources/views/components/week-calendar-grid.blade.php
resources/views/components/time-slot-panel.blade.php
```

**Deliverables**:
- ✅ `AppointmentSlotPicker.php` Livewire Component
- ✅ `loadWeekSlots()` - lädt Slots via Service
- ✅ `selectDate()` / `selectSlot()` - User-Actions
- ✅ `navigateWeek()` - Vor/Zurück/Heute

---

#### Tag 3: UI-Templates & Styling (6-8h)
```bash
# Blade-Templates mit Tailwind CSS
week-calendar-grid.blade.php      # 7-Tage-Ansicht
time-slot-panel.blade.php          # Slot-Liste
```

**Features**:
- ✅ Responsive Design (Desktop + Mobile)
- ✅ Hover-States, Loading-States
- ✅ Accessibility (Keyboard-Navigation, ARIA-Labels)
- ✅ Dark Mode Support

---

#### Tag 4: Filament-Integration (4-6h)
```bash
# AppointmentResource anpassen
app/Filament/Resources/AppointmentResource.php
```

**Änderungen**:
```php
// Reschedule Action: DatePicker → Livewire Component ersetzen
Tables\Actions\Action::make('reschedule')
    ->form([
        // ALT: Forms\Components\DateTimePicker::make('starts_at')

        // NEU: Custom Livewire Component
        Forms\Components\ViewField::make('slot_picker')
            ->view('livewire.appointment-slot-picker')
            ->afterStateHydrated(function ($component, $state) {
                // Initialisiere mit aktuellem Termin-Daten
            }),

        // Hidden Fields (gefüllt durch Livewire)
        Forms\Components\Hidden::make('starts_at'),
        Forms\Components\Hidden::make('ends_at'),
    ])
```

**Deliverables**:
- ✅ Reschedule-Action nutzt neuen Kalender
- ✅ Hidden Fields werden durch Livewire gefüllt
- ✅ Form-Validation funktioniert

---

#### Tag 5: Testing & Bugfixes (4-6h)
```bash
# Manual Testing
- Desktop: Chrome, Firefox, Safari
- Mobile: iOS Safari, Chrome Android
- Keyboard-Navigation (Tab, Enter, Pfeiltasten)
- Screen-Reader (NVDA/JAWS)

# Performance Testing
- Lighthouse (Ziel: >90 Performance Score)
- Network-Tab (Slot-Loading <500ms?)
- Memory-Profiling (Keine Memory-Leaks?)

# Edge-Cases
- Keine Slots verfügbar
- Slot zwischenzeitlich gebucht (Race Condition)
- Network-Fehler (Cal.com API down)
```

**Deliverables**:
- ✅ Alle Manual Tests bestanden
- ✅ Lighthouse Score >90
- ✅ Edge-Cases abgefangen
- ✅ User-Feedback gesammelt

---

### 📍 Phase 2: Production-Ready (2-3 Tage) - Optional

#### Tag 6-7: Reservation Pattern (Race-Condition Prevention)
```php
// Temporäre Reservierung beim Slot-Klick
public function reserveSlot($slotTime) {
    DB::table('slot_reservations')->insert([
        'slot_time' => $slotTime,
        'staff_id' => $this->staffId,
        'session_id' => session()->getId(),
        'expires_at' => now()->addMinutes(10),
    ]);
}

// Cron Job: Cleanup expired reservations
Schedule::call(function () {
    DB::table('slot_reservations')
        ->where('expires_at', '<', now())
        ->delete();
})->everyMinute();
```

#### Tag 8: Cache-Invalidierung & Real-Time Updates
```php
// Event Listener
protected $listen = [
    AppointmentBooked::class => [InvalidateSlotsCache::class],
    AppointmentRescheduled::class => [InvalidateSlotsCache::class],
];

// Optional: Livewire Polling für Updates
<div wire:poll.60s="refreshSlots">
```

---

### 📍 Phase 3: Optimierung (1-2 Tage) - Nice-to-Have

#### Tag 9: Performance-Tuning
- Virtual Scrolling implementieren
- Prefetching (nächste Woche vorab laden)
- Response-Compression (Gzip)

#### Tag 10: UX-Enhancements
- Drag & Drop (Termin verschieben)
- Keyboard-Shortcuts (N = Next Week, P = Previous, T = Today)
- Quick Actions ("Nächster freier Slot")

---

## 🧪 Testing-Strategie

### Unit-Tests
```php
// tests/Unit/Services/SlotGeneratorServiceTest.php
public function test_generates_correct_number_of_slots()
{
    $service = new SlotGeneratorService();
    $weekStart = Carbon::parse('2025-10-14');
    $slots = $service->generateWeekSlots(
        staffId: 1,
        weekStart: $weekStart,
        duration: 30,
        bookedAppointments: collect()
    );

    // 7 Tage × 8h × 4 Slots/h = 224 Slots (ohne Sonntag)
    $this->assertCount(224, $slots);
}

public function test_conflict_detection_works()
{
    // Slot um 09:00 gebucht
    $booked = collect([
        (object)['start_time' => Carbon::parse('2025-10-14 09:00')]
    ]);

    $slots = $service->generateWeekSlots(..., $booked);

    // 09:00 sollte "available: false" sein
    $slot = collect($slots)->firstWhere('start_time', '2025-10-14T09:00:00+02:00');
    $this->assertFalse($slot['available']);
}
```

### Integration-Tests
```php
// tests/Feature/AppointmentSlotPickerTest.php
public function test_can_load_weekly_slots()
{
    $response = $this->actingAs($user)
        ->get('/api/appointments/slots/week?staff_id=1&week=2025-W42&duration=30');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['start_time', 'end_time', 'available']
            ],
            'meta' => ['total_slots', 'available_slots']
        ]);
}
```

### Performance-Tests
```bash
# Artillery Load Testing
artillery quick --count 100 --num 10 \
  https://localhost/api/appointments/slots/week?staff_id=1&week=2025-W42

# Erwartung: p95 < 500ms, p99 < 1000ms
```

---

## 📝 Code-Snippets (Pseudo-Code)

### Livewire Component
```php
<?php
namespace App\Livewire;

use Livewire\Component;
use App\Services\SlotCacheService;

class AppointmentSlotPicker extends Component
{
    public ?string $staffId = null;
    public ?int $serviceDuration = 30;
    public string $selectedDate;
    public string $selectedWeekStart;
    public ?string $selectedSlot = null;

    public array $weekDays = [];
    public array $timeSlots = [];

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->selectedWeekStart = now()->startOfWeek()->format('Y-m-d');
        $this->loadWeekSlots();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedSlot = null;
        $this->dispatch('date-selected', date: $date);
    }

    public function selectSlot(string $slotTime): void
    {
        $this->selectedSlot = $slotTime;

        $start = Carbon::parse($slotTime);
        $end = $start->copy()->addMinutes($this->serviceDuration);

        // Emit zu Filament Form
        $this->dispatch('slot-selected', [
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $end->toIso8601String(),
        ]);
    }

    protected function loadWeekSlots(): void
    {
        $cacheKey = "slots:{$this->staffId}:{$this->selectedWeekStart}";

        $this->timeSlots = Cache::remember($cacheKey, 300, function() {
            return app(SlotCacheService::class)->getWeeklySlots(
                $this->staffId,
                $this->selectedWeekStart,
                $this->serviceDuration
            );
        });

        $this->buildWeekDays();
    }
}
```

### Blade Template
```blade
<div x-data="slotPicker()" class="flex flex-col md:flex-row gap-4">
    {{-- Week Calendar Grid --}}
    <div class="md:w-1/2">
        <div class="flex justify-between mb-4">
            <button wire:click="navigateWeek('prev')">◀ Zurück</button>
            <span>{{ $weekRange }}</span>
            <button wire:click="navigateWeek('next')">Weiter ▶</button>
        </div>

        <div class="grid grid-cols-7 gap-2">
            @foreach($weekDays as $day)
                <button
                    @click="selectDate('{{ $day['date'] }}')"
                    class="p-4 rounded-lg border
                        {{ $day['isSelected'] ? 'bg-primary-500 text-white' : 'bg-white' }}
                        {{ !$day['hasSlots'] ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-100' }}"
                    {{ !$day['hasSlots'] ? 'disabled' : '' }}
                >
                    <div class="text-xs">{{ $day['dayName'] }}</div>
                    <div class="text-lg font-bold">{{ $day['dayNumber'] }}</div>
                    @if($day['hasSlots'])
                        <div class="text-xs text-gray-600">
                            {{ count($timeSlots[$day['date']] ?? []) }} Slots
                        </div>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Time Slot Panel --}}
    <div class="md:w-1/2">
        <h3 class="font-semibold mb-2">
            {{ Carbon\Carbon::parse($selectedDate)->locale('de')->isoFormat('dddd, D. MMMM') }}
        </h3>

        <div class="space-y-2 max-h-96 overflow-y-auto">
            @forelse($timeSlots[$selectedDate] ?? [] as $slot)
                <button
                    @click="selectSlot('{{ $slot['time'] }}')"
                    class="w-full p-3 rounded-lg border text-left
                        {{ $selectedSlot === $slot['time'] ? 'bg-success-500 text-white' : 'bg-white hover:bg-gray-50' }}"
                >
                    {{ Carbon\Carbon::parse($slot['time'])->format('H:i') }} Uhr
                </button>
            @empty
                <p class="text-gray-500">⚠️ Keine freien Slots verfügbar</p>
            @endforelse
        </div>
    </div>
</div>

<script>
function slotPicker() {
    return {
        selectedDate: @entangle('selectedDate'),
        selectedSlot: @entangle('selectedSlot'),

        selectDate(date) {
            $wire.selectDate(date);
        },

        selectSlot(slotTime) {
            $wire.selectSlot(slotTime);
        }
    }
}
</script>
```

---

## 🚨 Risiken & Mitigations

| Risiko | Wahrscheinlichkeit | Impact | Mitigation |
|--------|-------------------|--------|------------|
| Cal.com API Timeout | Mittel | Hoch | Redis-Cache + Fallback-UI |
| Slot-Konflikte (Race Condition) | Niedrig | Hoch | Reservation Pattern |
| Performance auf Mobile | Niedrig | Mittel | Virtual Scrolling + Lazy Loading |
| Browser-Kompatibilität | Niedrig | Mittel | Polyfills + Testing |
| Redis-Unavailability | Niedrig | Hoch | Fallback zu File-Cache |

---

## ✅ Success Criteria

### Pre-Launch
- ✅ Lighthouse Performance Score >90
- ✅ WCAG 2.1 AA compliant
- ✅ Mobile responsive (<768px)
- ✅ Slot-Auswahl <5 Sekunden (User-Action)
- ✅ Alle Edge-Cases getestet

### Post-Launch (Metriken)
- 📊 **Time-to-Book**: Von 45-60s auf <20s (3× schneller)
- 📊 **Booking-Fehlerrate**: <1% (aktuell: unbekannt)
- 📊 **User-Satisfaction**: >8/10 (User-Feedback)
- 📊 **Zero Double-Bookings**: Durch Reservation Pattern

---

## 💰 Kosten-Nutzen-Analyse

### Aufwand
- **Development**: 10-12 Arbeitstage (bei 1 Developer)
- **Testing**: 2-3 Tage
- **Deployment**: 0.5 Tage
- **Gesamt**: ~2-3 Wochen

### Nutzen
- ✅ **3× schnellere Terminbuchung** (45s → 15s)
- ✅ **Bessere UX** für Mitarbeiter (Kunde wartet weniger)
- ✅ **Professionelleres System** (wie Calendly)
- ✅ **Wartbarer Code** (Services, Tests, Dokumentation)
- ✅ **Wiederverwendbar** (auch für andere Features)

### ROI
```
Zeitersparnis pro Buchung: 30 Sekunden
Buchungen pro Tag: ~10
Ersparnis pro Tag: 5 Minuten = 1% mehr Kapazität
Kosten: 2 Wochen Development
Break-Even: Nach ~3 Monaten bei 1 FTE
```

---

## 🎓 Lessons Learned (aus Research)

### Top 10 Best Practices (Calendly/Cal.com)
1. **Unified View**: Monat + Tag gleichzeitig = weniger Klicks
2. **Performance**: Redis-Cache kann 90% Improvement bringen (20s → 2s)
3. **Reservation Pattern**: 10-Min Timeout verhindert Double-Bookings
4. **Optimistic Locking**: Besser als Pessimistic für Booking-Systeme
5. **Cache Aggressiv**: Event-Based Invalidation
6. **Mobile-First**: Vertical Stack statt Horizontal Split
7. **Custom Components**: Keine FullCalendar-Library (zu überladen)
8. **Error Recovery**: Immer alternative Slots vorschlagen
9. **Appointment Buffers**: 5-15 Min zwischen Terminen
10. **Simplicity Wins**: Weniger Klicks = bessere UX

---

## 📚 Technologie-Stack

### Backend
```yaml
Framework: Laravel 10.x
Livewire: 3.x (für Reactive Components)
Cache: Redis (statt File-Cache)
Database: MySQL/PostgreSQL mit Indexes
Events: Laravel Events (AppointmentBooked, etc.)
```

### Frontend
```yaml
UI-Framework: Alpine.js 3.x (bereits vorhanden)
Styling: Tailwind CSS 3.x
Calendar: Custom Component (kein FullCalendar)
State-Management: Livewire Wire (x-data + @entangle)
```

### Performance
```yaml
Caching: Redis (Server) + LocalStorage (Client)
Virtual-Scrolling: Alpine.js Custom
Compression: Gzip/Brotli (Nginx/Apache)
Prefetching: Optional (Nice-to-Have)
```

---

## 🚀 Next Steps - Quick Start Guide

### Sofort starten (Quick Win)
```bash
# 1. Redis-Cache aktivieren (5 Min)
sed -i 's/CACHE_STORE=file/CACHE_STORE=redis/' .env
php artisan cache:clear
redis-cli PING  # Test

# 2. Database-Indexes erstellen (10 Min)
php artisan make:migration add_appointment_week_indexes
# ... Index-Code einfügen
php artisan migrate

# 3. Service-Skeleton erstellen (30 Min)
mkdir -p app/Services
touch app/Services/WeeklySlotService.php
touch app/Services/SlotGeneratorService.php
touch app/Services/SlotCacheService.php
```

### Phase 1 starten (Tag 1)
```bash
# Backend-Services implementieren
# Siehe: "Phase 1 Tag 1" oben für Details

# Parallel: UI-Mockup erstellen
# Figma/Sketch: Design nach oben gezeigtem Layout
```

---

## 📄 Deliverables

### Dokumente (bereits erstellt)
1. ✅ **Frontend-Architektur**: Component-Design, UI-Patterns, Performance
2. ✅ **Backend-Architektur**: API-Design, Caching, Database-Optimierung
3. ✅ **Performance-Plan**: Optimierungsstrategien, Benchmarks
4. ✅ **Research-Report**: Best Practices von Calendly/Cal.com
5. ✅ **Master-Plan**: Dieses Dokument (Roadmap + Code)

### Code (zu erstellen)
1. ⏳ Livewire Component (`AppointmentSlotPicker`)
2. ⏳ Backend Services (Slot-Generation, Caching)
3. ⏳ Blade Templates (Calendar UI)
4. ⏳ Database Migrations (Indexes)
5. ⏳ Tests (Unit + Integration)

---

## 🤝 User-Freigabe benötigt

### Entscheidungen
1. **Phase 1 starten?** (MVP in 4-5 Tagen)
2. **Phase 2 optional?** (Reservation Pattern + Real-Time Updates)
3. **Design-Freigabe?** (Layout oben OK oder Anpassungen?)
4. **Priorität?** (Sofort starten oder später?)

### Fragen
1. Soll Kalender **Sonntag** zeigen (oder nur Mo-Sa)?
2. **Arbeitszeiten** fix 09:00-17:00 oder konfigurierbar?
3. **Slot-Interval**: 15 Min OK oder 30 Min?
4. **Mobile-Nutzung**: Wie hoch ist Priorität? (Admin nutzen meist Desktop)

---

**Ende Master-Plan**

**Status**: ⏳ Wartet auf User-Freigabe
**Nächster Schritt**: Phase 1 Tag 1 starten (Backend-Services)
**Geschätzter Erfolg**: 🟢 Hoch (basierend auf Research + Agent-Analysen)
