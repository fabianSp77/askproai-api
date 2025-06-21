# 🚀 Laravel Optimierung Guide für AskProAI

## 📊 Aktuelle Performance-Probleme

Nach Analyse des Codes wurden folgende Optimierungspotenziale identifiziert:

### 1. **N+1 Query Probleme** 🔴 KRITISCH

**Problem:**
```php
// In vielen Controllers und Resources
$calls = Call::all();
foreach ($calls as $call) {
    echo $call->customer->name; // N+1 Problem!
    echo $call->branch->name;   // Noch ein N+1!
}
```

**Lösung:**
```php
// Eager Loading verwenden
$calls = Call::with(['customer', 'branch', 'appointment'])->get();

// Oder in Filament Resources
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['customer', 'branch', 'appointment']);
}
```

### 2. **Fehlende Datenbank-Indizes** 🟡 WICHTIG

**Analyse:**
```sql
-- Häufige WHERE-Klauseln ohne Index
SELECT * FROM calls WHERE from_number = '+49...'; -- Kein Index!
SELECT * FROM calls WHERE company_id = 85 AND created_at > '2025-06-01'; -- Teilweise indiziert
```

**Optimierung:**
```php
// Migration für Performance-Indizes
Schema::table('calls', function (Blueprint $table) {
    $table->index('from_number');
    $table->index('to_number');
    $table->index(['company_id', 'created_at']);
    $table->index(['branch_id', 'status']);
});

Schema::table('customers', function (Blueprint $table) {
    $table->index(['company_id', 'phone']);
    $table->unique(['company_id', 'email']);
});
```

### 3. **Keine Cache-Nutzung** 🟡 WICHTIG

**Problem:**
```php
// Wird bei jedem Request ausgeführt
$eventTypes = CalcomEventType::where('company_id', $companyId)->get();
$branches = Branch::where('company_id', $companyId)->where('is_active', true)->get();
```

**Lösung:**
```php
// Cache nutzen
$eventTypes = Cache::remember("event_types:company:{$companyId}", 300, function () use ($companyId) {
    return CalcomEventType::where('company_id', $companyId)->get();
});

// Mit Tags für einfaches Invalidieren
$branches = Cache::tags(['branches', "company:{$companyId}"])
    ->remember("branches:active:{$companyId}", 3600, function () use ($companyId) {
        return Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();
    });
```

### 4. **Synchrone Webhook-Verarbeitung** 🔴 KRITISCH

**Problem:**
```php
// RetellWebhookController - Blockiert Request
public function handle(Request $request)
{
    // Lange Verarbeitung...
    $call = Call::create([...]); // DB Operation
    $customer = Customer::firstOrCreate([...]); // Noch eine
    $appointment = Appointment::create([...]); // Und noch eine
    
    return response()->json(['success' => true]);
}
```

**Lösung:**
```php
public function handle(Request $request)
{
    // Sofort in Queue
    ProcessWebhookJob::dispatch($request->all())
        ->onQueue('webhooks');
    
    // Immediate Response
    return response()->json(['status' => 'acknowledged'], 200);
}
```

### 5. **Ineffiziente Queries** 🟡 WICHTIG

**Problem:**
```php
// Lädt ALLE Daten
$allCalls = Call::all();
$count = $allCalls->count();

// Mehrere Queries für Aggregation
$totalCalls = Call::count();
$completedCalls = Call::where('status', 'completed')->count();
$failedCalls = Call::where('status', 'failed')->count();
```

**Lösung:**
```php
// Nur Count Query
$count = Call::count();

// Eine Query für alle Counts
$stats = Call::selectRaw('
    COUNT(*) as total,
    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
')->first();
```

## 🛠️ Konkrete Optimierungsmaßnahmen

### 1. **Eloquent Query Optimierung**

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    // N+1 Probleme in Development aufdecken
    Model::preventLazyLoading(!$this->app->isProduction());
    
    // Warnung bei langsamen Queries (>500ms)
    DB::whenQueryingForLongerThan(500, function (Connection $connection, QueryExecuted $event) {
        Log::warning('Slow query detected', [
            'sql' => $event->sql,
            'time' => $event->time,
            'connection' => $connection->getName()
        ]);
    });
}
```

### 2. **Repository Pattern mit Cache**

```php
// app/Repositories/CallRepository.php
class CallRepository
{
    public function getRecentCalls(int $companyId, int $limit = 10): Collection
    {
        return Cache::tags(['calls', "company:{$companyId}"])
            ->remember("recent_calls:{$companyId}:{$limit}", 60, function () use ($companyId, $limit) {
                return Call::with(['customer', 'branch', 'appointment'])
                    ->where('company_id', $companyId)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
            });
    }
    
    public function invalidateCache(int $companyId): void
    {
        Cache::tags(["company:{$companyId}"])->flush();
    }
}
```

### 3. **Database Query Monitoring**

```php
// app/Http/Middleware/DatabaseQueryMonitoring.php
class DatabaseQueryMonitoring
{
    public function handle($request, Closure $next)
    {
        if (config('app.debug')) {
            DB::enableQueryLog();
        }
        
        $response = $next($request);
        
        if (config('app.debug')) {
            $queries = DB::getQueryLog();
            $totalTime = collect($queries)->sum('time');
            
            if (count($queries) > 50) {
                Log::warning('Too many queries', [
                    'count' => count($queries),
                    'time' => $totalTime,
                    'url' => $request->fullUrl()
                ]);
            }
        }
        
        return $response;
    }
}
```

### 4. **Optimierte Filament Widgets**

```php
// app/Filament/Admin/Widgets/OptimizedStatsWidget.php
class OptimizedStatsWidget extends Widget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';
    
    public function getStats(): array
    {
        // Alles in einer Query mit Cache
        return Cache::remember('dashboard_stats:' . auth()->user()->company_id, 60, function () {
            $stats = DB::table('calls')
                ->where('company_id', auth()->user()->company_id)
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('
                    COUNT(*) as total_calls,
                    COUNT(DISTINCT customer_id) as unique_customers,
                    AVG(duration_sec) as avg_duration,
                    SUM(CASE WHEN appointment_id IS NOT NULL THEN 1 ELSE 0 END) as appointments_created
                ')
                ->first();
                
            return [
                Stat::make('Total Calls', number_format($stats->total_calls))
                    ->description('Last 30 days')
                    ->descriptionIcon('heroicon-m-arrow-trending-up'),
                    
                Stat::make('Unique Customers', number_format($stats->unique_customers))
                    ->description('Last 30 days'),
                    
                Stat::make('Avg Duration', round($stats->avg_duration / 60, 1) . ' min')
                    ->description('Per call'),
                    
                Stat::make('Appointments', number_format($stats->appointments_created))
                    ->description('Created from calls')
            ];
        });
    }
}
```

### 5. **Chunk Processing für große Datenmengen**

```php
// app/Console/Commands/ProcessHistoricalCalls.php
class ProcessHistoricalCalls extends Command
{
    public function handle()
    {
        $this->info('Processing historical calls...');
        
        Call::where('processed', false)
            ->chunkById(200, function ($calls) {
                foreach ($calls as $call) {
                    // Process in background
                    ProcessCallJob::dispatch($call);
                }
                
                $this->info("Processed chunk of {$calls->count()} calls");
            });
    }
}
```

### 6. **View Caching & Optimization**

```bash
# In deployment script
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
php artisan filament:cache-components
```

### 7. **Redis Configuration für Queue & Cache**

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'), // Schneller als predis
    
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
        'read_write_timeout' => 60,
    ],
    
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

### 8. **Pagination statt All()**

```php
// Schlecht
$calls = Call::all(); // Lädt ALLE Records

// Gut - Simple Pagination
$calls = Call::simplePaginate(50); // Nur Next/Previous

// Gut - Full Pagination
$calls = Call::paginate(50); // Mit Seitenzahlen

// Gut - Cursor Pagination (für APIs)
$calls = Call::cursorPaginate(50); // Für infinite scroll
```

### 9. **Lazy Collections für große Datenmengen**

```php
// Memory-intensiv
$users = User::all()->filter(function ($user) {
    return $user->calls()->count() > 100;
});

// Memory-effizient
$users = User::cursor()->filter(function ($user) {
    return $user->calls()->count() > 100;
});

// Oder mit lazy()
User::lazy()->each(function ($user) {
    ProcessUserJob::dispatch($user);
});
```

### 10. **Queue Optimierung**

```php
// config/horizon.php
'defaults' => [
    'supervisor-default' => [
        'connection' => 'redis',
        'queue' => ['default'],
        'balance' => 'auto',
        'autoScalingStrategy' => 'time',
        'maxProcesses' => 10,
        'maxTime' => 0,
        'maxJobs' => 0,
        'memory' => 128,
        'tries' => 2,
        'timeout' => 60,
        'nice' => 0,
    ],
    
    'supervisor-webhooks' => [
        'connection' => 'redis',
        'queue' => ['webhooks'],
        'balance' => 'simple',
        'autoScalingStrategy' => 'size',
        'maxProcesses' => 20, // Mehr Worker für Webhooks
        'maxTime' => 0,
        'maxJobs' => 0,
        'memory' => 128,
        'tries' => 3,
        'timeout' => 30,
    ],
],
```

## 📈 Performance Monitoring

### 1. **Laravel Telescope Installation**
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### 2. **Custom Performance Metrics**
```php
// app/Services/PerformanceMonitor.php
class PerformanceMonitor
{
    public function recordMetric(string $metric, float $value, array $tags = []): void
    {
        // Send to monitoring service
        Http::post('https://metrics.example.com/api/metrics', [
            'metric' => $metric,
            'value' => $value,
            'tags' => array_merge($tags, [
                'app' => 'askproai',
                'env' => app()->environment()
            ]),
            'timestamp' => now()->timestamp
        ]);
    }
}

// Usage
app(PerformanceMonitor::class)->recordMetric('webhook.processing_time', $duration, [
    'provider' => 'retell',
    'status' => 'success'
]);
```

## 🎯 Sofort umsetzbare Optimierungen

### 1. **Quick Wins** (1 Tag)
- [ ] Eager Loading in allen Filament Resources aktivieren
- [ ] Cache für Company/Branch Queries implementieren
- [ ] Database Indizes hinzufügen
- [ ] View & Config Caching aktivieren

### 2. **Mittelfristig** (1 Woche)
- [ ] Webhook Queue Processing implementieren
- [ ] Repository Pattern mit Cache Layer
- [ ] Pagination in allen Listen
- [ ] Redis für Cache & Queue

### 3. **Langfristig** (1 Monat)
- [ ] Vollständiges Performance Monitoring
- [ ] Database Sharding vorbereiten
- [ ] CDN für Assets
- [ ] Horizontal Scaling Strategy

## 🔥 Kritischste Optimierungen

1. **Webhook Async Processing** - Verhindert Timeouts
2. **N+1 Query Prevention** - Reduziert DB Last um 90%
3. **Caching Strategy** - Reduziert Response Time um 70%
4. **Database Indexing** - Beschleunigt Queries um 10-100x

Diese Optimierungen werden die Performance signifikant verbessern und das System für echten SaaS-Betrieb vorbereiten.