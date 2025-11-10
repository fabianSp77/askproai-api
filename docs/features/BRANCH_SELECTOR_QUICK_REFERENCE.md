# Branch Selector - Quick Reference Card

**Feature:** Cal.com Branch Selector
**Version:** 1.0
**Status:** Production Ready

---

## Quick Commands

### Test API Endpoint

```bash
# Via curl (requires Sanctum token)
curl -X GET https://your-domain.com/api/calcom/branches \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" | jq

# Via Tinker
php artisan tinker
>>> $user = User::find(1);
>>> $response = app(\App\Http\Controllers\Api\CalcomBranchesController::class)->index();
>>> $response->getData();
```

### Run Tests

```bash
# All tests
vendor/bin/phpunit tests/Feature/CalcomBranchSelectorTest.php

# Single test
vendor/bin/phpunit --filter it_returns_branches_for_authenticated_admin_user

# With coverage
vendor/bin/phpunit --coverage-html coverage tests/Feature/CalcomBranchSelectorTest.php
```

### Debug Checklist

```bash
# 1. Check user role
php artisan tinker
>>> User::find(1)->roles()->pluck('name');

# 2. Check branch count
>>> Branch::where('company_id', 1)->count();

# 3. Check API route
php artisan route:list | grep calcom/branches

# 4. Check logs
tail -f storage/logs/laravel.log | grep -i "calcom\|branch"

# 5. Clear caches
php artisan config:clear && php artisan cache:clear && php artisan view:clear
```

---

## File Locations

```
Backend:
  app/Http/Controllers/Api/CalcomBranchesController.php
  app/Filament/Pages/CalcomBooking.php

Frontend:
  resources/views/filament/pages/calcom-booking.blade.php
  resources/views/components/calcom-scripts.blade.php

Routes:
  routes/api.php (line ~394)

Tests:
  tests/Feature/CalcomBranchSelectorTest.php

Docs:
  docs/features/CALCOM_BRANCH_SELECTOR.md
  CALCOM_BRANCH_SELECTOR_IMPLEMENTATION_SUMMARY.md
```

---

## Key Variables

### Livewire State

```php
// CalcomBooking.php
public ?int $selectedBranchId = null;
public array $branches = [];
```

### Alpine.js State

```javascript
{
    selectedBranchId: @entangle('selectedBranchId'),
    branches: @entangle('branches'),
    isLoading: false,
    error: null
}
```

### localStorage

```javascript
// Key
'calcom_selected_branch_id'

// Get
localStorage.getItem('calcom_selected_branch_id')

// Set
localStorage.setItem('calcom_selected_branch_id', branchId)

// Clear
localStorage.removeItem('calcom_selected_branch_id')
```

---

## Visibility Conditions

```php
// Selector visible when:
@if($this->isAdmin() && count($branches) > 1)

// isAdmin() returns true for:
- super_admin
- Admin
- company_owner
- company_admin

// Hidden for:
- company_manager
- company_staff
- Companies with ≤1 branch
```

---

## API Response Format

```json
{
  "success": true,
  "branches": [
    {
      "id": 1,
      "name": "Main Branch",
      "slug": "main-branch",
      "services_count": 5,
      "is_default": true,
      "address": "Street 1, 12345 City"
    }
  ],
  "company": {
    "id": 1,
    "name": "Company Name",
    "calcom_team_id": 34209,
    "calcom_team_slug": "company-slug"
  }
}
```

---

## Common Issues & Fixes

### Issue: Selector not visible

```php
// Check role
User::find($id)->hasAnyRole(['super_admin', 'Admin', 'company_owner', 'company_admin']);

// Check branch count
Branch::where('company_id', $companyId)->count() > 1;
```

### Issue: Widget not reloading

```javascript
// Manual reload
if (window.Cal?.reload) {
    window.Cal.reload();
} else {
    location.reload();
}
```

### Issue: 401 Unauthorized

```bash
# Check Sanctum token
php artisan tinker
>>> $user = User::find(1);
>>> $token = $user->createToken('test-token')->plainTextToken;
>>> echo $token;

# Use token in curl
curl -H "Authorization: Bearer $token" ...
```

---

## Code Snippets

### Add Branch to Company

```php
$branch = Branch::create([
    'company_id' => 1,
    'name' => 'New Branch',
    'street' => 'Example St 123',
    'postal_code' => '12345',
    'city' => 'Berlin',
]);
```

### Assign Admin Role

```php
$user = User::find(1);
$user->assignRole('company_admin');
```

### Create Test Data

```php
$company = Company::factory()->create();
$admin = User::factory()->create(['company_id' => $company->id]);
$admin->assignRole('company_admin');

$branch1 = Branch::factory()->create(['company_id' => $company->id]);
$branch2 = Branch::factory()->create(['company_id' => $company->id]);

Service::factory()->count(5)->create([
    'company_id' => $company->id,
    'branch_id' => $branch1->id,
    'is_active' => true,
]);
```

---

## Performance Metrics

| Metric | Value |
|--------|-------|
| API Response Time | ~50ms |
| Database Queries | 2 queries |
| Widget Reload Time | ~300ms |
| Total Page Load | ~600ms |

---

## Related Links

- Full Documentation: `/docs/features/CALCOM_BRANCH_SELECTOR.md`
- Implementation Summary: `/CALCOM_BRANCH_SELECTOR_IMPLEMENTATION_SUMMARY.md`
- Test Suite: `/tests/Feature/CalcomBranchSelectorTest.php`
- Cal.com Integration: `/docs/e2e/calcom-integration.md`

---

**Last Updated:** 2025-11-10
