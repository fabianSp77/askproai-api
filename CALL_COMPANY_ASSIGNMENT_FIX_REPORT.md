# Call Company Assignment Fix Report

## Issue Date
2025-07-03

## GitHub Issue
#262 - "die daten sind nicht korrekt"

## Problem Description
The analytics page was showing incorrect data for companies. Investigation revealed that calls were assigned to the wrong company_id, causing data to appear in the wrong company's analytics.

## Root Cause
Calls were being assigned company_id without verifying which company actually owns the phone number in the `to_number` field. This resulted in:
- 144 calls to phone number `+493083793369` (owned by AskProAI, Company ID 15) were incorrectly assigned to Krückeberg (Company ID 1)
- When Krückeberg viewed their analytics, they saw no data because their actual phone number `+493033081738` had no calls

## Impact
1. **Data Integrity**: 144 out of 145 calls (99.3%) had incorrect company assignments
2. **Analytics Accuracy**: Companies were either seeing no data or incorrect data
3. **Multi-tenant Security**: Companies could potentially see calls that belonged to other companies

## Solution Implemented

### 1. Analysis Script
Created `check-analytics-data.php` to identify the issue:
- Verified phone number ownership
- Checked call assignments
- Identified mismatches between to_number and company_id

### 2. Fix Script
Created `fix-call-company-assignment.php` that:
- Analyzed all calls in the system
- Identified calls where company_id didn't match the company that owns the to_number
- Updated 144 mismatched calls to have the correct company_id and branch_id

### 3. Data Correction Results
- **Before**: 144 calls with incorrect company_id
- **After**: 0 calls with incorrect company_id
- **Verification**: All calls now correctly assigned to the company that owns the phone number

## Code Changes
No code changes were required. The phone number filtering implementation was working correctly. The issue was purely data integrity in the database.

## Prevention Measures

### 1. Immediate Action Needed
Add validation when creating/importing calls to ensure company_id matches the phone number owner:

```php
// In Call model or import service
$phoneNumber = PhoneNumber::where('number', $to_number)->first();
if ($phoneNumber) {
    $call->company_id = $phoneNumber->company_id;
    $call->branch_id = $phoneNumber->branch_id;
}
```

### 2. Database Constraint (Recommended)
Consider adding a database trigger or constraint to ensure data integrity:

```sql
-- Example trigger to auto-set company_id based on to_number
CREATE TRIGGER set_call_company_id
BEFORE INSERT ON calls
FOR EACH ROW
BEGIN
    DECLARE comp_id INT;
    DECLARE br_id VARCHAR(36);
    
    SELECT company_id, branch_id INTO comp_id, br_id
    FROM phone_numbers
    WHERE number = NEW.to_number
    AND is_active = 1
    LIMIT 1;
    
    IF comp_id IS NOT NULL THEN
        SET NEW.company_id = comp_id;
        SET NEW.branch_id = br_id;
    END IF;
END;
```

### 3. Regular Data Integrity Checks
Schedule a regular job to check for and report data mismatches:

```php
php artisan schedule:add "0 */6 * * * php /var/www/api-gateway/check-call-company-assignment.php"
```

## Lessons Learned
1. **Trust but Verify**: Even with proper filtering in place, data integrity issues can cause incorrect results
2. **Root Cause Analysis**: The reported "incorrect data" wasn't a bug in the display logic but corrupted data
3. **Data Import Validation**: When importing calls (especially from Retell.ai webhooks), always verify company ownership of phone numbers

## Files Created
1. `/var/www/api-gateway/check-analytics-data.php` - Analysis script
2. `/var/www/api-gateway/check-all-calls.php` - Call investigation script
3. `/var/www/api-gateway/check-phone-numbers.php` - Phone number verification script
4. `/var/www/api-gateway/fix-call-company-assignment.php` - Fix script

## Follow-up Actions
1. ✅ Fixed existing data (144 calls corrected)
2. ⚠️ Need to fix the import process to prevent future occurrences
3. ⚠️ Consider adding database constraints for data integrity
4. ⚠️ Add monitoring for data consistency