# Rollback Procedures - Emergency Recovery

## 🎯 Purpose
Define exact rollback procedures for each day/component in case of critical failure.

---

## 📍 Current State Snapshot (Tag 2 Complete)

### Git State
```bash
# Current commit (Tag 2 complete)
git log -1 --oneline
# Expected: "Day 2: Models + Factories + Trait integration"

# Tag for safety
git tag -a day_2_complete -m "Migrations + Models + Tests validated"
git push origin day_2_complete
```

### Database State (testing.sqlite)
```bash
# Migrations applied
php artisan migrate:status | grep "2025_10_01"
# Expected: 7 migrations "Ran"

# Backup
cp database/testing.sqlite database/backups/testing_day2_$(date +%Y%m%d).sqlite
```

### Files Created (Tag 1-2)
```
database/migrations/2025_10_01_060100_create_notification_configurations_table.php
database/migrations/2025_10_01_060200_create_callback_requests_table.php
database/migrations/2025_10_01_060200_create_notification_event_mappings_table.php
database/migrations/2025_10_01_060200_create_policy_configurations_table.php
database/migrations/2025_10_01_060300_create_appointment_modifications_table.php
database/migrations/2025_10_01_060300_create_callback_escalations_table.php
database/migrations/2025_10_01_060400_create_appointment_modification_stats_table.php

app/Models/PolicyConfiguration.php
app/Models/AppointmentModification.php
app/Models/AppointmentModificationStat.php
app/Models/CallbackRequest.php
app/Models/CallbackEscalation.php
app/Models/NotificationConfiguration.php
app/Models/NotificationEventMapping.php
app/Models/Traits/HasConfigurationInheritance.php

database/factories/ (7 factories)

Modified:
app/Models/Company.php (added trait)
app/Models/Branch.php (added trait)
app/Models/Service.php (added trait)
app/Models/Staff.php (added trait)
```

---

## 🔄 Rollback Scenarios

### Scenario 1: PolicyConfigurationService Fails (Tag 3)

#### Failure Indicators
- ❌ Tests fail during implementation
- ❌ Logic errors discovered in review
- ❌ Performance unacceptable (>500ms queries)
- ❌ Cache not working as expected

#### Rollback Procedure
```bash
# 1. Stop current work immediately
echo "STOPPING Tag 3 work - Failure detected"

# 2. Verify Tag 2 state is clean
git status
# Should show only new Tag 3 files

# 3. Stash or discard Tag 3 changes
git stash push -m "Tag 3 work - rolled back due to [REASON]"
# OR if no value in keeping:
git reset --hard day_2_complete

# 4. Verify rollback to Tag 2
php artisan migrate:status | grep "2025_10_01"
# Expected: Still 7 migrations "Ran" (migrations not affected)

# 5. Verify models still work
php artisan tinker --execute="
echo 'PolicyConfiguration: ' . (class_exists('App\Models\PolicyConfiguration') ? 'OK' : 'FAIL') . PHP_EOL;
"

# 6. Clear caches
php artisan cache:clear
php artisan config:clear

# 7. Document failure
cat > docs/ROLLBACK_LOG_TAG3.md << EOF
# Rollback Log - Tag 3

**Date**: $(date +%Y-%m-%d_%H:%M:%S)
**Component**: PolicyConfigurationService
**Reason**: [DETAILED REASON]
**Impact**: Tag 3 work discarded, back to Tag 2 state
**Next Steps**: [REDESIGN PLAN]
EOF
```

#### Files to Remove (Tag 3 specific)
```bash
# PolicyConfigurationService (if created)
rm -f app/Services/Policies/PolicyConfigurationService.php

# Tests (if created)
rm -f tests/Feature/ConfigurationHierarchyTest.php
rm -f tests/Unit/PolicyConfigurationServiceTest.php

# Any other Tag 3 files
git clean -fd app/Services/Policies/
git clean -fd tests/Feature/
```

#### Verification After Rollback
```bash
# 1. Models still work
php /tmp/test_final.php
# Expected: All 5 models tested successfully

# 2. Trait still integrated
php artisan tinker --execute="
\$company = App\Models\Company::first();
echo 'Trait: ' . (method_exists(\$company, 'getEffectivePolicyConfig') ? 'OK' : 'FAIL') . PHP_EOL;
"

# 3. Cache still works
php artisan tinker --execute="
Cache::put('rollback_test', 'ok', 60);
echo 'Cache: ' . (Cache::get('rollback_test') === 'ok' ? 'OK' : 'FAIL') . PHP_EOL;
Cache::forget('rollback_test');
"
```

---

### Scenario 2: PolicyEngine Fails (Tag 4-5)

#### Failure Indicators (CRITICAL)
- ❌ Logic error in canCancel/canReschedule
- ❌ Fee calculation incorrect
- ❌ Race condition detected
- ❌ Quota checks fail
- ❌ Edge cases not handled

#### Rollback Procedure
```bash
# 1. STOP immediately (per PROJECT_CONSTRAINTS.md)
echo "CRITICAL FAILURE - PolicyEngine"

# 2. Check which tag to rollback to
git tag | grep day_
# Options: day_2_complete, day_3_complete (if passed)

# 3. Rollback to last stable
git reset --hard day_3_complete  # If Tag 3 passed
# OR
git reset --hard day_2_complete  # If Tag 3 also problematic

# 4. Remove PolicyEngine files
rm -rf app/Services/Policies/AppointmentPolicyEngine.php
rm -rf tests/Feature/PolicyEngineTest.php
rm -rf tests/Unit/AppointmentPolicyEngineTest.php

# 5. Verify services still work
php artisan tinker --execute="
\$company = App\Models\Company::first();
\$config = \$company->getEffectivePolicyConfig('cancellation');
echo 'ConfigService: ' . (is_array(\$config) || is_null(\$config) ? 'OK' : 'FAIL') . PHP_EOL;
"

# 6. Clear related caches
redis-cli FLUSHDB
php artisan cache:clear

# 7. Document critical failure
cat > docs/CRITICAL_FAILURE_TAG45.md << EOF
# CRITICAL FAILURE - PolicyEngine

**Date**: $(date +%Y-%m-%d_%H:%M:%S)
**Component**: AppointmentPolicyEngine
**Severity**: CRITICAL
**Reason**: [DETAILED TECHNICAL REASON]
**Data Impact**: None (no DB changes)
**Rollback To**: Tag 3 complete
**Recovery Plan**: [DETAILED REDESIGN]
**Lessons Learned**: [WHAT WENT WRONG]
EOF
```

#### Verification After Rollback
- ✅ PolicyConfigurationService still works (if Tag 3 passed)
- ✅ Models still work
- ✅ Migrations intact
- ✅ No corrupted data

---

### Scenario 3: Event System Fails (Tag 6-7)

#### Failure Indicators
- ❌ Memory leak detected
- ❌ Events not firing
- ❌ Listener failures break main flow
- ❌ Queue processing incorrect

#### Rollback Procedure
```bash
# 1. Stop event processing
php artisan queue:clear
supervisorctl stop laravel-worker:*

# 2. Rollback code
git reset --hard day_5_complete

# 3. Remove event files
rm -rf app/Events/Appointments/
rm -rf app/Listeners/Appointments/
rm -rf tests/Feature/EventSystemTest.php

# 4. Clear event-related data
redis-cli FLUSHDB
php artisan cache:clear

# 5. Restart clean
supervisorctl start laravel-worker:*

# 6. Verify no event references remain
grep -r "AppointmentModified" app/Observers/
# Should find nothing in observers if properly rolled back
```

---

### Scenario 4: Retell Integration Fails (Tag 8-9)

#### Failure Indicators (SECURITY CRITICAL)
- ❌ Signature validation broken
- ❌ Double-booking occurs
- ❌ Security vulnerability found
- ❌ Webhook processing fails

#### Rollback Procedure
```bash
# 1. CRITICAL - Disable webhook endpoint immediately
# Option A: Maintenance mode
php artisan down --message="Security update in progress"

# Option B: Comment out route
# In routes/api.php:
# // Route::post('/webhooks/retell', [RetellWebhookController::class, 'handle']);

# 2. Rollback code
git reset --hard day_7_complete

# 3. Remove Retell handlers
rm -rf app/Services/Retell/Handlers/
rm -rf tests/Feature/RetellIntegrationTest.php

# 4. Verify old webhook still works (if preserved)
# Test with Retell dashboard webhook test

# 5. Document security issue
cat > docs/SECURITY_INCIDENT_TAG89.md << EOF
# SECURITY INCIDENT - Retell Integration

**Date**: $(date +%Y-%m-%d_%H:%M:%S)
**Component**: Retell Webhook Handlers
**Severity**: CRITICAL SECURITY
**Issue**: [DETAILED SECURITY ISSUE]
**Mitigation**: Endpoint disabled, code rolled back
**Investigation**: [PENDING/IN PROGRESS]
EOF

# 6. Re-enable production (only after verification)
php artisan up
```

---

### Scenario 5: Production Deployment Fails (Tag 15)

#### Failure Indicators
- ❌ Migration fails on production MySQL
- ❌ Models don't load
- ❌ 500 errors in production
- ❌ Data corruption detected

#### Rollback Procedure (PRODUCTION)
```bash
# 1. Enable maintenance mode
php artisan down --message="Emergency maintenance"

# 2. Rollback migrations
php artisan migrate:rollback --step=7

# 3. Verify rollback
php artisan migrate:status | grep "2025_10_01"
# Expected: All "Pending"

# 4. Restore database backup
mysql -u root -p askproai_db < /backups/pre_deployment_*.sql

# 5. Verify database integrity
mysql -u root -p askproai_db -e "SELECT COUNT(*) FROM companies;"
# Should match pre-deployment count

# 6. Rollback code
git checkout [previous_production_tag]

# 7. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
redis-cli FLUSHALL

# 8. Restart services
supervisorctl restart all

# 9. Verify production healthy
curl -I https://api.askproai.de/health
# Expected: 200 OK

# 10. Disable maintenance mode
php artisan up

# 11. Monitor for 30 minutes
tail -f storage/logs/laravel.log | grep -i "error\|exception"

# 12. Document production incident
cat > docs/PRODUCTION_INCIDENT_$(date +%Y%m%d_%H%M%S).md << EOF
# PRODUCTION INCIDENT - Deployment Rollback

**Date**: $(date +%Y-%m-%d_%H:%M:%S)
**Component**: Production Deployment
**Severity**: CRITICAL
**Issue**: [DETAILED REASON FOR ROLLBACK]
**Downtime**: [DURATION]
**Data Loss**: [YES/NO + DETAILS]
**Recovery**: Complete - Back to pre-deployment state
**Next Steps**: [INVESTIGATION + REVISED PLAN]
EOF
```

---

## 🧪 Rollback Testing (Safe)

### Test Rollback Procedure (Don't Execute, Just Validate)
```bash
# Simulate rollback without changing files
git stash push -m "Test rollback simulation"

# Show what would be rolled back
git diff day_2_complete

# Show files that would be removed
git clean -fd --dry-run

# Restore to continue work
git stash pop
```

### Practice Rollback (On Branch)
```bash
# Create test branch
git checkout -b rollback-practice

# Simulate failure
touch app/Services/FAKE_BROKEN_FILE.php
git add .
git commit -m "Simulated failure"

# Execute rollback
git reset --hard day_2_complete

# Verify clean state
git status
# Expected: nothing to commit, working tree clean

# Return to main work
git checkout main
git branch -D rollback-practice
```

---

## 📊 Rollback Decision Matrix

| Severity | Response Time | Action | Approval |
|----------|--------------|---------|----------|
| Critical (Production down) | Immediate | Full rollback | Automatic |
| High (Security issue) | < 15min | Component rollback | Lead approval |
| Medium (Feature broken) | < 1 hour | Fix or rollback | Team decision |
| Low (Bug found) | Next day | Fix in place | Developer decision |

---

## 📝 Rollback Checklist Template

```markdown
## Rollback Execution Log

**Date**: [YYYY-MM-DD HH:MM:SS]
**Component**: [Name]
**Rolled Back From**: Tag X
**Rolled Back To**: Tag Y
**Reason**: [Detailed reason]

### Pre-Rollback State
- [ ] Current commit: [hash]
- [ ] Database state: [migrations applied]
- [ ] Cache state: [keys present]
- [ ] Backup created: [location]

### Rollback Execution
- [ ] Code rolled back: `git reset --hard [tag]`
- [ ] Files removed: [list]
- [ ] Database rolled back: [migrations]
- [ ] Cache cleared: [redis/memcached]
- [ ] Services restarted: [list]

### Post-Rollback Validation
- [ ] Models work: [✅/❌]
- [ ] Tests pass: [✅/❌]
- [ ] Cache works: [✅/❌]
- [ ] No errors in logs: [✅/❌]
- [ ] Production healthy: [✅/❌ or N/A]

### Lessons Learned
- [What went wrong]
- [What to do differently]
- [Process improvements needed]

### Next Steps
- [ ] [Action item 1]
- [ ] [Action item 2]
```

---

**Version**: 1.0
**Last Updated**: 2025-10-02
**Last Tested**: N/A (Will test on Tag 16-17 buffer days)
