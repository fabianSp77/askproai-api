# CLAUDE.md

> 🚀 **Quick Access**: [Quick Reference](./CLAUDE_QUICK_REFERENCE.md) | [Error Patterns](./ERROR_PATTERNS.md) | [Deployment](./DEPLOYMENT_CHECKLIST.md) | [DB Access](#database-credentials) | [MCP Servers](#mcp-server-übersicht-und-verwendung)
> 
> 🆕 **Business-Critical**: [5-Min Onboarding](./5-MINUTEN_ONBOARDING_PLAYBOOK.md) | [Customer Success](./CUSTOMER_SUCCESS_RUNBOOK.md) | [Emergency Response](./EMERGENCY_RESPONSE_PLAYBOOK.md)
> 
> 📊 **Analytics & Monitoring**: [KPI Dashboard](./KPI_DASHBOARD_TEMPLATE.md) | [Health Monitor](./INTEGRATION_HEALTH_MONITOR.md) | [Troubleshooting](./TROUBLESHOOTING_DECISION_TREE.md) | [Data Flow](./PHONE_TO_APPOINTMENT_FLOW.md)
>
> 🎯 **BEST PRACTICES 2025**: [Context Summary](./CLAUDE_CONTEXT_SUMMARY.md) | [Best Practices](./BEST_PRACTICES_IMPLEMENTATION.md) | [Dev Process](./DEVELOPMENT_PROCESS_2025.md)

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 📑 Inhaltsverzeichnis

### 🆘 AKTUELLE BLOCKER (Stand: 2025-06-28)
- **Multi-Branch Implementation Issue** - Branch Selector verursacht Livewire/Blade Fehler
  - Details: [MULTI_BRANCH_IMPLEMENTATION_STATUS_2025-06-27.md](./MULTI_BRANCH_IMPLEMENTATION_STATUS_2025-06-27.md)
  - Problem: Global Branch Selector Dropdown funktioniert nicht
  - Workaround: Branch Selector als separate Page verfügbar

### 🚨 KRITISCH: Retell.ai Integration nach Context Reset
**Problem**: Retell Integration funktioniert nicht mehr nach Context Reset
**Lösung**: 
```bash
# Quick Fix - Führe diese Befehle aus:
./retell-quick-setup.sh

# Oder manuell:
php retell-health-check.php  # Prüft und repariert automatisch
php sync-retell-agent.php    # Synchronisiert Agent-Konfiguration
php fetch-retell-calls.php   # Importiert Anrufe
```

**Wichtige Dokumentationen**:
- [RETELL_COMPLETE_SYSTEM_DOCUMENTATION_2025-06-29.md](./RETELL_COMPLETE_SYSTEM_DOCUMENTATION_2025-06-29.md) - Vollständige Systemdokumentation
- [RETELL_INTEGRATION_COMPLETE_2025-06-29.md](./RETELL_INTEGRATION_COMPLETE_2025-06-29.md) - Integration Status
- [RETELL_INTEGRATION_CRITICAL.md](./RETELL_INTEGRATION_CRITICAL.md) - Kritische Infos

### 🔴 Kritisch (Täglich benötigt)
- [Essential Commands](#essential-commands)
- [Database Credentials](#database-credentials)
- [Common Issues & Solutions](#common-issues--solutions)
- [Troubleshooting Retell.ai](#troubleshooting-retellai-integration)

### 🟡 Wichtig (Regelmäßig benötigt)
- [Workflow für neue Aufgaben](#workflow-für-neue-aufgaben)
- [MCP-Server Übersicht](#mcp-server-übersicht-und-verwendung)
- [Testing Approach](#testing-approach)
- [Environment Configuration](#environment-configuration)

### 🟢 Referenz (Bei Bedarf)
- [Project Overview](#project-overview)
- [Architecture Overview](#architecture-overview)
- [Business Logic & Workflow](#business-logic--workflow)
- [Security & Monitoring](#security--monitoring)
- [Performance Considerations](#performance-considerations)

### 📋 Archiv & Historie
- [Kritische Verständnispunkte (Juni 2025)](#kritische-verständnispunkte-juni-2025)
- [Kritische Blocker (Stand 2025-06-17)](#kritische-blocker-stand-2025-06-17)

---

## 🔴 Database Credentials

```bash
# MySQL/MariaDB Access (Verifiziert am 2025-06-18)
# Application User (verwendet von Laravel)
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db

# Root User
mysql -u root -p'V9LGz2tdR5gpDQz'

# Connection Details
Host: 127.0.0.1
Port: 3306
Database: askproai_db
```

**SSH Access**:
```bash
ssh hosting215275@hosting215275.ae83d.netcup.net
ssh root@hosting215275.ae83d.netcup.net
```

---

## 🚀 Best Practices & Automation (NEU 2025)

### Automatische MCP-Server Nutzung
```php
// Automatisch den besten MCP-Server finden und nutzen
$discovery = app(MCPAutoDiscoveryService::class);
$result = $discovery->executeTask('book appointment for tomorrow', $params);

// In Services mit UsesMCPServers Trait
$this->executeMCPTask('fetch customer data', ['phone' => $phoneNumber]);
```

### Data Flow Tracking
```php
// Jeden API-Call automatisch tracken
$correlationId = $this->dataFlowLogger->startFlow('webhook_incoming', 'retell', 'internal');
// ... API calls werden automatisch getrackt
$this->dataFlowLogger->completeFlow($correlationId);
```

### System Understanding & Impact Analysis
```bash
# Vor Änderungen: System verstehen
php artisan analyze:component App\\Services\\BookingService

# Vor Deployment: Impact analysieren
php artisan analyze:impact --git
```

### Neue Essential Commands
```bash
# MCP Discovery
php artisan mcp:discover "beschreibe deine aufgabe"
php artisan mcp:discover "create appointment" --execute

# Impact Analysis
php artisan analyze:impact --component=ServiceName
php artisan analyze:impact --git

# Documentation Health
php artisan docs:check-updates
php artisan docs:check-updates --auto-fix

# Code Quality
composer quality        # Alles auf einmal
composer pint          # Code formatieren
composer stan          # Static Analysis
composer test:coverage # Tests mit Coverage

# Quick Setup
composer setup         # Komplettes Setup inkl. Git Hooks
```

---

## Project Overview

AskProAI is an AI-powered SaaS platform that automatically answers incoming customer calls and independently schedules appointments. Through the integration of phone AI (Retell.ai) and online calendar system (Cal.com), it creates a seamless end-to-end solution for appointment bookings, relieving service companies from manual appointment management.

### Core Value Proposition
- **24/7 Availability**: AI answers calls round the clock, never missing a potential appointment
- **Automatic Booking**: Converts phone conversations directly into calendar appointments
- **Multi-tenant Architecture**: Supports multiple independent businesses with data isolation
- **Multi-location Support**: Manages companies with multiple branches/locations
- **German Market Focus**: Optimized for German-speaking businesses and customers

### Target Industries
- Medical practices (doctors, therapists, physiotherapists)
- Beauty salons and barbershops
- Veterinary clinics
- Legal offices and consultancies
- Any appointment-based service business

## 🔄 Automatische Dokumentations-Aktualisierung

### Wann wird geprüft?
Bei jeder Änderung von:
- **Services** → ERROR_PATTERNS.md, TROUBLESHOOTING_DECISION_TREE.md
- **MCP-Server** → CLAUDE.md (MCP-Sektion), INTEGRATION_HEALTH_MONITOR.md
- **API-Routes** → PHONE_TO_APPOINTMENT_FLOW.md, ERROR_PATTERNS.md
- **Konfiguration** → DEPLOYMENT_CHECKLIST.md, CLAUDE.md
- **Migrationen** → DEPLOYMENT_CHECKLIST.md

### Automatische Prüfung:
```bash
# Nach jedem Commit automatisch
git commit -m "feat: Neues Feature"
# → 🔍 Prüfe ob Dokumentation aktualisiert werden muss...

# Manuell prüfen
php artisan docs:check-updates

# Dokumentations-Gesundheit anzeigen
php artisan docs:health
```

### Git Hooks aktivieren:
```bash
# Einmalig einrichten
git config core.hooksPath .githooks
```

## Workflow für neue Aufgaben

Verwende diesen erweiterten Workflow bei der Bearbeitung neuer Aufgaben:

### 1. **Analyse Phase**
- Gründliche Analyse der Aufgabenstellung
- Code-Base Recherche mit relevanten Tools
- Identifikation betroffener Komponenten und Dependencies
- Dokumentation existierender Patterns und Constraints
- **MCP-Server Evaluation**: Prüfe verfügbare MCP-Server für optimale Unterstützung:
  - **Interne MCP-Server**: DatabaseMCP, CalcomMCP, RetellMCP, etc. (siehe unten)
  - **Externe MCP-Server**: Context7 für Dokumentationen, taskmaster_ai für Task Management
  - **Auswahl-Kriterien**: Wähle MCP-Server basierend auf Aufgabentyp und benötigten Funktionen

### 2. **Planning Phase**
- Erstelle detaillierten Plan mit klaren Deliverables
- Identifiziere potenzielle Risiken und Edge Cases
- Definiere Success Metrics
- Dokumentiere in `tasks/todo.md`
- **Task Management MCP nutzen**: 
  - Verwende `TodoWrite`/`TodoRead` für einfache Aufgabenverwaltung
  - Aktiviere `taskmaster_ai` MCP für komplexe Projekte (`MCP_TASKMASTER_ENABLED=true`)
  - Erstelle detaillierte Schritt-für-Schritt-Pläne mit Abhängigkeiten

### 3. **Specification Phase**
- Erstelle technische Spezifikation basierend auf dem Plan
- Definiere Interfaces, Datenstrukturen und APIs
- Spezifiziere Error Handling und Logging-Strategie
- Hinterfrage und validiere die Spezifikation
- **API/Library Documentation via Context7**:
  - Nutze `mcp__context7__resolve-library-id` für Library-Suche
  - Hole aktuelle Docs mit `mcp__context7__get-library-docs`
  - Besonders wichtig bei: Laravel, Filament, Cal.com, Retell.ai APIs

### 4. **Validation Phase**
- Überprüfe Spezifikation gegen bestehende Code-Base
- Recherchiere Best Practices und aktuelle Dokumentationen
- Identifiziere potenzielle Konflikte oder Breaking Changes
- Optimiere basierend auf Findings

### 5. **Implementation Phase**
- Arbeite To-Do-Liste schrittweise ab
- Implementiere umfassendes Logging mit Correlation IDs
- Schreibe Tests parallel zur Implementierung
- Erstelle sinnvolle, atomare Commits

### 6. **Review & Documentation**
- Code Review mit Fokus auf:
  - Security (Multi-Tenancy, Input Validation)
  - Performance (Query Optimization, Caching)
  - Error Handling (Graceful Degradation)
  - Maintainability (Clean Code, SOLID)
- Aktualisiere Dokumentation
- Füge Debugging-Informationen in `todo.md` hinzu

### 7. **Deployment Preparation**
- Erstelle Deployment Checklist
- Definiere Rollback-Strategie
- Plane Monitoring und Alerting
- Dokumentiere Known Issues und Workarounds

## MCP-Server Übersicht und Verwendung

### Verfügbare MCP-Server im Projekt

#### Interne MCP-Server (bereits implementiert)
1. **CalcomMCPServer** - Cal.com Integration für Kalenderfunktionen
2. **RetellMCPServer** - Retell.ai Integration für AI-Telefonie
3. **DatabaseMCPServer** - Sichere Datenbankoperationen
4. **WebhookMCPServer** - Webhook-Verarbeitung
5. **QueueMCPServer** - Queue-Management und Job-Status
6. **StripeMCPServer** - Payment-Processing
7. **KnowledgeMCPServer** - Knowledge Base Management
8. **AppointmentMCPServer** - Terminverwaltung
9. **CustomerMCPServer** - Kundenverwaltung
10. **CompanyMCPServer** - Multi-Tenant Management
11. **BranchMCPServer** - Filialverwaltung
12. **RetellConfigurationMCPServer** - Retell Agent Konfiguration
13. **RetellCustomFunctionMCPServer** - Custom AI Funktionen
14. **AppointmentManagementMCPServer** - Erweiterte Terminworkflows
15. **SentryMCPServer** - Error Tracking

#### Externe MCP-Server (konfigurierbar)
- **sequential_thinking** - Schrittweise Problemlösung (standardmäßig aktiv)
- **postgres** - Datenbankzugriff (mapped auf MySQL/MariaDB)
- **effect_docs** - Dokumentationsgenerierung
- **taskmaster_ai** - Erweiterte Aufgabenverwaltung (standardmäßig deaktiviert)

#### Claude-spezifische MCP-Tools
- **mcp__context7__resolve-library-id** - Library-Namen zu Context7 IDs auflösen
- **mcp__context7__get-library-docs** - Aktuelle Library-Dokumentation abrufen

### MCP-Server Verwendungsrichtlinien

#### Wann welchen MCP-Server nutzen:

**Für API-Integrationen und Dokumentation:**
- Nutze Context7 MCP für externe Library-Dokumentation (Laravel, Filament, etc.)
- Verwende interne MCP-Server für projektspezifische APIs

**Für Task Management:**
- Einfache Aufgaben: `TodoWrite`/`TodoRead` Tools
- Komplexe Projekte: Aktiviere `taskmaster_ai` MCP
- Detaillierte Planung mit Abhängigkeiten und Meilensteinen

**Für Datenoperationen:**
- DatabaseMCPServer für sichere Queries
- WebhookMCPServer für Event-Verarbeitung
- QueueMCPServer für asynchrone Jobs

**Best Practices:**
1. **Evaluiere zu Beginn jeder Aufgabe** verfügbare MCP-Server
2. **Kombiniere mehrere MCP-Server** für optimale Ergebnisse
3. **Dokumentiere MCP-Nutzung** in Implementierungsnotizen
4. **Prüfe externe MCP-Optionen** bei neuen Anforderungen

### MCP-Server aktivieren:
```bash
# Externe MCP-Server in .env aktivieren
MCP_TASKMASTER_ENABLED=true
MCP_EFFECT_DOCS_ENABLED=true

# MCP-Status prüfen
php artisan mcp:status

# MCP-Health Check
php artisan mcp:health
```

## Essential Commands

### Development Setup
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --force  # Production requires --force
npm run dev
php artisan serve
```

### NEW: Best Practices Commands (2025)
```bash
# MCP Discovery & Execution
php artisan mcp:discover "describe your task"      # Find best MCP server
php artisan mcp:discover "task" --execute         # Execute directly
php artisan mcp:health                            # Check MCP health
php artisan mcp:list                              # List available servers

# Data Flow Tracking
php artisan dataflow:list                         # View recent flows
php artisan dataflow:diagram <correlation-id>     # Generate sequence diagram
php artisan dataflow:start                        # Start tracking session

# Code Analysis
php artisan analyze:impact --git                  # Analyze git changes
php artisan analyze:component App\\Service        # Analyze specific component
php artisan analyze:understand App\\Service       # Understand existing code

# Documentation Health
php artisan docs:check-updates                    # Check doc health
php artisan docs:check-updates --auto-fix        # Auto-update timestamps
php artisan docs:health                          # Overall doc status

# Code Quality
composer quality                                  # Run all checks
composer pint                                    # Format code
composer pint:test                              # Check formatting only
composer stan                                    # Static analysis
composer stan:baseline                          # Create baseline
```

### Running Tests
```bash
php artisan test                    # Run all tests
php artisan test --filter TestName  # Run specific test
php artisan test --parallel        # Faster with parallel execution
php artisan test --coverage        # With code coverage
./vendor/bin/phpunit tests/Feature  # Run feature tests only
```

### Queue Management
```bash
php artisan horizon          # Start Horizon queue worker
php artisan horizon:status   # Check Horizon status
php artisan queue:work       # Alternative queue worker
```

### Cache Management
```bash
php artisan optimize:clear   # Clear all caches
php artisan cache:clear      # Clear application cache
php artisan config:clear     # Clear config cache
php artisan route:clear      # Clear route cache
```

### Database Commands
```bash
php artisan migrate --force                          # Run migrations in production
php artisan make:migration create_example_table      # Create new migration
php artisan tinker                                  # Interactive shell
```

## Architecture Overview

### Core Domain Model
```
Company (Tenant)
     Branches (Locations)
     Staff (Employees)
     Services (Offerings)
     Customers
     Appointments
```

### Service Layer Architecture
The application uses a service-oriented architecture with key services:
- `CalcomService` / `CalcomV2Service` - Calendar API integration
- `RetellService` / `RetellV2Service` - AI phone service integration
- `CalcomEventTypeSyncService` - Event type synchronization
- `CallDataRefresher` - Call data updates
- Calendar providers follow strategy pattern (BaseCalendarService)

**Core Business Services:**
- `AppointmentService` - Appointment lifecycle management
- `CustomerService` - Customer management and duplicate detection
- `CallService` - Call processing and analysis

### Repository Pattern Implementation
The application uses the Repository Pattern for data access:
- `BaseRepository` - Abstract base class with common CRUD operations
- `AppointmentRepository` - Appointment-specific queries and filters
- `CustomerRepository` - Customer lookup and duplicate detection
- `CallRepository` - Call data access and statistics
- Repositories handle all database queries, services contain business logic

### Multi-Tenancy Implementation
- Global scope `TenantScope` automatically filters data by company
- Tenant identification via subdomain or request headers
- All models with `company_id` are automatically scoped

### Integration Flow (End-to-End Appointment Booking)
1. **Call Reception**: Customer calls → Retell.ai AI agent answers with company greeting
2. **Dialog & Data Capture**: AI conducts conversation, extracts appointment details (date, time, service, customer info)
3. **Webhook Processing**: Retell.ai sends call data to AskProAI webhook (`/api/retell/webhook`)
4. **Customer Management**: System checks/creates customer record based on phone number
5. **Availability Check**: Validates requested time slot (Cal.com integration planned)
6. **Appointment Creation**: Books appointment in database and Cal.com calendar
7. **Confirmation**: AI confirms booking verbally, system sends email confirmation

### Key Design Patterns
- **Repository Pattern**: Data access abstraction (though not fully implemented)
- **Service Pattern**: Business logic encapsulation
- **Policy Pattern**: Authorization rules
- **Factory Pattern**: Calendar provider instantiation
- **Observer Pattern**: Model events and listeners
- **Middleware Pattern**: Webhook signature verification

## Critical Implementation Details

### Webhook Security
All webhooks use signature verification middleware:
- `VerifyCalcomSignature` - Cal.com webhooks
- `VerifyRetellSignature` - Retell.ai webhooks

### Event Type Management
The system is transitioning from `staff_service_assignments` to `staff_event_types` table. Both exist currently but `staff_event_types` is the target structure with enhanced features.

### API Versioning
- Cal.com: Transitioning from v1 to v2 API (both currently in use)
- Use `CalcomV2Service` for new implementations
- Legacy `CalcomService` still handles some operations

### Queue Processing
- Uses Laravel Horizon for queue management
- Critical jobs: `ProcessRetellCallJob`, `RefreshCallDataJob`
- Webhooks should be processed asynchronously to prevent timeouts

### Filament Admin Panel
- Main admin interface at `/admin`
- Resources follow naming convention: `{Model}Resource`
- Custom pages in `app/Filament/Admin/Pages/`
- Relation managers for nested resource management

## Environment Configuration

Key environment variables that must be set:
```
# Database
DB_CONNECTION=mysql
DB_DATABASE=askproai

# Cal.com Integration
DEFAULT_CALCOM_API_KEY=
DEFAULT_CALCOM_TEAM_SLUG=

# Retell.ai Integration
DEFAULT_RETELL_API_KEY=
DEFAULT_RETELL_AGENT_ID=

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.udag.de
MAIL_FROM_ADDRESS=

# Queue
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
```

## Database Considerations

### Migration Strategy
- Always backup before running migrations in production
- Use `--force` flag in production environment
- Check for data migrations after schema changes

### Key Tables
- `companies` - Tenant organizations
- `branches` - Physical locations
- `staff` - Employees
- `appointments` - Core booking data
- `calls` - Phone call records from Retell.ai
- `calcom_event_types` - Calendar event templates
- `staff_event_types` - Staff-to-event assignments (new structure)

## Testing Approach

### Comprehensive Test Suite
The application includes a full test suite covering all layers:

**Unit Tests** (`tests/Unit/`)
- Repository classes with full CRUD coverage
- Model relationships and scopes
- Helper functions and utilities

**Integration Tests** (`tests/Integration/`)
- Service layer with event dispatching
- External API mocking (Cal.com, Retell.ai)
- Transaction handling and rollbacks
- Business rule validation

**Feature Tests** (`tests/Feature/`)
- API endpoint testing with authentication
- Webhook processing (`tests/Feature/Webhook/`)
- Request validation and error handling
- Multi-tenancy isolation

**E2E Tests** (`tests/E2E/`)
- Complete business workflows
- Phone-to-appointment flow
- Customer lifecycle management
- Appointment management flow

### Test Database
Uses SQLite in-memory database (configured in `phpunit.xml`)

### Running Test Suites
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Integration
php artisan test --testsuite=Feature
php artisan test --testsuite=E2E

# Run with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

## Debugging & Troubleshooting

### Booking Flow Debugging

#### 1. Enable Debug Logging
```bash
# Set in .env
LOG_CHANNEL=stack
LOG_LEVEL=debug
BOOKING_DEBUG=true
```

#### 2. Check Correlation IDs
```sql
-- Find all logs for a failed booking
SELECT * FROM api_call_logs 
WHERE correlation_id = 'YOUR-CORRELATION-ID'
ORDER BY created_at;

-- Check webhook processing
SELECT * FROM webhook_events 
WHERE payload->>'$.call_id' = 'YOUR-CALL-ID';
```

#### 3. Common Issues & Solutions

**Issue: "Time slot no longer available"**
```sql
-- Check for stuck locks
SELECT * FROM appointment_locks 
WHERE expires_at < NOW() 
AND branch_id = 'YOUR-BRANCH-ID';

-- Clean expired locks
DELETE FROM appointment_locks WHERE expires_at < NOW();
```

**Issue: "Cal.com sync failed"**
```bash
# Check circuit breaker status
php artisan circuit-breaker:status

# Reset circuit breaker
php artisan circuit-breaker:reset calcom

# Manually retry sync
php artisan appointments:sync-failed
```

**Issue: "Webhook not processing"**
```php
// Check webhook signature
curl -X POST https://api.askproai.de/api/webhook \
  -H "x-retell-signature: YOUR_SIGNATURE" \
  -H "Content-Type: application/json" \
  -d '{"event_type":"call_ended","call_id":"test"}'
```

**Issue: "Database Access Denied" after deployment**
This error typically occurs when Laravel's cached configuration contains incorrect database credentials.

**Symptoms:**
- Error: `Access denied for user 'askproai_user'@'localhost' (using password: YES)`
- Occurs after deployment or environment changes
- Application was working before, suddenly stops

**Root Cause:**
Laravel's config cache (`bootstrap/cache/config.php`) may contain incorrect values from:
1. `.env.production` template files with placeholder values
2. Old cached values from previous deployments
3. Environment file precedence issues

**Solution:**
```bash
# 1. Delete the cached config file
rm -f bootstrap/cache/config.php

# 2. Check for .env.production files that might override .env
ls -la .env*

# 3. Rename any .env.production files to prevent loading
mv .env.production .env.production.template

# 4. Recreate config cache with correct values
php artisan config:cache

# 5. Restart PHP-FPM to ensure changes take effect
sudo systemctl restart php8.3-fpm
```

**Prevention:**
- Never commit `.env.production` files with actual credentials
- Use `.env.production.template` for template files
- Always clear config cache after deployment: `php artisan optimize:clear`
- Verify database credentials in `.env` before caching config

### Performance Monitoring

```sql
-- Slow queries
SELECT 
    query_time,
    lock_time,
    rows_sent,
    rows_examined,
    sql_text
FROM mysql.slow_log
WHERE query_time > 1
ORDER BY query_time DESC
LIMIT 10;

-- API performance
SELECT 
    service,
    AVG(duration_ms) as avg_ms,
    MAX(duration_ms) as max_ms,
    COUNT(*) as total_calls
FROM api_call_logs
WHERE created_at >= NOW() - INTERVAL 1 HOUR
GROUP BY service;
```

## Common Development Tasks

### Adding a New Integration
1. Create service class in `app/Services/`
2. Add configuration to `config/services.php`
3. Create webhook controller if needed
4. Add webhook route with signature verification
5. Create job for async processing
6. Add circuit breaker configuration
7. Implement logging with correlation IDs
8. Write integration tests

### Creating a New Filament Resource
```bash
php artisan make:filament-resource ModelName --generate
```

### Adding a Cal.com Event Type
Event types are synced from Cal.com. Use the sync command or import wizard in admin panel.

### Troubleshooting Retell.ai Integration

#### Problem: "Es werden keine Anrufe eingespielt"

**Quick Fix:**
1. Ensure Horizon is running: `php artisan horizon`
2. Click "Anrufe abrufen" button in the admin panel
3. Check if calls are imported

**Root Causes & Solutions:**

1. **Missing Webhook Registration**
   - Register webhook URL in Retell.ai dashboard: `https://yourdomain.com/api/retell/webhook`
   - Enable events: `call_ended`, `call_started`, `call_analyzed`

2. **Missing API Keys**
   ```bash
   # Check if company has API key
   php artisan tinker
   >>> Company::first()->retell_api_key
   
   # If empty, update from .env
   >>> $c = Company::first();
   >>> $c->retell_api_key = config('services.retell.api_key');
   >>> $c->save();
   ```

3. **Manual Import**
   ```bash
   # Import all recent calls
   php fix-retell-import.php
   
   # Or use the UI button "Anrufe abrufen"
   ```

4. **Debugging**
   ```bash
   # Check webhook logs
   tail -f storage/logs/laravel.log | grep -i retell
   
   # Test API connection
   php test-retell-api.php
   
   # Check queue processing
   php artisan queue:work --queue=webhooks --tries=1
   ```

**Webhook Flow:**
`Retell Call` → `POST /api/retell/webhook` → `Signature Verify` → `ProcessRetellCallEndedJob` → `Call Record`

**Required .env Variables:**
```
RETELL_TOKEN=key_xxx
RETELL_WEBHOOK_SECRET=key_xxx
RETELL_BASE=https://api.retellai.com
```

## UI/UX Development & Testing

### Screenshot Workflow for Claude
When working on UI/UX features, provide screenshots to Claude for analysis:

1. **Capture Screenshots**:
   ```bash
   # macOS: Cmd+Shift+4 (selection) or Cmd+Shift+3 (full screen)
   # Save to: ~/Desktop/askproai-screenshots/
   
   # Linux: Use gnome-screenshot or similar
   gnome-screenshot -a -f ~/askproai-screenshot.png
   ```

2. **Provide Context**:
   - Current page/route (e.g., `/admin/appointments`)
   - User role logged in (Admin, Staff, etc.)
   - What feature/issue to analyze
   - Browser/device info if relevant

3. **Common UI Areas to Screenshot**:
   - Dashboard widgets and stats
   - Form layouts and validation states
   - Table views with filters
   - Mobile responsive views
   - Error states and notifications
   - Multi-step wizards (each step)

### Automated Screenshot Testing
```bash
# Capture UI state after changes
php artisan ui:capture --all
php artisan ui:capture --route=/admin/appointments --compare

# Post-deployment verification
./scripts/post-deploy-ui-check.sh

# Enable UI test mode in browser
# Add ?ui_test=1 to any admin URL to activate markers
```

### UI Testing Tools
1. **Browser Console Tool**: Load `/js/askproai-ui-tester.js`
2. **Filament Trait**: Add `HasUITesting` to resources
3. **Capture Trait**: Use `CapturesUIState` in controllers

### When Claude Should Request Screenshots
After making changes to:
- Filament resources (tables, forms, filters)
- Dashboard widgets
- Email templates
- Responsive layouts
- Error states
- Multi-step processes

**Example Request**:
"I've updated the AppointmentResource form. Please run `php artisan ui:capture --route=/admin/appointments/create` and provide the screenshot so I can verify the layout changes."

### Design System Reference
- **Primary Colors**: Blue (#3B82F6), Gray (#6B7280)
- **Framework**: Filament 3.x (based on Tailwind CSS)
- **Icons**: Heroicons
- **Components**: Filament's built-in components
- **Responsive Breakpoints**: sm:640px, md:768px, lg:1024px, xl:1280px

## Performance Considerations

### 🎯 Performance Benchmarks & Targets

#### Response Time Targets:
- **API Endpoints**: < 200ms (p95)
- **Webhook Processing**: < 500ms 
- **Admin Dashboard**: < 1s page load
- **Database Queries**: < 100ms per query
- **Queue Job Processing**: < 30s per job

#### Resource Limits:
- **Memory Usage**: < 512MB per request
- **CPU Usage**: < 80% sustained
- **Database Connections**: < 100 concurrent
- **Redis Memory**: < 2GB
- **Disk I/O**: < 100 MB/s

#### Throughput Targets:
- **API Requests**: 1000 req/min
- **Webhook Events**: 500/min
- **Concurrent Users**: 100
- **Calls per Hour**: 1000
- **Appointments per Day**: 5000

### 🚨 Performance Monitoring:
```bash
# Check current performance
php artisan performance:analyze

# Slow query log
tail -f storage/logs/slow-queries.log

# Real-time metrics
php artisan horizon:metrics
```

### Caching Strategy
- Event types cached for 5 minutes
- Company settings cached indefinitely (clear on update)
- API responses cached for 1 minute
- Clear cache after configuration changes

### Query Optimization
- **MUST** use eager loading for relationships
- **MUST** implement query scopes for common filters
- **MUST** add indexes for: company_id, created_at, phone_number
- **AVOID** whereRaw() - use query builder instead
- **LIMIT** results to 50 per page max

### Queue Configuration
- Horizon configured for multiple queues
- High priority: webhooks (timeout: 60s)
- Default: general processing (timeout: 300s)
- Low priority: maintenance tasks (timeout: 900s)

## Business Logic & Workflow

### Tenant Hierarchy
```
Platform (AskProAI)
├── Company (Tenant/Mandant)
│   ├── Branches (Filialen/Standorte)
│   │   ├── Staff (Mitarbeiter)
│   │   ├── Services (Dienstleistungen)
│   │   └── Working Hours
│   ├── Customers (Kunden)
│   └── Appointments (Termine)
```

### Key Business Rules
- Each company can have multiple branches with independent calendars
- Staff can be assigned to branches and offer specific services
- Appointments link customers, staff, services, and time slots
- All data is tenant-isolated via company_id

### Appointment Status & No-Show Policies
**Current Implementation:**
- Appointment statuses: `scheduled`, `confirmed`, `completed`, `cancelled`, `no_show`
- No automatic no-show marking implemented yet
- No automatic customer blocking for repeated no-shows

**TODO/Planned:**
- Auto-mark as no-show if appointment time + buffer passed without check-in
- Track no-show count per customer for potential blocking
- Company-configurable no-show policies (warnings, blocks after X no-shows)

### Webhook Signature Verification
All incoming webhooks MUST be verified:
- Retell.ai: Uses `VerifyRetellSignature` middleware
- Cal.com: Uses `VerifyCalcomSignature` middleware
- Reject unverified requests immediately

## Current Limitations & TODOs

### MVP Scope
- Basic appointment booking via phone works
- No automatic cancellation/rescheduling yet
- Limited error handling in AI conversations
- Email templates are basic (German only)

### Known Issues
- Transitioning from `staff_service_assignments` to `staff_event_types` table
- Mixed Cal.com API v1/v2 usage (standardize on v2)
- White-label branding not fully implemented
- SMS/WhatsApp notifications planned but not implemented

### Planned Features
- Multi-language support (30+ languages via Retell.ai)
- Google Calendar fallback integration
- CRM integrations (Pipedrive, Salesforce)
- Advanced analytics dashboard
- Mobile app API endpoints (partially implemented)

## UI/UX Access Levels

### Admin Dashboard (Filament - `/admin`)
**Available to Company Admins:**
- View/manage appointments, customers, staff, services
- Configure company settings, branches, working hours
- View call logs and transcripts
- Manual appointment creation/editing
- Basic reporting (appointment counts, call statistics)

**Super Admin Only:**
- Company (tenant) management
- System-wide settings
- API status monitoring
- Queue/Horizon management

### Customer Self-Service
**Currently Implemented:**
- Phone-based booking via AI (Retell.ai)
- Email confirmations for appointments

**NOT Yet Available for Customers:**
- Web portal for appointment management
- Self-service cancellation/rescheduling
- SMS notifications
- WhatsApp integration
- Mobile app

### Mandant Self-Service Features
**Already Available:**
- Onboarding wizard (initial setup)
- Service configuration
- Staff management
- Working hours setup
- Basic email template customization (planned)

**Still Admin/Backend Only:**
- Retell.ai agent configuration (must be done in Retell.ai directly)
- Cal.com event type mapping
- Webhook configuration
- API key management
- Advanced analytics
- Billing/subscription management

## Security & Monitoring

### Security Layer Components
The platform implements a comprehensive security layer with the following components:

#### 1. **Encryption Service**
- Automatic encryption of sensitive fields (API keys, passwords)
- Uses AES-256-CBC encryption
- Transparent encryption/decryption via model observers

#### 2. **Adaptive Rate Limiting**
- Configurable per-endpoint limits
- User-based and IP-based tracking
- Automatic throttling with exponential backoff
- Real-time monitoring of rate limit violations

#### 3. **Threat Detection**
- SQL injection pattern detection
- XSS attempt blocking
- Path traversal prevention
- Command injection protection
- Automatic alerting for critical threats

#### 4. **Security Middleware**
- `ThreatDetectionMiddleware` - Blocks malicious requests
- `AdaptiveRateLimitMiddleware` - Enforces rate limits
- `MetricsMiddleware` - Collects performance metrics

### Monitoring Stack
```bash
# Start monitoring services
docker-compose -f docker-compose.observability.yml up -d

# Access dashboards
# Prometheus: http://localhost:9090
# Grafana: http://localhost:3000 (admin/admin)
# Alertmanager: http://localhost:9093
```

### Security Commands
```bash
# Run security audit
php artisan askproai:security-audit

# Create system backup
php artisan askproai:backup --type=full --encrypt --compress

# Smart migrations with zero downtime
php artisan migrate:smart --analyze
php artisan migrate:smart --online
```

### Security Dashboard
Access the security dashboard at `/admin/security-dashboard` (super admin only) to view:
- Real-time threat statistics
- Rate limiting metrics
- System vulnerabilities
- Backup status
- Recent security events

### Metrics Endpoint
Prometheus metrics available at `/api/metrics` including:
- HTTP request durations
- Queue sizes
- Active calls
- Security threat counters
- Rate limit violations

### Backup Strategy
- **Full Backup**: Complete system backup (daily)
- **Incremental Backup**: Changed files only (hourly)
- **Critical Backup**: Essential data only (every 6 hours)
- All backups are encrypted and compressed
- 30-day retention policy by default

### Security Best Practices
1. **Never commit sensitive data** - Use environment variables
2. **Verify all webhooks** - Signature verification is mandatory
3. **Monitor rate limits** - Review Grafana dashboards regularly
4. **Regular backups** - Ensure backup jobs are running
5. **Update dependencies** - Run `composer update` monthly
6. **Review security logs** - Check `/storage/logs/security.log`

### Documentation Access & Security

#### Protected Documentation Locations
All documentation is now password-protected using Basic Authentication:

1. **Main Documentation**: `/public/documentation/`
   - Protected by `.htaccess` with Basic Auth
   - Credentials stored in `/var/www/api-gateway/.htpasswd`
   
2. **MkDocs Documentation**: `/public/mkdocs/`
   - Accessible at: https://api.askproai.de/mkdocs/
   - Generated from `/docs_mkdocs/` source files
   
3. **API Documentation**: `/public/docs/api/swagger/`
   - Swagger/OpenAPI documentation
   - Contains API endpoint specifications

4. **Legacy Locations** (secured):
   - `/public/admin_old/` - Old admin docs (access denied)
   - `/storage/documentation-backups/` - Moved backups (non-public)

#### Access Credentials
- **Auth File**: `/var/www/api-gateway/.htpasswd`
- **Auth Type**: Basic Authentication
- **How to access**: Use browser prompt or curl with credentials:
  ```bash
  curl -u username:password https://api.askproai.de/documentation/
  ```

#### Documentation Build Process
```bash
# Build MkDocs documentation
cd /var/www/api-gateway
mkdocs build

# Documentation source: /docs_mkdocs/
# Output directory: /public/mkdocs/
```

#### Security Audit History
- **2025-06-25**: Major security audit completed
  - Removed hardcoded credentials from config files
  - Added .htaccess protection to all doc directories
  - Moved backup directories out of public access
  - Full report: `DOCUMENTATION_SECURITY_AUDIT_2025-06-25.md`

### Performance Optimization

#### Caching Layers
- **Application Cache**: Redis-based caching for frequently accessed data
- **Query Cache**: Automatic caching of expensive database queries
- **Response Cache**: API response caching with TTL
- **View Cache**: Compiled Blade template caching

#### Performance Commands
```bash
# Cache optimization
php artisan cache:table          # Create cache database table
php artisan route:cache          # Cache routes for faster loading
php artisan config:cache         # Cache configuration
php artisan view:cache           # Cache compiled views

# Performance monitoring
php artisan performance:analyze   # Analyze slow queries
php artisan performance:report    # Generate performance report

# Queue optimization
php artisan horizon:snapshot      # Create queue metrics snapshot
php artisan queue:monitor        # Monitor queue performance
```

#### Database Optimization
- Eager loading relationships to prevent N+1 queries
- Query optimization with proper indexes
- Database query profiling in development
- Connection pooling for high traffic

### Emergency Procedures
```bash
# Block suspicious IP
php artisan security:block-ip 192.168.1.1

# Emergency backup
php artisan askproai:backup --type=critical --encrypt

# Clear all caches (if compromised)
php artisan optimize:clear

# Rotate API keys
php artisan security:rotate-keys
```

## 🔄 Automatische Dokumentations-Aktualisierung

### Übersicht
Das System stellt sicher, dass die Dokumentation bei Code-Änderungen automatisch aktualisiert wird. Es überwacht alle Commits und informiert Entwickler proaktiv über notwendige Updates.

### Komponenten

#### 1. Git Hooks (Automatische Prüfung)
- **Post-Commit**: Prüft nach jedem Commit ob Dokumentation betroffen ist
- **Pre-Push**: Blockiert Push bei kritischer Dokumentation (<50% Health)
- **Commit-Message**: Erzwingt Conventional Commits Format

#### 2. Laravel Commands
```bash
# Dokumentations-Gesundheit prüfen
php artisan docs:check-updates

# Mit automatischen Fixes
php artisan docs:check-updates --auto-fix

# JSON Output für Automation
php artisan docs:check-updates --json
```

#### 3. Automatische Erkennung bei
- **Neue Features** (`feat:` Commits)
- **Service-Änderungen** (`app/Services/`)
- **MCP-Server Updates** (`app/Services/MCP/`)
- **API-Änderungen** (`routes/`, Controller)
- **Datenbank-Änderungen** (Migrations)
- **Konfigurationsänderungen** (`config/`, `.env.example`)

### Workflow für neue Features

1. **Entwickle dein Feature**
```bash
# Beispiel: Neuer Service
echo "<?php // New Service" > app/Services/MyNewService.php
git add app/Services/MyNewService.php
git commit -m "feat: add my new service"
```

2. **Automatische Benachrichtigung**
```
📚 Dokumentation muss möglicherweise aktualisiert werden!
Gründe:
  - Service-Klassen geändert
💡 Tipp: Führe 'php artisan docs:check-updates' aus
```

3. **Prüfe betroffene Dokumentation**
```bash
php artisan docs:check-updates
```

4. **Update Dokumentation**
- System zeigt genau welche Dateien aktualisiert werden müssen
- Bei `--auto-fix` werden Timestamps automatisch aktualisiert

### Dashboard Widget
- Verfügbar im Admin-Panel unter `/admin`
- Zeigt Dokumentations-Gesundheit in Echtzeit
- Nur für Admins sichtbar
- Quick Actions für häufige Befehle

### Setup
```bash
# Einmalige Installation
./scripts/setup-doc-hooks.sh

# Git Hooks sind bereits aktiviert für dieses Projekt
git config core.hooksPath .githooks
```

### Gesundheits-Metriken
- **80-100%**: 🟢 Dokumentation aktuell
- **60-79%**: 🟡 Kleinere Updates empfohlen  
- **40-59%**: 🟠 Dringende Updates nötig
- **0-39%**: 🔴 Kritisch - Push wird blockiert

### CI/CD Integration
GitHub Actions Workflow prüft bei jedem PR:
- Dokumentations-Gesundheit
- Erstellt automatisch Update-PRs
- Kommentiert PRs mit Status

## 📋 Archivierte Historische Informationen

> ℹ️ **Hinweis**: Historische Blocker und veraltete Informationen wurden archiviert in: [`docs/archive/BLOCKER_JUNI_2025.md`](./docs/archive/BLOCKER_JUNI_2025.md)

Für aktuelle Blocker und Issues, siehe das GitHub Issue Tracking oder die aktuelle Projektdokumentation.

