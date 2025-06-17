# Performance Critical Indexes Migration

## Priorität: HOCH 🔴

## Problemstellung
Die Datenbank hat fehlende Performance-kritische Indizes, was zu langsamen Queries führt. Basierend auf einer Analyse der häufigsten Query-Patterns wurden kritische fehlende Indizes identifiziert.

## To-Do Liste

### 1. Migration erstellen ✅
- [x] Analyse der häufigsten Query-Patterns durchgeführt
- [x] Fehlende Indizes identifiziert:
  - [x] company_id in allen tenant-spezifischen Tabellen
  - [x] phone_number in customers
  - [x] status, created_at für Reporting
  - [x] Composite Indizes für häufige WHERE Kombinationen
  - [x] Foreign Key Indizes
  - [x] Zeitbasierte Query Indizes (start_time, end_time)
- [x] Migration `2025_06_17_add_performance_critical_indexes.php` erstellt
- [x] Index-Existenz-Check implementiert (verhindert Duplikate)

### 2. Implementierte Indizes ✅

#### Appointments Table (Kritischste Tabelle)
- [x] company_id - Tenant Isolation
- [x] starts_at, ends_at - Zeitbasierte Queries
- [x] status - Status-Filter
- [x] customer_id, branch_id, staff_id, service_id - Foreign Keys
- [x] calcom_booking_id, calcom_v2_booking_id - Cal.com Integration
- [x] Composite: (company_id, starts_at) - Häufigste Query
- [x] Composite: (company_id, status)
- [x] Composite: (status, starts_at)
- [x] Composite: (branch_id, starts_at)
- [x] Composite: (staff_id, starts_at)
- [x] reminder_24h_sent_at - Reminder Processing

#### Calls Table (Zweithäufigste)
- [x] company_id - Tenant Isolation
- [x] created_at, start_timestamp - Zeitbasierte Queries
- [x] from_number, to_number - Phone Lookups
- [x] call_status - Status Filter
- [x] retell_call_id (UNIQUE) - API Integration
- [x] call_id - Call Tracking
- [x] customer_id, appointment_id - Relations
- [x] Composite: (company_id, created_at)
- [x] Composite: (customer_id, created_at)
- [x] Composite: (company_id, call_status)
- [x] duration_sec, cost - Performance Metrics

#### Customers Table
- [x] company_id - Tenant Isolation
- [x] phone - Critical für Call Matching
- [x] email - Email Lookups
- [x] Composite: (company_id, phone) - Duplicate Detection
- [x] Composite: (company_id, email)
- [x] name - Search Optimization

#### Weitere Tabellen
- [x] branches: company_id, is_active, (company_id, is_active)
- [x] staff: company_id, branch_id, email, (company_id, branch_id)
- [x] services: company_id, deleted_at, (company_id, deleted_at)
- [x] companies: is_active, deleted_at, subdomain (UNIQUE)
- [x] users: company_id, email (UNIQUE)

#### Integration Tables
- [x] calcom_event_types: company_id, calcom_id, (company_id, calcom_id) UNIQUE
- [x] staff_event_types: staff_id, calcom_event_type_id, UNIQUE Constraint
- [x] working_hours: staff_id, day_of_week, (staff_id, day_of_week)
- [x] calcom_bookings: company_id, calcom_id, uid, starts_at
- [x] activity_log: created_at, causer, subject, event

### 3. Performance Analyse Tool ✅
- [x] Command zum Analysieren von Query-Performance implementiert
- [x] Dokumentation für Performance-Analyse erstellt

### 4. Dokumentation ✅
- [x] PERFORMANCE_INDEXES_MIGRATION.md erstellt
- [x] Erklärung warum jeder Index wichtig ist
- [x] Erwartete Performance-Verbesserungen dokumentiert
- [x] Monitoring-Anleitungen hinzugefügt
- [x] Rollback-Prozedur dokumentiert

### 5. Testing & Deployment
- [ ] Migration in Test-Umgebung ausführen
- [ ] Performance-Metriken vor Migration erfassen
- [ ] Migration ausführen: `php artisan migrate`
- [ ] Performance-Metriken nach Migration vergleichen
- [ ] Query Execution Plans mit EXPLAIN prüfen
- [ ] Monitoring für die nächsten 24h einrichten

### 6. Post-Deployment
- [ ] Slow Query Log überwachen
- [ ] Index-Usage-Statistiken prüfen
- [ ] Ggf. weitere Composite Indizes basierend auf tatsächlicher Nutzung hinzufügen
- [ ] Table Statistics aktualisieren: `ANALYZE TABLE`

## Review

### Was wurde erreicht:

1. **Umfassende Index-Migration erstellt**:
   - 60+ neue Indizes für Performance-Optimierung
   - Alle kritischen Query-Patterns abgedeckt
   - Duplicate-Check verhindert Fehler bei Re-Run

2. **Schwerpunkte der Optimierung**:
   - **Tenant Isolation**: company_id Indizes überall
   - **Zeitbasierte Queries**: Optimiert für Dashboard und Reports
   - **Phone Lookups**: Kritisch für eingehende Anrufe
   - **Composite Indizes**: Für häufige Query-Kombinationen

3. **Erwartete Verbesserungen**:
   - Appointment Listings: 50-90% schneller
   - Customer Phone Lookup: 80-95% schneller
   - Dashboard Statistics: 60-80% schneller
   - Call History: 70-85% schneller

4. **Best Practices implementiert**:
   - Index-Existenz wird geprüft (idempotent)
   - Down-Method für sauberes Rollback
   - Dokumentation für Operations Team

### Nächste Schritte:

1. **Backup der Datenbank erstellen**
2. **Migration in Staging testen**
3. **Performance-Baseline erfassen**
4. **Migration in Production (off-peak)**
5. **Performance-Monitoring aktivieren**