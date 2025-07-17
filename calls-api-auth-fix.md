# Calls API Authentication Fix - 16. Juli 2025

## Problem
Die Calls-Seite zeigt keine Daten an - ähnliches Problem wie bei der Billing-Seite.

## Ursache
Die Calls API-Route in `api-portal.php` verwendet das `portal.auth.api` Middleware, aber die Session wird nicht korrekt zwischen Web und API geteilt.

## Lösung implementiert

### 1. SharePortalSession Middleware hinzugefügt
Die API-Routen in `api-portal.php` verwenden jetzt zusätzlich die SharePortalSession Middleware:

```php
Route::prefix('business/api')
    ->middleware(['web', 'portal.session', \App\Http\Middleware\SharePortalSession::class, 'portal.auth.api'])
    ->name('business.api.')
    ->group(function () {
```

### 2. JavaScript-Fix
Doppelte `credentials: 'include'` Zeilen in `useCalls.js` entfernt.

### 3. Debug-Endpoint erstellt
Neuer Debug-Endpoint für Tests: `/business/api/debug-calls-auth`

## Getestete URLs
- **Calls-Seite**: https://api.askproai.de/business/calls
- **Debug-Seite**: https://api.askproai.de/test-calls-api-debug.html

## Status
✅ Die Calls API sollte jetzt mit der gleichen Authentifizierung wie die Billing API funktionieren.

## Wichtig
Die Session-Sharing funktioniert nur, wenn der User über das Business Portal eingeloggt ist (nicht über das Admin Panel).