# Portal API Authentication Fix
*Datum: 2025-07-30*

## Problem
Nach erfolgreichem Login erhalten API-Calls einen 401 Unauthorized Error, obwohl der User eingeloggt ist.

## Root Cause
1. Die Session wird nicht korrekt zwischen Web- und API-Requests geteilt
2. Die Portal-Session-Konfiguration wird für API-Requests nicht angewendet
3. Die Middleware-Reihenfolge war nicht optimal

## Implementierte Lösungen

### 1. PortalAuthFixed für alle Business-Routes
**Datei**: `routes/business-portal.php`

```php
// Vorher:
Route::prefix('business')->middleware(['business-portal', 'portal.auth'])

// Nachher:
Route::prefix('business')->middleware(['business-portal', 'portal.auth.fixed'])
```

### 2. EnsurePortalSession in API-Middleware
**Datei**: `app/Http/Kernel.php`

```php
'business-api' => [
    PortalSessionConfig::class,
    EnsurePortalSession::class,  // NEU: Forciert Portal-Session
    EncryptCookies::class,
    // ...
]
```

### 3. Verbesserte PortalAuthFixed
- Liest Session direkt aus Portal-Verzeichnis
- Setzt Company Context automatisch
- Besseres Error Logging

### 4. Debug-Endpoint
**Route**: `/business/api/session-debug`

Zeigt:
- Session ID vs Cookie ID
- Auth Status
- Session-Datei-Inhalt
- Konfiguration

## Test-Anleitung

1. **Browser Cache/Cookies löschen**
2. **Neu einloggen**
3. **Calls-Seite aufrufen**
4. **Debug prüfen**:
   ```javascript
   fetch('/business/api/session-debug', {
     credentials: 'same-origin',
     headers: {
       'Accept': 'application/json',
       'X-Requested-With': 'XMLHttpRequest'
     }
   }).then(r => r.json()).then(console.log)
   ```

## Erwartetes Verhalten

- User loggt sich ein
- Navigiert zu Calls
- API lädt Daten ohne 401 Error
- Session bleibt zwischen Requests erhalten

## Cleanup

Nach erfolgreichem Test entfernen:
- `/business/api/session-debug` Route
- SessionDebugController
- Debug-Logging in PortalAuthFixed

## Wichtige Dateien

1. `app/Http/Middleware/PortalAuthFixed.php`
2. `app/Http/Middleware/EnsurePortalSession.php`
3. `app/Http/Controllers/Portal/Auth/LoginController.php`
4. `routes/business-portal.php`