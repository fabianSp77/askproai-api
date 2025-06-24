# Sentry Integration

## Overview

Sentry integration provides real-time error tracking, performance monitoring, and alerting for AskProAI. It helps identify, debug, and resolve issues before they impact users.

## Configuration

### Installation
```bash
# Install Sentry Laravel SDK
composer require sentry/sentry-laravel

# Publish configuration
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

### Environment Setup
```bash
# Sentry Configuration
SENTRY_LARAVEL_DSN=https://xxxxx@xxx.ingest.sentry.io/xxxxx
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.1
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=1.0.0

# Environment-specific settings
SENTRY_SEND_DEFAULT_PII=false
SENTRY_BREADCRUMBS_SQL_BINDINGS=true
```

### Configuration File
```php
// config/sentry.php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    
    'release' => env('SENTRY_RELEASE', '1.0.0'),
    
    'environment' => env('SENTRY_ENVIRONMENT', 'production'),
    
    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
        'queue_info' => true,
        'command_info' => true,
    ],
    
    'tracing' => [
        'transaction_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
        'trace_propagation_targets' => [
            'api.askproai.de',
            'retellai.com',
            'cal.com',
        ],
    ],
    
    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),
    
    'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
        // Filter sensitive data
        return app(SentryDataFilter::class)->filter($event, $hint);
    },
];
```

## Error Tracking

### Automatic Error Capture
```php
// App\Exceptions\Handler.php
public function register()
{
    $this->reportable(function (Throwable $e) {
        if ($this->shouldReport($e) && app()->bound('sentry')) {
            app('sentry')->captureException($e);
        }
    });
}
```

### Manual Error Reporting
```php
use Sentry\Laravel\Facades\Sentry;

try {
    $this->riskyOperation();
} catch (\Exception $e) {
    Sentry::captureException($e, [
        'tags' => [
            'component' => 'booking-service',
            'action' => 'create-appointment'
        ],
        'extra' => [
            'appointment_data' => $appointmentData,
            'user_id' => auth()->id()
        ]
    ]);
    
    throw $e;
}
```

### Custom Error Context
```php
class BookingService
{
    public function createAppointment(array $data)
    {
        // Add context for better debugging
        Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($data) {
            $scope->setContext('appointment', [
                'service_id' => $data['service_id'],
                'date' => $data['date'],
                'branch_id' => $data['branch_id']
            ]);
            
            $scope->setTag('booking.type', $data['type'] ?? 'standard');
        });
        
        // Booking logic...
    }
}
```

## Performance Monitoring

### Transaction Tracking
```php
namespace App\Http\Middleware;

use Sentry\Laravel\Facades\Sentry;
use Sentry\Tracing\TransactionContext;

class SentryTransaction
{
    public function handle($request, \Closure $next)
    {
        $transaction = Sentry::startTransaction(
            new TransactionContext('http.request', $request->method() . ' ' . $request->route()->uri())
        );
        
        Sentry::getCurrentHub()->setSpan($transaction);
        
        try {
            $response = $next($request);
            $transaction->setHttpStatus($response->status());
            return $response;
        } finally {
            $transaction->finish();
        }
    }
}
```

### Database Query Monitoring
```php
// Enable query tracking
DB::listen(function ($query) {
    $span = Sentry::getCurrentHub()->getSpan();
    
    if ($span) {
        $querySpan = $span->startChild('db.query');
        $querySpan->setDescription($query->sql);
        $querySpan->setData([
            'db.system' => 'mysql',
            'db.name' => config('database.connections.mysql.database'),
            'time' => $query->time
        ]);
        $querySpan->finish();
    }
});
```

### API Call Monitoring
```php
class CalcomService
{
    public function getAvailability($eventTypeId, $date)
    {
        $span = Sentry::getCurrentHub()->getSpan();
        $httpSpan = $span ? $span->startChild('http.client') : null;
        
        if ($httpSpan) {
            $httpSpan->setDescription('GET cal.com/availability');
            $httpSpan->setData([
                'http.url' => 'https://api.cal.com/v2/availability',
                'http.method' => 'GET',
                'event_type_id' => $eventTypeId
            ]);
        }
        
        try {
            $response = $this->client->get('/availability', [
                'eventTypeId' => $eventTypeId,
                'date' => $date
            ]);
            
            if ($httpSpan) {
                $httpSpan->setHttpStatus(200);
            }
            
            return $response;
        } catch (\Exception $e) {
            if ($httpSpan) {
                $httpSpan->setHttpStatus($e->getCode());
            }
            throw $e;
        } finally {
            $httpSpan?->finish();
        }
    }
}
```

## User Context

### Identifying Users
```php
namespace App\Http\Middleware;

use Sentry\Laravel\Facades\Sentry;

class SentryUserContext
{
    public function handle($request, \Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($user) {
                $scope->setUser([
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->name,
                    'ip_address' => request()->ip(),
                    'company_id' => $user->company_id,
                    'role' => $user->role
                ]);
            });
        }
        
        return $next($request);
    }
}
```

### Company Context
```php
class TenantMiddleware
{
    public function handle($request, \Closure $next)
    {
        if ($company = $this->resolveCompany($request)) {
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($company) {
                $scope->setContext('company', [
                    'id' => $company->id,
                    'name' => $company->name,
                    'plan' => $company->subscription_plan,
                    'created_at' => $company->created_at
                ]);
                
                $scope->setTag('company.plan', $company->subscription_plan);
            });
        }
        
        return $next($request);
    }
}
```

## Custom Integrations

### Webhook Error Tracking
```php
class RetellWebhookController
{
    public function handle(Request $request)
    {
        $transaction = Sentry::startTransaction(
            new TransactionContext('webhook', 'retell.webhook')
        );
        
        try {
            $this->validateSignature($request);
            $this->processWebhook($request->all());
            
            $transaction->setData([
                'webhook.type' => $request->input('event_type'),
                'webhook.call_id' => $request->input('call_id')
            ]);
            
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Sentry::captureException($e, [
                'fingerprint' => ['webhook-error', $e->getCode()],
                'tags' => [
                    'webhook.provider' => 'retell',
                    'webhook.type' => $request->input('event_type')
                ]
            ]);
            
            throw $e;
        } finally {
            $transaction->finish();
        }
    }
}
```

### Queue Job Monitoring
```php
class ProcessRetellCallJob implements ShouldQueue
{
    public function handle()
    {
        $transaction = Sentry::startTransaction(
            new TransactionContext('queue.job', 'process-retell-call')
        );
        
        Sentry::getCurrentHub()->setSpan($transaction);
        
        try {
            // Job logic...
            $transaction->setData(['call_id' => $this->callId]);
        } catch (\Exception $e) {
            Sentry::captureException($e, [
                'tags' => ['queue.job' => 'process-retell-call'],
                'extra' => ['call_id' => $this->callId]
            ]);
            throw $e;
        } finally {
            $transaction->finish();
        }
    }
}
```

## Alerting

### Alert Rules Configuration
```php
// Custom alert configuration
class SentryAlertConfig
{
    public static function criticalAlerts(): array
    {
        return [
            'payment_failed' => [
                'conditions' => [
                    'error.type' => 'PaymentException',
                    'level' => 'error'
                ],
                'actions' => ['email', 'slack']
            ],
            'high_error_rate' => [
                'conditions' => [
                    'error_count' => '> 100',
                    'time_window' => '5 minutes'
                ],
                'actions' => ['pagerduty']
            ],
            'booking_service_down' => [
                'conditions' => [
                    'transaction.name' => 'booking.create',
                    'failure_rate' => '> 50%'
                ],
                'actions' => ['email', 'sms']
            ]
        ];
    }
}
```

## Release Tracking

### Deployment Integration
```bash
#!/bin/bash
# deploy.sh

# Create Sentry release
VERSION=$(git rev-parse --short HEAD)
sentry-cli releases new -p askproai $VERSION

# Upload source maps
sentry-cli releases files $VERSION upload-sourcemaps ./public/js

# Set commits
sentry-cli releases set-commits $VERSION --auto

# Deploy release
php artisan down
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan up

# Mark deployment
sentry-cli releases finalize $VERSION
sentry-cli releases deploys $VERSION new -e production
```

### Release Health Monitoring
```php
// Track release adoption
Sentry::configureScope(function (\Sentry\State\Scope $scope) {
    $scope->setTag('release', config('app.version'));
    $scope->setTag('deployment', config('app.deployment_id'));
});
```

## Data Filtering

### Sensitive Data Protection
```php
namespace App\Services;

class SentryDataFilter
{
    private array $sensitiveFields = [
        'password',
        'api_key',
        'token',
        'secret',
        'credit_card',
        'ssn',
        'bank_account'
    ];
    
    public function filter(\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event
    {
        // Filter request data
        if ($request = $event->getRequest()) {
            $request['data'] = $this->filterSensitiveData($request['data'] ?? []);
            $event->setRequest($request);
        }
        
        // Filter extra context
        $extra = $event->getExtra();
        $event->setExtra($this->filterSensitiveData($extra));
        
        // Filter breadcrumbs
        $breadcrumbs = array_map(function ($breadcrumb) {
            $breadcrumb['data'] = $this->filterSensitiveData($breadcrumb['data'] ?? []);
            return $breadcrumb;
        }, $event->getBreadcrumbs());
        
        $event->setBreadcrumbs($breadcrumbs);
        
        return $event;
    }
    
    private function filterSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->filterSensitiveData($value);
            } elseif ($this->isSensitiveField($key)) {
                $data[$key] = '[FILTERED]';
            }
        }
        
        return $data;
    }
    
    private function isSensitiveField(string $key): bool
    {
        foreach ($this->sensitiveFields as $field) {
            if (stripos($key, $field) !== false) {
                return true;
            }
        }
        return false;
    }
}
```

## Testing

### Test Integration
```php
// Disable Sentry in tests
if (app()->environment('testing')) {
    config(['sentry.dsn' => null]);
}

// Test with mock
public function test_error_reporting()
{
    $this->mock(\Sentry\State\HubInterface::class)
        ->shouldReceive('captureException')
        ->once()
        ->withArgs(function ($exception) {
            return $exception instanceof BookingException;
        });
    
    // Trigger error...
}
```

## Monitoring Dashboard

### Custom Dashboards
- Error rate by service
- Performance metrics by endpoint
- Queue job failure rates
- External API response times
- User-reported issues

### Key Metrics
```php
class SentryMetrics
{
    public function getDashboardData(): array
    {
        return [
            'error_rate' => $this->getErrorRate(),
            'apdex_score' => $this->getApdexScore(),
            'crash_free_sessions' => $this->getCrashFreeRate(),
            'transaction_health' => $this->getTransactionHealth(),
            'top_errors' => $this->getTopErrors(),
            'slowest_endpoints' => $this->getSlowestEndpoints()
        ];
    }
}
```

## Related Documentation
- [Monitoring](../operations/monitoring.md)
- [Error Handling](../development/debugging.md)
- [Performance Optimization](../operations/performance.md)