# Multi-Company Staff Architecture

**Datum**: 2025-10-21
**Status**: ⚠️ Currently Single-Company, Should Be Many-to-Many
**Priority**: Medium (Design Debt)

---

## 📊 Current State (Problem)

```
Staff Model:
  - company_id (FK) ← Single Company
  - branch_id (FK) ← Single Branch

Result:
  ❌ Staff kann nur zu einer Firma gehören
  ❌ Staff kann nicht in mehreren Filialen arbeiten
  ❌ Cross-Company Assignments verletzen Multi-Tenancy
```

---

## 🎯 Desired State (Solution)

```
Staff (1) ←→ (Many) Company
  via staff_company Pivot

Staff (1) ←→ (Many) Branch
  via staff_branch Pivot (optional)

Result:
  ✅ Staff kann in mehreren Firmen arbeiten
  ✅ Staff kann in mehreren Filialen arbeiten
  ✅ Primäre Company für Authentifizierung/Dateneignerschaft
```

---

## 🔧 Implementation Roadmap

### Phase 1: Create Pivot Tables
```bash
php artisan make:migration create_staff_company_table
php artisan make:migration create_staff_branch_table
```

**staff_company Table:**
```sql
- id (primary)
- staff_id (uuid, FK → staff)
- company_id (FK → companies)
- is_primary (boolean) - Primary company for auth
- is_active (boolean)
- timestamps
- unique(staff_id, company_id)
```

**staff_branch Table:**
```sql
- id (primary)
- staff_id (uuid, FK → staff)
- branch_id (uuid, FK → branches)
- is_primary (boolean)
- is_active (boolean)
- timestamps
- unique(staff_id, branch_id)
```

### Phase 2: Update Models

```php
// Staff Model
class Staff extends Model {
    // Keep for backward compatibility during migration
    // but transition to pivot-based queries
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'staff_company')
            ->withPivot(['is_primary', 'is_active'])
            ->withTimestamps();
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'staff_branch')
            ->withPivot(['is_primary', 'is_active'])
            ->withTimestamps();
    }

    // Helper: Get primary company
    public function getPrimaryCompany()
    {
        return $this->companies()
            ->wherePivot('is_primary', true)
            ->first() ?? $this->companies()->first();
    }
}

// Company Model
class Company extends Model {
    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'staff_company')
            ->withPivot(['is_primary', 'is_active'])
            ->withTimestamps();
    }
}

// Branch Model
class Branch extends Model {
    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'staff_branch')
            ->withPivot(['is_primary', 'is_active'])
            ->withTimestamps();
    }
}
```

### Phase 3: Update Queries

**Before:**
```php
Staff::where('company_id', $companyId)->get()
```

**After:**
```php
Staff::whereHas('companies', function($q) use ($companyId) {
    $q->where('company_id', $companyId)
      ->wherePivot('is_active', true);
})->get()
```

### Phase 4: Migrate Existing Data

```php
// In migration
foreach (Staff::all() as $staff) {
    if ($staff->company_id) {
        $staff->companies()->attach($staff->company_id, [
            'is_primary' => true,
            'is_active' => true,
        ]);
    }
}

// Then optional: Remove old company_id column
Schema::table('staff', function (Blueprint $table) {
    $table->dropColumn(['company_id', 'branch_id']);
});
```

---

## 🚀 Temporary Workaround (Current)

**File**: `app/Filament/Resources/ServiceResource.php`

```php
// Remove company_id filtering to allow cross-company staff
->options(function () {
    return Staff::where('is_active', true)
        ->orderBy('name')
        ->pluck('name', 'id');
})
```

**Note**: This bypasses multi-tenant checks temporarily but allows cross-company staff assignment.

---

## ⚠️ Risks & Considerations

### Current Risks (Single-Company)
```
✅ Strong Multi-Tenancy Isolation
❌ Inflexible for real-world scenarios
❌ Duplicate staff records needed
❌ No cross-company staff support
```

### Post-Migration Risks (Many-to-Many)
```
✅ Flexible Multi-Company Support
✅ No Duplicate Staff Records
⚠️ Need careful RLS (Row Level Security) implementation
⚠️ Need explicit company filtering in queries
⚠️ Primary company concept critical
```

### Migration Strategy
```
1. Add pivot tables alongside existing columns
2. Backfill pivot tables from existing data
3. Update queries gradually (scope-by-scope)
4. Remove old columns only after validation
5. Add comprehensive tests
```

---

## 📋 Affected Areas

When implementing Many-to-Many Staff-Company:

1. **Staff Queries**
   - Staff listing by company
   - Staff filtering in Services
   - Staff filtering in Appointments
   - Staff filtering in Callings

2. **Multi-Tenancy**
   - CompanyScope middleware may need updates
   - Staff scoping in queries
   - RLS checks for staff records

3. **Filament Resources**
   - Staff Resource (List/Create/Edit)
   - Service Resource (Staff Assignment)
   - Appointment Resource (Staff Selection)
   - All staff select dropdowns

4. **APIs**
   - Staff endpoints
   - Assignment endpoints
   - Availability endpoints

5. **Jobs & Services**
   - Cal.com sync (staff matching)
   - Appointment assignment
   - Availability checks
   - Booking confirmations

---

## 🎯 Business Logic Examples

### Example 1: Staff Works in Multiple Companies

```
Fabian Spitzer:
  ├─ Company 1 (Demo) - Primary
  ├─ Company 15 (AskProAI) - Secondary
  └─ Company 20 (Other) - Secondary

Service 47 (Company 15) can now assign Fabian ✅
Service 1 (Company 1) can also assign Fabian ✅
Service 100 (Company 20) can also assign Fabian ✅
```

### Example 2: Availability Across Companies

```
Check availability for Fabian on 2025-11-01:

1. Get all companies Fabian works for
2. Get Cal.com calendars for each company
3. Merge availability
4. Show consolidated slots
```

### Example 3: Appointments Across Companies

```
Fabian booked for Service 47 (Company 15)
  ├─ knows Fabian also works for Company 1
  ├─ can check: is availability conflicting with Company 1?
  ├─ can prevent double-booking across companies
  └─ can sync to all Cal.com accounts
```

---

## 📊 Data Model Comparison

### Current (Single-Company)
```
Staff Table:
  - id (uuid)
  - company_id (FK) ← Single
  - branch_id (FK) ← Single
  - name, email, ...

Service_Staff Table:
  - service_id (FK)
  - staff_id (FK)
  - pivot data (is_primary, can_book, ...)
```

### Proposed (Many-to-Many)
```
Staff Table:
  - id (uuid)
  - name, email, ... (no company_id!)

Staff_Company Table (NEW):
  - staff_id (FK)
  - company_id (FK)
  - is_primary
  - is_active

Staff_Branch Table (NEW):
  - staff_id (FK)
  - branch_id (FK)
  - is_primary
  - is_active

Service_Staff Table:
  - service_id (FK)
  - staff_id (FK)
  - (same pivot data)
```

---

## 🧪 Testing Strategy

After implementation:

```php
// Test 1: Staff in multiple companies
$staff = Staff::find($staffId);
$companies = $staff->companies()->pluck('id');
$this->assertCount(3, $companies);

// Test 2: Company can access their staff
$company = Company::find($companyId);
$staff = $company->staff()->where('id', $staffId)->exists();
$this->assertTrue($staff);

// Test 3: Service can assign cross-company staff
$service->staff()->attach($staffId, [...]);
$this->assertTrue($service->staff()->where('id', $staffId)->exists());

// Test 4: Availability checks both companies
$availability = $availabilityService->check($staffId, $date);
$this->assertIsArray($availability);

// Test 5: Multi-tenancy still works
$otherCompany = Company::where('id', '!=', $companyId)->first();
$staffInOther = $otherCompany->staff()->where('id', $staffId)->exists();
$this->assertTrue($staffInOther); // Staff accessible from both
```

---

## 📌 Decision Points

**Question 1**: Keep old company_id column?
- Option A: Keep for backward compatibility (safer)
- Option B: Remove completely after migration (cleaner)
- **Recommendation**: Option A for now, remove later

**Question 2**: Make branch_id Many-to-Many too?
- Option A: Yes, allow staff in multiple branches
- Option B: No, keep single branch (simpler)
- **Recommendation**: Option B for now, add later if needed

**Question 3**: Primary company importance?
- Option A: Critical - used for auth, RLS, defaults
- Option B: Optional - just for preference
- **Recommendation**: Option A - critical for data ownership

---

## 🚀 Implementation Timeline

| Phase | Effort | Risk | Timeline |
|-------|--------|------|----------|
| Planning (current) | 1d | Low | Done |
| Migrations & Models | 2d | Low | v2.1 |
| Query Updates | 3d | Medium | v2.2 |
| Testing | 2d | High | v2.2 |
| Production Rollout | 1d | High | v2.2 |

**Total**: ~1-2 weeks

---

## 📚 References

- Staff Model: `app/Models/Staff.php`
- Company Model: `app/Models/Company.php`
- Service Resource: `app/Filament/Resources/ServiceResource.php`
- Multi-Tenancy: `config/companyscope.php`

---

**Status**: Ready for Planning → Design → Implementation
**Owner**: Backend Team
**Stakeholder**: Operations (for cross-company staff workflows)
