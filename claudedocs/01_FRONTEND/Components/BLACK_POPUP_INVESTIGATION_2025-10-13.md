# Schwarzes Pop-up Fenster - Investigation

**Date:** 2025-10-13 20:16
**Status:** 🔴 UNDER INVESTIGATION
**User Report:** "Nachdem ich Datum und Uhrzeit ausgewählt hab, geht ein schwarzes Pop-up Fenster auf nach dem Speichern"

---

## Problem Summary

**User sees:** Black popup window after clicking "Speichern" (Save)
**Cannot read:** Error message is not readable (black background?)

**Critical Finding:** Server logs show **NO SAVE REQUEST** reaching the backend!
- Page loads successfully
- Form displays correctly
- But when user clicks "Speichern", NO HTTP request reaches Laravel

**This means:** The error is happening in the **FRONTEND** (JavaScript/Livewire), not the backend!

---

## Timeline of Events

### 20:07:12 - First Save Attempt (BEFORE fixes)
```
✅ UPDATE successful: starts_at = '2025-10-27 12:00:00'
❌ ERROR after save: SyncToCalcomOnRescheduled listener failed
🔴 500 ERROR returned to browser
```

### 20:11:03 - Second Save Attempt (BEFORE fixes)
```
✅ UPDATE successful: starts_at = '2025-10-19 12:00:00'
❌ ERROR after save: SyncToCalcomOnRescheduled listener failed
🔴 500 ERROR returned to browser
```

### 20:14:00 - Listener Disabled in Code
```php
// app/Listeners/Appointments/SyncToCalcomOnRescheduled.php:40
public function handle(AppointmentRescheduled $event): void
{
    // ⚠️ TEMPORARILY DISABLED - Migration pending
    return; // Early exit - no more errors!
}
```

### 20:15:02 - User Loads Edit Page (AFTER fix)
```
✅ Page loads successfully (appointment #702)
✅ All data displayed correctly
⏳ User clicks "Speichern"...
❌ NO HTTP REQUEST in logs!
```

**No entries after 20:15:02 showing:**
- `livewire/update` POST request
- `appointments` UPDATE query
- Any 500 ERROR

**Conclusion:** The save button click is **NOT reaching the server**!

---

## Possible Causes (Frontend Issues)

### 1. Filament Validation Error (Most Likely)
The DateTimePicker might have a validation error that shows as a black notification:

```php
// Current code:
Forms\Components\DateTimePicker::make('starts_at')
    ->required()
    ->native(false)
    ->disabled(fn ($get) => !$get('staff_id'))
```

**Potential issues:**
- Field might be disabled when user tries to save
- Date format mismatch (d.m.Y H:i vs Y-m-d H:i:s)
- Min/max date validation failing
- Required validation on disabled field

### 2. JavaScript Error in Browser
Filament/Livewire JavaScript error preventing form submission:

**Need to check:**
- Browser console for JavaScript errors
- Network tab for failed requests
- Livewire component state

### 3. Filament Notification Styling Issue
Black popup = Filament notification with broken CSS?

**Check:**
- `public/css/filament/` CSS files
- Filament asset compilation
- Notification component rendering

---

## Investigation Steps

### Step 1: Check Browser Console
**User needs to:**
1. Open appointment #702 edit page
2. Press F12 to open Developer Tools
3. Go to "Console" tab
4. Click "Speichern" button
5. Look for RED error messages
6. Screenshot any errors

**Expected findings:**
- JavaScript errors like "Uncaught TypeError..."
- Livewire errors like "Cannot read property..."
- Network errors like "Failed to fetch..."

### Step 2: Check Network Tab
**User needs to:**
1. Open appointment #702 edit page
2. Press F12 → "Network" tab
3. Click "Speichern" button
4. Look for failed requests (red)
5. Screenshot the network activity

**Expected findings:**
- POST request to `/livewire/update` with status 4xx or 5xx
- Or NO request at all (confirms frontend blocking)

### Step 3: Check Notification Content
**User needs to:**
1. When black popup appears, try to:
   - Hover mouse over it
   - Right-click on it
   - Take a screenshot while it's visible
2. Try to read ANY text in the black area

**Expected findings:**
- Validation error message
- "This field is required"
- Date format error
- Or completely blank (CSS issue)

---

## Temporary Workaround

While investigating, user can try:

### Option A: Use Database Direct Edit (Admin)
```sql
UPDATE appointments
SET starts_at = '2025-10-20 14:00:00',
    ends_at = '2025-10-20 14:30:00'
WHERE id = 702;
```

### Option B: Check if CREATE works
Try creating a NEW appointment instead of editing:
- If creation works → Issue specific to edit mode
- If creation fails → Issue with DateTimePicker in general

### Option C: Disable DateTimePicker Validations
Temporarily remove strict validations:

```php
// Remove these lines temporarily:
->required()
->minDate(now())
->maxDate(now()->addWeeks(2))
```

---

## Code Locations to Review

### 1. DateTimePicker Implementation
**File:** `app/Filament/Resources/AppointmentResource.php`
**Lines:** 320-393

```php
Forms\Components\DateTimePicker::make('starts_at')
    ->label('⏰ Termin-Beginn')
    ->seconds(false)
    ->minDate(now())  // ← Could be blocking?
    ->maxDate(now()->addWeeks(2))  // ← Could be blocking?
    ->required()  // ← Could be blocking if field disabled?
    ->native(false)
    ->displayFormat('d.m.Y H:i')  // ← Format mismatch?
    ->reactive()
    ->disabled(fn (callable $get) => !$get('staff_id'))  // ← Stays disabled?
```

### 2. Livewire Component
**File:** `app/Filament/Resources/AppointmentResource/Pages/EditAppointment.php`

Check `afterSave()` hook and form submission logic.

### 3. Frontend Assets
**Files:**
- `public/js/filament/forms/components/*.js`
- `public/css/filament/forms/forms.css`

Check if Filament assets are properly compiled.

---

## Next Actions

**IMMEDIATE (User):**
1. Open Browser Console (F12)
2. Try to save again
3. Screenshot any error messages
4. Report findings back

**PENDING (Developer):**
1. Review DateTimePicker validation rules
2. Check Livewire form submission
3. Test with validation disabled
4. Consider reverting to old DatePicker + Radio approach if unsolvable

---

## Technical Details

### System Info
- **Laravel:** 11.x
- **Filament:** v3.3.39
- **Livewire:** v3.6.4
- **PHP:** 8.3-fpm
- **Browser:** Chrome 141

### Recent Changes
1. Replaced DatePicker + Radio with DateTimePicker
2. Disabled Cal.com sync listener
3. Added "Nächster freier Slot" button
4. Changed field structure from 132 lines → 73 lines

### Working Before
- Old implementation with DatePicker + Radio worked (with 500 error after save)
- Save requests DID reach server (even if they failed)

### Not Working Now
- DateTimePicker implementation blocks save before reaching server
- NO save requests in server logs
- Black popup appears (unreadable error)

---

## Status: BLOCKED

**Blocked by:** Need user to provide browser console errors

**Cannot proceed without:**
- JavaScript error messages from browser
- Network tab showing failed/missing requests
- Content of the black popup notification

**User action required:** Open F12 Developer Tools and provide screenshots

---

**Created:** 2025-10-13 20:16
**Last Updated:** 2025-10-13 20:16
**Investigator:** Claude
**Status:** Awaiting browser error details from user
