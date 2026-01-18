# Codebase Findings - askproai-api

**Erstellt:** 2026-01-18
**Zweck:** Dokumentation der Codebase-Analyse als Grundlage für die CLAUDE.md Erstellung

---

## Executive Summary

askproai-api ist eine **Laravel 11.31 Enterprise-Anwendung** für AI-gestütztes Terminmanagement mit:
- **Retell AI Voice Integration** (Sprachassistent für Terminbuchung)
- **Cal.com Integration** (Kalendersystem)
- **Multi-Tenant Architektur** (Company-basierte Isolation)
- **Filament 3.3 Admin Panel**

---

## 1. Technologie-Stack

| Komponente | Version | Zweck |
|------------|---------|-------|
| PHP | ^8.2 | Runtime |
| Laravel | ^11.31 | Framework |
| Filament | ^3.3 | Admin Panel |
| SQLite/MySQL/Postgres | - | Database |
| Spatie Permission | ^6.21 | RBAC |
| Twilio SDK | ^8.8 | SMS |
| Pusher | ^7.2 | Realtime |

---

## 2. Architektur-Übersicht

### Schichten
```
┌─────────────────────────────────────────────┐
│  Filament Admin + Customer Portal           │
├─────────────────────────────────────────────┤
│  API Layer (V1, V2, Retell)                 │
├─────────────────────────────────────────────┤
│  Service Layer (189+ Services)              │
├─────────────────────────────────────────────┤
│  Domain Layer (VoiceAI, Appointments)       │
├─────────────────────────────────────────────┤
│  Model Layer (88 Models)                    │
├─────────────────────────────────────────────┤
│  Database (229 Migrations)                  │
└─────────────────────────────────────────────┘
```

### Design Patterns
- **Saga Pattern** - Distributed Transactions (Cal.com + DB Sync)
- **Strategy Pattern** - Staff Assignment
- **Observer Pattern** - Model Events
- **Multi-Tenancy** - BelongsToCompany Trait

---

## 3. Verzeichnisstruktur

### App-Struktur
```
app/
├── Models/           # 88 Eloquent Models
├── Services/         # 189+ Services in 36 Kategorien
│   ├── Retell/       # 28 Retell-spezifische Services
│   ├── Booking/      # Booking Engine
│   ├── Saga/         # Distributed Transactions
│   └── ...
├── Http/
│   ├── Controllers/  # 15+ Controller-Kategorien
│   ├── Middleware/   # 32 Middleware-Klassen
│   └── Requests/     # 10+ Form Requests
├── Domains/          # DDD Bounded Contexts
│   ├── VoiceAI/
│   ├── Appointments/
│   └── Notifications/
├── Filament/         # 60+ Admin Resources
├── Jobs/             # 22 Queue Jobs
├── Events/           # 15 Domain Events
├── Listeners/        # 13 Event Listeners
├── Policies/         # 29 Authorization Policies
└── Observers/        # 10 Model Observers
```

---

## 4. Externe Integrationen

### 4.1 Retell AI (Haupt-Integration)
- **Zweck:** KI-Sprachassistent für Telefonanrufe
- **Endpoints:** `/api/retell/*` (20+ Endpoints)
- **Config:** `config/retell.php`
- **Key Services:**
  - `RetellAIService` - Core Integration
  - `CustomerRecognitionService` - Kundenidentifikation
  - `AppointmentCreationService` - Terminbuchung via Voice

### 4.2 Cal.com
- **Zweck:** Kalendersystem & Verfügbarkeit
- **Endpoints:** `/v2/availability/*`, `/v2/bookings/*`
- **Config:** `config/booking.php`, `config/calcom.php`
- **Key Services:**
  - `CalcomService` - API Integration
  - `CalcomHostMappingService` - Staff-Zuordnung

### 4.3 Twilio
- **Zweck:** SMS-Benachrichtigungen
- **Package:** `twilio/sdk ^8.8`
- **Verwendet via:** Laravel Notification Channels

### 4.4 Stripe
- **Zweck:** Zahlungsabwicklung
- **Webhook:** `/webhooks/stripe`
- **Events:** payment_intent.succeeded, subscription.updated

---

## 5. API-Routen (Wichtigste)

### Retell Function Calls
```
POST /api/retell/check-customer
POST /api/retell/check-availability
POST /api/retell/book-appointment
POST /api/retell/cancel-appointment
POST /api/retell/reschedule-appointment
POST /api/retell/get-customer-appointments
```

### V2 API (Cal.com)
```
POST /v2/availability/simple
POST /v2/availability/composite
POST /v2/bookings
PATCH /v2/bookings/{id}/reschedule
DELETE /v2/bookings/{id}
```

### Webhooks
```
POST /webhooks/retell
POST /webhooks/calcom
POST /webhooks/stripe
```

### Health Checks
```
GET /health
GET /health/detailed
GET /health/calcom
```

---

## 6. Datenmodell (Kern-Entitäten)

### Multi-Tenant Core
- `Company` - Mandant
- `User` - Benutzer
- `Branch` - Filiale
- `Customer` - Kunde
- `Staff` - Mitarbeiter

### Appointments
- `Appointment` - Termin
- `Service` - Dienstleistung
- `AppointmentPhase` - Status-Phasen
- `RecurringAppointmentPattern` - Wiederkehrende Termine

### Retell/Voice
- `RetellAgent` - KI-Agent Konfiguration
- `RetellCallSession` - Anrufsession
- `RetellCallEvent` - Anrufereignisse

### Cal.com Integration
- `CalcomEventMap` - Event-Type Mapping
- `CalcomHostMapping` - Host-Zuordnung

---

## 7. Feature Flags

Definiert in `config/features.php`:
```php
'phonetic_matching_enabled'     // Cologne Phonetic für Kundensuche
'skip_alternatives_for_voice'   // Voice-Optimierung
'processing_time_enabled'       // Nested Booking (Friseur)
'gateway_mode_enabled'          // Service Gateway Routing
'enrichment_enabled'            // 2-Phase Delivery
```

---

## 8. Sicherheit

### Webhook-Verifizierung
- `VerifyRetellSignature` - HMAC für Retell
- `VerifyCalcomSignature` - HMAC für Cal.com
- `VerifyStripeWebhookSignature` - Stripe Signatur

### Rate Limiting
- Webhooks: 60 req/min
- Retell Function Calls: 100 req/min
- Booking: 30 req/min

### Multi-Tenancy
- `BelongsToCompany` Trait
- `EnsureUserBelongsToCompany` Middleware

---

## 9. Testing

### Test-Struktur
```
tests/
├── Unit/           # 50+ Unit Tests
├── Feature/        # 108 Feature Tests
├── Integration/    # API Integration Tests
├── E2E/playwright/ # Browser E2E Tests
└── Performance/k6/ # Load Tests
```

### Frameworks
- PHPUnit 11.0.1
- Pest 3.0
- Playwright (E2E)
- K6 (Load Testing)

---

## 10. Offene Punkte / Fragen für CLAUDE.md

### Zu klären mit dem Team:
1. **Deployment-Prozess** - Wie wird deployed? (GitHub Actions, Manual, etc.)
2. **Environment-Setup** - Welche .env Variablen sind kritisch?
3. **Test-Befehle** - Welche Test-Suite wird für CI/CD verwendet?
4. **Coding Standards** - PHPStan Level? PSR-12?
5. **Git Workflow** - Branch-Naming, PR-Prozess?
6. **Cal.com Setup** - Wie werden neue Mandanten konfiguriert?
7. **Retell Agent Setup** - Wie werden neue Agents provisioniert?

### Potenzielle Dokumentationslücken:
- Keine existierende CLAUDE.md gefunden
- Kein CONTRIBUTING.md
- API-Dokumentation via Scramble (`/docs/api`)

---

## 11. Empfehlungen für CLAUDE.md

### Struktur-Vorschlag:
```
1. Quick Start (Build, Test, Run)
2. Architektur-Übersicht
3. Wichtige Services & deren Zweck
4. API-Dokumentation Verweis
5. Testing Guidelines
6. Deployment
7. Troubleshooting
```

### Priorisierte Inhalte:
1. Wie man die App lokal startet
2. Wie man Tests ausführt
3. Retell/Cal.com Integration verstehen
4. Multi-Tenancy beachten
5. Feature Flags

---

## Anhang: Wichtige Dateien

| Datei | Zweck |
|-------|-------|
| `config/retell.php` | Retell AI Konfiguration |
| `config/booking.php` | Cal.com & Booking Settings |
| `config/features.php` | Feature Flags |
| `routes/api.php` | Alle API Routes |
| `app/Services/Retell/` | Retell Service Layer |
| `app/Services/CalcomService.php` | Cal.com Integration |
| `app/Http/Controllers/Api/RetellApiController.php` | Retell Handler |

---

*Dieses Dokument dient als Grundlage für die Erstellung der CLAUDE.md*
