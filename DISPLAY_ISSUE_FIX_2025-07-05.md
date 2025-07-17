# Business Portal Display Issue - Behoben

**Datum**: 2025-07-05  
**Status**: ✅ Behoben

## Problem

Daten wurden nicht im Business Portal angezeigt, obwohl sie in der Datenbank vorhanden waren.

## Ursachen

1. **API Response Structure Mismatch**
   - API gibt zurück: `{ call: {...} }`
   - React erwartete: `{...}` (direkt das Call-Objekt)

2. **Falscher Feldname**
   - Component: `call.duration`
   - API liefert: `call.duration_sec`

3. **Navigation Path Issue**
   - React Router hat `basename="/business"`
   - Navigation muss `/business/calls/{id}` verwenden

## Behobene Dateien

### `/resources/js/Pages/Portal/Calls/Show.jsx`

```javascript
// Alt:
const data = await response.json();
setCall(data);

// Neu:
const data = await response.json();
if (data.call) {
    setCall(data.call);  // Extrahiere das call-Objekt
} else {
    setCall(data);       // Fallback
}
```

### Weitere Fixes:
- Duration: `call.duration` → `call.duration_sec`
- Navigation: `/calls` → `/business/calls`
- Debug-Logging hinzugefügt

## Nach dem Fix

1. **Frontend neu gebaut**: `npm run build` ✅
2. **Browser-Cache leeren**: Ctrl+F5
3. **Debug-Info in Konsole**:
   ```
   API Response Structure: {call: {...}}
   Current call state: {id: 262, ...}
   ```

## Verifizierung

1. Navigieren Sie zu `/business/calls/262`
2. Daten sollten jetzt angezeigt werden:
   - Transkript
   - Summary
   - Kundeninformationen
   - Anrufdetails

Falls weiterhin Probleme auftreten, prüfen Sie die Browser-Konsole für Fehlermeldungen.