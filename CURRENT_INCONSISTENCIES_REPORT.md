# 🔴 Aktuelle Inkonsistenzen im AskProAI System

## 1. Doppelte Konzepte für Dienstleistungen

### Problem:
4 verschiedene Wege um dasselbe darzustellen:

```
Services (intern) ←→ CalcomEventTypes (extern)
    ↓                         ↓
MasterServices ←→ UnifiedEventTypes
```

### Konkrete Beispiele:
- `services` Tabelle: "Haarschnitt", "Färben", "Styling"
- `calcom_event_types` Tabelle: "30-min-consultation", "hair-cut-color"
- Keine klare Verknüpfung zwischen beiden!

### Auswirkung:
- Wizard erstellt Services UND importiert EventTypes → Duplikate
- Appointments wissen nicht ob sie Service oder EventType referenzieren sollen
- Staff-Zuordnung unklar (zu Services oder EventTypes?)

## 2. Wizard-Chaos

### QuickSetupWizard Probleme:

#### a) Edit-Mode funktioniert nicht richtig:
```php
// Problem: Lädt Daten nicht korrekt
if ($this->editMode && $this->editingCompany) {
    // Branches werden geladen, aber Services nicht
    // Phone numbers werden dupliziert
    // Cal.com sync wird immer neu gemacht
}
```

#### b) Doppelte Datenerstellung:
- Step 3: Erstellt interne Services
- Step 5: Importiert Cal.com EventTypes
- Keine Verknüpfung zwischen beiden!

#### c) Fehlende Transaktionen:
- Einzelne Steps können fehlschlagen
- Teilweise gespeicherte Daten
- Kein Rollback möglich

### Andere Wizards gefunden:
- `OnboardingWizard.php` (macht dasselbe?)
- `EventTypeImportWizard.php` (nur für Events)
- `QuickSetupWizardV2.php` (warum V2?)

## 3. Menü-Duplikationen

### Doppelte Resources:
```
ServiceResource          ←→ MasterServiceResource
WorkingHourResource      ←→ WorkingHoursResource  
CalcomEventTypeResource  ←→ UnifiedEventTypeResource
```

### Versteckte wichtige Resources:
- `CustomerResource` (shouldRegisterNavigation = false)
- `CallResource` (versteckt)
- Warum sind diese versteckt?

### Falsche Navigation Groups:
Definiert: Dashboard, Geschäftsvorgänge, Unternehmensstruktur
Verwendet: Stammdaten, Abrechnung, Kalender & Events (existieren nicht!)

## 4. Datenbank-Inkonsistenzen

### Redundante Tabellen:
```sql
-- Services/EventTypes (4 Varianten):
services
master_services  
calcom_event_types
unified_event_types

-- Staff-Zuordnungen (3 Varianten):
staff_services
staff_service_assignments
staff_event_types

-- Working Hours (2 Varianten):
working_hours
business_hours
```

### Namens-Inkonsistenzen:
- Deutsch/Englisch gemischt: `filialen` vs `branches`
- Singular/Plural: `customer` vs `customers`
- Underscore/Camelcase: `event_type` vs `eventType`

## 5. API/Integration Verwirrung

### Cal.com Integration:
- V1 und V2 gleichzeitig im Einsatz
- `CalcomService` vs `CalcomV2Service`
- Booking geht über V1, alles andere V2?

### Retell Integration:
- `RetellService` vs `RetellV2Service`
- Webhook processing an 3 verschiedenen Stellen

## 6. Konkrete Fehler im System

### CompanyResource:
```php
// Form ist unvollständig (Code bei Zeile 100 abgeschnitten)
// Fehlende Felder für Cal.com Integration
// Keine Validierung der API Keys
```

### BranchResource:
- Fehlt: Cal.com Event Type Zuordnung
- Fehlt: Öffnungszeiten-Management
- Fehlt: Staff-Übersicht

### ServiceResource:
```php
// Lädt Cal.com EventTypes direkt im Form (schlecht!):
$eventTypes = CalcomEventType::where('company_id', $company->id)->get();
// Sollte über Repository oder Service laufen
```

## 7. Business Logic Probleme

### Unklar: Was ist die "Source of Truth"?
- Sind Services intern verwaltet?
- Oder kommen alle von Cal.com?
- Was passiert bei Konflikten?

### Fehlende Validierung:
- Doppelte Services möglich
- Gleiche EventTypes mehrfach importierbar
- Keine Unique Constraints

### Multi-Tenancy Issues:
- TenantScope fehlt manchmal
- Company-Context nicht immer gesetzt
- Daten-Leaks zwischen Mandanten möglich

## 🎯 Empfohlene Sofortmaßnahmen

1. **STOPP**: Keine neuen Features in alter Struktur!

2. **Entscheidung treffen**:
   - Cal.com EventTypes = Single Source of Truth
   - Services-Tabelle wird deprecated

3. **Wizard vereinfachen**:
   - Nur noch EventType Import
   - Keine Service-Erstellung mehr
   - Transaktionen für alle Steps

4. **Resources konsolidieren**:
   - Eine Resource pro Konzept
   - Versteckte Resources entfernen
   - Navigation Groups korrigieren

5. **Namenskonventionen**:
   - Alles auf Englisch
   - snake_case für Datenbank
   - camelCase für Code

Diese Inkonsistenzen müssen dringend behoben werden, bevor neue Features entwickelt werden!