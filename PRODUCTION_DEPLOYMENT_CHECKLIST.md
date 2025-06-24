# Production Deployment Checklist - AskProAI Appointment Booking

## 🚀 Pre-Deployment Checks

### 1. Code & Database
- [ ] All migrations applied successfully
  ```bash
  php artisan migrate:status | grep -E "(2025_06_24|pending)"
  ```
- [ ] No syntax errors
  ```bash
  php -l app/Services/AppointmentBookingService.php
  php -l app/Jobs/ProcessRetellCallEndedJob.php
  ```
- [ ] Clear all caches
  ```bash
  php artisan optimize:clear
  ```

### 2. Queue & Background Jobs
- [ ] Horizon is running
  ```bash
  php artisan horizon:status
  ```
- [ ] Process stuck webhooks
  ```bash
  php artisan queue:work --queue=webhooks --tries=3 --timeout=120
  ```
- [ ] Check failed jobs
  ```bash
  php artisan queue:failed
  ```

### 3. Retell.ai Configuration
- [ ] Custom function `collect_appointment_data` configured
- [ ] Webhook URL set to `https://api.askproai.de/api/retell/collect-appointment`
- [ ] Agent prompt updated with appointment collection flow
- [ ] Test function with curl:
  ```bash
  curl -X POST https://api.askproai.de/api/retell/collect-appointment/test
  ```

### 4. Cal.com Integration
- [ ] Verify event types are synced
  ```sql
  SELECT COUNT(*) FROM calcom_event_types WHERE branch_id IS NOT NULL;
  ```
- [ ] Check branch configurations
  ```sql
  SELECT id, name, calcom_event_type_id FROM branches WHERE is_active = 1;
  ```

## 🧪 Testing Protocol

### 1. Unit Test (5 min)
Run the automated test:
```bash
php test-appointment-booking-flow.php
```

Expected: All 7 tests should pass

### 2. Real Phone Call Test (10 min)
1. Call the test number: +49 30 837 93 369
2. Request appointment for "Beratungsgespräch"
3. Provide test data:
   - Name: "Test Kunde Production"
   - Date: Tomorrow
   - Time: 14:00
   - Email: test@askproai.de

### 3. Verification
Check if appointment was created:
```sql
SELECT 
    a.id,
    a.starts_at,
    c.name as customer,
    s.name as service,
    a.status,
    a.calcom_event_type_id
FROM appointments a
JOIN customers c ON a.customer_id = c.id
LEFT JOIN services s ON a.service_id = s.id
WHERE a.created_at > NOW() - INTERVAL 1 HOUR
ORDER BY a.created_at DESC;
```

## 📊 Monitoring Setup

### 1. Real-time Monitoring
```bash
# Watch appointment creation
tail -f storage/logs/laravel-*.log | grep -E "appointment|booking|Retell" --color

# Monitor queue processing
php artisan horizon
```

### 2. Key Metrics to Track
- Calls per hour
- Appointment conversion rate
- Average booking time
- Error rate

### 3. Alerting Thresholds
- [ ] Failed webhook > 5 in 10 minutes
- [ ] Appointment creation error > 3 in 5 minutes
- [ ] Queue depth > 100 jobs
- [ ] Horizon not running

## 🚦 Go/No-Go Criteria

### GO ✅
- All tests pass
- Real phone call successfully books appointment
- No errors in last 30 minutes
- Queue processing normally

### NO-GO ❌
- Any test failures
- Errors in appointment creation
- Queue backed up > 50 jobs
- Horizon issues

## 🔄 Rollback Plan

If issues occur after deployment:

1. **Immediate**: Disable Retell webhook
   ```bash
   # Comment out webhook route in routes/api.php
   # Line ~216: Route::post('/retell/webhook', ...)
   ```

2. **Revert Code**:
   ```bash
   git checkout [previous-commit-hash] -- app/Services/AppointmentBookingService.php
   git checkout [previous-commit-hash] -- app/Jobs/ProcessRetellCallEndedJob.php
   ```

3. **Clear Caches**:
   ```bash
   php artisan optimize:clear
   ```

## 📋 Post-Deployment Tasks

1. [ ] Monitor first 10 real appointments
2. [ ] Check email notifications are sent
3. [ ] Verify Cal.com bookings are created
4. [ ] Review conversion metrics after 24 hours
5. [ ] Document any issues or improvements

## 👥 Communication Plan

### Internal Team
- [ ] Notify development team of deployment time
- [ ] Alert support team about new feature
- [ ] Update internal documentation

### External
- [ ] Prepare customer announcement
- [ ] Update website features list
- [ ] Create help documentation

## ✅ Sign-offs

- [ ] Development Team Lead: _________________
- [ ] Operations Manager: _________________
- [ ] Product Owner: _________________
- [ ] Go-Live Time: _________________

---
*Last Updated: 2025-06-24*