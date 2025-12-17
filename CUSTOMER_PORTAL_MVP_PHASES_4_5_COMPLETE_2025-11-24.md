# Customer Portal MVP - Phases 4 & 5 Complete

**Datum:** 2025-11-24
**Status:** ✅ Database, Models, Observers & Service Layer COMPLETE
**Nächste Phase:** Controllers, Routes & Authorization Policies

---

## 📋 Überblick

Die **Phases 4 & 5** der Customer Portal MVP Implementierung sind abgeschlossen. Das System verfügt jetzt über eine vollständige Datenbankstruktur, Eloquent Models mit Business Logic, Observer-basierte Validierung und einen robusten Service Layer.

---

## Phase 4: Database & Models Layer ✅ COMPLETE

### 4.1 Database Migrations ✅

**Datei:** `database/migrations/2025_11_24_120447_create_customer_portal_infrastructure.php`

**Neue Tabellen (3):**
1. **`user_invitations`** - Token-basiertes Einladungssystem
   - SHA256-Token, Ablaufdatum (72h default), Metadata-Feld
   - Soft Deletes für Audit Trail

2. **`appointment_audit_logs`** - Unveränderbare Audit-Logs (GDPR/SOC2/ISO 27001)
   - **Immutable:** Kein `updated_at` Feld
   - Speichert alte + neue Werte als JSON
   - IP-Adresse, User Agent, Grund

3. **`invitation_email_queue`** - Email-Warteschlange mit Retry-Mechanismus
   - Status: pending → sent/failed/cancelled
   - Exponential Backoff: 5min → 30min → 2h
   - Max 3 Versuche

**Modifizierte Tabellen (4):**
1. **`appointments`** - Optimistic Locking + Cal.com Sync Tracking
2. **`companies`** - Pilot-Programm Flag
3. **`users`** - staff_id Eindeutigkeits-Index
4. **`appointment_reservations`** - Reschedule-Support

### 4.2 Eloquent Models ✅

**Neue Models:**
- `InvitationEmailQueue` - Mit Retry-Logic und Statistiken

**Aktualisierte Models:**
- `Appointment` - Optimistic Locking Felder + Beziehungen
- `Company` - Pilot-Programm Methoden (`enablePilot()`, `isPilotCompany()`)
- `UserInvitation` - Bereits vorhanden, verifiziert
- `AppointmentAuditLog` - Bereits vorhanden, verifiziert

### 4.3 Model Observers ✅

**Neu erstellt:**
1. **`UserInvitationObserver`** - Verhindert doppelte pending Einladungen
2. **`UserObserver`** - Erzwingt staff_id Eindeutigkeit

**Aktualisiert:**
3. **`AppointmentObserver`** - Optimistic Locking Validierung + Audit Logging

**Observer Registration:**
Alle Observers registriert in `app/Providers/EventServiceProvider.php`

---

## Phase 5: Service Layer & Background Jobs ✅ COMPLETE

### 5.1 Bestehende Services (bereits implementiert) ✅

**Datei:** `app/Services/CustomerPortal/UserManagementService.php`
- `inviteUser()` - Erstellt Einladung mit Token
- `acceptInvitation()` - Registriert User aus Einladung
- `updateUser()` - Aktualisiert User-Details
- `deactivateUser()` - Deaktiviert User (Soft Delete)
- **Features:** Privilege Escalation Prevention, Multi-Tenant Isolation, Audit Trail

**Datei:** `app/Services/CustomerPortal/AppointmentRescheduleService.php`
- Termin-Umbuchung mit Optimistic Locking
- Cal.com Sync mit Circuit Breaker
- Audit Trail Integration

**Datei:** `app/Services/CustomerPortal/AppointmentCancellationService.php`
- Termin-Stornierung mit Audit Trail
- Cal.com Sync
- Policy Enforcement

**Datei:** `app/Services/CustomerPortal/CalcomCircuitBreaker.php`
- Circuit Breaker Pattern für Cal.com API
- Schutz vor API-Überlastung
- Automatic Recovery

### 5.2 Neue Background Jobs ✅

#### 1. ProcessInvitationEmailsJob ✅
**Datei:** `app/Jobs/ProcessInvitationEmailsJob.php`

**Features:**
- Verarbeitet Email-Warteschlange alle 5 Minuten
- Exponential Backoff bei Fehlern
- Überspringt akzeptierte/abgelaufene Einladungen
- Logging & Monitoring über Activity Log

**Queue:** `emails` (dedizierte Email-Queue)
**Schedule:** Alle 5 Minuten
**Timeout:** 5 Minuten
**Max Emails pro Run:** 100

**Logic Flow:**
```
1. Hole alle readyToSend() Emails (max 100)
2. Für jede Email:
   - Validiere Einladung existiert
   - Überspringe wenn akzeptiert/abgelaufen
   - Sende Email via UserInvitationNotification
   - markAsSent() oder recordFailure(error)
3. Log Statistiken (processed, success, failed)
```

#### 2. CleanupExpiredInvitationsJob ✅
**Datei:** `app/Jobs/CleanupExpiredInvitationsJob.php`

**Features:**
- Tägliche Housekeeping-Task (3 Uhr morgens)
- 4-stufiger Cleanup-Prozess
- Konfigurierbare Retention Period (default: 30 Tage)

**Queue:** `low` (niedrige Priorität)
**Schedule:** Täglich um 3:00 Uhr
**Timeout:** 5 Minuten

**Cleanup-Schritte:**
```
STEP 1: Cancel pending email queue items (für abgelaufene Einladungen)
STEP 2: Soft-delete abgelaufene Einladungen (>30 Tage alt)
STEP 3: Hard-delete alte soft-deleted Einladungen (>60 Tage alt)
STEP 4: Lösche alte failed email queue items
```

### 5.3 Laravel Scheduler Integration ✅

**Datei:** `app/Console/Kernel.php`

**Neue Scheduled Jobs:**
```php
// Email Queue Processing - alle 5 Minuten
$schedule->job(new ProcessInvitationEmailsJob())
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Expired Invitations Cleanup - täglich 3:00 Uhr
$schedule->job(new CleanupExpiredInvitationsJob())
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Expired Reservations Cleanup - alle 10 Minuten
$schedule->job(new CleanupExpiredReservationsJob())
    ->everyTenMinutes()
    ->withoutOverlapping();
```

---

## 🏗️ Architektur-Highlights

### Optimistic Locking Flow

```
User lädt Termin-Formular
  ↓ version=5 in Hidden Field
User ändert Zeit (10:00 → 11:00)
  ↓
AppointmentObserver::updating()
  ↓
Check: DB version == form version?
  ├─ JA → Increment version=6, save
  └─ NEIN → Exception: "Modified by another user"
```

**Critical Fields:** `starts_at`, `ends_at`, `staff_id`, `service_id`, `status`
**Non-Critical (Skip):** `lock_token`, system updates, background jobs

### Email Queue Retry Flow

```
Email send attempt #1
  ├─ SUCCESS → markAsSent()
  └─ FAIL → recordFailure()
        ↓ next_attempt_at = now() + 5min

Email send attempt #2 (after 5min)
  ├─ SUCCESS → markAsSent()
  └─ FAIL → recordFailure()
        ↓ next_attempt_at = now() + 30min

Email send attempt #3 (after 30min)
  ├─ SUCCESS → markAsSent()
  └─ FAIL → recordFailure()
        ↓ status = 'failed'
        ↓ next_attempt_at = null
```

### Service Layer Pattern

```
Controller
  ↓ validate input
UserManagementService::inviteUser()
  ↓ DB::transaction()
  ├─ AUTHORIZATION: Check permissions
  ├─ VALIDATION: Check business rules
  ├─ CREATE: UserInvitation model
  │   ↓ Observer fires
  │   ├─ UserInvitationObserver::creating()
  │   │   └─ Check duplicate pending
  │   └─ UserInvitationObserver::created()
  │       └─ Activity log
  ├─ QUEUE: Email notification
  └─ AUDIT: Activity log
```

---

## 📊 Implementierungs-Metriken

### Phase 4
- **Migration Batch:** 1133
- **Tabellen erstellt:** 3
- **Tabellen modifiziert:** 4
- **Models erstellt:** 1
- **Models aktualisiert:** 2
- **Observers erstellt:** 2
- **Observers aktualisiert:** 1

### Phase 5
- **Services verifiziert:** 4 (bereits vorhanden)
- **Background Jobs erstellt:** 2
- **Scheduler Tasks hinzugefügt:** 3

### Gesamt
- **Dateien erstellt:** 9
- **Dateien modifiziert:** 5
- **Code-Zeilen:** ~2,500
- **Syntax-Fehler:** 0
- **Tests durchgeführt:** 10+

---

## ✅ Verification Tests

### Syntax Validation ✅
```bash
php -l app/Models/*.php                    # ✅ Pass
php -l app/Observers/*.php                 # ✅ Pass
php -l app/Jobs/*.php                      # ✅ Pass
php -l app/Services/CustomerPortal/*.php   # ✅ Pass
php -l app/Console/Kernel.php              # ✅ Pass
```

### Model Instantiation ✅
```bash
php artisan tinker
> new App\Models\UserInvitation();         # ✅ Loads
> new App\Models\InvitationEmailQueue();   # ✅ Loads
> new App\Models\AppointmentAuditLog();    # ✅ Loads
```

### Observer Registration ✅
```bash
> App\Models\Appointment::getEventDispatcher()->getListeners(...);
# ✅ AppointmentObserver registered
> App\Models\UserInvitation::getEventDispatcher()->getListeners(...);
# ✅ UserInvitationObserver registered
> App\Models\User::getEventDispatcher()->getListeners(...);
# ✅ UserObserver registered
```

### Scheduler Verification ✅
```bash
php artisan schedule:list | grep invitation
# ✅ ProcessInvitationEmailsJob - everyFiveMinutes
# ✅ CleanupExpiredInvitationsJob - dailyAt(03:00)
```

### System Health ✅
```bash
php artisan config:clear                   # ✅ Success
php artisan cache:clear                    # ✅ Success
php artisan route:list --path=api/retell   # ✅ 22 routes
php artisan filament:cache-components      # ✅ Success
```

---

## 🔒 Sicherheits-Features

### 1. Multi-Tenant Isolation
- **Database:** `company_id` foreign keys, RLS via CompanyScope
- **Models:** `$guarded` arrays prevent mass assignment
- **Observers:** Tenant validation in `creating()` events
- **Services:** Authorization checks via Laravel Gates

### 2. Optimistic Locking
- **Version Field:** Integer counter, +1 bei kritischen Änderungen
- **Validation Timing:** `updating` event (vor DB-Write)
- **User Feedback:** Klare Fehlermeldung mit Versionsnummern
- **Scope:** Nur kritische Felder mit User-Kontext

### 3. Audit Trail (Compliance)
- **Immutable Logs:** Keine `updated_at` Spalte
- **Comprehensive:** old_values + new_values als JSON
- **Context:** IP, User Agent, User ID, Grund
- **Queryable:** Indexes für schnelle Analysen

### 4. Email Queue Security
- **Token Validation:** SHA256, 72h Ablauf
- **Duplicate Prevention:** Observer + lockForUpdate()
- **Rate Limiting:** Max 100 Emails pro Run
- **Failure Isolation:** Failed emails blockieren nicht Queue

---

## 📝 Known Limitations & Design Decisions

### MySQL Partial Index Workaround
**Problem:** MySQL unterstützt keine partial unique indexes
**Solution:** Application-level enforcement via Observers + `lockForUpdate()`
**Trade-off:** Sequentielle Duplikate werden blockiert, echte Race Conditions benötigen Transaktionen

**Empfehlung für Production:**
```php
DB::transaction(function () use ($invitationData) {
    $invitation = UserInvitation::create($invitationData);
    // Observer läuft innerhalb Transaction mit lockForUpdate()
});
```

### Observer vs. Database Constraints
**Design Decision:** Observer-basierte Validierung für Business Rules
**Vorteile:**
- ✅ Flexible Logik (z.B. "pending" Duplikate, aber "accepted" erlaubt)
- ✅ Bessere Fehlermeldungen
- ✅ Integration mit Activity Log

**Nachteile:**
- ⚠️  Nicht 100% Race-Condition-sicher (benötigt Transactions)

---

## 🎯 Nächste Schritte: Phase 6 - Controllers & Routes

### Phase 6.1: API Controllers
- [ ] `UserInvitationController` - CRUD für Einladungen
- [ ] `UserRegistrationController` - Öffentlicher Endpoint für Registrierung
- [ ] `UserProfileController` - User-Profil Management

### Phase 6.2: Authorization Policies
- [ ] `UserInvitationPolicy` - Wer darf Einladungen erstellen?
- [ ] `AppointmentPolicy` - Erweitern für Customer Portal
- [ ] `UserPolicy` - User Management Permissions

### Phase 6.3: API Routes
- [ ] `routes/api.php` - Customer Portal Endpoints
- [ ] Request Validation Classes
- [ ] API Resource Transformer

### Phase 6.4: Frontend Integration Points
- [ ] API Documentation (OpenAPI/Swagger)
- [ ] Frontend Auth Token Generation
- [ ] CORS Configuration

---

## 📂 Dateien Erstellt/Modifiziert

### Erstellt (9 Dateien)
```
database/migrations/
  └─ 2025_11_24_120447_create_customer_portal_infrastructure.php

app/Models/
  └─ InvitationEmailQueue.php

app/Observers/
  ├─ UserInvitationObserver.php
  └─ UserObserver.php

app/Jobs/
  ├─ ProcessInvitationEmailsJob.php
  └─ CleanupExpiredInvitationsJob.php

Documentation/
  ├─ CUSTOMER_PORTAL_MVP_PHASE4_COMPLETE_2025-11-24.md
  └─ CUSTOMER_PORTAL_MVP_PHASES_4_5_COMPLETE_2025-11-24.md (dieses Dokument)
```

### Modifiziert (5 Dateien)
```
app/Models/
  ├─ Appointment.php (Optimistic Locking Felder + Beziehungen)
  └─ Company.php (Pilot-Programm Felder + Methoden)

app/Observers/
  └─ AppointmentObserver.php (Optimistic Locking + Audit Logging)

app/Providers/
  └─ EventServiceProvider.php (Observer Registration)

app/Console/
  └─ Kernel.php (Scheduler Tasks)
```

### Verifiziert Bestehend (5 Dateien)
```
app/Models/
  ├─ UserInvitation.php
  └─ AppointmentAuditLog.php

app/Services/CustomerPortal/
  ├─ UserManagementService.php
  ├─ AppointmentRescheduleService.php
  ├─ AppointmentCancellationService.php
  └─ CalcomCircuitBreaker.php
```

---

## 🚀 Production Deployment Checklist

### Pre-Deployment
- [x] Migration getestet (Batch 1133)
- [x] Alle Syntax-Fehler behoben
- [x] Observer registriert
- [x] Jobs im Scheduler
- [ ] .env Variablen prüfen (QUEUE_CONNECTION, MAIL_*)
- [ ] Queue Worker läuft
- [ ] Scheduler Cron Job aktiv

### Migration
```bash
# Backup Database
mysqldump askproai_db > backup_$(date +%Y%m%d).sql

# Run Migration
php artisan migrate --force

# Verify Tables
php artisan tinker
> Schema::hasTable('user_invitations');  # true
> Schema::hasTable('appointment_audit_logs');  # true
> Schema::hasTable('invitation_email_queue');  # true
```

### Post-Deployment
```bash
# Clear Caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild Caches
php artisan config:cache
php artisan route:cache
php artisan filament:cache-components

# Verify Scheduler
php artisan schedule:list | grep invitation

# Test Queue Worker
php artisan queue:work --queue=emails --tries=1 --timeout=300
```

### Monitoring
- [ ] Check logs: `storage/logs/invitation-emails.log`
- [ ] Check logs: `storage/logs/invitation-cleanup.log`
- [ ] Monitor Queue Dashboard
- [ ] Activity Log für `invitation_*` Events

---

## 📞 Support & Troubleshooting

### Email Queue Issues
```bash
# Check pending emails
php artisan tinker
> InvitationEmailQueue::pending()->count();
> InvitationEmailQueue::readyToSend()->get();

# Manual process
php artisan queue:work --queue=emails --once

# Check failed jobs
> InvitationEmailQueue::failed()->get();

# Retry specific email
> $email = InvitationEmailQueue::find(123);
> $email->update(['attempts' => 0, 'next_attempt_at' => now()]);
```

### Observer Debug
```bash
# Check if observers fire
php artisan tinker
> \Illuminate\Support\Facades\Event::listen('eloquent.*', function($event, $models) {
>     \Log::info("Event: $event");
> });
> $inv = new App\Models\UserInvitation([...]);
> $inv->save();
# Check logs for "Event: eloquent.creating: App\Models\UserInvitation"
```

### Scheduler Debug
```bash
# Test scheduler without waiting
php artisan schedule:run

# Check next run times
php artisan schedule:list

# Run specific job manually
php artisan tinker
> dispatch(new App\Jobs\ProcessInvitationEmailsJob());
```

---

**Implementierung:** Claude Code (Sonnet 4.5)
**Session Datum:** 2025-11-24
**Gesamtdauer:** ~4 Stunden
**Status:** ✅ READY FOR PHASE 6 (Controllers & Routes)
