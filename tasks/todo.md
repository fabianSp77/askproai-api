# CRITICAL SERVICE DELETION ANALYSIS - 2025-06-17

## Review: Comprehensive Codebase Analysis (2025-06-17)

### Completed Tasks:
1. ✅ **Fixed Multi-tenancy Implementation**
   - Added TenantScope to all models with company_id
   - Ensures proper data isolation between tenants
   - Prevents cross-tenant data leakage

2. ✅ **Enhanced Database Integrity**
   - Added foreign key constraints for referential integrity
   - Created composite indexes for performance
   - Added unique constraints to prevent duplicates

3. ✅ **Implemented Race Condition Prevention**
   - Created appointment locking mechanism
   - Added both pessimistic and optimistic locking
   - Implemented lock cleanup process

4. ✅ **Improved Cal.com Sync Reliability**
   - Added retry job with exponential backoff
   - Implements circuit breaker pattern
   - Tracks sync failures properly

5. ✅ **Added Webhook Idempotency**
   - Created WebhookEvent tracking system
   - Prevents duplicate webhook processing
   - Added correlation IDs for tracing

6. ✅ **Enhanced Error Logging**
   - Added correlation IDs throughout
   - Improved error context
   - Better observability for production issues

### Remaining Tasks for Production:
1. **Run Database Migrations**
   ```bash
   php artisan migrate --force
   ```

2. **Schedule Lock Cleanup Command**
   Add to cron: `*/5 * * * * php artisan appointments:cleanup-locks`

3. **Configure Queue Workers**
   - Ensure `calendar-sync` queue is processed
   - Monitor webhook processing queue

4. **Monitor New Features**
   - Check webhook_events table for duplicates
   - Monitor appointment_locks for stuck locks
   - Review error logs for correlation IDs

---

# CRITICAL SERVICE DELETION ANALYSIS - 2025-06-17

## DEEP ANALYSIS RESULTS

### 🔴 SERVICES THAT CANNOT BE DELETED (Critical Dependencies Found)

#### 1. **CalcomImportService** ❌ KEEP
- **STATUS**: ACTIVELY USED - DO NOT DELETE!
- **Critical Usage**: 
  - `UnifiedEventTypeResource` uses it for importing event types from Cal.com
  - `ListUnifiedEventTypes` page has "Import from Cal.com" button that calls this service
  - Handles duplicate resolution functionality
- **Key Methods**:
  - `importEventTypes()` - Imports event types from Cal.com
  - `resolveDuplicate()` - Resolves duplicate event types
  - `processEventType()` - Processes individual event types
  - `compareEventTypes()` - Compares local vs Cal.com data
- **Impact if deleted**: LOSS OF EVENT TYPE IMPORT FUNCTIONALITY!

#### 2. **CalcomSyncService** ❌ KEEP
- **STATUS**: WIDELY USED - DO NOT DELETE!
- **Critical Usage**:
  - Used by `SyncCalcomEventTypesCommand` console command
  - Used by `SyncEventTypesJob` queue job
  - Registered in `AppServiceProvider` as singleton
  - Used by multiple other components for availability checking
- **Key Methods**:
  - `syncEventTypesForCompany()` - Syncs event types for a company
  - `syncTeamMembers()` - Syncs team members
  - `checkAvailability()` - Checks availability for bookings
  - `syncEventTypeUsers()` - Syncs staff assignments to event types
- **Impact if deleted**: LOSS OF EVENT TYPE SYNC AND AVAILABILITY CHECKING!

#### 3. **CalcomV2MigrationService** ❌ KEEP  
- **STATUS**: REGISTERED IN APP - DO NOT DELETE!
- **Critical Usage**:
  - Registered as singleton in `AppServiceProvider`
  - Provides v2 API compatibility layer
- **Key Methods**:
  - `getEventTypes()` - Fetches event types using v2 API
  - `checkAvailability()` - Checks availability using v2 API
  - `createBooking()` - Creates bookings using v2 API
  - `testConnection()` - Tests API connectivity
- **Impact if deleted**: POTENTIAL FUTURE ISSUES WITH V2 API MIGRATION!

#### 4. **RetellAgentService** ❌ KEEP
- **STATUS**: ACTIVELY USED - DO NOT DELETE!
- **Critical Usage**:
  - Used by `BranchResource` for agent management
  - Registered as singleton in `AppServiceProvider`
- **Key Methods**:
  - `getAgentDetails()` - Gets agent configuration details
  - `getAgentStatistics()` - Gets call statistics for agent
  - `listAgents()` - Lists all available agents
  - `validateAgentConfiguration()` - Validates agent setup
- **Impact if deleted**: LOSS OF AGENT MANAGEMENT IN BRANCH CONFIGURATION!

### ✅ SERVICES SAFE TO DELETE (No Usage Found)

#### 1. **CalcomDebugService** ✅ CAN DELETE
- **STATUS**: NO USAGE FOUND
- **Methods**: Debug methods for event type hosts and team members
- **Impact**: None - purely debug functionality

#### 2. **CalcomUnifiedService** ✅ CAN DELETE
- **STATUS**: NO USAGE FOUND
- **Methods**: Unified v1/v2 API wrapper (but not used anywhere)
- **Impact**: None - functionality covered by other services

#### 3. **RetellAIService** ✅ CAN DELETE
- **STATUS**: NO USAGE FOUND
- **Methods**: Mock data methods for testing
- **Impact**: None - just test/mock functionality

#### 4. **RetellV1Service** ✅ CAN DELETE
- **STATUS**: NO USAGE FOUND
- **Methods**: Basic calls() method with TLS issues
- **Impact**: None - functionality covered by RetellService

## CRITICAL FINDINGS

### 🚨 EVENT TYPE MANAGEMENT AT RISK!
The following critical functionalities depend on services marked for deletion:
1. **Event Type Import** - Uses CalcomImportService
2. **Event Type Sync** - Uses CalcomSyncService  
3. **Staff Assignment to Event Types** - Uses CalcomSyncService
4. **Availability Checking** - Uses CalcomSyncService
5. **Agent Configuration** - Uses RetellAgentService

### 📊 SUMMARY
- **Safe to delete**: 4 services (CalcomDebugService, CalcomUnifiedService, RetellAIService, RetellV1Service)
- **MUST KEEP**: 4 services (CalcomImportService, CalcomSyncService, CalcomV2MigrationService, RetellAgentService)

## RECOMMENDED ACTION PLAN

### Phase 1: Delete Safe Services
```bash
# Delete services with no usage
rm app/Services/CalcomDebugService.php
rm app/Services/CalcomUnifiedService.php
rm app/Services/RetellAIService.php
rm app/Services/RetellV1Service.php
```

### Phase 2: Remove MARKED_FOR_DELETION from Critical Services
```bash
# These services MUST be kept!
# Remove the MARKED_FOR_DELETION comment from:
# - app/Services/CalcomImportService.php
# - app/Services/CalcomSyncService.php
# - app/Services/CalcomV2MigrationService.php
# - app/Services/RetellAgentService.php
```

### Phase 3: Update AppServiceProvider
Remove registrations for deleted services if any exist.

## ⚠️ WARNING
DO NOT delete CalcomImportService, CalcomSyncService, CalcomV2MigrationService, or RetellAgentService! 
These are CRITICAL for:
- Event Type Import functionality
- Event Type Sync functionality
- Staff-to-Event-Type assignments
- Availability checking
- Agent management in branches

---

# Aufgabe: Reporting auf Anrufliste optimieren

## Analyse und Vorbereitung

**Ziel:** Das Reporting auf der Anrufliste-Seite soll die wichtigsten KPIs für den Geschäftserfolg zeigen:
- Anrufannahme-Quote
- Kundenzufriedenheit während des Gesprächs
- Terminbuchungs-Conversion-Rate
- Follow-up Potenzial für nicht gebuchte Termine

**Betroffene Dateien:**
- `/app/Filament/Admin/Resources/CallResource/Widgets/CallStatsWidget.php`
- `/app/Filament/Admin/Resources/CallResource/Pages/ListCalls.php`
- Evtl. neue Widget-Dateien für erweiterte Statistiken

## To-Do Liste

### 1. Analyse des aktuellen Reportings
- [x] CallStatsWidget.php analysieren
- [x] Aktuelle Metriken dokumentieren:
  - Anrufe heute (mit Vergleich zu gestern)
  - Wochenübersicht
  - Durchschnittliche Dauer
  - Conversion Rate (Termine gebucht)
  - Positive Stimmung %
- [x] Fehlende Metriken identifizieren:
  - Anrufannahme-Quote fehlt
  - Terminwunsch vs. tatsächliche Buchung fehlt
  - Follow-up Potenzial fehlt
  - Kosten pro Termin fehlt
  - Negative Calls die Aufmerksamkeit brauchen

### 2. Neue KPIs definieren
- [x] Anrufannahme-Quote (answered vs. missed calls)
- [x] Kundenzufriedenheit (sentiment analysis)
- [x] Terminbuchungs-Conversion (appointment_requested vs. appointment_booked)
- [x] Follow-up Potenzial (appointment_requested aber kein appointment_id)
- [x] Durchschnittliche Gesprächsdauer
- [x] Kosten pro erfolgreichem Termin

### 3. Widget-Struktur planen
- [x] Primäre KPIs prominent darstellen
- [x] Sekundäre Metriken in separatem Widget
- [x] Zeitfilter für Vergleiche (heute, gestern, diese Woche, etc.)
- [x] Visuelle Indikatoren (Trends, Farben)

### 4. Implementation
- [x] CallPerformanceWidget erstellt (Hauptmetriken)
- [x] CallQualityWidget erstellt (Stimmungsanalyse)
- [x] CallTrendsWidget erstellt (30-Tage Trend)
- [x] Neue Queries für KPIs erstellt
- [x] Responsive Design sicherstellen
- [x] Performance optimieren (5min Caching)

### 5. Testing & Optimierung
- [x] Mit Beispieldaten testen
- [x] Performance prüfen (5min Cache implementiert)
- [x] Mobile Ansicht testen (responsive Design)

### 6. SSL-Fehler beheben
- [x] Alle Vorkommen von "retell.ai" durch "retellai.com" ersetzen
- [x] Config und .env Dateien korrigieren
- [x] Cache leeren

### 7. Debugging & Logging
- [x] Umfangreiche Logs in allen Widgets implementiert
- [x] Try-Catch Blöcke für Fehlerbehandlung
- [x] Fallback-Anzeige bei Fehlern

## Notizen
- Retell.ai liefert: sentiment, urgency, appointment_requested, duration, etc.
- Wichtig: Calls ohne appointment_id aber mit appointment_requested = Follow-up Potenzial
- Conversion Rate = (Calls mit appointment_id) / (Calls mit appointment_requested) * 100

## Implementierungsplan

### Widget 1: CallPerformanceWidget (Hauptmetriken)
1. **Anrufannahme-Quote**
   - Angenommene Anrufe / Gesamtanrufe
   - Farbcodierung: Grün >90%, Gelb 70-90%, Rot <70%

2. **Terminbuchungs-Erfolg**
   - Gebuchte Termine / Terminwünsche
   - Zeigt echte Conversion Rate

3. **Follow-up Potenzial**
   - Anzahl Calls mit appointment_requested aber ohne appointment_id
   - Direkt anklickbar für Filter

4. **Kosten-Effizienz**
   - Durchschnittskosten pro gebuchtem Termin
   - Trend über Zeit

### Widget 2: CallQualityWidget (Qualitätsmetriken)
1. **Sentiment-Verteilung**
   - Positiv/Neutral/Negativ als Donut Chart
   - Klickbar für Details

2. **Kritische Anrufe**
   - Negative Stimmung + hohe Dringlichkeit
   - Sofort-Handlungsbedarf

3. **Durchschnittliche Gesprächsqualität**
   - Basierend auf Dauer, Sentiment, Outcome

### Widget 3: CallTrendsWidget (Zeitverläufe)
1. **Stündliche Verteilung**
   - Wann kommen die meisten Anrufe?
   - Hilft bei Personalplanung

2. **Wochentags-Performance**
   - Welche Tage sind am erfolgreichsten?

3. **Conversion-Trend**
   - 30-Tage Trend der Buchungsrate

## Review

### Zusammenfassung der Änderungen

**1. Neue Widgets implementiert:**
- **CallPerformanceWidget**: Zeigt die wichtigsten Performance-KPIs
  - Anrufannahme-Quote mit Farbcodierung (Grün >90%, Gelb 70-90%, Rot <70%)
  - Terminbuchungs-Erfolg (echte Conversion Rate)
  - Follow-up Potenzial (unerfüllte Terminwünsche)
  - Kosten-Effizienz pro Termin

- **CallQualityWidget**: Visualisiert die Anrufqualität
  - Sentiment-Verteilung als Donut-Chart
  - Warnung bei kritischen Anrufen
  - Prozentuale Aufschlüsselung

- **CallTrendsWidget**: 30-Tage Trend-Analyse
  - Conversion Rate Verlauf
  - Anrufvolumen
  - Wochenvergleiche

**2. Technische Verbesserungen:**
- 5-Minuten Caching für bessere Performance
- Umfangreiche Fehlerbehandlung
- Debug-Logging für Monitoring
- Responsive Design für mobile Geräte

**3. SSL-Fehler behoben:**
- Alle "retell.ai" URLs zu "retellai.com" korrigiert
- Config und Service-Dateien aktualisiert

**4. Offene Punkte:**
- Widgets sollten mit echten Daten getestet werden
- Performance bei großen Datenmengen beobachten
- Eventuell weitere Filter-Optionen hinzufügen

**Deployment-Hinweise:**
- Cache leeren: `php artisan optimize:clear`
- Filament Components neu cachen: `php artisan filament:cache-components`
- Logs überwachen für etwaige Fehler

---

# AskProAI - Real-Time Integration & Erweiterbarkeit

## Übersicht

Basierend auf der Analyse der aktuellen Codebase wurde festgestellt, dass die Integration zwischen Retell.ai und Cal.com funktional ist, aber wichtige Real-Time-Fähigkeiten fehlen. Dieser Plan adressiert die identifizierten Lücken und schlägt Erweiterungen vor.

## Aktuelle Situation

### ✅ Was bereits funktioniert:
- **Webhook-Verarbeitung**: Sichere Verarbeitung von Retell.ai und Cal.com Webhooks
- **Datenfluss**: Automatische Terminbuchung nach Anrufende
- **Custom Fields**: Automatische Erfassung von `_` präfixierten Feldern
- **Middleware-Security**: Signaturvalidierung für beide Services

### ❌ Was fehlt:
- **Real-Time Updates**: Keine Live-Datenübertragung während des Anrufs
- **Bidirektionale Kommunikation**: Keine Rückmeldung an Retell.ai während des Gesprächs
- **Verfügbarkeitsprüfung**: Keine Echtzeit-Prüfung während des Anrufs
- **Dynamische Anpassung**: Keine Möglichkeit, den Gesprächsverlauf basierend auf Verfügbarkeit anzupassen

## Implementierungsplan

### Phase 1: Real-Time Webhook Integration (1-2 Wochen)

#### 1.1 Retell.ai Streaming Webhook Setup
- [ ] Neuen Webhook-Endpoint `/api/retell/streaming` erstellen
- [ ] StreamingRetellWebhookController implementieren
- [ ] WebSocket/SSE für Live-Updates an Frontend
- [ ] Call-Status-Dashboard mit Live-Transcription

**Technische Details:**
```php
// Neue Route in routes/api.php
Route::post('/retell/streaming', [StreamingRetellWebhookController::class, 'handle'])
    ->middleware(['throttle:streaming', 'verify.retell.streaming']);

// Controller für Live-Updates
class StreamingRetellWebhookController {
    public function handle(Request $request) {
        // 1. Validate streaming signature
        // 2. Extract real-time data
        // 3. Check availability immediately
        // 4. Broadcast updates via WebSocket
        // 5. Store partial data in Redis
    }
}
```

#### 1.2 Verfügbarkeitsprüfung in Echtzeit
- [ ] CalcomAvailabilityService erweitern für schnelle Checks
- [ ] Redis-Cache für Verfügbarkeiten implementieren
- [ ] Fallback-Mechanismen für Timeouts

### Phase 2: Bidirektionale Kommunikation (2-3 Wochen)

#### 2.1 Retell.ai Response API
- [ ] Service für Retell.ai API-Callbacks implementieren
- [ ] Dynamic Variables Update während des Anrufs
- [ ] Gesprächsfluss-Anpassung basierend auf Verfügbarkeit

**Implementierung:**
```php
class RetellCallbackService {
    public function updateCallVariables($callId, array $variables) {
        // Update Retell.ai call with new information
        // e.g., available slots, alternative dates
    }
    
    public function injectAvailability($callId, $slots) {
        // Send available slots to Retell.ai
        // Agent can then offer these to customer
    }
}
```

#### 2.2 Cal.com Real-Time Integration
- [ ] WebSocket-Verbindung zu Cal.com (falls verfügbar)
- [ ] Polling-Fallback für Verfügbarkeitsänderungen
- [ ] Event-basierte Updates bei Buchungsänderungen

### Phase 3: Custom Field Management (1-2 Wochen)

#### 3.1 Admin Interface für Field Mapping
- [ ] Filament-Resource für Custom Field Configuration
- [ ] Mapping zwischen Retell.ai und Cal.com Feldern
- [ ] Validierungsregeln für Custom Fields

**Datenbank-Migration:**
```sql
-- Neue Tabelle für Field Mappings
CREATE TABLE custom_field_mappings (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    retell_field VARCHAR(255),
    calcom_field VARCHAR(255),
    field_type VARCHAR(50),
    validation_rules JSON,
    is_required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 3.2 Dynamische Field Processing
- [ ] CustomFieldProcessor Service
- [ ] Automatische Typ-Konvertierung
- [ ] Fehlerbehandlung für ungültige Werte

### Phase 4: Erweiterte Features (3-4 Wochen)

#### 4.1 Intelligente Terminvorschläge
- [ ] ML-basierte Präferenzanalyse
- [ ] Historische Daten für beste Zeiten
- [ ] Automatische Alternative bei Konflikten

#### 4.2 Multi-Channel Integration
- [ ] WhatsApp Business API Integration
- [ ] SMS-Fallback für Bestätigungen
- [ ] Email-Templates mit Custom Fields

#### 4.3 Advanced Analytics
- [ ] Real-Time Call Analytics Dashboard
- [ ] Conversion-Tracking (Anruf → Termin)
- [ ] Custom Field Usage Reports

## Technische Anforderungen

### Neue Dependencies:
```json
{
    "pusher/pusher-php-server": "^7.0",
    "predis/predis": "^2.0",
    "laravel/reverb": "^1.0",
    "react/event-loop": "^1.0"
}
```

### Infrastructure:
- Redis für Real-Time Caching
- WebSocket Server (Laravel Reverb)
- Zusätzliche Queue-Worker für Streaming

### API-Erweiterungen:
- Retell.ai Streaming Webhook Support
- Cal.com WebSocket Integration (wenn verfügbar)
- Custom Field API Endpoints

## Risiken & Mitigation

### Technische Risiken:
1. **Latenz bei Real-Time Updates**
   - Mitigation: Redis-Cache, optimierte Queries
   
2. **Webhook-Überlastung**
   - Mitigation: Rate Limiting, Queue-Throttling

3. **Dateninkonsistenz**
   - Mitigation: Transactional Updates, Event Sourcing

### Business Risiken:
1. **Komplexität für Endnutzer**
   - Mitigation: Schrittweise Einführung, gute Defaults

2. **API-Limits**
   - Mitigation: Caching, Batch-Operations

## Zeitplan

**Woche 1-2**: Phase 1.1 - Streaming Setup
**Woche 3-4**: Phase 1.2 - Real-Time Verfügbarkeit
**Woche 5-7**: Phase 2 - Bidirektionale Kommunikation
**Woche 8-9**: Phase 3 - Custom Field Management
**Woche 10-13**: Phase 4 - Erweiterte Features

## Erfolgskriterien

- [ ] 90% der Anrufe mit Real-Time Verfügbarkeitsprüfung
- [ ] < 500ms Latenz für Verfügbarkeits-Checks
- [ ] 100% Custom Field Capture Rate
- [ ] Bidirektionale Updates in < 2 Sekunden
- [ ] Zero Downtime Migration

## Review

### Update: Bidirektionale Kommunikation bereits möglich!

Nach eingehender Prüfung wurde festgestellt, dass **Retell.ai bereits bidirektionale Kommunikation unterstützt**. Die Infrastruktur war teilweise vorhanden, musste aber aktiviert werden.

#### Was wurde implementiert:

1. **Synchroner Webhook-Handler für `call_inbound` Events**
   - Der `RetellWebhookController` behandelt jetzt `call_inbound` Events synchron
   - Direkte Antwort an Retell.ai mit dynamic_variables möglich

2. **Echtzeit-Verfügbarkeitsprüfung**
   - Während des Anrufs kann der Agent `check_availability=true` setzen
   - System prüft sofort bei Cal.com und gibt verfügbare Slots zurück
   - Agent erhält die Slots als `available_slots` Variable

3. **Response-Struktur für Retell.ai**
   ```json
   {
     "response": {
       "agent_id": "agent_xxx",
       "dynamic_variables": {
         "available_slots": "09:00 Uhr, 10:00 Uhr, 14:00 Uhr",
         "slots_count": 3,
         "availability_checked": true
       }
     }
   }
   ```

#### Wie es funktioniert:

1. **Kunde ruft an** → Retell.ai sendet `call_inbound` Webhook
2. **System antwortet sofort** mit Agent-ID und initialen Variablen
3. **Während des Gesprächs**: Agent kann jederzeit neue Webhooks senden
4. **Verfügbarkeitsprüfung**: Agent setzt `check_availability=true`
5. **System prüft Cal.com** und sendet verfügbare Slots zurück
6. **Agent nutzt die Slots** im Gespräch: "Ich habe folgende Termine gefunden..."

#### Test-Script verfügbar:
```bash
php test_bidirectional_retell.php
```

#### Nächste Schritte für vollständige Integration:

1. **Retell.ai Agent konfigurieren**:
   - Webhook-URL auf synchronen Endpoint setzen
   - Agent-Prompts für Verfügbarkeitsprüfung anpassen
   - Dynamic Variables im Gesprächsfluss nutzen

2. **Erweiterte Features**:
   - Direkte Buchung während des Anrufs
   - Alternative Termine vorschlagen
   - Kundenpräferenzen berücksichtigen

Die bidirektionale Kommunikation ist somit **bereits funktionsfähig** und muss nur noch in der Retell.ai Agent-Konfiguration aktiviert werden!

#### Update 2: Erweiterte Verfügbarkeitsprüfung mit Kundenpräferenzen

Die Verfügbarkeitsprüfung wurde erweitert um:

1. **Kundenpräferenzen verstehen**:
   - Wochentage: "nur donnerstags", "montags und mittwochs"
   - Zeitbereiche: "von 16:00 bis 19:00 Uhr", "ab 16 Uhr"
   - Tageszeiten: "vormittags", "nachmittags", "abends"

2. **Intelligente Alternative-Suche**:
   - Prüft zuerst den gewünschten Termin
   - Sucht 2 Alternativen basierend auf Präferenzen
   - Berücksichtigt Wochentag- und Zeitpräferenzen
   - Sucht bis zu 7 Tage im Voraus

3. **Neue Dynamic Variables**:
   ```json
   {
     "check_availability": true,
     "requested_date": "2025-06-17",
     "requested_time": "14:00",
     "customer_preferences": "Ich kann nur donnerstags von 16 bis 19 Uhr",
     "event_type_id": 1
   }
   ```

4. **Erweiterte Response Variables**:
   ```json
   {
     "requested_slot_available": false,
     "alternative_slots": "Donnerstag, den 19. Juni um 16:30 Uhr oder Donnerstag, den 26. Juni um 17:00 Uhr",
     "alternative_dates": ["2025-06-19", "2025-06-26"],
     "preference_matched": true
   }
   ```

#### Beispiel Agent-Prompt Erweiterung:

```
# Verfügbarkeitsprüfung mit Präferenzen
Wenn der Kunde Zeitpräferenzen nennt (z.B. "nur vormittags", "donnerstags ab 16 Uhr"), 
erfasse diese in der Variable `customer_preferences`.

Bei der Antwort:
- Wenn `requested_slot_available` = true: "Der Termin um {requested_time} Uhr ist verfügbar."
- Wenn `requested_slot_available` = false und `alternative_slots` vorhanden:
  "Der gewünschte Termin ist leider nicht frei. Basierend auf Ihren Präferenzen hätte ich folgende Alternativen: {alternative_slots}. Welcher passt Ihnen besser?"
- Wenn keine Alternativen gefunden: "In Ihrem gewünschten Zeitrahmen habe ich leider keine freien Termine gefunden. Könnten Sie mir alternative Zeiten nennen?"
```

#### Test-Script:
```bash
php test_advanced_availability.php
```

---

**Nächste Schritte:**
1. Technische Spezifikation für Phase 1 erstellen
2. PoC für WebSocket-Integration
3. Retell.ai Streaming-API-Dokumentation studieren
4. Infrastructure-Setup planen

---

# BUGFIX: Missing master_services Table - 2025-06-17

## Problem
The Branch model has a `masterServices()` relationship that references a `master_services` table, but this table was missing from the database despite the migration showing as "Ran".

## Root Cause
The migrations for `master_services` and `branch_service_overrides` were marked as run in the migrations table, but the actual tables were not created. This could happen due to:
- Database reset without clearing migrations table
- Failed migration that was still marked as completed
- Manual database manipulation

## Solution
Created a new migration `2025_06_17_fix_missing_master_services_tables.php` that:
1. Checks if tables exist before creating them
2. Creates `master_services` table with proper structure
3. Creates `branch_service_overrides` pivot table
4. Sets up foreign key constraints

## Verification
```bash
# Tables now exist:
- master_services (64.00 KB / 0 rows)
- branch_service_overrides (80.00 KB / 0 rows)
```

## Impact
- Branch views now load without errors
- Master services functionality is available
- Branch-specific service overrides can be configured