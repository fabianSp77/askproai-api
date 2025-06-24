# Debugging Guide

## Overview

This guide provides comprehensive debugging techniques and tools for troubleshooting issues in the AskProAI application. It covers various debugging scenarios, tools, and best practices.

## Development Environment Debugging

### Laravel Telescope

Telescope provides insight into requests, exceptions, database queries, queued jobs, mail, notifications, cache operations, scheduled tasks, variable dumps and more.

```php
// Access Telescope
http://localhost:8000/telescope

// Programmatically enable/disable
app('telescope')->startRecording();
app('telescope')->stopRecording();

// Tag entries for filtering
Telescope::tag(function (IncomingEntry $entry) {
    if ($entry->type === 'request') {
        return ['status:' . $entry->content['response_status']];
    }
    return [];
});
```

### Laravel Debugbar

```php
// Enable/disable debugbar
config(['debugbar.enabled' => true]);

// Add custom messages
\Debugbar::info('Info message');
\Debugbar::error('Error message');
\Debugbar::warning('Warning message');

// Add custom timeline events
\Debugbar::startMeasure('api-call', 'External API Call');
// ... your code
\Debugbar::stopMeasure('api-call');

// Add data to debugbar
\Debugbar::addCollector(new \DebugBar\DataCollector\MessagesCollector('custom'));
```

### Debug Helpers

```php
// Dump and die
dd($variable);

// Dump and continue
dump($variable);

// Dump to log
logger('Debug message', ['context' => $data]);

// Conditional debugging
dump_if($condition, $variable);
dd_unless($condition, $variable);

// Ray debugging (if installed)
ray($data)->green();
ray()->showQueries();
ray()->pause();
```

## Logging and Monitoring

### Structured Logging

```php
// app/Logging/StructuredLogger.php
namespace App\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StructuredLogger
{
    private string $correlationId;
    private array $context = [];
    
    public function __construct()
    {
        $this->correlationId = request()->header('X-Correlation-ID', Str::uuid());
    }
    
    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }
    
    public function info(string $message, array $data = []): void
    {
        $this->log('info', $message, $data);
    }
    
    public function error(string $message, array $data = []): void
    {
        $this->log('error', $message, $data);
    }
    
    private function log(string $level, string $message, array $data): void
    {
        Log::$level($message, array_merge([
            'correlation_id' => $this->correlationId,
            'user_id' => auth()->id(),
            'company_id' => auth()->user()?->company_id,
            'request_id' => request()->id(),
            'timestamp' => now()->toIso8601String(),
        ], $this->context, $data));
    }
}

// Usage
app(StructuredLogger::class)
    ->withContext(['service' => 'appointment'])
    ->info('Appointment created', ['appointment_id' => $appointment->id]);
```

### Query Debugging

```php
// Enable query log
DB::enableQueryLog();

// Your queries here
$users = User::where('active', true)->get();

// Get executed queries
$queries = DB::getQueryLog();
dd($queries);

// Log all queries
DB::listen(function ($query) {
    Log::debug('Query', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time,
    ]);
});

// Using Laravel Debugbar
\Debugbar::startMeasure('complex-query', 'Complex Query Execution');
$results = DB::select('SELECT * FROM large_table WHERE complex_conditions');
\Debugbar::stopMeasure('complex-query');
```

### Performance Debugging

```php
// app/Http/Middleware/PerformanceMonitoring.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class PerformanceMonitoring
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $start;
        
        if ($duration > 1) { // Log slow requests (> 1 second)
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration' => round($duration, 3),
                'memory' => round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB',
            ]);
        }
        
        $response->headers->set('X-Response-Time', round($duration * 1000) . 'ms');
        
        return $response;
    }
}
```

## Debugging External Services

### Retell.ai Debugging

```php
// app/Services/Debug/RetellDebugger.php
namespace App\Services\Debug;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RetellDebugger
{
    public function debugWebhook(array $payload): void
    {
        Log::channel('retell')->debug('Webhook received', [
            'event_type' => $payload['event_type'],
            'call_id' => $payload['call_id'] ?? null,
            'raw_payload' => $payload,
        ]);
    }
    
    public function testConnection(): array
    {
        try {
            $response = Http::withToken(config('services.retell.api_key'))
                ->get('https://api.retellai.com/v1/agents');
            
            return [
                'status' => 'connected',
                'agents' => $response->json(),
                'response_time' => $response->handlerStats()['total_time'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }
    }
    
    public function simulateWebhook(string $eventType, array $data = []): void
    {
        $payload = array_merge([
            'event_type' => $eventType,
            'call_id' => 'debug_' . Str::random(10),
            'timestamp' => now()->toIso8601String(),
        ], $data);
        
        $response = Http::withHeaders([
            'x-retell-signature' => $this->generateSignature($payload),
        ])->post(route('retell.webhook'), $payload);
        
        Log::channel('retell')->debug('Simulated webhook', [
            'payload' => $payload,
            'response' => $response->json(),
            'status' => $response->status(),
        ]);
    }
}
```

### Cal.com Debugging

```php
// app/Console/Commands/DebugCalcom.php
namespace App\Console\Commands;

use App\Services\CalcomV2Service;
use Illuminate\Console\Command;

class DebugCalcom extends Command
{
    protected $signature = 'debug:calcom {action}';
    
    public function handle(CalcomV2Service $calcom)
    {
        switch ($this->argument('action')) {
            case 'test':
                $this->testConnection($calcom);
                break;
                
            case 'availability':
                $this->checkAvailability($calcom);
                break;
                
            case 'event-types':
                $this->listEventTypes($calcom);
                break;
        }
    }
    
    private function testConnection(CalcomV2Service $calcom): void
    {
        $this->info('Testing Cal.com connection...');
        
        try {
            $response = $calcom->testConnection();
            $this->info('✓ Connection successful');
            $this->table(['Key', 'Value'], collect($response)->map(fn($v, $k) => [$k, $v]));
        } catch (\Exception $e) {
            $this->error('✗ Connection failed: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
        }
    }
}
```

## Error Debugging

### Exception Handler Enhancement

```php
// app/Exceptions/Handler.php
namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function report(Throwable $exception)
    {
        // Add context to all exceptions
        if (app()->bound('sentry')) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) {
                $scope->setContext('request', [
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'ip' => request()->ip(),
                ]);
                
                if ($user = auth()->user()) {
                    $scope->setContext('user', [
                        'id' => $user->id,
                        'email' => $user->email,
                        'company_id' => $user->company_id,
                    ]);
                }
            });
        }
        
        // Log additional context for debugging
        if ($this->shouldReport($exception)) {
            Log::error('Exception occurred', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(5)->toArray(),
                'request' => [
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'data' => request()->except(['password', 'password_confirmation']),
                ],
            ]);
        }
        
        parent::report($exception);
    }
}
```

### Custom Exception with Debug Info

```php
// app/Exceptions/BookingException.php
namespace App\Exceptions;

use Exception;

class BookingException extends Exception
{
    protected array $context = [];
    
    public static function slotNotAvailable(array $context = []): self
    {
        $exception = new self('The requested time slot is not available');
        $exception->context = $context;
        return $exception;
    }
    
    public function context(): array
    {
        return $this->context;
    }
    
    public function report()
    {
        Log::error('Booking exception', [
            'message' => $this->getMessage(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }
    
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'debug' => config('app.debug') ? $this->context : null,
            ], 422);
        }
        
        return parent::render($request);
    }
}
```

## Queue Debugging

### Queue Monitoring

```php
// app/Console/Commands/MonitorQueues.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class MonitorQueues extends Command
{
    protected $signature = 'queue:monitor';
    
    public function handle()
    {
        $queues = ['default', 'high', 'low', 'webhooks', 'emails'];
        
        $this->table(
            ['Queue', 'Size', 'Processing', 'Failed', 'Status'],
            collect($queues)->map(fn($queue) => [
                $queue,
                $this->getQueueSize($queue),
                $this->getProcessingCount($queue),
                $this->getFailedCount($queue),
                $this->getQueueStatus($queue),
            ])
        );
        
        // Monitor specific job
        $this->monitorJob('App\Jobs\ProcessRetellWebhook');
    }
    
    private function getQueueSize(string $queue): int
    {
        return Redis::llen("queues:{$queue}");
    }
    
    private function monitorJob(string $jobClass): void
    {
        $jobs = Redis::lrange('queues:default', 0, -1);
        
        $count = collect($jobs)
            ->map(fn($job) => json_decode($job, true))
            ->filter(fn($job) => ($job['displayName'] ?? '') === $jobClass)
            ->count();
            
        $this->info("Found {$count} {$jobClass} jobs in queue");
    }
}
```

### Failed Job Debugging

```php
// app/Console/Commands/DebugFailedJob.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugFailedJob extends Command
{
    protected $signature = 'queue:debug-failed {id}';
    
    public function handle()
    {
        $job = DB::table('failed_jobs')->find($this->argument('id'));
        
        if (!$job) {
            $this->error('Failed job not found');
            return;
        }
        
        $payload = json_decode($job->payload, true);
        $exception = $job->exception;
        
        $this->info('Failed Job Details:');
        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $job->id],
                ['Queue', $job->queue],
                ['Failed At', $job->failed_at],
                ['Job Class', $payload['displayName'] ?? 'Unknown'],
                ['Attempts', $payload['attempts'] ?? 0],
            ]
        );
        
        $this->info("\nJob Data:");
        $this->line(json_encode($payload['data'] ?? [], JSON_PRETTY_PRINT));
        
        $this->error("\nException:");
        $this->line($exception);
        
        if ($this->confirm('Retry this job?')) {
            $this->call('queue:retry', ['id' => [$job->id]]);
        }
    }
}
```

## Database Debugging

### Query Analysis

```php
// app/Console/Commands/AnalyzeQueries.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeQueries extends Command
{
    protected $signature = 'db:analyze-queries';
    
    public function handle()
    {
        DB::enableQueryLog();
        
        // Run your application logic here
        $this->call('appointment:sync');
        
        $queries = collect(DB::getQueryLog());
        
        $this->info("Total queries: " . $queries->count());
        
        // Find slow queries
        $slowQueries = $queries->filter(fn($q) => $q['time'] > 100);
        
        if ($slowQueries->isNotEmpty()) {
            $this->warn("\nSlow queries detected:");
            $slowQueries->each(function ($query) {
                $this->line("Time: {$query['time']}ms");
                $this->line("SQL: {$query['query']}");
                $this->line("Bindings: " . json_encode($query['bindings']));
                $this->line("");
            });
        }
        
        // Find N+1 queries
        $this->detectNPlusOne($queries);
    }
    
    private function detectNPlusOne($queries)
    {
        $patterns = $queries->map(fn($q) => preg_replace('/\d+/', 'N', $q['query']))
            ->countBy()
            ->filter(fn($count) => $count > 1);
            
        if ($patterns->isNotEmpty()) {
            $this->warn("\nPotential N+1 queries detected:");
            $patterns->each(function ($count, $pattern) {
                $this->line("Pattern repeated {$count} times: {$pattern}");
            });
        }
    }
}
```

### Migration Debugging

```php
// app/Console/Commands/DebugMigration.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DebugMigration extends Command
{
    protected $signature = 'migrate:debug {migration}';
    
    public function handle()
    {
        $migrationClass = $this->argument('migration');
        $migration = new $migrationClass;
        
        // Test migration in transaction
        DB::beginTransaction();
        
        try {
            $this->info('Running migration...');
            $migration->up();
            
            // Check schema changes
            $this->info('Schema changes applied successfully');
            
            // You can inspect the changes here
            $this->call('db:show', ['table' => 'your_table']);
            
            if ($this->confirm('Keep changes?')) {
                DB::commit();
                $this->info('Changes committed');
            } else {
                DB::rollback();
                $this->info('Changes rolled back');
            }
        } catch (\Exception $e) {
            DB::rollback();
            $this->error('Migration failed: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
        }
    }
}
```

## API Debugging

### Request/Response Logging Middleware

```php
// app/Http/Middleware/LogApiRequests.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogApiRequests
{
    public function handle($request, Closure $next)
    {
        $requestId = Str::uuid();
        
        // Log request
        Log::channel('api')->info('API Request', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'body' => $request->except(['password', 'password_confirmation']),
            'ip' => $request->ip(),
        ]);
        
        $response = $next($request);
        
        // Log response
        Log::channel('api')->info('API Response', [
            'request_id' => $requestId,
            'status' => $response->status(),
            'headers' => $response->headers->all(),
            'body' => $response->getContent(),
        ]);
        
        return $response;
    }
}
```

### API Testing Tool

```php
// app/Console/Commands/TestApi.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestApi extends Command
{
    protected $signature = 'api:test {endpoint} {--method=GET} {--data=}';
    
    public function handle()
    {
        $endpoint = $this->argument('endpoint');
        $method = $this->option('method');
        $data = json_decode($this->option('data') ?? '{}', true);
        
        $url = url($endpoint);
        
        $this->info("Testing API: {$method} {$url}");
        
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getTestToken(),
        ])->$method($url, $data);
        
        $this->table(
            ['Property', 'Value'],
            [
                ['Status', $response->status()],
                ['Time', $response->handlerStats()['total_time'] . 's'],
                ['Size', strlen($response->body()) . ' bytes'],
            ]
        );
        
        $this->info("\nResponse Headers:");
        foreach ($response->headers() as $key => $values) {
            $this->line("{$key}: " . implode(', ', $values));
        }
        
        $this->info("\nResponse Body:");
        $this->line($response->body());
    }
}
```

## Production Debugging

### Safe Production Debugging

```php
// app/Http/Controllers/DebugController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:debug');
    }
    
    public function systemInfo()
    {
        return [
            'app' => [
                'version' => config('app.version'),
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
                'url' => config('app.url'),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'loaded_extensions' => get_loaded_extensions(),
            ],
            'laravel' => [
                'version' => app()->version(),
                'cache_driver' => config('cache.default'),
                'queue_driver' => config('queue.default'),
                'session_driver' => config('session.driver'),
            ],
            'server' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'os' => php_uname(),
                'load_average' => sys_getloadavg(),
            ],
        ];
    }
    
    public function healthCheck()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'external_services' => $this->checkExternalServices(),
        ];
        
        return response()->json($checks);
    }
}
```

### Feature Flags for Debugging

```php
// config/debug.php
return [
    'features' => [
        'verbose_logging' => env('DEBUG_VERBOSE_LOGGING', false),
        'query_logging' => env('DEBUG_QUERY_LOGGING', false),
        'api_logging' => env('DEBUG_API_LOGGING', false),
        'performance_tracking' => env('DEBUG_PERFORMANCE', false),
    ],
];

// Usage
if (config('debug.features.verbose_logging')) {
    Log::debug('Verbose debug information', $context);
}
```

## Debugging Tools

### Artisan Commands

```bash
# Clear all caches
php artisan optimize:clear

# View application routes
php artisan route:list --path=api

# View scheduled tasks
php artisan schedule:list

# Test specific command
php artisan tinker
>>> app(RetellService::class)->testConnection()

# View configuration
php artisan config:show database

# Debug queue workers
php artisan queue:listen --tries=1 --timeout=0

# Debug specific job
php artisan queue:work --queue=webhooks --stop-when-empty
```

### Tinker Sessions

```php
// Useful tinker commands
php artisan tinker

// Test database connection
>>> DB::connection()->getPdo();

// Test Redis connection
>>> Redis::ping();

// Debug specific model
>>> $appointment = Appointment::with(['customer', 'service'])->find(1);
>>> $appointment->toArray();

// Test service
>>> $service = app(AppointmentService::class);
>>> $service->checkAvailability('2025-07-01', '14:00');

// Clear specific cache
>>> Cache::forget('key');

// Debug configuration
>>> config('services.retell');

// Test email
>>> Mail::raw('Test email', fn($message) => $message->to('test@example.com'));
```

## Debugging Best Practices

1. **Use Correlation IDs**: Track requests across services
2. **Structured Logging**: Use consistent log formats
3. **Don't Debug in Production**: Use feature flags and staging environment
4. **Clean Up Debug Code**: Remove or disable debug statements before merging
5. **Document Debug Findings**: Keep a debug journal for recurring issues
6. **Use Version Control**: Tag releases for easy rollback and comparison
7. **Monitor Performance Impact**: Ensure debugging doesn't affect performance

## Related Documentation

- [Testing Guide](testing.md)
- [Monitoring Setup](../deployment/monitoring.md)
- [Error Handling](../api/error-handling.md)
- [Performance Optimization](../operations/performance.md)