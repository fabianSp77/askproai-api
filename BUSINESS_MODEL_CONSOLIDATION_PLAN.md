# Business Model Consolidation Plan für AskProAI

## 🎯 Ziel: Von Chaos zu Klarheit

### Aktueller Zustand (Chaos):
```
Company
├── Services (intern)
├── MasterServices (Templates)
├── CalcomEventTypes (extern)
├── UnifiedEventTypes (Hybrid)
└── 3 verschiedene Staff-Zuordnungen
```

### Ziel-Zustand (Klarheit):
```
Company
└── CalcomEventTypes (Single Source of Truth)
    ├── Branch Assignment
    └── Staff Assignment
```

## 📋 Konsolidierungs-Schritte

### Phase 1: Analyse & Backup (1 Tag)
1. **Backup aller Daten**
   ```bash
   php artisan askproai:backup --type=full --encrypt
   ```

2. **Daten-Mapping erstellen**
   - Welche Services sind mit welchen EventTypes verknüpft?
   - Welche Staff-Zuordnungen existieren wo?

### Phase 2: Datenbank-Bereinigung (2 Tage)

#### Zu entfernende Tabellen:
- `services` → Migrieren zu `calcom_event_types`
- `master_services` → Löschen (unnötig)
- `unified_event_types` → Löschen (fehlgeschlagener Versuch)
- `staff_services` → Migrieren zu `staff_event_types`
- `staff_service_assignments` → Migrieren zu `staff_event_types`

#### Neue, klare Struktur:
```sql
-- Nur noch diese Tabellen:
calcom_event_types (Master-Daten von Cal.com)
├── id
├── calcom_id (externe ID)
├── company_id
├── title
├── slug
├── duration
└── description

branch_event_types (Welche Filiale bietet was an)
├── branch_id
├── event_type_id
└── is_active

staff_event_types (Welcher Mitarbeiter kann was)
├── staff_id
├── event_type_id
├── branch_id
└── is_available
```

### Phase 3: Code-Konsolidierung (3 Tage)

#### 1. QuickSetupWizard vereinfachen:
```php
// ALT: Komplexe Service-Erstellung
$this->createInternalService();
$this->syncWithCalcom();
$this->mapServiceToEventType();

// NEU: Nur Import
$this->importCalcomEventTypes();
$this->assignToBranch();
$this->assignToStaff();
```

#### 2. Menü-Struktur aufräumen:
- **Behalten**: CompanyResource (Basis-Daten)
- **Behalten**: QuickSetupWizard (Onboarding)
- **Löschen**: Duplicate Assignment Pages
- **Neu**: Inline-Editing in BranchResource

#### 3. API vereinheitlichen:
```php
// Nur noch Cal.com V2
class CalcomService {
    public function importEventTypes($companyId);
    public function checkAvailability($eventTypeId, $date);
    public function createBooking($eventTypeId, $data);
}
```

## 🔧 Implementierungs-Plan

### Woche 1: Vorbereitung
- [ ] Vollständiges Backup
- [ ] Analyse-Scripts schreiben
- [ ] Migrations vorbereiten
- [ ] Test-Umgebung aufsetzen

### Woche 2: Migration
- [ ] Daten-Migration durchführen
- [ ] Alte Tabellen archivieren
- [ ] Code anpassen
- [ ] Tests schreiben

### Woche 3: Testing & Rollout
- [ ] Umfassende Tests
- [ ] Staging-Deployment
- [ ] Produktion-Migration
- [ ] Monitoring

## ⚠️ Risiken & Mitigation

### Risiko 1: Datenverlust
**Mitigation**: 
- Incremental Backups
- Rollback-Scripts vorbereitet
- Alte Tabellen zunächst nur umbenennen, nicht löschen

### Risiko 2: Breaking Changes
**Mitigation**:
- Feature Flags für neue Implementierung
- Parallel-Betrieb möglich
- Schrittweise Migration pro Company

### Risiko 3: Cal.com API Limits
**Mitigation**:
- Caching-Layer
- Rate Limiting
- Batch-Imports

## 📊 Erfolgs-Metriken

1. **Weniger Code**: -50% Code-Reduktion erwartet
2. **Weniger Tabellen**: Von 119 auf ~60 Tabellen
3. **Klarere Struktur**: 1 Source of Truth statt 4
4. **Bessere Performance**: Weniger JOINs nötig
5. **Einfacheres Onboarding**: 3 Schritte statt 10

## 🚀 Quick Wins (Sofort umsetzbar)

1. **Stopp neuer Features** in alten Strukturen
2. **Dokumentation** der gewünschten Ziel-Architektur
3. **Deprecation Notices** in altem Code
4. **Start mit einem Test-Mandanten**

## 💡 Langfristige Vision

```
AskProAI Platform
└── Company (Multi-Tenant)
    ├── Branches (Locations)
    │   └── Cal.com Event Types (Services)
    │       └── Staff Assignments
    └── Unified Booking Flow
        ├── Phone (Retell.ai)
        ├── Web (Customer Portal)
        └── API (Partners)
```

Alle Buchungen laufen über Cal.com als zentrale Wahrheit für Verfügbarkeiten.