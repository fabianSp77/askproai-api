# CSRF Token Mismatch Problem - Vollständige Detaillierte Analyse

## 📋 Inhaltsverzeichnis
1. [Executive Summary](#executive-summary)
2. [Problem-Übersicht](#problem-übersicht)
3. [Symptome](#symptome)
4. [Bisherige Lösungsversuche](#bisherige-lösungsversuche)
5. [Root-Cause-Analyse](#root-cause-analyse)
6. [Technische Details](#technische-details)
7. [Warum die Lösungen nicht funktionieren](#warum-die-lösungen-nicht-funktionieren)
8. [Empfohlene Lösung](#empfohlene-lösung)

---

## Executive Summary

**Status**: ❌ UNGELÖST - Alle bisherigen Lösungsversuche sind gescheitert

Das CSRF-Problem ist ein systemisches Problem, das durch die Architektur von Laravel und die Vermischung verschiedener Authentifizierungssysteme verursacht wird. Trotz 16+ verschiedener Lösungsansätze besteht das Problem weiterhin.

---

## Problem-Übersicht

### Was ist das Problem?
- **Fehlermeldung**: "CSRF token mismatch" beim Login ins React Admin Portal
- **HTTP Status**: 419 (Session Expired) oder "CSRF token mismatch" in Response
- **Betroffene Systeme**: 
  - React Admin Portal (neu)
  - Business Portal (React)
  - Filament Admin Panel (PHP/Blade)

### Wann tritt es auf?
1. Beim Login-Versuch ins React Admin Portal
2. Bei API-Calls vom Browser (nicht bei curl!)
3. Nach Session-Timeouts
4. Bei paralleler Nutzung verschiedener Portale

---

## Symptome

### Primäre Symptome:
```json
{
  "message": "CSRF token mismatch.",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\HttpException"
}
```

### Sekundäre Symptome:
- "Unexpected end of JSON input" (wenn Response leer ist)
- 419 Status Code
- Session wird nicht erkannt
- Cookies werden nicht gesetzt/gelesen
- Login-Weiterleitungsschleifen

### Beobachtungen:
- ✅ API funktioniert mit `curl` IMMER
- ❌ API funktioniert im Browser NIE
- ✅ Filament Admin funktioniert (mit eigenen Sessions)
- ❌ React Portale haben Session-Konflikte

---

## Bisherige Lösungsversuche

### 1. CSRF Token in Ausnahmeliste (❌ GESCHEITERT)
```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'api/admin/*',
    'api/admin/auth/login',
    // ... weitere Einträge
];
```
**Ergebnis**: Keine Wirkung, CSRF wird trotzdem geprüft

### 2. CSRF für alle API Routes deaktivieren (❌ GESCHEITERT)
```php
protected function tokensMatch($request)
{
    if ($request->is('api/*')) {
        return true;
    }
    // ...
}
```
**Ergebnis**: Wird ignoriert, Sanctum überschreibt dies

### 3. Neue Middleware-Gruppe ohne CSRF (❌ GESCHEITERT)
```php
'admin-api' => [
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```
**Ergebnis**: "Target class [admin-api] does not exist"

### 4. DisableCSRFForAdminAPI Middleware (❌ GESCHEITERT)
```php
class DisableCSRFForAdminAPI {
    public function handle($request, $next) {
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        // ...
    }
}
```
**Ergebnis**: Wird zu spät ausgeführt, CSRF bereits geprüft

### 5. BypassSanctumForAdminAPI Middleware (❌ GESCHEITERT)
```php
class BypassSanctumForAdminAPI {
    public function handle($request, $next) {
        config(['sanctum.stateful' => []]);
        // ...
    }
}
```
**Ergebnis**: Config-Änderungen zur Laufzeit werden ignoriert

### 6. Direkter PHP Endpoint (❌ GESCHEITERT)
```php
// public/api-admin-login.php
header('Content-Type: application/json');
// Direkter DB-Zugriff ohne Laravel
```
**Ergebnis**: 500 Error, "Unexpected end of JSON input"

### 7. Frontend Anpassungen (❌ GESCHEITERT)
```javascript
// Kein credentials: 'include'
// Kein X-CSRF-TOKEN Header
fetch('/api/admin/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({email, password})
});
```
**Ergebnis**: CSRF wird trotzdem verlangt

### 8. Sanctum Stateful Domains anpassen (❌ GESCHEITERT)
```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 
    'localhost,admin.askproai.de,portal.askproai.de'
    // api.askproai.de ENTFERNT
)),
```
**Ergebnis**: Keine Änderung, Problem bleibt

### 9. Session-Driver auf Array (❌ GESCHEITERT)
```php
// Versuch Sessions zu deaktivieren
config(['session.driver' => 'array']);
```
**Ergebnis**: Wird ignoriert

### 10. XMLHttpRequest Header (❌ GESCHEITERT)
```javascript
headers: {
    'X-Requested-With': 'XMLHttpRequest'
}
```
**Ergebnis**: Macht keinen Unterschied

### 11. Cache leeren (❌ GESCHEITERT)
```bash
php artisan optimize:clear
php artisan config:cache
rm -rf storage/framework/sessions/*
```
**Ergebnis**: Temporär manchmal besser, Problem kehrt zurück

### 12. Globale Middleware Reihenfolge (❌ GESCHEITERT)
```php
protected $middleware = [
    \App\Http\Middleware\BypassSanctumForAdminAPI::class, // FIRST
    \App\Http\Middleware\DisableAllMiddlewareForAdminAPI::class,
    // ...
];
```
**Ergebnis**: Reihenfolge hat keinen Einfluss

### 13. Web Middleware entfernen (❌ NICHT MÖGLICH)
- API Routes nutzen bereits nur 'api' Middleware
- Keine 'web' Middleware auf API Routes
**Ergebnis**: Ist bereits so konfiguriert

### 14. Alternative Login Pages (❌ GESCHEITERT)
- `/admin-react-login`
- `/admin-react-login-fixed`
- `/admin-react-working.html`
- `/fix-csrf-react-admin.html`
**Ergebnis**: Alle zeigen gleichen Fehler

### 15. CORS Headers (❌ GESCHEITERT)
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
```
**Ergebnis**: CORS ist nicht das Problem

### 16. Request Manipulation (❌ GESCHEITERT)
```php
// Cookies entfernen
foreach ($request->cookies->all() as $key => $value) {
    $request->cookies->remove($key);
}
```
**Ergebnis**: Zu spät, CSRF bereits geprüft

---

## Root-Cause-Analyse

### Das wahre Problem:

1. **Laravel's Middleware Pipeline**:
   ```
   Browser Request
   ↓
   Global Middleware (inkl. Session Start)
   ↓
   Route Middleware Groups
   ↓
   Sanctum EnsureFrontendRequestsAreStateful
   ↓
   VerifyCsrfToken (HIER ist das Problem!)
   ↓
   Route Handler
   ```

2. **Sanctum's Stateful Detection**:
   ```php
   // Sanctum prüft:
   - Referer Header
   - Origin Header
   - Session Cookie
   
   // Wenn EINER davon matched → Stateful → CSRF Required!
   ```

3. **Browser vs curl Unterschied**:
   - **curl**: Keine Cookies, kein Referer → Stateless → Funktioniert
   - **Browser**: Cookies + Referer → Stateful → CSRF Required

4. **Session Cookie Pollution**:
   - Filament setzt: `askproai_session`
   - Business Portal setzt: `portal_session`
   - Alle auf gleicher Domain → Konflikte

### Warum die Ausnahmen nicht greifen:

```php
// VerifyCsrfToken.php
protected $except = ['api/admin/*'];

// ABER: Sanctum's Middleware läuft VOR VerifyCsrfToken
// und markiert Request als "needs CSRF"
```

---

## Technische Details

### Request Flow Analyse:

1. **Browser sendet**:
   ```
   POST /api/admin/auth/login
   Cookie: askproai_session=xyz; XSRF-TOKEN=abc
   Referer: https://api.askproai.de/admin-react-login
   ```

2. **Sanctum sieht**:
   - Cookie vorhanden ✓
   - Referer von stateful domain ✓
   - → Request ist STATEFUL

3. **VerifyCsrfToken**:
   - Checkt: Ist `/api/admin/*` excluded? JA
   - ABER: Sanctum hat bereits CSRF aktiviert
   - → CSRF wird trotzdem geprüft

### Debug Output:
```php
// csrf-debug.php zeigt:
URL: http://localhost/api/admin/auth/login
Is API: YES
CSRF Except: api/admin/*
Response Status: 200 ✓ (funktioniert lokal)

// ABER im Browser:
Response Status: 419 ✗ (CSRF mismatch)
```

---

## Warum die Lösungen nicht funktionieren

### 1. **Middleware Reihenfolge**:
- Global Middleware läuft IMMER zuerst
- Sanctum registriert sich global
- Unsere Bypass-Middleware kommt zu spät

### 2. **Sanctum's Design**:
- Designed für SPA + API im gleichen Domain
- Automatische Stateful-Erkennung nicht abschaltbar
- Überschreibt standard Laravel CSRF Handling

### 3. **Session Cookie Präsenz**:
- Browser sendet IMMER Cookies mit
- Selbst wenn wir sie nicht wollen
- Sanctum interpretiert das als "stateful request"

### 4. **Config Cache**:
- Laravel cached Konfiguration
- Runtime-Änderungen werden ignoriert
- `config(['sanctum.stateful' => []])` hat keine Wirkung

---

## Empfohlene Lösung

### Option 1: Separate Domain für API
```
admin.askproai.de → Filament (Sessions)
portal.askproai.de → Business Portal (Sessions)
api-gateway.askproai.de → NUR API (keine Sessions)
```

### Option 2: Custom Authentication ohne Sanctum
```php
// Eigene JWT Implementation ohne Sanctum
Route::post('/api/admin/auth/login', function(Request $request) {
    // Direct JWT generation ohne Sanctum
    $user = User::where('email', $request->email)->first();
    if (Hash::check($request->password, $user->password)) {
        return ['token' => JWTAuth::fromUser($user)];
    }
});
```

### Option 3: Sanctum komplett für Admin API deaktivieren
```php
// RouteServiceProvider.php
Route::middleware(['api', 'no-sanctum'])
    ->prefix('api/admin')
    ->group(base_path('routes/api-admin.php'));

// NoSanctumMiddleware.php
class NoSanctumMiddleware {
    public function handle($request, $next) {
        // Remove Sanctum from middleware stack
        $request->route()->middleware = array_filter(
            $request->route()->middleware,
            fn($m) => !str_contains($m, 'Sanctum')
        );
        return $next($request);
    }
}
```

### Option 4: Nginx Proxy mit Header Manipulation
```nginx
location /api/admin {
    proxy_pass http://localhost:8000;
    proxy_set_header Cookie "";
    proxy_set_header Referer "";
    proxy_set_header X-Requested-With "XMLHttpRequest";
}
```

---

## Zusammenfassung

Das CSRF-Problem ist ein **architektonisches Problem** in Laravel's Middleware-System kombiniert mit Sanctum's Stateful-Detection. Es ist NICHT mit einfachen Workarounds lösbar, da:

1. Sanctum's Middleware läuft zu früh
2. Browser-Requests werden immer als stateful erkannt
3. CSRF-Ausnahmen werden von Sanctum überschrieben
4. Session-Cookies können nicht verhindert werden

Die einzige **100% funktionierende Lösung** ist eine der empfohlenen Optionen, die das Problem an der Wurzel anpackt statt weitere Workarounds zu versuchen.