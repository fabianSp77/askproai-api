# Deployment Checklist - Cal.com Sync Button Fix

## Pre-Deployment

### Code Verification
- [x] **Syntax Check:** `php -l app/Filament/Resources/ServiceResource/Pages/ViewService.php`
  - Result: ✅ No syntax errors detected

- [x] **TODO Comments Removed:** `grep "TODO" app/Filament/Resources/ServiceResource/Pages/ViewService.php`
  - Result: ✅ No matches

- [x] **Automated Verification:** `php verify_sync_button_fix.php`
  - Result: ✅ VERIFICATION PASSED (8 success, 1 warning)

- [x] **Git Status:** File ready for commit
  - Modified: `app/Filament/Resources/ServiceResource/Pages/ViewService.php`

### Documentation
- [x] Technical documentation created: `CALCOM_SYNC_BUTTON_FIX_VERIFICATION.md`
- [x] Manual test guide created: `MANUAL_TEST_SYNC_BUTTON.md`
- [x] Executive summary created: `SYNC_BUTTON_FIX_SUMMARY.md`
- [x] Deployment checklist created: `DEPLOYMENT_CHECKLIST_SYNC_BUTTON.md`

---

## Deployment Steps

### 1. Backup Current State
```bash
# Create backup of current file (optional, but recommended)
cp app/Filament/Resources/ServiceResource/Pages/ViewService.php \
   app/Filament/Resources/ServiceResource/Pages/ViewService.php.backup.$(date +%Y%m%d_%H%M%S)
```
- [ ] Backup created

### 2. Clear Application Caches
```bash
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan cache:clear
```
- [ ] All caches cleared

### 3. Verify Queue Configuration
```bash
# Check current queue driver
php artisan tinker
>>> config('queue.default')
=> "sync"  # or "database", "redis", etc.
```
- [ ] Queue configuration verified
- [ ] Queue worker running (if not using 'sync' driver)

### 4. Deploy File Changes
```bash
# If using Git deployment
git add app/Filament/Resources/ServiceResource/Pages/ViewService.php
git commit -m "fix: Implement proper Cal.com sync in ViewService with UpdateCalcomEventTypeJob

- Replace TODO placeholder with complete implementation
- Add confirmation modal with service details
- Handle edge cases (no Event Type ID, sync pending, dispatch failure)
- Add comprehensive user feedback notifications
- Implement proper error logging
- Use existing UpdateCalcomEventTypeJob for sync
- Follow Filament 3 patterns

Closes: #TICKET_NUMBER (if applicable)"

git push origin main  # or your deployment branch
```
- [ ] Changes committed
- [ ] Changes pushed to repository

### 5. Pull Changes on Production Server
```bash
# SSH into production server
ssh user@production-server

# Navigate to project directory
cd /var/www/api-gateway

# Pull latest changes
git pull origin main

# Clear caches again
php artisan config:clear
php artisan view:clear
php artisan route:clear
```
- [ ] Changes pulled on production
- [ ] Caches cleared on production

---

## Post-Deployment Verification

### 1. Run Automated Verification
```bash
php verify_sync_button_fix.php
```
**Expected Output:**
```
✅ VERIFICATION PASSED
Next Steps:
1. Test sync button in Filament UI: /admin/services/32
2. Process queue job: php artisan queue:work --once
3. Monitor logs: tail -f storage/logs/laravel.log
```
- [ ] Verification passed

### 2. Manual UI Test
```bash
# Navigate to:
https://your-domain.com/admin/services/32

# Actions:
1. Locate "Cal.com Sync" button in header
2. Click button
3. Verify modal appears with correct content
4. Click "Jetzt synchronisieren"
5. Verify success notification appears
```
- [ ] Button visible
- [ ] Modal displays correctly
- [ ] Notification appears

### 3. Verify Job Processing
```bash
# If queue driver is 'sync'
tail -n 50 storage/logs/laravel.log | grep -i "cal.com"

# If using async queue
php artisan queue:work --once
```
**Expected Log:**
```
[YYYY-MM-DD HH:MM:SS] local.INFO: [Cal.com Update] Successfully updated Event Type
{"service_id":32,"event_type_id":"3664712"}
```
- [ ] Job processed successfully
- [ ] Log entries show success

### 4. Verify Database Update
```bash
php artisan tinker

$service = App\Models\Service::find(32);
echo "Sync Status: {$service->sync_status}\n";
echo "Last Sync: {$service->last_calcom_sync}\n";
echo "Sync Error: {$service->sync_error}\n";
exit
```
**Expected Output:**
```
Sync Status: synced
Last Sync: 2025-10-25 HH:MM:SS
Sync Error: null
```
- [ ] `sync_status = 'synced'`
- [ ] `last_calcom_sync` updated
- [ ] `sync_error = null`

### 5. Verify Cal.com Update (Optional)
```bash
# Check Cal.com Event Type via API
curl -X GET "https://api.cal.com/v2/event-types/3664712" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "cal-api-version: 2024-08-13"
```
- [ ] Cal.com event type updated (if API access available)

---

## Edge Case Testing (Optional)

### Test Case 1: Service Without Event Type ID
1. Navigate to service without `calcom_event_type_id`
2. Verify button is **hidden**

- [ ] Button hidden for services without Event Type ID

### Test Case 2: Sync Already Pending
```sql
UPDATE services SET sync_status = 'pending' WHERE id = 32;
```
1. Click sync button
2. Verify info notification: "Synchronisation läuft bereits"
3. Reset: `UPDATE services SET sync_status = 'synced' WHERE id = 32;`

- [ ] Duplicate sync prevented
- [ ] Info notification shown

### Test Case 3: Multiple Services
Test with different services (if available):
- Service ID 33, 34, 35, etc.
- Verify each syncs correctly
- Check for race conditions

- [ ] Multiple services sync correctly

---

## Monitoring (First 24 Hours)

### 1. Monitor Application Logs
```bash
# Watch logs continuously
tail -f storage/logs/laravel.log | grep -i "cal.com"

# Check for errors
tail -f storage/logs/laravel.log | grep -i "error"
```
- [ ] Logs monitored
- [ ] No unexpected errors

### 2. Monitor Queue (if async)
```bash
# Check failed jobs
php artisan queue:failed

# Monitor queue size
php artisan queue:work --verbose
```
- [ ] No failed jobs
- [ ] Queue processing normally

### 3. Monitor Sync Success Rate
```sql
SELECT
    sync_status,
    COUNT(*) as count,
    MAX(last_calcom_sync) as latest_sync
FROM services
WHERE calcom_event_type_id IS NOT NULL
GROUP BY sync_status;
```
**Expected:** Most services have `sync_status = 'synced'`

- [ ] Sync success rate > 95%

### 4. User Feedback
- Monitor for support tickets related to sync
- Check for confusion about new modal
- Gather user feedback on improved UX

- [ ] No user complaints
- [ ] Positive feedback on clarity

---

## Rollback Plan (If Needed)

### If Critical Issue Found
```bash
# 1. Restore backup
cp app/Filament/Resources/ServiceResource/Pages/ViewService.php.backup.YYYYMMDD_HHMMSS \
   app/Filament/Resources/ServiceResource/Pages/ViewService.php

# 2. Clear caches
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 3. Verify rollback
php -l app/Filament/Resources/ServiceResource/Pages/ViewService.php

# 4. Document issue
# Create ROLLBACK_ISSUE_YYYYMMDD.md with:
# - Description of issue
# - Steps to reproduce
# - Impact on users
# - Reason for rollback
```

### Rollback Criteria
- [ ] Syntax errors preventing page load
- [ ] Job failures > 50% of attempts
- [ ] User-facing errors in production
- [ ] Data corruption in services table
- [ ] Cal.com API rate limiting issues

---

## Cleanup (After Successful Deployment)

### Remove Temporary Files (Optional)
```bash
# Remove verification script (keep if useful for future)
# rm verify_sync_button_fix.php

# Remove backup files after 7 days
find app/Filament/Resources/ServiceResource/Pages/ -name "*.backup.*" -mtime +7 -delete
```
- [ ] Cleanup performed (if desired)

### Archive Documentation
```bash
# Move to project documentation folder
mkdir -p claudedocs/06_SECURITY/Fixes/2025-10-25_CalcomSyncButton/
mv CALCOM_SYNC_BUTTON_FIX_VERIFICATION.md claudedocs/06_SECURITY/Fixes/2025-10-25_CalcomSyncButton/
mv MANUAL_TEST_SYNC_BUTTON.md claudedocs/06_SECURITY/Fixes/2025-10-25_CalcomSyncButton/
mv SYNC_BUTTON_FIX_SUMMARY.md claudedocs/06_SECURITY/Fixes/2025-10-25_CalcomSyncButton/
mv DEPLOYMENT_CHECKLIST_SYNC_BUTTON.md claudedocs/06_SECURITY/Fixes/2025-10-25_CalcomSyncButton/
mv verify_sync_button_fix.php claudedocs/06_SECURITY/Fixes/2025-10-25_CalcomSyncButton/
```
- [ ] Documentation archived (if desired)

---

## Sign-Off

### Deployment Team
- [ ] **Developer:** Implementation verified and tested
- [ ] **QA:** Manual testing completed successfully
- [ ] **DevOps:** Deployment executed without issues
- [ ] **Product:** Feature approved and documented

### Sign-Off Details
- **Deployment Date:** ___________________
- **Deployed By:** _______________________
- **Verified By:** _______________________
- **Issues Found:** _______________________ (None expected)
- **Production Status:** ✅ LIVE / ⏸️ PENDING / ❌ ROLLED BACK

---

## Notes

### Deployment Notes
```
(Add any specific notes about this deployment)
```

### Issues Encountered
```
(Document any unexpected issues)
```

### Improvements for Next Time
```
(Suggestions for improving deployment process)
```

---

**Deployment Checklist Version:** 1.0
**Created:** 2025-10-25
**Last Updated:** 2025-10-25
**Status:** ✅ Ready for Deployment
