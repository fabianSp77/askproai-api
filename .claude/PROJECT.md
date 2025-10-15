# AskPro AI Gateway - Project Context

**Stack**: Laravel 11 | Filament 3 | PHP 8.2 | PostgreSQL | Redis
**Domain**: Appointment Management | AI Voice Agent | Multi-Tenant SaaS

---

## Project-Specific Patterns

### Laravel/Filament Standards
```php
// Filament Resource Pattern
app/Filament/Resources/{Model}Resource.php
  ├─ Pages/List{Model}.php
  ├─ Pages/Create{Model}.php
  ├─ Pages/Edit{Model}.php
  └─ Pages/View{Model}.php

// Service Layer Pattern
app/Services/{Domain}/{Service}Service.php
  → Business logic, external integrations

// Job Pattern
app/Jobs/{Domain}/{Action}Job.php
  → Async operations, queue workers

// Event/Listener Pattern
app/Events/{Domain}/{Event}.php
app/Listeners/{Domain}/{Listener}.php
  → Event-driven architecture
```

### Multi-Tenancy
- **Scope**: Company-based isolation
- **Context**: `TenantMiddleware` sets `CompanyScope`
- **Models**: All extend `CompanyScopedModel`
- **Security**: RLS (Row Level Security) via `companyscope`

### Key Integrations

#### Cal.com
```
Purpose: Scheduling & availability
Architecture: Team-based (each staff → team member)
Sync: Bidirectional (appointments ⇄ bookings)
Cache: Redis-based availability cache
Docs: claudedocs/02_BACKEND/Calcom/
```

#### Retell.ai
```
Purpose: Voice AI agent for appointment booking
Latest: V83 prompt (architecture fix)
Latency: 80% reduction achieved
Function Calls: collect_appointment_info, check_availability
Docs: claudedocs/03_API/Retell_AI/
```

---

## Domain Abbreviations

### Core Entities
```
appt    → appointment
avail   → availability
cal     → calcom
cfg     → configuration
cust    → customer
pol     → policy
svc     → service
```

### Operations
```
sync    → synchronization
val     → validation
notif   → notification
reschedule → resched
```

### Technical
```
RCA     → Root Cause Analysis
RLS     → Row Level Security
MCP     → Model Context Protocol
E2E     → End-to-End
UX      → User Experience
```

---

## Architecture Patterns

### Appointment Lifecycle
```
1. Retell AI Voice Call
   ↓
2. collect_appointment_info()
   ↓
3. check_availability() → Cal.com API
   ↓
4. Create Appointment (Laravel)
   ↓
5. SyncToCalcomJob (Queue)
   ↓
6. Bidirectional sync established
   ↓
7. Cache invalidation (Redis)
```

### Cache Strategy
```
Key Pattern: company:{id}:{resource}:{identifier}
TTL: 5min (availability), 1hr (configuration)
Invalidation: Event-driven (listeners)
Storage: Redis
```

### Testing Strategy
```
Unit: PHPUnit (Services, Models)
E2E: Puppeteer (Filament UI flows)
Manual: Test guides in claudedocs/04_TESTING/
Security: Tenant isolation tests
```

---

## Common Tasks → Documentation

### Frontend Work
```
Appointment UI    → claudedocs/01_FRONTEND/Appointments_UI/
Week Picker       → claudedocs/01_FRONTEND/Week_Picker/
Filament Admin    → claudedocs/01_FRONTEND/Filament/
UX Research       → claudedocs/01_FRONTEND/UX_Research/
```

### Backend Work
```
Cal.com Sync      → claudedocs/02_BACKEND/Calcom/
Services          → claudedocs/02_BACKEND/Services/
Database          → claudedocs/02_BACKEND/Database/
Laravel Core      → claudedocs/02_BACKEND/Laravel/
```

### API Work
```
Retell AI         → claudedocs/03_API/Retell_AI/
Webhooks          → claudedocs/03_API/Webhooks/
Controllers       → claudedocs/03_API/Controllers/
```

### Bug Fixing
```
500 Errors        → claudedocs/06_SECURITY/Fixes/
Frontend Bugs     → claudedocs/01_FRONTEND/Components/
API Issues        → claudedocs/03_API/Controllers/
RCA               → claudedocs/08_REFERENCE/RCA/
```

---

## Critical Files Reference

### Configuration
```
config/companyscope.php       → Multi-tenant config
config/calcom.php             → Cal.com settings
config/services.php           → External APIs (Retell)
```

### Key Models
```
app/Models/Appointment.php    → Core appointment model
app/Models/Staff.php          → Multi-branch staff
app/Models/PolicyConfiguration.php → Policy system
app/Models/CalcomHostMapping.php → Cal.com team mapping
```

### Key Services
```
app/Services/AppointmentAlternativeFinder.php → Smart rescheduling
app/Services/Retell/AppointmentCreationService.php → AI booking
app/Services/Appointments/WeeklyAvailabilityService.php → Availability
```

### Key Controllers
```
app/Http/Controllers/Api/RetellApiController.php → Retell webhook
app/Http/Controllers/CalcomWebhookController.php → Cal.com webhook
app/Http/Controllers/RetellFunctionCallHandler.php → Function calls
```

---

## Development Workflow

### Before Starting
```bash
1. Check documentation: claudedocs/00_INDEX.md
2. Review recent fixes: claudedocs/06_SECURITY/Fixes/
3. Check architecture: claudedocs/07_ARCHITECTURE/
4. Read relevant RCA: claudedocs/08_REFERENCE/RCA/
```

### During Development
```bash
1. Follow Laravel/Filament patterns
2. Maintain multi-tenant isolation
3. Update cache invalidation
4. Add tests (Unit + E2E)
5. Document in appropriate claudedocs/ category
```

### After Development
```bash
1. Run tests: vendor/bin/pest
2. Check logs: tail -f storage/logs/laravel.log
3. Update documentation
4. Create RCA if bug fix
5. Add to appropriate index
```

---

## Token Optimization Strategies

### When Reading Docs
```
1. Start with 00_INDEX.md → find category
2. Use category INDEX.md → find specific file
3. Use grep for keywords if uncertain
4. Prefer *_FINAL_*.md or *_COMPLETE_*.md
```

### When Searching Code
```
1. Use specific patterns:
   - Filament: app/Filament/Resources/*
   - Services: app/Services/*
   - Models: app/Models/*

2. Check recent changes:
   - git status
   - git log --oneline -10

3. Search by domain:
   - Appointments: grep -r "Appointment" app/
   - Cal.com: grep -r "Calcom" app/
```

---

## Known Issues & Workarounds

### Cal.com Sync
- **Issue**: Cache collision on availability
- **Fix**: Event-driven invalidation
- **Docs**: `02_BACKEND/Calcom/CALCOM_CACHE_RCA_2025-10-11.md`

### Retell Latency
- **Issue**: 3-5s response time
- **Fix**: V83 prompt + function optimization
- **Result**: 80% reduction
- **Docs**: `03_API/Retell_AI/COLLECT_APPOINTMENT_LATENCY_OPTIMIZATION_2025-10-13.md`

### Week Picker Reactive
- **Issue**: Desktop rendering bugs
- **Fix**: Comprehensive reactive fix
- **Docs**: `01_FRONTEND/Week_Picker/WOCHENKALENDER_FINAL_FIX_2025-10-14.md`

---

## Quick Commands

```bash
# Start services
php artisan serve
npm run dev

# Queue worker
php artisan queue:work

# Tests
vendor/bin/pest

# Cache
php artisan cache:clear
php artisan config:clear

# Logs
tail -f storage/logs/laravel.log

# Database
php artisan migrate
php artisan db:seed
```

---

**Documentation Index**: `claudedocs/00_INDEX.md`
**Last Updated**: 2025-10-14
**Project Version**: v2.0 (Post-reorganization)
