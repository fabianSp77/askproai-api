# Billing API Authentication Fix - 16. Juli 2025

## Problem
Die Business Portal Billing API gibt "Failed to fetch usage" zurück, obwohl der User authentifiziert ist.

## Ursache
Die Session wird nicht korrekt zwischen Web-Requests (`/business`) und API-Requests (`/business/api/*`) geteilt, weil:
1. Der Session-Cookie auf den Pfad `/business` beschränkt ist
2. Die Authentication nicht von der Session wiederhergestellt wird

## Implementierte Lösung

### 1. SharePortalSession Middleware
Neue Middleware erstellt: `/app/Http/Middleware/SharePortalSession.php`
- Stellt die Portal-Authentication aus der Session wieder her
- Setzt die `current_company_id` in der App-Instance
- Unterstützt Admin-Viewing

### 2. Middleware zu business-api Gruppe hinzugefügt
In `/bootstrap/app.php`:
```php
$middleware->group('business-api', [
    \App\Http\Middleware\PortalSessionConfig::class,
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \App\Http\Middleware\SharePortalSession::class,  // NEU
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
]);
```

### 3. Debug-Endpoint
Neuer Debug-Endpoint: `/business/api/billing/debug-auth`
- Zeigt Session-Konfiguration
- Zeigt Auth-Status
- Zeigt Company-Context

## Test-Schritte

1. **Cache leeren** (bereits erledigt)
   ```bash
   php artisan optimize:clear
   ```

2. **Browser-Test**
   - Browser-Cache leeren (Ctrl+Shift+R)
   - Business Portal neu laden
   - Billing-Seite aufrufen
   
3. **Debug-Endpoint testen**
   ```
   https://api.askproai.de/business/api/billing/debug-auth
   ```

## Erwartetes Ergebnis
- Billing API sollte jetzt erfolgreich Daten zurückgeben
- Keine "Failed to fetch" Fehler mehr

## Langfristige Lösung
Für eine robustere Lösung sollte implementiert werden:
1. **Token-basierte Authentication** (Sanctum/JWT)
2. **API-spezifische Auth-Tokens**
3. **Separate Session-Domains** für Web und API

## Notfall-Rollback
Falls Probleme auftreten:
1. SharePortalSession aus bootstrap/app.php entfernen
2. `php artisan optimize:clear` ausführen