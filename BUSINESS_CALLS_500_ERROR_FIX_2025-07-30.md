# Business Calls 500 Error Fix
*Datum: 2025-07-30*

## Problem
500 Internal Server Error beim Aufruf von `/business/calls` nach dem Login.

## Mögliche Ursachen
1. User ist nicht authentifiziert (Session-Problem)
2. Company ID fehlt
3. Datenbankabfrage schlägt fehl

## Implementierte Fixes

### 1. CallController Verbesserung
**Datei**: `app/Http/Controllers/Portal/CallController.php`

- Prüfung ob User authentifiziert ist
- Bessere Error-Behandlung für fehlende Company ID
- Logging bei Fehlern

### 2. Debug-Tool
**Datei**: `/public/business-debug.php`

Zeigt:
- Request-Details
- Response Status
- Auth Status
- Session-Daten
- Company Context

## Debug-Anleitung

1. **Im Browser aufrufen**:
   ```
   https://api.askproai.de/business-debug.php
   ```

2. **Prüfen Sie**:
   - `auth.portal_authenticated` - sollte `true` sein
   - `auth.portal_user.company_id` - sollte gesetzt sein
   - `session.has_login_key` - sollte `true` sein
   - `response.status_code` - zeigt den HTTP Status

3. **Bei 500 Error**:
   - `response.error` zeigt die Fehlermeldung
   - Prüfen Sie ob Company ID vorhanden ist
   - Prüfen Sie die Session-Daten

## Nächste Schritte

1. Browser-Cache/Cookies löschen
2. Neu einloggen
3. Debug-Tool aufrufen
4. Ergebnis prüfen

Falls immer noch Fehler:
- Laravel Logs prüfen: `tail -f storage/logs/laravel.log`
- Session-Dateien prüfen: `ls -la storage/framework/sessions/portal/`

## Cleanup

Nach erfolgreichem Test löschen:
- `/public/business-debug.php`
- Alle anderen Debug-Scripts