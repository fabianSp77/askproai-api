# Fix Execution Guide: Booking Flow Errors

**Date**: 2025-10-15
**Status**: 🔴 READY TO EXECUTE
**Total Time**: 30 minutes
**Risk Level**: Medium (requires code changes + database updates)

---

## Quick Start

```bash
cd /var/www/api-gateway

# Phase 1: Fix Cal.com Event Type ID (5 min)
./fix-phase1-calcom-event-type.sh

# Phase 2: Fix Hidden Fields Architecture (15 min)
./fix-phase2-hidden-fields.sh

# Phase 3: Integration Testing (10 min)
./fix-phase3-integration-test.sh
```

---

## Prerequisites

- [x] SSH access to production server
- [x] Access to Cal.com dashboard (https://app.cal.com)
- [x] Root cause analysis read (`ROOT_CAUSE_ANALYSIS_2025-10-15.md`)
- [x] Database backup (automatic in Phase 1)
- [x] Code backup (automatic in Phase 2)

---

## Phase 1: Cal.com Event Type ID Fix

### What it does
- Checks current service Cal.com configuration
- Lists all Cal.com event types for company
- Prompts for correct event type ID from Cal.com
- Updates database
- Tests Cal.com API connectivity

### Execution
```bash
./fix-phase1-calcom-event-type.sh
```

### Manual Steps Required
1. Script will pause and ask for Cal.com Event Type ID
2. Login to https://app.cal.com
3. Navigate to team "AskProAI" (Team ID: 39203)
4. Go to Event Types
5. Find "15 Minuten Schnellberatung" (or create if missing)
6. Copy the event type ID from URL: `https://app.cal.com/event-types/{EVENT_TYPE_ID}`
7. Paste ID into script prompt

### Success Criteria
```
✅ Cal.com API Test: SUCCESS
HTTP Status: 200
Total Slots Found: [number > 0]
```

### Troubleshooting
- **404 Still Returned**: Event type ID is still incorrect, verify in Cal.com dashboard
- **Connection Error**: Check Cal.com API key in `.env` (`CALCOM_API_KEY`)
- **Empty Slots**: Normal if no availability configured in Cal.com for next 7 days

---

## Phase 2: Hidden Fields Architecture Fix

### What it does
- Backs up `app/Filament/Resources/AppointmentResource.php`
- Moves hidden fields (service_id, staff_id, branch_id, customer_id) to top-level schema
- Removes old hidden field declarations from hidden sections
- Clears all caches (view, application, Filament)
- Verifies form schema structure

### Execution
```bash
./fix-phase2-hidden-fields.sh
```

### Manual Steps Required
- **Press Enter** to confirm code changes
- Review backup file location (displayed in output)

### Success Criteria
```
✅ All required hidden fields found at top level
✅ Phase 2 Complete: Hidden Fields Fixed
```

### Rollback (if needed)
```bash
# Backup location shown in script output
cp app/Filament/Resources/AppointmentResource.php.backup-TIMESTAMP app/Filament/Resources/AppointmentResource.php
php artisan view:clear
php artisan cache:clear
```

### Troubleshooting
- **Verification Failed**: Check backup file, manually verify hidden fields placement
- **Syntax Error**: Restore backup, contact support
- **Cache Issues**: Run `php artisan optimize:clear` to clear all caches

---

## Phase 3: Integration Testing

### What it does
- Tests Cal.com API connectivity (GET /slots/available)
- Verifies form schema structure (hidden fields at top level)
- Tests WeeklyAvailabilityService integration
- Provides browser testing checklist

### Execution
```bash
./fix-phase3-integration-test.sh
```

### Success Criteria
```
✅ Test 1: Cal.com API Connectivity
✅ Test 2: Form Schema Structure
✅ Test 3: WeeklyAvailabilityService Integration
✅ ALL TESTS PASSED
```

### Browser Testing Checklist
After all automated tests pass:

1. Navigate to: `https://YOUR_DOMAIN/admin/appointments/create`
2. Open browser DevTools (F12) → Console tab
3. Select a branch
4. Select a customer
5. Select service "15 Minuten Schnellberatung"
6. **Check console**:
   - ✅ `[BookingFlowWrapper] service_id updated: 46`
   - ❌ NO `[BookingFlowWrapper] service_id field not found`
7. **Calendar should load** with available time slots
   - ❌ NO `Cal.com API-Fehler: GET /slots/available (HTTP 404)`
8. Select a time slot
9. Click "Create" button
10. **Appointment created successfully**

---

## What Changed

### Database (Phase 1)
```sql
-- Service #46 (15 Minuten Schnellberatung)
UPDATE services
SET calcom_event_type_id = 'VERIFIED_CORRECT_ID'
WHERE id = 46;
```

### Code (Phase 2)
**File**: `app/Filament/Resources/AppointmentResource.php`

**Before** (Lines 349-369):
```php
Section::make('💇 Was wird gemacht?')
    ->visible(fn ($context) => $context !== 'create')
    ->schema([
        // ... other fields ...
        Forms\Components\Hidden::make('service_id'),  // ❌ Inside hidden section
        Forms\Components\Hidden::make('staff_id'),
        Forms\Components\Hidden::make('branch_id'),
        Forms\Components\Hidden::make('customer_id'),
    ])
```

**After**:
```php
return $form->schema([
    // ✅ GLOBAL HIDDEN FIELDS (Top-level, always in DOM)
    Forms\Components\Hidden::make('service_id')
        ->default(null)
        ->reactive()
        ->afterStateUpdated(function ($state, callable $set) {
            if ($state) {
                $service = Service::find($state);
                if ($service) {
                    $set('duration_minutes', $service->duration_minutes ?? 30);
                    $set('price', $service->price);
                }
            }
        }),
    Forms\Components\Hidden::make('staff_id')->default(null),
    Forms\Components\Hidden::make('branch_id')->default(null),
    Forms\Components\Hidden::make('customer_id')->default(null),

    // ... sections follow ...
    Section::make('💇 Was wird gemacht?')
        ->visible(fn ($context) => $context !== 'create')
        ->schema([
            // Only visible EDIT fields here
        ]),
]);
```

**Key Change**: Hidden fields moved from **inside hidden section** to **top-level schema**

---

## Verification Commands

### Check Service Configuration
```bash
php artisan tinker --execute="
\$service = \App\Models\Service::find(46);
echo 'Service: ' . \$service->name . '\n';
echo 'Event Type ID: ' . \$service->calcom_event_type_id . '\n';
echo 'Company Team ID: ' . \$service->company->calcom_team_id . '\n';
"
```

### Test Cal.com API
```bash
curl -X GET "https://api.cal.com/v2/slots/available?eventTypeId=YOUR_EVENT_TYPE_ID&teamId=39203&startTime=$(date -u +%Y-%m-%dT00:00:00Z)&endTime=$(date -u -d '+7 days' +%Y-%m-%dT23:59:59Z)" \
  -H "Authorization: Bearer YOUR_CALCOM_API_KEY" \
  -H "cal-api-version: 2024-08-13"
```

### Check Hidden Fields in Form
```bash
php artisan tinker --execute="
\$resource = new \App\Filament\Resources\AppointmentResource();
\$form = \$resource::form(\Filament\Forms\Form::make());
\$schema = \$form->getSchema();

foreach (\$schema as \$component) {
    if (\$component instanceof \Filament\Forms\Components\Hidden) {
        echo '✅ Hidden field: ' . \$component->getName() . '\n';
    }
}
"
```

---

## Rollback Plan

### If Phase 1 Fails
```bash
# Restore old event type ID (if known)
php artisan tinker
>>> $service = \App\Models\Service::find(46);
>>> $service->calcom_event_type_id = 'OLD_EVENT_TYPE_ID';
>>> $service->save();
>>> \Illuminate\Support\Facades\Cache::flush();
```

### If Phase 2 Fails
```bash
# Restore backup
cp app/Filament/Resources/AppointmentResource.php.backup-TIMESTAMP \
   app/Filament/Resources/AppointmentResource.php

# Clear caches
php artisan view:clear
php artisan cache:clear
php artisan config:clear
```

### If Phase 3 Fails
- No rollback needed (read-only testing)
- Review logs: `tail -100 storage/logs/laravel.log`
- Re-run individual phase scripts

---

## Common Errors & Solutions

### Error: "service_id field not found" (Console)
**Cause**: Phase 2 not executed or failed
**Solution**: Run `./fix-phase2-hidden-fields.sh`

### Error: "Cal.com API-Fehler: GET /slots/available (HTTP 404)"
**Cause**: Phase 1 not executed or event type ID still incorrect
**Solution**: Run `./fix-phase1-calcom-event-type.sh` and verify event type ID in Cal.com

### Error: "Form not found" (Console)
**Cause**: BookingFlowWrapper Alpine.js context issue
**Solution**: Check if BookingFlowWrapper is inside `<form>` tag in rendered HTML

### Error: "Circuit breaker open"
**Cause**: Cal.com API repeatedly failing (5+ consecutive failures)
**Solution**:
```bash
php artisan tinker
>>> $calcom = app(\App\Services\CalcomService::class);
>>> $calcom->resetCircuitBreaker();
>>> echo "Circuit breaker reset\n";
```

### Error: "Event type does not exist in team"
**Cause**: Event type belongs to different Cal.com team
**Solution**: Create new event type in correct team (AskProAI, ID 39203)

---

## Post-Deployment Checklist

- [ ] Phase 1 executed successfully (Cal.com API returns 200)
- [ ] Phase 2 executed successfully (Hidden fields at top level)
- [ ] Phase 3 all tests passed (3/3 green)
- [ ] Browser console: No "field not found" errors
- [ ] Calendar loads with available slots
- [ ] Time slot selection works
- [ ] Appointment creation succeeds
- [ ] Database: Appointment record created with correct service_id, staff_id
- [ ] Cal.com: Booking created and synced
- [ ] User receives confirmation (if notifications enabled)

---

## Support Information

### Log Locations
```bash
# Application logs
tail -f storage/logs/laravel.log

# Cal.com integration logs
tail -f storage/logs/calcom.log

# Queue worker logs (if using queues)
tail -f storage/logs/worker.log
```

### Debug Commands
```bash
# Clear all caches
php artisan optimize:clear

# Check queue status
php artisan queue:work --once

# Check circuit breaker status
php artisan tinker
>>> $calcom = app(\App\Services\CalcomService::class);
>>> dd($calcom->getCircuitBreakerStatus());
```

### Contact
- Root Cause Analysis: `ROOT_CAUSE_ANALYSIS_2025-10-15.md`
- Technical Documentation: `claudedocs/01_FRONTEND/Appointments_UI/`
- Cal.com Docs: `claudedocs/02_BACKEND/Calcom/`

---

## Timeline Estimate

| Phase | Duration | Type | Risk |
|-------|----------|------|------|
| Phase 1: Cal.com Fix | 5 min | Manual + Automated | Low |
| Phase 2: Code Fix | 15 min | Automated | Medium |
| Phase 3: Testing | 10 min | Automated | None |
| Browser Testing | 5 min | Manual | None |
| **Total** | **35 min** | Mixed | **Medium** |

---

## Success Definition

**Complete Success** = All conditions met:
1. ✅ Cal.com API returns 200 with slots
2. ✅ Console shows "service_id updated" (no "not found")
3. ✅ Calendar displays available time slots
4. ✅ Appointment creation succeeds
5. ✅ Database record created correctly
6. ✅ Cal.com booking synced

**Partial Success** = Some conditions met:
- If only Phase 1 passes: Availability works, but form submission fails
- If only Phase 2 passes: Form submission works, but no slots shown

**Failure** = Neither phase passes:
- Console errors persist
- Cal.com API returns 404
- Appointment creation fails

---

**Status**: 🟢 READY TO EXECUTE
**Next Step**: Run `./fix-phase1-calcom-event-type.sh`
