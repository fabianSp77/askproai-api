# Processing Time Feature Flags - Deployment Guide

**Date**: 2025-10-28
**Status**: ✅ **COMPLETE** - Ready for Production Rollout
**Version**: 1.0

---

## 📋 Overview

The Processing Time / Split Appointments feature uses a comprehensive feature flag system for controlled, gradual rollout to production. This guide covers configuration, rollout strategy, and monitoring.

---

## 🎯 Feature Flag Architecture

### Configuration Location
- **File**: `config/features.php`
- **Environment**: `.env` variables
- **Scope**: Service-level + Company-level + Global toggle

### Feature Flags Available

```php
// Master Toggle
FEATURE_PROCESSING_TIME_ENABLED=false

// Whitelist Controls
FEATURE_PROCESSING_TIME_SERVICE_WHITELIST=""
FEATURE_PROCESSING_TIME_COMPANY_WHITELIST=""

// UI & Behavior
FEATURE_PROCESSING_TIME_SHOW_UI=true
FEATURE_PROCESSING_TIME_CALCOM_SYNC=true
FEATURE_PROCESSING_TIME_AUTO_PHASES=true
```

---

## 🔐 Feature Flag Logic

### Rollout Control Flow

```
┌─────────────────────────────────────────┐
│ Service has has_processing_time = true? │
└──────────────┬──────────────────────────┘
               │ YES
               ▼
┌─────────────────────────────────────────┐
│ Master Toggle (processing_time_enabled) │
└──────────┬───────────────┬──────────────┘
           │               │
       FALSE│           TRUE│
           │               │
           ▼               ▼
    ┌──────────┐    ┌───────────────┐
    │ Service  │    │   Company      │
    │Whitelist?│    │  Whitelist?    │
    └────┬─────┘    └───┬─────┬─────┘
         │              │     │
    YES  │         EMPTY│  YES│
         │              │     │
         └──────┬───────┴─────┘
                │
                ▼
         ┌─────────────┐
         │   ENABLED   │
         └─────────────┘
```

### Implementation

**Service Model** (`app/Models/Service.php:397-421`):
```php
public function hasProcessingTime(): bool
{
    // 1. Service must have processing time configured
    if (!$this->has_processing_time) {
        return false;
    }

    // 2. Check master toggle
    if (!config('features.processing_time_enabled', false)) {
        // Master OFF → check service whitelist
        $serviceWhitelist = config('features.processing_time_service_whitelist', []);
        return in_array($this->id, $serviceWhitelist, true);
    }

    // 3. Master ON → check company whitelist
    $companyWhitelist = config('features.processing_time_company_whitelist', []);

    // Empty whitelist = available to all
    if (empty($companyWhitelist)) {
        return true;
    }

    // Check if service's company is whitelisted
    return in_array($this->company_id, $companyWhitelist, true);
}
```

---

## 🚀 Rollout Strategy

### Phase 1: Internal Testing (Week 1)
**Goal**: Validate feature in production with controlled test services

**Configuration**:
```bash
# .env
FEATURE_PROCESSING_TIME_ENABLED=false
FEATURE_PROCESSING_TIME_SERVICE_WHITELIST="uuid-test-1,uuid-test-2"
FEATURE_PROCESSING_TIME_COMPANY_WHITELIST=""
FEATURE_PROCESSING_TIME_SHOW_UI=true
FEATURE_PROCESSING_TIME_CALCOM_SYNC=true
FEATURE_PROCESSING_TIME_AUTO_PHASES=true
```

**What This Does**:
- ✅ Master toggle OFF → secure by default
- ✅ Only whitelisted services have Processing Time
- ✅ All companies can use whitelisted services (for internal testing across test companies)
- ✅ UI visible, Cal.com sync enabled, phases auto-created

**Testing Checklist**:
- [ ] Create appointment with whitelisted service → phases created
- [ ] Create appointment with non-whitelisted service → no phases
- [ ] Check admin panel → phases visible in appointment detail
- [ ] Check calendar → phases shown in Day View
- [ ] Reschedule appointment → phases updated correctly
- [ ] Change service → phases recreated/deleted as appropriate
- [ ] Cal.com sync → appointment synced as single event
- [ ] Cache behavior → :pt_1 vs :pt_0 cache keys isolated

---

### Phase 2: Pilot Customers (Week 2-3)
**Goal**: Gradual rollout to 3-5 pilot companies for real-world validation

**Configuration**:
```bash
# .env
FEATURE_PROCESSING_TIME_ENABLED=true
FEATURE_PROCESSING_TIME_SERVICE_WHITELIST=""
FEATURE_PROCESSING_TIME_COMPANY_WHITELIST="1,5,12"
FEATURE_PROCESSING_TIME_SHOW_UI=true
FEATURE_PROCESSING_TIME_CALCOM_SYNC=true
FEATURE_PROCESSING_TIME_AUTO_PHASES=true
```

**What This Does**:
- ✅ Master toggle ON → feature active
- ✅ Only 3 pilot companies (IDs: 1, 5, 12) have access
- ✅ All services with has_processing_time=true in these companies work
- ✅ Other companies don't see the feature

**Pilot Selection Criteria**:
1. Internal/friendly customers
2. Low appointment volume (easier monitoring)
3. Hairdresser use case (dye processing time)
4. Active feedback participation

**Monitoring During Pilot**:
```bash
# Check phase creation logs
tail -f storage/logs/laravel.log | grep AppointmentPhaseObserver

# Check availability cache behavior
tail -f storage/logs/laravel.log | grep WeeklyAvailability

# Check Cal.com sync
tail -f storage/logs/laravel.log | grep "SyncAppointment\|CalcomService"
```

**Success Metrics**:
- ✅ Zero errors in phase creation
- ✅ Correct cache isolation (no collision)
- ✅ Cal.com sync working (single events)
- ✅ Staff can book overlapping appointments during processing phases
- ✅ Customer feedback positive

---

### Phase 3: General Availability (Week 4+)
**Goal**: Full rollout to all companies

**Configuration**:
```bash
# .env
FEATURE_PROCESSING_TIME_ENABLED=true
FEATURE_PROCESSING_TIME_SERVICE_WHITELIST=""
FEATURE_PROCESSING_TIME_COMPANY_WHITELIST=""
FEATURE_PROCESSING_TIME_SHOW_UI=true
FEATURE_PROCESSING_TIME_CALCOM_SYNC=true
FEATURE_PROCESSING_TIME_AUTO_PHASES=true
```

**What This Does**:
- ✅ Master toggle ON
- ✅ Empty whitelists → available to ALL companies
- ✅ Any service with has_processing_time=true works

**Pre-Rollout Checklist**:
- [ ] Phase 2 pilot successful (no critical bugs)
- [ ] Customer feedback incorporated
- [ ] Performance metrics acceptable
- [ ] Documentation complete
- [ ] Support team trained

---

## 🎛️ Feature Flag Controls

### 1. Master Toggle (`processing_time_enabled`)
**Controls**: Global feature availability

| Value | Behavior |
|-------|----------|
| `false` | Only service whitelist works (testing mode) |
| `true` | Feature enabled according to company whitelist |

**Use Cases**:
- Emergency disable: Set to `false` → only whitelisted services work
- Testing: Keep `false` during development
- Rollout: Set to `true` when ready for pilot/production

---

### 2. Service Whitelist (`processing_time_service_whitelist`)
**Controls**: Individual services that can use Processing Time

**Format**: Comma-separated service UUIDs
```bash
FEATURE_PROCESSING_TIME_SERVICE_WHITELIST="9d4f1234-5678-90ab-cdef-1234567890ab,9d4f9876-5432-10ab-cdef-0987654321ba"
```

**Use Cases**:
- **Phase 1 Testing**: Whitelist 2-3 test services
- **Emergency Disable Specific Service**: Remove UUID from list
- **Gradual Service Rollout**: Add UUIDs one by one

**How to Get Service UUIDs**:
```bash
# Via Tinker
php artisan tinker
>>> App\Models\Service::where('has_processing_time', true)->pluck('name', 'id')->toArray();

# Via MySQL
mysql -u root -p askpro_gateway
SELECT id, name FROM services WHERE has_processing_time = 1;
```

---

### 3. Company Whitelist (`processing_time_company_whitelist`)
**Controls**: Which companies can use Processing Time

**Format**: Comma-separated company IDs
```bash
FEATURE_PROCESSING_TIME_COMPANY_WHITELIST="1,5,12"
```

**Use Cases**:
- **Pilot Rollout**: 3-5 pilot companies
- **Geographic Rollout**: Enable region by region
- **Emergency Disable Company**: Remove ID from list

**How to Get Company IDs**:
```bash
# Via Tinker
php artisan tinker
>>> App\Models\Company::pluck('name', 'id')->toArray();

# Via MySQL
mysql -u root -p askpro_gateway
SELECT id, name FROM companies;
```

---

### 4. UI Display (`processing_time_show_ui`)
**Controls**: Frontend visibility of phase breakdowns

| Value | Behavior |
|-------|----------|
| `true` | Show phase visualizations in admin panel |
| `false` | Hide UI elements (backend still creates phases) |

**Use Cases**:
- **A/B Testing**: Test UI impact on user experience
- **Soft Launch**: Enable backend without UI visibility
- **Debug Mode**: Disable UI while troubleshooting

**Affects**:
- ✅ `app/Filament/Resources/AppointmentResource.php:1337` - Appointment Detail View
- ✅ `resources/views/.../appointment-calendar.blade.php:150` - Calendar Day View
- ✅ `resources/views/.../appointment-calendar.blade.php:219` - Calendar Legend

---

### 5. Cal.com Sync (`processing_time_calcom_sync_enabled`)
**Controls**: Whether to sync Processing Time appointments to Cal.com

| Value | Behavior |
|-------|----------|
| `true` | Sync appointments to Cal.com as single events |
| `false` | Skip Cal.com sync (testing/development mode) |

**Use Cases**:
- **Development**: Disable to avoid polluting Cal.com test calendars
- **Debugging**: Isolate sync issues
- **Cal.com Maintenance**: Temporarily disable during Cal.com issues

**Implementation**: Currently not wired up in `SyncAppointmentToCalcomJob` (TODO: Add check in job)

---

### 6. Auto Phase Creation (`processing_time_auto_create_phases`)
**Controls**: Observer-based automatic phase generation

| Value | Behavior |
|-------|----------|
| `true` | AppointmentPhaseObserver auto-creates phases |
| `false` | Manual phase management only |

**Use Cases**:
- **Testing**: Create custom phase configurations manually
- **Migration**: Disable during data migrations
- **Debugging**: Troubleshoot phase creation logic

**Affects**:
- ✅ `app/Observers/AppointmentPhaseObserver.php:33` - `created()` method
- ✅ `app/Observers/AppointmentPhaseObserver.php:66` - `updated()` method

---

## 📊 Monitoring & Alerts

### Log Patterns to Monitor

**Phase Creation Success**:
```bash
grep "AppointmentPhaseObserver: Phases created" storage/logs/laravel.log
```

**Phase Creation Failures**:
```bash
grep "AppointmentPhaseObserver: Failed" storage/logs/laravel.log
```

**Cache Behavior**:
```bash
grep "week_availability.*:pt_" storage/logs/laravel.log
```

**Cal.com Sync**:
```bash
grep "SyncAppointmentToCalcomJob" storage/logs/laravel.log
```

### Key Metrics

| Metric | Target | Alert Threshold |
|--------|--------|-----------------|
| Phase creation success rate | >99% | <95% |
| Cache hit rate | >80% | <60% |
| Cal.com sync success | >99% | <95% |
| Average phase creation time | <50ms | >200ms |

---

## 🔧 Troubleshooting

### Problem: Phases Not Created

**Symptoms**:
- Appointment exists but no phases in database
- Calendar doesn't show phase breakdown

**Diagnosis**:
```bash
# Check feature flags
php artisan tinker
>>> config('features.processing_time_enabled');
>>> config('features.processing_time_auto_create_phases');

# Check service configuration
>>> $service = App\Models\Service::find('uuid');
>>> $service->hasProcessingTime();
>>> $service->has_processing_time;
>>> $service->initial_duration;
>>> $service->processing_duration;
>>> $service->final_duration;

# Check logs
tail -f storage/logs/laravel.log | grep "AppointmentPhase"
```

**Solutions**:
1. ✅ Enable `processing_time_auto_create_phases` if disabled
2. ✅ Check service whitelist if master toggle is OFF
3. ✅ Check company whitelist if master toggle is ON
4. ✅ Verify service has valid phase durations configured

---

### Problem: Cache Collision

**Symptoms**:
- Regular service shows Processing Time slots
- Processing Time service shows regular slots

**Diagnosis**:
```bash
# Check cache keys
php artisan tinker
>>> $keys = Cache::getRedis()->keys('*week_availability*');
>>> foreach ($keys as $key) { echo $key . "\n"; }

# Should see different keys:
# week_availability:1:uuid1:2025-10-28:pt_0  (regular service)
# week_availability:1:uuid2:2025-10-28:pt_1  (processing time service)
```

**Solutions**:
1. ✅ Verify `app/Services/Appointments/WeeklyAvailabilityService.php:91` has `:pt_{0|1}` suffix
2. ✅ Clear cache: `php artisan cache:clear`
3. ✅ Restart queue workers: `php artisan queue:restart`

---

### Problem: UI Not Showing

**Symptoms**:
- Phases exist in database
- UI doesn't show phase breakdown

**Diagnosis**:
```bash
# Check UI flag
php artisan tinker
>>> config('features.processing_time_show_ui');

# Check if phases exist
>>> $appt = App\Models\Appointment::find(123);
>>> $appt->phases;
```

**Solutions**:
1. ✅ Enable `processing_time_show_ui` if disabled
2. ✅ Clear view cache: `php artisan view:clear`
3. ✅ Check browser console for JS errors

---

## 🎯 Quick Reference

### Enable Feature for Testing (Internal Only)
```bash
# .env
FEATURE_PROCESSING_TIME_ENABLED=false
FEATURE_PROCESSING_TIME_SERVICE_WHITELIST="uuid1,uuid2"
```

### Enable Feature for Pilot (3 Companies)
```bash
# .env
FEATURE_PROCESSING_TIME_ENABLED=true
FEATURE_PROCESSING_TIME_COMPANY_WHITELIST="1,5,12"
```

### Enable Feature Globally (Full Rollout)
```bash
# .env
FEATURE_PROCESSING_TIME_ENABLED=true
FEATURE_PROCESSING_TIME_SERVICE_WHITELIST=""
FEATURE_PROCESSING_TIME_COMPANY_WHITELIST=""
```

### Emergency Disable Everything
```bash
# .env
FEATURE_PROCESSING_TIME_ENABLED=false
FEATURE_PROCESSING_TIME_SERVICE_WHITELIST=""
```

---

## ✅ Deployment Checklist

### Pre-Deployment
- [ ] Feature flags added to `config/features.php`
- [ ] Service model updated with feature flag logic
- [ ] Observer updated with auto-create flag check
- [ ] UI components updated with show_ui flag check
- [ ] Documentation complete
- [ ] Tests passing

### Phase 1 Deployment (Testing)
- [ ] Deploy code to production
- [ ] Set `FEATURE_PROCESSING_TIME_ENABLED=false`
- [ ] Add 2-3 test service UUIDs to whitelist
- [ ] Verify test services work correctly
- [ ] Monitor logs for 24-48 hours

### Phase 2 Deployment (Pilot)
- [ ] Phase 1 successful
- [ ] Set `FEATURE_PROCESSING_TIME_ENABLED=true`
- [ ] Clear service whitelist
- [ ] Add 3-5 pilot company IDs to whitelist
- [ ] Contact pilot customers for feedback
- [ ] Monitor metrics for 1-2 weeks

### Phase 3 Deployment (General Availability)
- [ ] Phase 2 successful
- [ ] Clear company whitelist
- [ ] Announce feature to all customers
- [ ] Monitor for increased load
- [ ] Collect feedback

---

## 📞 Support

**Questions?**
- Documentation: `claudedocs/02_BACKEND/Processing_Time/`
- Code: `app/Models/Service.php`, `app/Services/AppointmentPhaseCreationService.php`
- Logs: `storage/logs/laravel.log`

**Emergency Disable**:
```bash
# Set in .env and restart queue workers
FEATURE_PROCESSING_TIME_ENABLED=false
php artisan queue:restart
php artisan cache:clear
```

---

**Last Updated**: 2025-10-28
**Version**: 1.0
**Status**: Production Ready ✅
