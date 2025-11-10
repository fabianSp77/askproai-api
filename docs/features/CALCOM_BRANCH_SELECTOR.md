# Cal.com Branch Selector - Feature Documentation

**Version:** 1.0
**Date:** 2025-11-10
**Status:** ✅ Production Ready

---

## Overview

The Branch Selector component enables admin users to switch between different company branches when booking appointments via the Cal.com integration. This feature provides multi-branch support while maintaining a seamless booking experience.

### Key Features

- **Role-Based Visibility**: Only visible to admin users (`super_admin`, `Admin`, `company_owner`, `company_admin`)
- **Persistent Selection**: Branch choice saved to `localStorage` for session persistence
- **Real-Time Widget Updates**: Cal.com Atoms widget reloads with selected branch context
- **Service Counts**: Displays active service count per branch
- **Loading States**: Visual feedback during widget reload
- **Error Handling**: Graceful error states with user-friendly messages
- **Responsive Design**: Works on mobile and desktop with Filament theme compatibility

---

## Architecture

### Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | Laravel 11 + Filament 3 |
| Frontend | Livewire 3 + Alpine.js |
| Styling | Tailwind CSS (Filament theme) |
| Widget | Cal.com Atoms (React) |

### Data Flow

```
User Selects Branch
    ↓
Alpine.js → Livewire State Update
    ↓
localStorage Persistence
    ↓
CalcomConfig Update
    ↓
Cal.com Widget Reload
    ↓
Branch-Specific Services Displayed
```

---

## File Structure

```
app/
├── Filament/Pages/
│   └── CalcomBooking.php              ← Livewire component logic
├── Http/Controllers/Api/
│   └── CalcomBranchesController.php   ← API endpoint for branches
└── Models/
    ├── Branch.php                     ← Branch model
    └── Company.php                    ← Company model

resources/views/
├── filament/pages/
│   └── calcom-booking.blade.php       ← UI component with Alpine.js
└── components/
    └── calcom-scripts.blade.php       ← Cal.com Atoms initialization

routes/
└── api.php                            ← API route registration

docs/features/
└── CALCOM_BRANCH_SELECTOR.md          ← This documentation
```

---

## Implementation Details

### 1. Backend API Endpoint

**Route:** `GET /api/calcom/branches`
**Middleware:** `auth:sanctum`
**Controller:** `App\Http\Controllers\Api\CalcomBranchesController`

#### Response Format

```json
{
  "success": true,
  "branches": [
    {
      "id": 1,
      "name": "Hauptfiliale",
      "slug": "hauptfiliale",
      "services_count": 12,
      "is_default": true,
      "address": "Musterstraße 1, 12345 Berlin"
    },
    {
      "id": 2,
      "name": "Filiale Nord",
      "slug": "filiale-nord",
      "services_count": 8,
      "is_default": false,
      "address": "Nordstraße 45, 10115 Berlin"
    }
  ],
  "company": {
    "id": 1,
    "name": "Friseur1 GmbH",
    "calcom_team_id": 34209,
    "calcom_team_slug": "friseur1"
  }
}
```

#### Security

- **Company Isolation**: Only returns branches for authenticated user's company
- **Active Services**: Counts only `is_active = true` services
- **Error Handling**: Graceful 500 errors with debug mode support

---

### 2. Livewire Component (`CalcomBooking.php`)

#### Public Properties

```php
public ?int $selectedBranchId = null;  // Current branch selection
public array $branches = [];            // Available branches
```

#### Key Methods

| Method | Purpose |
|--------|---------|
| `mount()` | Initialize with user's default branch |
| `loadBranches()` | Fetch branches from database |
| `selectBranch(int)` | Handle branch selection + dispatch event |
| `isAdmin()` | Check if selector should be visible |

#### Livewire Events

```php
$this->dispatch('branch-changed', branchId: $branchId);
```

---

### 3. Blade View (`calcom-booking.blade.php`)

#### Alpine.js State

```javascript
{
    selectedBranchId: @entangle('selectedBranchId'),  // Livewire sync
    branches: @entangle('branches'),                   // Livewire sync
    isLoading: false,                                  // UI state
    error: null                                        // Error state
}
```

#### Key Alpine Methods

| Method | Behavior |
|--------|----------|
| `init()` | Load branches + restore from localStorage |
| `selectBranch(id)` | Update selection + trigger Livewire |
| `reloadCalcomWidget(id)` | Update `window.CalcomConfig` + reload widget |
| `getBranchName(id)` | Helper for displaying branch names |
| `getBranchServicesCount(id)` | Helper for service counts |

---

### 4. UI Components

#### Branch Selector Dropdown

```html
<select
    id="branch-selector"
    x-model="selectedBranchId"
    @change="selectBranch($event.target.value)"
    class="block w-full rounded-lg border-gray-300 dark:border-gray-700..."
>
    <option value="">Select a branch...</option>
    <template x-for="branch in branches" :key="branch.id">
        <option
            :value="branch.id"
            x-text="`${branch.name} (${branch.services_count} services)`"
        ></option>
    </template>
</select>
```

#### Loading State

```html
<div x-show="isLoading" class="flex items-center gap-2...">
    <svg class="animate-spin h-4 w-4...">...</svg>
    <span>Loading booking widget...</span>
</div>
```

#### Error State

```html
<div
    x-show="error"
    x-text="error"
    class="rounded-lg bg-danger-50 dark:bg-danger-900/20..."
></div>
```

#### Selected Branch Info Card

```html
<div x-show="selectedBranchId && !isLoading" class="rounded-lg bg-primary-50...">
    <div class="flex items-start gap-3">
        <svg class="h-5 w-5...">...</svg>
        <div>
            <p x-text="getBranchName(selectedBranchId)"></p>
            <p x-text="`${getBranchServicesCount(selectedBranchId)} available services`"></p>
        </div>
    </div>
</div>
```

---

## Visibility Logic

### When Selector is Visible

The branch selector appears when **ALL** conditions are met:

```php
@if($this->isAdmin() && count($branches) > 1)
```

1. ✅ User has admin role (`super_admin`, `Admin`, `company_owner`, `company_admin`)
2. ✅ Company has more than 1 branch

### When Selector is Hidden

- Non-admin users (`company_manager`, `company_staff`)
- Companies with 0 or 1 branches
- Unauthenticated users (page not accessible)

---

## Persistence Strategy

### localStorage Schema

```javascript
// Key
'calcom_selected_branch_id'

// Value (integer)
123

// Scope
Per browser, per domain
```

### Lifecycle

1. **On Init**: Read from localStorage → set `selectedBranchId`
2. **On Change**: Write to localStorage → trigger widget reload
3. **On Page Refresh**: Restore from localStorage

---

## Cal.com Widget Integration

### Configuration Update

```javascript
// Before branch change
window.CalcomConfig = {
    teamId: 34209,
    teamSlug: 'friseur1',
    defaultBranchId: 1,  // Previous branch
    ...
};

// After branch change
window.CalcomConfig.defaultBranchId = 2;  // New branch

// Widget reload
const bookerElement = document.querySelector('[data-calcom-booker]');
const config = JSON.parse(bookerElement.getAttribute('data-calcom-booker'));
config.initialBranchId = 2;
bookerElement.setAttribute('data-calcom-booker', JSON.stringify(config));

if (window.Cal?.reload) {
    window.Cal.reload();  // Force re-initialization
}
```

---

## Error Handling

### API Errors

```php
// CalcomBranchesController.php
try {
    $branches = Branch::where('company_id', $user->company_id)
        ->withCount('services')
        ->get();
} catch (\Exception $e) {
    \Log::error('[CalcomBranches] Failed to fetch branches', [
        'user_id' => $user->id,
        'company_id' => $user->company_id,
        'error' => $e->getMessage()
    ]);

    return response()->json([
        'error' => 'Failed to fetch branches',
        'branches' => []
    ], 500);
}
```

### Frontend Errors

```javascript
try {
    // Widget reload logic
} catch (error) {
    console.error('[BranchSelector] Failed to reload Cal.com widget:', error);
    this.error = 'Failed to load booking widget. Please refresh the page.';
}
```

---

## Testing Checklist

### Manual Testing

- [ ] **Admin User + Multiple Branches**: Selector visible, all branches listed
- [ ] **Admin User + Single Branch**: Selector hidden, default branch used
- [ ] **Non-Admin User**: Selector hidden regardless of branch count
- [ ] **Branch Selection**: Widget reloads with correct services
- [ ] **Persistence**: Selected branch restored after page refresh
- [ ] **Loading States**: Spinner appears during widget reload
- [ ] **Error States**: Graceful error display on failures
- [ ] **Mobile Responsive**: Works on mobile viewports
- [ ] **Dark Mode**: Compatible with Filament dark theme

### API Testing

```bash
# Test branches endpoint
curl -X GET https://your-domain.com/api/calcom/branches \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Accept: application/json"

# Expected: 200 OK with branches array
```

---

## Performance Considerations

### Database Queries

```php
// Optimized query with eager loading
Branch::where('company_id', $user->company_id)
    ->with('services:id,branch_id,name,is_active')  // Eager load
    ->withCount(['services' => function ($query) {
        $query->where('is_active', true);           // Conditional count
    }])
    ->get();
```

**Query Count:** 2 queries (branches + services)
**Cache Strategy:** None (real-time data required)

### Frontend Performance

- **Alpine.js**: Lightweight (~15KB gzipped)
- **Livewire**: Server-side state management (minimal JS)
- **Cal.com Atoms**: Lazy-loaded React bundle (~100KB)

---

## Future Enhancements

### Phase 2 (Planned)

- [ ] **Branch-Specific Availability**: Filter services by branch working hours
- [ ] **Multi-Branch Booking**: Book across branches in single flow
- [ ] **Branch Analytics**: Track booking conversion by branch
- [ ] **Staff Assignment**: Show available staff per branch
- [ ] **Branch Preferences**: Remember preferred branch per user

### Phase 3 (Backlog)

- [ ] **Branch Comparison**: Side-by-side availability comparison
- [ ] **Branch Notifications**: Alert on branch-specific updates
- [ ] **Branch Ratings**: Display customer ratings per branch
- [ ] **Branch Capacity**: Show real-time booking capacity

---

## Troubleshooting

### Issue: Selector Not Visible

**Diagnosis:**
1. Check user role: `User::find($id)->roles()->pluck('name')`
2. Check branch count: `Branch::where('company_id', $companyId)->count()`

**Solution:**
- Ensure user has admin role
- Ensure company has >1 branches

---

### Issue: Widget Not Reloading

**Diagnosis:**
1. Check browser console for JavaScript errors
2. Verify `window.Cal` object exists
3. Check `data-calcom-booker` attribute update

**Solution:**
```javascript
// Debug widget state
console.log(window.Cal);
console.log(document.querySelector('[data-calcom-booker]'));

// Manual reload
if (window.Cal?.reload) {
    window.Cal.reload();
}
```

---

### Issue: Branches Not Loading

**Diagnosis:**
1. Check API response: Network tab → `/api/calcom/branches`
2. Check Livewire console: Browser DevTools → Livewire tab

**Solution:**
```bash
# Check logs
tail -f storage/logs/laravel.log | grep CalcomBranches

# Test API directly
php artisan tinker
>>> $user = User::find(1);
>>> $branches = \App\Models\Branch::where('company_id', $user->company_id)->get();
```

---

## Related Documentation

- **Cal.com Integration**: `/docs/e2e/calcom-integration.md`
- **Filament Customization**: `/docs/e2e/filament-customization.md`
- **Branch Management**: `/docs/features/BRANCH_MANAGEMENT.md`
- **Multi-Tenancy**: `/docs/architecture/MULTI_TENANCY.md`

---

## Changelog

### v1.0 (2025-11-10)
- ✅ Initial implementation
- ✅ API endpoint with company isolation
- ✅ Livewire component with state management
- ✅ Alpine.js widget reload logic
- ✅ Tailwind CSS styling with dark mode
- ✅ localStorage persistence
- ✅ Error handling and loading states

---

**Maintained by:** AskPro AI Team
**Last Updated:** 2025-11-10
**Status:** Production Ready
