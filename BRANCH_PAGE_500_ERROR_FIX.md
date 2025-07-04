# Branch Page 500 Error Fix

## Problem
Die Branches-Seite (`/admin/branches`) zeigte einen 500 Internal Server Error.

## Ursache
Die `maxHeight()` Methode existiert nicht auf `ActionGroup` in der aktuellen Filament-Version. Diese Methode war in der Context7 Dokumentation erwähnt, aber anscheinend nur in neueren Versionen verfügbar.

## Lösung
Entfernt die nicht existierende `maxHeight()` Methode aus der ActionGroup-Konfiguration:

```php
// Vorher (verursachte Fehler):
->dropdownWidth(MaxWidth::ExtraSmall)
->maxHeight('400px')  // ❌ Diese Methode existiert nicht
->dropdownPlacement('bottom-end')

// Nachher (funktioniert):
->dropdownWidth(MaxWidth::ExtraSmall)
->dropdownPlacement('bottom-end')
```

## Verbleibende Konfiguration
Die ActionGroup hat jetzt folgende funktionierende Einstellungen:
- `->button()` - Styled als Button
- `->dropdownWidth(MaxWidth::ExtraSmall)` - Begrenzt die Breite
- `->dropdownPlacement('bottom-end')` - Positioniert rechts unten

Die Höhenbegrenzung wird weiterhin durch das CSS in `action-group-fix.css` gehandhabt:
```css
.fi-dropdown-list {
    max-height: 400px !important;
    overflow-y: auto !important;
}
```

## Status
✅ 500 Error behoben
✅ Dropdown funktioniert korrekt
✅ Styling durch CSS weiterhin aktiv