# Business Portal 419 Page Expired Fix

## Problem
Nach dem Login im Business Portal erscheint ein 419 "Page Expired" Fehler. Dies ist ein CSRF Token Fehler, der durch Session-/Cookie-Probleme verursacht wird.

## Ursachen
1. **Session-Cookie Secure Flag**: Portal erwartet HTTPS-only Cookies (`secure => true`)
2. **JavaScript interferiert**: Das `portal-login-fix.js` klont das Form und könnte CSRF Token verlieren
3. **Session-Pfad**: Portal nutzt separates Session-Verzeichnis
4. **Browser-Extension**: `multi-tabs.js` ist eine Browser-Extension (nicht Teil der App)

## Implementierte Lösungen

### 1. Session-Konfiguration angepasst
**Datei**: `config/session_portal.php`
```php
// Geändert von:
'secure' => env('SESSION_SECURE_COOKIE', true),
// Zu:
'secure' => env('SESSION_SECURE_COOKIE', false),
```
Dies erlaubt Session-Cookies auch über HTTP.

### 2. JavaScript deaktiviert
**Datei**: `resources/views/portal/layouts/auth.blade.php`
```blade
{{-- Portal Login Fix - Temporarily disabled due to form submission issues
<script src="{{ asset('js/portal-login-fix.js') }}?v={{ time() }}"></script> --}}
```
Das Script, das das Form klont, wurde deaktiviert.

### 3. Verbessertes JavaScript erstellt
**Datei**: `public/js/portal-login-fix-improved.js`
- Erhält CSRF Token
- Fügt Token zu Ajax-Requests hinzu
- Keine Form-Manipulation

## Konfiguration

### Environment Variables (.env)
```env
# Für Development (HTTP)
SESSION_SECURE_COOKIE=false
PORTAL_SESSION_COOKIE=askproai_portal_session
SESSION_DOMAIN=

# Für Production (HTTPS)
SESSION_SECURE_COOKIE=true
```

### Nach Änderungen
```bash
# Cache leeren
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Optional: Session-Dateien löschen
rm -f storage/framework/sessions/portal/*
```

## Troubleshooting

### Prüfe Session-Verzeichnis
```bash
ls -la storage/framework/sessions/portal
# Sollte existieren mit Schreibrechten
```

### Debug im Browser
```javascript
// In Browser-Konsole
debugPortalLogin()
```

### Prüfe Cookies
1. Browser DevTools → Application → Cookies
2. Suche nach `askproai_portal_session`
3. Prüfe ob `Secure` Flag gesetzt ist

### Alternative Fixes

#### Fix 1: Force HTTPS
```nginx
# In nginx config
if ($scheme != "https") {
    return 301 https://$server_name$request_uri;
}
```

#### Fix 2: Session Domain setzen
```env
SESSION_DOMAIN=.askproai.de
```

#### Fix 3: CSRF Token in Form sicherstellen
```javascript
// Wenn JavaScript wieder aktiviert wird
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
form.innerHTML += `<input type="hidden" name="_token" value="${csrfToken}">`;
```

## Testing

1. **Browser Cache leeren**
   - Cookies löschen
   - Hard Refresh (Ctrl+F5)

2. **Test Login**
   ```
   URL: https://api.askproai.de/business/login
   Email: [test-email]
   Password: [test-password]
   ```

3. **Monitoring**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "csrf\|419\|session"
   ```

## Langfristige Lösung

1. **Einheitliche Session-Konfiguration** für alle Portale
2. **HTTPS überall** erzwingen
3. **SPA mit API-Auth** statt Form-basiertem Login
4. **Session-Management Service** für Multi-Portal Setup