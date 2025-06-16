# Event-Management-System Dokumentation

## Übersicht

Das Event-Management-System ist eine vollständige Integration zwischen AskProAI und Cal.com, die es ermöglicht, Event-Types (Dienstleistungen) zu importieren, mit Mitarbeitern zu verknüpfen und intelligente Verfügbarkeitsprüfungen durchzuführen.

## Hauptfunktionen

### 1. Event-Type Synchronisation
- Import von Event-Types aus Cal.com
- Automatische Zuordnung zu Team-Mitgliedern
- Unterstützung für Team-Events und Einzeltermine
- Metadaten-Verwaltung (Dauer, Preis, etc.)

### 2. Mitarbeiter-Zuordnung
- Flexible Zuordnung von Mitarbeitern zu Event-Types
- Custom Dauer und Preise pro Mitarbeiter
- Primäre Mitarbeiter-Kennzeichnung
- Matrix-Ansicht für Bulk-Zuordnungen

### 3. Intelligente Verfügbarkeitsprüfung
- Verfügbarkeit mit oder ohne Mitarbeiterwunsch
- Berücksichtigung von Event-Type-Dauer
- Multi-Filial-Support
- Fallback auf Standard-Event-Type

### 4. Performance-Optimierung
- 15-Minuten Cache für Verfügbarkeitsabfragen
- Queue-basierte Synchronisation
- Bulk-Operations für Zuordnungen
- Pre-Caching von häufigen Abfragen

## Datenbank-Schema

### Neue/Erweiterte Tabellen

#### calcom_event_types
```sql
- id (BIGINT)
- company_id (BIGINT)
- branch_id (UUID, nullable)
- calcom_event_type_id (VARCHAR)
- calcom_numeric_event_type_id (BIGINT)
- name (VARCHAR)
- slug (VARCHAR)
- description (TEXT)
- duration_minutes (INT)
- price (DECIMAL)
- is_active (BOOLEAN)
- is_team_event (BOOLEAN)
- requires_confirmation (BOOLEAN)
- booking_limits (JSON)
- metadata (JSON)
- last_synced_at (TIMESTAMP)
```

#### staff_event_types
```sql
- id (BIGINT)
- staff_id (UUID)
- event_type_id (BIGINT)
- calcom_user_id (VARCHAR)
- is_primary (BOOLEAN)
- custom_duration (INT)
- custom_price (DECIMAL)
- availability_override (JSON)
```

#### Erweiterungen
- companies: `default_event_type_id`
- staff: `calcom_username`, `working_hours`

## API Endpoints

### Event-Type Synchronisation
```
GET /api/event-management/sync/event-types/{company}
GET /api/event-management/sync/team/{company}
```

### Verfügbarkeitsprüfung
```
POST /api/event-management/check-availability
{
    "event_type_id": 123,
    "date_from": "2024-01-10T00:00:00Z",
    "date_to": "2024-01-17T00:00:00Z",
    "staff_id": "uuid" (optional)
}
```

### Event-Type Management
```
GET /api/event-management/event-types/{company}/branch/{branch?}
GET /api/event-management/staff-event-matrix/{company}
POST /api/event-management/staff-event-assignments
```

## Filament Admin Interface

### CalcomEventTypeResource
- Liste aller Event-Types mit Filterung
- Sync-Button für manuellen Import
- Bulk-Zuordnung zu Mitarbeitern
- Status-Anzeige und Metadaten

### Staff-Event Assignment Page
- Matrix-Ansicht: Mitarbeiter × Event-Types
- Checkbox-basierte Zuordnung
- Bulk-Actions (alle auswählen/abwählen)
- Speichern aller Änderungen auf einmal

### Availability Overview Widget
- Dashboard-Widget für Verfügbarkeitsübersicht
- Zeigt nächste freie Termine
- Auslastung pro Mitarbeiter
- Filialübergreifende Übersicht

## Services

### CalcomSyncService
Hauptservice für Cal.com Integration:
- `syncEventTypesForCompany($companyId)`
- `syncTeamMembers($companyId)`
- `checkAvailability($eventTypeId, $dateFrom, $dateTo, $staffId = null)`
- Cache-Management mit 15-Minuten TTL

### AvailabilityChecker
Intelligente Verfügbarkeitsprüfung:
- `checkAvailabilityFromRequest($request)`
- `findNextAvailableSlot($eventTypeId, $staffId, $branchId)`
- Parsing von natürlichsprachlichen Anfragen
- Fallback-Mechanismen

### RetellWebhookController (erweitert)
- `extractBookingRequest($data)` - Extrahiert Service und Mitarbeiterwunsch
- `tryEnhancedCalcomBooking()` - Buchung mit Event-Type und Mitarbeiter
- `isAvailabilityRequest()` - Erkennt Verfügbarkeitsanfragen
- `handleAvailabilityRequest()` - Verarbeitet Verfügbarkeitsanfragen

## Queue Jobs

### SyncEventTypesJob
- Asynchrone Synchronisation von Event-Types
- Retry-Mechanismus (3 Versuche)
- Timeout: 5 Minuten

### BulkAssignStaffToEventTypesJob
- Bulk-Zuordnung von Mitarbeitern
- Batch-Processing (100 pro Batch)
- Transaktionale Sicherheit

### PrecacheAvailabilityJob
- Vorab-Caching von Verfügbarkeiten
- Cache für 15 Minuten
- Separate Caches pro Mitarbeiter

## Artisan Commands

```bash
# Synchronisiere Event-Types
php artisan calcom:sync-event-types [company_id]

# Mit Queue
php artisan calcom:sync-event-types --queue

# Force Sync (ignoriert letzte Sync-Zeit)
php artisan calcom:sync-event-types --force
```

## Test-Daten

### EventManagementTestSeeder
Erstellt Testdaten für 3 Branchen:
- **Friseur**: 10 Services, 5 Mitarbeiter, 3 Filialen
- **Arzt**: 8 Services, 4 Mitarbeiter, 2 Filialen  
- **Fitness**: 10 Services, 5 Mitarbeiter, 3 Filialen

```bash
php artisan db:seed --class=EventManagementTestSeeder
```

## Best Practices

### 1. Synchronisation
- Führen Sie die Synchronisation regelmäßig durch (z.B. stündlich via Cron)
- Nutzen Sie Queue-Jobs für große Datenmengen
- Überwachen Sie die Logs für Fehler

### 2. Zuordnungen
- Nutzen Sie die Matrix-Ansicht für initiale Bulk-Zuordnungen
- Definieren Sie primäre Mitarbeiter für wichtige Services
- Nutzen Sie Custom-Preise sparsam

### 3. Performance
- Cache wird automatisch für 15 Minuten gesetzt
- Nutzen Sie Pre-Caching für häufig angefragte Event-Types
- Invalidieren Sie Cache nach Änderungen

### 4. Fehlerbehandlung
- Standard-Event-Types als Fallback definieren
- Logging aller API-Calls
- Graceful Degradation bei Cal.com Ausfällen

## Troubleshooting

### Problem: Event-Types werden nicht synchronisiert
1. Prüfen Sie Cal.com API Key in Company-Einstellungen
2. Prüfen Sie Logs: `storage/logs/laravel.log`
3. Testen Sie manuell: `php artisan calcom:sync-event-types [company_id]`

### Problem: Mitarbeiter-Zuordnungen werden nicht gespeichert
1. Prüfen Sie ob Mitarbeiter aktiv und buchbar sind
2. Prüfen Sie Browser-Konsole für JavaScript-Fehler
3. Prüfen Sie Netzwerk-Tab für fehlgeschlagene Requests

### Problem: Verfügbarkeiten werden nicht angezeigt
1. Prüfen Sie Cal.com Kalender-Einstellungen
2. Prüfen Sie Mitarbeiter-Zuordnungen
3. Cache löschen: `php artisan cache:clear`

## Zukünftige Erweiterungen

1. **Bidirektionale Synchronisation**: Event-Types in AskProAI erstellen und zu Cal.com pushen
2. **Erweiterte Verfügbarkeitsregeln**: Pufferzeiten, Überschneidungen, etc.
3. **Multi-Calendar Support**: Google Calendar, Outlook Integration
4. **Reporting**: Auslastungsberichte, Buchungsstatistiken
5. **Mobile App**: Native Apps für iOS/Android

## Sicherheit

- Alle API-Keys werden verschlüsselt gespeichert
- Tenant-basierte Isolation
- Rate-Limiting für API-Endpoints
- Audit-Logs für alle Änderungen