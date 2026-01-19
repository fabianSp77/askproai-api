# External Integrations

**Analysis Date:** 2026-01-19

## APIs & External Services

**Cal.com (Scheduling):**
- Purpose: Appointment scheduling, availability management, team-based booking
- SDK/Client: `app/Services/CalcomV2Client.php` (custom HTTP client with HTTP/2)
- Auth: `CALCOM_API_KEY`, `CALCOM_WEBHOOK_SECRET`
- API Version: `2024-08-13` (configurable via `CALCOM_API_VERSION`)
- Endpoints used: `/v2/bookings`, `/v2/slots/available`, team-scoped endpoints
- Features: OAuth support, webhook integration, bidirectional sync

**Retell AI (Voice Agent):**
- Purpose: AI-powered voice calls for appointment booking
- SDK/Client: `app/Services/RetellApiClient.php`, `app/Services/RetellAIService.php`
- Auth: `RETELLAI_API_KEY`, `RETELLAI_WEBHOOK_SECRET`, `RETELLAI_FUNCTION_SECRET`
- Base URL: `https://api.retell.ai` (configurable)
- Function calls: `check_availability`, `collect_appointment`, `book_appointment`, `initialize_call`
- Webhook events: Call completed, function calls

**Stripe (Payments):**
- Purpose: Payment processing, invoicing, subscription billing
- SDK/Client: `app/Services/Billing/StripeInvoicingService.php`, `app/Services/StripeCheckoutService.php`
- Auth: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- Features: Checkout sessions, invoices, webhook processing

**Twilio (Communications):**
- Purpose: SMS and WhatsApp messaging
- SDK/Client: `app/Services/SmsService.php`, Twilio SDK
- Auth: `TWILIO_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM_NUMBER`
- Channels: `app/Services/Notifications/Channels/SmsChannel.php`, `WhatsAppChannel.php`
- Features: Appointment confirmations, reminders, notifications

**Samedi (Medical Scheduling):**
- Purpose: Integration with Samedi medical appointment system
- Config: `SAMEDI_BASE_URL`, `SAMEDI_CLIENT_ID`, `SAMEDI_CLIENT_SECRET`
- Base URL: `https://api.samedi.de/api/v1`

**Google Translate (Translation):**
- Purpose: Free translation service for multilingual support
- SDK/Client: `app/Services/FreeTranslationService.php` via `stichoza/google-translate-php`
- Features: Auto-detection, German translation, 30-day caching

**European Central Bank (Exchange Rates):**
- Purpose: Currency exchange rate fetching
- SDK/Client: `app/Services/ExchangeRateService.php`
- API: `https://api.frankfurter.app/latest`
- Supported: EUR, USD, GBP

**Firebase (Future Integration):**
- Purpose: Push notifications (configured but not heavily used)
- Auth: `FIREBASE_CREDENTIALS`, `FIREBASE_PROJECT_ID`

## Data Storage

**Primary Database:**
- Type: MySQL/MariaDB (PostgreSQL configured as fallback)
- Connection: `DB_CONNECTION=mysql` (production), `sqlite` (local default)
- ORM: Eloquent
- Features: Strict mode, UTF8MB4, SSL support

**Caching:**
- Primary: Redis (`phpredis` client)
- Fallback: Database cache
- Connection: `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`
- Uses: Session, cache, queue, slot locking

**File Storage:**
- Local: `storage/app/private`, `storage/app/public`
- S3/MinIO: Audio recordings (`audio-storage` disk)
- Config: `AUDIO_STORAGE_DRIVER=s3` for production
- Audio S3: `AUDIO_S3_BUCKET`, `AUDIO_S3_ENDPOINT` (MinIO compatible)

**Queue:**
- Driver: Database (default), Redis (production recommended)
- Table: `jobs`, `failed_jobs`, `job_batches`
- Connection: `QUEUE_CONNECTION`

## Authentication & Identity

**Auth Provider:**
- Custom Laravel authentication with Sanctum for API tokens
- Session-based for web, token-based for API
- Guards: `web`, `admin`, `portal`, `api`

**Role/Permission:**
- Spatie Laravel Permission package
- Filament Shield integration
- Multi-tenant company scoping

**Customer Portal:**
- Invitation-based registration
- Sanctum tokens with configurable lifetime
- Rate limiting per customer

## Monitoring & Observability

**Error Tracking:**
- Laravel Telescope (local debugging)
- Custom `ErrorMonitoringService`

**Logs:**
- Monolog stack driver
- Daily rotation available
- Slack channel for alerts (configurable)

**Health Checks:**
- `app/Http/Controllers/Api/HealthCheckController.php`
- Endpoints: `/api/health`, `/api/health/detailed`, `/api/health/metrics`
- Cal.com health: `/api/health/calcom`

**Tracing:**
- `app/Services/Tracing/DistributedTracingService.php`
- `app/Services/Tracing/RequestCorrelationService.php`
- `app/Services/Tracing/AuditLogService.php`

## CI/CD & Deployment

**Hosting:**
- Self-hosted (based on configuration)
- Laravel Sail for local development

**CI Pipeline:**
- PHPUnit/Pest for unit/feature tests
- Playwright for E2E tests
- GitHub Actions (inferred from configs)

## Webhooks & Callbacks

**Incoming Webhooks:**

| Webhook | Route | Middleware | Purpose |
|---------|-------|------------|---------|
| Retell Legacy | `POST /api/webhook` | `retell.signature` | Call events |
| Retell Modern | `POST /api/webhooks/retell` | `retell.signature` | Call completed |
| Retell Function | `POST /api/webhooks/retell/function` | `throttle:100,1` | Real-time function calls |
| Cal.com | `POST /api/calcom/webhook` | `calcom.signature` | Booking events |
| Stripe | `POST /api/webhooks/stripe` | `stripe.webhook` | Payment events |

**Function Call Endpoints (Retell AI):**
- `POST /api/retell/initialize-call` - Call initialization
- `POST /api/retell/check-availability` - Slot availability
- `POST /api/retell/collect-appointment` - Appointment data collection
- `POST /api/retell/book-appointment` - Booking creation
- `POST /api/retell/get-customer-appointments` - Customer appointments
- `POST /api/retell/cancel-appointment` - Cancellation
- `POST /api/retell/reschedule-appointment` - Rescheduling

**Outgoing Webhooks:**
- Service Gateway output handlers (`app/Services/ServiceGateway/OutputHandlers/`)
- Callback webhooks (`app/Services/Webhooks/CallbackWebhookService.php`)

## Environment Configuration

**Required env vars:**
```
# Application
APP_KEY, APP_URL, APP_ENV

# Database
DB_CONNECTION, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Cal.com (Critical)
CALCOM_API_KEY, CALCOM_WEBHOOK_SECRET, CALCOM_TEAM_SLUG

# Retell AI (Critical)
RETELLAI_API_KEY, RETELLAI_WEBHOOK_SECRET

# Stripe (Billing)
STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET

# Twilio (Optional)
TWILIO_SID, TWILIO_AUTH_TOKEN, TWILIO_FROM_NUMBER

# Redis (Production)
REDIS_HOST, REDIS_PORT, REDIS_PASSWORD
```

**Secrets location:**
- `.env` file (not committed)
- Environment variables in production
- Test secrets in `phpunit.xml`

## Rate Limiting

**API Endpoints:**
- General: `throttle:60,1` (60 requests/minute)
- Function calls: `throttle:100,1` (100 requests/minute)
- Booking operations: `api.rate-limit:30,60` (30/minute)
- Sensitive operations: `throttle:10,1` (10 requests/minute)

**Feature-specific:**
- Slot locking TTL: 5 minutes (configurable)
- Slot intelligence cache: 15 minutes
- Customer portal rate limit: 60 requests/minute

## Circuit Breakers & Resilience

**Cal.com:**
- Circuit breaker threshold: 5 failures
- Circuit breaker timeout: 60 seconds
- Connection timeout: 5 seconds
- Request timeout: 10 seconds (reduced from 30s)

**Slot Locking (Redis):**
- Lock TTL: 300 seconds (5 minutes)
- Auto-cleanup via Redis TTL
- Fallback: DB logging for metrics

---

*Integration audit: 2026-01-19*
