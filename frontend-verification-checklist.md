# 🔍 Frontend Verification Checklist

## Sofort zu testen:

### 1. Business Portal Admin Page
**URL**: https://api.askproai.de/admin/business-portal-admin

#### Test-Schritte:
1. [ ] Seite lädt ohne JavaScript-Fehler (F12 → Console)
2. [ ] "Emergency Fix loaded" Meldung in Console sichtbar
3. [ ] **Company Dropdown** (Firmen auswählen)
   - [ ] Klickbar
   - [ ] Zeigt Firmen-Liste
   - [ ] Auswahl funktioniert
   - [ ] Lädt Company-Daten nach Auswahl
4. [ ] **Portal öffnen Button**
   - [ ] Klickbar
   - [ ] Führt Redirect aus
   - [ ] Öffnet Business Portal
5. [ ] **Branch Selector** (Navigation oben)
   - [ ] Dropdown öffnet sich
   - [ ] Filialen werden angezeigt
   - [ ] Auswahl funktioniert

#### Debug-Commands in Browser Console:
```javascript
// Status prüfen
window.emergencyFix.status()

// Sollte zeigen:
// - Fix Attempts: X
// - Company Selectors Fixed: X
// - Alpine: Loaded
// - Livewire: Loaded

// Manuell testen
window.emergencyFix.testPortalButton()
window.emergencyFix.testCompanySelect()
```

### 2. Andere betroffene Seiten testen
- [ ] `/admin/staff` - Staff Dropdown
- [ ] `/admin/appointments` - Filter Dropdowns
- [ ] `/admin/customers` - Action Buttons

## Falls Tests fehlschlagen:

1. **Screenshot** der Fehlermeldung
2. **Console Output** kopieren
3. **Network Tab** prüfen (fehlen Scripts?)
4. **Elements Tab** prüfen (sind Elemente disabled?)