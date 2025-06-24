# Cache Configuration

## Overview

AskProAI uses multiple caching layers to optimize performance and reduce database load. This guide covers cache configuration, strategies, and best practices.

## Cache Drivers

### Available Drivers
```php
// config/cache.php
return [
    'default' => env('CACHE_DRIVER', 'redis'),
    
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],
        
        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],
        
        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'eu-central-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],
        
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],
        
        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],
    ],
    
    'prefix' => env('CACHE_PREFIX', 'askproai_cache'),
];
```

### Redis Configuration
```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
    
    'queue' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_QUEUE_DB', 2),
    ],
    
    'sessions' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_SESSION_DB', 3),
    ],
];
```

## Caching Strategies

### Cache Keys Structure
```php
// app/Services/Cache/CacheKeyBuilder.php
class CacheKeyBuilder
{
    protected string $prefix;
    protected string $separator = ':';
    
    public function __construct()
    {
        $this->prefix = config('cache.prefix');
    }
    
    public function make(string $type, ...$identifiers): string
    {
        $parts = array_merge([$this->prefix, $type], $identifiers);
        return implode($this->separator, array_filter($parts));
    }
    
    // Examples:
    // askproai_cache:company:123:settings
    // askproai_cache:availability:2026361:2025-06-30
    // askproai_cache:user:456:permissions
}
```

### Cache TTL Configuration
```php
// config/cache-ttl.php
return [
    // Short-lived cache (1-5 minutes)
    'availability' => 60,           // Cal.com availability slots
    'active_calls' => 30,          // Real-time call status
    'rate_limits' => 60,           // API rate limit counters
    
    // Medium-lived cache (5-60 minutes)
    'event_types' => 300,          // Cal.com event types
    'company_settings' => 1800,     // Company configuration
    'user_permissions' => 900,      // User access rights
    'service_health' => 300,        // External service status
    
    // Long-lived cache (1-24 hours)
    'statistics' => 3600,          // Dashboard statistics
    'reports' => 7200,             // Generated reports
    'translations' => 86400,        // Language translations
    'feature_flags' => 3600,        // Feature toggles
    
    // Session-based cache
    'user_session' => 7200,        // User session data
    'api_tokens' => 86400,         // API authentication tokens
];
```

## Model Caching

### Eloquent Model Caching
```php
// app/Traits/Cacheable.php
trait Cacheable
{
    protected static function bootCacheable()
    {
        static::saved(function ($model) {
            $model->clearCache();
        });
        
        static::deleted(function ($model) {
            $model->clearCache();
        });
    }
    
    public function getCacheKey(): string
    {
        return app(CacheKeyBuilder::class)->make(
            $this->getTable(),
            $this->getKey()
        );
    }
    
    public function remember($ttl = null)
    {
        $ttl = $ttl ?? $this->getCacheTTL();
        
        return Cache::remember($this->getCacheKey(), $ttl, function () {
            return $this->fresh();
        });
    }
    
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey());
        
        // Clear related caches
        foreach ($this->getCacheRelations() as $relation) {
            Cache::forget($this->getCacheKey() . ':' . $relation);
        }
    }
    
    protected function getCacheTTL(): int
    {
        return config('cache-ttl.' . $this->getTable(), 3600);
    }
    
    protected function getCacheRelations(): array
    {
        return [];
    }
}
```

### Query Result Caching
```php
// app/Services/Cache/QueryCache.php
class QueryCache
{
    public function remember(string $key, $ttl, Closure $callback)
    {
        // Check if caching is enabled
        if (!config('database.cache_queries', true)) {
            return $callback();
        }
        
        return Cache::tags(['queries'])->remember($key, $ttl, $callback);
    }
    
    public function rememberForever(string $key, Closure $callback)
    {
        return Cache::tags(['queries'])->rememberForever($key, $callback);
    }
    
    public function flush(): void
    {
        Cache::tags(['queries'])->flush();
    }
}

// Usage in repository
class AppointmentRepository
{
    public function getUpcomingForBranch(int $branchId): Collection
    {
        $key = "appointments:upcoming:branch:{$branchId}";
        
        return app(QueryCache::class)->remember($key, 300, function () use ($branchId) {
            return Appointment::where('branch_id', $branchId)
                ->where('date', '>=', now())
                ->orderBy('date')
                ->with(['customer', 'service', 'staff'])
                ->get();
        });
    }
}
```

## API Response Caching

### Response Cache Middleware
```php
// app/Http/Middleware/CacheResponse.php
class CacheResponse
{
    public function handle($request, Closure $next, $ttl = null)
    {
        // Skip caching for non-GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }
        
        // Skip if user is authenticated (personalized content)
        if ($request->user()) {
            return $next($request);
        }
        
        $key = $this->getCacheKey($request);
        
        return Cache::remember($key, $ttl ?? 300, function () use ($request, $next) {
            $response = $next($request);
            
            // Only cache successful responses
            if ($response->isSuccessful()) {
                return $response;
            }
            
            // Don't cache error responses
            Cache::forget($key);
            return $response;
        });
    }
    
    protected function getCacheKey(Request $request): string
    {
        $url = $request->url();
        $queryParams = $request->query();
        ksort($queryParams);
        
        return 'response:' . md5($url . '?' . http_build_query($queryParams));
    }
}
```

### API Endpoint Caching
```php
// routes/api.php
Route::middleware(['cache.response:300'])->group(function () {
    Route::get('/event-types', [EventTypeController::class, 'index']);
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/services', [ServiceController::class, 'index']);
});

// Controller with custom cache
class StatisticsController extends Controller
{
    public function dashboard()
    {
        $stats = Cache::remember('dashboard:stats:' . auth()->user()->company_id, 3600, function () {
            return [
                'total_appointments' => $this->getAppointmentStats(),
                'total_calls' => $this->getCallStats(),
                'revenue' => $this->getRevenueStats(),
                'customer_growth' => $this->getCustomerGrowth(),
            ];
        });
        
        return response()->json($stats);
    }
}
```

## Cache Warming

### Cache Warming Commands
```php
// app/Console/Commands/WarmCache.php
class WarmCache extends Command
{
    protected $signature = 'cache:warm {--type=all}';
    
    public function handle()
    {
        $type = $this->option('type');
        
        $this->info('Starting cache warming...');
        
        match($type) {
            'event-types' => $this->warmEventTypes(),
            'availability' => $this->warmAvailability(),
            'statistics' => $this->warmStatistics(),
            'all' => $this->warmAll(),
        };
        
        $this->info('Cache warming completed!');
    }
    
    protected function warmEventTypes()
    {
        $companies = Company::active()->get();
        
        foreach ($companies as $company) {
            $this->info("Warming event types for company: {$company->name}");
            
            app(CalcomService::class)
                ->setCompany($company)
                ->getEventTypes(true); // Force refresh
        }
    }
    
    protected function warmAvailability()
    {
        $eventTypes = CalcomEventType::active()->get();
        $dates = CarbonPeriod::create(now(), now()->addDays(7));
        
        foreach ($eventTypes as $eventType) {
            foreach ($dates as $date) {
                $key = "availability:{$eventType->calcom_event_type_id}:{$date->format('Y-m-d')}";
                
                Cache::remember($key, 300, function () use ($eventType, $date) {
                    return app(CalcomService::class)->getAvailability(
                        $eventType->calcom_event_type_id,
                        $date->format('Y-m-d')
                    );
                });
            }
        }
    }
}
```

### Scheduled Cache Warming
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Warm critical caches before business hours
    $schedule->command('cache:warm --type=event-types')
        ->dailyAt('06:00')
        ->withoutOverlapping();
    
    $schedule->command('cache:warm --type=availability')
        ->everyThirtyMinutes()
        ->between('07:00', '20:00')
        ->withoutOverlapping();
    
    $schedule->command('cache:warm --type=statistics')
        ->hourly()
        ->withoutOverlapping();
}
```

## Cache Invalidation

### Event-Based Invalidation
```php
// app/Listeners/InvalidateCache.php
class InvalidateCache
{
    public function handle($event)
    {
        match(get_class($event)) {
            AppointmentCreated::class => $this->invalidateAppointmentCaches($event->appointment),
            EventTypeUpdated::class => $this->invalidateEventTypeCaches($event->eventType),
            CompanySettingsUpdated::class => $this->invalidateCompanyCaches($event->company),
        };
    }
    
    protected function invalidateAppointmentCaches(Appointment $appointment)
    {
        // Clear availability cache for the date
        $key = "availability:{$appointment->calcom_event_type_id}:{$appointment->date->format('Y-m-d')}";
        Cache::forget($key);
        
        // Clear branch statistics
        Cache::forget("statistics:branch:{$appointment->branch_id}");
        
        // Clear dashboard cache
        Cache::forget("dashboard:stats:{$appointment->company_id}");
    }
}
```

### Manual Cache Clearing
```php
// app/Services/Cache/CacheCleaner.php
class CacheCleaner
{
    public function clearAll(): void
    {
        Cache::flush();
    }
    
    public function clearByPattern(string $pattern): int
    {
        $count = 0;
        $redis = Cache::getRedis();
        
        $keys = $redis->keys(config('cache.prefix') . ':' . $pattern);
        
        foreach ($keys as $key) {
            $redis->del($key);
            $count++;
        }
        
        return $count;
    }
    
    public function clearByTags(array $tags): void
    {
        Cache::tags($tags)->flush();
    }
    
    public function clearExpired(): int
    {
        // Redis handles expiration automatically
        // This is for file or database cache drivers
        if (Cache::getStore() instanceof FileStore) {
            return $this->clearExpiredFiles();
        }
        
        return 0;
    }
}
```

## Cache Monitoring

### Cache Statistics
```php
// app/Services/Cache/CacheMonitor.php
class CacheMonitor
{
    public function getStats(): array
    {
        $redis = Cache::getRedis();
        $info = $redis->info();
        
        return [
            'used_memory' => $info['used_memory_human'],
            'total_keys' => $redis->dbSize(),
            'hit_rate' => $this->calculateHitRate(),
            'evicted_keys' => $info['evicted_keys'] ?? 0,
            'expired_keys' => $info['expired_keys'] ?? 0,
            'connected_clients' => $info['connected_clients'],
        ];
    }
    
    public function getKeysByPattern(string $pattern): array
    {
        $redis = Cache::getRedis();
        $keys = $redis->keys(config('cache.prefix') . ':' . $pattern);
        
        $result = [];
        foreach ($keys as $key) {
            $ttl = $redis->ttl($key);
            $type = $redis->type($key);
            
            $result[] = [
                'key' => str_replace(config('cache.prefix') . ':', '', $key),
                'ttl' => $ttl > 0 ? $ttl : 'persistent',
                'type' => $type,
                'size' => strlen($redis->get($key)),
            ];
        }
        
        return $result;
    }
}
```

### Cache Dashboard
```php
// app/Http/Controllers/Admin/CacheDashboardController.php
class CacheDashboardController extends Controller
{
    public function index()
    {
        $monitor = app(CacheMonitor::class);
        
        return view('admin.cache-dashboard', [
            'stats' => $monitor->getStats(),
            'patterns' => [
                'companies' => $monitor->getKeysByPattern('company:*'),
                'availability' => $monitor->getKeysByPattern('availability:*'),
                'sessions' => $monitor->getKeysByPattern('session:*'),
            ],
        ]);
    }
    
    public function clear(Request $request)
    {
        $pattern = $request->input('pattern');
        
        $count = app(CacheCleaner::class)->clearByPattern($pattern);
        
        return response()->json([
            'message' => "Cleared {$count} cache entries",
        ]);
    }
}
```

## Performance Optimization

### Cache Preloading
```php
// app/Http/Middleware/PreloadCache.php
class PreloadCache
{
    public function handle($request, Closure $next)
    {
        if ($user = $request->user()) {
            // Preload user-specific caches
            dispatch(function () use ($user) {
                Cache::remember("user:{$user->id}:permissions", 900, function () use ($user) {
                    return $user->getAllPermissions();
                });
                
                Cache::remember("company:{$user->company_id}:settings", 1800, function () use ($user) {
                    return $user->company->settings;
                });
            })->afterResponse();
        }
        
        return $next($request);
    }
}
```

### Batch Operations
```php
// app/Services/Cache/CacheBatch.php
class CacheBatch
{
    public function getMany(array $keys): array
    {
        return Cache::many($keys);
    }
    
    public function putMany(array $values, $ttl = null): bool
    {
        return Cache::putMany($values, $ttl);
    }
    
    public function pipeline(Closure $callback)
    {
        return Cache::getRedis()->pipeline(function ($pipe) use ($callback) {
            return $callback($pipe);
        });
    }
}
```

## Best Practices

### 1. Cache Key Naming
- Use consistent, hierarchical naming
- Include version numbers for cache busting
- Make keys human-readable for debugging

### 2. TTL Strategy
- Shorter TTL for frequently changing data
- Longer TTL for static configuration
- Use cache tags for grouped invalidation

### 3. Memory Management
- Monitor memory usage regularly
- Set appropriate maxmemory policies
- Use LRU eviction for Redis

### 4. Error Handling
```php
try {
    $value = Cache::remember($key, $ttl, $callback);
} catch (Exception $e) {
    Log::error('Cache operation failed', ['key' => $key, 'error' => $e->getMessage()]);
    // Fallback to direct database query
    $value = $callback();
}
```

## Related Documentation
- [Performance Optimization](../operations/performance.md)
- [Database Configuration](database.md)
- [Queue Configuration](queues.md)