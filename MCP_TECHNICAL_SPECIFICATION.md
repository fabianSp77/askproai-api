# 🔧 MCP Technical Specification für AskProAI

## 1. System Architecture

### 1.1 High-Level Architecture
```
┌─────────────────────────────────────────────────────────────┐
│                        Load Balancer                         │
│                    (Nginx + Rate Limiting)                   │
└─────────────────────┬───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│                      API Gateway                             │
│               (Laravel + MCP Controllers)                    │
└─────────────────────┬───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│                    Message Queue                             │
│                  (Redis + Horizon)                           │
└─────────┬───────────┬───────────┬───────────┬──────────────┘
          │           │           │           │
     ┌────▼────┐ ┌───▼────┐ ┌───▼────┐ ┌────▼────┐
     │Routing  │ │Call    │ │Booking │ │Database │
     │MCP      │ │MCP     │ │MCP     │ │MCP      │
     └────┬────┘ └───┬────┘ └───┬────┘ └────┬────┘
          │          │          │            │
          └──────────┴──────────┴────────────┘
                             │
                    ┌────────▼────────┐
                    │   PostgreSQL    │
                    │   + Redis Cache │
                    └─────────────────┘
```

### 1.2 Service Communication
```yaml
Communication Pattern: Event-Driven + Request-Response
Protocol: JSON-RPC 2.0 over Redis
Serialization: MessagePack for performance
Timeout: 30s default, configurable per service
Retry: Exponential backoff with jitter
```

## 2. MCP Service Specifications

### 2.1 RoutingMCP Service

#### Interface Definition
```php
namespace App\Services\MCP;

interface RoutingMCPInterface {
    /**
     * Route incoming call to appropriate handler
     * @param array $webhookData Retell webhook payload
     * @return RoutingDecision
     */
    public function routeCall(array $webhookData): RoutingDecision;
    
    /**
     * Resolve phone number to branch
     * @param string $phoneNumber E.164 format
     * @return Branch|null
     */
    public function resolveBranch(string $phoneNumber): ?Branch;
    
    /**
     * Extract booking intent from transcript
     * @param string $transcript Call transcript
     * @return BookingIntent
     */
    public function analyzeIntent(string $transcript): BookingIntent;
    
    /**
     * Find available staff for service
     * @param int $branchId
     * @param int $eventTypeId
     * @param Carbon $startTime
     * @return Collection<Staff>
     */
    public function findAvailableStaff(int $branchId, int $eventTypeId, Carbon $startTime): Collection;
}
```

#### Data Structures
```php
class RoutingDecision {
    public string $type; // 'specific_staff', 'next_available', 'transfer', 'info_only'
    public ?int $staffId;
    public ?int $eventTypeId;
    public ?int $branchId;
    public array $metadata;
    public float $confidence; // 0.0 - 1.0
}

class BookingIntent {
    public string $action; // 'book', 'cancel', 'reschedule', 'info'
    public ?Carbon $requestedDate;
    public ?string $requestedTime;
    public ?string $serviceName;
    public ?string $staffName;
    public array $extractedEntities;
}
```

#### Implementation Details
```php
class RoutingMCP implements RoutingMCPInterface {
    private PhoneNumberResolver $phoneResolver;
    private IntentAnalyzer $intentAnalyzer;
    private AvailabilityChecker $availabilityChecker;
    private CacheManager $cache;
    
    public function routeCall(array $webhookData): RoutingDecision {
        // 1. Extract key information
        $phoneNumber = $webhookData['from_number'];
        $transcript = $webhookData['transcript'] ?? '';
        
        // 2. Resolve branch with caching
        $cacheKey = "branch:phone:{$phoneNumber}";
        $branch = $this->cache->remember($cacheKey, 3600, function() use ($phoneNumber) {
            return $this->resolveBranch($phoneNumber);
        });
        
        if (!$branch) {
            throw new UnknownPhoneNumberException($phoneNumber);
        }
        
        // 3. Analyze intent
        $intent = $this->analyzeIntent($transcript);
        
        // 4. Make routing decision
        return $this->makeRoutingDecision($branch, $intent);
    }
    
    private function makeRoutingDecision(Branch $branch, BookingIntent $intent): RoutingDecision {
        $decision = new RoutingDecision();
        $decision->branchId = $branch->id;
        
        // Specific staff requested
        if ($intent->staffName) {
            $staff = $this->findStaffByName($branch->id, $intent->staffName);
            if ($staff) {
                $decision->type = 'specific_staff';
                $decision->staffId = $staff->id;
            }
        }
        
        // Service requested
        if ($intent->serviceName) {
            $eventType = $this->findEventTypeByName($branch->company_id, $intent->serviceName);
            if ($eventType) {
                $decision->eventTypeId = $eventType->id;
                
                if (!$decision->staffId) {
                    $availableStaff = $this->findAvailableStaff(
                        $branch->id, 
                        $eventType->id, 
                        $intent->requestedDate
                    );
                    
                    if ($availableStaff->isNotEmpty()) {
                        $decision->type = 'next_available';
                        $decision->staffId = $availableStaff->first()->id;
                    }
                }
            }
        }
        
        return $decision;
    }
}
```

### 2.2 CallMCP Service

#### Interface Definition
```php
interface CallMCPInterface {
    /**
     * Save call data from Retell webhook
     */
    public function saveCall(array $retellData, int $companyId): Call;
    
    /**
     * Extract structured data from transcript
     */
    public function extractMetadata(string $transcript): array;
    
    /**
     * Update call with appointment link
     */
    public function linkToAppointment(string $callId, int $appointmentId): void;
    
    /**
     * Get call analytics
     */
    public function getCallAnalytics(int $companyId, Carbon $from, Carbon $to): CallAnalytics;
}
```

#### Implementation
```php
class CallMCP implements CallMCPInterface {
    private DatabaseMCP $database;
    private MetadataExtractor $extractor;
    
    public function saveCall(array $retellData, int $companyId): Call {
        // Transaction with retry logic
        return $this->database->transaction(function() use ($retellData, $companyId) {
            // 1. Find or create customer
            $customer = $this->findOrCreateCustomer(
                $retellData['from_number'], 
                $companyId
            );
            
            // 2. Create call record
            $call = new Call([
                'company_id' => $companyId,
                'customer_id' => $customer->id,
                'retell_call_id' => $retellData['call_id'],
                'from_number' => $retellData['from_number'],
                'to_number' => $retellData['to_number'],
                'status' => $retellData['call_status'],
                'duration_seconds' => $retellData['duration'],
                'recording_url' => $retellData['recording_url'] ?? null,
                'transcript' => $retellData['transcript'] ?? null,
                'metadata' => $this->extractMetadata($retellData['transcript'] ?? ''),
                'started_at' => Carbon::parse($retellData['start_timestamp']),
                'ended_at' => Carbon::parse($retellData['end_timestamp']),
            ]);
            
            $call->save();
            
            // 3. Process post-save actions asynchronously
            ProcessCallMetadataJob::dispatch($call)->onQueue('low');
            
            return $call;
        });
    }
    
    public function extractMetadata(string $transcript): array {
        return $this->extractor->extract($transcript, [
            'customer_name' => '/mein name ist (.+)/i',
            'requested_service' => '/ich möchte (.+) buchen/i',
            'preferred_date' => '/am (.+) datum/i',
            'preferred_time' => '/um (.+) uhr/i',
            'sentiment' => 'ai_analysis',
            'intent' => 'ai_classification',
        ]);
    }
}
```

### 2.3 BookingMCP Service

#### Interface Definition
```php
interface BookingMCPInterface {
    /**
     * Create a new booking
     */
    public function createBooking(BookingRequest $request): BookingResult;
    
    /**
     * Check availability for given criteria
     */
    public function checkAvailability(AvailabilityCriteria $criteria): Collection;
    
    /**
     * Handle booking conflicts
     */
    public function handleConflict(BookingConflict $conflict): ConflictResolution;
    
    /**
     * Distributed locking for slot reservation
     */
    public function reserveSlot(int $staffId, Carbon $startTime, int $duration): string;
}
```

#### Implementation with Distributed Locking
```php
class BookingMCP implements BookingMCPInterface {
    private CalcomMCP $calcom;
    private DatabaseMCP $database;
    private RedisManager $redis;
    
    public function createBooking(BookingRequest $request): BookingResult {
        // 1. Reserve slot with distributed lock
        $lockKey = $this->reserveSlot(
            $request->staffId, 
            $request->startTime, 
            $request->eventType->duration
        );
        
        try {
            // 2. Double-check availability
            if (!$this->isSlotAvailable($request)) {
                throw new SlotNoLongerAvailableException();
            }
            
            // 3. Create booking in Cal.com
            $calcomBooking = $this->calcom->createBooking([
                'eventTypeId' => $request->eventType->calcom_id,
                'start' => $request->startTime->toIso8601String(),
                'responses' => [
                    'name' => $request->customer->name,
                    'email' => $request->customer->email,
                    'phone' => $request->customer->phone,
                ],
                'metadata' => [
                    'source' => 'phone_ai',
                    'call_id' => $request->callId,
                ],
            ]);
            
            // 4. Create local appointment record
            $appointment = $this->database->transaction(function() use ($request, $calcomBooking) {
                return Appointment::create([
                    'company_id' => $request->companyId,
                    'branch_id' => $request->branchId,
                    'customer_id' => $request->customer->id,
                    'staff_id' => $request->staffId,
                    'event_type_id' => $request->eventType->id,
                    'calcom_booking_id' => $calcomBooking['id'],
                    'start_time' => $request->startTime,
                    'end_time' => $request->startTime->copy()->addMinutes($request->eventType->duration),
                    'status' => 'scheduled',
                    'source' => 'phone_ai',
                    'call_id' => $request->callId,
                ]);
            });
            
            // 5. Send confirmations asynchronously
            SendBookingConfirmationJob::dispatch($appointment)->onQueue('high');
            
            return new BookingResult(
                success: true,
                appointment: $appointment,
                confirmationNumber: $appointment->confirmation_number
            );
            
        } finally {
            // Always release the lock
            $this->releaseLock($lockKey);
        }
    }
    
    public function reserveSlot(int $staffId, Carbon $startTime, int $duration): string {
        $lockKey = "booking:lock:{$staffId}:{$startTime->format('Y-m-d-H-i')}";
        $lockValue = Str::random(32);
        
        // Try to acquire lock with 30 second timeout
        $acquired = $this->redis->set(
            $lockKey, 
            $lockValue, 
            'NX', 
            'EX', 
            30
        );
        
        if (!$acquired) {
            throw new SlotLockedException("Slot is being booked by another request");
        }
        
        return "{$lockKey}:{$lockValue}";
    }
    
    private function releaseLock(string $lockKey): void {
        [$key, $value] = explode(':', $lockKey, 2);
        
        // Lua script for atomic check-and-delete
        $script = <<<'LUA'
            if redis.call("get", KEYS[1]) == ARGV[1] then
                return redis.call("del", KEYS[1])
            else
                return 0
            end
        LUA;
        
        $this->redis->eval($script, 1, $key, $value);
    }
}
```

### 2.4 DatabaseMCP Service

#### Interface Definition
```php
interface DatabaseMCPInterface {
    /**
     * Execute query with automatic caching
     */
    public function query(string $sql, array $bindings = [], int $cacheTtl = 0): Collection;
    
    /**
     * Execute transaction with retry logic
     */
    public function transaction(Closure $callback, int $attempts = 3): mixed;
    
    /**
     * Invalidate cache by pattern
     */
    public function invalidateCache(string $pattern): void;
    
    /**
     * Get connection from pool
     */
    public function getConnection(string $mode = 'write'): Connection;
}
```

#### Implementation with Connection Pooling
```php
class DatabaseMCP implements DatabaseMCPInterface {
    private array $writePool = [];
    private array $readPool = [];
    private CacheManager $cache;
    private int $maxConnections = 50;
    
    public function query(string $sql, array $bindings = [], int $cacheTtl = 0): Collection {
        // Determine if query is read or write
        $isWrite = $this->isWriteQuery($sql);
        
        if (!$isWrite && $cacheTtl > 0) {
            $cacheKey = $this->getCacheKey($sql, $bindings);
            
            return $this->cache->remember($cacheKey, $cacheTtl, function() use ($sql, $bindings) {
                return $this->executeQuery($sql, $bindings, 'read');
            });
        }
        
        return $this->executeQuery($sql, $bindings, $isWrite ? 'write' : 'read');
    }
    
    public function transaction(Closure $callback, int $attempts = 3): mixed {
        $attempt = 1;
        
        while ($attempt <= $attempts) {
            $connection = $this->getConnection('write');
            
            try {
                $connection->beginTransaction();
                $result = $callback($connection);
                $connection->commit();
                
                return $result;
                
            } catch (DeadlockException $e) {
                $connection->rollBack();
                
                if ($attempt === $attempts) {
                    throw $e;
                }
                
                // Exponential backoff with jitter
                $delay = (2 ** $attempt) * 100 + random_int(0, 100);
                usleep($delay * 1000);
                
                $attempt++;
                
            } catch (Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }
    }
    
    public function getConnection(string $mode = 'write'): Connection {
        $pool = $mode === 'write' ? $this->writePool : $this->readPool;
        
        // Find available connection
        foreach ($pool as $connection) {
            if (!$connection->inTransaction()) {
                return $connection;
            }
        }
        
        // Create new connection if under limit
        if (count($pool) < $this->maxConnections) {
            $connection = $this->createConnection($mode);
            $pool[] = $connection;
            return $connection;
        }
        
        // Wait for available connection
        return $this->waitForConnection($mode);
    }
}
```

### 2.5 CalcomMCP Service

#### Interface Definition
```php
interface CalcomMCPInterface {
    /**
     * Get availability with circuit breaker
     */
    public function getAvailability(int $eventTypeId, DateRange $range): Collection;
    
    /**
     * Create booking with retry logic
     */
    public function createBooking(array $data): array;
    
    /**
     * Sync event types with rate limiting
     */
    public function syncEventTypes(int $companyId): Collection;
    
    /**
     * Bulk availability check
     */
    public function bulkCheckAvailability(array $criteria): array;
}
```

#### Implementation with Circuit Breaker
```php
class CalcomMCP implements CalcomMCPInterface {
    private CalcomV2Client $client;
    private CircuitBreaker $circuitBreaker;
    private RateLimiter $rateLimiter;
    private CacheManager $cache;
    
    public function __construct() {
        $this->circuitBreaker = new CircuitBreaker(
            name: 'calcom',
            failureThreshold: 5,
            recoveryTime: 60,
            timeout: 30
        );
        
        $this->rateLimiter = new RateLimiter(
            name: 'calcom_api',
            maxRequests: 100,
            perMinutes: 1
        );
    }
    
    public function getAvailability(int $eventTypeId, DateRange $range): Collection {
        return $this->circuitBreaker->call(function() use ($eventTypeId, $range) {
            // Check rate limit
            $this->rateLimiter->hit();
            
            // Check cache first
            $cacheKey = "availability:{$eventTypeId}:{$range->start->format('Y-m-d')}:{$range->end->format('Y-m-d')}";
            
            return $this->cache->remember($cacheKey, 300, function() use ($eventTypeId, $range) {
                $response = $this->client->get("/availability/{$eventTypeId}", [
                    'dateFrom' => $range->start->toIso8601String(),
                    'dateTo' => $range->end->toIso8601String(),
                ]);
                
                return collect($response['slots'])->map(function($slot) {
                    return new TimeSlot(
                        start: Carbon::parse($slot['start']),
                        end: Carbon::parse($slot['end']),
                        available: true
                    );
                });
            });
        });
    }
    
    public function createBooking(array $data): array {
        return $this->circuitBreaker->call(function() use ($data) {
            $this->rateLimiter->hit();
            
            // Retry logic with exponential backoff
            $attempt = 0;
            $maxAttempts = 3;
            
            while ($attempt < $maxAttempts) {
                try {
                    return $this->client->post('/bookings', $data);
                    
                } catch (CalcomApiException $e) {
                    $attempt++;
                    
                    if ($attempt >= $maxAttempts || !$this->isRetryable($e)) {
                        throw $e;
                    }
                    
                    $delay = (2 ** $attempt) * 1000;
                    usleep($delay * 1000);
                }
            }
        });
    }
    
    private function isRetryable(CalcomApiException $e): bool {
        // Retry on 429 (rate limit) and 5xx errors
        return in_array($e->getStatusCode(), [429, 500, 502, 503, 504]);
    }
}
```

## 3. Infrastructure Requirements

### 3.1 Server Specifications
```yaml
Production Environment:
  Application Servers:
    Count: 3 (minimum)
    CPU: 8 cores
    RAM: 32 GB
    Storage: 100 GB SSD
    
  Database Server:
    Type: PostgreSQL 15+
    CPU: 16 cores
    RAM: 64 GB
    Storage: 500 GB NVMe SSD
    IOPS: 10,000+
    
  Cache Server:
    Type: Redis 7+
    RAM: 16 GB
    Persistence: AOF with fsync every second
    
  Queue Workers:
    Count: Auto-scaling 5-50
    CPU: 2 cores each
    RAM: 4 GB each
```

### 3.2 Network Architecture
```yaml
Load Balancing:
  Type: Application Load Balancer
  SSL: Termination at LB
  Health Checks: /health endpoint
  Sticky Sessions: Not required
  
Security:
  WAF: Enable with OWASP rules
  DDoS: CloudFlare or AWS Shield
  VPC: Private subnets for DB/Cache
  Security Groups: Least privilege
```

### 3.3 Monitoring Stack
```yaml
Metrics:
  - Prometheus for metrics collection
  - Grafana for visualization
  - Custom dashboards per MCP service
  
Logging:
  - ELK Stack (Elasticsearch, Logstash, Kibana)
  - Structured JSON logging
  - Correlation IDs for request tracing
  
Alerting:
  - PagerDuty integration
  - Slack notifications
  - Email alerts for critical issues
  
APM:
  - New Relic or DataDog
  - Distributed tracing
  - Performance profiling
```

## 4. Deployment Strategy

### 4.1 CI/CD Pipeline
```yaml
Pipeline Stages:
  1. Code Quality:
     - PHPStan Level 8
     - PHPCS with PSR-12
     - Security scanning (Snyk)
     
  2. Testing:
     - Unit tests (PHPUnit)
     - Integration tests
     - Load tests (K6)
     
  3. Build:
     - Docker multi-stage build
     - Image scanning
     - Push to registry
     
  4. Deploy:
     - Blue-green deployment
     - Database migrations
     - Cache warming
     - Health checks
```

### 4.2 Rollback Strategy
```yaml
Automated Rollback Triggers:
  - Error rate > 5%
  - Response time > 3s (p95)
  - Health check failures
  - Memory usage > 90%
  
Rollback Process:
  1. Pause new deployments
  2. Switch traffic to previous version
  3. Preserve logs and metrics
  4. Notify on-call team
  5. Create incident report
```

## 5. Security Specifications

### 5.1 Authentication & Authorization
```php
// Multi-tenant authentication middleware
class MCPAuthMiddleware {
    public function handle($request, $next) {
        // 1. Validate API key
        $apiKey = $request->header('X-API-Key');
        $company = Company::where('api_key', $apiKey)->first();
        
        if (!$company) {
            throw new UnauthorizedException();
        }
        
        // 2. Set tenant context
        app()->instance('current_company', $company);
        
        // 3. Apply rate limiting per tenant
        $rateLimiter = app(RateLimiter::class);
        $rateLimiter->forCompany($company->id)->hit();
        
        return $next($request);
    }
}
```

### 5.2 Data Encryption
```yaml
At Rest:
  - Database: Transparent Data Encryption (TDE)
  - File Storage: AES-256 encryption
  - Backups: Encrypted with separate keys
  
In Transit:
  - TLS 1.3 minimum
  - Certificate pinning for mobile apps
  - mTLS for service-to-service
  
Sensitive Data:
  - PII: Encrypted with field-level encryption
  - API Keys: Hashed with bcrypt
  - Phone Numbers: Partially masked in logs
```

### 5.3 Audit Logging
```php
// Comprehensive audit logging
class AuditLogger {
    public function log(string $action, array $context = []): void {
        AuditLog::create([
            'company_id' => app('current_company')->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'resource_type' => $context['resource_type'] ?? null,
            'resource_id' => $context['resource_id'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'changes' => $context['changes'] ?? [],
            'timestamp' => now(),
        ]);
    }
}
```

## 6. Performance Optimization

### 6.1 Database Optimization
```sql
-- Partitioning for large tables
CREATE TABLE calls_2025 PARTITION OF calls
FOR VALUES FROM ('2025-01-01') TO ('2026-01-01');

-- Materialized views for analytics
CREATE MATERIALIZED VIEW daily_booking_stats AS
SELECT 
    company_id,
    branch_id,
    DATE(start_time) as booking_date,
    COUNT(*) as total_bookings,
    COUNT(DISTINCT customer_id) as unique_customers,
    AVG(EXTRACT(EPOCH FROM (end_time - start_time))/60) as avg_duration_minutes
FROM appointments
WHERE status = 'completed'
GROUP BY company_id, branch_id, DATE(start_time)
WITH DATA;

-- Create indexes for common queries
CREATE INDEX CONCURRENTLY idx_appointments_lookup 
ON appointments(company_id, branch_id, start_time)
WHERE status IN ('scheduled', 'confirmed');
```

### 6.2 Caching Strategy
```php
class CacheStrategy {
    // Cache layers
    const L1_TTL = 60;      // 1 minute - hot data
    const L2_TTL = 3600;    // 1 hour - warm data
    const L3_TTL = 86400;   // 1 day - cold data
    
    public function get(string $key, Closure $callback, string $tier = 'L2') {
        // Try L1 cache first (Redis)
        $value = Cache::store('redis')->get($key);
        if ($value !== null) return $value;
        
        // Try L2 cache (Redis with longer TTL)
        if ($tier !== 'L1') {
            $value = Cache::store('redis_persistent')->get($key);
            if ($value !== null) {
                // Promote to L1
                Cache::store('redis')->put($key, $value, self::L1_TTL);
                return $value;
            }
        }
        
        // Generate value
        $value = $callback();
        
        // Store in appropriate tiers
        $ttl = constant("self::{$tier}_TTL");
        Cache::store('redis')->put($key, $value, min($ttl, self::L1_TTL));
        
        if ($tier !== 'L1') {
            Cache::store('redis_persistent')->put($key, $value, $ttl);
        }
        
        return $value;
    }
}
```

## 7. Testing Strategy

### 7.1 Test Coverage Requirements
```yaml
Unit Tests:
  - Coverage: 90%+
  - Focus: Business logic, data transformations
  - Tools: PHPUnit, Mockery
  
Integration Tests:
  - Coverage: 80%+
  - Focus: MCP service interactions
  - Tools: PHPUnit, Testcontainers
  
E2E Tests:
  - Coverage: Critical paths
  - Focus: Complete booking flow
  - Tools: Cypress, Artillery
  
Load Tests:
  - Target: 1000 concurrent calls
  - Duration: 60 minutes
  - Tools: K6, Grafana
```

### 7.2 Test Data Management
```php
// Test data factory for realistic scenarios
class TestDataFactory {
    public function createRealisticBookingScenario(): array {
        $company = Company::factory()->withBranches(3)->create();
        
        foreach ($company->branches as $branch) {
            // Create staff with different specializations
            Staff::factory()
                ->count(5)
                ->withEventTypes(3)
                ->withAvailability()
                ->create(['branch_id' => $branch->id]);
            
            // Create customers with history
            Customer::factory()
                ->count(100)
                ->withAppointmentHistory()
                ->create(['company_id' => $company->id]);
        }
        
        return [
            'company' => $company,
            'test_phone' => '+49 30 12345678',
            'test_customer' => $company->customers->first(),
        ];
    }
}
```

## 8. Operational Runbooks

### 8.1 Incident Response
```yaml
P1 - Complete Outage:
  1. Check health endpoints
  2. Verify database connectivity
  3. Check Redis availability
  4. Review recent deployments
  5. Initiate rollback if needed
  
P2 - Degraded Performance:
  1. Check APM dashboards
  2. Identify slow queries
  3. Check cache hit rates
  4. Scale workers if needed
  5. Enable read replicas
  
P3 - Integration Issues:
  1. Check circuit breaker status
  2. Verify API credentials
  3. Check rate limits
  4. Review integration logs
  5. Contact third-party support
```

### 8.2 Maintenance Procedures
```yaml
Database Maintenance:
  - Weekly: VACUUM ANALYZE
  - Monthly: Reindex large tables
  - Quarterly: Partition rotation
  
Cache Maintenance:
  - Daily: Memory usage check
  - Weekly: Key analysis
  - Monthly: Full cache flush
  
Queue Maintenance:
  - Hourly: Dead letter queue check
  - Daily: Worker health check
  - Weekly: Queue depth analysis
```

---

**Diese Spezifikation wurde für maximale Klarheit, Wartbarkeit und Skalierbarkeit entwickelt.**