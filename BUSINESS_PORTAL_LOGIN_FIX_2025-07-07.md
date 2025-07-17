# Business Portal Login Fix - 2025-07-07

## Problem
Der Business Portal Login funktionierte nicht mehr - 419 CSRF Token Fehler und Session-Persistenz-Probleme bei API-Calls.

## Ursachen
1. **CSRF Token Mismatch (419 Error)**
   - Session `same_site` Policy war auf 'strict' gesetzt
   - React fetch() Calls verwendeten `credentials: 'same-origin'` statt `credentials: 'include'`

2. **Session-Persistenz bei API-Calls**
   - Session-Cookies wurden bei Cross-Origin-Requests nicht mitgesendet
   - Inkonsistente Credentials-Handhabung in React-Komponenten

## Durchgeführte Fixes

### 1. Session-Konfiguration angepasst
- `config/session.php`: `same_site` von 'strict' auf 'lax' geändert
- `.env`: `SESSION_DOMAIN` leer gelassen (verwendet automatisch aktuelle Domain)
- `.env`: `SESSION_LIFETIME` von 120 auf 480 Minuten erhöht (8 Stunden)

### 2. React fetch() Calls korrigiert
- Alle fetch() Calls in `resources/js/` auf `credentials: 'include'` umgestellt
- Betrifft hauptsächlich:
  - `hooks/useCalls.js`
  - `hooks/useGoals.js`
  - `hooks/useAuth.jsx`
  - Dashboard-Komponenten
  - Weitere API-bezogene Komponenten

### 3. CORS Middleware implementiert
- Neue Middleware: `app/Http/Middleware/PortalApiCors.php`
- Setzt korrekte CORS-Headers für API-Endpoints:
  - `Access-Control-Allow-Credentials: true`
  - `Access-Control-Allow-Origin` mit Whitelist
- Registriert in `bootstrap/app.php` und API-Routen

### 4. Build & Cache
- JavaScript neu gebaut mit `npm run build`
- Alle Laravel-Caches geleert
- PHP-FPM neu gestartet

## Test-Zugangsdaten
- **URL**: https://api.askproai.de/business/login
- **Email**: fabianspitzer@icloud.com
- **Passwort**: demo123

## Weitere Test-Accounts
- test@askproai.de / Test123!
- admin@askproai.de / Admin123!

## Empfehlungen für die Zukunft

1. **Konsistente API-Call-Implementierung**
   - Alle fetch() Calls sollten `axiosInstance` verwenden
   - Zentrale Error-Handling und Auth-Logik

2. **Laravel Sanctum für SPA**
   - Token-basierte Authentifizierung für React SPA
   - Session nur für initiales Login

3. **Monitoring**
   - Session-Persistenz überwachen
   - API-Response-Zeiten tracken
   - Failed Login Attempts loggen

## Status
✅ Problem behoben - Login funktioniert wieder