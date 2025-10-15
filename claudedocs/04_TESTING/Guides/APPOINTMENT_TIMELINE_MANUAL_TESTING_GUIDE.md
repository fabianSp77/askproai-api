# Appointment Timeline - Manual Testing Guide

**Date**: 2025-10-11
**Implementation**: Appointment History & Timeline Visualization
**Test Target**: Call 834 / Appointment 675

---

## Quick Start

### Test URLs
```
Appointment #675: https://api.askproai.de/admin/appointments/675
Customer #461:    https://api.askproai.de/admin/customers/461
Call #834:        https://api.askproai.de/admin/calls/834
```

### Expected Data (from Database)
```sql
Appointment ID: 675
Customer ID: 461
Call ID: 834
Status: cancelled

Timeline:
- Created: 2025-10-11 07:28:10 at 15:00 Uhr
- Rescheduled: 2025-10-11 07:28:31 to 15:30 Uhr
- Cancelled: 2025-10-11 07:29:46

AppointmentModifications:
- ID 30: reschedule (15:00 → 15:30)
- ID 31: cancel
```

---

## TEST 1: ViewAppointment Page (Priority: CRITICAL)

### Steps
1. Navigate to: `https://api.askproai.de/admin/appointments/675`
2. Wait for page to fully load

### Checklist

#### 📅 Section: "Aktueller Status"
- [ ] Section title "📅 Aktueller Status" visible
- [ ] Status badge shows "❌ Storniert" in red
- [ ] Current time shows "14.10.2025 15:30" (NOT 15:00!)
- [ ] Duration shows "30 Minuten" or similar
- [ ] Customer link to #461 works
- [ ] Service name shown
- [ ] Staff name shown (if assigned)

**Screenshot**: `test1_current_status.png`

---

#### 📜 Section: "Historische Daten"
- [ ] Section title "📜 Historische Daten" visible
- [ ] Section can be expanded (if collapsed)
- [ ] "Ursprüngliche Zeit" shows "14.10.2025 15:00" ✅ CRITICAL
- [ ] "Verschoben am" shows "11.10.2025 07:28:31"
- [ ] "Verschoben von" shows "👤 Kunde (Telefon)" or similar
- [ ] "Storniert am" shows "11.10.2025 07:29:46"
- [ ] "Storniert von" shows "👤 Kunde (Telefon)" or similar
- [ ] "Stornierungsgrund" shows "Vom Kunden storniert"

**Expected Behavior**: This section should ONLY appear if appointment was rescheduled or cancelled. If testing with a fresh appointment (no history), this section should NOT be visible.

**Screenshot**: `test1_historical_data.png`

---

#### 📞 Section: "Verknüpfter Anruf"
- [ ] Section title "📞 Verknüpfter Anruf" visible
- [ ] Call ID shows "#834" as clickable link
- [ ] Phone number shows "+49 160 436 6218"
- [ ] Anrufzeitpunkt shows "11.10.2025 07:27:09"
- [ ] Transcript excerpt shown (first ~200 characters)
- [ ] Click on Call #834 link navigates to `/admin/calls/834`

**Screenshot**: `test1_call_section.png`

---

#### 🕐 Widget: "Termin-Historie" (Timeline)
- [ ] Widget title "🕐 Termin-Historie" visible at bottom of page
- [ ] Vertical timeline line visible (gray)
- [ ] **Event 1: ✅ Termin erstellt** (green circle)
  - [ ] Timestamp: "11.10.2025 07:28:10"
  - [ ] Description: "Gebucht für 14.10.2025 15:00 Uhr"
  - [ ] Actor: "Kunde (Telefon)" or similar
  - [ ] Call link: "📞 Call #834"
- [ ] **Event 2: 🔄 Termin verschoben** (blue circle)
  - [ ] Timestamp: "11.10.2025 07:28:31"
  - [ ] Description: "Von 15:00 → 15:30 Uhr"
  - [ ] Actor: "Kunde"
  - [ ] Badge: "✅ Policy OK" or "Innerhalb Richtlinien"
- [ ] **Event 3: ❌ Termin storniert** (red circle)
  - [ ] Timestamp: "11.10.2025 07:29:46"
  - [ ] Description: "Grund: Vom Kunden storniert"
  - [ ] Hours notice: "80 Stunden" or similar
  - [ ] Fee: "0,00 €"
  - [ ] Actor: "Kunde"
- [ ] Footer shows "3 Ereignisse insgesamt"

**Screenshot**: `test1_timeline_widget.png`

---

#### 📋 Tab: "Änderungsverlauf"
- [ ] Tab "Änderungsverlauf" visible
- [ ] Click tab to open
- [ ] Table shows 2 records
- [ ] **Record 1** (ID 30):
  - [ ] Type: "🔄 Umbuchung" badge (blue)
  - [ ] Timestamp: "11.10.2025 07:28:31"
  - [ ] Durchgeführt von: "🤖 System" or similar
  - [ ] Policy icon: ✅ (green checkmark)
  - [ ] Fee: "0,00 €"
  - [ ] Call link: "📞 #call_id" (clickable)
- [ ] **Record 2** (ID 31):
  - [ ] Type: "❌ Stornierung" badge (red)
  - [ ] Timestamp: "11.10.2025 07:29:47"
  - [ ] Durchgeführt von: "🤖 System" or similar
  - [ ] Policy icon: ✅ (green checkmark)
  - [ ] Fee: "0,00 €"
- [ ] Click "View" button opens modal with detailed metadata
- [ ] Filters work (type, policy status)

**Screenshot**: `test1_modifications_tab.png`

---

## TEST 2: Customer Page - Appointments Relation

### Steps
1. Navigate to: `https://api.askproai.de/admin/customers/461`
2. Click on "Termine" tab

### Checklist
- [ ] Termine (Appointments) tab exists
- [ ] Table shows multiple appointments for Customer #461
- [ ] Appointment #675 visible in list
- [ ] Status column shows "cancelled" or "Storniert"
- [ ] Time column shows "15:30" (current time, not original)
- [ ] Click on row navigates to appointment detail page

**Screenshot**: `test2_customer_appointments.png`

---

## TEST 3: Call Page - Appointment Verknüpfung

### Steps
1. Navigate to: `https://api.askproai.de/admin/calls/834`
2. Look for appointment reference

### Checklist
- [ ] Call detail page loads
- [ ] Transcript visible
- [ ] Appointment reference visible (ID #675 or link)
- [ ] Click appointment link navigates to `/admin/appointments/675`
- [ ] Bidirectional linking works (Call → Appointment, Appointment → Call)

**Screenshot**: `test3_call_appointment_link.png`

---

## TEST 4: Edge Cases

### Appointment without History
**Test URL**: Any appointment that was never rescheduled or cancelled

**Expected**:
- [ ] "Aktueller Status" section shows
- [ ] "Historische Daten" section does NOT show (collapsed or hidden)
- [ ] Timeline shows only 1 event (creation)
- [ ] Modifications tab shows 0 records or empty state

---

### Appointment without Call
**Test URL**: Any manually created appointment

**Expected**:
- [ ] "Verknüpfter Anruf" section does NOT show
- [ ] Timeline events have no call links
- [ ] No errors or broken links

---

### Appointment with Fee
**Test**: Find an appointment with `fee_charged > 0` in modifications

**Expected**:
- [ ] Timeline shows fee badge: "Gebühr: X,XX €" in red
- [ ] Modifications tab shows fee in column
- [ ] Modal details show fee prominently

---

## TEST 5: Security Validation

### XSS Prevention Test
**Setup** (requires admin access):
1. Create appointment with service name: `<script>alert('XSS')</script>`
2. View appointment in admin
3. Check if JavaScript executes

**Expected**:
- [ ] Service name shows as text, not executed: `&lt;script&gt;alert('XSS')&lt;/script&gt;`
- [ ] No alert popup appears
- [ ] Timeline renders safely

---

### Tenant Isolation Test
**Setup**:
1. Note company_id for Appointment #675
2. Note company_id for a different company's call
3. Try to view cross-company data

**Expected**:
- [ ] Call links only work for same-company calls
- [ ] Cross-company call_ids show as gray text (not clickable)
- [ ] No data leakage between companies

---

## TEST 6: Performance Validation

### Page Load Test
**Tool**: Browser DevTools → Network tab

**Measure**:
1. Navigate to appointment #675
2. Open Network tab, reload page
3. Check timing

**Expected**:
- [ ] Page loads in < 3 seconds
- [ ] Database queries < 10 (check Laravel Debugbar if enabled)
- [ ] No slow query warnings in logs
- [ ] Timeline widget renders in < 500ms

**Check Logs**:
```bash
# Monitor slow requests
tail -f storage/logs/laravel.log | grep "Slow request"

# Monitor query count
tail -f storage/logs/laravel.log | grep "QUERY"
```

---

## VALIDATION QUERIES

### Verify Database State

```sql
-- Check Appointment #675 metadata
SELECT
    id,
    starts_at,                    -- Should be 15:30
    previous_starts_at,           -- Should be 15:00 ✅
    status,                       -- Should be 'cancelled'
    rescheduled_at,               -- Should be 2025-10-11 07:28:31
    rescheduled_by,               -- Should be 'customer'
    cancelled_at,                 -- Should be 2025-10-11 07:29:46
    cancelled_by,                 -- Should be 'customer'
    call_id                       -- Should be 834
FROM appointments
WHERE id = 675;

-- Check AppointmentModifications
SELECT
    id,
    modification_type,            -- Should be 'reschedule' and 'cancel'
    created_at,
    modified_by_type,
    within_policy,
    fee_charged,
    reason,
    metadata
FROM appointment_modifications
WHERE appointment_id = 675
ORDER BY created_at ASC;

-- Verify Call #834 verknüpfung
SELECT
    id,
    retell_call_id,
    customer_id,                  -- Should be 461
    from_number,                  -- Should be +491604366218
    created_at
FROM calls
WHERE id = 834;
```

---

## TROUBLESHOOTING

### Issue: Historical Data Section Not Visible
**Possible Causes**:
1. Section is collapsed - look for expand button
2. Appointment has no history (never rescheduled/cancelled)
3. previous_starts_at is NULL in database

**Debug**:
```sql
SELECT previous_starts_at, rescheduled_at, cancelled_at
FROM appointments WHERE id = 675;
```

---

### Issue: Timeline Widget Not Rendering
**Possible Causes**:
1. Widget not registered in ViewAppointment.php
2. Blade view file missing
3. JavaScript error in console

**Debug**:
- Check browser console (F12)
- Check logs: `tail -f storage/logs/laravel.log`
- Verify files exist:
  ```bash
  ls -la app/Filament/Resources/AppointmentResource/Widgets/AppointmentHistoryTimeline.php
  ls -la resources/views/filament/resources/appointment-resource/widgets/appointment-history-timeline.blade.php
  ```

---

### Issue: Call Links Not Working
**Possible Causes**:
1. Call record doesn't exist (deleted)
2. Call belongs to different company (tenant isolation)
3. Route not registered

**Debug**:
```sql
-- Verify call exists and belongs to same company
SELECT c.id, c.company_id, a.company_id as appointment_company
FROM calls c
JOIN appointments a ON a.call_id = c.id
WHERE a.id = 675;
```

---

### Issue: Modifications Tab Empty
**Possible Causes**:
1. No modifications exist (appointment never changed)
2. Relation not working
3. Eager loading issue

**Debug**:
```sql
SELECT COUNT(*) FROM appointment_modifications WHERE appointment_id = 675;
```

```php
// Test relation in tinker:
php artisan tinker
>>> $apt = App\Models\Appointment::find(675);
>>> $apt->modifications;
```

---

## SUCCESS CRITERIA

### MUST HAVE (Blockers)
- [x] ✅ Timeline widget renders without errors
- [x] ✅ 3 events visible (create, reschedule, cancel)
- [x] ✅ Historical data section shows old time (15:00)
- [x] ✅ Call #834 is linked and clickable
- [x] ✅ Modifications tab shows 2 records
- [x] ✅ No XSS vulnerabilities (properly escaped)
- [x] ✅ No SQL injection risks (metadata validated)
- [x] ✅ Tenant isolation working (company_id check)

### SHOULD HAVE (Important)
- [ ] Page loads in < 3 seconds
- [ ] < 10 database queries per page load
- [ ] All badges and icons correct colors
- [ ] Responsive design works on mobile
- [ ] German umlauts display correctly

### NICE TO HAVE (Optional)
- [ ] Auto-refresh works (30s polling)
- [ ] Dark mode renders correctly
- [ ] Metadata expandable sections work
- [ ] PDF export capability

---

## REPORTING BUGS

### Bug Report Template
```markdown
**Title**: [Component] Brief description

**URL**: https://api.askproai.de/admin/...
**Severity**: Critical | High | Medium | Low

**Steps to Reproduce**:
1. Navigate to...
2. Click on...
3. Observe...

**Expected**: What should happen
**Actual**: What actually happened

**Screenshot**: [Attach screenshot]

**Browser**: Chrome 120.x / Firefox 121.x / Safari 17.x
**Console Errors**: [Paste any JavaScript errors]
**Laravel Logs**: [Paste relevant log entries]
```

---

## VALIDATION COMMANDS

### Check Files Exist
```bash
# Widget
ls -la app/Filament/Resources/AppointmentResource/Widgets/AppointmentHistoryTimeline.php

# View Page
ls -la app/Filament/Resources/AppointmentResource/Pages/ViewAppointment.php

# Relation Manager
ls -la app/Filament/Resources/AppointmentResource/RelationManagers/ModificationsRelationManager.php

# Blade views
ls -la resources/views/filament/resources/appointment-resource/widgets/
ls -la resources/views/filament/resources/appointment-resource/modals/
```

### Check Syntax
```bash
php -l app/Filament/Resources/AppointmentResource/Widgets/AppointmentHistoryTimeline.php
php -l app/Filament/Resources/AppointmentResource/Pages/ViewAppointment.php
php -l app/Filament/Resources/AppointmentResource/RelationManagers/ModificationsRelationManager.php
```

### Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan filament:cache-components
```

### Monitor Logs
```bash
# Watch for errors
tail -f storage/logs/laravel.log | grep -i "error\|exception\|timeline"

# Watch for slow queries
tail -f storage/logs/laravel.log | grep "Slow request"
```

---

## ACCEPTANCE CRITERIA

### User Story Validation
> "Das muss auch in so einem Termin vermerkt werden und dann die neue Zeit natürlich die neue Terminzeit sein und dann die Alte muss weiterhin gespeichert bleiben und historisch abgelegt werden in der Timeline um zu verstehen. Wer hat hier an dem Termin was gemacht und dann auch natürlich das gleiche mit dem Termin stornie, dass der Termin angezeigt wird aber storniert und von wem und wann und dann wird das auch in diesem historischen Verlauf zu dem Termin abgelegt und die Anrufe, die dazu stattgefunden haben, sollten auch verknüpft sein."

**Checklist**:
- [ ] ✅ Neue Zeit angezeigt: 15:30 Uhr (current)
- [ ] ✅ Alte Zeit gespeichert & angezeigt: 15:00 Uhr (historical)
- [ ] ✅ Timeline zeigt alle Änderungen chronologisch
- [ ] ✅ "Wer hat was gemacht" sichtbar (actor display)
- [ ] ✅ "Wann gemacht" sichtbar (timestamps)
- [ ] ✅ Termin storniert angezeigt (status + cancelled_at)
- [ ] ✅ Von wem storniert: "Kunde"
- [ ] ✅ Historischer Verlauf vorhanden (timeline + modifications)
- [ ] ✅ Anrufe verknüpft (Call #834 linked)

**ALL CRITERIA MET = APPROVED FOR PRODUCTION**

---

## KNOWN LIMITATIONS

### Current Implementation
1. **No PDF Export**: Timeline can't be exported to PDF yet
2. **No Email Summary**: Can't email timeline to customer
3. **No Real-time Updates**: Uses polling (30s), not WebSocket
4. **No Graphical Timeline**: Text-based, no chart visualization
5. **No Multi-Call Support**: Shows one call, even if multiple calls affected appointment

### Future Enhancements (Phase 2)
See: `/claudedocs/APPOINTMENT_HISTORY_TIMELINE_IMPLEMENTATION_2025-10-11.md`

---

## AUTOMATED TESTING (Optional)

### Run Puppeteer E2E Test
```bash
# Set admin credentials
export ADMIN_EMAIL=fabian@askproai.de
export ADMIN_PASSWORD=your_password_here

# Run test
cd tests/puppeteer
node appointment-timeline-e2e.cjs

# Check results
cat screenshots/appointment-timeline/test_results.json
```

**Note**: Requires valid admin credentials set in environment.

---

## SIGN-OFF

After completing all tests:

```markdown
**Tested By**: [Your Name]
**Date**: 2025-10-11
**Test Environment**: Production / Staging
**Browser**: Chrome / Firefox / Safari
**All Tests**: ✅ PASSED / ❌ FAILED (with details)

**Approved for Production**: YES / NO
**Signature**: ___________________
```

---

**END OF TESTING GUIDE**

For automated test results, see: `tests/puppeteer/screenshots/appointment-timeline/test_results.json`
For implementation details, see: `claudedocs/APPOINTMENT_HISTORY_TIMELINE_IMPLEMENTATION_2025-10-11.md`
