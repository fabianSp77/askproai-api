# 🔴 Test Call Booking Failure - Root Cause Analysis

**Datum**: 2025-10-06 11:00 CEST
**Call ID**: `call_b06f0eeb39cc53a3657bbf069b7`
**Status**: ✅ FIXED

---

## 📋 Error Summary

### User Test Call Details
- **Customer**: Hansi Schuster
- **Requested Date**: 2025-10-10
- **Requested Time**: 10:15 (initially), then "ersten freien Termin am Vormittag"
- **Service**: Beratung
- **Outcome**: 500 Server Error - Appointment booking failed

### Error Message
```
TypeError: App\Services\AppointmentAlternativeFinder::setTenantContext():
Argument #2 ($branchId) must be of type ?int, string given,
called in /var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php on line 981
```

---

## 🔍 Root Cause Analysis

### The Problem
**Type Mismatch**: `branch_id` column type vs function signature type incompatibility

### Database Schema Reality
```php
// Call model database column
branch_id: UUID string
// Example: '9f4d5e2a-46f7-41b6-b81d-1532725381d4'
```

### Code Expectation (WRONG)
```php
// AppointmentAlternativeFinder.php:37 (BEFORE FIX)
public function setTenantContext(?int $companyId, ?int $branchId = null): self
//                                                  ^^^^ Expected integer!
```

### What Happened
1. **Test call made** by user requesting appointment
2. **Exact time (10:15) NOT available** in Cal.com slots
3. **Alternative slot search triggered** (RetellFunctionCallHandler.php:976)
4. **Call record loaded** with `branch_id = '9f4d5e2a-...'` (UUID string)
5. **setTenantContext() called** at line 981:
   ```php
   $alternatives = $this->alternativeFinder
       ->setTenantContext($companyId, $branchId)  // ❌ Passing UUID string
   ```
6. **TypeError thrown** because function expected `?int` but received `string`
7. **500 error returned** to Retell.ai agent
8. **Booking failed** - agent informed user of technical problem

---

## 🔧 The Fix

### Changed Files
**File**: `/var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php`

### Changes Applied

#### 1. Property Type Declaration (Line 25)
```php
// BEFORE
private ?int $branchId = null;

// AFTER
private ?string $branchId = null; // UUID string identifier
```

#### 2. Method Signature (Line 40)
```php
// BEFORE
public function setTenantContext(?int $companyId, ?int $branchId = null): self

// AFTER
/**
 * @param int|null $companyId
 * @param string|null $branchId UUID string identifier for branch
 */
public function setTenantContext(?int $companyId, ?string $branchId = null): self
```

---

## 📊 Why This Bug Occurred

### System Architecture Context
- **Multi-tenant system** with companies and branches
- **UUID identifiers** used for branch isolation (UUIDs are strings)
- **Integer identifiers** used for companies (traditional auto-increment)
- **AppointmentAlternativeFinder** recently added for "alternative slot" feature
- **Type hint** incorrectly assumed both IDs would be integers

### Branch ID Usage Pattern
```php
// Calls table schema
$table->uuid('branch_id')->nullable();

// Other methods in codebase correctly use string type
// Example: ServiceSelectionService
public function getDefaultService(int $companyId, ?string $branchId = null)
```

**Conclusion**: The `AppointmentAlternativeFinder` was the ONLY service using `?int` for `$branchId` - **inconsistent with the rest of the codebase**.

---

## ✅ Testing & Verification

### Type Check Before Fix
```bash
php artisan tinker
$call = \App\Models\Call::find(686);
echo gettype($call->branch_id);
# Output: string
echo $call->branch_id;
# Output: 9f4d5e2a-46f7-41b6-b81d-1532725381d4
```

### Why Error Only Occurred NOW
The alternative finder is only called when:
1. ✅ User requests specific time
2. ✅ That exact time is NOT available in Cal.com
3. ✅ System searches for alternative slots

**First booking attempt (10:15)**: Slot not available → trigger alternative finder → error
**Why previous tests passed**: If exact requested time IS available, alternative finder is never called!

---

## 🎯 Impact Assessment

### Calls Affected
- **Scope**: ANY call where requested time is unavailable
- **Frequency**: Depends on Cal.com availability vs user requests
- **User Experience**: Agent says "Es tut mir leid, es scheint ein technisches Problem zu geben"
- **Data Impact**: No data corruption - fail-fast with clear error

### Business Impact
- ❌ **Appointments could not be booked** when exact time unavailable
- ❌ **Alternative slot suggestions** completely broken
- ✅ **No data corruption** - system failed safely
- ✅ **Error logged clearly** for debugging

---

## 🚀 Resolution Timeline

| Time | Event |
|------|-------|
| 10:56:22 | User test call - booking failed with 500 error |
| 10:57:02 | TypeError logged to Laravel logs |
| 11:00:00 | User reported: "Hab ein Testanruf gemacht es gab aber ein technisches Problem" |
| 11:01:00 | Error logs analyzed - root cause identified |
| 11:02:00 | Type signatures corrected in AppointmentAlternativeFinder |
| 11:03:00 | Fix deployed - ready for re-test |

**Total Resolution Time**: ~7 minutes from user report to fix deployed

---

## 📝 Lessons Learned

### What Went Well
✅ **Fail-fast design**: System threw clear TypeError instead of silent failure
✅ **Comprehensive logging**: Error stack trace showed exact line and type mismatch
✅ **User feedback**: User immediately reported issue for investigation

### What Could Be Improved
⚠️ **Type consistency**: Inconsistent use of `int` vs `string` for `branch_id` across services
⚠️ **Testing coverage**: Alternative finder path not tested with real branch UUIDs
⚠️ **Type hints review**: Need systematic review of all tenant context methods

### Recommended Actions
1. **Code review**: Audit all `setTenantContext()` methods for type consistency
2. **Testing**: Add integration tests for alternative slot finder with real UUIDs
3. **Documentation**: Document that `branch_id` is UUID string, `company_id` is integer
4. **Linting**: Consider PHPStan/Psalm to catch type mismatches earlier

---

## 🔄 Next Steps

### Immediate (Done)
- ✅ Fix type signatures in AppointmentAlternativeFinder
- ✅ Document root cause and fix

### Short-term (To Do)
- ⏳ **User re-test**: Ask user to make another test call
- ⏳ **Verify alternative slots work**: Confirm alternatives returned when exact time unavailable
- ⏳ **Check Cal.com response**: Analyze `hosts` array for staff assignment (Phase 1 PoC)

### Long-term (Backlog)
- 🔮 Audit all tenant context methods for type consistency
- 🔮 Add integration tests for alternative finder
- 🔮 Document tenant ID conventions (UUID vs integer)

---

## 💡 Technical Debt Identified

### Type System Inconsistency
```php
// INCONSISTENT across codebase:

// ❌ AppointmentAlternativeFinder (was wrong)
->setTenantContext(?int $companyId, ?int $branchId)

// ✅ ServiceSelectionService (correct)
->getDefaultService(int $companyId, ?string $branchId)

// ✅ Database schema (source of truth)
$table->uuid('branch_id')->nullable();
```

**Recommendation**: Standardize on `?string $branchId` everywhere since database uses UUID.

---

## 🎓 Key Takeaways

1. **Type hints are double-edged**: Catch bugs early BUT can cause runtime crashes if wrong
2. **Database schema is source of truth**: Always verify column types before writing type hints
3. **Fail-fast is good**: Clear TypeError better than silent wrong behavior
4. **Testing edge cases matters**: Alternative finder only triggered when slots unavailable
5. **Consistency across codebase**: One method with wrong types can break entire feature

**Status**: Bug fixed, system ready for re-test, Phase 1 PoC ready to continue ✅
