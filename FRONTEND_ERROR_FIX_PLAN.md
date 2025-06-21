# Frontend Error Fix Plan - AskProAI

## Übersicht der Fehlerquellen

### 1. Promise-Fehler (Kritisch)
- **Problem**: Unhandled Promise Rejections auf mehreren Admin-Seiten
- **Betroffene Seiten**: `/admin/calls`, `/admin/customers`, `/admin/appointments`
- **Ursache**: Fehlende `.catch()` Handler bei asynchronen Operationen

### 2. Navigation/Redirect-Fehler (Mittel)
- **Problem**: Unerwartete Weiterleitungen beim Tab-Wechsel
- **Ursache**: Livewire-Navigation Konflikte oder Session-Probleme
- **Symptom**: Redirect-Loops bei Tab-Wechsel

### 3. Backend-Frontend Type Mismatches (Hoch)
- **Problem**: Backend sendet falsche Datentypen
- **Beispiele**: 
  - `RoiCalculationService` sendet stdClass statt Array
  - `LiveActivityFeedWidget` erhält null statt int
  - `CircuitBreaker` erhält string statt int

## Implementierungsplan

### Phase 1: Sentry Frontend Integration (2 Stunden)

#### 1.1 Sentry Browser SDK einrichten
```bash
npm install @sentry/browser @sentry/integrations
```

#### 1.2 Create Sentry Initialization File
`resources/js/sentry-config.js`:
```javascript
import * as Sentry from '@sentry/browser';
import { BrowserTracing } from '@sentry/tracing';

export function initSentry() {
    if (window.SENTRY_DSN) {
        Sentry.init({
            dsn: window.SENTRY_DSN,
            environment: window.APP_ENV || 'production',
            integrations: [
                new BrowserTracing({
                    routingInstrumentation: Sentry.reactRouterV6Instrumentation(
                        window.history
                    ),
                    tracingOrigins: [window.location.hostname],
                }),
            ],
            tracesSampleRate: window.APP_ENV === 'production' ? 0.1 : 1.0,
            beforeSend(event, hint) {
                // Filter out known non-critical errors
                if (event.exception) {
                    const error = hint.originalException;
                    // Skip Livewire reconnection errors
                    if (error?.message?.includes('Livewire connection')) {
                        return null;
                    }
                }
                return event;
            },
        });

        // Capture user context
        const userId = document.querySelector('meta[name="user-id"]')?.content;
        if (userId) {
            Sentry.setUser({ id: userId });
        }
    }
}
```

#### 1.3 Update app.blade.php
```blade
@push('scripts')
<script>
    window.SENTRY_DSN = "{{ config('sentry.dsn') }}";
    window.APP_ENV = "{{ config('app.env') }}";
</script>
<script type="module">
    import { initSentry } from '/js/sentry-config.js';
    initSentry();
</script>
@endpush
```

### Phase 2: Global Error Handlers (1 Stunde)

#### 2.1 Enhanced Error Handler
`resources/js/error-handler.js`:
```javascript
class ErrorHandler {
    constructor() {
        this.setupGlobalHandlers();
        this.setupLivewireHandlers();
        this.setupPromiseHandlers();
        this.errorQueue = [];
        this.flushErrors();
    }

    setupGlobalHandlers() {
        window.addEventListener('error', (event) => {
            this.handleError({
                type: 'javascript-error',
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error,
                stack: event.error?.stack
            });
        });
    }

    setupPromiseHandlers() {
        window.addEventListener('unhandledrejection', (event) => {
            this.handleError({
                type: 'unhandled-promise-rejection',
                reason: event.reason,
                promise: event.promise,
                stack: event.reason?.stack || new Error().stack
            });
            
            // Prevent default browser behavior
            event.preventDefault();
        });
    }

    setupLivewireHandlers() {
        if (window.Livewire) {
            // Handle Livewire errors
            Livewire.onError((message, statusCode) => {
                this.handleError({
                    type: 'livewire-error',
                    message: message,
                    statusCode: statusCode
                });

                // Handle specific status codes
                if (statusCode === 419) {
                    // CSRF token expired
                    this.refreshCSRFToken();
                } else if (statusCode === 401) {
                    // Session expired
                    window.location.href = '/login';
                }
                
                // Prevent default Livewire error handling
                return false;
            });

            // Track navigation issues
            let navigationCount = 0;
            Livewire.hook('message.processed', (message, component) => {
                navigationCount++;
                if (navigationCount > 10 && performance.now() < 1000) {
                    this.handleError({
                        type: 'livewire-navigation-loop',
                        message: 'Possible navigation loop detected',
                        component: component.name
                    });
                }
            });
        }
    }

    handleError(errorData) {
        // Add context
        errorData.url = window.location.href;
        errorData.userAgent = navigator.userAgent;
        errorData.timestamp = new Date().toISOString();
        errorData.viewport = {
            width: window.innerWidth,
            height: window.innerHeight
        };

        // Send to Sentry if available
        if (window.Sentry) {
            Sentry.captureException(new Error(errorData.message), {
                contexts: {
                    error_details: errorData
                }
            });
        }

        // Queue for backend logging
        this.errorQueue.push(errorData);
    }

    async flushErrors() {
        if (this.errorQueue.length === 0) {
            setTimeout(() => this.flushErrors(), 5000);
            return;
        }

        const errors = [...this.errorQueue];
        this.errorQueue = [];

        try {
            await fetch('/api/log-frontend-error', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify({ errors })
            });
        } catch (e) {
            // Re-queue on failure
            this.errorQueue.unshift(...errors);
        }

        setTimeout(() => this.flushErrors(), 5000);
    }

    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async refreshCSRFToken() {
        try {
            const response = await fetch('/api/csrf-token');
            const data = await response.json();
            document.querySelector('meta[name="csrf-token"]').content = data.token;
            
            // Update Livewire's CSRF token
            if (window.Livewire) {
                Livewire.connection.driver.csrf = data.token;
            }
        } catch (e) {
            console.error('Failed to refresh CSRF token:', e);
        }
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.errorHandler = new ErrorHandler();
});
```

### Phase 3: Fix Backend Type Issues (2 Stunden)

#### 3.1 Fix RoiCalculationService
```php
// app/Services/Analytics/RoiCalculationService.php
public function getCallMetrics(): array
{
    $result = DB::table('calls')
        ->select([
            DB::raw('COUNT(*) as total_calls'),
            DB::raw('AVG(duration_minutes) as avg_duration'),
            DB::raw('SUM(cost) as total_cost')
        ])
        ->where('company_id', $this->companyId)
        ->first();
    
    // Fix: Convert stdClass to array
    return $result ? (array) $result : [
        'total_calls' => 0,
        'avg_duration' => 0,
        'total_cost' => 0
    ];
}
```

#### 3.2 Fix LiveActivityFeedWidget
```php
// app/Filament/Admin/Widgets/LiveActivityFeedWidget.php
protected function formatDuration(?int $seconds): string
{
    // Fix: Handle null values
    if ($seconds === null || $seconds <= 0) {
        return '0s';
    }
    
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    
    if ($minutes > 0) {
        return sprintf('%dm %ds', $minutes, $remainingSeconds);
    }
    
    return sprintf('%ds', $seconds);
}
```

#### 3.3 Fix CircuitBreaker Type Issue
```php
// app/Services/CalcomV2Service.php
public function __construct()
{
    $this->circuitBreaker = new CircuitBreaker(
        failureThreshold: (int) config('calcom-v2.circuit_breaker_threshold', 5),
        successThreshold: 2,
        timeout: 60
    );
}
```

### Phase 4: Livewire Component Fixes (3 Stunden)

#### 4.1 Create Base Livewire Component with Error Handling
```php
// app/Livewire/BaseComponent.php
namespace App\Livewire;

use Livewire\Component;

abstract class BaseComponent extends Component
{
    protected function handleError(\Throwable $e, string $userMessage = 'Ein Fehler ist aufgetreten')
    {
        logger()->error('Livewire component error', [
            'component' => static::class,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => $userMessage
        ]);
        
        if (app()->environment('local')) {
            throw $e;
        }
    }
    
    protected function safeExecute(callable $callback, string $errorMessage = 'Operation fehlgeschlagen')
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            $this->handleError($e, $errorMessage);
            return null;
        }
    }
}
```

#### 4.2 Fix Tab Navigation Issues
```javascript
// resources/js/tab-navigation-fix.js
document.addEventListener('DOMContentLoaded', function() {
    // Prevent multiple rapid tab switches
    let isNavigating = false;
    
    document.addEventListener('click', function(e) {
        const tabLink = e.target.closest('[wire\\:click*="activeTab"]');
        if (tabLink && isNavigating) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        if (tabLink) {
            isNavigating = true;
            setTimeout(() => { isNavigating = false; }, 1000);
        }
    });
    
    // Fix browser back/forward with tabs
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.activeTab) {
            Livewire.emit('setActiveTab', e.state.activeTab);
        }
    });
});
```

### Phase 5: Testing & Monitoring (1 Stunde)

#### 5.1 Create Frontend Test Page
```php
// app/Http/Controllers/FrontendTestController.php
class FrontendTestController extends Controller
{
    public function testErrors()
    {
        return view('admin.test-errors');
    }
}
```

```blade
{{-- resources/views/admin/test-errors.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Frontend Error Testing</h1>
    
    <div class="space-y-4">
        <button onclick="triggerJSError()" class="btn btn-danger">
            Trigger JS Error
        </button>
        
        <button onclick="triggerPromiseRejection()" class="btn btn-danger">
            Trigger Promise Rejection
        </button>
        
        <button onclick="triggerLivewireError()" class="btn btn-danger">
            Trigger Livewire Error
        </button>
        
        <button onclick="triggerTypeError()" class="btn btn-danger">
            Trigger Type Error
        </button>
    </div>
    
    <div id="error-log" class="mt-8 p-4 bg-gray-100 rounded">
        <h2 class="font-bold">Error Log:</h2>
        <pre id="log-content"></pre>
    </div>
</div>

<script>
function triggerJSError() {
    throw new Error('Test JavaScript Error');
}

function triggerPromiseRejection() {
    Promise.reject('Test Promise Rejection');
}

function triggerLivewireError() {
    Livewire.emit('nonExistentMethod');
}

function triggerTypeError() {
    const obj = null;
    obj.someMethod();
}

// Log errors locally
window.addEventListener('error', (e) => {
    document.getElementById('log-content').textContent += 
        `\n[ERROR] ${e.message} at ${e.filename}:${e.lineno}`;
});
</script>
@endsection
```

### Phase 6: Deployment & Rollout

#### 6.1 Deployment Checklist
- [ ] Sentry DSN in `.env` konfiguriert
- [ ] Frontend Build mit neuen Scripts
- [ ] Backend Fixes deployed
- [ ] Error Handler auf allen Seiten aktiv
- [ ] Test Page funktioniert
- [ ] Monitoring Dashboard eingerichtet

#### 6.2 Monitoring Setup
```bash
# Create monitoring command
php artisan make:command MonitorFrontendErrors

# Schedule in Kernel.php
$schedule->command('monitor:frontend-errors')->hourly();
```

## Zeitplan

1. **Tag 1** (4 Stunden):
   - Sentry Integration
   - Global Error Handlers
   - Backend Type Fixes

2. **Tag 2** (4 Stunden):
   - Livewire Component Fixes
   - Testing Setup
   - Deployment

3. **Tag 3** (2 Stunden):
   - Monitoring
   - Fine-tuning
   - Documentation

## Success Metrics

- Frontend Error Rate < 0.1%
- Keine unhandled Promise Rejections
- Keine Navigation Loops
- 100% Type Safety in Backend Responses
- Alle Errors in Sentry erfasst

## Rollback Plan

Falls Probleme auftreten:
1. Error Handler deaktivieren via Feature Flag
2. Sentry Integration pausieren
3. Zu vorherigen Livewire Components zurück
4. Backend Fixes einzeln rückgängig machen