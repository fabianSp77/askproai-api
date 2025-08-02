# Business Portal Datenproblem Behebung
*Datum: 2025-07-30*

## Problem
Nach dem Login im Business Portal:
1. Benutzer wird sofort wieder ausgeloggt
2. Keine Daten werden angezeigt (Anrufe, Termine)
3. Session wird nicht richtig beibehalten

## Root Cause
Das Problem hatte mehrere Ursachen:

1. **TenantScope**: Prüfte nicht den `portal` Guard
2. **Company Context**: Wurde für Portal-Benutzer nicht gesetzt
3. **Session-Konflikte**: Verschiedene Session-Stores und Konfigurationen

## Implementierte Fixes

### 1. TenantScope erweitert
**Datei**: `app/Scopes/TenantScope.php`
```php
// Neu: Prüft portal guard zuerst
if (Auth::guard('portal')->check()) {
    $user = Auth::guard('portal')->user();
    if ($user && isset($user->company_id)) {
        return (int) $user->company_id;
    }
}
```

### 2. Neue Middleware: PortalCompanyContext
**Datei**: `app/Http/Middleware/PortalCompanyContext.php`
- Setzt explizit den Company Context für Portal-Benutzer
- Speichert Company ID in Session als Backup
- Stellt Context aus Session wieder her

### 3. Middleware Stack aktualisiert
**Datei**: `app/Http/Kernel.php`
- `PortalCompanyContext` zu beiden Middleware-Gruppen hinzugefügt:
  - `business-portal`
  - `business-api`

## Wie es jetzt funktioniert

1. **Login**: Benutzer loggt sich ein
2. **Session**: Portal-spezifische Session wird erstellt
3. **Context**: Company ID wird in App Container gesetzt
4. **Queries**: TenantScope filtert automatisch nach Company ID
5. **API Calls**: Context wird aus Session wiederhergestellt

## Test Instructions

1. **Cache leeren**:
   ```bash
   php artisan optimize:clear
   ```

2. **Neu einloggen** unter `/business/login`

3. **Prüfen**:
   - Session bleibt erhalten
   - Dashboard zeigt Daten
   - Termine-Seite funktioniert
   - Anrufe werden angezeigt

## Technische Details

### Session Flow
```
Login → PortalSessionConfig → StartSession → FixPortalApiAuth → PortalCompanyContext
         ↓                                                          ↓
         Setzt Session Cookie                                      Setzt Company ID
```

### Data Access Flow
```
Query → Model → TenantScope → getCurrentCompanyId()
                              ↓
                              Prüft: app('current_company_id')
                                     Auth::guard('portal')
                                     Session Backup
```

## Debugging

Bei Problemen prüfen:
```php
// In Controller oder View
dd([
    'portal_auth' => Auth::guard('portal')->check(),
    'portal_user' => Auth::guard('portal')->user(),
    'company_id' => app('current_company_id'),
    'session_company' => session('current_company_id'),
    'session_id' => session()->getId()
]);
```

## Nächste Schritte

1. **Session Management**: Vereinheitlichen der Session-Konfiguration
2. **Guard Consolidation**: Portal und Web Guards zusammenführen
3. **Performance**: Company Context cachen
4. **Monitoring**: Session-Metriken hinzufügen