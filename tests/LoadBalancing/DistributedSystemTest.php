<?php

namespace Tests\LoadBalancing;

use Tests\TestCase;
use App\Services\LoadBalancerService;
use App\Services\HealthCheckService;
use App\Services\CircuitBreakerService;
use App\Services\ServiceDiscoveryService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DistributedSystemTest extends TestCase
{
    protected $loadBalancer;
    protected $healthCheck;
    protected $circuitBreaker;
    protected $serviceDiscovery;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loadBalancer = new LoadBalancerService();
        $this->healthCheck = new HealthCheckService();
        $this->circuitBreaker = new CircuitBreakerService();
        $this->serviceDiscovery = new ServiceDiscoveryService();
    }

    /**
     * Test: Load balancer algorithm implementations
     */
    public function test_load_balancing_algorithms()
    {
        $servers = [
            ['id' => 'server1', 'weight' => 3, 'connections' => 10],
            ['id' => 'server2', 'weight' => 2, 'connections' => 15],
            ['id' => 'server3', 'weight' => 1, 'connections' => 5],
        ];
        
        // Test Round Robin
        $this->loadBalancer->setAlgorithm('round_robin');
        $selections = [];
        for ($i = 0; $i < 6; $i++) {
            $server = $this->loadBalancer->selectServer($servers);
            $selections[] = $server['id'];
        }
        $this->assertEquals(['server1', 'server2', 'server3', 'server1', 'server2', 'server3'], $selections);
        
        // Test Weighted Round Robin
        $this->loadBalancer->setAlgorithm('weighted_round_robin');
        $selections = [];
        for ($i = 0; $i < 12; $i++) {
            $server = $this->loadBalancer->selectServer($servers);
            $selections[$server['id']] = ($selections[$server['id']] ?? 0) + 1;
        }
        $this->assertEquals(6, $selections['server1']); // weight 3 = 50%
        $this->assertEquals(4, $selections['server2']); // weight 2 = 33%
        $this->assertEquals(2, $selections['server3']); // weight 1 = 17%
        
        // Test Least Connections
        $this->loadBalancer->setAlgorithm('least_connections');
        $server = $this->loadBalancer->selectServer($servers);
        $this->assertEquals('server3', $server['id']); // Has least connections (5)
        
        // Test IP Hash (consistent hashing)
        $this->loadBalancer->setAlgorithm('ip_hash');
        $clientIp = '192.168.1.100';
        $server1 = $this->loadBalancer->selectServer($servers, ['client_ip' => $clientIp]);
        $server2 = $this->loadBalancer->selectServer($servers, ['client_ip' => $clientIp]);
        $this->assertEquals($server1['id'], $server2['id']); // Same IP always gets same server
    }

    /**
     * Test: Health check mechanisms
     */
    public function test_health_check_mechanisms()
    {
        $services = [
            ['name' => 'api-1', 'url' => 'http://api1.internal:8080/health'],
            ['name' => 'api-2', 'url' => 'http://api2.internal:8080/health'],
            ['name' => 'api-3', 'url' => 'http://api3.internal:8080/health'],
        ];
        
        // Mock health check responses
        Http::fake([
            'api1.internal:8080/health' => Http::response(['status' => 'healthy'], 200),
            'api2.internal:8080/health' => Http::response(['status' => 'degraded'], 200),
            'api3.internal:8080/health' => Http::response(null, 500),
        ]);
        
        // Perform health checks
        $results = [];
        foreach ($services as $service) {
            $health = $this->healthCheck->check($service);
            $results[$service['name']] = $health;
        }
        
        $this->assertTrue($results['api-1']['healthy']);
        $this->assertTrue($results['api-2']['healthy']); // Degraded but still healthy
        $this->assertFalse($results['api-3']['healthy']);
        
        // Test health check with retries
        Http::fake([
            'flaky.internal:8080/health' => Http::sequence()
                ->push(null, 500)
                ->push(null, 500)
                ->push(['status' => 'healthy'], 200)
        ]);
        
        $health = $this->healthCheck->checkWithRetries([
            'name' => 'flaky-api',
            'url' => 'http://flaky.internal:8080/health'
        ], 3);
        
        $this->assertTrue($health['healthy']);
        $this->assertEquals(3, $health['attempts']);
    }

    /**
     * Test: Circuit breaker patterns
     */
    public function test_circuit_breaker_behavior()
    {
        $service = 'payment-api';
        
        // Circuit starts closed
        $this->assertEquals('closed', $this->circuitBreaker->getState($service));
        
        // Simulate failures
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure($service);
        }
        
        // Circuit should open after threshold
        $this->assertEquals('open', $this->circuitBreaker->getState($service));
        
        // Calls should be rejected when open
        $result = $this->circuitBreaker->call($service, function() {
            return 'success';
        });
        $this->assertNull($result);
        $this->assertTrue($this->circuitBreaker->isOpen($service));
        
        // Wait for half-open state
        sleep(3); // Assuming 3 second timeout
        $this->assertEquals('half-open', $this->circuitBreaker->getState($service));
        
        // Successful call should close circuit
        $result = $this->circuitBreaker->call($service, function() {
            return 'success';
        });
        $this->assertEquals('success', $result);
        $this->assertEquals('closed', $this->circuitBreaker->getState($service));
        
        // Test circuit breaker with fallback
        $this->circuitBreaker->recordFailure($service, 10); // Force open
        $result = $this->circuitBreaker->callWithFallback($service, 
            function() { throw new \Exception('Service down'); },
            function() { return 'fallback response'; }
        );
        $this->assertEquals('fallback response', $result);
    }

    /**
     * Test: Service discovery and registration
     */
    public function test_service_discovery()
    {
        // Register services
        $this->serviceDiscovery->register([
            'name' => 'appointment-service',
            'version' => '2.1.0',
            'endpoints' => [
                ['host' => '10.0.1.10', 'port' => 8080, 'weight' => 100],
                ['host' => '10.0.1.11', 'port' => 8080, 'weight' => 100],
            ],
            'health_check' => '/health',
            'metadata' => ['region' => 'eu-west-1', 'zone' => 'a']
        ]);
        
        $this->serviceDiscovery->register([
            'name' => 'appointment-service',
            'version' => '2.0.0',
            'endpoints' => [
                ['host' => '10.0.1.12', 'port' => 8080, 'weight' => 50],
            ],
            'health_check' => '/health',
            'metadata' => ['region' => 'eu-west-1', 'zone' => 'b']
        ]);
        
        // Discover services
        $services = $this->serviceDiscovery->discover('appointment-service');
        $this->assertCount(3, $services['endpoints']);
        
        // Discover with version constraint
        $services = $this->serviceDiscovery->discover('appointment-service', ['version' => '2.1.0']);
        $this->assertCount(2, $services['endpoints']);
        
        // Discover with metadata filter
        $services = $this->serviceDiscovery->discover('appointment-service', ['zone' => 'a']);
        $this->assertCount(2, $services['endpoints']);
        
        // Test service deregistration
        $this->serviceDiscovery->deregister('appointment-service', '10.0.1.12');
        $services = $this->serviceDiscovery->discover('appointment-service');
        $this->assertCount(2, $services['endpoints']);
    }

    /**
     * Test: Distributed rate limiting
     */
    public function test_distributed_rate_limiting()
    {
        $rateLimiter = new \App\Services\DistributedRateLimiter();
        $key = 'api:user:123';
        $limit = 10;
        $window = 60; // 1 minute
        
        // Test allowing requests within limit
        for ($i = 0; $i < $limit; $i++) {
            $allowed = $rateLimiter->attempt($key, $limit, $window);
            $this->assertTrue($allowed, "Request $i should be allowed");
        }
        
        // Test blocking after limit
        $allowed = $rateLimiter->attempt($key, $limit, $window);
        $this->assertFalse($allowed);
        
        // Test getting remaining attempts
        $remaining = $rateLimiter->remaining($key, $limit, $window);
        $this->assertEquals(0, $remaining);
        
        // Test reset time
        $resetTime = $rateLimiter->availableAt($key, $window);
        $this->assertGreaterThan(time(), $resetTime);
        $this->assertLessThanOrEqual(time() + $window, $resetTime);
        
        // Test sliding window
        $slidingLimiter = new \App\Services\SlidingWindowRateLimiter();
        for ($i = 0; $i < $limit; $i++) {
            $allowed = $slidingLimiter->attempt($key, $limit, $window);
            $this->assertTrue($allowed);
            usleep(100000); // 0.1 second between requests
        }
    }

    /**
     * Test: Distributed caching strategies
     */
    public function test_distributed_caching()
    {
        $cache = new \App\Services\DistributedCache();
        
        // Test write-through cache
        $cache->setStrategy('write-through');
        $value = $cache->remember('user:123', function() {
            return ['name' => 'John Doe', 'email' => 'john@example.com'];
        });
        
        // Verify data is in both cache and database
        $this->assertEquals($value, $cache->get('user:123'));
        $this->assertEquals($value, DB::table('users')->find(123));
        
        // Test write-behind cache
        $cache->setStrategy('write-behind');
        $cache->put('user:124', ['name' => 'Jane Doe']);
        
        // Data should be in cache immediately
        $this->assertEquals(['name' => 'Jane Doe'], $cache->get('user:124'));
        
        // But not in database yet
        $this->assertNull(DB::table('users')->find(124));
        
        // Process write-behind queue
        $cache->flushWriteBehind();
        $this->assertNotNull(DB::table('users')->find(124));
        
        // Test cache invalidation across nodes
        $cache->put('global:config', ['version' => 1]);
        $cache->invalidateAcrossNodes('global:config');
        
        // Verify invalidation message was broadcast
        $this->assertTrue(Redis::sismember('invalidated_keys', 'global:config'));
    }

    /**
     * Test: Distributed locking
     */
    public function test_distributed_locking()
    {
        $locker = new \App\Services\DistributedLock();
        $resource = 'appointment:booking:123';
        
        // Test acquiring lock
        $lock1 = $locker->acquire($resource, 5); // 5 second TTL
        $this->assertNotNull($lock1);
        
        // Test lock prevents concurrent access
        $lock2 = $locker->acquire($resource, 5);
        $this->assertNull($lock2);
        
        // Test lock release
        $locker->release($lock1);
        $lock3 = $locker->acquire($resource, 5);
        $this->assertNotNull($lock3);
        
        // Test lock auto-expiry
        $shortLock = $locker->acquire('test:resource', 1);
        sleep(2);
        $newLock = $locker->acquire('test:resource', 5);
        $this->assertNotNull($newLock);
        
        // Test distributed mutex
        $results = [];
        $threads = [];
        
        for ($i = 0; $i < 5; $i++) {
            $threads[] = new \Thread(function() use ($locker, &$results, $i) {
                $locker->synchronized('shared:counter', function() use (&$results, $i) {
                    $current = Cache::get('shared:counter', 0);
                    usleep(10000); // Simulate work
                    Cache::put('shared:counter', $current + 1);
                    $results[] = $i;
                });
            });
        }
        
        foreach ($threads as $thread) {
            $thread->start();
        }
        foreach ($threads as $thread) {
            $thread->join();
        }
        
        $this->assertEquals(5, Cache::get('shared:counter'));
    }

    /**
     * Test: Distributed tracing
     */
    public function test_distributed_tracing()
    {
        $tracer = new \App\Services\DistributedTracer();
        
        // Start root span
        $rootSpan = $tracer->startSpan('api.request', [
            'http.method' => 'POST',
            'http.url' => '/api/appointments'
        ]);
        
        // Child span for database
        $dbSpan = $tracer->startSpan('db.query', [
            'db.statement' => 'SELECT * FROM appointments',
            'parent_id' => $rootSpan->id
        ]);
        usleep(50000); // Simulate query time
        $tracer->finishSpan($dbSpan);
        
        // Child span for external service
        $httpSpan = $tracer->startSpan('http.client', [
            'http.url' => 'https://api.cal.com/events',
            'parent_id' => $rootSpan->id
        ]);
        usleep(100000); // Simulate API call
        $tracer->finishSpan($httpSpan);
        
        // Finish root span
        $tracer->finishSpan($rootSpan);
        
        // Get trace
        $trace = $tracer->getTrace($rootSpan->traceId);
        $this->assertCount(3, $trace['spans']);
        
        // Verify span relationships
        $this->assertEquals($rootSpan->id, $trace['spans'][1]['parent_id']);
        $this->assertEquals($rootSpan->id, $trace['spans'][2]['parent_id']);
        
        // Test trace context propagation
        $headers = $tracer->inject($rootSpan);
        $this->assertArrayHasKey('X-Trace-Id', $headers);
        $this->assertArrayHasKey('X-Span-Id', $headers);
        
        // Extract trace context
        $extractedSpan = $tracer->extract($headers);
        $this->assertEquals($rootSpan->traceId, $extractedSpan->traceId);
    }

    /**
     * Test: Distributed consensus
     */
    public function test_distributed_consensus()
    {
        $consensus = new \App\Services\DistributedConsensus();
        
        // Test leader election
        $nodes = ['node1', 'node2', 'node3'];
        $leader = $consensus->electLeader('cluster1', $nodes);
        $this->assertContains($leader, $nodes);
        
        // Verify same leader across queries
        $leader2 = $consensus->getLeader('cluster1');
        $this->assertEquals($leader, $leader2);
        
        // Test consensus for configuration change
        $proposal = [
            'action' => 'add_node',
            'node' => 'node4',
            'timestamp' => time()
        ];
        
        $approved = $consensus->propose('cluster1', $proposal);
        $this->assertTrue($approved);
        
        // Test split-brain detection
        $consensus->simulateNetworkPartition(['node1'], ['node2', 'node3']);
        
        try {
            $consensus->propose('cluster1', ['action' => 'critical_update']);
            $this->fail('Should detect split-brain');
        } catch (\App\Exceptions\SplitBrainException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test: Distributed queue management
     */
    public function test_distributed_queue_management()
    {
        $queueManager = new \App\Services\DistributedQueueManager();
        
        // Test queue partitioning
        $partitions = $queueManager->getPartitions('appointments');
        $this->assertCount(4, $partitions); // Assuming 4 partitions
        
        // Test message routing
        $message = ['type' => 'appointment.created', 'id' => 123];
        $partition = $queueManager->routeMessage('appointments', $message);
        $this->assertContains($partition, $partitions);
        
        // Test consistent routing
        $partition2 = $queueManager->routeMessage('appointments', $message);
        $this->assertEquals($partition, $partition2);
        
        // Test rebalancing on node addition
        $queueManager->addNode('new-worker');
        $newPartitions = $queueManager->getPartitions('appointments');
        
        // Some messages should move to new node
        $movedCount = 0;
        for ($i = 0; $i < 1000; $i++) {
            $oldPartition = $queueManager->routeMessage('appointments', ['id' => $i], $partitions);
            $newPartition = $queueManager->routeMessage('appointments', ['id' => $i], $newPartitions);
            if ($oldPartition !== $newPartition) {
                $movedCount++;
            }
        }
        
        // Approximately 1/5 of messages should move (1 new node added to 4)
        $this->assertGreaterThan(150, $movedCount);
        $this->assertLessThan(250, $movedCount);
    }
}
