# Fixed 500 Error on Calls Page

## Problem
The calls overview page at https://api.askproai.de/admin/calls was returning a 500 error.

## Root Cause
The error was: `Svg by name "o-face-neutral" from set "heroicons" not found`

The CallResource was using invalid Heroicon names:
- `heroicon-o-face-neutral` - doesn't exist
- `heroicon-m-emoji-happy` - doesn't exist

## Solution
1. Changed icon names to valid Heroicons:
   - `heroicon-o-face-smile` → `heroicon-m-face-smile`
   - `heroicon-o-face-frown` → `heroicon-m-face-frown`
   - `heroicon-o-face-neutral` → `heroicon-m-minus-circle`
   - `heroicon-m-emoji-happy` → `heroicon-m-minus-circle`

2. Fixed both the table column icons and infolist icons

## Files Modified
- `/var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php`

## Result
The calls page should now load without errors. The sentiment column will show:
- 😊 Smile icon for positive sentiment
- 😢 Frown icon for negative sentiment
- ⭕ Minus circle icon for neutral sentiment
- ❓ Question mark circle for unknown/not analyzed