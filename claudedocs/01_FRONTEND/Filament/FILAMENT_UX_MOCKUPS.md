# Filament Customer History - UX Mockups & Design Patterns

**Visual design reference for implementing customer timeline views**

---

## 1. Customer Detail Page Layout

```
╔═════════════════════════════════════════════════════════════════════╗
║ 👤 Max Mustermann                                    [Edit] [Delete] ║
╠═════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║ 📊 Customer Overview Widget                                          ║
║ ┌───────────────────────────────────────────────────────────────┐   ║
║ │ 💎 VIP Customer  │  📅 15 Termine  │  💰 1.250,00 €           │   ║
║ └───────────────────────────────────────────────────────────────┘   ║
║                                                                       ║
║ 🕐 Kundenhistorie                                    [Collapse ▲]    ║
║ ┌───────────────────────────────────────────────────────────────┐   ║
║ │                                                               │   ║
║ │  ● 10.10.2025 14:30   📞  ANRUF (3m 45s)                     │   ║
║ │                           Ergebnis: Termin vereinbart         │   ║
║ │                           ↳ 🔗 Termin erstellt: 15.10 10:00  │   ║
║ │                                                               │   ║
║ │  ● 08.10.2025 09:15   📅  TERMIN VERSCHOBEN 🔄               │   ║
║ │                           Massage • Anna Schmidt              │   ║
║ │                           Von: 10.10 → Nach: 15.10            │   ║
║ │                           🤖 KI • 📞 Via Anruf                │   ║
║ │                                                               │   ║
║ │  ● 05.10.2025 16:20   📞  ANRUF (1m 12s)                     │   ║
║ │                           Ergebnis: Termin verschieben        │   ║
║ │                           ↳ 🔗 Termin #123 verschoben        │   ║
║ │                                                               │   ║
║ │  ● 01.10.2025 11:00   📅  TERMIN ABGESCHLOSSEN ✅            │   ║
║ │                           Massage (60 Min) • Anna Schmidt     │   ║
║ │                           10:00 - 11:00 • 80,00 €             │   ║
║ │                                                               │   ║
║ │  ● 25.09.2025 13:45   📞  ANRUF (5m 30s)                     │   ║
║ │                           Ergebnis: Termin vereinbart         │   ║
║ │                           ↳ 🔗 Termin erstellt: 01.10 11:00  │   ║
║ │                                                               │   ║
║ │                        [Mehr laden...]                        │   ║
║ └───────────────────────────────────────────────────────────────┘   ║
║                                                                       ║
║ 📅 Termine                                                            ║
║ ┌───────────────────────────────────────────────────────────────┐   ║
║ │ Datum      │ Service  │ Mitarbeiter │ Status │ Änderungen     │   ║
║ ├───────────────────────────────────────────────────────────────┤   ║
║ │ 15.10 10:00│ Massage  │ Anna S.     │ ✓ Best │ 📞 🔄 Geändert │   ║
║ │ 01.10 11:00│ Massage  │ Anna S.     │ ✅ Abg │ 📞 Via Call    │   ║
║ │ 15.09 14:00│ Behandlg │ Maria K.    │ ✅ Abg │ —              │   ║
║ └───────────────────────────────────────────────────────────────┘   ║
║                                                                       ║
║ 📞 Anrufe                                                             ║
║ ┌───────────────────────────────────────────────────────────────┐   ║
║ │ Zeit        │ Dauer │ Richtung    │ Ergebnis      │ Termine    │   ║
║ ├───────────────────────────────────────────────────────────────┤   ║
║ │ 10.10 14:30│ 3m 45s│ ← Eingehend │ 📅 Vereinbart │ ✅ 1 Termin│   ║
║ │ 05.10 16:20│ 1m 12s│ ← Eingehend │ 🔄 Verschoben │ ✅ 1 Termin│   ║
║ │ 25.09 13:45│ 5m 30s│ ← Eingehend │ 📅 Vereinbart │ ✅ 1 Termin│   ║
║ └───────────────────────────────────────────────────────────────┘   ║
╚═══════════════════════════════════════════════════════════════════════╝
```

---

## 2. Timeline Event Types - Visual Patterns

### Call Event (Successful)
```
┌─────────────────────────────────────────────────────────────┐
│ ● 10.10.2025 14:30   📞  ANRUF (3m 45s)                     │
│                           Ergebnis: Termin vereinbart ✅     │
│                           ↳ 🔗 Termin erstellt: 15.10 10:00 │
└─────────────────────────────────────────────────────────────┘
   Blue border (border-l-4 border-blue-400)
```

### Call Event (No Action)
```
┌─────────────────────────────────────────────────────────────┐
│ ● 03.10.2025 09:15   📞  ANRUF (45s)                        │
│                           Ergebnis: Keine Aktion            │
└─────────────────────────────────────────────────────────────┘
   Gray border (border-l-4 border-gray-300)
```

### Appointment Created
```
┌─────────────────────────────────────────────────────────────┐
│ ● 25.09.2025 13:45   📅  TERMIN ERSTELLT                    │
│                           Massage (60 Min) • Anna Schmidt    │
│                           01.10.2025 10:00 - 11:00           │
│                           🤖 KI-Assistent • 📞 Via Anruf     │
└─────────────────────────────────────────────────────────────┘
   Green border (border-l-4 border-green-500)
```

### Appointment Rescheduled
```
┌─────────────────────────────────────────────────────────────┐
│ ● 08.10.2025 09:15   📅  TERMIN VERSCHOBEN 🔄               │
│                           Massage • Anna Schmidt             │
│                           Von: 10.10.2025 10:00              │
│                           Nach: 15.10.2025 10:00             │
│                           Geändert via: AI Assistant         │
│                           📞 Via Anruf                       │
└─────────────────────────────────────────────────────────────┘
   Yellow border (border-l-4 border-yellow-500)
```

### Appointment Cancelled
```
┌─────────────────────────────────────────────────────────────┐
│ ● 12.09.2025 16:30   📅  TERMIN STORNIERT ❌                │
│                           Behandlung • Maria Klein           │
│                           Geplant: 15.09.2025 14:00          │
│                           Grund: Kundenwunsch                │
└─────────────────────────────────────────────────────────────┘
   Red border (border-l-4 border-red-500)
```

### Appointment Completed
```
┌─────────────────────────────────────────────────────────────┐
│ ● 01.10.2025 11:00   📅  TERMIN ABGESCHLOSSEN ✅            │
│                           Massage (60 Min) • Anna Schmidt    │
│                           10:00 - 11:00 • 80,00 €            │
│                           🤖 KI-Assistent                    │
└─────────────────────────────────────────────────────────────┘
   Green border (border-l-4 border-green-500)
```

---

## 3. Appointment Detail Page - Enhanced Infolist

```
╔═══════════════════════════════════════════════════════════════════════╗
║ Termin #123                                          [Edit] [Delete]  ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                         ║
║ 📝 Terminübersicht                                   [Collapse ▼]      ║
║ ┌─────────────────────────────────────────────────────────────────┐   ║
║ │ #123            │ ✓ Bestätigt           │ Einzeltermin          │   ║
║ │                                                                 │   ║
║ │ Max Mustermann  │ Unternehmen: Test GmbH                       │   ║
║ └─────────────────────────────────────────────────────────────────┘   ║
║                                                                         ║
║ 📝 Buchungsdetails                                   [Collapse ▼]      ║
║ ┌─────────────────────────────────────────────────────────────────┐   ║
║ │ Buchungsquelle          │ Buchungstyp      │ Erstellt am       │   ║
║ │ 🤖 KI-Assistent         │ Einzeltermin     │ 25.09.25 13:45    │   ║
║ │                                                                 │   ║
║ │ 📞 Erstellt durch Anruf vom 25.09.2025 13:45                   │   ║
║ │    [Link zum Anruf →]                                          │   ║
║ │                                                                 │   ║
║ │ Lebenszyklus            │ Letzte Änderung                      │   ║
║ │ 🔄 Geändert             │ 08.10.25 09:15 (vor 2 Tagen)        │   ║
║ └─────────────────────────────────────────────────────────────────┘   ║
║                                                                         ║
║ 🔄 Änderungshistorie                                 [Collapse ▼]      ║
║ ┌─────────────────────────────────────────────────────────────────┐   ║
║ │                                                                 │   ║
║ │ ✅ Termin bestätigt                                            │   ║
║ │    10.10.2025 15:00                                            │   ║
║ │    Bestätigt via: SMS                                          │   ║
║ │                                                                 │   ║
║ │ 🔄 Termin verschoben                                           │   ║
║ │    08.10.2025 09:15                                            │   ║
║ │    Von: 10.10.2025 10:00                                       │   ║
║ │    Nach: 15.10.2025 10:00                                      │   ║
║ │    Geändert via: AI Assistant                                  │   ║
║ │    Grund: Kundenwunsch                                         │   ║
║ │                                                                 │   ║
║ │ ✅ Termin bestätigt                                            │   ║
║ │    05.10.2025 09:15                                            │   ║
║ │    Bestätigt via: SMS                                          │   ║
║ │                                                                 │   ║
║ │ 📅 Termin erstellt                                             │   ║
║ │    25.09.2025 13:45                                            │   ║
║ │    Erstellt via: Telefon-KI                                    │   ║
║ │    Call ID: #retell_abc123                                     │   ║
║ │    Buchungsquelle: AI Assistant                                │   ║
║ │                                                                 │   ║
║ └─────────────────────────────────────────────────────────────────┘   ║
║                                                                         ║
║ 📅 Teilnehmer                                        [Collapse ▲]      ║
║ ┌─────────────────────────────────────────────────────────────────┐   ║
║ │ Kunde: Max Mustermann                                          │   ║
║ │ Mitarbeiter: Anna Schmidt                                      │   ║
║ │ Service: Massage (60 Min)                                      │   ║
║ │ Filiale: Hauptfiliale • Unternehmen: Test GmbH                │   ║
║ └─────────────────────────────────────────────────────────────────┘   ║
╚═══════════════════════════════════════════════════════════════════════╝
```

---

## 4. Call Detail View - Appointment Impact

```
╔═══════════════════════════════════════════════════════════════════════╗
║ Anruf #retell_abc123                                 [Edit] [Delete]  ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                         ║
║ 📞 Anrufübersicht                                                      ║
║ ┌─────────────────────────────────────────────────────────────────┐   ║
║ │ retell_abc123   │ ← Eingehend      │ ✅ Beantwortet             │   ║
║ │                                                                 │   ║
║ │ 25.09.2025 13:45 • Dauer: 5m 30s                              │   ║
║ └─────────────────────────────────────────────────────────────────┘   ║
║                                                                         ║
║ 🎯 Anrufergebnis & Aktionen                                           ║
║ ┌─────────────────────────────────────────────────────────────────┐   ║
║ │ Ergebnis: 📅 Termin vereinbart                                 │   ║
║ │ Termin gebucht: ✅ Ja                                          │   ║
║ └─────────────────────────────────────────────────────────────────┘   ║
║                                                                         ║
║ 🔗 Termine aus diesem Anruf                          [Collapse ▼]      ║
║ ┌─────────────────────────────────────────────────────────────────┐   ║
║ │                                                                 │   ║
║ │ ┌─────────────────────────────────────────────────────────┐   │   ║
║ │ │ Massage                                    ✓ Bestätigt  │   │   ║
║ │ │ 01.10.2025 10:00 - 11:00                               │   │   ║
║ │ │ Mitarbeiter: Anna Schmidt                              │   │   ║
║ │ │                                       [Details anzeigen →] │   ║
║ │ └─────────────────────────────────────────────────────────┘   │   ║
║ │                                                                 │   ║
║ └─────────────────────────────────────────────────────────────────┘   ║
║                                                                         ║
║ 👤 Teilnehmer                                                          ║
║ ┌─────────────────────────────────────────────────────────────────┐   ║
║ │ Kunde: Max Mustermann                                          │   ║
║ │ Mitarbeiter: —                                                 │   ║
║ └─────────────────────────────────────────────────────────────────┘   ║
╚═══════════════════════════════════════════════════════════════════════╝
```

---

## 5. Appointments Table - Lifecycle Indicators

```
╔═══════════════════════════════════════════════════════════════════════╗
║ Termine für Max Mustermann                          [+ Neuer Termin]  ║
╠═══════════════════════════════════════════════════════════════════════╣
║ Filters: [Status ▼] [Anstehend ☑] [Vergangen ☐]                      ║
╠═══════════════════════════════════════════════════════════════════════╣
║ Termin         │Service │Mitarbeiter│Status      │Änderungen         ║
╠═══════════════════════════════════════════════════════════════════════╣
║ 📅 15.10 10:00 │Massage │Anna S.    │✓ Bestätigt │📞 Via Call        ║
║ 30 Min.        │        │           │            │🔄 Geändert        ║
║                │        │           │            │Geändert: vor 2d   ║
║                │        │           │            │Erstellt: 25.09    ║
║────────────────┼────────┼───────────┼────────────┼───────────────────║
║ 📅 01.10 11:00 │Massage │Anna S.    │✅ Abgeschl │📞 Via Call        ║
║ 60 Min.        │        │           │            │—                  ║
║                │        │           │            │                   ║
║────────────────┼────────┼───────────┼────────────┼───────────────────║
║ 📅 15.09 14:00 │Behandlg│Maria K.   │✅ Abgeschl │—                  ║
║ 45 Min.        │        │           │            │                   ║
║                │        │           │            │                   ║
╚═══════════════════════════════════════════════════════════════════════╝

Legend:
📞 Via Call = Created from phone call
🔄 Geändert = Modified after creation
✅ Abgeschl = Completed successfully
✓ Bestätigt = Confirmed
```

---

## 6. Mobile Responsive Design

### Desktop (>768px)
```
┌────────────────────────────────────────────────┐
│ Timeline Event                                 │
├────────────────────────────────────────────────┤
│ [Date] [Time] [Icon] [Event Details...]       │
│   10.10    14:30  📞   ANRUF (3m 45s)         │
│                        Ergebnis: ...           │
└────────────────────────────────────────────────┘
```

### Mobile (<768px)
```
┌──────────────────────────┐
│ Timeline Event           │
├──────────────────────────┤
│ 10.10.2025               │
│ 14:30                    │
│                          │
│ 📞 ANRUF (3m 45s)        │
│ Ergebnis: Termin...      │
│ ↳ Termin erstellt...     │
└──────────────────────────┘
```

### Responsive Classes
```html
<!-- Desktop: 3-column grid -->
<div class="grid grid-cols-1 md:grid-cols-[auto_auto_1fr] gap-2 md:gap-4">

<!-- Mobile: Stack vertically -->
<div class="flex flex-col md:flex-row gap-2">

<!-- Hide on mobile -->
<div class="hidden md:block">

<!-- Truncate text on mobile -->
<div class="truncate md:overflow-visible">
```

---

## 7. Color Palette & Badge Styles

### Status Badges
```
✅ Abgeschlossen   [green bg-green-100 text-green-700]
✓ Bestätigt        [blue bg-blue-100 text-blue-700]
⏳ Ausstehend      [yellow bg-yellow-100 text-yellow-700]
❌ Storniert       [red bg-red-100 text-red-700]
👻 Nicht erschienen [gray bg-gray-100 text-gray-700]
```

### Source Badges
```
📞 Telefon         [blue bg-blue-50 text-blue-600]
💻 Online          [green bg-green-50 text-green-600]
🤖 KI-Assistent    [purple bg-purple-50 text-purple-600]
📱 App             [indigo bg-indigo-50 text-indigo-600]
🚶 Walk-In         [gray bg-gray-50 text-gray-600]
```

### Lifecycle Badges
```
📞 Via Call        [blue bg-blue-100 text-blue-700]
🔄 Geändert        [yellow bg-yellow-100 text-yellow-700]
—  Unverändert     [gray bg-gray-50 text-gray-500]
```

---

## 8. Interactive States

### Hover Effects
```css
/* Timeline events */
.timeline-event:hover {
    background-color: bg-gray-50 dark:bg-gray-800;
    transition: background-color 150ms;
}

/* Links */
.appointment-link:hover {
    color: blue-800;
    text-decoration: underline;
}

/* Badges */
.badge:hover {
    opacity: 0.9;
}
```

### Click/Expand Behavior
```
┌─────────────────────────────────────────────────────────────┐
│ ● 10.10.2025 14:30   📞  ANRUF (3m 45s)         [▼ Details] │
└─────────────────────────────────────────────────────────────┘

Click [▼ Details] →

┌─────────────────────────────────────────────────────────────┐
│ ● 10.10.2025 14:30   📞  ANRUF (3m 45s)         [▲ Details] │
├─────────────────────────────────────────────────────────────┤
│ Von: +49 123 456789                                         │
│ An: +49 987 654321                                          │
│ Dauer: 5m 30s                                               │
│ Transkript: [Link]                                          │
│ Aufnahme: [Play]                                            │
└─────────────────────────────────────────────────────────────┘
```

---

## 9. Empty States

### No Timeline Events
```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│                        🕐                                   │
│                                                             │
│              Keine Historie vorhanden                       │
│                                                             │
│    Es wurden noch keine Anrufe oder Termine erfasst.       │
│                                                             │
│                  [+ Neuen Termin erstellen]                 │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### No Appointments from Call
```
┌─────────────────────────────────────────────────────────────┐
│ 🔗 Termine aus diesem Anruf                    [Collapse ▲] │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Dieser Anruf hat keine Termine erzeugt.                   │
│                                                             │
│  Ergebnis: Keine Aktion                                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 10. Loading States

### Timeline Skeleton
```
┌─────────────────────────────────────────────────────────────┐
│ 🕐 Kundenhistorie                              [Collapse ▲] │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ● ████████  ████  ████████████████                        │
│              ████████████                                   │
│                                                             │
│  ● ████████  ████  ████████████████                        │
│              ████████████                                   │
│                                                             │
│  ● ████████  ████  ████████████████                        │
│              ████████████                                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Filament Loading Indicator
```php
// In widget class
protected function getViewData(): array
{
    return [
        'timeline' => $this->buildTimeline(),
        'isLoading' => false,
    ];
}
```

```blade
{{-- In blade view --}}
@if($isLoading)
    <div class="flex justify-center py-8">
        <x-filament::loading-indicator class="h-8 w-8" />
    </div>
@else
    {{-- Timeline content --}}
@endif
```

---

## 11. Accessibility Considerations

### Screen Reader Support
```html
<!-- Semantic HTML -->
<nav aria-label="Customer timeline">
    <ol role="list" aria-label="Timeline events">
        <li role="listitem">
            <article aria-labelledby="event-1-title">
                <h3 id="event-1-title">
                    <span class="sr-only">Call on</span>
                    10.10.2025 14:30
                </h3>
                <!-- Event details -->
            </article>
        </li>
    </ol>
</nav>

<!-- Icon alternatives -->
<span aria-label="Phone call">📞</span>
<span class="sr-only">Appointment completed</span>✅
```

### Keyboard Navigation
```
Tab order:
1. Timeline section collapse button
2. First event link
3. Second event link
...
N. "Load more" button

Focus indicators:
- Blue outline on focused elements
- High contrast for dark mode
```

### Color Contrast
```
Ensure WCAG AA compliance:
- Text: 4.5:1 contrast ratio
- Large text: 3:1 contrast ratio
- UI components: 3:1 contrast ratio

Test with:
- Chrome DevTools Lighthouse
- axe DevTools extension
```

---

## 12. Performance Optimization

### Lazy Loading Timeline
```php
// Load only first 20 events initially
protected function buildTimeline(): Collection
{
    return $this->getAllTimelineEvents()
        ->sortByDesc('timestamp')
        ->take(20); // Initial load
}

// Load more via AJAX
public function loadMoreEvents()
{
    $this->eventsLoaded += 20;
    $this->dispatch('events-loaded');
}
```

### Eager Loading Strategy
```php
// Single query to load all related data
$customer->load([
    'calls' => fn($q) => $q
        ->with('appointments')
        ->latest()
        ->limit(50),
    'appointments' => fn($q) => $q
        ->with(['service', 'staff', 'call'])
        ->latest()
        ->limit(50),
]);
```

### Caching
```php
// Cache timeline for 5 minutes
protected function buildTimeline(): Collection
{
    return Cache::remember(
        "customer.{$this->record->id}.timeline",
        now()->addMinutes(5),
        fn() => $this->fetchTimelineData()
    );
}

// Clear cache on updates
protected static function booted()
{
    static::updated(function ($model) {
        Cache::forget("customer.{$model->customer_id}.timeline");
    });
}
```

---

## Summary Checklist

### Visual Design
- [ ] Consistent color coding across all views
- [ ] Clear iconography for event types
- [ ] Proper spacing and alignment
- [ ] Dark mode support

### Functionality
- [ ] Chronological timeline display
- [ ] Clickable links to related records
- [ ] Expandable details sections
- [ ] Filter and search capabilities

### User Experience
- [ ] Mobile responsive design
- [ ] Clear empty states
- [ ] Loading indicators
- [ ] Helpful error messages

### Performance
- [ ] Eager loading relationships
- [ ] Limited initial load (20-50 items)
- [ ] Lazy loading for more items
- [ ] Caching where appropriate

### Accessibility
- [ ] Semantic HTML structure
- [ ] Screen reader support
- [ ] Keyboard navigation
- [ ] WCAG AA compliance

---

This design provides a comprehensive, user-friendly interface for viewing customer appointment history with excellent UX, performance, and accessibility.
