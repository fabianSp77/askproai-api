# Staff & Event Type Synchronization - Implementierung

## Problem (Issue #204)
Der User erkannte, dass Mitarbeiter im System nur diejenigen sein sollten, die auch in Cal.com als Hosts für Event Types zugewiesen sind. Administrative Mitarbeiter sollten trotzdem im System bleiben können.

## Implementierte Lösung

### 1. **Erweiterte Commands**

#### `calcom:sync-users`
```bash
php artisan calcom:sync-users --company=1 --branch=1 --interactive
```
- Importiert Cal.com Benutzer als Mitarbeiter
- Optionen für interaktive Auswahl
- Verknüpft automatisch über Email oder Cal.com User ID

#### `calcom:sync-event-type-users`
```bash
php artisan calcom:sync-event-type-users --company=1 --immediate
```
- Synchronisiert Event Type Host-Zuordnungen
- Erstellt/aktualisiert `staff_event_types` Einträge
- Entfernt veraltete Zuordnungen automatisch

### 2. **Model-Erweiterungen**

#### Staff Model
```php
// Neue Methoden
$staff->canHostEventType($eventTypeId); // Prüft ob Staff Event Type hosten kann
$staff->getHostableEventTypes();        // Alle Event Types die Staff hosten kann
$staff->hasEventTypeAssignments();      // Hat Staff Event Type Zuordnungen?
$staff->isAdministrative();             // Ist Staff administrativ (ohne Event Types)?
```

### 3. **Service Layer**

#### StaffAssignmentService
- `getAvailableStaffForEventType()` - Findet verfügbare Hosts
- `validateStaffAssignment()` - Validiert Staff-Event Type Zuordnung
- `autoAssignStaff()` - Automatische Staff-Zuweisung (round-robin, least-busy, random)
- `syncEventTypeHosts()` - Synchronisiert Hosts aus Cal.com

### 4. **Booking Validierung**

Die `AppointmentBookingService::validateStaff()` Methode prüft jetzt:
- Staff ist aktiv und buchbar
- Staff bietet den Service an
- **NEU**: Staff kann den Event Type hosten (mit Warning, noch nicht blockierend)

### 5. **UI Verbesserungen**

#### QuickSetupWizardV2
- **"Cal.com Benutzer importieren"** Button
- **"Event Type Zuordnungen synchronisieren"** Button  
- Erweiterte Staff-Formulare mit:
  - Cal.com User ID (read-only)
  - "Kann Termine annehmen" Toggle
  - Automatische Event Type Sync-Hinweise

### 6. **Datenbank-Schema**

Nutzt existierende `staff_event_types` Tabelle:
- `staff_id` → `event_type_id` Mapping
- `calcom_user_id` für direkte Verknüpfung
- `is_primary` für Haupt-Host Markierung

## Workflow für Benutzer

### Initial Setup:
1. **Cal.com API Key** konfigurieren
2. **Event Types importieren** aus Cal.com
3. **Services erstellen** und mit Event Types verknüpfen
4. **Cal.com Benutzer importieren** als Mitarbeiter
5. **Event Type Hosts synchronisieren** für korrekte Zuordnungen

### Laufender Betrieb:
- Regelmäßig Event Type Hosts synchronisieren
- Neue Cal.com Benutzer importieren bei Bedarf
- Administrative Mitarbeiter manuell pflegen

## Technische Details

### Sync-Logik:
1. Hole Event Type Details von Cal.com (inkl. hosts/users Array)
2. Finde lokale Staff über: calcom_user_id → email → name
3. Erstelle/Update staff_event_types Einträge
4. Entferne veraltete Zuordnungen

### Validierungs-Stufen:
1. **Soft Validation** (aktuell): Logge Warnung wenn Staff nicht Host ist
2. **Hard Validation** (geplant): Blockiere Buchung wenn Staff nicht Host ist

## Vorteile

✅ **Datenintegrität**: Nur autorisierte Hosts können Termine annehmen
✅ **Flexibilität**: Administrative Mitarbeiter ohne Event Types möglich
✅ **Automatisierung**: Sync hält Daten aktuell mit Cal.com
✅ **Transparenz**: UI zeigt Cal.com Verknüpfungen

## Nächste Schritte

1. **Harte Validierung aktivieren** nach vollständiger Datenmigration
2. **Automatische Sync-Jobs** einrichten (täglich/stündlich)
3. **Availability-Check** gegen Cal.com implementieren
4. **Staff-Auswahl UI** auf Event Type Hosts beschränken