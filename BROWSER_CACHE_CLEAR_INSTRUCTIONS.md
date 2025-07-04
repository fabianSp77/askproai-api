# Browser Cache Clear Instructions - WICHTIG!

## Problem
Die Änderungen sind implementiert, aber der Browser lädt noch die alten JavaScript/CSS Dateien aus dem Cache.

## Lösung: Hard Refresh durchführen

### Option 1: Hard Refresh (Empfohlen)
1. Öffne die Seite: https://api.askproai.de/admin/quick-setup-wizard-v2
2. Drücke:
   - **Windows/Linux**: `Ctrl + Shift + R` oder `Ctrl + F5`
   - **Mac**: `Cmd + Shift + R`

### Option 2: Developer Tools Cache Disable
1. Öffne Chrome DevTools: `F12` oder Rechtsklick → "Untersuchen"
2. Gehe zum "Network" Tab
3. Aktiviere Checkbox "Disable cache" 
4. Lade die Seite neu mit `F5`

### Option 3: Cache komplett löschen
1. Chrome: `Ctrl/Cmd + Shift + Delete`
2. Wähle:
   - Zeitraum: "Gesamte Zeit"
   - ✓ Bilder und Dateien im Cache
   - ✓ Cookies und andere Websitedaten
3. "Daten löschen" klicken
4. Browser neu starten

## Verifizierung der neuen Version

Nach dem Cache Clear solltest du in den DevTools (Network Tab) sehen:
- `dropdown-manager-GY3rxpOD.js` (NICHT die alten dropdown-fix Dateien)
- `app-CVvAb3xm.js` (neue Version)

## Was wurde behoben?

1. **Autocomplete Warnings**: Alle Formularfelder haben jetzt korrekte `autocomplete` Attribute
2. **Dropdown Funktionalität**: Neuer einheitlicher Dropdown Manager
3. **Async Listener Error**: Bessere DOM-Element-Prüfung

## Test nach Cache Clear

1. Keine Konsolen-Warnungen mehr über fehlende autocomplete Attribute
2. Alle Dropdowns (Branchen, Services, etc.) funktionieren beim Klicken
3. Kein "async listener" Fehler mehr

## Wenn immer noch Probleme:

1. **Private/Inkognito Fenster** verwenden
2. **Browser neu starten**
3. **Andere Browser** testen (Firefox, Edge)
4. **Prüfen ob richtige URL**: https://api.askproai.de/admin/quick-setup-wizard-v2 (nicht die alte wizard Version)