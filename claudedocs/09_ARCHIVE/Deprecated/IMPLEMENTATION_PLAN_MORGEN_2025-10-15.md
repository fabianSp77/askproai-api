# Implementierungsplan - Option A: Kompletter Ersatz
**Datum:** Mittwoch, 15. Oktober 2025
**Ziel:** Alte Dropdowns entfernen, neue Booking Flow Komponente erweitern
**Geschätzte Dauer:** 5-7 Stunden

---

## 📋 Übersicht

**Was wird gemacht:**
- ✅ Alte Service/Mitarbeiter Dropdowns ENTFERNEN
- ✅ Booking Flow erweitern mit Filiale + Kunde
- ✅ Design verbessern (Kontrast, Accessibility)
- ✅ Vollständig testen mit Puppeteer
- ✅ User-Validierung

**Was bleibt:**
- Alte Filiale-Auswahl (wird integriert)
- Alte Kunden-Suche (wird integriert)
- Status, Notizen, etc. (unverändert)

---

## ⏰ Zeitplan für Morgen

### **Phase 1: Analyse & Backup** (08:00 - 08:30, 30 Min)
**Ziel:** Sichern und verstehen

**Tasks:**
1. Git Branch erstellen: `feature/unified-booking-flow`
2. Backup erstellen von:
   - `app/Filament/Resources/AppointmentResource.php`
   - `app/Livewire/AppointmentBookingFlow.php`
   - `resources/views/livewire/appointment-booking-flow.blade.php`
3. Screenshots vom aktuellen Zustand machen
4. Laravel Logs leeren für sauberes Testing

**Deliverable:** ✅ Sauberer Arbeitszustand mit Backup

---

### **Phase 2: AppointmentBookingFlow erweitern** (08:30 - 10:30, 2h)
**Ziel:** Komponente um Filiale + Kunde erweitern

#### 2.1 Livewire Component erweitern (1h)
**File:** `app/Livewire/AppointmentBookingFlow.php`

**Änderungen:**
```php
// NEU: Properties hinzufügen
public ?int $selectedBranchId = null;
public ?int $selectedCustomerId = null;
public array $availableBranches = [];
public array $availableCustomers = [];
public string $customerSearchQuery = '';

// NEU: Methods hinzufügen
public function loadAvailableBranches(): void
public function selectBranch(int $branchId): void
public function searchCustomers(string $query): void
public function selectCustomer(int $customerId): void
```

**Steps:**
1. Properties für Branch und Customer hinzufügen
2. `mount()` erweitern: Branch aus auth()->user()->company_id laden
3. `loadAvailableBranches()` implementieren
4. `searchCustomers()` mit Live-Search implementieren
5. `selectBranch()` und `selectCustomer()` mit Events

**Testing:** PHP Syntax Check nach jedem Schritt

---

#### 2.2 Blade Template erweitern (1h)
**File:** `resources/views/livewire/appointment-booking-flow.blade.php`

**Neue Sections hinzufügen (OBEN vor Service):**
```blade
{{-- 1. FILIALE AUSWAHL --}}
<div class="fi-section">
    <div class="fi-section-header">🏢 Filiale</div>
    <div class="fi-radio-group">
        @foreach($availableBranches as $branch)
            <label class="fi-radio-option">
                <input type="radio" wire:model.live="selectedBranchId">
                {{ $branch['name'] }}
            </label>
        @endforeach
    </div>
</div>

{{-- 2. KUNDE SUCHEN --}}
<div class="fi-section">
    <div class="fi-section-header">👤 Kunde</div>
    <input type="text"
           wire:model.live.debounce.300ms="customerSearchQuery"
           placeholder="Name, Telefon oder E-Mail eingeben...">

    @if(strlen($customerSearchQuery) >= 3)
        <div class="customer-results">
            @foreach($availableCustomers as $customer)
                <button wire:click="selectCustomer({{ $customer['id'] }})">
                    {{ $customer['name'] }}
                </button>
            @endforeach
        </div>
    @endif
</div>
```

**Steps:**
1. Section 1 + 2 OBEN einfügen (vor Service)
2. Styles für Search-Input hinzufügen
3. Customer-Results Dropdown stylen
4. Loading States hinzufügen
5. Selected States visualisieren

**Testing:** Blade Syntax Check

---

### **Phase 3: Alte Felder entfernen** (10:30 - 11:00, 30 Min)
**Ziel:** Duplikation beseitigen

**File:** `app/Filament/Resources/AppointmentResource.php`

**Zu entfernen:**
```php
// LINES 131-164: Service Dropdown → LÖSCHEN
Forms\Components\Select::make('service_id')...

// LINES 166-201: Staff Dropdown → LÖSCHEN
Forms\Components\Select::make('staff_id')...

// LINES 267-295: Service Info Banner → LÖSCHEN
Forms\Components\Placeholder::make('service_info')...
```

**BEHALTEN:**
```php
// LINES 67-129: Kontext Section → BEHALTEN (für Edit-Modus)
Forms\Components\Select::make('company_id')...
Forms\Components\Select::make('branch_id')...
Forms\Components\Select::make('customer_id')...

// Aber: In Create-Modus VERSTECKEN (wird durch Component ersetzt)
->hidden(fn ($context) => $context === 'create')
```

**Steps:**
1. Service Dropdown auskommentieren (nicht löschen!)
2. Staff Dropdown auskommentieren
3. Service Info Banner auskommentieren
4. Kontext-Fields mit `->hidden()` für Create-Modus
5. Git Commit: "Remove duplicate fields"

**Testing:** PHP Syntax Check

---

### **Phase 4: Design verbessern** (11:00 - 12:00, 1h)
**Ziel:** Kontrast-Probleme beheben

**File:** `resources/views/livewire/appointment-booking-flow.blade.php`

**Änderungen:**
```css
/* VORHER (SCHLECHT): */
.dark .fi-section {
    background-color: var(--color-gray-800);
    border-color: var(--color-gray-700);  /* Kontrast 1.2:1 ❌ */
}

/* NACHHER (GUT): */
.dark .fi-section {
    background-color: var(--color-gray-800);
    border-color: var(--color-gray-500);  /* Kontrast 3.5:1 ✅ */
}

/* Focus-Indikatoren hinzufügen: */
.fi-radio-option:focus-within {
    outline: 2px solid var(--color-primary-500);
    outline-offset: 2px;
}
```

**Steps:**
1. Alle Border-Colors auf min. 3:1 Kontrast anpassen
2. Focus-Indikatoren für Keyboard-Navigation
3. Hover-States verbessern
4. Loading-Spinner-Farben anpassen
5. Error-States deutlicher machen

**Testing:** Accessibility Check mit DevTools

---

### **MITTAGSPAUSE** (12:00 - 13:00)

---

### **Phase 5: Integration & Events** (13:00 - 14:00, 1h)
**Ziel:** Hidden Fields korrekt befüllen

**File:** `resources/views/livewire/appointment-booking-flow-wrapper.blade.php`

**Alpine.js Events erweitern:**
```javascript
x-on:branch-selected.window="
    const branchInput = form.querySelector('input[name=branch_id]');
    branchInput.value = $event.detail.branchId;
    branchInput.dispatchEvent(new Event('change', { bubbles: true }));
"

x-on:customer-selected.window="
    const customerInput = form.querySelector('input[name=customer_id]');
    customerInput.value = $event.detail.customerId;
    customerInput.dispatchEvent(new Event('change', { bubbles: true }));
"
```

**Steps:**
1. Branch-Selected Event Handler
2. Customer-Selected Event Handler
3. Service-Selected Event Handler (schon vorhanden, prüfen)
4. Slot-Selected Event Handler (schon vorhanden, prüfen)
5. Alle Hidden Fields testen

**Testing:** Browser DevTools → Check hidden field values

---

### **Phase 6: Puppeteer Tests schreiben** (14:00 - 15:30, 1.5h)
**Ziel:** Vollständige E2E Tests

**File:** `tests/puppeteer/unified-booking-flow-test.cjs`

**Test Cases:**
```javascript
// TEST 1: Page Load
✅ No 500 errors
✅ Component visible
✅ All sections present

// TEST 2: Branch Selection
✅ Branches loaded
✅ Click branch → updates component
✅ Hidden field populated

// TEST 3: Customer Search
✅ Search input works
✅ Results appear after 3 chars
✅ Select customer → updates component
✅ Hidden field populated

// TEST 4: Service Selection
✅ Services loaded
✅ Click service → calendar updates
✅ Hidden field populated

// TEST 5: Employee Selection
✅ Employees loaded (or "any available")
✅ Click employee → calendar filters
✅ No duplicate fields visible

// TEST 6: Calendar Interaction
✅ Week navigation works
✅ Slots displayed
✅ Click slot → confirmation shown
✅ Hidden fields populated (starts_at, ends_at)

// TEST 7: Form Submit
✅ All required fields filled
✅ Form validation passes
✅ Appointment created in DB

// TEST 8: Design Validation
✅ Contrast ratios ≥ 3:1
✅ Focus indicators visible
✅ No duplicate sections
✅ Colors match theme
```

**Steps:**
1. Test schreiben (alle 8 Testcases)
2. Test ausführen
3. Screenshots machen
4. Fehler dokumentieren
5. Iterieren bis alle Tests grün

**Testing:** Alle Tests müssen ✅ sein

---

### **Phase 7: Manual Testing** (15:30 - 16:30, 1h)
**Ziel:** User-Flow durchgehen

**Manual Checklist:**
```
Browser: Chrome, Firefox, Safari
Theme: Light Mode, Dark Mode

[ ] Page loads without errors
[ ] No console errors (F12)
[ ] All sections visible in correct order:
    1. 🏢 Filiale
    2. 👤 Kunde
    3. 💇 Service
    4. 👤 Mitarbeiter
    5. 📅 Kalender
[ ] NO duplicate fields anywhere
[ ] Colors look good (no "gray on gray")
[ ] Focus indicators work (Tab navigation)
[ ] All interactions smooth
[ ] Form submits successfully
[ ] Appointment appears in database
```

**Edge Cases:**
```
[ ] Branch with no services → Error message
[ ] Customer search no results → "Nicht gefunden"
[ ] Service with no availability → "Keine Termine"
[ ] Week with no slots → "Nicht verfügbar"
[ ] Form submit with missing fields → Validation errors
```

**Testing:** Document all issues found

---

### **Phase 8: Bugfixes & Polish** (16:30 - 17:30, 1h)
**Ziel:** Alle gefundenen Probleme beheben

**Expected Issues:**
- Loading states nicht sichtbar
- Error messages unklar
- Contrast-Probleme übersehen
- Edge cases nicht behandelt
- Performance-Probleme

**Steps:**
1. Liste alle gefundenen Bugs
2. Priorisieren (Critical → Nice-to-have)
3. Beheben in Reihenfolge
4. Re-testen nach jedem Fix
5. Git Commits für jeden Fix

**Testing:** Regression Tests nach jedem Fix

---

### **Phase 9: Dokumentation & Deploy** (17:30 - 18:00, 30 Min)
**Ziel:** Deployment vorbereiten

**Dokumentation:**
```markdown
1. USER_GUIDE_APPOINTMENT_BOOKING.md
   - Screenshots vom neuen Flow
   - Schritt-für-Schritt Anleitung
   - Troubleshooting Section

2. CHANGELOG_2025-10-15.md
   - Was wurde geändert
   - Was wurde entfernt
   - Breaking Changes (keine)

3. TESTING_REPORT_2025-10-15.md
   - Alle Test-Ergebnisse
   - Screenshots
   - Performance Metrics
```

**Deploy Checklist:**
```bash
# 1. Final Tests
php artisan test
npm run test
node tests/puppeteer/unified-booking-flow-test.cjs

# 2. Git
git add .
git commit -m "feat: Unified booking flow - remove duplicates"
git push origin feature/unified-booking-flow

# 3. Create Pull Request
gh pr create --title "Unified Booking Flow" --body "..."

# 4. Merge & Deploy
# (nach User-Review)
```

---

## 📊 Success Criteria

**Must Have (Critical):**
- ✅ No duplicate fields visible
- ✅ No 500 errors
- ✅ All functionality works
- ✅ Contrast ratios ≥ 3:1 (WCAG AA)
- ✅ Form submission creates appointment

**Should Have (Important):**
- ✅ All Puppeteer tests pass
- ✅ Good UX (smooth, intuitive)
- ✅ Error messages clear
- ✅ Loading states visible
- ✅ Mobile responsive

**Nice to Have (Optional):**
- ✅ Keyboard navigation perfect
- ✅ Screen reader compatible
- ✅ Performance optimized (< 2s load)
- ✅ Animations smooth

---

## 🚨 Risk Management

### High Risk (Wahrscheinlich)
**Problem:** Alpine.js Events funktionieren nicht
**Mitigation:** Fallback auf native JavaScript Events
**Time Buffer:** +30 Min

**Problem:** Customer Search zu langsam
**Mitigation:** Debouncing auf 500ms erhöhen
**Time Buffer:** +15 Min

**Problem:** Kontrast-Fixes ändern zu viel
**Mitigation:** Nur Dark Mode Borders anpassen
**Time Buffer:** +15 Min

### Medium Risk (Möglich)
**Problem:** Puppeteer Tests finden Component nicht
**Mitigation:** Selektoren anpassen, wait-Zeiten erhöhen
**Time Buffer:** +30 Min

**Problem:** Hidden Fields werden nicht befüllt
**Mitigation:** Alpine.js Debugging, Console Logs
**Time Buffer:** +45 Min

### Low Risk (Unwahrscheinlich)
**Problem:** Laravel Cache-Probleme
**Mitigation:** `php artisan cache:clear`
**Time Buffer:** +5 Min

---

## 📦 Deliverables am Ende des Tages

**Code:**
- ✅ Erweiterte AppointmentBookingFlow Component
- ✅ Bereinigte AppointmentResource (keine Duplikate)
- ✅ Verbesserte CSS (Kontrast, Accessibility)
- ✅ Alpine.js Wrapper mit allen Events

**Tests:**
- ✅ Puppeteer E2E Test Suite (8 Tests)
- ✅ Manual Testing Checklist (completed)
- ✅ Screenshots vom neuen Flow

**Dokumentation:**
- ✅ User Guide
- ✅ Changelog
- ✅ Testing Report

**Git:**
- ✅ Feature Branch: `feature/unified-booking-flow`
- ✅ Pull Request erstellt
- ✅ Bereit zum Merge

---

## 🎯 Quick Start für Morgen

**08:00 Uhr - Sofort starten:**
```bash
# 1. Branch erstellen
git checkout -b feature/unified-booking-flow

# 2. Backup
cp app/Filament/Resources/AppointmentResource.php \
   app/Filament/Resources/AppointmentResource.php.backup

# 3. Screenshots
# Browser öffnen, /admin/appointments/create
# Screenshots machen für Vorher/Nachher

# 4. Logs leeren
> storage/logs/laravel.log

# 5. Diesem Plan folgen Phase für Phase
```

---

## ✅ Ende-des-Tages Check

**Vor Feierabend prüfen:**
```
[ ] Alle Puppeteer Tests grün
[ ] Keine console errors
[ ] Keine Laravel errors in logs
[ ] Screenshots gemacht (vorher/nachher)
[ ] Dokumentation geschrieben
[ ] Git committed & pushed
[ ] PR erstellt
[ ] User benachrichtigt für Review
```

---

**Geschätzte Dauer:** 5-7 Stunden (08:00 - 18:00 mit Pausen)
**Puffer:** 1-2 Stunden für unerwartete Probleme
**Status:** ✅ Plan fertig, bereit für Morgen

**Viel Erfolg morgen! 🚀**
