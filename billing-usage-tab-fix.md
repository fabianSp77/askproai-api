# Billing Usage Tab Fix - 16. Juli 2025

## Problem
Die Usage-Tab-Seite im Business Portal blieb grau, wenn direkt zu `/business/billing/usage` navigiert wurde.

## Ursache
Die React-Komponente hat den aktiven Tab immer mit 'overview' initialisiert, unabhängig von der URL.

## Implementierte Lösung

### 1. Tab-Initialisierung basierend auf URL
```javascript
// Check URL for tab parameter
const getInitialTab = () => {
    const urlPath = window.location.pathname;
    if (urlPath.includes('/billing/usage')) {
        return 'usage';
    }
    return 'overview';
};
const [activeTab, setActiveTab] = useState(getInitialTab());
```

### 2. URL-Update beim Tab-Wechsel
```javascript
<Tabs activeKey={activeTab} onChange={(key) => {
    setActiveTab(key);
    // Update URL without page reload
    const newUrl = key === 'usage' ? '/business/billing/usage' : '/business/billing';
    window.history.pushState({}, '', newUrl);
}}>
```

## Geänderte Dateien
- `/resources/js/Pages/Portal/Billing/Index.jsx`

## Ergebnis
✅ Direkte Navigation zu `/billing/usage` zeigt jetzt den Usage-Tab
✅ URL wird beim Tab-Wechsel aktualisiert
✅ Browser Vor/Zurück-Navigation funktioniert

## Build-Status
✅ Assets erfolgreich neu gebaut
✅ Änderungen sind live