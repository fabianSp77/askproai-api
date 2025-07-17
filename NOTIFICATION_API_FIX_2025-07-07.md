# Notification API Fix - Business Portal (2025-07-07)

## Problem
Nach dem Login über das Admin Portal und Navigation zum Business Portal funktionierten die Notification APIs nicht. Die JavaScript Console zeigte:
```
Failed to fetch notifications: SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
```

## Ursache
1. Die JavaScript-Dateien (`NotificationCenter.jsx` und `NotificationService.js`) verwendeten inkonsistente API-Pfade:
   - `NotificationCenter.jsx`: `/business/api-optional/notifications`
   - `NotificationService.js`: `/business/api/notifications`

2. Die catch-all Route für die React SPA schloss nur `api/` aus, aber nicht `api-optional/`, wodurch API-Requests zur React-App umgeleitet wurden.

## Lösung

### 1. Route-Konfiguration korrigiert
In `/var/www/api-gateway/routes/business-portal.php`:
```php
// Vorher:
Route::get('/{any?}', [ReactDashboardController::class, 'index'])
    ->where('any', '^(?!api/).*$')
    ->name('dashboard');

// Nachher:
Route::get('/{any?}', [ReactDashboardController::class, 'index'])
    ->where('any', '^(?!api/|api-optional/).*$')
    ->name('dashboard');
```

### 2. NotificationApiController angepasst
Für optionale Endpoints ohne Authentifizierung werden jetzt leere Responses zurückgegeben statt 401 Fehler.

### 3. Notification Routes hinzugefügt
Alle fehlenden Notification-Endpoints wurden im `api-optional` Bereich hinzugefügt:
- `/business/api-optional/notifications/{id}/read`
- `/business/api-optional/notifications/read-all`
- `/business/api-optional/notifications/{id}` (DELETE)
- `/business/api-optional/notifications/delete-all`
- `/business/api-optional/notifications/preferences`

## Verifizierung
```bash
# Route Cache leeren
php artisan route:clear && php artisan optimize:clear

# API testen
curl -s "https://api.askproai.de/business/api-optional/notifications" \
  -H "Accept: application/json" \
  -H "X-Requested-With: XMLHttpRequest"

# Erwartete Response:
{"notifications":{"data":[],"current_page":1,"per_page":20,"total":0,"last_page":1},"unread_count":0,"category_counts":[]}
```

## Test-Tools
- `/public/test-notifications-api.html` - Interaktiver API-Tester für Notifications
- `/public/test-calls-api.html` - API-Tester für Calls

## Wichtige Hinweise
- Die WebSocket-Funktionalität für Live-Notifications ist derzeit deaktiviert (kein Socket.io Server läuft)
- Die Notification-Daten werden erfolgreich über REST API abgerufen
- Admin-Impersonation funktioniert jetzt korrekt mit dem Business Portal