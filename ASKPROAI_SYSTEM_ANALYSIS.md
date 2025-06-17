# AskProAI System-Analyse und Vereinfachungsplan

## 1. Aktuelle Datenbankstruktur

Das System hat **119 Tabellen** mit über **95 Migrations** - das ist extrem überkomplex für ein MVP!

### Hauptprobleme:
- **Massive Redundanz**: Mehrere Tabellen für den gleichen Zweck
- **Verwirrende Namensgebung**: Deutsch/Englisch gemischt (kunden/customers, tenants/companies)
- **Unvollständige Migrationen**: Viele "fix_", "add_missing_", "ensure_exists" Migrations
- **Tote Tabellen**: Viele ungenutzte Legacy-Tabellen

### Kern-Tabellen (MUST HAVE):
```
companies (Mandanten)
├── branches (Standorte)
├── staff (Mitarbeiter)
├── services (Dienstleistungen)
├── customers (Kunden)
├── appointments (Termine)
└── calls (Anrufe von Retell.ai)
```

### Redundante Tabellen (DELETE):
- `kunden` → use `customers`
- `tenants` → use `companies`
- `dummy_companies` → test data, remove
- `master_services` + `service_staff` + `staff_services` → consolidate
- `staff_service_assignments` + `staff_service_assignments_backup` → use `staff_event_types`
- `unified_event_types` + `calendar_event_types` → use `calcom_event_types`
- Alle OAuth-Tabellen → nicht genutzt
- Alte Booked-System Tabellen (resources, reservation_*, etc.)

## 2. Service-Layer Chaos

### Calcom Services (7 verschiedene!):
- `CalcomService.php` - V1 API
- `CalcomV2Service.php` - V2 API  
- `CalcomUnifiedService.php` - Wrapper für V1/V2
- `CalcomSyncService.php` - Synchronisation
- `CalcomEventTypeSyncService.php` - Event Type Sync (2x!)
- `CalcomImportService.php` - Import Logic
- `CalService.php` - ???

**LÖSUNG**: Ein Service `CalcomService` mit V2 API only!

### Retell Services (5 verschiedene!):
- `RetellService.php`
- `RetellV2Service.php` 
- `RetellV1Service.php`
- `RetellAIService.php`
- `RetellAgentService.php`

**LÖSUNG**: Ein Service `RetellService`!

## 3. Setup-Flow von A-Z

### Was funktioniert:
1. **Onboarding Wizard** (`OnboardingWizard.php`) - gut strukturiert
2. **Company Setup** - Basis-Daten erfassen
3. **Branch/Staff/Service** - Anlegen funktioniert

### Was fehlt/problematisch ist:

#### Bei Retell.ai (manuell):
1. Account erstellen
2. Agent erstellen mit Prompt
3. Webhook URL eintragen: `https://domain.com/api/retell/webhook`
4. API Key kopieren
5. Agent ID kopieren

**PROBLEM**: Kein automatisches Agent-Setup!

#### Bei Cal.com (manuell):
1. Account erstellen
2. Team erstellen
3. Event Types anlegen
4. API Key generieren
5. Webhook registrieren

**PROBLEM**: Event Type Mapping komplex und fehleranfällig!

#### Im System:
1. API Keys eintragen
2. Event Types importieren
3. Staff zu Event Types zuordnen
4. Arbeitszeiten konfigurieren

**PROBLEM**: Zu viele manuelle Schritte!

## 4. Fehlende Komponenten

### KRITISCH (MUST HAVE):
- [ ] **Automatisches Retell Agent Setup** via API
- [ ] **Webhook-Registrierung** automatisieren
- [ ] **Bezahlsystem** ist nur halb implementiert
- [ ] **Monitoring/Alerting** bei Fehlern
- [ ] **Backup/Recovery** System

### WICHTIG (SHOULD HAVE):
- [ ] **Multi-Language Support** (nur DE vorhanden)
- [ ] **Customer Portal** für Terminverwaltung
- [ ] **SMS/WhatsApp** Benachrichtigungen
- [ ] **Reporting/Analytics** Dashboard
- [ ] **API Rate Limiting** für externe APIs

## 5. Vereinfachungsplan

### PHASE 1: CLEANUP (1 Woche)

#### DELETE:
- Alle ungenutzten Migrations konsolidieren
- Legacy-Tabellen entfernen
- Redundante Services löschen
- Test-Dateien aufräumen (`test_*.php`)

#### CONSOLIDATE:
```php
// Vorher: 7 Calcom Services
CalcomService::class // V2 API only
CalcomWebhookService::class // Webhook handling

// Vorher: 5 Retell Services  
RetellService::class // Unified API
RetellWebhookService::class // Webhook handling
```

### PHASE 2: CORE MVP (2 Wochen)

#### Datenbank-Schema (neu):
```sql
-- Kern-Tabellen only
companies
branches  
staff
services
customers
appointments
calls

-- Integration
api_credentials (für alle APIs)
webhooks (unified webhook log)
event_type_mappings

-- Billing
subscriptions
invoices
usage_tracking
```

#### Setup-Flow vereinfachen:
```php
class SetupWizard {
    // Step 1: Company & Branch
    // Step 2: Connect Retell (mit Auto-Agent-Setup!)
    // Step 3: Connect Cal.com (mit Auto-Import!)
    // Step 4: Test Call
    // DONE!
}
```

### PHASE 3: AUTOMATION (2 Wochen)

#### Auto-Setup für Retell:
```php
class RetellAutoSetup {
    public function createAgent($company) {
        // 1. Agent via API erstellen
        // 2. Prompt konfigurieren
        // 3. Webhook registrieren
        // 4. Phone Number zuweisen
        return $agentId;
    }
}
```

#### Auto-Setup für Cal.com:
```php
class CalcomAutoSetup {
    public function setupTeam($company) {
        // 1. Team erstellen/finden
        // 2. Standard Event Types anlegen
        // 3. Webhook registrieren
        // 4. Staff-Kalender verknüpfen
    }
}
```

### PHASE 4: PRODUCTION READY (1 Woche)

- [ ] Monitoring Dashboard
- [ ] Error Recovery
- [ ] Backup System
- [ ] Rate Limiting
- [ ] Security Audit

## 6. Klare Prioritäten

### MUST HAVE (MVP):
1. **Anruf → Termin Flow** muss 100% funktionieren
2. **Einfaches Onboarding** (max. 10 Minuten)
3. **Basis-Reporting** (Anrufe, Termine)
4. **Email-Benachrichtigungen**

### NICE TO HAVE (später):
1. Multi-Standort Support
2. Erweiterte Kalenderfunktionen
3. Customer Self-Service Portal
4. SMS/WhatsApp
5. Detaillierte Analytics

### DELETE (überflüssig):
1. OAuth/Passport Authentication
2. Komplexe Permission-Systeme
3. Legacy Booked-Tabellen
4. Redundante Services
5. Test-Scaffolding

## 7. Technische Schulden

### Sofort beheben:
- API Keys im Klartext (!) → Verschlüsselung
- Keine Webhook-Signature-Verifizierung bei allen Endpoints
- SQL-Injection möglich in Raw Queries
- Fehlende Indexes auf wichtigen Spalten

### Migration-Strategie:
1. Neue saubere Migrations erstellen
2. Daten-Migration Script
3. Alte Migrations archivieren
4. Fresh Install testen

## 8. Empfohlene Architektur

```
app/
├── Models/          # Nur Kern-Models
├── Services/
│   ├── Core/       # Business Logic
│   ├── Integration/ # External APIs
│   └── Support/    # Helpers
├── Http/
│   ├── Controllers/
│   └── Webhooks/   # Unified Webhook Handling
└── Jobs/           # Async Processing
```

## Fazit

Das System ist aktuell **overengineered** für ein MVP. Mit konsequenter Vereinfachung kann es in 4-6 Wochen zu einem stabilen, wartbaren System werden.

**Kernprinzip**: Lieber wenige Features die 100% funktionieren, als viele die halb fertig sind!