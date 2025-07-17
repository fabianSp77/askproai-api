# Tiefgreifende Analyse: Login-Probleme bei AskProAI

## Zusammenfassung
Nach eingehender Analyse der Logs, Session-Konfiguration und Middleware-Stack zeigt sich, dass die Login-Funktionalität grundsätzlich funktioniert, aber es gibt mehrere kritische Probleme mit der Session-Verwaltung und Cookie-Konfiguration.

## 1. Session-Konfiguration Analyse

### Aktuelle Konfiguration (.env)
```
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=api.askproai.de
SESSION_SAME_SITE=lax
SESSION_COOKIE=askproai_session
SESSION_ENCRYPT=true
SESSION_HTTP_ONLY=true
```

### Identifizierte Probleme

#### Problem 1: SESSION_DOMAIN Konflikt
- **Aktuell**: `SESSION_DOMAIN=api.askproai.de`
- **Problem**: Cookies werden nur für die exakte Domain gesetzt, nicht für Subdomains
- **Auswirkung**: Sessions können zwischen verschiedenen Teilen der Anwendung nicht geteilt werden

#### Problem 2: Secure Cookie auf HTTPS
- **Aktuell**: `SESSION_SECURE_COOKIE=true`
- **Problem**: Cookies werden nur über HTTPS gesendet
- **Auswirkung**: Wenn irgendwo HTTP verwendet wird, schlägt die Session fehl

## 2. Middleware-Stack Analyse

### Admin Portal (Filament)
```php
->middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    AuthenticateSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
    SubstituteBindings::class,
    DisableBladeIconComponents::class,
    DispatchServingFilamentEvent::class,
    BranchContextMiddleware::class,
])
```

### Business Portal
```php
'portal' => [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
    SubstituteBindings::class,
]
```

### Problem: Unterschiedliche Middleware-Konfigurationen
- Admin Portal nutzt `AuthenticateSession` zusätzlich
- Verschiedene Session-Handler könnten zu Konflikten führen

## 3. Auth Guards Analyse

### Konfigurierte Guards
1. **web** (Admin Portal) - Standard Laravel Auth
2. **portal** (Business Portal) - Custom Portal Auth

### Problem: Session Key Konflikte
Die Logs zeigen erfolgreiche Logins:
```
[2025-07-07 11:10:25] production.INFO: === AUTH EVENT: LOGIN SUCCESS === 
{"guard":"portal","user":"demo@example.com","user_id":23,"remember":false,
"session_id":"cI6rqfVdC3pijt3PU6YihbyqX9kstJOwO5KR4Qp1","session_regenerated":true}
```

Aber die Session-Datenbank zeigt `user_id: NULL` für diese Session!

## 4. JavaScript/Frontend Probleme

### portal-auth-debug.html Analyse
Die Debug-Seite zeigt mehrere Test-Endpunkte:
- `/business/simple-login` - Umgeht CSRF
- `/business/api/check-auth` - Prüft Auth-Status
- `/business/api/session-debug` - Debug-Informationen

### Problem: AJAX Session-Handling
```javascript
credentials: 'include' // Wichtig für Cookie-Übertragung
```

## 5. Konkrete Lösungsvorschläge

### Sofortmaßnahmen

#### 1. Session-Domain korrigieren
```bash
# In .env ändern:
SESSION_DOMAIN=.askproai.de  # Mit Punkt für Subdomain-Unterstützung
# ODER komplett entfernen für automatische Erkennung:
# SESSION_DOMAIN=
```

#### 2. Session-Driver prüfen
```bash
# Prüfen ob die sessions-Tabelle korrekt funktioniert
php artisan session:table
php artisan migrate
```

#### 3. Cache leeren
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

#### 4. Test-Script für Session-Debugging
```php
<?php
// test-session-issue.php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Session-Konfiguration prüfen
echo "Session Config:\n";
echo "Driver: " . config('session.driver') . "\n";
echo "Domain: " . config('session.domain') . "\n";
echo "Secure: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "Cookie: " . config('session.cookie') . "\n\n";

// Datenbank-Sessions prüfen
$sessions = DB::table('sessions')->orderBy('last_activity', 'desc')->limit(5)->get();
echo "Recent Sessions:\n";
foreach ($sessions as $session) {
    echo "ID: {$session->id}, User: {$session->user_id}, Activity: " . date('Y-m-d H:i:s', $session->last_activity) . "\n";
}
```

### Langfristige Lösungen

#### 1. Einheitliche Session-Verwaltung
- Beide Portale sollten denselben Session-Driver verwenden
- Middleware-Stack vereinheitlichen

#### 2. CORS-Headers für API
```php
// Neue Middleware: PortalApiCors.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('Access-Control-Allow-Origin', 'https://api.askproai.de');
    $response->headers->set('Access-Control-Allow-Credentials', 'true');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization');
    
    return $response;
}
```

#### 3. Session-Debugging Middleware
```php
// Neue Middleware: SessionDebugMiddleware.php
public function handle($request, Closure $next)
{
    \Log::debug('Session Debug', [
        'url' => $request->fullUrl(),
        'session_id' => session()->getId(),
        'has_user' => Auth::check(),
        'guard' => Auth::getDefaultDriver(),
        'user_id' => Auth::id(),
        'session_data' => session()->all()
    ]);
    
    return $next($request);
}
```

## 6. Vermutete Hauptursache

Das Hauptproblem scheint die **Session-Domain-Konfiguration** zu sein. Die Änderung von `.askproai.de` auf `api.askproai.de` hat wahrscheinlich dazu geführt, dass:

1. Alte Sessions ungültig wurden
2. Neue Sessions nicht korrekt zwischen Requests geteilt werden
3. Die Guards (web/portal) unterschiedliche Session-Namespaces verwenden

## 7. Empfohlene Sofortmaßnahmen

1. **SESSION_DOMAIN auf `.askproai.de` zurücksetzen oder entfernen**
2. **Cache komplett leeren**
3. **PHP-FPM neustarten**
4. **Test mit dem Debug-Tool durchführen**

```bash
# Ausführen in dieser Reihenfolge:
sed -i 's/SESSION_DOMAIN=api.askproai.de/SESSION_DOMAIN=.askproai.de/' .env
php artisan config:clear
php artisan cache:clear
sudo systemctl restart php8.3-fpm
```

## 8. Monitoring

Nach den Änderungen sollten folgende Logs überwacht werden:
```bash
tail -f storage/logs/laravel.log | grep -E "(AUTH EVENT|Session|CSRF)"
```

Die Session-Datenbank sollte korrekte user_id Werte zeigen:
```sql
SELECT id, user_id, ip_address, user_agent, last_activity 
FROM sessions 
WHERE user_id IS NOT NULL 
ORDER BY last_activity DESC;
```