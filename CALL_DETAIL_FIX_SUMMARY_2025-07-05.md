# Call Detail Fix - Business Portal React

**Datum**: 2025-07-05  
**Status**: ✅ Behoben

## Problem

Die Anruf-Detailseite führte zum alten Portal/Kundenportal statt zur React-Version des Business Portals.

## Lösung

### 1. **CallController angepasst**
- Entfernt: Prüfung auf `?react=true` Parameter
- Neu: Immer React-Views verwenden für Business Portal
- Code: `return view('portal.calls.react-show', compact('call'));`

### 2. **React-Views aktualisiert**
- Statt einzelne Komponenten zu laden, wird jetzt die vollständige React-App geladen
- `react-show.blade.php` und `react-index.blade.php` nutzen jetzt `app-react-simple.jsx`
- Initial-Route wird über `data-initial-route` an die React-App übergeben

### 3. **React Router erweitert**
- Neue Route: `/calls/:id` für Call-Details
- Import von `CallShow` Component hinzugefügt
- Navigate-Logik für initial-route implementiert

### 4. **Initial Route Handling**
- `app-react-simple.jsx` liest `data-initial-route` aus
- `PortalAppModern` navigiert beim Start zur initial-route
- Ermöglicht direkten Zugriff auf Detail-Seiten

## Technische Details

### Blade Template (react-show.blade.php)
```blade
<div id="app" 
     data-auth="{{ json_encode(['user' => Auth::guard('portal')->user() ?: Auth::user()]) }}"
     data-api-url="{{ url('/api') }}"
     data-csrf="{{ csrf_token() }}"
     data-initial-route="/calls/{{ $call->id }}">
</div>
```

### React Router (PortalAppModern.jsx)
```jsx
<Route path="/calls/:id" element={<CallShow csrfToken={csrfToken} />} />

// Initial route navigation
React.useEffect(() => {
    if (initialRoute && location.pathname === '/') {
        navigate(initialRoute);
    }
}, [initialRoute]);
```

## Ergebnis

- ✅ Anruf-Liste lädt React-App mit Navigation
- ✅ Anruf-Details laden React-App und navigieren zur richtigen Route
- ✅ Konsistente Nutzung der React-App im gesamten Business Portal
- ✅ Keine Vermischung von altem und neuem Portal mehr