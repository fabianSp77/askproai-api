# Redirect-Analyse Report - AskProAI

## Zusammenfassung
Nach umfassender Analyse der gesamten Codebase wurden mehrere potentielle Ursachen für unerwartete Redirects zum Dashboard identifiziert.

## Gefundene Probleme

### 1. **Explizite Dashboard-Redirects**

#### `/var/www/api-gateway/routes/web.php` (Zeile 19-22)
```php
Route::get('/dashboard', function () {
    return redirect('/admin');
})->middleware(['auth'])->name('dashboard');
```
**Problem**: Die Route `/dashboard` leitet immer zu `/admin` weiter.

#### `/var/www/api-gateway/app/Filament/Admin/Pages/OnboardingWizard.php`
- **Zeile 65-67**: Redirect bei fehlendem Company-Objekt
```php
if (!$company) {
    session()->flash('error', 'Sie haben kein zugeordnetes Unternehmen.');
    redirect()->route('filament.admin.pages.dashboard');
    return;
}
```
- **Zeile 75-77**: Redirect wenn Onboarding abgeschlossen
```php
if ($progress['is_completed']) {
    redirect()->route('filament.admin.pages.dashboard');
}
```
- **Zeile 652 & 775**: Redirect nach Onboarding-Abschluss
```php
->action(fn () => redirect()->route('filament.admin.pages.simple-dashboard'))
redirect()->route('filament.admin.pages.simple-dashboard');
```

#### `/var/www/api-gateway/app/Filament/Admin/Pages/ErrorFallback.php` (Zeile 40)
```php
public function goToDashboard(): void
{
    $this->redirect('/admin');
}
```

### 2. **Livewire-bezogene Probleme**

#### `/var/www/api-gateway/app/Http/Middleware/LivewireDebugMiddleware.php`
- Protokolliert Livewire-Redirects
- Zeile 31-37: Erkennt und loggt Livewire-Redirects

#### `/var/www/api-gateway/app/Http/Middleware/FixLivewireIssues.php`
- Zeile 19-24: Blockiert GET-Requests zu `/livewire/update`
- Zeile 27-29: Stellt sicher, dass Session existiert

#### `/var/www/api-gateway/public/js/error-handler.js`
- Zeile 43-45: Verhindert Standard-Redirect-Verhalten bei Livewire-Fehlern
- Zeile 61-72: Überwacht URL-Änderungen alle 500ms

### 3. **CSRF-Token Probleme**

#### `/var/www/api-gateway/app/Http/Middleware/VerifyCsrfToken.php` (Zeile 20)
```php
// Temporarily exclude admin login to fix CSRF issue
'admin/login',
```
**Problem**: Admin-Login ist von CSRF-Prüfung ausgenommen (temporär)

#### `/var/www/api-gateway/app/Http/Middleware/FixCsrfToken.php`
- Regeneriert fehlende oder abgelaufene Sessions

### 4. **Session/Cookie-Handling**

#### `/var/www/api-gateway/bootstrap/app.php`
- Zeile 36: `LivewireDebugMiddleware` global hinzugefügt
- Zeile 52-59: API-Middleware-Konfiguration

### 5. **Filament-spezifische Konfigurationen**

#### `/var/www/api-gateway/app/Filament/Admin/Pages/SimpleDashboard.php`
- Zeile 13: `protected static string $routePath = '/';`
- Dashboard ist als Root-Route konfiguriert

#### `/var/www/api-gateway/app/Providers/Filament/AdminPanelProvider.php`
- Zeile 66: SimpleDashboard als Standard-Page definiert
- Zeile 54: SPA-Modus deaktiviert (`->spa(false)`)

### 6. **Resource-spezifische Redirects**

#### CalcomEventType Resource Pages
- `/var/www/api-gateway/app/Filament/Admin/Resources/CalcomEventTypeResource/Pages/EditCalcomEventType.php` (Zeile 20-23)
- `/var/www/api-gateway/app/Filament/Admin/Resources/CalcomEventTypeResource/Pages/CreateCalcomEventType.php` (Zeile 12-15)
```php
protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
```

### 7. **JavaScript-basierte Redirects**

#### `/var/www/api-gateway/public/js/error-handler.js`
- Überwacht und protokolliert alle Navigation-Events
- Hooks in `history.pushState` und `history.replaceState`
- Livewire-Error-Handler kann Redirects verhindern

### 8. **Debug-Middleware**

#### `/var/www/api-gateway/app/Http/Middleware/DebugRedirects.php`
- Loggt alle 301/302 Redirects mit Stack-Trace
- Erfasst Session-Errors und Livewire-Status

#### `/var/www/api-gateway/app/Http/Middleware/DebugAllRequests.php`
- Loggt alle eingehenden Requests und Redirects

## Empfohlene Lösungsansätze

### Sofortmaßnahmen:
1. **CSRF-Token Fix**: Entfernen Sie die temporäre Ausnahme für `/admin/login` in `VerifyCsrfToken.php`
2. **Onboarding-Logik**: Überprüfen Sie die Company-Zuordnung in `OnboardingWizard.php`
3. **Route-Konflikt**: Prüfen Sie ob `/dashboard` Route entfernt werden kann

### Debugging-Schritte:
1. Aktivieren Sie alle Debug-Middleware temporär
2. Prüfen Sie die Logs in `storage/logs/laravel.log` für Redirect-Muster
3. Nutzen Sie Browser DevTools Network-Tab für 302-Responses

### Langfristige Verbesserungen:
1. Implementieren Sie einheitliche Redirect-Logik
2. Zentralisieren Sie Dashboard-URLs in einer Config
3. Verbessern Sie Error-Handling für fehlende Company-Zuordnungen

## Wichtigste Verdächtige

1. **OnboardingWizard Company-Check** (Zeilen 65-67)
2. **CSRF-Token Probleme** bei Admin-Login
3. **Livewire Session-Handling** 
4. **Route-Konflikt** zwischen `/dashboard` und `/admin`

Diese Probleme sollten in der angegebenen Reihenfolge untersucht und behoben werden.