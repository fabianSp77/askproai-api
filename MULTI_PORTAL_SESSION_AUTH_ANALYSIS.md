# Multi-Portal Session & Auth Architektur Analyse

## 🔴 Kritische Erkenntnisse

### 1. Session Cookie Kollision
```php
// PROBLEM: Alle Portale nutzen denselben Cookie Namen
// config/session.php
'cookie' => 'askproai_session'  // Wird von ALLEN Portalen geteilt!

// config/session_admin.php und session_portal.php
// Diese werden NICHT richtig geladen!
```

### 2. TenantScope verliert Company Context
```php
// app/Scopes/TenantScope.php
public function apply(Builder $builder, Model $model)
{
    // Problem: Verlässt sich auf aktuelle Auth Guard
    if (Auth::guard('portal')->check()) {
        $user = Auth::guard('portal')->user();
        // Beim Portal-Wechsel ist die falsche Guard aktiv!
    }
}
```

### 3. CSRF Token Sharing
```php
// Alle Portale teilen sich denselben Token!
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'admin/*',      // GEFÄHRLICH: Komplette CSRF-Deaktivierung
    'livewire/*',   // Verursacht 419 Errors
];
```

### 4. AdminAccessController Session Pollution
```php
// app/Http/Controllers/Portal/AdminAccessController.php
public function login($userId)
{
    // Setzt Sessions ohne alte zu löschen
    session(['admin_impersonation' => true]);
    session(['original_admin_id' => Auth::id()]);
    // Alte Portal-Session bleibt aktiv!
}
```

## 🎯 Root Cause Analyse

### Haupt-Problem: Session State Sharing

1. **Gleicher Session Store**: Alle Portale schreiben in `storage/framework/sessions/`
2. **Gleicher Cookie Name**: `askproai_session` wird überall verwendet
3. **Session-Daten vermischen sich**: Admin-Daten überschreiben Portal-Daten

### Sekundäre Probleme:

1. **Middleware Reihenfolge**: Session wird initialisiert BEVOR Portal erkannt wird
2. **Guard Konflikte**: Mehrere Guards können gleichzeitig authentifiziert sein
3. **Tenant Context**: Geht verloren beim Guard-Wechsel

## 💡 Lösungskonzept

### Option 1: Session Namespace Isolation (Empfohlen)

```php
// 1. Neue Middleware: PortalSessionIsolation
class PortalSessionIsolation
{
    public function handle($request, $next)
    {
        $portal = $this->detectPortal($request);
        
        // Setze portal-spezifische Session Config
        config([
            'session.cookie' => $portal . '_session',
            'session.table' => 'sessions_' . $portal,
            'session.prefix' => $portal . '_'
        ]);
        
        return $next($request);
    }
}

// 2. Separate Session Tables
Schema::create('sessions_admin', ...);
Schema::create('sessions_portal', ...);
Schema::create('sessions_business', ...);
```

### Option 2: Multi-Domain Setup

```php
// Separate Domains für Portale
admin.askproai.de    -> Admin Portal
portal.askproai.de   -> Business Portal
app.askproai.de      -> Haupt-App

// Jede Domain hat eigene Cookies
```

### Option 3: JWT-basierte Authentifizierung

```php
// Keine Sessions, nur Tokens
// Jedes Portal hat eigenen Token-Prefix
// admin_token_xxx, portal_token_xxx
```

## 🛠️ Sofort-Fixes

### 1. Session Flush beim Portal-Wechsel
```php
// AdminAccessController.php
public function switchToPortal($userId)
{
    // 1. Speichere notwendige Daten
    $adminId = Auth::id();
    $companyId = session('company_id');
    
    // 2. Kompletter Session Reset
    Auth::logout();
    session()->flush();
    session()->regenerate();
    
    // 3. Neue Session mit Portal Context
    session(['portal_type' => 'business']);
    session(['switched_from_admin' => true]);
    session(['admin_id' => $adminId]);
    
    // 4. Login als Portal User
    Auth::guard('portal')->login($user);
}
```

### 2. Portal-aware CSRF
```php
// Neue Middleware: PortalAwareCSRF
class PortalAwareCSRF extends VerifyCsrfToken
{
    protected function tokensMatch($request)
    {
        $portal = $this->detectPortal($request);
        $sessionToken = session($portal . '_token');
        
        // Portal-spezifischer Token
        return hash_equals($sessionToken, $request->input('_token'));
    }
}
```

### 3. Fix TenantScope
```php
// Expliziter Company Context
class TenantScope
{
    public function apply(Builder $builder, Model $model)
    {
        // Nicht auf Auth verlassen, sondern Session
        $companyId = session('active_company_id');
        
        if (!$companyId) {
            // Fallback auf Auth
            $companyId = $this->getCompanyFromAuth();
        }
        
        if ($companyId) {
            $builder->where('company_id', $companyId);
        }
    }
}
```

## 📋 Implementierungs-Roadmap

### Phase 1: Quick Fixes (1-2 Tage)
1. ✅ Session Flush beim Portal-Wechsel
2. ✅ Expliziter Company Context in Session
3. ✅ CSRF Token Regenerierung

### Phase 2: Session Isolation (3-5 Tage)
1. 🔲 PortalSessionIsolation Middleware
2. 🔲 Separate Session Tables
3. 🔲 Portal-spezifische Cookies

### Phase 3: Vollständige Trennung (1-2 Wochen)
1. 🔲 Multi-Domain Setup
2. 🔲 JWT Authentication
3. 🔲 Komplett getrennte Auth Guards

## 🚨 Sofort-Maßnahmen

1. **CSRF komplett deaktivieren** für Admin (temporär)
2. **Session Flush** bei jedem Portal-Wechsel
3. **Company ID** explizit in Session speichern
4. **Monitoring** für Session-Konflikte einrichten