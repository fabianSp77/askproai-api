# 📋 AskProAI Technical Specification v2.0 - FINAL

## Executive Summary
Diese finale Spezifikation basiert auf einer gründlichen Analyse der bestehenden Codebase, Best Practices aus der Industrie und identifizierten Schwachstellen. Sie priorisiert Zuverlässigkeit, Skalierbarkeit und Wartbarkeit.

## 🚨 Kritische Erkenntnisse aus der Code-Analyse

### 1. **Sicherheitslücken behoben**
- **Problem**: 12 Models ohne TenantScope → Cross-Tenant Datenlecks möglich
- **Lösung**: TenantScope zu allen relevanten Models hinzugefügt

### 2. **Race Conditions**
- **Problem**: Gleichzeitige Buchungen können denselben Slot belegen
- **Lösung**: Optimistic Locking + Time Slot Locking Mechanismus

### 3. **Webhook Idempotenz**
- **Problem**: Doppelte Webhook-Verarbeitung möglich
- **Lösung**: Webhook Event Tracking mit unique constraints

### 4. **External API Failures**
- **Problem**: Cal.com/Retell Ausfälle blockieren Buchungen
- **Lösung**: Circuit Breaker + Retry Queue + Fallback Mechanismen

---

## 📊 Überarbeitete Datenbank-Architektur

### Neue kritische Tabellen

```sql
-- 1. Time Slot Locking (Verhindert Race Conditions)
CREATE TABLE appointment_locks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id CHAR(36) NOT NULL,
    staff_id CHAR(36) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    lock_token CHAR(36) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slot (branch_id, staff_id, start_time),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 2. Webhook Event Tracking (Idempotenz)
CREATE TABLE webhook_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL, -- 'retell', 'calcom', 'stripe'
    event_id VARCHAR(255) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    processed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_event (source, event_id),
    INDEX idx_processed (processed_at, retry_count)
) ENGINE=InnoDB;

-- 3. Service Availability Cache
CREATE TABLE availability_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(255) NOT NULL,
    branch_id CHAR(36) NOT NULL,
    date DATE NOT NULL,
    service_id BIGINT UNSIGNED NULL,
    data JSON NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cache (cache_key),
    INDEX idx_branch_date (branch_id, date),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- 4. API Call Logs (Debugging & Monitoring)
CREATE TABLE api_call_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    correlation_id CHAR(36) NOT NULL,
    service VARCHAR(50) NOT NULL, -- 'calcom', 'retell'
    method VARCHAR(10) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    request_payload JSON,
    response_status INT,
    response_body JSON,
    duration_ms INT,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_correlation (correlation_id),
    INDEX idx_service_status (service, response_status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;
```

### Optimierte Indizes für Performance

```sql
-- Composite Indizes für häufige Queries
ALTER TABLE appointments 
ADD INDEX idx_availability_check (branch_id, staff_id, start_time, status);

ALTER TABLE staff_skills 
ADD INDEX idx_skill_search (service_id, skill_level, branch_id);

ALTER TABLE availability_rules 
ADD INDEX idx_rule_lookup (staff_id, branch_id, day_of_week, start_time);
```

---

## 🔧 Service Layer mit verbessertem Error Handling

### EnhancedBookingService mit Locking

```php
namespace App\Services\Booking;

use App\Services\Locking\TimeSlotLockManager;
use App\Services\Circuit\CircuitBreaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnhancedBookingService
{
    private TimeSlotLockManager $lockManager;
    private CircuitBreaker $circuitBreaker;
    private LoggerInterface $logger;
    
    public function createAppointment(array $data): AppointmentResult
    {
        $correlationId = Str::uuid();
        $this->logger->info('Starting appointment creation', [
            'correlation_id' => $correlationId,
            'data' => $data
        ]);
        
        // 1. Acquire time slot lock
        $lockToken = $this->lockManager->acquireLock(
            $data['branch_id'],
            $data['staff_id'],
            $data['start_time'],
            $data['end_time']
        );
        
        if (!$lockToken) {
            $this->logger->warning('Failed to acquire slot lock', [
                'correlation_id' => $correlationId
            ]);
            return AppointmentResult::failure('Time slot is no longer available');
        }
        
        DB::beginTransaction();
        
        try {
            // 2. Double-check availability
            if (!$this->isSlotStillAvailable($data)) {
                throw new SlotUnavailableException();
            }
            
            // 3. Create appointment
            $appointment = $this->createAppointmentRecord($data);
            
            // 4. Sync to Cal.com with Circuit Breaker
            $calcomResult = $this->circuitBreaker->call('calcom', function() use ($appointment) {
                return $this->calcomService->createBooking($appointment);
            });
            
            if (!$calcomResult->success) {
                // Queue for retry instead of failing
                dispatch(new SyncAppointmentToCalcomJob($appointment))
                    ->onQueue('calendar-sync')
                    ->delay(now()->addMinutes(5));
                    
                $this->logger->warning('Cal.com sync failed, queued for retry', [
                    'correlation_id' => $correlationId,
                    'appointment_id' => $appointment->id
                ]);
            }
            
            DB::commit();
            
            // 5. Release lock after successful commit
            $this->lockManager->releaseLock($lockToken);
            
            // 6. Send notifications asynchronously
            dispatch(new SendAppointmentNotificationsJob($appointment));
            
            return AppointmentResult::success($appointment);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->lockManager->releaseLock($lockToken);
            
            $this->logger->error('Appointment creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
```

### Webhook Handler mit Idempotenz

```php
namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use App\Services\Webhook\WebhookProcessor;
use Illuminate\Http\Request;

class UnifiedWebhookController extends Controller
{
    private WebhookProcessor $processor;
    
    public function handle(Request $request)
    {
        $source = $this->identifySource($request);
        $eventId = $this->extractEventId($request, $source);
        
        // 1. Check for duplicate processing (Idempotenz)
        $existingEvent = WebhookEvent::where('source', $source)
            ->where('event_id', $eventId)
            ->first();
            
        if ($existingEvent && $existingEvent->processed_at) {
            return response()->json(['status' => 'already_processed'], 200);
        }
        
        // 2. Store webhook event
        $webhookEvent = WebhookEvent::updateOrCreate(
            [
                'source' => $source,
                'event_id' => $eventId
            ],
            [
                'event_type' => $request->input('event_type', 'unknown'),
                'payload' => $request->all(),
                'retry_count' => 0
            ]
        );
        
        // 3. Process asynchronously
        dispatch(new ProcessWebhookJob($webhookEvent))
            ->onQueue('webhooks');
            
        // 4. Acknowledge immediately (Retell requires 2xx within 10s)
        return response()->noContent();
    }
    
    private function identifySource(Request $request): string
    {
        // Check headers and payload to identify webhook source
        if ($request->hasHeader('x-retell-signature')) {
            return 'retell';
        }
        
        if ($request->hasHeader('x-cal-signature')) {
            return 'calcom';
        }
        
        if ($request->hasHeader('stripe-signature')) {
            return 'stripe';
        }
        
        return 'unknown';
    }
}
```

---

## 🏗️ Verbesserte Multi-Business-Model Architektur

### Business Model Router

```php
namespace App\Services\BusinessModels;

class BusinessModelRouter
{
    public function getModelForCompany(Company $company): BusinessModelInterface
    {
        return match($company->business_model_type) {
            'simple' => new SimpleBusinessModel($company),
            'multi_branch_hotline' => new MultiBranchHotlineModel($company),
            'complex_service_matrix' => new ComplexServiceMatrixModel($company),
            default => new SimpleBusinessModel($company)
        };
    }
}

interface BusinessModelInterface
{
    public function getBookingStrategy(): BookingStrategyInterface;
    public function getRoutingStrategy(): RoutingStrategyInterface;
    public function getAvailabilityEngine(): AvailabilityEngineInterface;
    public function getUIComponents(): array;
}
```

---

## 📝 Logging & Debugging Strategie

### Strukturiertes Logging mit Correlation IDs

```php
namespace App\Services\Logging;

class StructuredLogger
{
    public function logBookingFlow(string $step, array $context = []): void
    {
        $logData = [
            'timestamp' => now()->toIso8601String(),
            'correlation_id' => $context['correlation_id'] ?? Str::uuid(),
            'step' => $step,
            'company_id' => $context['company_id'] ?? null,
            'branch_id' => $context['branch_id'] ?? null,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
        
        Log::channel('booking_flow')->info($step, array_merge($logData, $context));
    }
    
    public function logApiCall(string $service, array $request, $response, float $duration): void
    {
        ApiCallLog::create([
            'correlation_id' => $request['correlation_id'] ?? Str::uuid(),
            'service' => $service,
            'method' => $request['method'] ?? 'POST',
            'endpoint' => $request['endpoint'],
            'request_payload' => $request['payload'] ?? null,
            'response_status' => $response['status'] ?? null,
            'response_body' => $response['body'] ?? null,
            'duration_ms' => $duration * 1000,
            'error_message' => $response['error'] ?? null,
        ]);
    }
}
```

### Debug Dashboard

```php
namespace App\Filament\Pages;

class BookingDebugDashboard extends Page
{
    protected static string $view = 'filament.pages.booking-debug-dashboard';
    
    public function getRecentFailedBookings()
    {
        return Appointment::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->with(['logs', 'webhookEvents'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(function($appointment) {
                return [
                    'id' => $appointment->id,
                    'correlation_id' => $appointment->correlation_id,
                    'error' => $appointment->error_message,
                    'logs' => $appointment->logs->toArray(),
                    'webhook_events' => $appointment->webhookEvents->toArray(),
                    'timeline' => $this->buildTimeline($appointment)
                ];
            });
    }
}
```

---

## 🔒 Security Best Practices

### 1. Multi-Tenancy mit Stancl/Tenancy Package

```php
// config/tenancy.php
return [
    'tenant_model' => \App\Models\Company::class,
    
    'central_domains' => [
        'admin.askproai.de',
    ],
    
    'tenant_identification' => [
        'header' => 'X-Company-ID',
        'subdomain' => true,
        'path' => false,
    ],
];

// Model mit automatischem Scoping
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Appointment extends Model
{
    use BelongsToTenant;
}
```

### 2. Webhook Signature Verification

```php
namespace App\Http\Middleware;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next, string $source)
    {
        $verifier = match($source) {
            'retell' => new RetellSignatureVerifier(),
            'calcom' => new CalcomSignatureVerifier(),
            'stripe' => new StripeSignatureVerifier(),
        };
        
        if (!$verifier->verify($request)) {
            Log::warning('Invalid webhook signature', [
                'source' => $source,
                'ip' => $request->ip()
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        return $next($request);
    }
}
```

---

## 📈 Performance Optimierungen

### 1. Query Optimization

```php
// Schlecht: N+1 Problem
$appointments = Appointment::all();
foreach ($appointments as $appointment) {
    echo $appointment->customer->name;
}

// Gut: Eager Loading
$appointments = Appointment::with([
    'customer:id,name,phone',
    'staff:id,name',
    'service:id,name,duration',
    'branch:id,name'
])->get();

// Optimal: Query Builder für große Datenmengen
$appointments = DB::table('appointments')
    ->join('customers', 'appointments.customer_id', '=', 'customers.id')
    ->join('staff', 'appointments.staff_id', '=', 'staff.id')
    ->select('appointments.id', 'customers.name', 'staff.name as staff_name')
    ->where('appointments.branch_id', $branchId)
    ->whereDate('appointments.start_time', $date)
    ->get();
```

### 2. Caching Strategy

```php
class AvailabilityCacheService
{
    private const CACHE_TTL = 300; // 5 minutes
    
    public function remember(string $key, Closure $callback)
    {
        // Use Redis with tags for easy invalidation
        return Cache::tags(['availability', "branch:{$this->branchId}"])
            ->remember($key, self::CACHE_TTL, $callback);
    }
    
    public function invalidateBranch(string $branchId): void
    {
        Cache::tags(["branch:{$branchId}"])->flush();
    }
}
```

---

## 🧪 Testing Strategy

### 1. Unit Tests für kritische Komponenten

```php
class TimeSlotLockManagerTest extends TestCase
{
    /** @test */
    public function it_prevents_double_booking_with_concurrent_requests()
    {
        $branch = Branch::factory()->create();
        $staff = Staff::factory()->create();
        $startTime = now()->addDay()->setTime(10, 0);
        
        // Simulate concurrent requests
        $results = collect(range(1, 5))->map(function() use ($branch, $staff, $startTime) {
            return $this->lockManager->acquireLock(
                $branch->id,
                $staff->id,
                $startTime,
                $startTime->copy()->addMinutes(30)
            );
        });
        
        // Only one should succeed
        $this->assertEquals(1, $results->filter()->count());
    }
}
```

### 2. Integration Tests

```php
class RetellWebhookIntegrationTest extends TestCase
{
    /** @test */
    public function it_handles_webhook_idempotently()
    {
        $payload = [
            'event_type' => 'call_ended',
            'call_id' => 'test_123',
            'call' => ['duration' => 120]
        ];
        
        // First request
        $response1 = $this->postJson('/api/webhook', $payload, [
            'x-retell-signature' => $this->generateSignature($payload)
        ]);
        
        $response1->assertStatus(204);
        
        // Duplicate request
        $response2 = $this->postJson('/api/webhook', $payload, [
            'x-retell-signature' => $this->generateSignature($payload)
        ]);
        
        $response2->assertStatus(200)
            ->assertJson(['status' => 'already_processed']);
        
        // Verify only processed once
        $this->assertEquals(1, Call::where('external_id', 'test_123')->count());
    }
}
```

---

## 🚀 Deployment Checklist

### Pre-Deployment
- [ ] Run all migrations on staging
- [ ] Test webhook endpoints with actual services
- [ ] Verify all environment variables
- [ ] Load test critical endpoints
- [ ] Review security checklist

### Deployment
```bash
# 1. Set maintenance mode
php artisan down --message="Upgrading booking system" --retry=60

# 2. Pull latest code
git pull origin main

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Run migrations
php artisan migrate --force

# 5. Clear caches
php artisan optimize:clear

# 6. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Restart queue workers
php artisan queue:restart

# 8. Remove maintenance mode
php artisan up
```

### Post-Deployment Monitoring
- [ ] Monitor error logs for 30 minutes
- [ ] Check webhook processing queue
- [ ] Verify booking flow end-to-end
- [ ] Monitor API response times
- [ ] Check database query performance

---

## 📊 Success Metrics

1. **Availability API Response Time**: < 100ms (p95)
2. **Booking Success Rate**: > 95%
3. **Webhook Processing Time**: < 500ms
4. **Zero Race Condition Errors**
5. **Cal.com Sync Success Rate**: > 98%

---

## 🔄 Continuous Improvement

### Monitoring Dashboard Queries

```sql
-- Daily Booking Success Rate
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_attempts,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as successful,
    ROUND(SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as success_rate
FROM appointments
WHERE created_at >= NOW() - INTERVAL 30 DAY
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- API Performance by Service
SELECT 
    service,
    COUNT(*) as total_calls,
    AVG(duration_ms) as avg_duration,
    MAX(duration_ms) as max_duration,
    SUM(CASE WHEN response_status >= 500 THEN 1 ELSE 0 END) as errors
FROM api_call_logs
WHERE created_at >= NOW() - INTERVAL 1 HOUR
GROUP BY service;
```

Diese finale Spezifikation berücksichtigt alle identifizierten Probleme und implementiert robuste Lösungen basierend auf Best Practices und der durchgeführten Code-Analyse.