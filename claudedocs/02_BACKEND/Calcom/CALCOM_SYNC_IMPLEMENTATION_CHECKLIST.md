# Cal.com Sync Verification - Implementation Checklist
**Quick Start Guide for Developers**

## Pre-Implementation Checklist

- [ ] Review complete architecture document
- [ ] Confirm database backup completed
- [ ] Verify staging environment ready
- [ ] Check Cal.com API credentials valid
- [ ] Confirm queue workers running
- [ ] Review notification email templates

## Phase 1: Database & Core Service

### Day 1: Migration

- [ ] **Create Migration**
  - [x] File created: `2025_10_11_000000_add_calcom_sync_tracking_to_appointments.php`
  - [ ] Test migration on local environment
  - [ ] Run: `php artisan migrate --pretend` (dry run)
  - [ ] Run: `php artisan migrate` (actual migration)
  - [ ] Verify columns added: `php artisan db:show appointments`

- [ ] **Verify Database Schema**
  ```bash
  php artisan tinker
  >>> Schema::hasColumn('appointments', 'calcom_sync_status')
  >>> Schema::hasColumn('appointments', 'requires_manual_review')
  >>> Schema::getIndexes('appointments')
  ```

### Day 1-2: CalcomService Extension

- [ ] **Add getBooking() Method**
  - [ ] Open: `/var/www/api-gateway/app/Services/CalcomService.php`
  - [ ] Add method from architecture doc (line ~730)
  - [ ] Test API call manually:
    ```bash
    php artisan tinker
    >>> $service = app(App\Services\CalcomService::class);
    >>> $response = $service->getBooking('test-booking-id');
    >>> $response->status()
    ```

- [ ] **Add Error Handling**
  - [ ] CircuitBreaker integration
  - [ ] 404 handling (booking not found)
  - [ ] Logging to calcom channel

### Day 2-3: CalcomSyncVerificationService

- [ ] **Create Service File**
  - [ ] Create: `/var/www/api-gateway/app/Services/CalcomSyncVerificationService.php`
  - [ ] Copy complete code from architecture doc
  - [ ] Register in service provider if needed

- [ ] **Implement Core Methods**
  - [ ] `verifyAppointment()` - main verification logic
  - [ ] `fetchCalcomBooking()` - API call wrapper
  - [ ] `verifyDataConsistency()` - compare local vs Cal.com
  - [ ] `handleOrphanedLocal()` - flag missing Cal.com booking
  - [ ] `handleDataInconsistency()` - flag mismatches
  - [ ] `handleVerificationError()` - error handling

- [ ] **Add Helper Methods**
  - [ ] `mapCalcomStatus()` - status mapping
  - [ ] `notifyAdmins()` - notification trigger
  - [ ] `retrySync()` - manual retry
  - [ ] `getSyncStats()` - dashboard stats

- [ ] **Test Service**
  ```bash
  php artisan test --filter CalcomSyncVerificationServiceTest
  ```

## Phase 2: Jobs & Notifications

### Day 3-4: VerifyCalcomSyncJob

- [ ] **Create Job File**
  - [ ] Create: `/var/www/api-gateway/app/Jobs/VerifyCalcomSyncJob.php`
  - [ ] Copy code from architecture doc
  - [ ] Configure queue: `QUEUE_CONNECTION=database`

- [ ] **Job Configuration**
  - [ ] Set tries: 3
  - [ ] Set timeout: 300 seconds
  - [ ] Set backoff: [60, 300, 900]
  - [ ] Add failed() handler

- [ ] **Test Job Dispatch**
  ```bash
  php artisan tinker
  >>> App\Jobs\VerifyCalcomSyncJob::dispatch(appointmentId: 1);
  >>> php artisan queue:work --once
  ```

- [ ] **Test Batch Processing**
  ```bash
  >>> App\Jobs\VerifyCalcomSyncJob::dispatch(verifyAll: true);
  >>> php artisan queue:work
  ```

### Day 4: CalcomSyncFailureNotification

- [ ] **Create Notification File**
  - [ ] Create: `/var/www/api-gateway/app/Notifications/CalcomSyncFailureNotification.php`
  - [ ] Copy code from architecture doc

- [ ] **Configure Notification Channels**
  - [ ] Database channel (always)
  - [ ] Mail channel (if email configured)
  - [ ] Slack/Discord (optional)

- [ ] **Design Email Template**
  - [ ] Subject line
  - [ ] Body content
  - [ ] Action button
  - [ ] Test email sending

- [ ] **Test Notification**
  ```bash
  php artisan tinker
  >>> $admin = User::find(1);
  >>> $appointment = Appointment::find(1);
  >>> $admin->notify(new CalcomSyncFailureNotification($appointment, 'orphaned_local'));
  ```

## Phase 3: UI & Automation

### Day 5-6: Dashboard Widget

- [ ] **Create Widget File**
  - [ ] Create: `/var/www/api-gateway/app/Filament/Widgets/CalcomSyncStatusWidget.php`
  - [ ] Copy code from architecture doc

- [ ] **Configure Widget**
  - [ ] Set sort order: 2
  - [ ] Set polling interval: 60s
  - [ ] Add tenant filtering

- [ ] **Add Stats Cards**
  - [ ] Synced appointments (green)
  - [ ] Pending verification (yellow)
  - [ ] Requires review (red)
  - [ ] Sync failures (red)

- [ ] **Test Widget Display**
  - [ ] Navigate to admin dashboard
  - [ ] Verify widget appears
  - [ ] Check stats accuracy
  - [ ] Test click-through links

### Day 6-7: Manual Retry Actions

- [ ] **Add to Appointment Resource**
  - [ ] Open: `/var/www/api-gateway/app/Filament/Resources/AppointmentResource.php`
  - [ ] Add table action: `retry_sync`
  - [ ] Add visibility condition
  - [ ] Add confirmation modal

- [ ] **Test Action**
  - [ ] Navigate to appointments list
  - [ ] Find failed appointment
  - [ ] Click "Retry Sync"
  - [ ] Verify action executes
  - [ ] Check notification appears

- [ ] **Add Bulk Action** (optional)
  - [ ] Retry multiple failed appointments
  - [ ] Progress indicator
  - [ ] Result summary

### Day 7: Scheduled Jobs

- [ ] **Configure Scheduler**
  - [ ] Open: `/var/www/api-gateway/app/Console/Kernel.php`
  - [ ] Add 6-hour job
  - [ ] Add daily comprehensive check
  - [ ] Test locally: `php artisan schedule:work`

- [ ] **Production Cron**
  - [ ] Verify cron entry exists: `* * * * * php /path/artisan schedule:run`
  - [ ] Test cron execution
  - [ ] Monitor logs: `tail -f storage/logs/laravel.log`

## Phase 4: Testing & Deployment

### Day 8-9: Integration Testing

- [ ] **Unit Tests**
  - [ ] Create: `/var/www/api-gateway/tests/Unit/Services/CalcomSyncVerificationServiceTest.php`
  - [ ] Test orphaned detection
  - [ ] Test data consistency checks
  - [ ] Test error handling
  - [ ] Run: `php artisan test --filter CalcomSyncVerificationServiceTest`

- [ ] **Feature Tests**
  - [ ] Create: `/var/www/api-gateway/tests/Feature/CalcomSyncVerificationTest.php`
  - [ ] Test job execution
  - [ ] Test notification delivery
  - [ ] Test manual retry
  - [ ] Run: `php artisan test --filter CalcomSyncVerificationTest`

- [ ] **End-to-End Testing**
  - [ ] Create orphaned appointment manually
  - [ ] Wait for verification job
  - [ ] Verify notification received
  - [ ] Test manual retry
  - [ ] Verify resolution

### Day 9-10: Documentation & Deployment

- [ ] **Update Documentation**
  - [x] Architecture document complete
  - [x] Visual summary complete
  - [x] Implementation checklist complete
  - [ ] Add to project README
  - [ ] Create admin user guide

- [ ] **Staging Deployment**
  - [ ] Run migration on staging
  - [ ] Deploy code changes
  - [ ] Test all features
  - [ ] Monitor for 24 hours

- [ ] **Production Deployment**
  - [ ] Schedule maintenance window
  - [ ] Backup database
  - [ ] Run migration: `php artisan migrate --force`
  - [ ] Deploy code
  - [ ] Verify queue workers running
  - [ ] Test notification delivery
  - [ ] Monitor dashboard widget

## Post-Deployment Checklist

### Immediate (Day 1)

- [ ] **Verify System Health**
  - [ ] Check queue jobs executing: `php artisan queue:monitor`
  - [ ] Verify scheduled jobs running: `php artisan schedule:list`
  - [ ] Check logs for errors: `tail -f storage/logs/laravel.log`
  - [ ] Monitor Cal.com API calls

- [ ] **Test Core Functions**
  - [ ] Create new appointment → verify auto-sync
  - [ ] Trigger manual retry → verify execution
  - [ ] Check dashboard widget → verify stats
  - [ ] Test notification delivery

### Week 1 Monitoring

- [ ] **Daily Checks**
  - [ ] Review sync stats dashboard
  - [ ] Check manual review queue
  - [ ] Monitor notification delivery
  - [ ] Review error logs

- [ ] **Performance Metrics**
  - [ ] Sync success rate: Target >95%
  - [ ] Average verification time: Target <5s
  - [ ] Manual review queue: Target <20
  - [ ] Notification delivery: Target <5min

### Week 2-4 Optimization

- [ ] **Tune Job Frequency**
  - [ ] Adjust 6-hour cycle if needed
  - [ ] Optimize batch size
  - [ ] Fine-tune retry delays

- [ ] **User Feedback**
  - [ ] Collect admin feedback
  - [ ] Identify pain points
  - [ ] Plan improvements

## Rollback Procedure

If critical issues occur:

1. **Disable Scheduled Jobs**
   ```bash
   # Comment out in app/Console/Kernel.php
   # $schedule->job(new VerifyCalcomSyncJob(verifyAll: true))
   ```

2. **Stop Queue Processing**
   ```bash
   php artisan queue:clear
   supervisorctl stop laravel-worker:*
   ```

3. **Rollback Migration**
   ```bash
   php artisan migrate:rollback --step=1
   ```

4. **Revert Code**
   ```bash
   git revert <commit-hash>
   git push origin main
   ```

5. **Notify Stakeholders**
   - Document issue
   - Communicate rollback
   - Plan resolution

## Quick Command Reference

```bash
# Migration
php artisan migrate
php artisan migrate:rollback
php artisan migrate:status

# Testing
php artisan test --filter CalcomSync
php artisan tinker

# Queue Management
php artisan queue:work
php artisan queue:listen
php artisan queue:restart
php artisan queue:clear

# Scheduler
php artisan schedule:list
php artisan schedule:run
php artisan schedule:work

# Manual Verification
php artisan tinker
>>> $service = app(App\Services\CalcomSyncVerificationService::class);
>>> $appointment = App\Models\Appointment::find(1);
>>> $service->verifyAppointment($appointment);

# Stats Check
>>> $stats = $service->getSyncStats(15); // company_id
>>> dd($stats);
```

## Troubleshooting Guide

### Issue: Migration Fails

**Symptoms**: Error during `php artisan migrate`

**Solutions**:
1. Check appointments table exists: `php artisan db:show appointments`
2. Verify no column conflicts: `php artisan tinker >>> Schema::hasColumn('appointments', 'calcom_sync_status')`
3. Check MySQL version supports ENUM type
4. Review migration logs: `storage/logs/laravel.log`

### Issue: Job Not Executing

**Symptoms**: Pending appointments not verified

**Solutions**:
1. Check queue workers running: `supervisorctl status`
2. Verify job in database: `SELECT * FROM jobs;`
3. Check failed_jobs table: `SELECT * FROM failed_jobs;`
4. Restart queue: `php artisan queue:restart`

### Issue: Notifications Not Delivered

**Symptoms**: Admins not receiving alerts

**Solutions**:
1. Verify admin users exist with correct roles
2. Check mail configuration: `php artisan tinker >>> Mail::raw('test', fn($msg) => $msg->to('admin@test.com'));`
3. Review notification logs
4. Check database notifications table

### Issue: Cal.com API Errors

**Symptoms**: All verifications failing

**Solutions**:
1. Check Cal.com API credentials: `config('services.calcom.api_key')`
2. Verify circuit breaker status: `$service->getCircuitBreakerStatus()`
3. Test API manually: `curl -H "Authorization: Bearer API_KEY" https://api.cal.com/v2/bookings/{id}`
4. Check rate limits

## Success Metrics

### Technical Metrics

- ✅ Migration executed without errors
- ✅ All tests passing (100% coverage on new code)
- ✅ Jobs executing on schedule
- ✅ Notifications delivered within 5 minutes
- ✅ Dashboard widget loading <2s

### Business Metrics

- ✅ Sync success rate >95%
- ✅ Manual review queue <20 appointments
- ✅ Issue resolution time <24 hours
- ✅ Zero data loss during sync
- ✅ Admin satisfaction score >4/5

---

**Implementation Checklist Version**: 1.0
**Last Updated**: 2025-10-11
**Estimated Completion**: 10 working days
