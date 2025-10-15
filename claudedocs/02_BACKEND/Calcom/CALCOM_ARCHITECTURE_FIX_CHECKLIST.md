# Cal.com Architecture Fix Checklist

**Date**: 2025-10-14
**Issue**: branches.calcom_event_type_id was incorrectly added back
**Status**: IMMEDIATE ACTION REQUIRED

---

## Problem Summary

Migration `2025_10_14_add_calcom_event_type_id_to_branches.php` added the field `calcom_event_type_id` to the `branches` table, but this is **architecturally wrong** and conflicts with a previous migration that **intentionally removed** this field.

---

## Root Cause

**Misunderstanding of Cal.com Team Architecture**:
- Assumed: Branch has ONE event type
- Reality: Branch has MULTIPLE services (each with own event type)

---

## Impact Assessment

### Current State
```bash
# Check if field exists in database
mysql -u askproai_user -p'askproai_secure_pass_2024' askproai_db \
  -e "DESCRIBE branches" | grep calcom_event_type_id
```

**If output shows the field**: Migration was run, needs rollback
**If no output**: Migration not yet run, just delete the file

### Affected Components
- ❌ Branch model ($fillable array has the field)
- ❌ Migration file exists and may have run
- ⚠️ Settings Dashboard (if built with this field)
- ⚠️ Any code using $branch->calcom_event_type_id

---

## Fix Actions

### Step 1: Check Migration Status

```bash
# Check if migration has been run
php artisan migrate:status | grep "add_calcom_event_type_id_to_branches"
```

**Output: "Ran"** → Go to Step 2 (Rollback Required)
**Output: "Pending"** → Go to Step 3 (Delete Migration)

---

### Step 2: Rollback Migration (If Already Run)

#### 2A: Create Rollback Migration

```bash
cd /var/www/api-gateway
php artisan make:migration remove_calcom_event_type_id_from_branches_final --table=branches
```

#### 2B: Edit the New Migration

File: `database/migrations/YYYY_MM_DD_HHMMSS_remove_calcom_event_type_id_from_branches_final.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove calcom_event_type_id from branches table (architectural fix)
     *
     * This field was incorrectly added in 2025_10_14 migration.
     * It conflicts with the correct architecture where:
     * - Companies have team_id (one team)
     * - Services have event_type_id (multiple event types per team)
     * - Branches link to services via branch_service pivot (many-to-many)
     *
     * See: claudedocs/CALCOM_TEAM_ARCHITECTURE_ANALYSIS_2025-10-14.md
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'calcom_event_type_id')) {
                // Drop index first
                $table->dropIndex(['calcom_event_type_id']);

                // Drop column
                $table->dropColumn('calcom_event_type_id');
            }
        });
    }

    /**
     * Reverse the migrations (should not be used - this is the correct state)
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('calcom_event_type_id')->nullable()->after('retell_agent_id');
            $table->index('calcom_event_type_id');
        });
    }
};
```

#### 2C: Run the Rollback Migration

```bash
php artisan migrate
```

#### 2D: Verify Removal

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' askproai_db \
  -e "DESCRIBE branches" | grep calcom_event_type_id
```

**Expected**: No output (field removed)

---

### Step 3: Delete Bad Migration File

```bash
cd /var/www/api-gateway
rm database/migrations/2025_10_14_add_calcom_event_type_id_to_branches.php
```

**Confirm deletion**:
```bash
ls -la database/migrations/ | grep add_calcom_event_type_id_to_branches
```

**Expected**: No output (file deleted)

---

### Step 4: Update Branch Model

File: `/var/www/api-gateway/app/Models/Branch.php`

#### 4A: Remove from $fillable Array

**Before**:
```php
protected $fillable = [
    'company_id', 'customer_id', 'name', 'slug', 'city', 'phone_number',
    // ... other fields ...
    'calcom_event_type_id',  // ← REMOVE THIS LINE
    'calcom_api_key', 'retell_agent_id',
    // ... rest of fields ...
];
```

**After**:
```php
protected $fillable = [
    'company_id', 'customer_id', 'name', 'slug', 'city', 'phone_number',
    // ... other fields ...
    'calcom_api_key', 'retell_agent_id',  // calcom_event_type_id REMOVED
    // ... rest of fields ...
];
```

#### 4B: Verify No Other References

```bash
cd /var/www/api-gateway
grep -r "branches.*calcom_event_type_id" app/ --include="*.php"
```

**Expected**: No output (no code uses this field)

---

### Step 5: Verify Correct Architecture

#### 5A: Check Company Structure

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' askproai_db -e "
SELECT id, name, calcom_team_id
FROM companies
WHERE calcom_team_id IS NOT NULL
LIMIT 5;
"
```

**Expected**: Companies have team_id

#### 5B: Check Services Structure

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' askproai_db -e "
SELECT id, company_id, name, calcom_event_type_id
FROM services
WHERE calcom_event_type_id IS NOT NULL
LIMIT 10;
"
```

**Expected**: Services have unique event_type_id

#### 5C: Check Branch-Service Pivot

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' askproai_db -e "
SELECT b.name AS branch_name, s.name AS service_name,
       bs.is_active, s.calcom_event_type_id
FROM branch_service bs
JOIN branches b ON bs.branch_id = b.id
JOIN services s ON bs.service_id = s.id
LIMIT 10;
"
```

**Expected**: Many-to-many relationships with service event_type_ids

---

### Step 6: Document Architecture (Already Done)

✅ Created comprehensive documentation:
- `/var/www/api-gateway/claudedocs/CALCOM_TEAM_ARCHITECTURE_ANALYSIS_2025-10-14.md`
- `/var/www/api-gateway/claudedocs/CALCOM_ARCHITECTURE_VISUAL_2025-10-14.txt`
- `/var/www/api-gateway/claudedocs/CALCOM_ARCHITECTURE_FIX_CHECKLIST.md` (this file)

---

## Verification Tests

### Test 1: Appointment Booking Flow

```php
// This should work WITHOUT branch event_type_id
$service = Service::where('company_id', 15)
    ->where('calcom_event_type_id', 2031135)
    ->first();

$appointment = Appointment::create([
    'company_id' => 15,
    'branch_id' => '9f4d5e2a-46f7-41b6-b81d-1532725381d4',
    'service_id' => $service->id,  // Uses service.calcom_event_type_id
    'customer_id' => $customerId,
    'start_time' => '2025-10-15 14:00:00',
]);
```

### Test 2: Branch Services Query

```php
// Get all active services for a branch
$branch = Branch::find('9f4d5e2a-46f7-41b6-b81d-1532725381d4');
$activeServices = $branch->activeServices()->get();

foreach ($activeServices as $service) {
    echo "Service: {$service->name}\n";
    echo "Event Type: {$service->calcom_event_type_id}\n";
    echo "Active: {$service->pivot->is_active}\n";
    echo "---\n";
}
```

### Test 3: Company Team Validation

```php
// Validate that a service belongs to company's team
$company = Company::find(15);
$isValid = $company->ownsService(2031135);  // true
$isValid = $company->ownsService(9999999);  // false
```

---

## Settings Dashboard Guidelines

### Company Settings Tab

**DO**:
```php
// Show company team_id
$company->calcom_team_id  // 39203

// Show company's services (with event_type_ids)
$company->services()->whereNotNull('calcom_event_type_id')->get()
```

**DON'T**:
```php
// Try to get event_type_id from branch
$branch->calcom_event_type_id  // ❌ WRONG - doesn't exist
```

### Branch Configuration Tab

**DO**:
```php
// Show branch's active services
$branch->activeServices()->get()

// Show service event_type_id from service table
foreach ($branch->services as $service) {
    echo $service->calcom_event_type_id;  // From services table
}
```

**DON'T**:
```php
// Try to get event_type_id from branch
$branch->calcom_event_type_id  // ❌ WRONG
```

### Service Assignment UI

**Correct Flow**:
1. Show all company services (from services table)
2. For each service, show which branches have it active
3. Allow toggling is_active in branch_service pivot
4. Allow overriding duration/price per branch

**Example UI**:
```
Service: Herrenhaarschnitt (Event Type: 2031135)
├─ Branch A: ✅ Active (duration: 45 min, price: €25)
├─ Branch B: ✅ Active (duration: 60 min override, price: €30 override)
└─ Branch C: ❌ Inactive
```

---

## Success Criteria

### Database State
- ✅ branches table has NO calcom_event_type_id column
- ✅ services table has calcom_event_type_id (UNIQUE)
- ✅ companies table has calcom_team_id
- ✅ branch_service pivot table exists with is_active

### Model State
- ✅ Branch model $fillable has NO calcom_event_type_id
- ✅ Service model has calcom_event_type_id
- ✅ Company model has calcom_team_id

### Code State
- ✅ No code references branch->calcom_event_type_id
- ✅ Appointment booking uses service->calcom_event_type_id
- ✅ Availability checks use service->calcom_event_type_id

### Documentation State
- ✅ Architecture documented in claudedocs/
- ✅ Visual diagrams created
- ✅ Fix checklist available (this file)

---

## Timeline Estimate

| Task | Estimated Time |
|------|----------------|
| Step 1: Check migration status | 2 minutes |
| Step 2: Create & run rollback migration | 10 minutes |
| Step 3: Delete bad migration file | 1 minute |
| Step 4: Update Branch model | 5 minutes |
| Step 5: Verify architecture | 5 minutes |
| Step 6: Run verification tests | 10 minutes |
| **Total** | **~30 minutes** |

---

## Rollback Plan (If Something Goes Wrong)

### Emergency Rollback

If the fix breaks something:

1. **Restore field temporarily**:
```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' askproai_db -e "
ALTER TABLE branches
ADD COLUMN calcom_event_type_id VARCHAR(255) NULL AFTER retell_agent_id;
"
```

2. **Restore model**:
```php
// Add back to $fillable temporarily
'calcom_event_type_id',
```

3. **Investigate what broke**:
```bash
grep -r "calcom_event_type_id" app/ --include="*.php" -B2 -A2
```

4. **Fix the dependent code first**, then re-apply the fix

---

## Related Issues & Context

### Historical Context
- **2025-09-29**: Migration intentionally REMOVED branches.calcom_event_type_id
- **2025-10-14**: Field incorrectly added back (this issue)
- **Reason for removal**: Architectural decision - branches should link to services, not event types

### Related Migrations
1. `2025_09_24_123318_create_branch_service_table.php` - Created pivot table
2. `2025_09_29_fix_calcom_event_ownership.php` - Removed branch event_type_id
3. `2025_10_14_add_calcom_event_type_id_to_branches.php` - ❌ BAD - Re-added field

### Related Documentation
- `/var/www/api-gateway/claudedocs/CALCOM_TEAM_ARCHITECTURE_ANALYSIS_2025-10-14.md`
- `/var/www/api-gateway/claudedocs/CALCOM_ARCHITECTURE_VISUAL_2025-10-14.txt`
- `/var/www/api-gateway/app/Services/CalcomV2Service.php`
- `/var/www/api-gateway/app/Models/TeamEventTypeMapping.php`

---

## Questions & Answers

### Q: Why can't branches have event_type_id?
**A**: Because branches can host MULTIPLE services, each with their own event_type_id. The many-to-many relationship is handled via the `branch_service` pivot table.

### Q: What if a branch only offers ONE service?
**A**: Still use branch_service pivot with one active relationship. This maintains architectural consistency and allows future expansion.

### Q: What is companies.calcom_event_type_id for?
**A**: Legacy/default event type for the whole company. Optional field from before multi-service architecture was implemented.

### Q: How do I know which services a branch offers?
**A**: Query the `branch_service` pivot table WHERE `is_active = 1`.

### Q: Can two branches have the same service?
**A**: YES! That's the point of many-to-many. Same service (same event_type_id) can be active in multiple branches with different overrides.

---

## Final Checklist

Before considering this issue resolved:

- [ ] Migration status checked
- [ ] Bad migration file deleted OR rollback migration created
- [ ] Branch model updated (field removed from $fillable)
- [ ] Database verified (field not in branches table)
- [ ] No code references branch->calcom_event_type_id
- [ ] Appointment booking tested
- [ ] Branch services query tested
- [ ] Team validation tested
- [ ] Documentation reviewed
- [ ] Settings Dashboard updated (if already built)

**Sign-off**: This checklist should be completed before proceeding with Settings Dashboard development.

---

**Next Steps**: Build Settings Dashboard using CORRECT architecture as documented.
