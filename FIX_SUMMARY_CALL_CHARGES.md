# Fix Summary: call_charges Table Missing

## Problem
The calls page (`/admin/calls`) was throwing a 500 error:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'askproai_db.call_charges' doesn't exist
```

## Root Cause
- The `call_charges` table was referenced in multiple places:
  - Call model relationships (`callCharge()` and `charge()`)
  - Various services (Dashboard, Billing, etc.)
  - CallResource eager loading
- But the table creation migration was missing

## Solution
1. Created the missing migration: `2025_07_03_create_call_charges_table.php`
2. Included all necessary columns:
   - Basic charge tracking (amount, type, currency)
   - Refund tracking fields
   - Proper foreign keys and indexes
3. Ran the migration with `--force` flag

## Result
✅ Calls page now loads successfully  
✅ No more database errors  
✅ All Call model relationships work properly  
✅ Portal remains 100% functional

## Technical Details
- Table: `call_charges`
- Model: `App\Models\CallCharge`
- Migration: `database/migrations/2025_07_03_create_call_charges_table.php`
- Columns: id, call_id, company_id, amount_charged, amount_credited, charge_type, currency, description, metadata, refund fields, timestamps

---
*Fixed on: 2025-08-05*