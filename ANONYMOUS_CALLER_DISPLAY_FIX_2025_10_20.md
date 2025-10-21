# Anonymous Caller Display Fix - 2025-10-20

## Problem Description

Bei anonymen Anrufern (`from_number = 'anonymous'`) wurden manchmal Wörter aus dem Transcript im `customer_name` Feld angezeigt, anstatt "Anonym" zu zeigen.

### Example
- **Call ID**: 602
- **from_number**: `anonymous`
- **customer_name**: `"mir nicht"` (Wörter aus dem Transcript!)
- **Expected Display**: "Anonym"
- **Actual Display**: "mir nicht"

## Root Cause

Die Display-Logik in `CallResource.php` prüfte die Felder in folgender Reihenfolge:
1. ✅ `customer_name` Feld
2. ✅ Verknüpfter Customer
3. ✅ Name aus Transcript extrahieren
4. ✅ Fallback: anonymous check

Das Problem: **Bei anonymen Anrufern wurde `customer_name` geprüft BEVOR geprüft wurde ob es ein anonymer Anrufer ist.**

## Solution

Die anonymer-Anrufer-Prüfung wurde an den **Anfang** jeder Display-Funktion verschoben:

```php
// CRITICAL: Check for anonymous callers FIRST
// Anonymous callers must ALWAYS show "Anonym", regardless of customer_name field
if ($record->from_number === 'anonymous') {
    return 'Anonym';  // or appropriate HTML
}
```

## Changes Made

### File: `app/Filament/Resources/CallResource.php`

**3 Locations Fixed:**

1. **Page Title** (Line ~72)
   - Function: `getRecordTitle()`
   - Returns: `'Anonymer Anrufer'`

2. **Table Column** (Line ~231)
   - Column: `customer_name`
   - Returns: `'<span class="text-gray-600">Anonym</span>'`

3. **Detail View** (Line ~1635)
   - TextEntry: `customer_name`
   - Returns: `'<div class="flex items-center"><span class="font-bold text-lg text-gray-600">Anonym</span></div>'`

## Testing

### Test Cases

```bash
# Find anonymous calls with customer_name set
mysql -u root askproai_db -e "SELECT id, from_number, customer_name FROM calls WHERE from_number = 'anonymous' LIMIT 10;"
```

**Results:**
- Call 439: `Hans Schuster` → Should display "Anonym" ✅
- Call 440: `Hann Schuster` → Should display "Anonym" ✅
- Call 602: `mir nicht` → Should display "Anonym" ✅
- Call 456: `gleich fertig` → Should display "Anonym" ✅

### Verification

All three locations now check for `from_number === 'anonymous'` FIRST:

```bash
grep -n "CRITICAL: Check for anonymous callers FIRST" app/Filament/Resources/CallResource.php
```

**Output:**
```
72:        // CRITICAL: Check for anonymous callers FIRST
231:                        // CRITICAL: Check for anonymous callers FIRST
1635:                                                        // CRITICAL: Check for anonymous callers FIRST
```

## Cache Clearing

```bash
php artisan filament:optimize-clear
php artisan cache:clear
php artisan view:clear
```

## Impact

**Before Fix:**
- Anonymous callers showed transcript fragments: "mir nicht", "gleich fertig", etc.
- Confusing for users
- Data integrity concerns

**After Fix:**
- Anonymous callers ALWAYS show "Anonym"
- Consistent display across:
  - List page: https://api.askproai.de/admin/calls/
  - Detail page: https://api.askproai.de/admin/calls/602
  - Page title/heading
- Clean, professional appearance

## Related Files

- `app/Filament/Resources/CallResource.php` (Modified)
- `app/Models/Call.php` (No changes needed)

## Prevention

The fix includes clear comments indicating that anonymous caller checks must ALWAYS come first:

```php
// CRITICAL: Check for anonymous callers FIRST
// Anonymous callers must ALWAYS show "Anonym", regardless of customer_name field
```

## Next Steps

✅ Fix implemented
✅ Cache cleared
✅ Verified in code

**Ready for testing:**
1. Visit https://api.askproai.de/admin/calls/
2. Look for any calls with "Anonym" displayed
3. Click on detail page to verify consistent display
4. Check page title also shows "Anonymer Anrufer"

---

**Status**: ✅ Complete
**Date**: 2025-10-20
**Author**: Claude Code with SuperClaude Framework
