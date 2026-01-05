# Changelog

All notable changes to AskPro API Gateway are documented here.

## [2.1.0] - 2026-01-04

### Added
- **Service Gateway Complete Implementation**
  - 2-Phase Delivery Pattern (Enrichment → Output)
  - 8 Webhook Presets (Jira, ServiceNow, OTRS, Zendesk, Freshdesk, Slack, Teams, Generic)
  - Exponential Backoff for webhook retries (60s → 120s → 300s)
  - Admin Alerts for delivery failures (Email + Slack)
  - 9-Widget Dashboard with real-time SLA tracking
  - 7-Step Company Onboarding Wizard
  - Retell Agent Auto-Provisioning

- **SLA Tracking**
  - Category-based SLA configuration
  - Response time tracking
  - Resolution time tracking
  - SLA breach alerts and escalation

- **Exchange Logging**
  - Complete audit trail for all webhook exchanges
  - Request/response payload storage
  - Performance metrics (response time, status codes)
  - Export functionality for debugging

### Fixed
- Critical multi-tenancy customer data isolation issue
- ENUM direction field in exchange logs
- Duplicate content in email Problembeschreibung section

### Security
- Fixed cross-company customer assignment vulnerability
- Added tenant isolation verification tests

---

## [2.0.0] - 2025-12-22

### Added
- **Service Gateway Initial Release**
  - Multi-tenant case management
  - Service case categories with priorities
  - Email output handler with customizable templates
  - Webhook output handler with template engine
  - Hybrid output mode (Email + Webhook)

- **Audio Recording Support**
  - Secure audio streaming for call recordings
  - Audio file attachment in backup emails
  - Configurable audio retention policies

### Changed
- Upgraded to Laravel 11
- Upgraded to Filament 3.2
- Improved Cal.com synchronization performance

---

## [1.9.0] - 2025-11-25

### Added
- **Conversation Flow Designer**
  - Visual node-based flow editor
  - Custom function definitions
  - Flow deployment to Retell.ai
  - Version control for flows

- **Retell Conversation Flow Integration**
  - Company-level flow assignment
  - Branch-level flow overrides
  - Auto-sync on deployment

### Fixed
- Date hallucination issue in Retell function calls
- Timezone handling in appointment booking

---

## [1.8.0] - 2025-11-14

### Added
- **Policy Configuration System**
  - Operational policy types
  - Cancellation policies
  - Rescheduling policies
  - Buffer time configuration

- **Customer Email Collection**
  - Callback request email capture
  - Optional email in appointment flow

### Changed
- Extended notification queue with operational types

---

## [1.7.0] - 2025-10-28

### Added
- **Branch-Based Appointments**
  - Branch assignment for appointments
  - Branch-specific working hours
  - Multi-branch staff support

- **User-Branch Association**
  - Staff linked to branches
  - Branch managers
  - Cross-branch visibility controls

---

## [1.6.0] - 2025-10-26

### Added
- **Customer Portal Performance**
  - Optimized database indexes
  - Caching layer for frequent queries
  - Lazy loading for large datasets

### Fixed
- Phone number synchronization issues
- Retell call session branch tracking

---

## [1.5.0] - 2025-10-23

### Added
- **Service Priority System**
  - Priority levels for services
  - Priority-based queue ordering
  - Staff priority assignments

- **Retell Monitoring**
  - Call quality metrics
  - Agent performance tracking
  - Real-time dashboard widgets

### Fixed
- Schema inconsistencies (Priority 1 fixes)
- Monitoring table creation errors

---

## [1.4.0] - 2025-10-17

### Added
- **Role-Based Access Control**
  - Spatie Permission integration
  - Custom permission tables
  - Role hierarchy support

- **Notification Delivery Tracking**
  - Delivery status monitoring
  - Retry logic for failed notifications
  - Delivery receipts

---

## [1.3.0] - 2025-10-13

### Added
- **Appointment Sync Orchestration**
  - Bidirectional Cal.com sync
  - Conflict resolution
  - Sync status tracking

### Performance
- 80% latency reduction in Retell function calls
- Optimized availability queries
- Database index improvements

---

## [1.2.0] - 2025-10-06

### Added
- **Service Staff Assignments**
  - Staff-to-service mapping
  - Service-specific availability
  - Assignment scheduling

- **Company Isolation Constraints**
  - Database-level tenant isolation
  - Foreign key constraints
  - Automatic company_id injection

### Fixed
- Cal.com V1/V2 booking ID separation
- Customer link status inconsistencies

---

## [1.1.0] - 2025-09-30

### Added
- **Phone Number Normalization**
  - International format support
  - Duplicate detection
  - Display format preferences

- **Default Service Flag**
  - Company default service
  - Fallback service selection

---

## [1.0.0] - 2025-09-25

### Added
- **Initial Release**
  - Multi-tenant architecture with CompanyScope
  - Filament admin panel
  - Cal.com integration for scheduling
  - Retell.ai integration for voice AI
  - Appointment management
  - Customer management
  - Staff management
  - Branch management
  - Basic notification system
  - Redis caching
  - Queue workers

---

## Migration Notes

### Upgrading to 2.x

1. Run database migrations:
```bash
php artisan migrate
```

2. Seed new categories:
```bash
php artisan db:seed --class=ThomasIncidentCategoriesSeeder
```

3. Configure webhook presets:
```bash
php artisan db:seed --class=WebhookPresetSeeder
```

4. Clear caches:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Breaking Changes in 2.0

- `ServiceCase` model now requires `category_id`
- Email templates moved to new blade structure
- Webhook authentication method changed to HMAC

---

## Versioning

This project uses [Semantic Versioning](https://semver.org/):

- **MAJOR**: Incompatible API changes
- **MINOR**: Backward-compatible functionality
- **PATCH**: Backward-compatible bug fixes
