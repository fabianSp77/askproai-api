# Calls Page Router Fix Needed

## Problem
Die Calls React-Seite zeigt den Fehler:
```
Uncaught Error: useNavigate() may be used only in the context of a <Router> component.
```

## Ursache
Die React-App verwendet `useNavigate()` von React Router, aber es fehlt der `<BrowserRouter>` Wrapper in der Haupt-App.

## Lösung
Die Datei `/resources/js/portal-calls.jsx` wurde bereits korrigiert und `BrowserRouter` hinzugefügt. Die Änderungen müssen jedoch neu gebaut werden:

```bash
npm run build
```

## Temporäre Lösung
Die Routen wurden temporär auf die funktionierende Blade-Version zurückgesetzt, bis der Build-Prozess abgeschlossen ist.

## Betroffene Dateien
- `/resources/js/portal-calls.jsx` - ✅ Bereits korrigiert
- `/resources/js/portal-dashboard.jsx` - ✅ Bereits korrigiert
- `/resources/js/portal-appointments.jsx` - ⚠️ Muss noch korrigiert werden (BrowserRouter fehlt bereits im Original)

## Status
- Die React-Controller und APIs funktionieren korrekt
- Nur die Frontend-Apps müssen neu gebaut werden