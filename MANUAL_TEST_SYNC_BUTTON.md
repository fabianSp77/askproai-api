# Manual Testing Guide - Cal.com Sync Button

## Quick Test (5 minutes)

### 1. Navigate to Service View
```
URL: https://your-domain.com/admin/services/32
Service: 15 Minuten Schnellberatung
Event Type ID: 3664712
```

### 2. Locate Sync Button
- Look for "Cal.com Sync" button in header actions
- Icon: Refresh/circular arrow
- Color: Primary (blue)

### 3. Click Sync Button
**Expected Modal Content:**
```
Heading: Service mit Cal.com synchronisieren

Description:
Dies synchronisiert den Service "15 Minuten Schnellberatung"
mit dem Cal.com Event Type (ID: 3664712).

Folgende Daten werden übertragen:
• Name und Beschreibung
• Dauer (15 Min.)
• Pufferzeit (0 Min.)
• Preis (€XX.XX)

Die Synchronisation erfolgt asynchron im Hintergrund.

Button: "Jetzt synchronisieren"
```

### 4. Confirm Sync
Click "Jetzt synchronisieren"

**Expected Notification:**
```
Title: Synchronisation gestartet
Body: Die Synchronisation wurde in die Warteschlange gestellt
      und wird in Kürze durchgeführt.
Type: Success (green)
Duration: 5 seconds
```

### 5. Check Queue Processing

**Option A: Sync Queue (Current Config)**
```bash
# The job will execute immediately (queue.default = sync)
# Check logs immediately
tail -n 50 storage/logs/laravel.log | grep -i "cal.com"
```

**Option B: Async Queue (Production)**
```bash
# Process the job manually
php artisan queue:work --once

# Expected output:
[YYYY-MM-DD HH:MM:SS][job-id] Processing: App\Jobs\UpdateCalcomEventTypeJob
[YYYY-MM-DD HH:MM:SS][job-id] Processed:  App\Jobs\UpdateCalcomEventTypeJob
```

### 6. Verify Sync Status

**Via Database:**
```sql
SELECT id, name, sync_status, last_calcom_sync, sync_error
FROM services
WHERE id = 32;
```

**Expected Result:**
- `sync_status`: 'synced' (if successful) or 'error' (if failed)
- `last_calcom_sync`: Current timestamp
- `sync_error`: NULL (if successful) or error message (if failed)

**Via Tinker:**
```bash
php artisan tinker

$service = App\Models\Service::find(32);
echo "Status: {$service->sync_status}\n";
echo "Last Sync: {$service->last_calcom_sync}\n";
echo "Error: {$service->sync_error}\n";
```

### 7. Verify Cal.com Update (Optional)

**Cal.com Dashboard:**
1. Login to Cal.com
2. Navigate to Event Types
3. Find Event Type ID 3664712
4. Verify details match service configuration

**Cal.com API (Advanced):**
```bash
# Get Event Type details via API
curl -X GET "https://api.cal.com/v2/event-types/3664712" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "cal-api-version: 2024-08-13"
```

---

## Edge Case Testing

### Test Case 1: Service Without Event Type ID

**Setup:**
```sql
-- Temporarily remove Event Type ID
UPDATE services SET calcom_event_type_id = NULL WHERE id = 999;
```

**Test:**
1. Navigate to /admin/services/999
2. Verify "Cal.com Sync" button is HIDDEN

**Expected:** Button not visible (->visible() returns false)

---

### Test Case 2: Sync Already Pending

**Setup:**
```sql
-- Set sync status to pending
UPDATE services SET sync_status = 'pending' WHERE id = 32;
```

**Test:**
1. Navigate to /admin/services/32
2. Click "Cal.com Sync" button
3. Click "Jetzt synchronisieren"

**Expected Notification:**
```
Title: Synchronisation läuft bereits
Body: Eine Synchronisation für diesen Service ist bereits
      in Bearbeitung. Bitte warten Sie einen Moment.
Type: Info (blue)
Duration: 5 seconds
```

**Cleanup:**
```sql
-- Reset sync status
UPDATE services SET sync_status = 'synced' WHERE id = 32;
```

---

### Test Case 3: Multiple Rapid Clicks

**Test:**
1. Navigate to /admin/services/32
2. Click "Cal.com Sync" button
3. Click "Jetzt synchronisieren"
4. IMMEDIATELY click "Cal.com Sync" again

**Expected:**
- First click: Success notification
- Second click: Info notification (sync already pending)

**Note:** This tests the duplicate job prevention logic.

---

### Test Case 4: Cal.com API Error (Simulation)

**Setup:**
```bash
# Temporarily set invalid Cal.com API key
# Edit .env:
CALCOM_API_KEY=invalid_key_for_testing
```

**Test:**
1. Navigate to /admin/services/32
2. Click "Cal.com Sync" button
3. Click "Jetzt synchronisieren"
4. Wait for job processing

**Expected:**
- Job will fail after retries
- `sync_status` set to 'error'
- `sync_error` contains error message
- Error logged in storage/logs/laravel.log

**Cleanup:**
```bash
# Restore correct API key
CALCOM_API_KEY=your_actual_api_key
```

---

## Log Monitoring

### Watch Logs in Real-Time
```bash
# All logs
tail -f storage/logs/laravel.log

# Cal.com specific
tail -f storage/logs/laravel.log | grep -i "cal.com"

# Job processing
tail -f storage/logs/laravel.log | grep -i "updatecalcom"
```

### Expected Log Entries (Success)

```
[2025-10-25 HH:MM:SS] local.INFO: [Cal.com Update] Successfully updated Event Type
{"service_id":32,"event_type_id":"3664712"}
```

### Expected Log Entries (Failure)

```
[2025-10-25 HH:MM:SS] local.ERROR: [Cal.com Update] Failed to update Event Type
{"service_id":32,"event_type_id":"3664712","error":"Cal.com API error: 401","response":{...}}
```

---

## Troubleshooting

### Button Not Visible
**Cause:** Service has no `calcom_event_type_id`
**Fix:** Assign Event Type ID to service

### Notification Shows But Nothing Happens
**Cause:** Queue worker not running (if queue.default != sync)
**Fix:** Run `php artisan queue:work`

### Sync Status Stays 'pending'
**Cause:** Job failed silently or queue worker stopped
**Check:**
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry JOB_ID
```

### Cal.com Not Updated
**Cause:** API authentication failure or network issue
**Check:**
```bash
# Test Cal.com API connectivity
curl -X GET "https://api.cal.com/v2/me" \
  -H "Authorization: Bearer $CALCOM_API_KEY" \
  -H "cal-api-version: 2024-08-13"
```

---

## Cleanup

After testing, run:

```bash
# Clear any test data
php artisan cache:clear

# Remove verification script (optional)
rm verify_sync_button_fix.php

# Keep documentation
# CALCOM_SYNC_BUTTON_FIX_VERIFICATION.md
# MANUAL_TEST_SYNC_BUTTON.md
```

---

## Success Criteria

✅ **Implementation Complete**
- [ ] Button visible for services with Event Type ID
- [ ] Button hidden for services without Event Type ID
- [ ] Confirmation modal shows correct information
- [ ] Success notification appears on sync
- [ ] Job dispatched to queue
- [ ] Sync status updated to 'pending' → 'synced'/'error'

✅ **Edge Cases Handled**
- [ ] Duplicate sync prevented
- [ ] API errors logged
- [ ] User feedback for all scenarios

✅ **Production Ready**
- [ ] No TODO comments
- [ ] Syntax valid
- [ ] Follows Filament 3 patterns
- [ ] Error handling comprehensive

---

**Status:** ✅ Ready for Production Testing
**Last Updated:** 2025-10-25
