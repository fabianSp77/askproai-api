# Branch Dropdown Fix Summary

## Issue
The branch dropdown in EventTypeSetupWizard is not populating after company selection.

## Root Cause Analysis
After extensive debugging, the issue appears to be related to Livewire's reactive state management in Filament forms. The branch dropdown's options are loaded via a callable that depends on the company_id field, but the dropdown doesn't refresh when company_id changes.

## What We've Confirmed Works
1. ✅ Database has correct data (5 active branches for company 85)
2. ✅ Query logic is correct (Branch::withoutGlobalScopes()->where('company_id', 85)->where('is_active', true))
3. ✅ User has company_id = 85
4. ✅ The options callable returns correct data when tested in isolation

## Current Implementation
```php
Select::make('branch_id')
    ->label('Filiale (Optional)')
    ->options(function (callable $get) {
        $companyId = $get('company_id');
        
        if (!$companyId) {
            return [];
        }
        
        return Branch::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');
    })
    ->placeholder('Alle Filialen')
    ->searchable()
    ->preload()
    ->live()
    ->visible(fn (callable $get) => filled($get('company_id')))
```

## Attempted Fixes
1. Added `->live()` to make it reactive
2. Added `->preload()` to load options immediately
3. Added `$this->dispatch('$refresh')` after company selection
4. Used `withoutGlobalScopes()` to bypass tenant filtering
5. Added extensive logging
6. Implemented `updated()` lifecycle hook
7. Cleared all caches

## Potential Solutions Not Yet Tried
1. **Use Alpine.js wire:init directive** to force refresh
2. **Implement custom Livewire component** instead of using Filament form
3. **Use JavaScript to manually trigger dropdown refresh**
4. **Downgrade to simpler non-reactive form**

## Recommended Next Steps
1. Check browser console for JavaScript errors when changing company
2. Enable Livewire debug mode to see component updates
3. Test in incognito mode to rule out browser cache
4. Consider using a different approach (like loading all branches initially and filtering client-side)

## Manual Testing Instructions
1. Go to `/admin/event-type-setup-wizard`
2. Open browser DevTools (F12)
3. Go to Network tab and filter by "livewire"
4. If company dropdown is enabled, change selection
5. Watch for Livewire update requests
6. Check Console for any JavaScript errors
7. Check if branch dropdown becomes visible and populated

## Current Status
The issue persists despite multiple attempts to fix it. The problem appears to be specific to how Filament handles dependent dropdowns in Livewire components. The data and queries are correct, but the UI doesn't update properly.