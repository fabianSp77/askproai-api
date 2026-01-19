# Architecture

**Analysis Date:** 2026-01-19

## Pattern Overview

**Overall:** Multi-Tenant Service-Oriented Laravel Architecture with Domain-Driven Design elements

**Key Characteristics:**
- Multi-tenant isolation via `CompanyScope` global scope (automatic query filtering)
- Service layer pattern separating business logic from controllers
- Event-driven architecture with EventBus for domain events
- Circuit breaker pattern for external API resilience (Cal.com, Retell)
- Filament 3 Admin Panel for all CRUD operations
- Webhook-driven integrations (Retell AI, Cal.com, Stripe)

## Layers

**HTTP Layer (Controllers):**
- Purpose: Handle incoming requests, validate input, return responses
- Location: `app/Http/Controllers/`
- Contains: API controllers, webhook handlers, admin routes
- Depends on: Services, Models
- Used by: Routes (`routes/api.php`, `routes/web.php`)
- Key files:
  - `app/Http/Controllers/RetellFunctionCallHandler.php` (570KB - main AI function router)
  - `app/Http/Controllers/RetellWebhookController.php` (webhook events)
  - `app/Http/Controllers/CalcomWebhookController.php` (Cal.com sync)
  - `app/Http/Controllers/Api/RetellApiController.php` (Retell API endpoints)

**Service Layer:**
- Purpose: Business logic, external integrations, complex operations
- Location: `app/Services/`
- Contains: Domain services, API clients, validators
- Depends on: Models, External APIs
- Used by: Controllers, Jobs, Commands
- Key files:
  - `app/Services/CalcomService.php` (80KB - Cal.com API integration)
  - `app/Services/AppointmentAlternativeFinder.php` (68KB - availability logic)
  - `app/Services/Retell/AppointmentCreationService.php` (59KB)
  - `app/Services/Retell/DateTimeParser.php` (58KB - German datetime parsing)

**Domain Layer:**
- Purpose: Domain events, listeners for cross-cutting concerns
- Location: `app/Domains/`
- Contains: Events, Listeners organized by domain
- Depends on: Models, Services
- Used by: EventBus (registered in AppServiceProvider)
- Structure:
  - `app/Domains/Appointments/Events/` - AppointmentCreatedEvent, AppointmentCancelledEvent
  - `app/Domains/Appointments/Listeners/` - CalcomSyncListener, SendConfirmationListener
  - `app/Domains/VoiceAI/Events/` - CallStartedEvent
  - `app/Domains/Notifications/Events/` - SendConfirmationRequiredEvent

**Model Layer:**
- Purpose: Data representation, relationships, scopes, business rules
- Location: `app/Models/`
- Contains: Eloquent models with multi-tenant traits
- Depends on: Database, Traits
- Used by: All layers
- Key patterns:
  - `BelongsToCompany` trait for automatic tenant isolation
  - `CompanyScope` global scope for query filtering
  - Observer pattern for side effects (CalcomSync, Notifications)

**Filament Admin Layer:**
- Purpose: Admin UI for data management
- Location: `app/Filament/`
- Contains: Resources, Pages, Widgets, Actions
- Depends on: Models, Services
- Structure:
  - `app/Filament/Resources/` (55+ resources for all entities)
  - `app/Filament/Pages/` (dashboards, settings, wizards)
  - `app/Filament/Widgets/` (stats, charts, activity feeds)
  - `app/Filament/Customer/` (customer portal resources)

## Data Flow

**Voice AI Appointment Booking Flow:**

1. Incoming call triggers Retell AI webhook (`POST /api/webhooks/retell`)
2. `RetellWebhookController` creates/updates `Call` record
3. AI function calls hit `RetellFunctionCallHandler::handleFunctionCall()`
4. `check_availability` → `CalcomService::getAvailability()` → Cal.com API
5. `collect_appointment_info` → validates customer data
6. `book_appointment` → `AppointmentCreationService::create()`
7. `Appointment` model created → `AppointmentObserver` fires
8. `AppointmentCreatedEvent` dispatched via EventBus
9. `CalcomSyncListener` queues `SyncAppointmentToCalcomJob`
10. `SendConfirmationListener` triggers notification

**Cal.com Bidirectional Sync Flow:**

1. Cal.com webhook → `POST /api/calcom/webhook`
2. `CalcomWebhookController::handle()` validates signature
3. Parse event type (booking.created, booking.cancelled, booking.rescheduled)
4. Find matching `Appointment` by `calcom_booking_uid`
5. Update local appointment status
6. Log to `AppointmentAuditLog`

**State Management:**
- Redis for caching (availability, configurations, session)
- Database transactions for appointment booking (optimistic locking)
- Queue jobs for async operations (email, sync, webhooks)

## Key Abstractions

**Multi-Tenant Isolation:**
- Purpose: Automatic data isolation per company
- Implementation: `app/Traits/BelongsToCompany.php` + `app/Scopes/CompanyScope.php`
- Pattern: Global scope applied via trait boot method
- Bypass: `withoutCompanyScope()`, `forCompany($id)`, `allCompanies()` macros

**Circuit Breaker:**
- Purpose: Resilience for external API calls
- Implementation: `app/Services/CircuitBreaker.php`
- Used by: CalcomService, RetellApiClient
- Config: 5 failures → open for 60s → 2 successes to close

**Rate Limiting:**
- Purpose: Prevent API abuse and external service limits
- Implementation: `app/Services/CalcomApiRateLimiter.php`
- Middleware: `app/Http/Middleware/RateLimitMiddleware.php`

**Idempotency:**
- Purpose: Prevent duplicate bookings from retried requests
- Location: `app/Services/Idempotency/`
- Pattern: Idempotency key stored with appointments

## Entry Points

**API Entry Points:**
- Location: `routes/api.php`
- Key routes:
  - `POST /api/webhooks/retell` - Retell call events
  - `POST /api/webhooks/retell/function` - Real-time AI function calls
  - `POST /api/calcom/webhook` - Cal.com sync
  - `POST /api/webhooks/stripe` - Payment processing
  - `POST /api/retell/*` - Retell AI function endpoints
  - `GET /api/health/*` - Health checks

**Web Entry Points:**
- Location: `routes/web.php`
- Key routes:
  - `/` → redirects to `/admin`
  - `/admin/*` - Filament admin panel
  - `/kundenportal/*` - Customer portal
  - `/docs/*` - Internal documentation

**Console Entry Points:**
- Location: `app/Console/Commands/`
- Key commands:
  - `SyncCalcomBookings` - Import Cal.com bookings
  - `SyncRetellCalls` - Import Retell call data
  - `SendDailyProfitReport` - Scheduled reporting
  - `WarmCache` - Cache warming

**Queue Workers:**
- Location: `app/Jobs/`
- Key jobs:
  - `SyncAppointmentToCalcomJob` (88KB - bidirectional sync)
  - `SendNotificationJob` - Email/SMS dispatch
  - `DeliverWebhookJob` - Outbound webhooks

## Error Handling

**Strategy:** Layered exception handling with logging

**Patterns:**
- Custom exceptions in `app/Exceptions/Appointments/`
- Global handler in `app/Exceptions/Handler.php`
- Structured logging with context (call_id, company_id)
- Circuit breaker for transient failures

**Key Exception Classes:**
- `CalcomBookingException` - Cal.com API failures
- `CustomerValidationException` - Invalid customer data
- `AppointmentDatabaseException` - DB constraint violations

## Cross-Cutting Concerns

**Logging:**
- Laravel logging with channels
- Log sanitization via `app/Helpers/LogSanitizer.php`
- Memory monitoring in production (AppServiceProvider)

**Validation:**
- Form requests in `app/Http/Requests/`
- Service-level validation in `app/Services/Validation/`
- Multi-tenant input validation observers

**Authentication:**
- Filament authentication for admin panel
- Sanctum tokens for API/Customer Portal
- Webhook signature verification (Retell, Cal.com, Stripe)

**Authorization:**
- Filament Shield for role-based access
- Policies in `app/Policies/`
- Super admin bypass in CompanyScope

---

*Architecture analysis: 2026-01-19*
