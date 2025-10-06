# Fix: Customer Creation - company_id Mass Assignment Issue

## Date: 2025-10-05

## Problem Description

### Root Cause
Laravel's mass assignment protection was silently ignoring `company_id` and `branch_id` fields when creating Customer records via `Customer::create()`, because these fields are in the `$guarded` array in the Customer model.

### Impact
- Cal.com bookings succeeded
- Database customer/appointment creation FAILED with: `Field 'company_id' doesn't have a default value`
- System out of sync: Cal.com has bookings, database has nothing
- Reschedule operations failed because appointments don't exist in database

### Affected Scenarios
- Anonymous callers (phone: "anonymous") booking appointments
- All appointment bookings via `ensureCustomerFromCall()` method
- Customer creation from Retell webhooks

## Discovered During
Test calls 630 and 634:
- **Call 630**: Book + Reschedule in same call → Booking succeeded in Cal.com, failed in DB
- **Call 634**: Standalone reschedule → Failed because appointment from Call 630 doesn't exist

## Fixed Files

### 1. `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

**Lines 1696-1718** - Anonymous caller customer creation:
```php
// BEFORE (BROKEN):
$customer = Customer::create([
    'company_id' => $call->company_id,  // ← Silently ignored!
    'branch_id' => $call->branch_id,    // ← Silently ignored!
    ...
]);

// AFTER (FIXED):
$customer = Customer::create([...]);
$customer->company_id = $call->company_id;
$customer->branch_id = $call->branch_id;
$customer->save();
```

**Lines 1733-1753** - Normal caller customer creation:
Same fix pattern applied.

### 2. `/var/www/api-gateway/app/Services/Retell/AppointmentCreationService.php`

**Lines 416-436** - Anonymous caller customer creation:
Same fix pattern applied.

### 3. `/var/www/api-gateway/app/Services/Webhook/BookingService.php`

**Lines 302-318** - Webhook customer creation:
Same fix pattern applied.

## Solution Pattern

```php
// Step 1: Create without guarded fields
$customer = Customer::create([
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'source' => 'retell_webhook',
    'status' => 'active'
]);

// Step 2: Set guarded fields directly (bypass mass assignment protection)
$customer->company_id = $call->company_id;
$customer->branch_id = $call->branch_id;
$customer->save();
```

## Why This Works
- Laravel's `$guarded` protection only applies to mass assignment (`create()`, `fill()`, `update()`)
- Direct property assignment bypasses this protection
- This is the recommended Laravel approach for setting guarded fields

## Testing Status
- ✅ Code changes deployed
- ✅ Opcache cleared
- ⏳ Awaiting test call to verify fix

## Related Issues
- See: `/var/www/api-gateway/claudedocs/cal-com-reschedule-analysis-2025-10-05.md`
- Bug #1: Stale booking ID after reschedule (FIXED)
- Bug #2: Misleading error messages (FIXED)
- Bug #3: Wrong appointment selection (PARTIALLY FIXED)
- **Bug #4**: company_id mass assignment (THIS FIX)

## Prevention
Consider adding validation in Customer model to ensure `company_id` is always set:
```php
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($customer) {
        if (empty($customer->company_id)) {
            throw new \Exception('company_id is required');
        }
    });
}
```
