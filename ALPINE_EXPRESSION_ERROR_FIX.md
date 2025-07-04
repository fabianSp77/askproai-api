# Alpine.js Expression Error Fix

## Problem
Alpine.js Expression Error mit "Invalid or unexpected token" beim Branch Selector Dropdown.

## Ursache
UUIDs wurden ohne Anführungszeichen in JavaScript-Expressions verwendet:
```javascript
// Fehlerhaft:
{{ $currentBranch ? $currentBranch->id : 'null' }} === branch.id
// Resultat wenn UUID: 34c4d48e-4753-4715-9c30-c55843a943e8 === branch.id
```

## Lösung
UUID-Werte in Anführungszeichen setzen:
```javascript
// Korrekt:
{{ $currentBranch ? "'" . $currentBranch->id . "'" : 'null' }} === branch.id
// Resultat: '34c4d48e-4753-4715-9c30-c55843a943e8' === branch.id
```

## Betroffene Dateien
- `/resources/views/filament/components/professional-branch-switcher.blade.php`
  - Zeile 148: `:class` Binding
  - Zeile 159: `x-if` Template Condition

## Status
✅ Beide Stellen korrigiert
✅ UUID-Werte werden jetzt korrekt als Strings behandelt
✅ Alpine.js Expression Error behoben