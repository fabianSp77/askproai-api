# Livewire 3 & Filament 3 - Code Reference Guide

## 1. Component Discovery Code Snippets

### A. AdminPanelProvider Component Discovery
**File**: `/var/www/api-gateway/app/Providers/Filament/AdminPanelProvider.php` (lines 50-52)

```php
->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
```

**What it does**:
- Scans directories for component classes
- Registers them with Livewire for initialization
- Makes them available for use in the panel

---

### B. RenderHooks Registration
**File**: `/var/www/api-gateway/app/Providers/Filament/AdminPanelProvider.php` (lines 37-47)

```php
->renderHook(
    'panels::head.end',
    fn (): string => Vite::useHotFile(public_path('hot'))
        ->useBuildDirectory('build')
        ->withEntryPoints(['resources/css/call-detail-full-width.css'])
        ->toHtml()
)
->renderHook(
    'panels::scripts.after',
    fn (): string => '<script>Livewire.start()</script>'
)
```

**What it does**:
- `panels::head.end`: Injects CSS at end of HEAD
- `panels::scripts.after`: Initializes Livewire after all scripts loaded

---

### C. Component Registry Logic
**File**: `/var/www/api-gateway/vendor/livewire/livewire/src/Mechanisms/ComponentRegistry.php`

#### Class to Name Conversion
```php
protected function generateNameFromClass($class)
{
    // Input: App\Livewire\Forms\EditUserForm
    // Process:
    //   1. Remove 'App\Livewire' prefix
    //   2. Replace backslashes with dots
    //   3. Convert CamelCase to kebab-case
    //   4. Remove .index suffix if exists
    // Output: forms.edit-user-form
}
```

#### Name to Class Conversion
```php
protected function generateClassFromName($name)
{
    // Input: forms.edit-user-form
    // Process:
    //   1. Split by dots: ['forms', 'edit-user-form']
    //   2. Convert each to StudlyCase: ['Forms', 'EditUserForm']
    //   3. Add root namespace: App\Livewire\Forms\EditUserForm
    // Output: App\Livewire\Forms\EditUserForm
}
```

---

## 2. Component Discovery Methods

### A. Filament Discovery
**File**: `/var/www/api-gateway/vendor/filament/filament/src/Panel/Concerns/HasComponents.php`

#### discoverResources()
```php
public function discoverResources(string $in, string $for): static
{
    if ($this->hasCachedComponents()) {
        return $this;
    }

    $this->resourceDirectories[] = $in;
    $this->resourceNamespaces[] = $for;

    $this->discoverComponents(
        Resource::class,
        $this->resources,
        directory: $in,
        namespace: $for,
    );

    return $this;
}
```

#### discoverPages()
```php
public function discoverPages(string $in, string $for): static
{
    if ($this->hasCachedComponents()) {
        return $this;
    }

    $this->pageDirectories[] = $in;
    $this->pageNamespaces[] = $for;

    $this->discoverComponents(
        Page::class,
        $this->pages,
        directory: $in,
        namespace: $for,
    );

    return $this;
}
```

#### discoverWidgets()
```php
public function discoverWidgets(string $in, string $for): static
{
    if ($this->hasCachedComponents()) {
        return $this;
    }

    $this->widgetDirectories[] = $in;
    $this->widgetNamespaces[] = $for;

    $this->discoverComponents(
        Widget::class,
        $this->widgets,
        directory: $in,
        namespace: $for,
    );

    return $this;
}
```

### B. Generic Discovery Logic
```php
protected function discoverComponents(
    string $baseClass,
    array &$register,
    ?string $directory,
    ?string $namespace
): void {
    if (blank($directory) || blank($namespace)) {
        return;
    }

    $filesystem = app(Filesystem::class);

    if ((! $filesystem->exists($directory)) && (! str($directory)->contains('*'))) {
        return;
    }

    foreach ($filesystem->allFiles($directory) as $file) {
        $class = // ... construct class name ...

        if (! class_exists($class)) {
            continue;
        }

        if ((new ReflectionClass($class))->isAbstract()) {
            continue;
        }

        // Queue for Livewire registration if it's a Component
        if (is_subclass_of($class, Component::class)) {
            $this->queueLivewireComponentForRegistration($class);
        }

        // Register if it matches the base class
        if (! is_subclass_of($class, $baseClass)) {
            continue;
        }

        $register[$file->getRealPath()] = $class;
    }
}
```

---

## 3. Livewire Component Registration

### A. Queue for Registration
**File**: `/var/www/api-gateway/vendor/filament/filament/src/Panel/Concerns/HasComponents.php` (line 584)

```php
protected function queueLivewireComponentForRegistration(string $component): void
{
    $componentName = app(ComponentRegistry::class)->getName($component);
    
    $this->livewireComponents[$componentName] = $component;
}
```

### B. Register with Livewire
**File**: `/var/www/api-gateway/vendor/filament/filament/src/Panel/Concerns/HasComponents.php` (line 482)

```php
protected function registerLivewireComponents(): void
{
    // Register built-in Filament components
    if (! $this->hasCachedComponents()) {
        $this->queueLivewireComponentForRegistration(DatabaseNotifications::class);
        $this->queueLivewireComponentForRegistration(EditProfile::class);
        $this->queueLivewireComponentForRegistration(GlobalSearch::class);
        $this->queueLivewireComponentForRegistration(Notifications::class);

        // ... more built-in components ...
    }

    // Register all queued components with Livewire
    foreach ($this->livewireComponents as $componentName => $componentClass) {
        Livewire::component($componentName, $componentClass);
    }
}
```

---

## 4. Wire:Snapshot System

### A. Snapshot HTML Attributes

```html
<div wire:snapshot="{
    &quot;data&quot;:{...},
    &quot;memo&quot;:{...},
    &quot;checksum&quot;:&quot;...&quot;
}"
wire:effects="{...}"
wire:id="component-id-123"
x-data="{}"
>
    <!-- Component content -->
</div>
```

### B. Snapshot Contents Structure

```json
{
  "data": {
    "publicProperty1": "value1",
    "publicProperty2": 123,
    "nested": {
      "property": "value"
    }
  },
  "memo": {
    "id": "unique-component-id",
    "name": "component.name.path",
    "path": "admin/appointments/create",
    "method": "GET",
    "children": [],
    "scripts": [],
    "assets": [],
    "errors": [],
    "locale": "de"
  },
  "checksum": "hash-of-component-state"
}
```

### C. Snapshot Processing (JavaScript)

```javascript
// Livewire.start() performs these steps:
// 1. Scan DOM for [wire:snapshot] attributes
// 2. For each snapshot:
//    a. Parse JSON
//    b. Validate checksum
//    c. Create component object
//    d. Restore state from 'data'
//    e. Attach event listeners
//    f. Mark component ready
```

---

## 5. Configuration Files

### A. Livewire Configuration
**File**: `/var/www/api-gateway/config/livewire.php`

```php
return [
    'class_namespace' => 'App\\Livewire',              // Component namespace
    'view_path' => resource_path('views/livewire'),   // View directory
    'layout' => 'components.layouts.app',              // Default layout
    'lazy_placeholder' => null,                        // Lazy load placeholder
    'temporary_file_upload' => [
        'disk' => null,
        'rules' => null,
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => ['png', 'gif', 'bmp', 'svg', ...],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
    'render_on_redirect' => false,                     // Re-render on redirect
    'legacy_model_binding' => false,                   // Don't use magic binding
    'inject_assets' => true,                           // Auto-inject JS/CSS
    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#2299dd',
    ],
    'inject_morph_markers' => true,                    // HTML morphing markers
    'pagination_theme' => 'tailwind',                  // Pagination style
];
```

### B. Filament Configuration
**File**: `/var/www/api-gateway/config/filament.php`

```php
return [
    'broadcasting' => [
        // 'echo' => [ ... ]  // Pusher config (disabled)
    ],
    'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),
    'assets_path' => null,  // Location of Filament assets
    'cache_path' => base_path('bootstrap/cache/filament'),  // Component cache
    'livewire_loading_delay' => 'default',  // 200ms delay for loading indicators
    'system_route_prefix' => 'filament',    // Prefix for system routes
];
```

---

## 6. Middleware Stack

### A. Filament Middleware Configuration
**File**: `/var/www/api-gateway/app/Providers/Filament/AdminPanelProvider.php` (lines 60-73)

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
])
->authMiddleware([
    Authenticate::class,
])
```

### B. Middleware Registration
**File**: `/var/www/api-gateway/bootstrap/app.php` (line 23-54)

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\PerformanceMonitoring::class);
    $middleware->append(\App\Http\Middleware\ErrorCatcher::class);

    $middleware->alias([
        'rate.limit' => \App\Http\Middleware\RateLimiting::class,
        'stripe.webhook' => \App\Http\Middleware\VerifyStripeWebhookSignature::class,
        'retell.webhook' => \App\Http\Middleware\VerifyRetellWebhookSignature::class,
        'retell.function' => \App\Http\Middleware\VerifyRetellFunctionSignature::class,
        'calcom.signature' => \App\Http\Middleware\VerifyCalcomSignature::class,
        'auth' => \App\Http\Middleware\Authenticate::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        // ... more aliases
    ]);
})
```

---

## 7. Service Provider Integration

### A. AppServiceProvider
**File**: `/var/www/api-gateway/app/Providers/AppServiceProvider.php`

```php
public function register(): void
{
    // Bind interfaces to implementations
    $this->app->bind(
        AvailabilityServiceInterface::class,
        WeeklyAvailabilityService::class
    );
    $this->app->bind(
        BookingServiceInterface::class,
        BookingService::class
    );
}

public function boot(): void
{
    // Set locales
    Carbon::setLocale('de');
    Number::useLocale('de');

    // Register middleware aliases
    $router = $this->app->make(Router::class);
    $router->aliasMiddleware('calcom.signature', \App\Http\Middleware\VerifyCalcomSignature::class);

    // Register observers
    Service::observe(ServiceObserver::class);
    Appointment::observe(AppointmentObserver::class);
    // ... more observers
}
```

### B. Provider Registration
**File**: `/var/www/api-gateway/bootstrap/providers.php`

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    App\Providers\FilamentColumnOrderingServiceProvider::class,
];
```

---

## 8. Panel Initialization Flow

```
Application Bootstrap
  └── bootstrap/providers.php loaded
      └── AppServiceProvider::register()
          └── Service bindings
      └── AdminPanelProvider::panel()
          ├── discover Resources
          ├── discover Pages
          ├── discover Widgets
          ├── queue for Livewire registration
          └── register RenderHooks
      └── AdminPanelProvider::boot()
          └── Panel::register()
              └── registerLivewireComponents()
                  └── Livewire::component() for each

HTTP Request → Filament Page
  └── Middleware stack applied
  └── Page rendered with wire:snapshot attributes
  └── HTML sent to browser

Browser Loads HTML
  └── Livewire.start() called (via renderHook)
  └── Components hydrated from wire:snapshot
  └── Event listeners attached
  └── Page interactive
```

---

## 9. Component Naming Examples

### A. Class to Name Mapping

```
Class:  App\Filament\Resources\AppointmentResource
Name:   appointment-resource

Class:  App\Filament\Resources\AppointmentResource\Pages\CreateAppointment
Name:   appointment-resource.pages.create-appointment

Class:  App\Filament\Pages\Dashboard
Name:   dashboard

Class:  App\Livewire\Forms\EditUserForm
Name:   forms.edit-user-form

Class:  App\Livewire\Components\UserTable\Index
Name:   user-table  (strips .index suffix)
```

### B. Name to Class Mapping

```
Name:   appointment-resource
Class:  App\Filament\Resources\AppointmentResource

Name:   dashboard
Class:  App\Filament\Pages\Dashboard

Name:   forms.edit-user-form
Class:  App\Livewire\Forms\EditUserForm

Name:   user-table
Class:  App\Livewire\Components\UserTable\Index
```

---

## 10. Cache Management

### A. Generate Cache
```bash
php artisan filament:cache-components
```

**Creates**: `bootstrap/cache/filament/panels/admin.php`

### B. Clear Cache
```bash
php artisan filament:clear-cached-components
```

### C. Cache Contents
```php
return [
    'livewireComponents' => [...],      // Registered Livewire components
    'clusters' => [...],                // Component clusters
    'clusteredComponents' => [...],     // Grouped components
    'pages' => [...],                   // Discovered pages
    'resources' => [...],               // Discovered resources
    'widgets' => [...],                 // Discovered widgets
    'pageDirectories' => [...],         // Page scan directories
    'resourceDirectories' => [...],     // Resource scan directories
    'widgetDirectories' => [...],       // Widget scan directories
];
```

---

## References

- Full Analysis: `LIVEWIRE_FILAMENT_INITIALIZATION_ANALYSIS.md`
- Executive Summary: `LIVEWIRE_INITIALIZATION_SUMMARY.md`
- Livewire Docs: https://livewire.laravel.com/
- Filament Docs: https://filamentphp.com/
