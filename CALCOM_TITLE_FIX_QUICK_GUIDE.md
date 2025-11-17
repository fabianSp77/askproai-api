# Cal.com Title Field - Quick Fix Guide
**Status**: 🚨 PRODUCTION BROKEN
**Time to Fix**: 5 minutes
**Urgency**: IMMEDIATE

---

## The Problem (One Sentence)

Title field was removed from `bookingFieldsResponses` in commit `fa4e0f337`, breaking ALL Cal.com bookings.

---

## The Fix (Copy-Paste Ready)

### File: `app/Services/Retell/AppointmentCreationService.php`

**Location**: Lines 888-897 (inside `$bookingData` array)

**BEFORE** (BROKEN):
```php
$bookingData = [
    'eventTypeId' => $service->calcom_event_type_id,
    'startTime' => $startTime->toIso8601String(),
    'endTime' => $startTime->copy()->addMinutes($durationMinutes)->toIso8601String(),
    'name' => $sanitizedName,
    'email' => $sanitizedEmail,
    'phone' => $sanitizedPhone,
    'timeZone' => self::DEFAULT_TIMEZONE,
    'language' => self::DEFAULT_LANGUAGE
];
```

**AFTER** (FIXED):
```php
$bookingData = [
    'eventTypeId' => $service->calcom_event_type_id,
    'startTime' => $startTime->toIso8601String(),
    'endTime' => $startTime->copy()->addMinutes($durationMinutes)->toIso8601String(),
    'name' => $sanitizedName,
    'email' => $sanitizedEmail,
    'phone' => $sanitizedPhone,
    'timeZone' => self::DEFAULT_TIMEZONE,
    'language' => self::DEFAULT_LANGUAGE,
    'title' => $service->name,          // ✅ ADD THIS
    'service_name' => $service->name    // ✅ ADD THIS (fallback)
];
```

**That's it!** The existing code in `CalcomService.php` (lines 146-154) will automatically put this into `bookingFieldsResponses`.

---

## Test the Fix

### Quick Test (30 seconds)
```bash
# 1. Open: https://api.askproai.de/test-retell-functions
# 2. Click "start_booking"
# 3. Fill in test data
# 4. Submit
# 5. Check logs - should see HTTP 200 (not 400)
```

### Voice Agent Test (2 minutes)
```
1. Call: +49 30 33081738
2. Say: "Termin morgen 10 Uhr Herrenhaarschnitt"
3. Confirm booking
4. Check if appointment appears in Cal.com
```

---

## Why This Happened

```
Commit 71168954f (Nov 10) → ✅ Added title to bookingFieldsResponses
         ↓
Commit 50749ce42 (Nov 13) → ❌ Moved title to WRONG location (payload root)
         ↓
Cal.com Error: "title should not exist" (in payload root)
         ↓
Commit fa4e0f337 (Nov 13) → ❌ Removed title from EVERYWHERE
         ↓
Cal.com Error: "title required" (in bookingFieldsResponses)
         ↓
🚨 ALL BOOKINGS BROKEN
```

**Root Cause**: Developer misinterpreted "title should not exist" (in payload root) as "remove title entirely"

**Reality**: Title should ONLY exist in `bookingFieldsResponses`, NOT in payload root.

---

## What Cal.com Actually Wants

```json
{
  "eventTypeId": 123,
  "start": "2025-11-14T08:00:00Z",
  "attendee": { ... },
  "bookingFieldsResponses": {
    "title": "Service Name",  ← ✅ HERE (REQUIRED)
    "phone": "+49...",
    "notes": "..."
  },
  "title": "Service Name"  ← ❌ NOT HERE (REJECTED)
}
```

---

## Impact

**Since 2025-11-13 17:10 CET**:
- ❌ Zero successful bookings
- ❌ All voice agent bookings fail silently
- ❌ All API bookings fail
- ❌ Customers think they booked but no appointment created

---

## Deployment

**No restart needed** - Laravel auto-reloads PHP files
**Just save the file** and test immediately

---

## Prevention

1. ✅ Always test before pushing
2. ✅ Check git history before "fixing" something
3. ✅ Read error messages carefully ("should not exist IN PAYLOAD ROOT" vs "remove entirely")
4. ✅ Add unit test for required fields

---

**Full RCA**: See `CALCOM_TITLE_FIELD_RCA_2025-11-13.md`
