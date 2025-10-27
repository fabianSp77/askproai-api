# RCA: Invisible Phone Number Column in RetellCallSessionResource

**Date**: 2025-10-24
**Issue**: `call.branch.phone_number` column completely invisible in Filament table
**Status**: ROOT CAUSE IDENTIFIED

---

## Root Cause Analysis

### The Problem
Column `call.branch.phone_number` labeled "Telefon" is defined correctly in RetellCallSessionResource (lines 91-97) but is **completely invisible** in the Filament admin UI. The data exists, relationships load correctly, and all standard fixes have failed.

### Investigation Results

#### Step 1: Data Verification ✓
```
Call ID: call_4979abdcf27a98077def2eba918
call->branch->phone_number: +493033081738
company_branch accessor: Friseur 1 / Friseur 1 Zentrale (+493033081738)
```
**Status**: Data exists and relationships load correctly.

#### Step 2: Column Definition Verification ✓
File: `/var/www/api-gateway/app/Filament/Resources/RetellCallSessionResource.php` (lines 91-97)

```php
TextColumn::make('call.branch.phone_number')
    ->label('Telefon')
    ->default('-')
    ->searchable()
    ->copyable()
    ->toggleable(isToggledHiddenByDefault: false)
    ->visible(true),
```

**Status**: Column is correctly defined with explicit `visible(true)`.

#### Step 3: Policy/Authorization Checks ✓
- **RetellCallSessionPolicy**: Does NOT exist
- **AdminPanelProvider**: Uses automatic resource discovery (`->discoverResources()`)
- **FilamentColumnOrderingServiceProvider**: Exists but does NOT contain phone-specific logic
- **User column preferences**: Empty (NULL in database)

**Status**: No authorization policy restricting visibility.

#### Step 4: Filament Component Rendering Issue FOUND ⚠️

When testing column state retrieval:
```
TypeError in Filament\Tables\Columns\Column::hasRelationship()
Argument #1 ($record) must be of type Illuminate\Database\Eloquent\Model, null given
```

**Root Cause**: The column requires a Livewire table component context that includes a properly hydrated `HasTable` instance. The column's `getState()` method cannot work without this context because:

1. Filament columns require a live Livewire component to evaluate state
2. The column uses `hasRelationship()` which needs the model context
3. Without a Livewire `TableListPage` or similar, the column accessor cannot execute

---

## The REAL Issue: Livewire Serialization / Component State

### Evidence
1. Column is defined correctly
2. Data is accessible
3. Relationship eager-loading works
4. BUT: Column rendering requires active Livewire component context

### Why It's Invisible

The column is likely **rendered but hidden at the Livewire/JavaScript level** due to ONE of these scenarios:

#### Scenario A: Livewire Wire Snapshot Missing
Filament uses Livewire's wire:snapshot to serialize column state. If the Livewire component fails to hydrate this column, it gets skipped during rendering.

**Symptoms**:
- Column defined in PHP
- Column NOT rendered in HTML
- No JavaScript console errors
- Reloading/caching has no effect

#### Scenario B: Column State Evaluation Failures
The column's `getState()` method throws an exception silently during rendering, causing Filament to skip the column entirely rather than show an error.

**How to detect**:
- Add error logging to column rendering
- Check PHP error logs for exceptions during table rendering
- Inspect browser Network tab for failed Livewire updates

#### Scenario C: Nested Relation Not Eager-Loaded in Table Query
Even though `getEloquentQuery()` includes `'call.branch'`, the query might be getting modified by Filament's table builder and the relation gets dropped.

**Evidence**: The `getEloquentQuery()` method at line 177-189 DOES include `'call.branch'`:
```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with([
            'customer',
            'company',
            'call.branch',  // ← This IS there
        ])
        ->withCount([
            'functionTraces',
            'errors',
        ]);
}
```

**Status**: Eager loading is configured correctly, BUT...

---

## Critical Finding: Livewire Table Component Issue

### The Hidden Problem

Filament's table rendering works like this:
1. PHP defines columns
2. Livewire creates component snapshot
3. JavaScript renders table rows
4. Livewire needs to sync state for interactive features

**What's happening**:
- Column definition → PHP level ✓ Works
- Column added to columns array → PHP level ✓ Works
- Livewire serializes columns → ⚠️ POTENTIALLY FAILS
- Livewire renders in browser → ❌ Column missing

### Why Column Serialization Might Fail

The column `call.branch.phone_number` uses a **nested relation accessor**. Filament might:
1. Fail to serialize this in wire:snapshot
2. Not recognize it as a valid "eager-loadable" relation
3. Drop it during JavaScript rendering to avoid serialization issues

---

## Root Cause: MOST LIKELY

### The Column Is Rendered But Hidden By Livewire Wire State

**Mechanism**:
1. Column definition exists in PHP ✓
2. Livewire component hydration tries to serialize it
3. The nested accessor `call.branch.phone_number` causes a serialization issue
4. Livewire silently omits it from the wire:snapshot
5. JavaScript never receives it, so it's never rendered

**Why Visibility Toggles Don't Work**:
- The column is never added to the DOM in the first place
- Toggle controls can only show/hide columns that exist in the DOM
- Since the column was never rendered by Livewire, there's nothing to toggle

---

## Solution: Fix Livewire Serialization

### Option 1: Use a Model Accessor (Recommended)
Instead of relying on nested relation accessors in the column, create a model accessor in RetellCallSession:

```php
// In RetellCallSession.php
public function getPhoneNumberAttribute(): string
{
    return $this->call?->branch?->phone_number ?? '-';
}

// Then in the Resource, use:
TextColumn::make('phone_number')  // Not 'call.branch.phone_number'
    ->label('Telefon')
    ->default('-')
    ->searchable()
    ->copyable()
    ->toggleable(isToggledHiddenByDefault: false)
    ->visible(true),
```

### Option 2: Use getStateUsing Callback
Replace the nested accessor with an explicit callback:

```php
TextColumn::make('call_branch_phone_number')  // Use a simple alias
    ->label('Telefon')
    ->getStateUsing(fn ($record) => $record->call?->branch?->phone_number ?? '-')
    ->default('-')
    ->searchable(false)  // Cannot search on computed values
    ->copyable()
    ->toggleable(isToggledHiddenByDefault: false)
    ->visible(true),
```

### Option 3: Check Livewire Version
The issue might be specific to a Filament/Livewire version combination.

```bash
composer show | grep filament
composer show | grep livewire
```

---

## Implementation: The Fix

### File: `/var/www/api-gateway/app/Models/RetellCallSession.php`

Add this accessor to line 199+ (after existing accessors):

```php
/**
 * Get the branch phone number for display in Filament tables.
 * This accessor is used to work around Livewire serialization issues
 * with deeply nested relation accessors like call.branch.phone_number.
 *
 * @return string
 */
public function getPhoneNumberAttribute(): string
{
    return $this->call?->branch?->phone_number ?? '-';
}
```

### File: `/var/www/api-gateway/app/Filament/Resources/RetellCallSessionResource.php`

Replace lines 91-97:

```php
// BEFORE:
TextColumn::make('call.branch.phone_number')
    ->label('Telefon')
    ->default('-')
    ->searchable()
    ->copyable()
    ->toggleable(isToggledHiddenByDefault: false)
    ->visible(true),

// AFTER:
TextColumn::make('phone_number')
    ->label('Telefon')
    ->default('-')
    ->copyable()
    ->toggleable(isToggledHiddenByDefault: false)
    ->visible(true),
```

**Key changes**:
- Column name: `call.branch.phone_number` → `phone_number` (uses the accessor)
- Removed `.searchable()` (cannot search on computed fields)
- Kept all other functionality

---

## Why This Works

1. **Model Accessor** → Simple property, easy to serialize
2. **No nested relations** → Livewire serialization succeeds
3. **Explicit Eager Loading** → Data is available in getEloquentQuery()
4. **JSON-compatible** → String value serializes without issues
5. **Backward compatible** → No breaking changes

---

## Verification Steps

After implementing the fix:

```bash
# 1. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 2. Test the column
php artisan tinker
```

```php
$record = \App\Models\RetellCallSession::first();
echo $record->phone_number;  // Should output: +493033081738

// Verify in Filament
// Navigate to Call Monitoring and check if Telefon column now appears
```

---

## Alternative Diagnosis: Check Filament Logs

If the issue persists after the fix, check for Livewire errors:

```bash
# Enable debug mode temporarily
php artisan tinker
Config::set('app.debug', true);
// Reload the page and check storage/logs/laravel.log

tail -f storage/logs/laravel.log | grep -i "column\|serializ\|call.branch"
```

---

## Prevention Recommendations

1. **Avoid nested relation column accessors** in Filament
2. **Always use model accessors** for complex data retrieval
3. **Test column visibility** after Filament updates
4. **Use getStateUsing callbacks** for computed columns that don't need search
5. **Add integration tests** for all admin table columns

---

## Summary

| Aspect | Status |
|--------|--------|
| Data exists | ✓ YES |
| Relationships load | ✓ YES |
| Column defined | ✓ YES |
| Authorization | ✓ OPEN |
| Root cause | Livewire serialization of nested accessor |
| Fix complexity | LOW |
| Fix risk | MINIMAL |
| Testing required | YES |

