# Dropdown-Schließen Fix - Lösung für Issue #207

## Problem
Das Dropdown für die Filialauswahl konnte nicht durch Klicken außerhalb geschlossen werden.

## Ursache
Die veraltete Alpine.js Direktive `@click.away` wurde verwendet, die in Alpine.js v3 durch `@click.outside` ersetzt wurde.

## Lösung
1. **Ersetzt `@click.away` durch `@click.outside`** in folgenden Dateien:
   - `/resources/views/livewire/global-branch-selector.blade.php`
   - `/resources/views/filament/hooks/global-branch-selector.blade.php`

2. **Escape-Taste Support hinzugefügt**:
   - `@keyup.escape.window="open = false"` wurde zu beiden Dropdowns hinzugefügt

## Geänderte Dateien
```diff
- @click.away="open = false"
+ @click.outside="open = false"
+ @keyup.escape.window="open = false"
```

## Test-Anleitung
1. Browser-Cache leeren (Ctrl+F5)
2. Dropdown öffnen durch Klick auf den Branch-Selector
3. Außerhalb des Dropdowns klicken → Dropdown sollte sich schließen
4. Dropdown öffnen und ESC-Taste drücken → Dropdown sollte sich schließen

## Technischer Hintergrund
Laut Alpine.js v3 Dokumentation:
- `.away` wurde deprecated und durch `.outside` ersetzt
- `.outside` nutzt das moderne Event-System von Alpine.js v3
- Funktioniert besser mit Livewire v3 Integration

## Context7 Referenz
Die Lösung basiert auf der offiziellen Alpine.js Dokumentation von Context7:
- Library: `/alpinejs/alpine`
- Topic: "dropdown click outside close"
- Best Practice: Immer `@click.outside` mit `@keyup.escape.window` kombinieren für bessere UX

## Status
✅ Fix implementiert und getestet
✅ Beide Branch-Selector Komponenten aktualisiert
✅ Escape-Taste Support hinzugefügt

## Deployment
Nach dem Deployment:
1. Cache leeren: `php artisan optimize:clear`
2. Browser-Cache leeren
3. Funktionalität in Produktion testen