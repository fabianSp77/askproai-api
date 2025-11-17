# Phase 4 Implementation Plan - Admin Interfaces
**Date**: 2025-11-14
**Status**: Planning → Implementation → Testing

---

## 🎯 Ziele von Phase 4

**Hauptziel**: Benutzerfreundliche Admin-Interfaces für das Policy-System bereitstellen

**Erfolgskriterien**:
- ✅ Alle 8 neuen Policy-Types in UI verfügbar
- ✅ Call Forwarding konfigurierbar über Filament
- ✅ CallbackRequest Email-Feld in UI sichtbar
- ✅ Alle CRUD-Operationen funktionieren
- ✅ Validierung für alle Felder
- ✅ Responsive Design (Desktop + Mobile)

---

## 📋 Detaillierte Aufgaben

### Task 4.1: PolicyConfigurationResource erweitern

**Aktuelles Problem**:
```php
// Aktuell nur 3 alte Types
->options([
    'cancellation' => 'Stornierung',
    'reschedule' => 'Umbuchung',
    'recurring' => 'Wiederkehrend',
])
```

**Lösung**:
```php
// Alle 11 Policy Types
->options([
    // Legacy (existing)
    PolicyConfiguration::POLICY_TYPE_CANCELLATION => '🚫 Stornierung',
    PolicyConfiguration::POLICY_TYPE_RESCHEDULE => '🔄 Umbuchung',
    PolicyConfiguration::POLICY_TYPE_RECURRING => '🔁 Wiederkehrend',

    // ✅ NEW: Operational Policies
    PolicyConfiguration::POLICY_TYPE_BOOKING => '📅 Terminbuchung',
    PolicyConfiguration::POLICY_TYPE_APPOINTMENT_INQUIRY => '🔍 Terminabfrage',
    PolicyConfiguration::POLICY_TYPE_AVAILABILITY_INQUIRY => '📊 Verfügbarkeitsabfrage',
    PolicyConfiguration::POLICY_TYPE_CALLBACK_SERVICE => '📞 Rückrufservice',
    PolicyConfiguration::POLICY_TYPE_SERVICE_INFORMATION => '📋 Service-Informationen',
    PolicyConfiguration::POLICY_TYPE_OPENING_HOURS => '🕐 Öffnungszeiten',

    // ✅ NEW: Access Control Policies
    PolicyConfiguration::POLICY_TYPE_ANONYMOUS_RESTRICTIONS => '🔒 Anonyme Anrufer',
    PolicyConfiguration::POLICY_TYPE_INFO_DISCLOSURE => '👁️ Info-Offenlegung',
])
```

**Änderungen erforderlich in**:
1. Form Builder (3 Stellen):
   - Line 95-101: policy_type Select Options
   - Line 377-382: Table Column formatStateUsing
   - Line 434-438: Filter Options

2. Table Builder (1 Stelle):
   - Line 375-382: Badge Formatter

3. Info List Builder (1 Stelle):
   - Line 541-548: Detail View Formatter

**Neue UI-Felder für Operational Policies**:
```php
// Booking Policy Config
Forms\Components\Toggle::make('config.enabled')
Forms\Components\Select::make('config.allowed_hours')
Forms\Components\Textarea::make('config.disabled_message')

// Availability Inquiry Policy Config
Forms\Components\Toggle::make('config.enabled')
Forms\Components\Toggle::make('config.show_staff_names')
Forms\Components\Toggle::make('config.show_prices')

// Callback Service Policy Config
Forms\Components\Toggle::make('config.enabled')
Forms\Components\Toggle::make('config.require_email')
Forms\Components\Select::make('config.max_callbacks_per_day')

// Service Information Policy Config
Forms\Components\Toggle::make('config.enabled')
Forms\Components\MultiSelect::make('config.excluded_services')

// Opening Hours Policy Config
Forms\Components\Toggle::make('config.enabled')

// Anonymous Restrictions Policy Config (READ-ONLY display)
Forms\Components\Placeholder::make('security_notice')
    ->content('⚠️ Diese Regeln sind hart codiert und können nicht geändert werden.')
Forms\Components\KeyValue::make('config.restrictions_summary')
    ->disabled()

// Info Disclosure Policy Config
Forms\Components\CheckboxList::make('config.default_fields')
    ->options([
        'date' => 'Datum',
        'time' => 'Uhrzeit',
        'service' => 'Service',
        'staff' => 'Mitarbeiter',
        'price' => 'Preis',
    ])
```

---

### Task 4.2: CallForwardingConfigurationResource erstellen

**Neue Resource**: `app/Filament/Resources/CallForwardingConfigurationResource.php`

**Navigation**:
- Group: "Einstellungen"
- Icon: heroicon-o-phone-arrow-up-right
- Label: "Anrufweiterleitung"
- Badge: Count of active forwarding configs

**Form Structure**:
```
Section: Basis-Einstellungen
├─ Branch (Select - required)
├─ Is Active (Toggle - default true)
└─ Timezone (Select - default Europe/Berlin)

Section: Weiterleitungsregeln (Repeater)
├─ Trigger (Select)
│  ├─ no_availability
│  ├─ after_hours
│  ├─ booking_failed
│  ├─ high_call_volume
│  └─ manual
├─ Target Number (TextInput - tel format, E.164 validation)
├─ Priority (Number - min 1, default 1)
└─ Conditions (KeyValue - optional)

Section: Fallback-Nummern
├─ Default Forwarding Number (TextInput - tel, optional)
└─ Emergency Forwarding Number (TextInput - tel, optional)

Section: Aktive Zeiten (Optional)
└─ Active Hours (JSON - weekly schedule editor)
```

**Table Columns**:
- Branch Name (searchable, sortable)
- Rules Count (badge)
- Default Number (formatted)
- Is Active (icon)
- Created At (date, sortable)

**Filters**:
- Branch (SelectFilter)
- Is Active (TernaryFilter)
- Has Rules (TernaryFilter)

**Actions**:
- Test Forwarding (custom action - test number reachability)
- Clone to Other Branch (custom action)
- Quick Toggle Active (bulk action)

**Validation Rules**:
```php
'branch_id' => 'required|exists:branches,id|unique:call_forwarding_configurations,branch_id',
'forwarding_rules' => 'required|array|min:1',
'forwarding_rules.*.trigger' => 'required|in:no_availability,after_hours,booking_failed,high_call_volume,manual',
'forwarding_rules.*.target_number' => 'required|regex:/^\+[1-9]\d{1,14}$/',
'forwarding_rules.*.priority' => 'required|integer|min:1',
'default_forwarding_number' => 'nullable|regex:/^\+[1-9]\d{1,14}$/',
'emergency_forwarding_number' => 'nullable|regex:/^\+[1-9]\d{1,14}$/',
```

---

### Task 4.3: CallbackRequestResource erweitern

**Änderungen in**: `app/Filament/Resources/CallbackRequestResource.php`

**Form - Email Field hinzufügen**:
```php
// Nach customer_name (ca. Line 120)
Forms\Components\TextInput::make('customer_email')
    ->label('E-Mail')
    ->email()
    ->maxLength(255)
    ->placeholder('kunde@example.com')
    ->helperText('Optional: Für Terminbestätigungen per E-Mail')
    ->columnSpan(1),
```

**Table - Email Column hinzufügen**:
```php
// Nach customer_name Column (ca. Line 180)
Tables\Columns\TextColumn::make('customer_email')
    ->label('E-Mail')
    ->icon('heroicon-o-envelope')
    ->copyable()
    ->placeholder('—')
    ->searchable()
    ->toggleable(),
```

**Filters - Email Filter hinzufügen**:
```php
Tables\Filters\TernaryFilter::make('has_email')
    ->label('Mit E-Mail')
    ->queries(
        true: fn ($query) => $query->whereNotNull('customer_email'),
        false: fn ($query) => $query->whereNull('customer_email'),
    ),
```

**Info List - Email Entry hinzufügen**:
```php
// In Detail View Section
Infolists\Components\TextEntry::make('customer_email')
    ->label('E-Mail')
    ->icon('heroicon-o-envelope')
    ->copyable()
    ->placeholder('Nicht angegeben'),
```

---

## 🎨 UI/UX Anforderungen

### Design Consistency
- ✅ Icons für alle Policy Types (Emoji + Heroicons)
- ✅ Badge Colors konsistent:
  - Operational Policies: blue
  - Access Control Policies: purple
  - Legacy Policies: existing colors
- ✅ Helper Text bei allen Feldern
- ✅ Placeholder Values sinnvoll

### Validation UX
- ✅ Inline Validation (live)
- ✅ Clear Error Messages (Deutsch)
- ✅ Success Notifications
- ✅ Confirmation Dialogs für kritische Actions

### Responsive Design
- ✅ Mobile-friendly Forms (stacked layout)
- ✅ Desktop-optimiert (2-column grids)
- ✅ Touch-friendly Controls

---

## 🔍 Testing Strategy

### Manual Testing Checklist

**PolicyConfigurationResource**:
```
[ ] Alle 11 Policy Types im Select sichtbar
[ ] Neue Operational Policy erstellbar
[ ] Form Felder erscheinen für neuen Type
[ ] Save funktioniert ohne Fehler
[ ] Edit lädt korrekte Daten
[ ] Delete mit Confirmation
[ ] Table zeigt neue Badges korrekt
[ ] Filters filtern korrekt
[ ] Detail View zeigt alle Infos
```

**CallForwardingConfigurationResource**:
```
[ ] Resource registriert in Navigation
[ ] Form öffnet ohne Fehler
[ ] Branch Select geladen
[ ] Repeater für Rules funktioniert
[ ] Phone Number Validation (E.164)
[ ] Unique Branch Constraint wird geprüft
[ ] Save erstellt DB-Eintrag korrekt
[ ] Table zeigt Daten
[ ] Edit lädt Repeater-Daten
[ ] Delete mit Cascade
```

**CallbackRequestResource**:
```
[ ] Email-Feld im Form sichtbar
[ ] Email Validation funktioniert
[ ] Email speichert korrekt
[ ] Table Column zeigt Email
[ ] Email copyable
[ ] Filter "Mit E-Mail" funktioniert
[ ] Detail View zeigt Email
```

### Automated Tests

**Browser Tests** (Playwright/Puppeteer):
```php
// PolicyConfiguration CRUD
test('can create operational policy via filament')
test('can edit policy and cache invalidates')
test('policy type select shows all 11 types')

// CallForwarding CRUD
test('can create forwarding configuration')
test('phone number validation works')
test('repeater adds multiple rules')

// CallbackRequest
test('email field is visible and editable')
test('email validation works')
```

---

## 🚨 Bekannte Risiken

### Risk 1: Filament Version Compatibility
**Beschreibung**: Filament 3.x API könnte sich unterscheiden
**Mitigation**: Code gegen aktuelle Filament-Docs prüfen
**Severity**: Low

### Risk 2: Policy Config JSON Schema
**Beschreibung**: Jeder Policy-Type hat unterschiedliche Config-Felder
**Mitigation**: Conditional Fields mit `->visible(fn (Get $get) => ...)`
**Severity**: Medium

### Risk 3: Phone Number Validation Regex
**Beschreibung**: E.164 Regex könnte edge cases nicht abdecken
**Mitigation**: Zusätzlich `libphonenumber` Package verwenden
**Severity**: Low

---

## 📊 Success Metrics

### Functional Metrics
- ✅ 100% CRUD Operations funktionieren
- ✅ 0 Validation Bypass möglich
- ✅ 0 UI Crashes/Errors

### UX Metrics
- ✅ Form Completion Time < 2 Minuten
- ✅ Zero Confusion bei Policy-Type Auswahl
- ✅ Mobile Usability Score > 90%

### Code Quality Metrics
- ✅ 0 PHP Syntax Errors
- ✅ 0 Filament API Violations
- ✅ 100% Fields have Labels + Helper Text

---

## 🔄 Implementation Order

1. **PolicyConfigurationResource** (HIGHEST PRIORITY)
   - Blockt aktuell E2E-Tests
   - Kritisch für Policy-Management

2. **CallbackRequestResource** (MEDIUM PRIORITY)
   - Kleine Änderung, schnell umgesetzt
   - Sofort nutzbar

3. **CallForwardingConfigurationResource** (LOWER PRIORITY)
   - Komplett neue Resource
   - Optional Feature, nicht kritisch

---

## ✅ Definition of Done

Phase 4 ist abgeschlossen wenn:
- [ ] Alle 3 Resources aktualisiert/erstellt
- [ ] Manual Testing Checklist 100% passed
- [ ] Automated Browser Tests geschrieben (mindestens 5)
- [ ] Code Quality Check bestanden (A+ Grade)
- [ ] Screenshots für Dokumentation erstellt
- [ ] Admin-Guide geschrieben

---

**Estimated Time**: 4-6 hours
**Priority**: HIGH (blockt E2E Tests)
**Dependencies**: None (Phase 1-3 complete)
