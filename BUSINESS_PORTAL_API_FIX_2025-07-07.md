# Business Portal API Fix - 2025-07-07

## Problem
Wenn Admins über "Portal öffnen" auf das Business Portal zugreifen, erhalten sie Fehler beim Laden der API-Daten:
- "Unexpected token '<'" - HTML wird statt JSON zurückgegeben
- 500 Internal Server Error
- Target class [portal.auth.api] does not exist

## Ursache
1. **Session-Problem**: Portal User wurde nicht korrekt eingeloggt (`portal_auth: false`)
2. **Middleware-Konflikt**: Routes in `api-portal.php` verwendeten `portal.auth` statt `portal.auth.api`
3. **Nicht-existente Middleware**: `portal.api.cors` existierte nicht

## Lösung

### 1. AdminAccessController erweitert
- Speichert `portal_user_id` in korrektem Session-Key
- Login mit "remember me" für bessere Persistenz
- Explizites Speichern der Laravel Auth Session Keys

### 2. PortalApiAuth Middleware verbessert  
- Erkennt Admin-User über Spatie "Super Admin" Role
- Erlaubt direkten Admin-Zugriff ohne Portal Login
- Setzt automatisch Company Context

### 3. Route-Middleware korrigiert
- `api-portal.php`: Geändert von `portal.auth` zu `portal.auth.api`
- `business-portal.php`: Entfernt nicht-existente `portal.api.cors`

## Verbleibende Schritte

### Für Admins:
1. **Browser-Cache leeren** (Ctrl+F5)
2. **Cookies löschen** oder Inkognito-Fenster nutzen
3. **Neu über "Portal öffnen" zugreifen**

### Alternative:
Direkt als Business Portal User einloggen:
- URL: https://api.askproai.de/business/login
- Email: demo@example.com
- Password: demo123

## Debug-Tools
- Session Check: https://api.askproai.de/api/debug/session
- API Debug: https://api.askproai.de/debug-business-portal-api.html