# Phone Number Format Fix Summary

## Issue
When entering the phone number +493033081738, the form was displaying it in a strange format due to an inappropriate input mask.

## Root Cause
The input mask `+99 999 99999999` was causing incorrect formatting:
- It was designed for a different phone number structure
- It was forcing spaces in wrong positions
- It made German phone numbers look confusing

## Solution Applied

### Removed the problematic mask
The mask has been completely removed from all phone number inputs:
- `branch_phone` field
- `hotline_number` field  
- `number` field in the repeater

### What remains:
- Clean validation with regex: `/^\+?[0-9\s\-\(\)]+$/`
- Automatic cleaning before save: `preg_replace('/[^0-9+]/', '', $state)`
- Maximum length limit: 20 characters
- Native browser tel input behavior

## Benefits

### Now you can enter phone numbers in any format:
- `+493033081738` - Standard international
- `+49 30 33081738` - With spaces
- `030 33081738` - Local format
- `+49 (30) 33081738` - With parentheses
- `030-33081738` - With dashes

### The number displays exactly as you type it!
No more weird formatting or unexpected spaces.

## Changes Made

### File Modified:
`/app/Filament/Admin/Pages/QuickSetupWizardV2.php`
- Removed `->mask('+99 999 99999999')` from all phone inputs
- Removed `->stripCharacters(' -')` (no longer needed)
- Added `->maxLength(20)` for safety

## Testing
1. Navigate to: https://api.askproai.de/admin/quick-setup-wizard-v2
2. Hard refresh (Ctrl+F5)
3. Enter +493033081738
4. The number will display exactly as entered!

## Related GitHub Issue
https://github.com/fabianSp77/askproai-api/issues/252