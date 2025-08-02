# Portal Session Logout Fix - Issue #444
*Datum: 2025-07-30*

## Problem
User wird nach erfolgreichem Login sofort wieder ausgeloggt, wenn er auf andere Seiten navigiert (z.B. Anrufe).

## Root Cause
1. **Session Regeneration**: Nach dem Login wird die Session ID geändert, aber die neue Session wird nicht korrekt persistiert
2. **Session Configuration**: Die Portal-Session-Konfiguration wird nicht konsistent angewendet
3. **Middleware Timing**: Session-Middleware wird zu spät initialisiert

## Implementierte Lösungen

### 1. EnsurePortalSession Middleware
**Datei**: `app/Http/Middleware/EnsurePortalSession.php`

Diese Middleware:
- Stellt sicher, dass die Portal-Session-Konfiguration verwendet wird
- Forciert die richtige Session ID aus dem Cookie
- Wird VOR StartSession ausgeführt

### 2. LoginController Verbesserung
**Datei**: `app/Http/Controllers/Portal/Auth/LoginController.php`

```php
// Force save nach regenerate
$request->session()->regenerate();
$request->session()->save(); // NEU: Explizit speichern
```

### 3. Middleware-Reihenfolge
**Datei**: `app/Http/Kernel.php`

```php
'business-portal' => [
    PortalSessionConfig::class,     // 1. Konfiguration setzen
    EnsurePortalSession::class,     // 2. Session ID sicherstellen
    EncryptCookies::class,          // 3. Cookies verarbeiten
    StartSession::class,            // 4. Session starten
    // ...
]
```

### 4. Debug-Tools
- `/portal-session-debug.php` - Detaillierte Session-Analyse

## Wie es jetzt funktioniert

1. **Login**: 
   - User loggt sich ein
   - Session wird regeneriert und explizit gespeichert
   - Portal Session Cookie wird gesetzt

2. **Navigation**:
   - EnsurePortalSession stellt sicher, dass die richtige Session geladen wird
   - PortalAuth prüft die Authentication
   - User bleibt eingeloggt

## Test-Anleitung

1. Browser Cache/Cookies löschen
2. Neu einloggen
3. Zu Anrufe navigieren
4. User sollte eingeloggt bleiben

## Debug bei Problemen

```bash
# Laravel Logs prüfen
tail -f storage/logs/laravel.log | grep -E "(PortalAuth|Portal user logged)"

# Session Debug
curl https://api.askproai.de/portal-session-debug.php \
  -H "Cookie: askproai_portal_session=YOUR_SESSION_ID"
```

## Cleanup

Nach erfolgreichem Test löschen:
- `/public/portal-session-debug.php`
- `/public/test-portal-session.php`
- `/public/debug-portal-session.php`