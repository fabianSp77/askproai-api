# AskPro AI Gateway - Claude Code Guide

> **Purpose**: Comprehensive guide for AI assistants working with this codebase
> **Last Updated**: 2026-01-19

## Quick Reference

| Aspect | Value |
|--------|-------|
| **Stack** | Laravel 11.31 / Filament 3.3 / PHP 8.2+ / PostgreSQL / Redis |
| **Domain** | AI Voice Agent for Appointment Booking (B2B SaaS) |
| **Multi-Tenancy** | Company-scoped via `CompanyScope` |
| **Deployment** | GitHub Actions + SSH (atomic symlink) |
| **Git Workflow** | GitFlow (feature → develop → main) |

---

## Communication Style
- User speaks German frequently. 'weiter' = continue, 'ja mach das' = yes do it, 'fertig' = done/finished.
- When user asks to do something, DO IT immediately. Don't explain what you would do or ask for confirmation — just implement it.
- Never imply an email was sent unless you actually sent it via an API or tool.

## Git & Commits
- Do NOT commit or push unless the user explicitly asks. Wait for user confirmation before git operations.
- When pushing, handle GitHub remote errors gracefully — retry once before reporting failure.

## Deployment & Scripts
- When editing deploy scripts or shell scripts, always use subshells `(cd /path && command)` instead of bare `cd` to avoid directory state leaking across commands.
- When running deploy scripts, ensure they handle non-interactive mode (no TTY prompts).

## Retell / Voice Agent
- Retell API uses hyphens not underscores in field names (e.g., `response-type` not `response_type`).
- Voice IDs must be Retell custom voice IDs, NOT ElevenLabs IDs.
- Always validate JSON for trailing commas after edits.
- **Pre-Deploy Checklist** (MUST show validation results before deploying):
  1. Validate all JSON payloads for correct field naming (hyphens not underscores)
  2. Verify all voice IDs are Retell custom IDs (not ElevenLabs IDs)
  3. Check for JSON syntax errors (trailing commas, missing brackets)
  4. Show validation results to user before proceeding with deployment

## Testing & CI
- After making changes, always clear OPcache and all Laravel caches before verifying: `php artisan optimize:clear`
- When fixing CI tests, run the full test suite locally first to catch all failures at once rather than discovering them incrementally across multiple push cycles.

## Livewire / Filament Stack
- This project uses Laravel + Livewire + Filament.
- When debugging Livewire errors (ComponentNotFoundException, snapshot errors, reactive prop mutations), check AdminPanelProvider.php widget/component discovery first.
- Never use `$getRecord()` in components without verifying the parent resource model type.

---

## 1. Quick Start

### First-Time Setup
```bash
# Clone and install
git clone git@github.com:askproai/api-gateway.git
cd api-gateway
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Required Environment Variables
```bash
# Core Application
APP_KEY=                          # Generate with php artisan key:generate
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql               # or mysql
DB_HOST=127.0.0.1
DB_DATABASE=askproai_db
DB_USERNAME=
DB_PASSWORD=

# Redis (required for caching, queues, slot locking)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Cal.com Integration (Required)
CALCOM_API_KEY=cal_live_xxx       # API key from Cal.com settings
CALCOM_BASE_URL=https://api.cal.com/v2
CALCOM_TEAM_SLUG=your-team        # Workspace slug
CALCOM_WEBHOOK_SECRET=            # For webhook verification

# Retell AI (Required)
RETELLAI_API_KEY=                 # Retell API key
RETELL_AGENT_ID=agent_xxx         # Default agent ID
RETELLAI_WEBHOOK_SECRET=          # Webhook HMAC secret

# Stripe (Required for billing)
STRIPE_KEY=pk_xxx
STRIPE_SECRET=sk_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# Twilio (SMS notifications)
TWILIO_SID=
TWILIO_AUTH_TOKEN=
TWILIO_FROM=

# AWS S3 (Audio storage)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=
```

### Development Commands
```bash
# Start all services (recommended)
composer dev                      # Runs: server, queue, logs, vite concurrently

# Individual services
php artisan serve                 # HTTP server at localhost:8000
php artisan queue:listen          # Queue worker
npm run dev                       # Vite dev server

# Database
php artisan migrate               # Run migrations
php artisan db:seed               # Seed test data
php artisan tinker                # Laravel REPL
```

### Test Commands
```bash
# Unit tests (recommended for development)
vendor/bin/pest tests/Unit

# Full test suite
vendor/bin/pest

# E2E tests
npx playwright test tests/E2E/playwright/

# Performance tests (K6)
k6 run tests/Performance/k6/baseline-booking-flow.js
```

---

## 2. Architecture Overview

### Request Flow
```
┌─────────────┐     ┌──────────────────┐     ┌─────────────┐
│  Retell AI  │────▶│  API Controllers │────▶│  Services   │
│  (Voice)    │     │  - Function calls│     │  - Business │
└─────────────┘     │  - Webhooks      │     │    Logic    │
                    └──────────────────┘     └──────┬──────┘
                                                    │
┌─────────────┐     ┌──────────────────┐     ┌──────▼──────┐
│  Cal.com    │◀───▶│  Jobs (Queue)    │◀───▶│   Models    │
│  (Calendar) │     │  - Sync          │     │  - Eloquent │
└─────────────┘     │  - Notifications │     │  - Scoped   │
                    └──────────────────┘     └─────────────┘
```

### Directory Structure
```
app/
├── Console/              # Artisan commands
├── Contracts/            # Interfaces
├── Domains/              # Domain-specific logic (DDD)
├── Enums/                # PHP enums
├── Events/               # Event classes
├── Exceptions/           # Custom exceptions
├── Filament/             # Admin panel (Filament 3)
│   ├── Resources/        # CRUD resources
│   ├── Pages/            # Custom pages
│   └── Widgets/          # Dashboard widgets
├── Http/
│   ├── Controllers/      # HTTP controllers
│   │   ├── Api/          # API endpoints
│   │   └── *.php         # Webhook controllers
│   ├── Middleware/       # Request middleware
│   └── Requests/         # Form requests
├── Jobs/                 # Queue jobs
├── Listeners/            # Event listeners
├── Mail/                 # Mailable classes
├── Models/               # Eloquent models
├── Observers/            # Model observers
├── Policies/             # Authorization policies
├── Providers/            # Service providers
├── Scopes/               # Global query scopes
├── Services/             # Business logic (core)
│   ├── Appointments/     # Booking services
│   ├── Billing/          # Stripe billing
│   ├── Booking/          # Slot locking, availability
│   ├── Cache/            # Cache management
│   ├── Customer/         # Customer management
│   ├── CustomerPortal/   # Customer portal services
│   ├── Retell/           # Retell AI services
│   ├── Saga/             # Distributed transactions
│   └── Tracing/          # Distributed tracing
├── Traits/               # Reusable traits
└── ValueObjects/         # Immutable value objects

config/
├── features.php          # Feature flags (IMPORTANT)
├── calcom.php            # Cal.com settings
├── billing.php           # Billing configuration
└── services.php          # External API credentials

resources/views/
├── filament/             # Filament blade components
├── emails/               # Email templates
└── livewire/             # Livewire components

tests/
├── Unit/                 # Unit tests (Pest)
├── Feature/              # Feature/integration tests
├── E2E/                  # Playwright E2E tests
└── Performance/          # K6 performance tests
```

### Service Layer Pattern
All business logic lives in Services. Controllers are thin:
```php
// Controllers: dispatch to services
public function checkAvailability(Request $request): JsonResponse
{
    $result = $this->availabilityService->check($request->validated());
    return response()->json($result);
}

// Services: contain business logic
class WeeklyAvailabilityService
{
    public function check(array $params): AvailabilityResult
    {
        // Business logic here
    }
}
```

---

## 3. Key Integrations

### Retell AI (Voice Agent)

**Purpose**: AI voice agent for automated appointment booking via phone calls.

**Key Files**:
| File | Purpose |
|------|---------|
| `app/Http/Controllers/RetellWebhookController.php` | Main webhook handler |
| `app/Http/Controllers/RetellFunctionCallHandler.php` | Function call dispatcher |
| `app/Services/Retell/AppointmentCreationService.php` | Create appointments |
| `app/Services/Retell/SlotIntelligenceService.php` | Smart slot matching |
| `app/Services/Retell/CustomerRecognitionService.php` | Caller identification |

**Function Calls** (invoked by Retell agent):
| Function | Purpose |
|----------|---------|
| `check_availability` | Check if slot is available |
| `collect_appointment_info` | Collect and validate booking data |
| `start_booking` | Create the appointment |
| `get_service_info` | Retrieve service details |
| `cancel_appointment` | Cancel existing appointment |

**Webhook Events**:
| Event | Handler |
|-------|---------|
| `call_started` | Initialize call session |
| `call_ended` | Finalize and store call data |
| `call_analyzed` | Process call transcript |

### Cal.com (Calendar/Scheduling)

**Purpose**: Scheduling backend for availability and booking sync.

**Key Files**:
| File | Purpose |
|------|---------|
| `app/Services/CalcomV2Service.php` | Cal.com API v2 client |
| `app/Http/Controllers/CalcomWebhookController.php` | Webhook handler |
| `app/Jobs/SyncAppointmentToCalcomJob.php` | Async sync job |
| `app/Services/CalcomHostMappingService.php` | Staff ↔ Host mapping |

**Sync Flow**:
```
Appointment Created (Laravel)
    ↓
SyncAppointmentToCalcomJob (Queue)
    ↓
CalcomV2Service::createBooking()
    ↓
Cal.com API v2
    ↓
Webhook: booking_created → CalcomWebhookController
    ↓
Update local appointment with calcom_booking_uid
```

**Important**: Cal.com uses Team-based architecture. Each staff member is a Cal.com team member.

### Stripe (Billing)

**Key Files**:
| File | Purpose |
|------|---------|
| `app/Services/Billing/StripeInvoicingService.php` | Invoice generation |
| `app/Http/Controllers/StripePaymentController.php` | Payment webhooks |
| `config/billing.php` | Company & pricing config |

### Twilio (SMS)

**Key Files**:
| File | Purpose |
|------|---------|
| `app/Services/SmsService.php` | SMS sending |
| `app/Services/Communication/NotificationService.php` | Notification dispatch |

---

## 4. Multi-Tenancy

### How It Works

This is a **company-scoped** multi-tenant application. Every user belongs to a `company_id`, and most models are automatically filtered.

**CompanyScope** (`app/Scopes/CompanyScope.php`):
```php
// Automatically applied to scoped models
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && !$user->hasRole('super_admin')) {
            $builder->where('company_id', Auth::user()->company_id);
        }
    }
}
```

### Implementing Multi-Tenancy in Models

```php
use App\Scopes\CompanyScope;

class Appointment extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);

        // Auto-set company_id on create
        static::creating(function ($model) {
            if (Auth::check() && !$model->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }
}
```

### Bypassing Scope (Admin Operations)

```php
// For specific company
Appointment::forCompany($companyId)->get();

// For all companies (super_admin only)
Appointment::allCompanies()->get();

// Remove scope temporarily
Appointment::withoutGlobalScope(CompanyScope::class)->get();
```

### Security Rules
- **ALWAYS** verify `company_id` in authorization policies
- **NEVER** expose cross-tenant data in API responses
- **ALWAYS** test tenant isolation in new features

---

## 5. Feature Flags

**Location**: `config/features.php`

All new features should be behind flags for safe rollout.

### Active Feature Flags

| Flag | Default | Description |
|------|---------|-------------|
| `phonetic_matching_enabled` | `false` | Phonetic name matching for caller ID |
| `skip_alternatives_for_voice` | `false` | Skip alternative slot suggestions |
| `processing_time_enabled` | `false` | Service phase splitting (e.g., hair dye) |
| `customer_portal` | `false` | Customer self-service portal |
| `parallel_calcom_booking` | `true` | Parallel Cal.com sync (70% faster) |
| `slot_locking.enabled` | `false` | Redis-based race condition prevention |
| `slot_intelligence.enabled` | `true` | Smart slot matching & fuzzy times |

### Usage Pattern

```php
// Check feature flag
if (config('features.phonetic_matching_enabled')) {
    // Use phonetic matching
}

// With company whitelist
if (config('features.processing_time_enabled') &&
    in_array($company->id, config('features.processing_time_company_whitelist'))) {
    // Feature enabled for this company
}
```

### Adding New Feature Flags

```php
// In config/features.php
'my_new_feature' => env('FEATURE_MY_NEW_FEATURE', false),

// In .env
FEATURE_MY_NEW_FEATURE=false
```

---

## 6. Testing Guide

### Test Structure
```
tests/
├── Unit/                         # Fast, isolated tests
│   ├── Services/                 # Service tests
│   ├── Models/                   # Model tests
│   └── RcaPreventionTest.php     # Bug regression tests
├── Feature/                      # Integration tests
│   ├── Api/                      # API endpoint tests
│   └── Integration/              # Full flow tests
├── E2E/                          # Browser tests
│   └── playwright/               # Playwright specs
└── Performance/                  # Load tests
    └── k6/                       # K6 scripts
```

### Running Tests

```bash
# Quick unit tests (during development)
vendor/bin/pest tests/Unit

# Specific test file
vendor/bin/pest tests/Unit/Services/WeeklyAvailabilityServiceTest.php

# With coverage
vendor/bin/pest --coverage

# Run specific group
vendor/bin/pest --group=slow
vendor/bin/pest --exclude-group=slow

# E2E tests
npx playwright test
npx playwright test --headed                    # With browser
npx playwright test tests/E2E/playwright/login.spec.ts
```

### CI Pipeline

The CI runs these test suites in order:
1. **Unit Tests** (Pest) - ~2 min
2. **RCA Prevention Tests** - Bug regression tests
3. **Integration Tests** - API & database
4. **Performance Tests** (K6) - Booking flow < 45s target
5. **E2E Tests** (Playwright) - Browser automation
6. **Security Tests** - Multi-tenant isolation, PHPStan

---

## 7. Deployment

### GitFlow Workflow

```
feature/xxx  →  develop  →  main  →  Production
     ↓             ↓          ↓
   (PR)       (staging)  (production)
```

**Branch Rules**:
- `feature/*` - New features, branch from `develop`
- `bugfix/*` - Bug fixes, branch from `develop`
- `hotfix/*` - Production hotfixes, branch from `main`
- `develop` - Integration branch, auto-deploys to staging
- `main` - Production branch, auto-deploys to production

### CI/CD Pipeline (GitHub Actions)

**Push to `develop`**:
1. Build artifacts
2. Run test suite
3. Deploy to staging (`staging.askproai.de`)

**Push to `main`**:
1. Verify staging health
2. Create pre-deploy backup (DB + app)
3. Build artifacts
4. Deploy to production (`api.askproai.de`)
5. Run smoke tests
6. Notify on failure

### Deployment Process (Atomic Symlink)

```bash
# Directory structure on server
/var/www/api-gateway/
├── releases/               # Multiple release directories
│   ├── 20260119_120000-abc123/
│   └── 20260119_100000-def456/
├── current -> releases/... # Symlink to active release
└── shared/                 # Persistent data
    ├── storage/            # Logs, uploads
    └── .env/               # Environment files
```

**Deployment Steps**:
1. Upload bundle to `/tmp/`
2. Extract to `/releases/{timestamp}-{commit}/`
3. Link shared resources (storage, .env)
4. Run migrations
5. Clear & rebuild caches
6. **Atomic symlink switch** (`mv -Tf`)
7. Reload PHP-FPM & Nginx
8. Run health checks

### Manual Deployment (Emergency)

```bash
# SSH to server
ssh www-data@api.askproai.de

# Navigate to project
cd /var/www/api-gateway/current

# Run migrations
php artisan migrate --force

# Clear caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# Reload services
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx
```

### Rollback

```bash
# Automatic rollback script
ssh www-data@api.askproai.de
cd /var/www/api-gateway
./scripts/rollback-production.sh

# Manual rollback (symlink to previous release)
cd /var/www/api-gateway
ln -sfn releases/{previous-release} current
sudo systemctl reload php8.3-fpm
```

---

## 8. Common Issues & Troubleshooting

### 500 Errors

**Check logs first**:
```bash
tail -f storage/logs/laravel.log
```

**Common causes**:
| Error | Cause | Fix |
|-------|-------|-----|
| Class not found | Autoload issue | `composer dump-autoload` |
| Config cache | Stale config | `php artisan config:clear` |
| Permission denied | Storage permissions | `chmod -R 775 storage` |
| Database connection | Wrong credentials | Check `.env` |

### Cal.com Sync Failures

**Symptoms**: Appointments created locally but not in Cal.com

**Debug steps**:
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Check Cal.com API logs
tail -f storage/logs/calcom.log
```

**Common causes**:
- Invalid API key → Check `CALCOM_API_KEY`
- Host not mapped → Check `CalcomHostMapping` for staff
- Event type missing → Verify `CALCOM_EVENT_TYPE_ID`

### Retell Webhook Issues

**Symptoms**: Calls not being tracked, missing transcripts

**Debug**:
```bash
# Check webhook logs
grep "retell" storage/logs/laravel.log

# Verify webhook secret
php artisan tinker
>>> config('services.retellai.webhook_secret')
```

**Common causes**:
- Signature mismatch → Verify `RETELLAI_WEBHOOK_SECRET`
- Firewall blocking → Whitelist Retell IPs
- Queue not running → Start `php artisan queue:work`

### Race Conditions (Double Bookings)

**The Problem**: Same slot booked twice during concurrent requests.

**Solution**: Enable slot locking (feature flag):
```bash
# In .env
FEATURE_SLOT_LOCKING=true
```

**How it works**:
1. `check_availability` acquires Redis lock
2. Lock held during booking flow (5 min TTL)
3. `start_booking` validates lock ownership
4. Prevents concurrent bookings for same slot

### Queue Problems

**Jobs not processing**:
```bash
# Check if worker is running
ps aux | grep "queue:work"

# Start worker
php artisan queue:work --tries=3

# Check pending jobs
php artisan tinker
>>> \DB::table('jobs')->count()
```

**Memory issues**:
```bash
# Use queue:listen for development (restarts per job)
php artisan queue:listen

# For production, use supervisor with --max-jobs
[program:queue-worker]
command=php artisan queue:work --max-jobs=1000 --max-time=3600
```

### Tech Debt Warnings

**Areas requiring attention**:
| Area | Issue | Priority |
|------|-------|----------|
| `RetellFunctionCallHandler` | God class, needs refactoring | Medium |
| `CalcomV2Service` | Missing circuit breaker | High |
| Feature tests | Many removed, need rebuilding | Medium |
| Filament components | Some double-encoded JSON | Low |

---

## 9. Critical Files Reference

### Controllers

| Controller | Purpose |
|------------|---------|
| `RetellWebhookController` | Retell call webhooks |
| `RetellFunctionCallHandler` | Retell function call dispatch |
| `CalcomWebhookController` | Cal.com sync webhooks |
| `StripePaymentController` | Stripe payment webhooks |
| `UnifiedWebhookController` | Generic webhook router |

### Key Services

| Service | Purpose |
|---------|---------|
| `WeeklyAvailabilityService` | Availability checking |
| `AppointmentCreationService` | Booking creation |
| `SlotLockService` | Redis-based slot locking |
| `SlotIntelligenceService` | Smart time matching |
| `CalcomV2Service` | Cal.com API client |
| `StripeInvoicingService` | Invoice generation |
| `CustomerRecognitionService` | Caller identification |

### Configuration Files

| File | Purpose |
|------|---------|
| `config/features.php` | Feature flags |
| `config/calcom.php` | Cal.com settings |
| `config/services.php` | External API credentials |
| `config/billing.php` | Billing & company info |
| `config/companyscope.php` | Multi-tenancy config |

### Documentation

| Path | Content |
|------|---------|
| `claudedocs/00_INDEX.md` | Documentation index |
| `claudedocs/02_BACKEND/Calcom/` | Cal.com integration docs |
| `claudedocs/03_API/Retell_AI/` | Retell integration docs |
| `claudedocs/06_SECURITY/RCA/` | Root cause analyses |

---

## 10. API & Webhooks

### Webhook Endpoints

| Endpoint | Source | Purpose |
|----------|--------|---------|
| `POST /api/retell/webhook` | Retell AI | Call lifecycle events |
| `POST /api/retell/function-call` | Retell AI | Function call execution |
| `POST /api/calcom/webhook` | Cal.com | Booking sync events |
| `POST /api/stripe/webhook` | Stripe | Payment events |

### Retell Function Calls

| Function | When Called |
|----------|-------------|
| `check_availability` | User asks about available times |
| `collect_appointment_info` | Agent collects booking details |
| `start_booking` | User confirms appointment |
| `get_service_info` | User asks about services |
| `cancel_appointment` | User wants to cancel |
| `transfer_to_human` | Escalation needed |

### Security Notes

- All webhooks verify HMAC signatures
- Cal.com: `CALCOM_WEBHOOK_SECRET`
- Retell: `RETELLAI_WEBHOOK_SECRET`
- Stripe: `STRIPE_WEBHOOK_SECRET`
- **Never** disable signature verification in production

---

## 11. Development Guidelines

### Code Style

```bash
# Format code (PSR-12 via Pint)
vendor/bin/pint

# Static analysis
vendor/bin/phpstan analyse
```

### Creating New Features

1. **Check existing patterns** in similar files
2. **Add feature flag** in `config/features.php`
3. **Write tests first** (TDD encouraged)
4. **Use service layer** for business logic
5. **Document** in appropriate `claudedocs/` category

### Commit Messages

```
feat: Add slot locking for race condition prevention
fix: Resolve Cal.com sync timeout in webhook handler
docs: Update Retell integration documentation
refactor: Extract availability checking to service
test: Add unit tests for phonetic matching
```

### Pull Request Checklist

- [ ] Tests pass locally (`vendor/bin/pest`)
- [ ] Code formatted (`vendor/bin/pint`)
- [ ] Feature flag added (if new feature)
- [ ] Documentation updated
- [ ] No hardcoded credentials
- [ ] Multi-tenant isolation verified

---

## Quick Commands Reference

```bash
# Development
composer dev                      # Start all services
php artisan tinker               # Laravel REPL

# Database
php artisan migrate              # Run migrations
php artisan migrate:rollback     # Rollback last migration
php artisan db:seed              # Seed database

# Cache
php artisan cache:clear          # Clear application cache
php artisan config:clear         # Clear config cache
php artisan route:clear          # Clear route cache
php artisan optimize:clear       # Clear all caches

# Queue
php artisan queue:work           # Process queue jobs
php artisan queue:failed         # List failed jobs
php artisan queue:retry all      # Retry all failed jobs

# Testing
vendor/bin/pest                  # Run all tests
vendor/bin/pest tests/Unit       # Run unit tests only
vendor/bin/pest --coverage       # Run with coverage

# Deployment
php artisan down                 # Maintenance mode
php artisan up                   # Exit maintenance mode
```

---

*For detailed documentation, see `claudedocs/00_INDEX.md`*
