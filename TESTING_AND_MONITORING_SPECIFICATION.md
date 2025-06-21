# Testing and Monitoring Technical Specification

## Table of Contents
1. [SQLite Test Migration Fix](#1-sqlite-test-migration-fix)
2. [Test Infrastructure](#2-test-infrastructure)
3. [Production Monitoring Dashboard](#3-production-monitoring-dashboard)
4. [Logging Strategy](#4-logging-strategy)

---

## 1. SQLite Test Migration Fix

### 1.1 Problem Analysis
The current test suite uses SQLite in-memory database which causes issues with:
- JSON column defaults (e.g., `json_encode([])`)
- Complex JSON operations
- Foreign key constraints
- Full-text indexes
- Database-specific functions

### 1.2 Database-agnostic Migration Strategy

#### Migration Helper Class
```php
// app/Database/Schema/BlueprintMacros.php
namespace App\Database\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class BlueprintMacros
{
    public static function register()
    {
        // JSON column with SQLite compatibility
        Blueprint::macro('jsonColumn', function ($column) {
            $driver = DB::connection()->getDriverName();
            
            if ($driver === 'sqlite') {
                return $this->text($column)->nullable();
            }
            
            return $this->json($column)->nullable()->default('[]');
        });
        
        // Conditional index creation
        Blueprint::macro('conditionalIndex', function ($columns, $name = null) {
            $driver = DB::connection()->getDriverName();
            
            if ($driver !== 'sqlite') {
                return $this->index($columns, $name);
            }
            
            return $this;
        });
    }
}
```

#### Migration Base Class
```php
// app/Database/Migrations/CompatibleMigration.php
namespace App\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class CompatibleMigration extends Migration
{
    protected function isTestEnvironment(): bool
    {
        return app()->environment('testing');
    }
    
    protected function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }
    
    protected function jsonEncode($data): string
    {
        if ($this->isSqlite()) {
            return json_encode($data);
        }
        
        return DB::raw("'" . json_encode($data) . "'");
    }
    
    protected function setJsonDefault($table, $column, $default = [])
    {
        if (!$this->isSqlite()) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT '" . json_encode($default) . "'");
        }
    }
}
```

### 1.3 JSON Column Handling for SQLite

#### Model Trait for JSON Handling
```php
// app/Models/Traits/HandlesJsonColumns.php
namespace App\Models\Traits;

trait HandlesJsonColumns
{
    protected function asJson($value)
    {
        if (config('database.default') === 'sqlite' && is_string($value)) {
            return $value;
        }
        
        return parent::asJson($value);
    }
    
    protected function fromJson($value, $asObject = false)
    {
        if (empty($value)) {
            return $asObject ? (object) [] : [];
        }
        
        return parent::fromJson($value, $asObject);
    }
}
```

### 1.4 Test Database Configuration

#### Enhanced Test Case Base
```php
// tests/TestCase.php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Register macros for test environment
        \App\Database\Schema\BlueprintMacros::register();
        
        // SQLite specific settings
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
            DB::statement('PRAGMA journal_mode = WAL');
        }
    }
    
    protected function refreshTestDatabase()
    {
        if (config('database.default') === 'mysql_test') {
            // MySQL test database refresh
            Artisan::call('migrate:fresh', ['--database' => 'mysql_test']);
        } else {
            // SQLite in-memory
            Artisan::call('migrate:fresh');
        }
    }
}
```

### 1.5 Migration Rollback Strategy

#### Safe Rollback Implementation
```php
// app/Console/Commands/TestMigrationRollback.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestMigrationRollback extends Command
{
    protected $signature = 'test:migration-rollback {migration}';
    
    public function handle()
    {
        $migration = $this->argument('migration');
        
        DB::transaction(function () use ($migration) {
            // Create savepoint
            DB::statement('SAVEPOINT migration_test');
            
            try {
                // Run migration
                Artisan::call('migrate', ['--path' => "database/migrations/{$migration}"]);
                
                // Test migration
                $this->testMigration();
                
                // Rollback
                Artisan::call('migrate:rollback', ['--path' => "database/migrations/{$migration}"]);
                
                $this->info("Migration rollback successful");
            } catch (\Exception $e) {
                // Rollback to savepoint
                DB::statement('ROLLBACK TO SAVEPOINT migration_test');
                $this->error("Migration rollback failed: " . $e->getMessage());
            }
        });
    }
}
```

---

## 2. Test Infrastructure

### 2.1 MySQL Test Container Setup

#### Docker Compose Configuration
```yaml
# docker-compose.test.yml
version: '3.8'
services:
  mysql_test:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: test_root_password
      MYSQL_DATABASE: askproai_test
      MYSQL_USER: askproai_test
      MYSQL_PASSWORD: test_password
    ports:
      - "3307:3306"
    volumes:
      - mysql_test_data:/var/lib/mysql
    command: 
      - --default-authentication-plugin=mysql_native_password
      - --character-set-server=utf8mb4
      - --collation-server=utf8mb4_unicode_ci
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  mysql_test_data:
```

#### Test Environment Configuration
```php
// config/database.php
'connections' => [
    // ... existing connections
    
    'mysql_test' => [
        'driver' => 'mysql',
        'host' => env('DB_TEST_HOST', '127.0.0.1'),
        'port' => env('DB_TEST_PORT', '3307'),
        'database' => env('DB_TEST_DATABASE', 'askproai_test'),
        'username' => env('DB_TEST_USERNAME', 'askproai_test'),
        'password' => env('DB_TEST_PASSWORD', 'test_password'),
        'unix_socket' => env('DB_TEST_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ],
],
```

### 2.2 Test Data Factories

#### Enhanced Factory Structure
```php
// database/factories/CompanyFactory.php
namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;
    
    public function definition()
    {
        return [
            'name' => $this->faker->company(),
            'subdomain' => $this->faker->unique()->domainWord(),
            'settings' => [
                'timezone' => 'Europe/Berlin',
                'language' => 'de',
                'currency' => 'EUR',
                'booking_buffer_minutes' => 15,
                'max_advance_booking_days' => 30,
            ],
            'metadata' => [
                'created_via' => 'factory',
                'test_mode' => true,
            ],
            'retell_api_key' => 'test_' . $this->faker->uuid(),
            'calcom_api_key' => 'test_' . $this->faker->uuid(),
            'is_active' => true,
        ];
    }
    
    public function withFullSetup()
    {
        return $this->afterCreating(function (Company $company) {
            // Create branch
            $branch = $company->branches()->create([
                'name' => 'Main Branch',
                'phone_number' => '+49' . $this->faker->numerify('#########'),
                'is_active' => true,
                'calcom_event_type_id' => $this->faker->numberBetween(1000000, 9999999),
            ]);
            
            // Create staff
            $staff = $branch->staff()->createMany([
                ['name' => $this->faker->name(), 'email' => $this->faker->email()],
                ['name' => $this->faker->name(), 'email' => $this->faker->email()],
            ]);
            
            // Create services
            $services = $company->services()->createMany([
                ['name' => 'Consultation', 'duration' => 30, 'price' => 50.00],
                ['name' => 'Treatment', 'duration' => 60, 'price' => 100.00],
            ]);
        });
    }
}
```

### 2.3 Mock Service Implementations

#### Cal.com Mock Service
```php
// tests/Mocks/MockCalcomV2Service.php
namespace Tests\Mocks;

use App\Services\Calcom\CalcomV2Service;

class MockCalcomV2Service extends CalcomV2Service
{
    protected array $mockData = [];
    protected array $callLog = [];
    
    public function __construct()
    {
        // Skip parent constructor
    }
    
    public function setMockData(string $method, $data): self
    {
        $this->mockData[$method] = $data;
        return $this;
    }
    
    public function getAvailability(array $params): array
    {
        $this->callLog[] = ['method' => 'getAvailability', 'params' => $params];
        
        return $this->mockData['getAvailability'] ?? [
            'slots' => [
                ['time' => '2025-06-18T09:00:00+02:00'],
                ['time' => '2025-06-18T10:00:00+02:00'],
                ['time' => '2025-06-18T11:00:00+02:00'],
            ]
        ];
    }
    
    public function createBooking(array $data): array
    {
        $this->callLog[] = ['method' => 'createBooking', 'data' => $data];
        
        return $this->mockData['createBooking'] ?? [
            'id' => rand(1000000, 9999999),
            'uid' => 'mock-' . uniqid(),
            'status' => 'ACCEPTED',
            'startTime' => $data['start'],
            'endTime' => $data['end'],
        ];
    }
    
    public function getCallLog(): array
    {
        return $this->callLog;
    }
}
```

### 2.4 CI/CD Integration

#### GitHub Actions Workflow
```yaml
# .github/workflows/ci.yml
name: CI

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: askproai_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_sqlite, pdo_mysql, redis
        coverage: pcov

    - name: Copy .env
      run: cp .env.ci .env

    - name: Install Dependencies
      run: |
        composer install --no-interaction --prefer-dist --optimize-autoloader
        npm ci

    - name: Generate key
      run: php artisan key:generate

    - name: Run Migrations
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: askproai_test
        DB_USERNAME: root
        DB_PASSWORD: password
      run: php artisan migrate --force

    - name: Run Tests with Coverage
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
      run: |
        php artisan test --parallel --coverage --min=80
        
    - name: Upload Coverage
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml
```

### 2.5 Coverage Requirements

#### PHPUnit Configuration
```xml
<!-- phpunit.xml -->
<phpunit>
    <!-- ... existing configuration ... -->
    
    <coverage>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
        <exclude>
            <directory>./app/Console/Commands</directory>
            <directory>./app/Providers</directory>
            <file>./app/Http/Kernel.php</file>
        </exclude>
        <report>
            <html outputDirectory="coverage-html"/>
            <text outputFile="coverage.txt" showOnlySummary="true"/>
            <xml outputDirectory="coverage-xml"/>
            <clover outputFile="coverage.xml"/>
        </report>
    </coverage>
    
    <logging>
        <testdoxHtml outputFile="testdox.html"/>
        <junit outputFile="junit.xml"/>
    </logging>
</phpunit>
```

---

## 3. Production Monitoring Dashboard

### 3.1 Real-time Metrics Collection

#### Metrics Service
```php
// app/Services/Monitoring/MetricsCollector.php
namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MetricsCollector
{
    protected const CACHE_TTL = 60; // 1 minute
    
    public function collectSystemMetrics(): array
    {
        return Cache::remember('system_metrics', self::CACHE_TTL, function () {
            return [
                'cpu_usage' => $this->getCpuUsage(),
                'memory_usage' => $this->getMemoryUsage(),
                'disk_usage' => $this->getDiskUsage(),
                'database_connections' => $this->getDatabaseConnections(),
                'queue_metrics' => $this->getQueueMetrics(),
                'cache_metrics' => $this->getCacheMetrics(),
                'api_metrics' => $this->getApiMetrics(),
            ];
        });
    }
    
    protected function getCpuUsage(): array
    {
        $load = sys_getloadavg();
        $cpuCount = swoole_cpu_num() ?? 1;
        
        return [
            'load_1min' => $load[0],
            'load_5min' => $load[1],
            'load_15min' => $load[2],
            'cpu_count' => $cpuCount,
            'usage_percent' => round(($load[0] / $cpuCount) * 100, 2),
        ];
    }
    
    protected function getMemoryUsage(): array
    {
        $memInfo = file_get_contents('/proc/meminfo');
        preg_match_all('/(\w+):\s+(\d+)\s/', $memInfo, $matches);
        $memInfo = array_combine($matches[1], $matches[2]);
        
        $total = $memInfo['MemTotal'] ?? 0;
        $free = $memInfo['MemFree'] ?? 0;
        $buffers = $memInfo['Buffers'] ?? 0;
        $cached = $memInfo['Cached'] ?? 0;
        
        $used = $total - $free - $buffers - $cached;
        
        return [
            'total_mb' => round($total / 1024, 2),
            'used_mb' => round($used / 1024, 2),
            'free_mb' => round($free / 1024, 2),
            'usage_percent' => $total > 0 ? round(($used / $total) * 100, 2) : 0,
        ];
    }
    
    protected function getQueueMetrics(): array
    {
        return [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'processed_last_hour' => Cache::get('queue_processed_hour', 0),
            'average_wait_time' => Cache::get('queue_avg_wait', 0),
            'horizon_status' => $this->getHorizonStatus(),
        ];
    }
    
    protected function getApiMetrics(): array
    {
        $now = now();
        $hourAgo = $now->copy()->subHour();
        
        $metrics = DB::table('api_call_logs')
            ->where('created_at', '>=', $hourAgo)
            ->selectRaw('
                service,
                COUNT(*) as total_calls,
                AVG(duration_ms) as avg_duration,
                MAX(duration_ms) as max_duration,
                MIN(duration_ms) as min_duration,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count
            ')
            ->groupBy('service')
            ->get();
        
        return $metrics->mapWithKeys(function ($metric) {
            return [$metric->service => [
                'total_calls' => $metric->total_calls,
                'avg_duration_ms' => round($metric->avg_duration, 2),
                'max_duration_ms' => $metric->max_duration,
                'min_duration_ms' => $metric->min_duration,
                'error_rate' => $metric->total_calls > 0 
                    ? round(($metric->error_count / $metric->total_calls) * 100, 2) 
                    : 0,
            ]];
        })->toArray();
    }
}
```

### 3.2 Health Check Endpoints

#### Comprehensive Health Check
```php
// app/Http/Controllers/Api/SystemHealthController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\HealthChecker;
use Illuminate\Http\JsonResponse;

class SystemHealthController extends Controller
{
    public function __construct(private HealthChecker $healthChecker)
    {
    }
    
    public function comprehensive(): JsonResponse
    {
        $health = $this->healthChecker->runAllChecks();
        
        return response()->json($health, $health['status'] === 'healthy' ? 200 : 503)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
    
    public function liveness(): JsonResponse
    {
        // Simple liveness check for k8s
        return response()->json(['status' => 'alive']);
    }
    
    public function readiness(): JsonResponse
    {
        $checks = $this->healthChecker->runReadinessChecks();
        
        return response()->json($checks, $checks['ready'] ? 200 : 503);
    }
}
```

#### Health Checker Service
```php
// app/Services/Monitoring/HealthChecker.php
namespace App\Services\Monitoring;

class HealthChecker
{
    protected array $checks = [];
    
    public function runAllChecks(): array
    {
        $results = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => [],
        ];
        
        // Database
        $dbCheck = $this->checkDatabase();
        $results['checks']['database'] = $dbCheck;
        if ($dbCheck['status'] !== 'healthy') {
            $results['status'] = 'unhealthy';
        }
        
        // Redis
        $redisCheck = $this->checkRedis();
        $results['checks']['redis'] = $redisCheck;
        if ($redisCheck['status'] !== 'healthy') {
            $results['status'] = 'unhealthy';
        }
        
        // External APIs
        $results['checks']['calcom'] = $this->checkCalcom();
        $results['checks']['retell'] = $this->checkRetell();
        
        // Disk space
        $diskCheck = $this->checkDiskSpace();
        $results['checks']['disk'] = $diskCheck;
        if ($diskCheck['status'] === 'critical') {
            $results['status'] = 'unhealthy';
        }
        
        return $results;
    }
    
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $duration = (microtime(true) - $start) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round($duration, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function checkDiskSpace(): array
    {
        $path = storage_path();
        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $used = $total - $free;
        $percentUsed = ($used / $total) * 100;
        
        $status = 'healthy';
        if ($percentUsed > 90) {
            $status = 'critical';
        } elseif ($percentUsed > 80) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'usage_percent' => round($percentUsed, 2),
            'free_gb' => round($free / 1073741824, 2),
            'total_gb' => round($total / 1073741824, 2),
        ];
    }
}
```

### 3.3 Performance Dashboards

#### Grafana Dashboard Configuration
```json
{
  "dashboard": {
    "title": "AskProAI Production Monitoring",
    "panels": [
      {
        "title": "API Response Times",
        "targets": [{
          "expr": "histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))"
        }]
      },
      {
        "title": "Queue Processing Rate",
        "targets": [{
          "expr": "rate(laravel_queue_jobs_processed_total[5m])"
        }]
      },
      {
        "title": "Error Rate by Service",
        "targets": [{
          "expr": "rate(api_errors_total[5m]) by (service)"
        }]
      },
      {
        "title": "Database Query Performance",
        "targets": [{
          "expr": "histogram_quantile(0.95, rate(mysql_query_duration_seconds_bucket[5m]))"
        }]
      }
    ]
  }
}
```

### 3.4 Error Tracking Integration

#### Sentry Configuration
```php
// config/sentry.php
return [
    'dsn' => env('SENTRY_DSN'),
    
    'release' => env('SENTRY_RELEASE', git_short_hash()),
    
    'environment' => env('APP_ENV', 'production'),
    
    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
        'queue_info' => true,
        'command_info' => true,
    ],
    
    'tracing' => [
        'queue_job_transactions' => true,
        'queue_jobs' => true,
        'sql_queries' => true,
        'sql_origin' => true,
        'views' => true,
        'livewire' => true,
        'http_client_requests' => true,
        'redis_commands' => true,
        'redis_origin' => true,
        'default_integrations' => true,
        'missing_routes' => false,
    ],
    
    'send_default_pii' => false,
    
    'before_send' => function (\Sentry\Event $event) {
        // Filter sensitive data
        if ($event->getRequest()) {
            $request = $event->getRequest();
            $headers = $request->getHeaders();
            unset($headers['authorization']);
            unset($headers['x-api-key']);
            $request->setHeaders($headers);
        }
        
        return $event;
    },
];
```

### 3.5 Alert Configuration

#### Alert Rules
```yaml
# prometheus/alerts.yml
groups:
  - name: askproai_alerts
    interval: 30s
    rules:
      - alert: HighErrorRate
        expr: rate(api_errors_total[5m]) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "High API error rate"
          description: "Error rate is {{ $value | humanizePercentage }} for the last 5 minutes"
      
      - alert: DatabaseConnectionFailure
        expr: mysql_up == 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "Database connection lost"
      
      - alert: QueueBacklog
        expr: laravel_queue_size > 1000
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "Queue backlog growing"
          description: "{{ $value }} jobs in queue"
      
      - alert: DiskSpaceLow
        expr: node_filesystem_avail_bytes{mountpoint="/"} / node_filesystem_size_bytes{mountpoint="/"} < 0.1
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Disk space critically low"
          description: "Only {{ $value | humanizePercentage }} disk space remaining"
```

### 3.6 SLA Monitoring

#### SLA Dashboard Component
```php
// app/Services/Monitoring/SlaMonitor.php
namespace App\Services\Monitoring;

class SlaMonitor
{
    protected array $slaTargets = [
        'uptime' => 99.9,
        'api_response_time_ms' => 200,
        'booking_success_rate' => 95,
        'webhook_processing_time_ms' => 500,
    ];
    
    public function calculateCurrentSla(): array
    {
        $period = now()->subDays(30);
        
        return [
            'period' => [
                'start' => $period->toIso8601String(),
                'end' => now()->toIso8601String(),
            ],
            'metrics' => [
                'uptime' => $this->calculateUptime($period),
                'api_performance' => $this->calculateApiPerformance($period),
                'booking_reliability' => $this->calculateBookingReliability($period),
                'webhook_performance' => $this->calculateWebhookPerformance($period),
            ],
            'targets' => $this->slaTargets,
        ];
    }
    
    protected function calculateUptime($since): array
    {
        $totalMinutes = $since->diffInMinutes(now());
        $downtimeMinutes = DB::table('system_downtimes')
            ->where('started_at', '>=', $since)
            ->sum(DB::raw('TIMESTAMPDIFF(MINUTE, started_at, COALESCE(ended_at, NOW()))'));
        
        $uptimePercent = (($totalMinutes - $downtimeMinutes) / $totalMinutes) * 100;
        
        return [
            'percentage' => round($uptimePercent, 3),
            'meets_sla' => $uptimePercent >= $this->slaTargets['uptime'],
            'downtime_minutes' => $downtimeMinutes,
        ];
    }
}
```

---

## 4. Logging Strategy

### 4.1 Structured Logging Format

#### Log Formatter
```php
// app/Logging/StructuredFormatter.php
namespace App\Logging;

use Monolog\Formatter\JsonFormatter;

class StructuredFormatter extends JsonFormatter
{
    public function format(array $record): string
    {
        $formatted = parent::format($record);
        $data = json_decode($formatted, true);
        
        // Add standard fields
        $data['timestamp'] = $record['datetime']->format('c');
        $data['environment'] = config('app.env');
        $data['application'] = config('app.name');
        $data['version'] = config('app.version');
        
        // Add request context
        if ($request = request()) {
            $data['request'] = [
                'id' => $request->header('X-Request-ID'),
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
            ];
        }
        
        // Add correlation ID
        $data['correlation_id'] = app('correlation_id');
        
        return json_encode($data) . PHP_EOL;
    }
}
```

### 4.2 Correlation ID Implementation

#### Correlation ID Middleware
```php
// app/Http/Middleware/CorrelationIdMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        
        // Store in container
        app()->instance('correlation_id', $correlationId);
        
        // Add to request
        $request->headers->set('X-Correlation-ID', $correlationId);
        
        // Add to Log context
        \Log::withContext(['correlation_id' => $correlationId]);
        
        $response = $next($request);
        
        // Add to response
        $response->headers->set('X-Correlation-ID', $correlationId);
        
        return $response;
    }
}
```

#### Service Integration
```php
// app/Services/Logging/CorrelationLogger.php
namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;

trait CorrelationLogger
{
    protected function logWithCorrelation(string $level, string $message, array $context = []): void
    {
        $context['correlation_id'] = app('correlation_id');
        $context['service'] = class_basename($this);
        $context['method'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown';
        
        Log::$level($message, $context);
    }
    
    protected function logInfo(string $message, array $context = []): void
    {
        $this->logWithCorrelation('info', $message, $context);
    }
    
    protected function logError(string $message, array $context = []): void
    {
        $this->logWithCorrelation('error', $message, $context);
    }
    
    protected function logDebug(string $message, array $context = []): void
    {
        if (config('app.debug')) {
            $this->logWithCorrelation('debug', $message, $context);
        }
    }
}
```

### 4.3 Log Aggregation

#### Elasticsearch Configuration
```php
// config/logging.php
'channels' => [
    'elasticsearch' => [
        'driver' => 'custom',
        'via' => App\Logging\ElasticsearchLogger::class,
        'hosts' => [
            env('ELASTICSEARCH_HOST', 'localhost:9200'),
        ],
        'index' => env('ELASTICSEARCH_INDEX', 'askproai-logs'),
        'type' => '_doc',
    ],
    
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'elasticsearch'],
        'ignore_exceptions' => false,
    ],
],
```

#### Elasticsearch Logger
```php
// app/Logging/ElasticsearchLogger.php
namespace App\Logging;

use Elasticsearch\ClientBuilder;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Logger;

class ElasticsearchLogger
{
    public function __invoke(array $config)
    {
        $client = ClientBuilder::create()
            ->setHosts($config['hosts'])
            ->build();
        
        $options = [
            'index' => $config['index'] . '-' . date('Y.m.d'),
            'type' => $config['type'],
        ];
        
        $handler = new ElasticsearchHandler($client, $options);
        $handler->setFormatter(new StructuredFormatter());
        
        return new Logger('elasticsearch', [$handler]);
    }
}
```

### 4.4 Debug Mode Configuration

#### Debug Helper
```php
// app/Helpers/DebugHelper.php
namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class DebugHelper
{
    public static function isDebugMode(): bool
    {
        return config('app.debug') || request()->has('debug');
    }
    
    public static function logIfDebug(string $message, array $context = []): void
    {
        if (self::isDebugMode()) {
            Log::debug($message, array_merge($context, [
                'debug_mode' => true,
                'caller' => self::getCaller(),
            ]));
        }
    }
    
    public static function getCaller(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        
        return [
            'file' => $trace[1]['file'] ?? 'unknown',
            'line' => $trace[1]['line'] ?? 0,
            'function' => $trace[2]['function'] ?? 'unknown',
            'class' => $trace[2]['class'] ?? 'unknown',
        ];
    }
    
    public static function measure(string $operation, callable $callback)
    {
        if (!self::isDebugMode()) {
            return $callback();
        }
        
        $start = microtime(true);
        $memory = memory_get_usage();
        
        try {
            $result = $callback();
            
            self::logIfDebug("Operation completed: {$operation}", [
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                'memory_used' => memory_get_usage() - $memory,
                'status' => 'success',
            ]);
            
            return $result;
        } catch (\Throwable $e) {
            self::logIfDebug("Operation failed: {$operation}", [
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                'error' => $e->getMessage(),
                'status' => 'failed',
            ]);
            
            throw $e;
        }
    }
}
```

#### Request Logging
```php
// app/Http/Middleware/RequestLogging.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestLogging
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        
        // Log request
        Log::info('Incoming request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'query' => $request->query(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000;
        
        // Log response
        Log::info('Outgoing response', [
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'memory_peak_mb' => round(memory_get_peak_usage() / 1048576, 2),
        ]);
        
        return $response;
    }
}
```

## Implementation Priority

1. **Phase 1 - Test Infrastructure** (Week 1)
   - SQLite migration fixes
   - MySQL test container setup
   - Basic test factories
   - CI/CD pipeline

2. **Phase 2 - Monitoring Foundation** (Week 2)
   - Health check endpoints
   - Metrics collection service
   - Basic Grafana dashboards
   - Error tracking integration

3. **Phase 3 - Advanced Monitoring** (Week 3)
   - Real-time performance dashboards
   - SLA monitoring
   - Alert configuration
   - Log aggregation

4. **Phase 4 - Logging Enhancement** (Week 4)
   - Structured logging implementation
   - Correlation ID system
   - Debug mode configuration
   - Request/response logging

## Success Metrics

- Test coverage > 80%
- All migrations work on both SQLite and MySQL
- CI/CD pipeline runs in < 10 minutes
- Monitoring dashboard shows real-time data
- All critical errors are tracked and alerted
- Correlation IDs allow full request tracing
- Debug mode provides detailed performance insights