# Fix für "undefined" Call ID Problem

**Datum**: 2025-07-05  
**Status**: ✅ Behoben

## Problem

Die Call-Show-Komponente erhielt `undefined` als Call-ID, was zu einem 404 Fehler führte beim Abrufen der Call-Details.

## Ursache

Die React Router `useParams` Hook extrahierte die ID nicht korrekt, da:
1. Die Komponente ursprünglich `callId` als Prop erwartete
2. React Router übergibt Parameter über die `useParams` Hook, nicht als Props

## Lösung

### 1. **useParams Hook implementiert**
```jsx
// Vorher:
const CallShow = ({ callId }) => {
    // callId war undefined
}

// Nachher:
const CallShow = () => {
    const { id: callId } = useParams();
    // callId wird korrekt aus der URL extrahiert
}
```

### 2. **Navigation mit useNavigate**
```jsx
const navigate = useNavigate();
// Zurück-Button nutzt jetzt React Router Navigation
onClick={() => navigate('/calls')}
```

### 3. **Fehlerbehandlung verbessert**
```jsx
if (!callId) {
    console.error('No call ID provided');
    setError('Keine Anruf-ID angegeben');
    setLoading(false);
    return;
}
```

### 4. **Route-Definition korrigiert**
```jsx
// In PortalAppModern.jsx
<Route path="/calls/:id" element={<CallShow />} />
// Keine csrfToken Prop mehr nötig - kommt aus useAuth Hook
```

## Technische Details

- `useParams()` extrahiert URL-Parameter in React Router v6
- Der Parameter-Name `:id` in der Route muss mit `params.id` übereinstimmen
- csrfToken wird über den AuthContext bereitgestellt, nicht als Prop

## Ergebnis

✅ Call-ID wird korrekt aus der URL extrahiert  
✅ API-Calls funktionieren mit der richtigen ID  
✅ Fehlerbehandlung für fehlende IDs  
✅ Navigation funktioniert innerhalb der React-App