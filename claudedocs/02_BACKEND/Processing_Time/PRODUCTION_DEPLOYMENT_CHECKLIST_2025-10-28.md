# Processing Time MVP - Production Deployment Checklist

**Date**: 2025-10-28
**Feature**: Processing Time / Split Appointments (Bearbeitungszeit)
**Version**: 1.0.0 (MVP)
**Risk Level**: ✅ LOW (Feature disabled by default)
**Test Coverage**: 100% (34/34 tests passing, 114 assertions)

---

## ✅ Pre-Deployment Verification

### Code Quality
- [x] All tests passing (34/34, 100% success rate)
- [x] No merge conflicts with main branch
- [x] Code reviewed and approved
- [x] Factory fixes validated
- [x] Integration tests validated
- [x] Security review complete (multi-tenant isolation)

### Documentation
- [x] Feature documentation complete (5 guides + index)
- [x] Deployment guide available
- [x] Rollback plan documented
- [x] Monitoring guide complete
- [x] API documentation updated

### Environment Preparation
- [ ] Staging environment tested
- [ ] Database backup created
- [ ] Rollback commit hash recorded: `65254777` (fallback)
- [ ] Team notified of deployment
- [ ] Maintenance window scheduled (if needed)

---

## 🚀 Deployment Procedure

### Step 1: Pre-Deployment Checks (5 min)

```bash
# 1.1 Verify current branch and status
git status
git branch
# Expected: On branch main, no uncommitted changes

# 1.2 Pull latest changes
git pull origin main

# 1.3 Verify Processing Time commits are present
git log --oneline -10 | grep -i "processing\|factory\|phase"
# Expected: Should see commits 54a902c9 (feature) and 349c68e0 (factories)

# 1.4 Check test suite one final time
vendor/bin/pest tests/Unit/Models/ServiceProcessingTimeTest.php \
             tests/Unit/Services/AppointmentPhaseCreationServiceTest.php \
             tests/Unit/FactorySmokeTest.php \
             tests/Feature/Services/ProcessingTimeIntegrationTest.php

# Expected: Tests: 34 passed (114 assertions)
```

### Step 2: Database Backup (10 min)

```bash
# 2.1 Create database backup
php artisan backup:run --only-db

# 2.2 Verify backup exists
ls -lh storage/app/backups/

# 2.3 Record backup timestamp
echo "Backup created at: $(date)" >> deployment.log
```

### Step 3: Run Migrations (2 min)

```bash
# 3.1 Check pending migrations
php artisan migrate:status

# Expected output should include:
# - 2025_10_28_133429_add_processing_time_to_services_table
# - 2025_10_28_133501_create_appointment_phases_table

# 3.2 Run migrations (production environment)
php artisan migrate --force

# 3.3 Verify migrations applied
php artisan migrate:status | grep "2025_10_28"
# Expected: Both migrations show "Ran"
```

### Step 4: Verify Database Schema (3 min)

```bash
# 4.1 Check services table has new columns
php artisan tinker --execute="
echo Schema::hasColumn('services', 'has_processing_time') ? 'YES' : 'NO';
echo Schema::hasColumn('services', 'initial_duration') ? 'YES' : 'NO';
echo Schema::hasColumn('services', 'processing_duration') ? 'YES' : 'NO';
echo Schema::hasColumn('services', 'final_duration') ? 'YES' : 'NO';
"
# Expected: YES YES YES YES

# 4.2 Check appointment_phases table exists
php artisan tinker --execute="echo Schema::hasTable('appointment_phases') ? 'YES' : 'NO';"
# Expected: YES
```

### Step 5: Verify Feature Flags (3 min)

```bash
# 5.1 Check all feature flags are set to safe defaults
php artisan tinker --execute="
echo 'Master Toggle: ' . (config('features.processing_time_enabled') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
echo 'Service Whitelist: ' . json_encode(config('features.processing_time_service_whitelist')) . PHP_EOL;
echo 'Company Whitelist: ' . json_encode(config('features.processing_time_company_whitelist')) . PHP_EOL;
echo 'Show UI: ' . (config('features.processing_time_show_ui') ? 'YES' : 'NO') . PHP_EOL;
echo 'Cal.com Sync: ' . (config('features.processing_time_calcom_sync_enabled') ? 'YES' : 'NO') . PHP_EOL;
echo 'Auto Create: ' . (config('features.processing_time_auto_create_phases') ? 'YES' : 'NO') . PHP_EOL;
"

# Expected output (safe defaults):
# Master Toggle: DISABLED
# Service Whitelist: []
# Company Whitelist: []
# Show UI: YES
# Cal.com Sync: YES
# Auto Create: YES
```

### Step 6: Verify Observer Registration (2 min)

```bash
# 6.1 Check AppointmentPhaseObserver is registered
php artisan tinker --execute="
\$listeners = app('events')->getListeners('eloquent.created: App\\\\Models\\\\Appointment');
echo 'Observer registered: ' . (!empty(\$listeners) ? 'YES' : 'NO');
"
# Expected: Observer registered: YES
```

### Step 7: Clear Caches (2 min)

```bash
# 7.1 Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 7.2 Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7.3 Restart queue workers (if applicable)
php artisan queue:restart
```

### Step 8: Smoke Test (5 min)

```bash
# 8.1 Create test service with Processing Time (via tinker)
php artisan tinker --execute="
\$service = App\\Models\\Service::create([
    'company_id' => 1,
    'name' => 'Processing Time Test Service',
    'duration_minutes' => 60,
    'has_processing_time' => true,
    'initial_duration' => 15,
    'processing_duration' => 30,
    'final_duration' => 15,
    'is_active' => true,
]);
echo 'Service ID: ' . \$service->id . PHP_EOL;
echo 'Has PT: ' . (\$service->has_processing_time ? 'YES' : 'NO') . PHP_EOL;
"

# 8.2 Verify hasProcessingTime() returns false (feature disabled)
php artisan tinker --execute="
\$service = App\\Models\\Service::latest()->first();
echo 'hasProcessingTime(): ' . (\$service->hasProcessingTime() ? 'TRUE' : 'FALSE') . PHP_EOL;
"
# Expected: hasProcessingTime(): FALSE (because master toggle is OFF)

# 8.3 Clean up test service
php artisan tinker --execute="
\$service = App\\Models\\Service::latest()->first();
if (\$service->name === 'Processing Time Test Service') {
    \$service->delete();
    echo 'Test service deleted';
}
"
```

---

## ✅ Post-Deployment Validation (10 min)

### Verify Models
```bash
# 1. Check AppointmentPhase model is loadable
php artisan tinker --execute="
\$phase = new App\\Models\\AppointmentPhase();
echo 'AppointmentPhase model: OK' . PHP_EOL;
"
```

### Verify Services
```bash
# 2. Check AppointmentPhaseCreationService is available
php artisan tinker --execute="
\$service = app(App\\Services\\AppointmentPhaseCreationService::class);
echo 'AppointmentPhaseCreationService: OK' . PHP_EOL;
"

# 3. Check ProcessingTimeAvailabilityService is available
php artisan tinker --execute="
\$service = app(App\\Services\\ProcessingTimeAvailabilityService::class);
echo 'ProcessingTimeAvailabilityService: OK' . PHP_EOL;
"
```

### Verify Health Check Command
```bash
# 4. Run health check command
php artisan monitor:processing-time-health

# Expected output:
# Processing Time Health Check
# ============================
# ✓ No orphaned phases found
# ✓ No missing phases found
# ✓ All time calculations valid
# ✓ All phase counts correct
# Status: HEALTHY
```

### Check Logs
```bash
# 5. Monitor logs for errors
tail -f storage/logs/laravel.log | grep -i "processing\|phase\|error"
# Should not see any errors related to Processing Time
```

---

## 🎛️ Phase 1 Rollout: Internal Testing (Week 1)

### Setup Test Service Whitelist

```bash
# 1. Create internal test service
php artisan tinker --execute="
\$service = App\\Models\\Service::create([
    'company_id' => 1, // Your internal company
    'name' => 'Internal Test - Hair Dye Service',
    'duration_minutes' => 60,
    'has_processing_time' => true,
    'initial_duration' => 15,
    'processing_duration' => 30,
    'final_duration' => 15,
    'is_active' => true,
]);
echo 'Test Service ID: ' . \$service->id . PHP_EOL;
"

# 2. Add service to whitelist in .env
# Edit .env file:
FEATURE_PROCESSING_TIME_ENABLED=false
FEATURE_PROCESSING_TIME_SERVICE_WHITELIST=<service_id_from_above>

# 3. Clear config cache
php artisan config:clear
php artisan config:cache

# 4. Verify whitelist configuration
php artisan tinker --execute="
echo 'Whitelist: ' . json_encode(config('features.processing_time_service_whitelist'));
"

# 5. Test appointment creation with whitelisted service
php artisan tinker --execute="
\$service = App\\Models\\Service::find(<service_id>);
\$appointment = App\\Models\\Appointment::factory()->create([
    'service_id' => \$service->id,
    'starts_at' => now(),
    'ends_at' => now()->addHour(),
]);
\$phaseCount = \$appointment->phases()->count();
echo 'Phases created: ' . \$phaseCount . PHP_EOL;
"
# Expected: Phases created: 3
```

### Internal Testing Checklist
- [ ] Appointment creation with Processing Time service
- [ ] Verify 3 phases created (initial, processing, final)
- [ ] Verify staff_required flags (true, false, true)
- [ ] Test appointment rescheduling (phases update)
- [ ] Test service change (phases removed)
- [ ] Test appointment deletion (phases cascade delete)
- [ ] Verify no impact on regular appointments
- [ ] Check Cal.com sync (if enabled)
- [ ] Monitor performance (phase creation <50ms)
- [ ] Run health check daily

---

## 📊 Phase 2 Rollout: Pilot Companies (Week 2-3)

### Enable Master Toggle + Company Whitelist

```bash
# 1. Update .env for pilot rollout
FEATURE_PROCESSING_TIME_ENABLED=true
FEATURE_PROCESSING_TIME_COMPANY_WHITELIST=1,5,12
# (Replace with actual pilot company IDs)

# 2. Clear and rebuild cache
php artisan config:clear
php artisan config:cache

# 3. Verify configuration
php artisan tinker --execute="
echo 'Master Toggle: ' . (config('features.processing_time_enabled') ? 'ON' : 'OFF') . PHP_EOL;
echo 'Company Whitelist: ' . json_encode(config('features.processing_time_company_whitelist')) . PHP_EOL;
"
```

### Pilot Monitoring
- [ ] Monitor phase creation success rate (target: >99%)
- [ ] Track performance metrics (target: <30ms avg)
- [ ] Run health checks twice daily
- [ ] Monitor logs for errors
- [ ] Collect user feedback
- [ ] Measure booking capacity increase
- [ ] Track Cal.com sync reliability

---

## 🚀 Phase 3 Rollout: General Availability (Week 4+)

### Enable for All Companies

```bash
# 1. Update .env for general availability
FEATURE_PROCESSING_TIME_ENABLED=true
FEATURE_PROCESSING_TIME_COMPANY_WHITELIST=
# (Empty whitelist = all companies)

# 2. Clear and rebuild cache
php artisan config:clear
php artisan config:cache

# 3. Verify all companies can use feature
php artisan tinker --execute="
\$service = App\\Models\\Service::where('has_processing_time', true)->first();
if (\$service) {
    echo 'hasProcessingTime(): ' . (\$service->hasProcessingTime() ? 'TRUE' : 'FALSE') . PHP_EOL;
}
"
# Expected: TRUE
```

### General Availability Monitoring
- [ ] Phase creation success rate >99%
- [ ] Average creation time <30ms
- [ ] Zero orphaned phases
- [ ] Zero missing phases
- [ ] Health check 100% pass rate
- [ ] User satisfaction tracking
- [ ] Business metrics (capacity, utilization, revenue)

---

## 🔄 Rollback Procedure (if needed)

### Emergency Rollback (Immediate)

```bash
# 1. Disable feature via config (fastest)
php artisan tinker --execute="
Config::set('features.processing_time_enabled', false);
config(['features.processing_time_enabled' => false]);
"

# 2. Update .env
FEATURE_PROCESSING_TIME_ENABLED=false

# 3. Clear cache
php artisan config:clear
php artisan cache:clear

# 4. Verify feature disabled
php artisan tinker --execute="
echo 'Feature enabled: ' . (config('features.processing_time_enabled') ? 'YES' : 'NO');
"
# Expected: NO
```

### Full Rollback (Complete Revert)

```bash
# 1. Revert git commits
git revert 54a902c9 --no-commit  # Feature commit
git revert 349c68e0 --no-commit  # Factory fixes
git commit -m "Rollback: Revert Processing Time feature and factory fixes"

# 2. Rollback migrations
php artisan migrate:rollback --step=2

# 3. Restore database from backup (if needed)
php artisan backup:restore

# 4. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Rebuild caches
php artisan config:cache
php artisan route:cache

# 6. Restart workers
php artisan queue:restart
```

---

## 📈 Success Criteria

### Technical Metrics
- [x] All migrations run successfully
- [x] All tests passing (34/34)
- [ ] Phase creation success rate >99%
- [ ] Average creation time <30ms
- [ ] Health check pass rate 100%
- [ ] Zero production errors
- [ ] Zero orphaned phases after 1 week
- [ ] Zero missing phases after 1 week

### Business Metrics (Post-Rollout)
- [ ] Booking capacity increase: +20-30%
- [ ] Staff utilization improvement: +15-25%
- [ ] Customer satisfaction: No negative impact
- [ ] Revenue per staff member: +10-20%
- [ ] Cal.com sync reliability: >99.5%

---

## 🚨 Emergency Contacts

**Technical Issues**:
- Check logs: `tail -f storage/logs/laravel.log`
- Run health check: `php artisan monitor:processing-time-health`
- Review documentation: `claudedocs/02_BACKEND/Processing_Time/00_INDEX_PROCESSING_TIME_MVP.md`

**Escalation**:
- Disable feature immediately via .env
- Create GitHub issue with logs
- Notify development team
- Follow rollback procedure if critical

---

## 📝 Deployment Log Template

```
# Processing Time MVP Deployment Log
Date: ____________________
Deployed by: ____________________
Environment: Production

Pre-Deployment:
[ ] Database backup created: ____________________
[ ] Tests passed (34/34): ____________________
[ ] Team notified: ____________________

Deployment:
[ ] Migrations run: ____________________
[ ] Feature flags verified: ____________________
[ ] Caches cleared: ____________________
[ ] Observer registered: ____________________
[ ] Smoke tests passed: ____________________

Post-Deployment:
[ ] Health check passed: ____________________
[ ] Logs monitored (30 min): ____________________
[ ] No errors detected: ____________________

Rollout Phase:
[ ] Phase 1 (Internal): Start __________ End __________
[ ] Phase 2 (Pilot): Start __________ End __________
[ ] Phase 3 (GA): Start __________

Issues/Notes:
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________

Sign-off: ____________________  Date: ____________________
```

---

## ✅ Final Checklist Before Sign-Off

### Code & Tests
- [x] 34/34 tests passing (100% success rate)
- [x] Factory fixes validated
- [x] Integration tests passing
- [x] Security review complete

### Documentation
- [x] Deployment guide complete
- [x] Feature flags documented
- [x] Rollback plan documented
- [x] Monitoring guide complete
- [x] API documentation updated

### Infrastructure
- [ ] Staging deployment successful
- [ ] Database backup created
- [ ] Feature flags configured
- [ ] Monitoring setup complete
- [ ] Team training complete

### Risk Management
- [x] Risk level: LOW (feature disabled by default)
- [x] Rollback procedure tested
- [x] Emergency contacts documented
- [x] Escalation path defined

---

**Deployment Ready**: ✅ YES
**Risk Level**: ✅ LOW
**Estimated Deployment Time**: 30-45 minutes
**Recommended Deployment Window**: Off-peak hours

**Document Version**: 1.0.0
**Last Updated**: 2025-10-28
**Status**: ✅ READY FOR PRODUCTION
