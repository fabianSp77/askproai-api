# Codebase Structure

**Analysis Date:** 2026-01-19

## Directory Layout

```
/var/www/api-gateway/
├── app/                    # Application code
│   ├── Console/           # Artisan commands
│   ├── Constants/         # Application constants
│   ├── Contracts/         # Interface definitions
│   ├── Domains/           # Domain events & listeners (DDD)
│   ├── Enums/             # PHP enums
│   ├── Events/            # Laravel events
│   ├── Exceptions/        # Custom exceptions
│   ├── Exports/           # Export classes (Excel)
│   ├── Filament/          # Admin panel (Resources, Pages, Widgets)
│   ├── Helpers/           # Helper classes
│   ├── Http/              # Controllers, Middleware, Requests
│   ├── Jobs/              # Queue jobs
│   ├── Listeners/         # Event listeners
│   ├── Livewire/          # Livewire components
│   ├── Mail/              # Mailable classes
│   ├── Models/            # Eloquent models
│   ├── Notifications/     # Laravel notifications
│   ├── Observers/         # Model observers
│   ├── Policies/          # Authorization policies
│   ├── Providers/         # Service providers
│   ├── Rules/             # Validation rules
│   ├── Scopes/            # Eloquent global scopes
│   ├── Services/          # Business logic & integrations
│   ├── Shared/            # Shared utilities (EventBus)
│   ├── Support/           # Support classes
│   ├── Traits/            # Reusable traits
│   ├── ValueObjects/      # Value objects
│   └── View/              # View composers
├── bootstrap/             # Laravel bootstrap
├── config/                # Configuration files
├── database/              # Migrations, seeders, factories
├── public/                # Web root (docs-site, assets)
├── resources/             # Views, JS, CSS
├── routes/                # Route definitions
├── storage/               # Logs, cache, uploads
├── tests/                 # Test suites
├── claudedocs/            # Developer documentation
└── .planning/             # GSD planning documents
```

## Directory Purposes

**`app/Console/Commands/`:**
- Purpose: Artisan CLI commands
- Contains: Data sync, monitoring, maintenance commands
- Key files:
  - `SyncCalcomBookings.php` - Import bookings from Cal.com
  - `SyncRetellCalls.php` - Import call data from Retell
  - `WarmCache.php` - Pre-warm caches
  - `BackfillCallAppointments.php` - Data migration

**`app/Domains/`:**
- Purpose: Domain-Driven Design bounded contexts
- Contains: Domain events and listeners
- Structure:
  - `Appointments/Events/` - AppointmentCreatedEvent, AppointmentCancelledEvent
  - `Appointments/Listeners/` - CalcomSyncListener, SendConfirmationListener
  - `VoiceAI/Events/` - CallStartedEvent
  - `Notifications/Events/` - SendConfirmationRequiredEvent

**`app/Filament/`:**
- Purpose: Filament 3 Admin Panel components
- Contains: Resources, Pages, Widgets, Actions
- Structure:
  - `Resources/` - CRUD resources (55+ entities)
  - `Resources/{Name}Resource/Pages/` - List, Create, Edit, View pages
  - `Pages/` - Custom dashboard pages
  - `Widgets/` - Dashboard widgets and stats
  - `Customer/` - Customer portal resources
  - `Actions/` - Reusable actions
  - `Concerns/` - Shared traits

**`app/Http/Controllers/`:**
- Purpose: Request handling
- Contains: API controllers, webhook handlers
- Key files:
  - `RetellFunctionCallHandler.php` (570KB) - AI function router
  - `RetellWebhookController.php` - Call event webhooks
  - `CalcomWebhookController.php` - Calendar sync
  - `ServiceDeskHandler.php` - Service gateway
  - `Api/RetellApiController.php` - Retell API endpoints
  - `Api/V2/` - V2 API endpoints

**`app/Http/Middleware/`:**
- Purpose: Request/response filtering
- Contains: Auth, rate limiting, signature verification
- Key files:
  - `TenantMiddleware.php` - Multi-tenant context
  - `VerifyRetellWebhookSignature.php` - Retell auth
  - `VerifyCalcomSignature.php` - Cal.com auth
  - `VerifyStripeWebhookSignature.php` - Stripe auth
  - `RateLimitMiddleware.php` - API rate limits

**`app/Jobs/`:**
- Purpose: Async queue jobs
- Contains: Background processing, sync jobs
- Key files:
  - `SyncAppointmentToCalcomJob.php` (88KB) - Bidirectional sync
  - `SendNotificationJob.php` - Email/SMS dispatch
  - `DeliverWebhookJob.php` - Outbound webhooks
  - `ServiceGateway/` - Service desk jobs

**`app/Models/`:**
- Purpose: Eloquent ORM models
- Contains: 80+ models with relationships
- Key files:
  - `Appointment.php` - Core appointment entity
  - `Call.php` - Voice call records
  - `Customer.php` - Customer data
  - `Company.php` - Tenant/company
  - `Service.php` - Bookable services
  - `Staff.php` - Staff members
  - `Branch.php` - Company branches
  - `ServiceCase.php` - Service desk cases
- Traits used: `BelongsToCompany`, `SoftDeletes`

**`app/Services/`:**
- Purpose: Business logic and integrations
- Contains: 36 service directories + standalone services
- Key directories:
  - `Retell/` - Retell AI services (29 files)
  - `Appointments/` - Booking logic
  - `ServiceGateway/` - External service integration
  - `Billing/` - Stripe/invoicing
  - `Cache/` - Cache management
  - `Booking/` - Composite booking
  - `Policies/` - Policy engine
- Key standalone services:
  - `CalcomService.php` (80KB) - Cal.com integration
  - `AppointmentAlternativeFinder.php` (68KB) - Availability
  - `BalanceService.php` - Credit management
  - `CostCalculator.php` - Call costing

**`app/Traits/`:**
- Purpose: Reusable model behaviors
- Contains: Multi-tenant, caching, API response traits
- Key files:
  - `BelongsToCompany.php` - Auto company_id isolation
  - `HasCompanyScope.php` - Query scope helper
  - `Cacheable.php` - Model caching
  - `OptimizedAppointmentQueries.php` - N+1 prevention

**`config/`:**
- Purpose: Application configuration
- Key files:
  - `calcom.php` - Cal.com settings
  - `retell.php` - Retell AI config
  - `gateway.php` - Service gateway
  - `billing.php` - Billing/Stripe
  - `features.php` - Feature flags
  - `services.php` - External API keys

**`resources/views/`:**
- Purpose: Blade templates
- Key directories:
  - `filament/` - Filament customizations
  - `emails/` - Email templates
  - `customer-portal/` - Portal views
  - `livewire/` - Livewire components

**`tests/`:**
- Purpose: Test suites
- Structure:
  - `Unit/` - PHPUnit unit tests
  - `Feature/` - Feature/integration tests
  - `E2E/` - Puppeteer E2E tests
  - `load/` - Load testing scripts
  - `*.sh` - Bash test scripts

## Key File Locations

**Entry Points:**
- `routes/api.php`: API route definitions
- `routes/web.php`: Web route definitions
- `app/Providers/AppServiceProvider.php`: Service bindings, boot logic
- `bootstrap/app.php`: Application bootstrap

**Configuration:**
- `config/calcom.php`: Cal.com API settings
- `config/retell.php`: Retell AI configuration
- `config/services.php`: External service credentials
- `config/gateway.php`: Service gateway config
- `.env`: Environment variables (not committed)

**Core Logic:**
- `app/Http/Controllers/RetellFunctionCallHandler.php`: AI function router
- `app/Services/CalcomService.php`: Calendar integration
- `app/Services/AppointmentAlternativeFinder.php`: Availability engine
- `app/Jobs/SyncAppointmentToCalcomJob.php`: Sync orchestration

**Testing:**
- `tests/Feature/`: Feature tests
- `tests/Unit/`: Unit tests
- `tests/E2E/`: End-to-end tests
- `phpunit.xml`: PHPUnit configuration

## Naming Conventions

**Files:**
- Models: `PascalCase.php` (e.g., `Appointment.php`)
- Controllers: `PascalCaseController.php` (e.g., `RetellApiController.php`)
- Services: `PascalCaseService.php` (e.g., `CalcomService.php`)
- Jobs: `PascalCaseJob.php` (e.g., `SyncAppointmentToCalcomJob.php`)
- Middleware: `PascalCase.php` (e.g., `TenantMiddleware.php`)
- Traits: `PascalCase.php` (e.g., `BelongsToCompany.php`)

**Filament Resources:**
- Resource: `{Model}Resource.php` (e.g., `AppointmentResource.php`)
- Pages: `{Model}Resource/Pages/{Action}{Model}.php`

**Directories:**
- Lowercase for standard Laravel directories
- PascalCase for domain-specific (e.g., `Appointments/`, `ServiceGateway/`)

## Where to Add New Code

**New Feature (complete domain):**
- Domain events: `app/Domains/{DomainName}/Events/`
- Domain listeners: `app/Domains/{DomainName}/Listeners/`
- Register in: `app/Providers/AppServiceProvider.php`

**New Model:**
- Model: `app/Models/{ModelName}.php`
- Migration: `database/migrations/`
- Use trait: `use BelongsToCompany;` for tenant isolation
- Observer: `app/Observers/{ModelName}Observer.php`

**New API Endpoint:**
- Controller: `app/Http/Controllers/Api/{Controller}.php`
- Route: `routes/api.php`
- Request validation: `app/Http/Requests/`

**New Service:**
- Simple: `app/Services/{ServiceName}Service.php`
- Complex: `app/Services/{Domain}/{ServiceName}Service.php`
- With interface: `app/Services/{Domain}/Contracts/{InterfaceName}.php`

**New Filament Resource:**
- Resource: `app/Filament/Resources/{Model}Resource.php`
- Pages: `app/Filament/Resources/{Model}Resource/Pages/`
- Use: `php artisan make:filament-resource {Model}`

**New Filament Widget:**
- Widget: `app/Filament/Widgets/{WidgetName}.php`
- Use: `php artisan make:filament-widget {WidgetName}`

**New Filament Page:**
- Page: `app/Filament/Pages/{PageName}.php`
- Use: `php artisan make:filament-page {PageName}`

**New Queue Job:**
- Job: `app/Jobs/{JobName}.php`
- Domain-specific: `app/Jobs/{Domain}/{JobName}.php`

**New Artisan Command:**
- Command: `app/Console/Commands/{CommandName}.php`

**New Test:**
- Unit: `tests/Unit/{Domain}/{TestName}Test.php`
- Feature: `tests/Feature/{Domain}/{TestName}Test.php`

**Utilities:**
- Helper: `app/Helpers/{HelperName}.php`
- Trait: `app/Traits/{TraitName}.php`

## Special Directories

**`claudedocs/`:**
- Purpose: Developer documentation, RCAs, guides
- Generated: No (manually written)
- Committed: Yes
- Note: Reference for AI assistants and developers

**`.planning/`:**
- Purpose: GSD planning and codebase analysis
- Generated: Yes (by mapping agents)
- Committed: Optional
- Subdirectories: `codebase/`, plans

**`storage/framework/views/`:**
- Purpose: Compiled Blade templates
- Generated: Yes (automatic)
- Committed: No

**`public/docs-site/`:**
- Purpose: Static documentation site (VitePress)
- Generated: Yes (npm run docs:build)
- Committed: Yes

**`backups/`:**
- Purpose: Code backups before major changes
- Generated: Manual
- Committed: Partial

**`vendor/`:**
- Purpose: Composer dependencies
- Generated: Yes (composer install)
- Committed: No

**`node_modules/`:**
- Purpose: NPM dependencies
- Generated: Yes (npm install)
- Committed: No

---

*Structure analysis: 2026-01-19*
