# Business Portal Performance Testing Guide

## 🎯 Ziele

Dieses Dokument definiert Performance-Standards und Testing-Verfahren für das Business Portal, um eine optimale Benutzererfahrung sicherzustellen.

---

## 📊 Performance-Ziele

### Response Time Targets
| Bereich | Zielzeit | Maximal akzeptabel |
|---------|----------|-------------------|
| Login | < 1s | 2s |
| Dashboard | < 2s | 3s |
| Listen (Calls/Customers) | < 1.5s | 2.5s |
| Detail-Seiten | < 1s | 2s |
| API-Endpoints | < 500ms | 1s |
| Suche/Filter | < 800ms | 1.5s |
| Export (CSV/PDF) | < 3s | 5s |

### Throughput Targets
| Metrik | Ziel | Minimum |
|--------|------|---------|
| Concurrent Users | 100 | 50 |
| Requests/Second | 500 | 250 |
| API Calls/Minute | 3000 | 1500 |
| Database Queries/Second | 1000 | 500 |

### Resource Usage Limits
| Resource | Normal | Maximum |
|----------|--------|---------|
| CPU Usage | < 50% | 80% |
| Memory Usage | < 2GB | 4GB |
| Database Connections | < 50 | 100 |
| Redis Memory | < 1GB | 2GB |

---

## 🛠️ Performance Test Tools

### 1. Apache JMeter Setup

```bash
# Installation
wget https://downloads.apache.org/jmeter/binaries/apache-jmeter-5.6.tgz
tar -xzf apache-jmeter-5.6.tgz
cd apache-jmeter-5.6/bin

# Start JMeter
./jmeter.sh
```

### 2. K6 Load Testing

```bash
# Installation
sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update
sudo apt-get install k6

# Run basic test
k6 run tests/performance/basic-load.js
```

### 3. Laravel Telescope (Development)

```bash
# Installation
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# Access at: /telescope
```

---

## 📝 Performance Test Scenarios

### Scenario 1: Login Load Test

```javascript
// tests/performance/k6/login-load.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '2m', target: 10 },  // Ramp up to 10 users
    { duration: '5m', target: 50 },  // Stay at 50 users
    { duration: '2m', target: 100 }, // Peak load
    { duration: '5m', target: 100 }, // Stay at peak
    { duration: '2m', target: 0 },   // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'], // 95% of requests under 2s
    errors: ['rate<0.1'], // Error rate under 10%
  },
};

const BASE_URL = 'https://api.askproai.de';

export default function () {
  // Get login page
  let res = http.get(`${BASE_URL}/business/login`);
  check(res, {
    'login page loaded': (r) => r.status === 200,
  });

  // Extract CSRF token
  const csrfToken = res.html().find('input[name="_token"]').attr('value');

  // Login attempt
  res = http.post(`${BASE_URL}/business/login`, {
    email: 'test@example.com',
    password: 'Test123!',
    _token: csrfToken,
  });

  const success = check(res, {
    'login successful': (r) => r.status === 302 || r.status === 200,
    'response time OK': (r) => r.timings.duration < 2000,
  });

  errorRate.add(!success);
  sleep(1);
}
```

### Scenario 2: Dashboard Stress Test

```javascript
// tests/performance/k6/dashboard-stress.js
import http from 'k6/http';
import { check } from 'k6';

export const options = {
  scenarios: {
    stress: {
      executor: 'ramping-arrival-rate',
      startRate: 50,
      timeUnit: '1s',
      preAllocatedVUs: 50,
      maxVUs: 200,
      stages: [
        { duration: '2m', target: 100 },
        { duration: '5m', target: 200 },
        { duration: '2m', target: 300 }, // Stress point
        { duration: '5m', target: 300 },
        { duration: '2m', target: 0 },
      ],
    },
  },
  thresholds: {
    http_req_duration: ['p(99)<3000'],
    http_req_failed: ['rate<0.05'],
  },
};

export default function () {
  const token = login(); // Helper function to get auth token
  
  const params = {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  };

  // Dashboard API calls
  const responses = http.batch([
    ['GET', `${BASE_URL}/api/business/dashboard/stats`, null, params],
    ['GET', `${BASE_URL}/api/business/dashboard/recent-calls`, null, params],
    ['GET', `${BASE_URL}/api/business/dashboard/upcoming-appointments`, null, params],
  ]);

  responses.forEach((res) => {
    check(res, {
      'status is 200': (r) => r.status === 200,
      'response time < 1s': (r) => r.timings.duration < 1000,
    });
  });
}
```

### Scenario 3: Call List Pagination

```javascript
// tests/performance/k6/call-list-pagination.js
export const options = {
  scenarios: {
    pagination_test: {
      executor: 'per-vu-iterations',
      vus: 20,
      iterations: 50,
      maxDuration: '10m',
    },
  },
};

export default function () {
  const token = login();
  
  // Test different page sizes
  const pageSizes = [10, 20, 50, 100];
  
  pageSizes.forEach(size => {
    const res = http.get(
      `${BASE_URL}/api/business/calls?per_page=${size}`,
      {
        headers: { 'Authorization': `Bearer ${token}` }
      }
    );
    
    check(res, {
      [`page size ${size} loads`]: (r) => r.status === 200,
      [`page size ${size} performance`]: (r) => r.timings.duration < (size * 20), // 20ms per item
    });
  });
}
```

---

## 🔍 Laravel Performance Analysis

### 1. Query Performance Monitoring

```php
// app/Http/Middleware/QueryPerformanceMonitor.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryPerformanceMonitor
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
            
            if ($totalTime > 1000) { // Log if queries take more than 1 second
                Log::warning('Slow query detected', [
                    'url' => $request->fullUrl(),
                    'total_time' => $totalTime,
                    'query_count' => count($queries),
                    'queries' => $queries
                ]);
            }
        }

        return $response;
    }
}
```

### 2. Response Time Tracking

```php
// app/Http/Middleware/ResponseTimeTracker.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class ResponseTimeTracker
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds
        
        // Track in Redis for real-time monitoring
        $key = 'performance:' . $request->route()->getName() ?? 'unknown';
        Redis::zadd($key, time(), $duration);
        Redis::expire($key, 3600); // Keep last hour
        
        // Add header for debugging
        $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
        
        return $response;
    }
}
```

### 3. Cache Performance Analysis

```bash
# Artisan command for cache analysis
php artisan cache:analyze

# Output example:
# Cache Hit Rate: 87%
# Average Response Time (cached): 12ms
# Average Response Time (uncached): 234ms
# Most Missed Keys: user.1234, company.settings.5
```

---

## 📈 Database Performance Optimization

### 1. Index Analysis

```sql
-- Find missing indexes
SELECT 
    s.table_name,
    s.column_name,
    s.cardinality,
    t.table_rows
FROM information_schema.statistics s
JOIN information_schema.tables t 
    ON s.table_schema = t.table_schema 
    AND s.table_name = t.table_name
WHERE s.table_schema = 'askproai_db'
    AND s.cardinality < (t.table_rows * 0.1)
    AND t.table_rows > 1000
ORDER BY t.table_rows DESC;

-- Analyze slow queries
SELECT 
    query_time,
    lock_time,
    rows_sent,
    rows_examined,
    sql_text
FROM mysql.slow_log
WHERE query_time > 1
ORDER BY query_time DESC
LIMIT 20;
```

### 2. Query Optimization Examples

```php
// Bad: N+1 Query Problem
$calls = Call::all();
foreach ($calls as $call) {
    echo $call->customer->name; // Extra query for each call
}

// Good: Eager Loading
$calls = Call::with('customer')->get();
foreach ($calls as $call) {
    echo $call->customer->name; // No extra queries
}

// Better: Select only needed columns
$calls = Call::with('customer:id,name')
    ->select('id', 'customer_id', 'duration', 'status')
    ->get();
```

### 3. Database Connection Pooling

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => [
        PDO::ATTR_PERSISTENT => true, // Enable persistent connections
        PDO::ATTR_EMULATE_PREPARES => true,
    ],
],
```

---

## 🚀 Frontend Performance

### 1. Alpine.js Performance Monitoring

```javascript
// resources/js/performance-monitor.js
window.performanceMonitor = {
    init() {
        // Monitor Alpine component initialization
        document.addEventListener('alpine:init', () => {
            console.time('Alpine Init');
        });
        
        document.addEventListener('alpine:initialized', () => {
            console.timeEnd('Alpine Init');
            
            // Log component count
            const components = document.querySelectorAll('[x-data]').length;
            console.log(`Alpine components initialized: ${components}`);
            
            // Send metrics to backend
            if (window.performance && performance.timing) {
                const timing = performance.timing;
                const pageLoad = timing.loadEventEnd - timing.navigationStart;
                const domReady = timing.domContentLoadedEventEnd - timing.navigationStart;
                
                fetch('/api/metrics/frontend', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        page_load: pageLoad,
                        dom_ready: domReady,
                        alpine_components: components,
                        url: window.location.pathname
                    })
                });
            }
        });
    },
    
    trackApiCall(endpoint, duration) {
        // Track API call performance
        if (duration > 1000) {
            console.warn(`Slow API call to ${endpoint}: ${duration}ms`);
        }
    }
};
```

### 2. Asset Optimization

```javascript
// vite.config.js performance optimizations
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { compression } from 'vite-plugin-compression2';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        compression({
            algorithm: 'gzip',
            threshold: 10240,
        }),
        compression({
            algorithm: 'brotliCompress',
            threshold: 10240,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'alpine': ['alpinejs'],
                    'charts': ['chart.js'],
                },
            },
        },
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
    },
});
```

### 3. Lazy Loading Implementation

```html
<!-- Blade component for lazy loading -->
@component('components.lazy-load')
    @slot('placeholder')
        <div class="skeleton-loader h-64 w-full"></div>
    @endslot
    
    @slot('content')
        <div x-data="{ loaded: false }"
             x-intersect="loaded = true">
            <template x-if="loaded">
                <!-- Heavy content here -->
                @include('partials.heavy-widget')
            </template>
        </div>
    @endslot
@endcomponent
```

---

## 📊 Performance Monitoring Dashboard

### Real-time Metrics Display

```php
// app/Http/Controllers/PerformanceController.php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class PerformanceController extends Controller
{
    public function dashboard()
    {
        $metrics = [
            'response_times' => $this->getResponseTimes(),
            'database_stats' => $this->getDatabaseStats(),
            'cache_stats' => $this->getCacheStats(),
            'queue_stats' => $this->getQueueStats(),
            'system_resources' => $this->getSystemResources(),
        ];
        
        return view('admin.performance-dashboard', compact('metrics'));
    }
    
    private function getResponseTimes()
    {
        $routes = ['dashboard', 'calls.index', 'calls.show'];
        $times = [];
        
        foreach ($routes as $route) {
            $key = "performance:$route";
            $values = Redis::zrange($key, -100, -1, 'WITHSCORES');
            
            if (!empty($values)) {
                $times[$route] = [
                    'avg' => array_sum($values) / count($values),
                    'min' => min($values),
                    'max' => max($values),
                    'p95' => $this->percentile($values, 95),
                ];
            }
        }
        
        return $times;
    }
    
    private function getDatabaseStats()
    {
        return DB::select("
            SELECT 
                COUNT(*) as total_connections,
                SUM(TIME > 0) as active_connections,
                MAX(TIME) as longest_query_time
            FROM information_schema.PROCESSLIST
            WHERE DB = 'askproai_db'
        ")[0];
    }
    
    private function getCacheStats()
    {
        $info = Redis::info();
        
        return [
            'hit_rate' => round(
                ($info['keyspace_hits'] / 
                ($info['keyspace_hits'] + $info['keyspace_misses'])) * 100, 
                2
            ),
            'memory_usage' => $info['used_memory_human'],
            'total_keys' => $info['db0']['keys'] ?? 0,
        ];
    }
}
```

---

## 🔧 Performance Optimization Checklist

### Backend Optimizations
- [ ] Enable OpCache
- [ ] Configure Redis for sessions
- [ ] Enable query caching
- [ ] Implement eager loading
- [ ] Add database indexes
- [ ] Enable HTTP/2
- [ ] Configure CDN
- [ ] Enable Gzip compression
- [ ] Optimize autoloader
- [ ] Use queue for heavy tasks

### Frontend Optimizations
- [ ] Minify CSS/JS
- [ ] Enable browser caching
- [ ] Implement lazy loading
- [ ] Optimize images (WebP)
- [ ] Use CSS sprites
- [ ] Reduce HTTP requests
- [ ] Implement service worker
- [ ] Use async/defer for scripts
- [ ] Enable resource hints
- [ ] Implement critical CSS

### Database Optimizations
- [ ] Add missing indexes
- [ ] Optimize slow queries
- [ ] Enable query cache
- [ ] Use read replicas
- [ ] Implement partitioning
- [ ] Regular VACUUM/ANALYZE
- [ ] Monitor connection pool
- [ ] Optimize JOIN queries
- [ ] Use prepared statements
- [ ] Archive old data

---

## 📈 Performance Reports

### Weekly Performance Report Template

```markdown
# Performance Report - Week {{week_number}}

## Executive Summary
- Average Response Time: {{avg_response}}ms (Target: <500ms)
- Peak Concurrent Users: {{peak_users}} (Target: 100)
- Error Rate: {{error_rate}}% (Target: <1%)
- Uptime: {{uptime}}% (Target: 99.9%)

## Key Metrics
| Metric | This Week | Last Week | Change |
|--------|-----------|-----------|---------|
| Avg Response Time | {{current_avg}} | {{last_avg}} | {{change}}% |
| P95 Response Time | {{current_p95}} | {{last_p95}} | {{change}}% |
| Requests/Second | {{current_rps}} | {{last_rps}} | {{change}}% |
| Error Rate | {{current_errors}} | {{last_errors}} | {{change}}% |

## Identified Issues
1. {{issue_1}}
   - Impact: {{impact}}
   - Action: {{action}}
   
2. {{issue_2}}
   - Impact: {{impact}}
   - Action: {{action}}

## Recommendations
- {{recommendation_1}}
- {{recommendation_2}}
- {{recommendation_3}}

## Next Steps
- [ ] {{action_item_1}}
- [ ] {{action_item_2}}
- [ ] {{action_item_3}}
```

---

## 🚨 Performance Alerting

### Monitoring Setup

```yaml
# prometheus/alerts.yml
groups:
  - name: performance_alerts
    rules:
      - alert: HighResponseTime
        expr: http_request_duration_seconds{quantile="0.95"} > 2
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High response time detected"
          description: "95th percentile response time is above 2 seconds"
      
      - alert: HighErrorRate
        expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "High error rate detected"
          description: "Error rate is above 5%"
      
      - alert: DatabaseConnectionPoolExhausted
        expr: mysql_global_status_threads_connected / mysql_global_variables_max_connections > 0.8
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Database connection pool nearly exhausted"
          description: "More than 80% of database connections are in use"
```

---

## 📝 Performance Testing Best Practices

1. **Test in Production-like Environment**
   - Same hardware specs
   - Same network conditions
   - Same data volume
   - Same concurrent users

2. **Establish Baselines**
   - Run tests before changes
   - Document current performance
   - Set realistic targets
   - Track trends over time

3. **Test Regularly**
   - After each deployment
   - Weekly full suite
   - Daily smoke tests
   - Before major releases

4. **Monitor Real Users**
   - Use RUM (Real User Monitoring)
   - Track actual user experience
   - Identify geographical issues
   - Monitor different devices/browsers

5. **Automate Performance Tests**
   - CI/CD integration
   - Automated alerting
   - Performance budgets
   - Regression detection