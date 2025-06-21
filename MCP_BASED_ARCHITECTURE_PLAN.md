# 🚀 MCP-basierte Architektur für AskProAI

## Vision: Klare, einfache Struktur mit MCP

### Kern-Prinzip:
```
Cal.com = Source of Truth für alle Termine
   ↓
MCP Services = Zentrale Business Logic
   ↓
Unified API = Konsistente Schnittstelle
```

## 📊 Neue Architektur

### 1. Datenmodell (vereinfacht)

```mermaid
Company (Mandant)
├── Branches (Filialen)
│   ├── CalcomEventTypes (von Cal.com importiert)
│   │   └── StaffAssignments (wer kann was)
│   └── PhoneNumbers (Retell.ai Routing)
├── Staff (Mitarbeiter)
│   └── Availability (Verfügbarkeit)
└── Customers (Kunden)
    └── Appointments (Termine)
```

### 2. MCP Service Layer

```php
// Zentrale MCP Services für alle Operationen
CompanyMCPService
├── importEventTypesFromCalcom()
├── syncBranchConfiguration()
└── validateSetup()

BookingMCPService  
├── checkAvailability()
├── createAppointment()
└── handlePhoneBooking()

StaffMCPService
├── assignToEventType()
├── updateAvailability()
└── getSchedule()
```

### 3. Vereinfachter Wizard Flow

```php
class SetupWizardMCP {
    // Schritt 1: Firma anlegen
    public function setupCompany($data) {
        return $this->companyMCP->createCompany($data);
    }
    
    // Schritt 2: Cal.com verbinden
    public function connectCalcom($apiKey) {
        return $this->calcomMCP->validateAndConnect($apiKey);
    }
    
    // Schritt 3: Event Types importieren
    public function importEventTypes() {
        return $this->calcomMCP->importAllEventTypes();
    }
    
    // Schritt 4: Filialen zuordnen
    public function assignToBranches($mapping) {
        return $this->companyMCP->mapEventTypesToBranches($mapping);
    }
    
    // Schritt 5: Mitarbeiter zuordnen
    public function assignStaff($assignments) {
        return $this->staffMCP->bulkAssignToEventTypes($assignments);
    }
}
```

## 🔧 Migrations-Plan

### Phase 1: Datenbank-Bereinigung

```sql
-- 1. Backup erstellen
CREATE TABLE services_backup AS SELECT * FROM services;
CREATE TABLE master_services_backup AS SELECT * FROM master_services;

-- 2. Mapping erstellen
CREATE TABLE service_to_event_type_mapping AS
SELECT 
    s.id as service_id,
    s.name as service_name,
    cet.id as event_type_id,
    cet.title as event_type_title
FROM services s
LEFT JOIN calcom_event_types cet ON 
    LOWER(s.name) LIKE CONCAT('%', LOWER(cet.title), '%');

-- 3. Staff-Zuordnungen migrieren
INSERT INTO staff_event_types (staff_id, event_type_id, branch_id)
SELECT DISTINCT
    ss.staff_id,
    m.event_type_id,
    s.branch_id
FROM staff_services ss
JOIN services s ON ss.service_id = s.id
JOIN service_to_event_type_mapping m ON s.id = m.service_id
WHERE m.event_type_id IS NOT NULL;

-- 4. Alte Tabellen deaktivieren (nicht löschen!)
RENAME TABLE services TO _deprecated_services;
RENAME TABLE master_services TO _deprecated_master_services;
RENAME TABLE staff_services TO _deprecated_staff_services;
```

### Phase 2: Code-Anpassungen

```php
// Alt (ServiceResource)
public static function form(Form $form): Form
{
    return $form->schema([
        Select::make('service_id')
            ->options(Service::pluck('name', 'id'))
    ]);
}

// Neu (EventTypeResource)  
public static function form(Form $form): Form
{
    return $form->schema([
        Select::make('event_type_id')
            ->options(CalcomEventType::pluck('title', 'id'))
            ->searchable()
    ]);
}
```

### Phase 3: Menu-Konsolidierung

```php
// AdminPanelProvider.php
->navigationGroups([
    'Dashboard',
    'Verwaltung' => [
        'Unternehmen',
        'Filialen', 
        'Mitarbeiter',
        'Event-Typen'
    ],
    'Betrieb' => [
        'Termine',
        'Anrufe',
        'Kunden'
    ],
    'System' => [
        'Monitoring',
        'Einstellungen'
    ]
])

// Zu entfernende Resources:
- ServiceResource ❌
- MasterServiceResource ❌  
- UnifiedEventTypeResource ❌
- DummyCompanyResource ❌
- WorkingHoursResource ❌ (Duplikat)

// Zu behalten:
- CompanyResource ✅
- BranchResource ✅
- StaffResource ✅
- CalcomEventTypeResource ✅ (umbenennen zu EventTypeResource)
- AppointmentResource ✅
- CallResource ✅ (wieder einblenden!)
- CustomerResource ✅ (wieder einblenden!)
```

## 📋 Konkrete TODOs

### Woche 1: Analyse & Vorbereitung
- [ ] Vollständige Daten-Analyse (welche Services sind mit EventTypes verknüpft?)
- [ ] Backup aller relevanten Tabellen
- [ ] Test-Umgebung mit Kopie der Produktionsdaten
- [ ] Mapping-Tabelle erstellen und validieren

### Woche 2: MCP Services implementieren
- [ ] CompanyMCPService erstellen
- [ ] BookingMCPService erweitern  
- [ ] StaffMCPService implementieren
- [ ] Tests für alle MCP Services

### Woche 3: Migration durchführen
- [ ] Datenbank-Migration auf Test-System
- [ ] Code-Anpassungen deployen
- [ ] Wizard neu implementieren
- [ ] Menu-Struktur bereinigen

### Woche 4: Rollout
- [ ] Staging-Tests mit echten Nutzern
- [ ] Produktions-Migration (Wartungsfenster)
- [ ] Monitoring der neuen Struktur
- [ ] Alte Tabellen nach 30 Tagen löschen

## ✅ Erwartete Vorteile

1. **Klarheit**: Nur noch 1 Source of Truth (Cal.com)
2. **Weniger Code**: -60% durch Entfernung von Duplikaten
3. **Bessere Performance**: Weniger JOINs, klarere Queries
4. **Einfacheres Onboarding**: 5 klare Schritte statt 10+
5. **Wartbarkeit**: MCP Services sind testbar und modular

## ⚠️ Wichtige Hinweise

1. **Keine neuen Features** in alter Struktur entwickeln!
2. **Alle Änderungen** über MCP Services
3. **Immer Backups** vor Migrationen
4. **Feature Flags** für schrittweise Migration
5. **Monitoring** während der Umstellung

Diese Architektur macht das System zukunftssicher und wartbar!