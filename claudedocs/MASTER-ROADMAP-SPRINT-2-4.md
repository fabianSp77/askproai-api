# 🚀 Master Roadmap: Sprint 2-4 Umsetzungsplan

**Erstellt**: 2025-09-30 14:00 UTC
**Basis**: Ultrathink-Analyse mit 4 parallelen Spezial-Agenten
**Scope**: 6-8 Wochen (3 Sprints)
**Ziel**: Production-ready System für 100+ Companies

---

## 📊 Executive Summary

### Kritische Erkenntnisse

**🔴 SICHERHEIT (P0 - Kritisch)**
- **6 kritische Vulnerabilities** (VULN-004 bis VULN-009) identifiziert
- **9 Endpoints komplett unauthenticated** durch fehlende Middleware-Registrierung
- **VULN-005**: Schwerwiegendste Lücke - `retell.function.whitelist` nicht in Kernel registriert

**⚡ PERFORMANCE (P0 - Kritisch)**
- **50-65% Performance-Verbesserung möglich** mit Quick Wins
- Webhook Response Time: 635-1690ms → 300-600ms
- 3 Quick Wins implementierbar in < 1 Stunde

**🏗️ ARCHITEKTUR (P1 - Hoch)**
- **SQLite in Production**: Kritischer Blocker für Skalierung
- **Controller-Komplexität**: 2091 Zeilen (RetellWebhookController)
- **Skalierbarkeit**: 60% ready für 100+ companies

**🧪 TESTS (P1 - Hoch)**
- **99.2% Test Failure Rate** (260/262 Tests failing)
- **Root Cause identifiziert**: phpunit.xml Fehlkonfiguration
- **4% Controller Coverage**, 0% Model Coverage

---

## 🎯 Priorisierung nach Impact & Dringlichkeit

### Kritikalitäts-Matrix

```
IMPACT →     LOW         MEDIUM        HIGH         CRITICAL
URGENCY ↓
CRITICAL  │              │             │  VULN-005    │
          │              │             │  Test Fix    │
HIGH      │              │  Arch Review│  VULN-004-009│
          │              │  DB Migration│ Perf Quick Wins│
MEDIUM    │  Refactoring │  Service    │  Controller  │
          │              │  Layer      │  Decompose   │
LOW       │  DDD         │  Event-     │  Microservices│
          │  Implementation│ Sourcing   │              │
```

---

## 🚨 SPRINT 2: Security & Quick Wins (2 Wochen)

**Ziel**: Kritische Sicherheitslücken schließen + Performance verdoppeln
**Aufwand**: 80 Stunden
**Team**: 1-2 Entwickler

### Week 1: Critical Security Fixes

#### 🔴 Task 2.1: VULN-005 - Middleware Registration Fix
**Dringlichkeit**: CRITICAL | **Aufwand**: 30 Min | **Impact**: 9 Endpoints absichern

**Problem**:
```php
// routes/api.php verwendet:
Route::middleware('retell.function.whitelist')->group(...);

// Aber app/Http/Kernel.php hat KEINE Registrierung:
protected $middlewareAliases = [
    // 'retell.function.whitelist' => ← FEHLT!
];
```

**Lösung**:
```php
// File: app/Http/Kernel.php:67
protected $middlewareAliases = [
    // ... existing middlewares ...
    'retell.function.whitelist' => \App\Http\Middleware\VerifyRetellFunctionSignatureWithWhitelist::class,
];
```

**Betroffene Endpoints**:
1. `/api/retell/book-appointment` (60 req/min limit - aber unauthenticated!)
2. `/api/retell/cancel-appointment`
3. `/api/retell/collect-appointment-data`
4. `/api/retell/list-services`
5. `/api/retell/check-availability`
6. `/api/retell/get-alternatives`
7. `/api/retell/collect-customer-info`
8. `/api/retell/query-customer-appointments`
9. `/api/retell/get-branches`

**Verifikation**:
```bash
# Test: Endpoint ohne Auth sollte 401 zurückgeben
curl -X POST https://api.askproai.de/api/retell/book-appointment -d '{}'
# Expected: 401 Unauthorized

# Nach Fix:
php artisan route:list --name=retell
# Verify: Alle retell.* routes haben Middleware
```

**Tests erstellen**:
```php
// tests/Integration/Security/MiddlewareRegistrationTest.php
public function test_retell_endpoints_require_authentication(): void {
    $endpoints = [
        '/api/retell/book-appointment',
        '/api/retell/cancel-appointment',
        // ... alle 9 endpoints
    ];

    foreach ($endpoints as $endpoint) {
        $response = $this->postJson($endpoint, []);
        $this->assertEquals(401, $response->status());
    }
}
```

---

#### 🔴 Task 2.2: VULN-004 - IP Whitelist Bypass Fix
**Dringlichkeit**: CRITICAL | **Aufwand**: 2h | **Impact**: AWS EC2 kann nicht mehr Auth bypassen

**Problem**:
```php
// File: app/Http/Middleware/VerifyRetellFunctionSignatureWithWhitelist.php:45-57
if ($this->isWhitelistedIp($request->ip())) {
    Log::info('Request from whitelisted IP', ['ip' => $request->ip()]);
    return $next($request);  // ← Bypass ohne weitere Validierung!
}

// AWS IP Ranges sind gewhitelisted:
private function isWhitelistedIp(string $ip): bool {
    $whitelistedRanges = [
        '35.180.0.0/16',  // AWS EU-WEST-3 (Retell)
        // Aber: JEDER mit AWS EC2 kann diese IPs bekommen!
    ];
}
```

**Sicherheitsrisiko**:
- Angreifer startet EC2 Instance in eu-west-3
- Bekommt IP aus 35.180.0.0/16 Range
- Kann jetzt ALLE Retell-Endpoints ohne Signature aufrufen

**Lösung (Option A - Empfohlen)**: IP Whitelist komplett entfernen
```php
// File: app/Http/Middleware/VerifyRetellFunctionSignatureWithWhitelist.php
public function handle(Request $request, Closure $next): Response
{
    // REMOVED: IP whitelist bypass
    // ALWAYS require signature verification

    $signature = $request->header('X-Retell-Function-Signature');
    if (!$signature) {
        return response()->json(['error' => 'Missing function signature'], 401);
    }

    $secret = config('services.retellai.function_secret');
    if (!$secret) {
        return response()->json(['error' => 'Function secret not configured'], 500);
    }

    $payload = $request->getContent();
    $expectedSignature = hash_hmac('sha256', $payload, $secret);

    if (!hash_equals($expectedSignature, trim($signature))) {
        Log::warning('Invalid function signature', [
            'ip' => $request->ip(),
            'path' => $request->path(),
        ]);
        return response()->json(['error' => 'Invalid function signature'], 401);
    }

    return $next($request);
}
```

**Lösung (Option B - Falls IP Whitelist benötigt)**: Spezifische IPs statt Ranges
```php
private function isWhitelistedIp(string $ip): bool {
    $whitelistedIps = [
        '35.180.123.45',  // Retell Production Server 1
        '35.180.123.46',  // Retell Production Server 2
    ];
    return in_array($ip, $whitelistedIps, true);
}

// WICHTIG: Auch mit IP Whitelist IMMER Signature prüfen!
if ($this->isWhitelistedIp($request->ip())) {
    Log::info('Request from whitelisted IP', ['ip' => $request->ip()]);
    // Continue to signature verification (don't bypass!)
}
```

**Tests**:
```php
// tests/Unit/Middleware/VerifyRetellFunctionSignatureWithWhitelistTest.php
public function test_ip_whitelist_does_not_bypass_signature_check(): void {
    $middleware = new VerifyRetellFunctionSignatureWithWhitelist();

    // Request from whitelisted IP but without signature
    $request = Request::create('/api/retell/book-appointment', 'POST');
    $request->server->set('REMOTE_ADDR', '35.180.123.45');

    $response = $middleware->handle($request, fn() => response('OK'));

    // Should reject even from whitelisted IP
    $this->assertEquals(401, $response->status());
    $this->assertStringContainsString('signature', $response->getContent());
}
```

---

#### 🔴 Task 2.3: VULN-006 - Diagnostic Endpoint Absichern
**Dringlichkeit**: HIGH | **Aufwand**: 15 Min | **Impact**: Sensitive Info nicht mehr public

**Problem**:
```php
// routes/api.php:155
Route::get('/diagnostic-info', [DiagnosticController::class, 'getDiagnosticInfo']);
// ← KEINE Middleware! Komplett public!

// Gibt zurück:
{
  "database_connection": "mysql",
  "database_host": "127.0.0.1",
  "database_name": "askproai_db",
  "redis_host": "127.0.0.1",
  "queue_connection": "redis",
  "app_environment": "production",
  "php_version": "8.3.23",
  "laravel_version": "11.46.0"
}
```

**Lösung**:
```php
// routes/api.php:155
Route::middleware(['auth:sanctum', 'role:admin'])
    ->get('/diagnostic-info', [DiagnosticController::class, 'getDiagnosticInfo']);
```

**Oder noch besser**: Diagnostic Endpoint komplett entfernen in Production
```php
// routes/api.php:155
if (app()->environment('local', 'staging')) {
    Route::get('/diagnostic-info', [DiagnosticController::class, 'getDiagnosticInfo']);
}
```

---

#### 🔴 Task 2.4: VULN-007 - X-Forwarded-For Spoofing Fix
**Dringlichkeit**: HIGH | **Aufwand**: 1h | **Impact**: IP-basierte Auth nicht mehr bypassbar

**Problem**:
```php
// app/Http/Middleware/VerifyRetellFunctionSignatureWithWhitelist.php:45
if ($this->isWhitelistedIp($request->ip())) {
    // ...
}

// $request->ip() verwendet X-Forwarded-For Header
// Angreifer kann einfach Header setzen:
curl -H "X-Forwarded-For: 35.180.0.1" https://api.askproai.de/api/retell/book-appointment
```

**Lösung**: Trusted Proxies konfigurieren
```php
// app/Http/Middleware/TrustProxies.php:17
protected $proxies = [
    '10.0.0.0/8',      // Internal load balancer
    '172.16.0.0/12',   // Docker network
    '192.168.0.0/16',  // Private network
];

// Oder falls keine Proxies:
protected $proxies = null;  // Don't trust any X-Forwarded-* headers
```

---

#### 🟡 Task 2.5: VULN-008 - Rate Limiting Implementation
**Dringlichkeit**: HIGH | **Aufwand**: 3h | **Impact**: DoS Protection

**Problem**: Keine Rate Limits auf kritischen Endpoints

**Lösung**:
```php
// app/Providers/RouteServiceProvider.php:35
RateLimiter::for('retell-webhooks', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});

RateLimiter::for('retell-bookings', function (Request $request) {
    // Per company_id und service_id limitieren
    $callContext = $request->attributes->get('call_context');
    $key = $callContext['company_id'] . ':' . ($request->input('service_id') ?? 'all');

    return Limit::perMinute(10)->by($key)->response(function() {
        return response()->json([
            'error' => 'Too many booking attempts. Please try again later.'
        ], 429);
    });
});

// routes/api.php
Route::middleware(['retell.webhook.signature', 'throttle:retell-webhooks'])
    ->post('/webhooks/retell', RetellWebhookController::class);

Route::middleware(['retell.function.whitelist', 'throttle:retell-bookings'])
    ->group(function() {
        Route::post('/retell/book-appointment', ...);
        Route::post('/retell/cancel-appointment', ...);
    });
```

---

#### 🟡 Task 2.6: VULN-009 - Mass Assignment Protection
**Dringlichkeit**: MEDIUM | **Aufwand**: 2h | **Impact**: Prevent data manipulation

**Problem**:
```php
// app/Models/Call.php:13
protected $fillable = [
    'external_id', 'customer_id', 'customer_name', /* ... 77 more fields */
];
// Alle 77 Felder sind mass-assignable!

// Angreifer kann senden:
{
  "customer_name": "Test",
  "cost": 0,            // ← Set cost to 0!
  "company_id": 999,    // ← Switch company!
  "is_admin": true      // ← Elevate privileges!
}
```

**Lösung**: $guarded statt $fillable
```php
// app/Models/Call.php:13
protected $guarded = [
    'id',
    'cost',
    'cost_cents',
    'base_cost',
    'company_id',
    'customer_id',
    'platform_profit',
    'reseller_profit',
    'total_profit',
    // Alle finanziellen und kritischen Felder
];

// Oder noch besser: Explizit nur erlaubte Felder
protected $fillable = [
    'customer_name',
    'from_number',
    'to_number',
    'notes',
];
```

**Review aller Models**:
```bash
grep -r "protected \$fillable = \[" app/Models/ | wc -l
# Output: 37 models

# Check welche Models zu viele fillable fields haben
for file in app/Models/*.php; do
    count=$(grep -A 100 'protected $fillable' "$file" | grep -c "'")
    if [ "$count" -gt 20 ]; then
        echo "$file: $count fields"
    fi
done
```

---

### Week 2: Performance Quick Wins

#### ⚡ Task 2.7: Parallel Cal.com API Calls
**Dringlichkeit**: HIGH | **Aufwand**: 15 Min | **Impact**: 50% Response Time Reduktion

**Implementierung**:
```php
// File: app/Http/Controllers/RetellWebhookController.php:2009
private function getQuickAvailability(Service $service): array
{
    $calcomService = new CalcomService();
    $today = Carbon::today();
    $tomorrow = Carbon::tomorrow();

    // BEFORE (Serial): 600-1600ms
    // $todayResponse = $calcomService->getAvailableSlots(...);    // 300-800ms
    // $tomorrowResponse = $calcomService->getAvailableSlots(...); // 300-800ms

    // AFTER (Parallel): 300-800ms
    $responses = Http::pool(fn ($pool) => [
        $pool->as('today')->withHeaders([
            'Authorization' => 'Bearer ' . config('services.calcom.api_key')
        ])->timeout(5)->get($this->buildAvailabilityUrl($service, $today)),

        $pool->as('tomorrow')->withHeaders([
            'Authorization' => 'Bearer ' . config('services.calcom.api_key')
        ])->timeout(5)->get($this->buildAvailabilityUrl($service, $tomorrow)),
    ]);

    $todaySlots = $this->extractTimeSlots($responses['today']->json());
    $tomorrowSlots = $this->extractTimeSlots($responses['tomorrow']->json());

    return [
        'today' => $todaySlots,
        'tomorrow' => $tomorrowSlots,
        'next' => $todaySlots[0] ?? $tomorrowSlots[0] ?? null,
    ];
}

private function buildAvailabilityUrl(Service $service, Carbon $date): string
{
    $query = http_build_query([
        'eventTypeId' => $service->calcom_event_type_id,
        'startTime' => $date->format('Y-m-d'),
        'endTime' => $date->format('Y-m-d'),
    ]);
    return config('services.calcom.base_url') . '/slots/available?' . $query;
}
```

**Messung**:
```php
// tests/Performance/WebhookResponseTimeTest.php
public function test_webhook_response_time_under_500ms(): void
{
    $start = microtime(true);

    $response = $this->postWebhook([
        'event' => 'call_started',
        'call' => ['call_id' => 'perf-test', 'to_number' => '+493083793369'],
    ]);

    $duration = (microtime(true) - $start) * 1000; // ms

    $this->assertLessThan(500, $duration, "Webhook response took {$duration}ms");
    $this->assertEquals(200, $response->status());
}
```

---

#### ⚡ Task 2.8: Call Context Caching
**Dringlichkeit**: MEDIUM | **Aufwand**: 10 Min | **Impact**: 3-4 DB Queries sparen

**Implementierung**:
```php
// File: app/Http/Controllers/RetellFunctionCallHandler.php:30
private array $callContextCache = [];

private function getCallContext(?string $callId): ?array
{
    if (!$callId) {
        Log::warning('Cannot get call context: callId is null');
        return null;
    }

    // Check cache first (request-scoped)
    if (isset($this->callContextCache[$callId])) {
        Log::debug('Call context cache hit', ['call_id' => $callId]);
        return $this->callContextCache[$callId];
    }

    // Load from database
    $call = \App\Models\Call::where('retell_call_id', $callId)
        ->with('phoneNumber')
        ->first();

    if (!$call || !$call->phoneNumber) {
        Log::warning('Call context not found', ['call_id' => $callId]);
        return null;
    }

    // Cache for request lifecycle
    $this->callContextCache[$callId] = [
        'company_id' => $call->phoneNumber->company_id,
        'branch_id' => $call->phoneNumber->branch_id,
        'phone_number_id' => $call->phoneNumber->id,
        'call_id' => $call->id,
    ];

    Log::debug('Call context loaded and cached', [
        'call_id' => $callId,
        'company_id' => $this->callContextCache[$callId]['company_id'],
    ]);

    return $this->callContextCache[$callId];
}
```

---

#### ⚡ Task 2.9: Availability Response Caching
**Dringlichkeit**: MEDIUM | **Aufwand**: 20 Min | **Impact**: 300-800ms → <5ms (99% faster)

**Implementierung**:
```php
// File: app/Services/CalcomService.php:108
public function getAvailableSlots(int $eventTypeId, string $startDate, string $endDate): Response
{
    $cacheKey = "calcom:slots:{$eventTypeId}:{$startDate}:{$endDate}";

    $cachedResponse = Cache::get($cacheKey);
    if ($cachedResponse) {
        Log::debug('Availability cache hit', ['key' => $cacheKey]);

        // Return mock Response with cached data
        return new \Illuminate\Http\Client\Response(
            new \GuzzleHttp\Psr7\Response(200, [], json_encode($cachedResponse))
        );
    }

    $query = [
        'eventTypeId' => $eventTypeId,
        'startTime' => $startDate,
        'endTime' => $endDate,
    ];

    $fullUrl = $this->baseUrl . '/slots/available?' . http_build_query($query);

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey
    ])->acceptJson()->timeout(5)->get($fullUrl);

    if ($response->successful()) {
        // Cache for 5 minutes
        Cache::put($cacheKey, $response->json(), 300);
        Log::debug('Availability cached', ['key' => $cacheKey]);
    }

    return $response;
}

// Invalidation on booking
public function createBooking(array $bookingDetails): Response
{
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey
    ])->acceptJson()->post($this->baseUrl . '/bookings', $bookingDetails);

    if ($response->successful()) {
        // Clear all availability cache for this event type
        $eventTypeId = $bookingDetails['eventTypeId'];
        $pattern = "calcom:slots:{$eventTypeId}:*";

        // Use Redis KEYS command to find and delete
        $keys = \Illuminate\Support\Facades\Redis::keys($pattern);
        if (!empty($keys)) {
            \Illuminate\Support\Facades\Redis::del(...$keys);
            Log::info('Cleared availability cache after booking', [
                'event_type_id' => $eventTypeId,
                'keys_cleared' => count($keys),
            ]);
        }
    }

    return $response;
}
```

---

#### 🧪 Task 2.10: Fix Test Infrastructure
**Dringlichkeit**: HIGH | **Aufwand**: 2h | **Impact**: 260 Tests werden passing

**Problem identifiziert**:
```xml
<!-- phpunit.xml:23-24 -->
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<!-- ↑ :memory: wird von RefreshDatabase trait ignoriert! -->
```

**Lösung**:
```xml
<!-- phpunit.xml:23-24 -->
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value="/var/www/api-gateway/database/testing.sqlite"/>
```

**Zusätzlich**:
```php
// tests/TestCase.php:40
protected function migrateFreshUsing()
{
    return [
        '--drop-views' => $this->shouldDropViews(),
        '--drop-types' => $this->shouldDropTypes(),
        '--database' => 'sqlite',
        '--path' => 'database/testing-migrations',  // ← Testing migrations only!
    ];
}

protected function setUp(): void
{
    parent::setUp();

    // Ensure testing database exists
    $dbPath = database_path('testing.sqlite');
    if (!file_exists($dbPath)) {
        touch($dbPath);
    }

    // Override migration path for tests
    $this->beforeApplicationDestroyed(function () {
        @unlink(database_path('testing.sqlite'));
    });
}
```

**Verifikation**:
```bash
# Test alle existierenden Tests
php artisan test

# Expected result:
# PASS  Tests\Unit\Middleware\VerifyRetellWebhookSignatureTest
# ✓ accepts webhook with valid signature
# ✓ rejects webhook with invalid signature
# ...
# PASS  Tests\Feature\AuthenticationTest
# ✓ login page is accessible
# ...
# Tests:  262 passed (was: 2 passed, 260 failed)
# Duration: <3 minutes
```

---

## 📈 SPRINT 3: Architecture & Scaling (2 Wochen)

**Ziel**: System skalierbar machen für 100+ Companies
**Aufwand**: 80 Stunden
**Team**: 2 Entwickler

### Week 3: Database & Queue Infrastructure

#### 🗄️ Task 3.1: PostgreSQL Migration
**Dringlichkeit**: CRITICAL | **Aufwand**: 12h | **Impact**: Skalierbarkeit für 100+ companies

**Warum PostgreSQL?**
- SQLite Single-Writer Limit: Max 50-60 concurrent writes/sec
- PostgreSQL: 1000+ concurrent connections mit pgBouncer
- JSON/JSONB Support für metadata
- Full-text search für Transcripts
- Better backup/replication

**Implementierung**:

**Phase 1: Lokale PostgreSQL Setup (2h)**
```bash
# Install PostgreSQL
apt-get install postgresql-15 postgresql-contrib

# Create database
sudo -u postgres psql
CREATE DATABASE askproai_production;
CREATE USER askproai_user WITH ENCRYPTED PASSWORD 'secure-password-here';
GRANT ALL PRIVILEGES ON DATABASE askproai_production TO askproai_user;
\q

# Configure Laravel
# .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=askproai_production
DB_USERNAME=askproai_user
DB_PASSWORD=secure-password-here
```

**Phase 2: Schema Migration (4h)**
```bash
# Dump existing data
mysqldump -u root askproai_db > /tmp/askproai_mysql_dump.sql

# Convert MySQL → PostgreSQL
# Option A: Use pgloader
pgloader mysql://root@localhost/askproai_db postgresql://askproai_user@localhost/askproai_production

# Option B: Manual conversion mit sed
sed 's/ENGINE=InnoDB//' /tmp/askproai_mysql_dump.sql > /tmp/askproai_postgres.sql
sed -i 's/AUTO_INCREMENT/SERIAL/' /tmp/askproai_postgres.sql
sed -i 's/`//g' /tmp/askproai_postgres.sql

# Run migrations on PostgreSQL
php artisan migrate:fresh --force

# Import data
psql -U askproai_user -d askproai_production < /tmp/askproai_postgres.sql
```

**Phase 3: Connection Pooling mit PgBouncer (2h)**
```ini
# /etc/pgbouncer/pgbouncer.ini
[databases]
askproai_production = host=127.0.0.1 port=5432 dbname=askproai_production

[pgbouncer]
listen_addr = 127.0.0.1
listen_port = 6432
auth_type = md5
auth_file = /etc/pgbouncer/userlist.txt
pool_mode = transaction
max_client_conn = 1000
default_pool_size = 25
```

```bash
# Laravel .env update
DB_HOST=127.0.0.1
DB_PORT=6432  # PgBouncer port
```

**Phase 4: Data Validation (2h)**
```php
// tests/Integration/PostgresMigrationTest.php
public function test_all_data_migrated_correctly(): void
{
    // Compare record counts
    $mysqlCounts = DB::connection('mysql')->table('companies')->count();
    $pgsqlCounts = DB::connection('pgsql')->table('companies')->count();
    $this->assertEquals($mysqlCounts, $pgsqlCounts);

    // Check data integrity
    $mysqlPhones = DB::connection('mysql')->table('phone_numbers')
        ->orderBy('id')->pluck('number_normalized')->toArray();
    $pgsqlPhones = DB::connection('pgsql')->table('phone_numbers')
        ->orderBy('id')->pluck('number_normalized')->toArray();
    $this->assertEquals($mysqlPhones, $pgsqlPhones);
}
```

**Phase 5: Blue-Green Deployment (2h)**
```bash
# 1. Setup PostgreSQL parallel zu MySQL
# 2. Run in dual-write mode (write to both DBs)
# 3. Verify data consistency
# 4. Switch reads to PostgreSQL
# 5. Monitor for 24h
# 6. Deprecate MySQL
```

---

#### 🔄 Task 3.2: Redis Queue Infrastructure
**Dringlichkeit**: HIGH | **Aufwand**: 6h | **Impact**: Async processing, bessere Response Times

**Implementierung**:

**Phase 1: Redis Setup (1h)**
```bash
# Already installed, verify
redis-cli ping
# PONG

# Configure Laravel
# .env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**Phase 2: Queue Workers Configuration (2h)**
```bash
# Supervisor configuration
# /etc/supervisor/conf.d/askproai-workers.conf
[program:askproai-queue-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/api-gateway/artisan queue:work redis --queue=high --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/api-gateway/storage/logs/queue-high.log
stopwaitsecs=3600

[program:askproai-queue-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/api-gateway/artisan queue:work redis --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/api-gateway/storage/logs/queue-default.log
stopwaitsecs=3600

[program:askproai-queue-low]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/api-gateway/artisan queue:work redis --queue=low --sleep=5 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/api-gateway/storage/logs/queue-low.log
stopwaitsecs=3600
```

```bash
# Apply supervisor config
supervisorctl reread
supervisorctl update
supervisorctl start askproai-queue-high:*
supervisorctl start askproai-queue-default:*
supervisorctl start askproai-queue-low:*
```

**Phase 3: Job Creation (3h)**
```php
// app/Jobs/ProcessRetellCallAnalysis.php
<?php

namespace App\Jobs;

use App\Models\Call;
use App\Services\CallAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRetellCallAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public Call $call
    ) {
        $this->onQueue('default');
    }

    public function handle(CallAnalysisService $analysisService): void
    {
        Log::info('Processing call analysis', ['call_id' => $this->call->id]);

        // Transcript processing
        $analysisService->processTranscript($this->call);

        // Sentiment analysis
        $analysisService->analyzeSentiment($this->call);

        // Customer matching
        $analysisService->matchCustomer($this->call);

        // Cost calculation
        $analysisService->calculateCosts($this->call);

        Log::info('Call analysis completed', ['call_id' => $this->call->id]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Call analysis failed', [
            'call_id' => $this->call->id,
            'error' => $exception->getMessage(),
        ]);

        $this->call->update([
            'analysis' => ['error' => 'Analysis failed: ' . $exception->getMessage()],
        ]);
    }
}

// app/Jobs/FindAppointmentAlternatives.php
<?php

namespace App\Jobs;

use App\Models\Call;
use App\Services\AppointmentAlternativeFinder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FindAppointmentAlternatives implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;

    public function __construct(
        public Call $call,
        public int $serviceId,
        public array $preferences = []
    ) {
        $this->onQueue('low'); // Low priority, not time-critical
    }

    public function handle(AppointmentAlternativeFinder $finder): void
    {
        Log::info('Finding appointment alternatives', [
            'call_id' => $this->call->id,
            'service_id' => $this->serviceId,
        ]);

        $alternatives = $finder->findAlternatives(
            $this->serviceId,
            $this->preferences
        );

        // Store alternatives for later retrieval
        $this->call->update([
            'metadata' => array_merge($this->call->metadata ?? [], [
                'alternatives' => $alternatives,
                'alternatives_found_at' => now()->toIso8601String(),
            ]),
        ]);

        Log::info('Alternatives found', [
            'call_id' => $this->call->id,
            'count' => count($alternatives),
        ]);
    }
}
```

**Usage in Controllers**:
```php
// app/Http/Controllers/RetellWebhookController.php:180
public function __invoke(Request $request)
{
    // ... phone number lookup ...

    // Create Call record (synchronous - must complete)
    $call = Call::create([
        'retell_call_id' => $callId,
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'phone_number_id' => $phoneNumberRecord->id,
        // ...
    ]);

    // Dispatch async analysis (runs in background)
    ProcessRetellCallAnalysis::dispatch($call);

    // Return immediately to Retell
    return response()->json([
        'status' => 'call_started',
        'call_id' => $call->id,
    ]);
}
```

---

### Week 4: Controller Refactoring & Testing

#### 🔧 Task 3.3: RetellWebhookController Decomposition
**Dringlichkeit**: MEDIUM | **Aufwand**: 12h | **Impact**: Wartbarkeit, Testbarkeit

**Current State**: 2091 Zeilen, 47 Methods

**Target State**: 7 kleinere Controller + Services

**Decomposition Plan**:

```
RetellWebhookController (2091 lines)
├── WebhookValidationController (150 lines)
│   └── Validates webhook signature, phone number lookup
├── CallLifecycleController (300 lines)
│   └── call_started, call_ended events
├── QuickAvailabilityController (400 lines)
│   └── getQuickAvailability, formatAvailability
├── ConversationEndedController (500 lines)
│   └── processConversationEnded, transcriptProcessing
├── CustomerIdentificationController (300 lines)
│   └── Customer matching, verification
├── AppointmentSummaryController (250 lines)
│   └── Summary generation, formatting
└── AnalyticsController (200 lines)
    └── Call analysis, cost calculation
```

**Service Layer** (new):
```
app/Services/Retell/
├── CallManagementService.php
├── AvailabilityService.php
├── ConversationProcessingService.php
├── CustomerMatchingService.php
├── AnalyticsService.php
└── AppointmentSummaryService.php
```

**Implementation Example**:
```php
// app/Services/Retell/AvailabilityService.php
<?php

namespace App\Services\Retell;

use App\Models\Service;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AvailabilityService
{
    public function __construct(
        private CalcomService $calcomService
    ) {}

    public function getQuickAvailability(Service $service): array
    {
        $cacheKey = "quick_availability:{$service->id}:" . Carbon::now()->format('Y-m-d');

        return Cache::remember($cacheKey, 300, function() use ($service) {
            $today = Carbon::today();
            $tomorrow = Carbon::tomorrow();

            $responses = $this->fetchAvailabilityParallel($service, $today, $tomorrow);

            $todaySlots = $this->extractTimeSlots($responses['today']->json());
            $tomorrowSlots = $this->extractTimeSlots($responses['tomorrow']->json());

            return [
                'today' => $todaySlots,
                'tomorrow' => $tomorrowSlots,
                'next' => $todaySlots[0] ?? $tomorrowSlots[0] ?? null,
            ];
        });
    }

    private function fetchAvailabilityParallel(Service $service, Carbon $today, Carbon $tomorrow): array
    {
        return Http::pool(fn ($pool) => [
            $pool->as('today')->withHeaders([
                'Authorization' => 'Bearer ' . config('services.calcom.api_key')
            ])->timeout(5)->get($this->buildUrl($service, $today)),

            $pool->as('tomorrow')->withHeaders([
                'Authorization' => 'Bearer ' . config('services.calcom.api_key')
            ])->timeout(5)->get($this->buildUrl($service, $tomorrow)),
        ]);
    }

    private function buildUrl(Service $service, Carbon $date): string
    {
        $query = http_build_query([
            'eventTypeId' => $service->calcom_event_type_id,
            'startTime' => $date->format('Y-m-d'),
            'endTime' => $date->format('Y-m-d'),
        ]);

        return config('services.calcom.base_url') . '/slots/available?' . $query;
    }

    private function extractTimeSlots(array $response): array
    {
        if (!isset($response['slots'])) {
            return [];
        }

        return collect($response['slots'])->map(function ($slot) {
            return [
                'time' => Carbon::parse($slot['time'])->format('H:i'),
                'available' => true,
            ];
        })->values()->toArray();
    }
}

// app/Http/Controllers/Retell/QuickAvailabilityController.php
<?php

namespace App\Http\Controllers\Retell;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\Retell\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickAvailabilityController extends Controller
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'nullable|exists:services,id',
            'company_id' => 'required|exists:companies,id',
        ]);

        $service = Service::where('company_id', $validated['company_id'])
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id')
            ->when($validated['service_id'] ?? null, fn($q) => $q->where('id', $validated['service_id']))
            ->first();

        if (!$service) {
            return response()->json([
                'error' => 'No active service found with Cal.com integration',
            ], 404);
        }

        $availability = $this->availabilityService->getQuickAvailability($service);

        return response()->json([
            'service_id' => $service->id,
            'service_name' => $service->name,
            'availability' => $availability,
        ]);
    }
}
```

---

#### 🧪 Task 3.4: Comprehensive Test Suite
**Dringlichkeit**: HIGH | **Aufwand**: 16h | **Impact**: 80% Coverage

**Test Plan**:

**Unit Tests** (120 Tests, 6h):
```
tests/Unit/
├── Models/
│   ├── CompanyTest.php (10 tests)
│   ├── BranchTest.php (8 tests)
│   ├── ServiceTest.php (12 tests)
│   ├── PhoneNumberTest.php (10 tests)
│   ├── CallTest.php (15 tests)
│   └── CustomerTest.php (10 tests)
├── Services/
│   ├── PhoneNumberNormalizerTest.php (12 tests)
│   ├── CalcomServiceTest.php (15 tests)
│   ├── AvailabilityServiceTest.php (10 tests)
│   └── CallManagementServiceTest.php (18 tests)
└── Middleware/
    ├── VerifyRetellWebhookSignatureTest.php (8 tests - EXISTS)
    └── VerifyRetellFunctionSignatureWithWhitelistTest.php (12 tests - NEW)
```

**Integration Tests** (50 Tests, 8h):
```
tests/Integration/
├── Security/
│   ├── PhoneNumberLookupTest.php (8 tests - EXISTS)
│   ├── BranchIsolationTest.php (12 tests)
│   ├── TenantIsolationTest.php (15 tests)
│   ├── WebhookAuthenticationTest.php (10 tests)
│   └── RateLimitingTest.php (5 tests)
├── Booking/
│   ├── BookingWorkflowTest.php (20 tests)
│   ├── AvailabilityCheckTest.php (12 tests)
│   └── CancellationTest.php (8 tests)
└── Webhooks/
    ├── CallLifecycleTest.php (15 tests)
    └── ConversationProcessingTest.php (12 tests)
```

**E2E Tests** (5 Tests, 2h):
```
tests/E2E/
├── CompleteBookingJourneyTest.php (1 test)
├── CallToBookingFlowTest.php (1 test)
├── CustomerIdentificationJourneyTest.php (1 test)
├── BranchSpecificBookingTest.php (1 test)
└── CancellationFlowTest.php (1 test)
```

**Sample Tests**:
```php
// tests/Integration/Security/TenantIsolationTest.php
<?php

namespace Tests\Integration\Security;

use Tests\TestCase;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\Service;
use App\Models\Branch;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TenantIsolationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * VULN-003 regression test: Verify no company_id fallbacks exist
     */
    public function test_unregistered_phone_number_never_falls_back_to_company_1(): void
    {
        // Create company ID 1 to test the old vulnerability
        $companyOne = Company::factory()->create(['id' => 1]);

        // Try webhook with unregistered phone number
        $payload = [
            'event' => 'call_started',
            'call' => [
                'call_id' => 'vuln-003-test',
                'to_number' => '+49999999999', // Not in database
                'from_number' => '+491234567890',
            ],
        ];

        $response = $this->postWebhookWithSignature('/webhooks/retell', $payload);

        // Must return 404, NOT create call for company_id=1
        $this->assertEquals(404, $response->status());

        $this->assertDatabaseMissing('calls', [
            'company_id' => 1,
            'retell_call_id' => 'vuln-003-test',
        ]);
    }

    public function test_service_selection_respects_branch_isolation(): void
    {
        // Company with 2 branches
        $company = Company::factory()->create();
        $branch1 = Branch::factory()->create(['company_id' => $company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company->id]);

        // Service only available at branch1
        $service1 = Service::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);

        // Phone number belongs to branch2
        $phoneNumber = PhoneNumber::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch2->id,
            'number_normalized' => '+493012345678',
        ]);

        // Start call from branch2 phone
        $call = $this->createCallForPhoneNumber($phoneNumber);

        // Try to book service from branch1
        $response = $this->postFunctionCall('/api/retell/check-availability', [
            'call_id' => $call->retell_call_id,
            'service_id' => $service1->id,
            'date' => '2025-10-01',
        ]);

        // Should reject: service not available at caller's branch
        $this->assertEquals(403, $response->status());
        $this->assertStringContainsString('not available at your branch', $response->json('error'));
    }

    public function test_cross_company_data_access_blocked(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $service1 = Service::factory()->create(['company_id' => $company1->id]);
        $phone2 = PhoneNumber::factory()->create(['company_id' => $company2->id]);

        $call = $this->createCallForPhoneNumber($phone2);

        // Try to access company1's service from company2's call
        $response = $this->postFunctionCall('/api/retell/check-availability', [
            'call_id' => $call->retell_call_id,
            'service_id' => $service1->id,
            'date' => '2025-10-01',
        ]);

        // Should reject: cross-company access
        $this->assertEquals(403, $response->status());
    }

    // ... 12 more isolation tests
}
```

---

## 🚀 SPRINT 4: Advanced Features & Optimization (2 Wochen)

**Ziel**: Production-ready System mit Monitoring
**Aufwand**: 80 Stunden
**Team**: 2 Entwickler

### Week 5: Monitoring & Observability

#### 📊 Task 4.1: Application Performance Monitoring (APM)
**Dringlichkeit**: MEDIUM | **Aufwand**: 8h | **Impact**: Proactive Issue Detection

**Tools**: Laravel Telescope + Custom Metrics

**Implementation**:
```php
// config/telescope.php
'watchers' => [
    Watchers\CacheWatcher::class => ['enabled' => true],
    Watchers\CommandWatcher::class => ['enabled' => true],
    Watchers\DumpWatcher::class => ['enabled' => true],
    Watchers\EventWatcher::class => ['enabled' => true],
    Watchers\ExceptionWatcher::class => ['enabled' => true],
    Watchers\JobWatcher::class => ['enabled' => true],
    Watchers\LogWatcher::class => ['enabled' => true],
    Watchers\MailWatcher::class => ['enabled' => false],
    Watchers\ModelWatcher::class => ['enabled' => false], // Too verbose
    Watchers\NotificationWatcher::class => ['enabled' => true],
    Watchers\QueryWatcher::class => [
        'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
        'slow' => 100, // Alert on queries > 100ms
    ],
    Watchers\RedisWatcher::class => ['enabled' => true],
    Watchers\RequestWatcher::class => [
        'enabled' => true,
        'size_limit' => 64,
    ],
    Watchers\GateWatcher::class => ['enabled' => false],
    Watchers\ScheduleWatcher::class => ['enabled' => true],
    Watchers\ViewWatcher::class => ['enabled' => false],
],
```

**Custom Metrics**:
```php
// app/Providers/AppServiceProvider.php:boot()
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

// Track webhook processing time
Event::listen('webhook.processed', function ($event) {
    $duration = $event->duration;

    Log::channel('metrics')->info('webhook.processed', [
        'duration_ms' => $duration,
        'event_type' => $event->type,
        'company_id' => $event->company_id,
    ]);

    // Alert if slow
    if ($duration > 1000) {
        Log::channel('slack')->warning('Slow webhook processing', [
            'duration_ms' => $duration,
            'call_id' => $event->call_id,
        ]);
    }
});

// Track booking success rate
Event::listen('booking.created', function ($event) {
    Log::channel('metrics')->info('booking.success', [
        'company_id' => $event->company_id,
        'service_id' => $event->service_id,
        'response_time_ms' => $event->response_time,
    ]);
});

Event::listen('booking.failed', function ($event) {
    Log::channel('metrics')->error('booking.failed', [
        'company_id' => $event->company_id,
        'service_id' => $event->service_id,
        'error' => $event->error,
    ]);
});
```

---

#### 🔔 Task 4.2: Alerting System
**Dringlichkeit**: HIGH | **Aufwand**: 6h | **Impact**: Downtime Prevention

**Slack Notifications**:
```php
// config/logging.php
'channels' => [
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'API Gateway Alerts',
        'emoji' => ':warning:',
        'level' => 'error',
    ],

    'slack-critical' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL_CRITICAL'),
        'username' => 'CRITICAL ALERT',
        'emoji' => ':rotating_light:',
        'level' => 'critical',
    ],
],
```

**Alert Rules**:
```php
// app/Services/AlertingService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AlertingService
{
    // Alert if error rate > 5% in last 5 minutes
    public function checkErrorRate(): void
    {
        $totalRequests = Cache::get('metrics:requests:5min', 0);
        $totalErrors = Cache::get('metrics:errors:5min', 0);

        if ($totalRequests > 100) {
            $errorRate = ($totalErrors / $totalRequests) * 100;

            if ($errorRate > 5) {
                Log::channel('slack-critical')->critical('High error rate detected', [
                    'error_rate' => round($errorRate, 2) . '%',
                    'total_requests' => $totalRequests,
                    'total_errors' => $totalErrors,
                    'window' => '5 minutes',
                ]);
            }
        }
    }

    // Alert if webhook response time > 2 seconds
    public function checkWebhookLatency(int $durationMs, string $callId): void
    {
        if ($durationMs > 2000) {
            Log::channel('slack')->warning('Slow webhook response', [
                'duration_ms' => $durationMs,
                'call_id' => $callId,
                'threshold_ms' => 2000,
            ]);
        }
    }

    // Alert if queue size > 1000
    public function checkQueueBacklog(): void
    {
        $queueSize = \Illuminate\Support\Facades\Queue::size('default');

        if ($queueSize > 1000) {
            Log::channel('slack-critical')->critical('Queue backlog detected', [
                'queue_size' => $queueSize,
                'threshold' => 1000,
                'recommendation' => 'Scale up queue workers',
            ]);
        }
    }

    // Alert if Cal.com API failing
    public function checkCalcomHealth(): void
    {
        $failureRate = Cache::get('calcom:failure_rate:1h', 0);

        if ($failureRate > 10) {
            Log::channel('slack-critical')->critical('Cal.com API degraded', [
                'failure_rate' => $failureRate . '%',
                'window' => '1 hour',
                'action' => 'Enable fallback booking mode',
            ]);
        }
    }
}

// Schedule checks
// app/Console/Kernel.php:schedule()
$schedule->call(function () {
    app(AlertingService::class)->checkErrorRate();
    app(AlertingService::class)->checkQueueBacklog();
    app(AlertingService::class)->checkCalcomHealth();
})->everyFiveMinutes();
```

---

### Week 6: Circuit Breaker & Fallback Logic

#### 🔌 Task 4.3: Circuit Breaker Implementation
**Dringlichkeit**: MEDIUM | **Aufwand**: 8h | **Impact**: Graceful Cal.com Degradation

**Implementation**:
```php
// app/Services/CircuitBreaker.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CircuitBreaker
{
    private string $serviceName;
    private int $failureThreshold = 5;
    private int $timeoutSeconds = 60;
    private int $halfOpenAttempts = 3;

    public function __construct(string $serviceName)
    {
        $this->serviceName = $serviceName;
    }

    public function call(callable $callback, callable $fallback = null)
    {
        $state = $this->getState();

        switch ($state) {
            case 'open':
                Log::warning('Circuit breaker OPEN', ['service' => $this->serviceName]);
                return $fallback ? $fallback() : throw new \Exception('Circuit breaker is OPEN for ' . $this->serviceName);

            case 'half-open':
                try {
                    $result = $callback();
                    $this->recordSuccess();
                    return $result;
                } catch (\Throwable $e) {
                    $this->recordFailure();
                    throw $e;
                }

            case 'closed':
            default:
                try {
                    $result = $callback();
                    $this->recordSuccess();
                    return $result;
                } catch (\Throwable $e) {
                    $this->recordFailure();
                    throw $e;
                }
        }
    }

    private function getState(): string
    {
        $failures = Cache::get("circuit_breaker:{$this->serviceName}:failures", 0);
        $lastFailure = Cache::get("circuit_breaker:{$this->serviceName}:last_failure");

        // Open: Too many failures
        if ($failures >= $this->failureThreshold) {
            // Check if timeout has passed
            if ($lastFailure && now()->diffInSeconds($lastFailure) >= $this->timeoutSeconds) {
                Cache::put("circuit_breaker:{$this->serviceName}:state", 'half-open', 3600);
                return 'half-open';
            }
            return 'open';
        }

        // Half-open: Testing recovery
        if (Cache::get("circuit_breaker:{$this->serviceName}:state") === 'half-open') {
            return 'half-open';
        }

        return 'closed';
    }

    private function recordFailure(): void
    {
        $failures = Cache::increment("circuit_breaker:{$this->serviceName}:failures");
        Cache::put("circuit_breaker:{$this->serviceName}:last_failure", now(), 3600);

        if ($failures >= $this->failureThreshold) {
            Log::error('Circuit breaker opening', [
                'service' => $this->serviceName,
                'failures' => $failures,
            ]);
            Cache::put("circuit_breaker:{$this->serviceName}:state", 'open', 3600);
        }
    }

    private function recordSuccess(): void
    {
        $state = Cache::get("circuit_breaker:{$this->serviceName}:state");

        if ($state === 'half-open') {
            $successes = Cache::increment("circuit_breaker:{$this->serviceName}:half_open_successes");

            if ($successes >= $this->halfOpenAttempts) {
                Log::info('Circuit breaker closing', ['service' => $this->serviceName]);
                Cache::forget("circuit_breaker:{$this->serviceName}:failures");
                Cache::forget("circuit_breaker:{$this->serviceName}:state");
                Cache::forget("circuit_breaker:{$this->serviceName}:half_open_successes");
            }
        } else {
            // Reset failure count on success
            Cache::forget("circuit_breaker:{$this->serviceName}:failures");
        }
    }
}

// Usage in CalcomService
// app/Services/CalcomService.php
public function getAvailableSlots(int $eventTypeId, string $startDate, string $endDate): Response
{
    $circuitBreaker = new CircuitBreaker('calcom-api');

    try {
        return $circuitBreaker->call(
            callback: function() use ($eventTypeId, $startDate, $endDate) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey
                ])->timeout(5)->get($this->baseUrl . '/slots/available', [
                    'eventTypeId' => $eventTypeId,
                    'startTime' => $startDate,
                    'endTime' => $endDate,
                ]);

                if (!$response->successful()) {
                    throw new \Exception('Cal.com API error: ' . $response->status());
                }

                return $response;
            },
            fallback: function() {
                // Return cached availability or generic slots
                return $this->getFallbackAvailability();
            }
        );
    } catch (\Throwable $e) {
        Log::error('Cal.com availability check failed', [
            'event_type_id' => $eventTypeId,
            'error' => $e->getMessage(),
        ]);

        // Return fallback
        return response()->json([
            'slots' => $this->getFallbackAvailability(),
            'source' => 'fallback',
        ]);
    }
}

private function getFallbackAvailability(): array
{
    // Return generic working hours slots
    return [
        ['time' => '09:00', 'available' => true],
        ['time' => '10:00', 'available' => true],
        ['time' => '11:00', 'available' => true],
        ['time' => '14:00', 'available' => true],
        ['time' => '15:00', 'available' => true],
        ['time' => '16:00', 'available' => true],
    ];
}
```

---

## 📊 Success Metrics & KPIs

### Sprint 2 (Security & Quick Wins)
- ✅ 0 unauthenticated endpoints (9 → 0)
- ✅ 50% faster webhook response (635-1690ms → 300-600ms)
- ✅ 99% test success rate (0.8% → 99%)
- ✅ 6 critical vulnerabilities fixed

### Sprint 3 (Architecture & Scaling)
- ✅ PostgreSQL migration complete
- ✅ Queue workers operational (14 workers)
- ✅ 80% code coverage (4% → 80%)
- ✅ Controller LOC reduced by 60% (2091 → 800)

### Sprint 4 (Advanced Features)
- ✅ APM dashboard operational
- ✅ Circuit breaker 99.9% uptime
- ✅ <1min mean time to alert
- ✅ 200+ automated tests

---

## 💰 Resource Requirements

### Infrastructure Costs (Monthly)

**Current (SQLite)**:
```
Server: $50/month (existing)
Total: $50/month
```

**Sprint 3 (PostgreSQL + Redis Queue)**:
```
Server: $50/month
PostgreSQL (managed): $50/month
Redis (managed): $30/month
Backups: $20/month
Total: $150/month (+$100)
```

**At 100 Companies**:
```
Server: $200/month (upgraded)
PostgreSQL: $400/month (scaled)
Redis: $100/month (scaled)
Monitoring: $50/month (Telescope hosting)
CDN: $100/month
Backups: $100/month
Queue Workers: $200/month (additional servers)
Load Balancer: $50/month
Total: $1,200/month
```

### Development Costs

| Sprint | Duration | Dev Hours | Cost @ $100/h |
|--------|----------|-----------|---------------|
| Sprint 2 | 2 weeks | 80h | $8,000 |
| Sprint 3 | 2 weeks | 160h (2 devs) | $16,000 |
| Sprint 4 | 2 weeks | 160h (2 devs) | $16,000 |
| **Total** | **6 weeks** | **400h** | **$40,000** |

---

## 🚦 Risk Assessment

### High Risk Items

1. **PostgreSQL Migration** (Sprint 3)
   - **Risk**: Data loss during migration
   - **Mitigation**: Blue-green deployment, dual-write period
   - **Rollback**: Keep MySQL online for 7 days

2. **Circuit Breaker False Positives** (Sprint 4)
   - **Risk**: Unnecessary Cal.com blocking
   - **Mitigation**: Conservative thresholds, monitoring
   - **Rollback**: Disable circuit breaker feature flag

3. **Queue Worker Scaling** (Sprint 3)
   - **Risk**: Job processing delays at scale
   - **Mitigation**: Horizontal scaling, priority queues
   - **Rollback**: Process jobs synchronously

### Medium Risk Items

1. **Controller Refactoring**
   - **Risk**: Breaking existing functionality
   - **Mitigation**: Comprehensive test suite before refactoring
   - **Rollback**: Git revert

2. **Rate Limiting**
   - **Risk**: False positives blocking legitimate traffic
   - **Mitigation**: Gradual rollout, monitoring
   - **Rollback**: Increase limits or disable

---

## 📝 Communication Plan

### Daily Standups
- **Time**: 09:00 UTC
- **Duration**: 15 min
- **Format**: Async (Slack) or Sync (Video)
- **Topics**: Progress, blockers, risks

### Sprint Reviews
- **Frequency**: End of each sprint
- **Duration**: 60 min
- **Attendees**: Dev team, stakeholders
- **Format**: Demo + Q&A

### Weekly Status Reports
- **Format**: Slack message
- **Content**:
  - Completed tasks
  - In-progress tasks
  - Blockers
  - Metrics

---

## 🎯 Sprint Priorisierung

### Must Have (P0)
- ✅ Sprint 2: VULN-005 Fix (30 min)
- ✅ Sprint 2: VULN-004 Fix (2h)
- ✅ Sprint 2: Performance Quick Wins (1h)
- ✅ Sprint 2: Test Infrastructure Fix (2h)
- ✅ Sprint 3: PostgreSQL Migration (12h)
- ✅ Sprint 3: Redis Queue Setup (6h)

### Should Have (P1)
- ✅ Sprint 2: Rate Limiting (3h)
- ✅ Sprint 2: Mass Assignment Fix (2h)
- ✅ Sprint 3: Controller Refactoring (12h)
- ✅ Sprint 3: Test Suite (16h)
- ✅ Sprint 4: APM Setup (8h)

### Nice to Have (P2)
- 🔵 Sprint 4: Circuit Breaker (8h)
- 🔵 Sprint 4: Advanced Monitoring (6h)
- 🔵 Sprint 4: Fallback Logic (4h)

---

## ✅ Definition of Done

Eine Task ist "Done" wenn:

1. ✅ Code geschrieben und reviewed
2. ✅ Unit Tests geschrieben und passing
3. ✅ Integration Tests geschrieben und passing
4. ✅ Dokumentation aktualisiert
5. ✅ Peer Review abgeschlossen
6. ✅ In Staging deployed und getestet
7. ✅ Performance-Impact gemessen
8. ✅ Security-Review abgeschlossen
9. ✅ Production-Deployment erfolgreich
10. ✅ Monitoring Alerts konfiguriert

---

## 📚 Dokumentation Updates

Folgende Dokumentationen müssen erstellt/aktualisiert werden:

### Sprint 2
- ✅ Security Fix Documentation (VULN-004 bis VULN-009)
- ✅ Performance Optimization Guide
- ✅ Test Infrastructure Setup Guide

### Sprint 3
- 📝 PostgreSQL Migration Guide
- 📝 Queue System Documentation
- 📝 Service Layer Architecture
- 📝 API Documentation Update

### Sprint 4
- 📝 Monitoring & Alerting Guide
- 📝 Circuit Breaker Configuration
- 📝 Incident Response Playbook
- 📝 Scaling Guide (0-1000 companies)

---

**Erstellt**: 2025-09-30 14:30 UTC
**Version**: 1.0
**Status**: READY FOR REVIEW
**Nächster Schritt**: Sprint 2 Planning Meeting