# Wochenkalender - Umfassender Test-Plan & UI/UX Audit

**Datum**: 2025-10-14
**Status**: 📋 **TEST-PLAN** - Bereit für manuelle Ausführung
**Ziel**: Top-Notch UI/UX + Bug-freie Funktion

---

## 🎯 Test-Strategie

### Test-Kategorien:
1. **Funktionale Tests** (Feature funktioniert)
2. **UI/UX Tests** (Sieht gut aus, intuitiv)
3. **Performance Tests** (Schnell, keine Verzögerung)
4. **Edge Cases** (Fehlerbehandlung)
5. **Responsive Tests** (Mobile + Desktop)
6. **Accessibility** (WCAG 2.1 AA)

---

## 📋 TEST 1: Appointment Create - Week Picker UI

### Vorbereitung:
1. Login: `https://api.askproai.de/admin`
2. Navigate: `/admin/appointments/create`

### Test-Schritte:

#### Step 1.1: Initial State (KEIN Service gewählt)
```
✓ Expected Behavior:
- Week Picker ist NICHT sichtbar
- Warning-Box erscheint: "⚠️ Bitte wählen Sie zuerst einen Service aus"
- Fallback DateTimePicker ist disabled mit Hinweis

📸 SCREENSHOT 1: "appointment-create-no-service.png"
Zu prüfen:
- [ ] Warning-Box hat korrekte Farbe (warning-50 bg)
- [ ] Text ist gut lesbar
- [ ] Icon (⚠️) ist sichtbar
```

#### Step 1.2: Service Selection
```
Actions:
1. Select Company: "Friseur Krückenberg" (oder Test Company)
2. Select Branch: "Hauptfiliale"
3. Select Customer: Beliebiger Kunde
4. Select Service: "Haare schneiden" (oder Service mit calcom_event_type_id)

✓ Expected Behavior:
- Week Picker erscheint SOFORT nach Service-Auswahl
- 7 Spalten (Mo, Di, Mi, Do, Fr, Sa, So) sind sichtbar
- Current Week ist geladen
- Week Info zeigt: "KW XX: DD.MM.YYYY - DD.MM.YYYY"
- Service Info zeigt: "📅 [Service Name] - Dauer: X Minuten"

📸 SCREENSHOT 2: "appointment-create-week-picker-loaded.png"
Zu prüfen:
- [ ] 7 Spalten gleichmäßig verteilt (Desktop)
- [ ] Header haben korrekte Labels (Mo, Di, Mi...)
- [ ] Datum unter Tag-Label (dd.mm.)
- [ ] Slots sind sichtbar (falls verfügbar)
- [ ] Background colors korrekt (gray-100 für Headers)
```

#### Step 1.3: Week Picker Visual Quality Check
```
📸 SCREENSHOT 3: "week-picker-visual-details.png"
Zoom in auf Week Picker - Zu prüfen:

Layout:
- [ ] Spalten-Abstände gleichmäßig (gap-2)
- [ ] Header zentriert, gut lesbar
- [ ] Borders korrekt (border-gray-200)
- [ ] Rounded corners (rounded-lg)

Slots:
- [ ] Slot-Buttons gut sichtbar
- [ ] Schriftgröße lesbar (text-xs)
- [ ] Hover-Effekt funktioniert (bg-primary-100)
- [ ] Padding ausreichend (px-2 py-1.5)
- [ ] Time-of-day Labels ("🌅 Morgen", "☀️ Mittag") sichtbar aber dezent

Colors (Light Mode):
- [ ] Header: bg-gray-100, text-gray-900
- [ ] Slots: bg-white, text-gray-700
- [ ] Selected Slot: bg-primary-600, text-white
- [ ] Hover: bg-primary-100

Typography:
- [ ] Font weights korrekt (semibold für Header, medium für Slots)
- [ ] Line heights korrekt (keine überlappenden Texte)
```

---

## 📋 TEST 2: Week Navigation

### Test-Schritte:

#### Step 2.1: Next Week Navigation
```
Action: Click "Nächste Woche ▶"

✓ Expected Behavior:
- Loading Overlay erscheint (< 1 Sekunde)
- Week Info updated: "KW XX+1: DD.MM - DD.MM"
- Neue Slots werden geladen
- Button ist während Load disabled
- Smooth Transition (kein Flackern)

📸 SCREENSHOT 4: "week-navigation-next-week.png"
Zu prüfen:
- [ ] Week Number incrementiert (+1)
- [ ] Date range updated
- [ ] Slots sind neu (nicht cached von vorheriger Woche)
- [ ] "Aktuelle Woche" Badge ist WEG (falls nicht mehr current)
```

#### Step 2.2: Previous Week Navigation
```
Action: Click "◀ Vorherige Woche"

✓ Expected Behavior:
- Zurück zur ursprünglichen Woche
- Week Info zeigt wieder Original Week
- "Aktuelle Woche" Badge erscheint wieder

📸 SCREENSHOT 5: "week-navigation-previous-week.png"
```

#### Step 2.3: Jump to Current Week
```
Setup: Navigate 2-3 Wochen in die Zukunft
Action: Click "📅 Zur aktuellen Woche springen"

✓ Expected Behavior:
- Sofort zurück zur aktuellen Woche
- Button verschwindet (nur sichtbar wenn nicht in current week)

📸 SCREENSHOT 6: "jump-to-current-week.png"
```

---

## 📋 TEST 3: Slot Selection - Visual Feedback

### Test-Schritte:

#### Step 3.1: Slot Hover
```
Action: Hover über verschiedene Slots (OHNE zu klicken)

✓ Expected Behavior:
- Slot Background ändert sich: white → primary-100
- Border ändert sich: gray-200 → primary-400
- Slight scale effect (scale-105)
- Cursor: pointer

📸 SCREENSHOT 7: "slot-hover-effect.png"
Zu prüfen:
- [ ] Hover ist smooth (transition-all duration-150)
- [ ] Kein Layout-Shift (scale within bounds)
- [ ] Farbe ist subtil (nicht zu auffällig)
```

#### Step 3.2: Slot Selection
```
Action: Click auf Slot "Montag, 09:00"

✓ Expected Behavior:
- Slot Background: primary-600 (kräftiges Blau)
- Text Color: white
- Font Weight: bold
- Scale: 105%
- Success Notification: "Slot ausgewählt: 14.10.2025 09:00 Uhr"
- Badge erscheint: "✓ Ausgewählter Termin: DD.MM.YYYY HH:MM Uhr"
- Hidden Field "starts_at" populated

📸 SCREENSHOT 8: "slot-selected-visual-feedback.png"
Zu prüfen:
- [ ] Selected Slot sticht hervor (deutlich erkennbar)
- [ ] Kontrast ausreichend (white text on primary-600)
- [ ] Badge hat Success-Farbe (success-50 bg)
- [ ] Notification erscheint oben rechts
```

#### Step 3.3: Re-Selection (anderer Slot)
```
Action: Click auf anderen Slot "Dienstag, 10:00"

✓ Expected Behavior:
- Vorheriger Slot: zurück zu normal (white bg)
- Neuer Slot: primary-600 bg
- Badge updated mit neuer Zeit
- Neue Notification

📸 SCREENSHOT 9: "slot-re-selection.png"
Zu prüfen:
- [ ] Nur EIN Slot selected (keine Multi-Selection)
- [ ] Transition smooth
```

---

## 📋 TEST 4: Appointment Erstellen (End-to-End)

### Test-Schritte:

#### Step 4.1: Complete Form Fill
```
Actions:
1. Company, Branch, Customer, Service auswählen
2. Slot auswählen: "Donnerstag, 14:00"
3. Optional: Notes hinzufügen
4. Click "Erstellen"

✓ Expected Behavior:
- Form validiert OK
- Appointment wird erstellt
- Redirect zu Appointment Detail Page
- Success Notification: "Termin erstellt"
- Appointment hat korrekte starts_at und ends_at

📸 SCREENSHOT 10: "appointment-created-success.png"

Zu verifizieren in DB:
SELECT id, service_id, starts_at, ends_at, status
FROM appointments
WHERE customer_id = 'CUSTOMER_ID'
ORDER BY created_at DESC LIMIT 1;

Expected:
- starts_at: "2025-10-17 14:00:00" (Donnerstag 14:00, Europe/Berlin)
- ends_at: starts_at + service.duration_minutes
- status: "confirmed" oder "pending"
```

---

## 📋 TEST 5: Reschedule Action - Wide Modal

### Test-Schritte:

#### Step 5.1: Open Reschedule Modal
```
Setup: Appointment bereits vorhanden
Actions:
1. Navigate: /admin/appointments
2. Find appointment in table
3. Click 3-dots menu (Action Column)
4. Click "Verschieben"

✓ Expected Behavior:
- Wide Modal öffnet (7xl width)
- Title: "Termin verschieben - Wochenansicht"
- Service Info displayed: "[Service Name] (XX min)"
- Week Picker loaded mit current appointment's slot highlighted
- Buttons: "Verschieben" (primary), "Abbrechen" (secondary)

📸 SCREENSHOT 11: "reschedule-modal-open.png"
Zu prüfen:
- [ ] Modal ist breit genug (7xl = ~80rem)
- [ ] Week Picker passt vollständig rein (kein horizontal scroll)
- [ ] Current slot ist highlighted (pre-selected)
- [ ] Modal ist zentriert
```

#### Step 5.2: Select New Slot
```
Action:
1. Click "Nächste Woche ▶"
2. Select neuen Slot: "Montag, 09:30"
3. Click "Verschieben"

✓ Expected Behavior:
- Modal schließt
- Appointment ist verschoben
- Success Notification: "Termin verschoben"
- Table row updated mit neuer Zeit
- Cache invalidiert (week_availability für Service)

📸 SCREENSHOT 12: "appointment-rescheduled-success.png"

Zu verifizieren:
- [ ] starts_at updated in DB
- [ ] ends_at recalculated
- [ ] AppointmentRescheduled Event gefired
- [ ] Cache cleared (check logs)
```

---

## 📋 TEST 6: Mobile Responsive View

### Test-Schritte:

#### Step 6.1: Mobile Layout (<768px)
```
Setup: Resize Browser to 375px width (iPhone SE) oder use DevTools Mobile Mode
Action: Navigate to /admin/appointments/create, select service

✓ Expected Behavior:
- Desktop 7-column Grid VERSTECKT
- Mobile Stacked Layout SICHTBAR
- Days sind collapsible (accordion style)
- Click Day Header → Expand/Collapse Slots
- Slot count badge sichtbar: "X Slots"

📸 SCREENSHOT 13: "mobile-week-picker-collapsed.png"
Zu prüfen:
- [ ] Layout ist vertikal gestacked
- [ ] Keine horizontale Scroll-Bar
- [ ] Text ist lesbar (nicht zu klein)
- [ ] Touch targets groß genug (min 44x44px)
```

#### Step 6.2: Mobile Day Expansion
```
Action: Click auf "Montag" Header

✓ Expected Behavior:
- Montag expandiert (Slots werden sichtbar)
- Smooth Animation (x-collapse)
- Chevron Icon rotiert (rotate-180)
- Andere Tage bleiben collapsed

📸 SCREENSHOT 14: "mobile-day-expanded.png"
Zu prüfen:
- [ ] Animation smooth (nicht ruckelig)
- [ ] Slots sind groß genug für Touch (min 44px height)
- [ ] Spacing zwischen Slots (space-y-2)
```

#### Step 6.3: Mobile Slot Selection
```
Action: Click auf Slot "09:00"

✓ Expected Behavior:
- Slot highlighted (primary-600 bg)
- Checkmark Icon erscheint rechts
- Badge oben: "Ausgewählter Termin: ..."
- Form field populated

📸 SCREENSHOT 15: "mobile-slot-selected.png"
Zu prüfen:
- [ ] Selected state deutlich erkennbar
- [ ] Checkmark Icon sichtbar (✓)
- [ ] Keine Layout-Verschiebung
```

---

## 📋 TEST 7: Error States & Edge Cases

### Test-Schritte:

#### Step 7.1: Service ohne Cal.com Event Type ID
```
Setup:
1. Create Service in DB ohne calcom_event_type_id
2. Try to create appointment mit diesem Service

✓ Expected Behavior:
- Week Picker zeigt Error:
  "⚠️ Service '[Name]' has no Cal.com Event Type ID configured"
- Error Banner: danger-50 bg, danger-800 text
- Fallback: DateTimePicker ist enabled

📸 SCREENSHOT 16: "error-no-event-type-id.png"
Zu prüfen:
- [ ] Error message klar und hilfreich
- [ ] User kann trotzdem fortfahren (mit DateTimePicker)
- [ ] Kein JS Error in Console
```

#### Step 7.2: Keine Slots verfügbar (Empty Week)
```
Setup: Service/Week mit null Slots (z.B. Sonntag für Friseur)
Action: Navigate zu dieser Woche

✓ Expected Behavior:
- Week Picker loaded, aber alle Days zeigen: "Keine Slots"
- Empty State Message:
  "Keine verfügbaren Termine in dieser Woche"
  "Für den Service 'X' sind in KW XX keine freien Slots verfügbar"
- Buttons: "Nächste Woche anzeigen ▶", "🔄 Neu laden"

📸 SCREENSHOT 17: "empty-week-no-slots.png"
Zu prüfen:
- [ ] Empty State ist freundlich (warning-50 bg, nicht danger)
- [ ] Icon (📅 oder ⚠️) sichtbar
- [ ] Call-to-Action Buttons vorhanden
- [ ] User nicht "stuck" (kann navigieren)
```

#### Step 7.3: Cal.com API Fehler (Simulate Network Error)
```
Setup:
1. Open DevTools → Network Tab
2. Enable "Offline" mode
3. Try to load Week Picker

✓ Expected Behavior:
- Error Banner erscheint:
  "Cal.com API-Fehler: [Error Message]. Bitte versuchen Sie es später erneut."
- Week Picker zeigt leere Struktur (no slots)
- "🔄 Aktualisieren" Button verfügbar

📸 SCREENSHOT 18: "calcom-api-error.png"
Zu prüfen:
- [ ] Error ist user-friendly (nicht technisch)
- [ ] User kann retry (Aktualisieren-Button)
- [ ] Fallback zu DateTimePicker möglich
- [ ] Kein White Screen of Death
```

---

## 📋 TEST 8: Dark Mode UI

### Test-Schritte:

#### Step 8.1: Toggle Dark Mode
```
Action:
1. Filament Settings → Toggle Dark Mode
2. Reload Week Picker

✓ Expected Behavior:
- All Colors invert korrekt:
  - Background: white → gray-900/gray-800
  - Text: gray-900 → white
  - Borders: gray-200 → gray-700
  - Primary colors bleiben primary (gut sichtbar)

📸 SCREENSHOT 19: "dark-mode-week-picker.png"
Zu prüfen:
- [ ] Kontrast ausreichend (readable text)
- [ ] Keine weißen "Flecken" (missing dark: classes)
- [ ] Selected Slot visible (primary-500 in dark mode)
- [ ] Hover effects sichtbar
```

#### Step 8.2: Dark Mode Slot Selection
```
Action: Select Slot in Dark Mode

📸 SCREENSHOT 20: "dark-mode-slot-selected.png"
Zu prüfen:
- [ ] Selected state sichtbar (contrast OK)
- [ ] Badge readable (success-900/20 bg)
- [ ] Icons visible
```

---

## 📋 TEST 9: Performance & Cache

### Test-Schritte:

#### Step 9.1: Initial Load Time
```
Action:
1. Clear Browser Cache (Ctrl+Shift+Del)
2. Open DevTools → Network Tab → "Disable Cache"
3. Navigate to Appointment Create
4. Select Service (trigger Week Picker load)

✓ Expected Performance:
- Week Picker appears: < 1 second
- Cal.com API call: ~300-500ms (check Network tab)
- Total render time: < 1 second

📸 SCREENSHOT 21: "network-tab-initial-load.png" (DevTools)
Zu prüfen:
- [ ] /slots/available API call: < 500ms
- [ ] Livewire render: < 200ms
- [ ] Total: < 1 second
```

#### Step 9.2: Cache Hit (Second Load)
```
Action:
1. Load Week Picker (same service, same week)
2. Check Network Tab

✓ Expected Performance:
- NO new API call to Cal.com (cache hit)
- Load time: < 100ms (instant)

📸 SCREENSHOT 22: "cache-hit-no-api-call.png"
Zu prüfen:
- [ ] Log entry: "[WeeklyAvailability] Cache hit"
- [ ] Network: NO /slots/available call
```

#### Step 9.3: Cache Invalidation Test
```
Action:
1. Book appointment for Service A
2. Immediately create another appointment for Service A
3. Check if booked slot is gone

✓ Expected Behavior:
- Booked slot NOT available in second appointment
- Cache was invalidated (Log: "[InvalidateWeekCache] Cache cleared")

Zu verifizieren:
- [ ] Log shows cache invalidation
- [ ] Fresh API call was made
- [ ] Booked slot disappeared
```

---

## 🎨 UI/UX AUDIT CHECKLIST

### Visual Design (10 Punkte)

#### Layout & Spacing ✅
- [ ] **Consistent Spacing**: Alle Abstände folgen Tailwind spacing scale (gap-2, p-3, etc.)
- [ ] **Alignment**: Alle Elemente korrekt aligned (center, left, right)
- [ ] **Whitespace**: Ausreichend Breathing Room (nicht zu gedrängt)
- [ ] **Grid Balance**: 7 Spalten gleichmäßig verteilt (Desktop)

#### Typography ✅
- [ ] **Hierarchy**: Clear H1 → H2 → Body Text hierarchy
- [ ] **Font Sizes**: Readable (min 12px, optimal 14-16px)
- [ ] **Font Weights**: Correct emphasis (semibold für Headers, medium für wichtige Elemente)
- [ ] **Line Height**: Keine überlappenden Texte

#### Colors ✅
- [ ] **Contrast Ratio**: WCAG AA compliant (min 4.5:1 für Text)
- [ ] **Color Consistency**: Primary/Success/Warning/Danger colors konsistent
- [ ] **Dark Mode**: Alle colors haben dark: variants
- [ ] **Hover States**: Clear visual feedback

#### Interactive Elements ✅
- [ ] **Button States**: Default, Hover, Active, Disabled alle unterscheidbar
- [ ] **Touch Targets**: Min 44x44px (Mobile)
- [ ] **Cursor**: pointer für clickable, default für non-clickable
- [ ] **Focus States**: Visible focus rings (keyboard navigation)

### User Experience (10 Punkte)

#### Usability ✅
- [ ] **Intuitive Navigation**: User findet Week Navigation ohne Hilfe
- [ ] **Clear Labels**: Alle Buttons/Fields haben descriptive labels
- [ ] **Progress Indication**: Loading states zeigen Progress
- [ ] **Undo/Cancel**: User kann Aktionen abbrechen

#### Feedback ✅
- [ ] **Immediate Feedback**: Actions haben instant visual feedback
- [ ] **Success Messages**: User weiß wenn Aktion erfolgreich
- [ ] **Error Messages**: Errors sind klar und hilfreich (nicht technisch)
- [ ] **Empty States**: Hilfreiche Messages wenn keine Daten

#### Performance ✅
- [ ] **Load Speed**: < 1 Sekunde für Initial Load
- [ ] **Perceived Performance**: Skeleton screens oder Loading indicators
- [ ] **Smooth Animations**: Keine ruckeligen Transitions
- [ ] **No Layout Shifts**: Kein "jumping" content

#### Accessibility ✅
- [ ] **Keyboard Navigation**: Alle Elemente via Tab erreichbar
- [ ] **Screen Reader**: Labels für Icons, alt text für images
- [ ] **Color Blindness**: Info nicht nur via Farbe (auch Icons/Text)
- [ ] **Focus Management**: Focus logisch und sichtbar

### Mobile Experience (5 Punkte)

#### Responsive Design ✅
- [ ] **Breakpoints**: Correct transitions (<768px → stacked)
- [ ] **Touch Targets**: Min 44x44px, ausreichend Spacing
- [ ] **Scrolling**: Natural scroll behavior (kein horizontal scroll)
- [ ] **Text Size**: Readable ohne Zoom (min 16px)
- [ ] **Orientation**: Works in Portrait & Landscape

---

## 🐛 POTENTIAL BUGS - Zu Prüfen

### High Priority (Muss funktionieren)

#### Bug 1: Livewire State Sync
**Problem**: Selected slot wird nicht an Parent Form übermittelt
**Test**: Select Slot → Check if `starts_at` Hidden Field populated
**Expected**: Value = ISO 8601 datetime string
**Fix (if fails)**: Check Alpine.js event wiring in wrapper view

#### Bug 2: Timezone Confusion
**Problem**: Slots zeigen UTC statt Europe/Berlin
**Test**: Slot zeigt "09:00" → Check DB: sollte auch "09:00" sein (nicht "07:00")
**Expected**: Display time = DB time (beide Europe/Berlin)
**Fix (if fails)**: Check `transformToWeekStructure()` timezone conversion

#### Bug 3: Cache Not Invalidated
**Problem**: Nach Booking sind Slots immer noch verfügbar
**Test**: Book slot → Reload Week Picker → Slot should be gone
**Expected**: Fresh API call, booked slot unavailable
**Fix (if fails)**: Check Event Listener registered in EventServiceProvider

#### Bug 4: Modal Width Overflow
**Problem**: Week Picker zu breit für Modal (horizontal scroll)
**Test**: Open Reschedule Modal → Check for horizontal scrollbar
**Expected**: Modal 7xl width contains Week Picker without scroll
**Fix (if fails)**: Adjust column widths oder modal size

### Medium Priority (UX Issue)

#### Bug 5: Empty State Navigation Loop
**Problem**: User stuck in empty week (keine Slots), kann nicht vor/zurück
**Test**: Navigate zu leerer Woche → Try prev/next buttons
**Expected**: Buttons funktionieren, auch wenn keine Slots
**Fix (if fails)**: Check button disabled logic

#### Bug 6: Mobile Day Collapse Issue
**Problem**: Multiple days expandieren gleichzeitig
**Test**: Mobile → Expand Montag → Expand Dienstag → Check if Montag collapsed
**Expected**: Only one day expanded at a time
**Fix (if fails)**: Check Alpine.js `showMobileDay` state logic

#### Bug 7: Dark Mode Color Contrast
**Problem**: Selected slot nicht sichtbar in Dark Mode
**Test**: Toggle Dark Mode → Select Slot → Check contrast
**Expected**: Clear visual difference (primary-500 vs gray-800)
**Fix (if fails)**: Adjust dark: color variants

### Low Priority (Edge Case)

#### Bug 8: Past Week Navigation
**Problem**: User kann zu vergangenen Wochen navigieren
**Test**: Click "◀ Vorherige" mehrmals bis letzte Woche
**Expected**: Either allow (show "Vergangene Woche" badge) OR prevent
**Fix (if decided)**: Add `minDate` check in `previousWeek()` method

#### Bug 9: Service Change without Picker Reset
**Problem**: User wechselt Service, Week Picker zeigt alte Slots
**Test**: Select Service A → Select Slot → Change to Service B
**Expected**: Week Picker reloads with Service B slots
**Fix (if fails)**: Add reactive listener on `service_id` change

---

## 📸 SCREENSHOT LOCATIONS & NAMING

Alle Screenshots sollten gespeichert werden in:
```
tests/puppeteer/screenshots/week-picker/
```

### Naming Convention:
```
01-appointment-create-no-service.png
02-appointment-create-week-picker-loaded.png
03-week-picker-visual-details.png
04-week-navigation-next-week.png
05-week-navigation-previous-week.png
06-jump-to-current-week.png
07-slot-hover-effect.png
08-slot-selected-visual-feedback.png
09-slot-re-selection.png
10-appointment-created-success.png
11-reschedule-modal-open.png
12-appointment-rescheduled-success.png
13-mobile-week-picker-collapsed.png
14-mobile-day-expanded.png
15-mobile-slot-selected.png
16-error-no-event-type-id.png
17-empty-week-no-slots.png
18-calcom-api-error.png
19-dark-mode-week-picker.png
20-dark-mode-slot-selected.png
21-network-tab-initial-load.png
22-cache-hit-no-api-call.png
```

---

## ✅ SUCCESS CRITERIA

### Must-Have (Blocker wenn nicht erfüllt):
- [x] Week Picker appears after Service selection
- [ ] 7 columns visible (Desktop)
- [ ] Slots clickable and selectable
- [ ] Selected slot highlights correctly
- [ ] Appointment can be created successfully
- [ ] Reschedule modal works
- [ ] Mobile responsive (stacked layout)
- [ ] No JavaScript errors in Console
- [ ] No visual bugs (broken layout)

### Should-Have (Important aber nicht Blocker):
- [ ] Load time < 1 second
- [ ] Cache invalidation works
- [ ] Dark mode looks good
- [ ] Empty states helpful
- [ ] Error messages clear
- [ ] Smooth animations

### Nice-to-Have (Future improvements):
- [ ] Prefetching next week (background)
- [ ] Virtual scrolling (>100 slots)
- [ ] Keyboard shortcuts (Arrow keys)
- [ ] Accessibility (Screen reader)

---

## 🔧 BUG FIX PRIORITY

### P0 - Critical (Fix immediately):
- Week Picker nicht sichtbar
- Slots nicht clickable
- State sync broken (Form nicht populated)
- Modal nicht öffnet
- JavaScript errors

### P1 - High (Fix before deploy):
- Cache invalidation broken
- Timezone wrong
- Mobile layout broken
- Dark mode unreadable
- Empty states missing

### P2 - Medium (Fix soon):
- Performance issues (>2s load)
- Animation ruckelig
- Minor layout issues
- Hover states nicht ideal

### P3 - Low (Fix later):
- Color tweaks
- Spacing adjustments
- Icon improvements
- Micro-interactions

---

## 📝 TEST EXECUTION LOG

```
┌─────────────────────────────────────────────────────────┐
│ TEST EXECUTION LOG                                      │
├─────────────────────────────────────────────────────────┤
│ Date: 2025-10-14                                        │
│ Tester: [YOUR NAME]                                     │
│ Environment: Production / Staging                       │
│ Browser: Chrome / Firefox / Safari                      │
│ Resolution: Desktop (1920x1080) / Mobile (375x667)      │
└─────────────────────────────────────────────────────────┘

TEST 1: Appointment Create - Week Picker UI
[ ] Step 1.1: Initial State (no service)      - ✅ PASS / ❌ FAIL
[ ] Step 1.2: Service Selection               - ✅ PASS / ❌ FAIL
[ ] Step 1.3: Visual Quality Check            - ✅ PASS / ❌ FAIL
Notes: ___________________________________________________________

TEST 2: Week Navigation
[ ] Step 2.1: Next Week                       - ✅ PASS / ❌ FAIL
[ ] Step 2.2: Previous Week                   - ✅ PASS / ❌ FAIL
[ ] Step 2.3: Jump to Current                 - ✅ PASS / ❌ FAIL
Notes: ___________________________________________________________

TEST 3: Slot Selection
[ ] Step 3.1: Hover Effect                    - ✅ PASS / ❌ FAIL
[ ] Step 3.2: Selection Feedback              - ✅ PASS / ❌ FAIL
[ ] Step 3.3: Re-Selection                    - ✅ PASS / ❌ FAIL
Notes: ___________________________________________________________

TEST 4: Appointment Erstellen (E2E)
[ ] Step 4.1: Complete Form Fill              - ✅ PASS / ❌ FAIL
Notes: ___________________________________________________________

TEST 5: Reschedule Action
[ ] Step 5.1: Open Modal                      - ✅ PASS / ❌ FAIL
[ ] Step 5.2: Select & Reschedule             - ✅ PASS / ❌ FAIL
Notes: ___________________________________________________________

TEST 6: Mobile Responsive
[ ] Step 6.1: Layout (<768px)                 - ✅ PASS / ❌ FAIL
[ ] Step 6.2: Day Expansion                   - ✅ PASS / ❌ FAIL
[ ] Step 6.3: Slot Selection                  - ✅ PASS / ❌ FAIL
Notes: ___________________________________________________________

TEST 7: Error States
[ ] Step 7.1: No Event Type ID                - ✅ PASS / ❌ FAIL
[ ] Step 7.2: Empty Week                      - ✅ PASS / ❌ FAIL
[ ] Step 7.3: API Error                       - ✅ PASS / ❌ FAIL
Notes: ___________________________________________________________

TEST 8: Dark Mode
[ ] Step 8.1: Toggle Dark Mode                - ✅ PASS / ❌ FAIL
[ ] Step 8.2: Slot Selection                  - ✅ PASS / ❌ FAIL
Notes: ___________________________________________________________

TEST 9: Performance
[ ] Step 9.1: Initial Load                    - ✅ PASS / ❌ FAIL
[ ] Step 9.2: Cache Hit                       - ✅ PASS / ❌ FAIL
[ ] Step 9.3: Cache Invalidation              - ✅ PASS / ❌ FAIL
Notes: ___________________________________________________________

┌─────────────────────────────────────────────────────────┐
│ SUMMARY                                                 │
├─────────────────────────────────────────────────────────┤
│ Total Tests: ____ / ____                                │
│ Passed: ____                                            │
│ Failed: ____                                            │
│ Blocked: ____                                           │
│                                                         │
│ Critical Bugs Found: ____                               │
│ High Priority Bugs: ____                                │
│ Medium Priority Bugs: ____                              │
│ Low Priority Bugs: ____                                 │
│                                                         │
│ Overall Status: ✅ READY / ⚠️ NEEDS FIXES / ❌ BLOCKED │
└─────────────────────────────────────────────────────────┘

RECOMMENDATION:
[ ] Deploy to Production immediately
[ ] Fix Critical/High bugs first, then deploy
[ ] Major rework needed, do not deploy

Tester Signature: ________________  Date: __________
```

---

**Ende - Comprehensive Test Plan**
