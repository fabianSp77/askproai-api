<?php

namespace Tests\SyntheticMonitoring;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\AlertingService;
use App\Services\MetricsCollector;
use Carbon\Carbon;

class ProductionMonitoringTest extends TestCase
{
    protected $alerting;
    protected $metrics;
    protected $productionUrl;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->alerting = new AlertingService();
        $this->metrics = new MetricsCollector();
        $this->productionUrl = config('app.production_url', 'https://api.askproai.de');
    }

    /**
     * Synthetic test: Critical user journey monitoring
     * Runs every 5 minutes in production
     */
    public function test_critical_appointment_booking_flow()
    {
        $startTime = microtime(true);
        $checkpoints = [];
        
        try {
            // Checkpoint 1: Health check
            $response = Http::timeout(5)->get($this->productionUrl . '/api/health');
            $checkpoints['health_check'] = [
                'status' => $response->status(),
                'duration_ms' => (microtime(true) - $startTime) * 1000
            ];
            $this->assertEquals(200, $response->status());
            
            // Checkpoint 2: Search for available slots
            $searchStart = microtime(true);
            $response = Http::timeout(10)->post($this->productionUrl . '/api/appointments/search', [
                'date' => now()->addDay()->format('Y-m-d'),
                'service_id' => 1,
                'duration' => 60
            ]);
            $checkpoints['slot_search'] = [
                'status' => $response->status(),
                'duration_ms' => (microtime(true) - $searchStart) * 1000,
                'slots_found' => count($response->json('data.slots', []))
            ];
            $this->assertEquals(200, $response->status());
            $this->assertNotEmpty($response->json('data.slots'));
            
            // Checkpoint 3: Validate booking process (dry run)
            $bookingStart = microtime(true);
            $response = Http::timeout(10)->post($this->productionUrl . '/api/appointments/validate', [
                'customer' => ['name' => 'Synthetic Test', 'phone' => '+49000000000'],
                'service_id' => 1,
                'slot' => $response->json('data.slots.0'),
                'dry_run' => true
            ]);
            $checkpoints['booking_validation'] = [
                'status' => $response->status(),
                'duration_ms' => (microtime(true) - $bookingStart) * 1000,
                'valid' => $response->json('data.valid', false)
            ];
            $this->assertEquals(200, $response->status());
            $this->assertTrue($response->json('data.valid'));
            
            // Record success metrics
            $totalDuration = (microtime(true) - $startTime) * 1000;
            $this->metrics->record('synthetic.booking_flow.success', 1);
            $this->metrics->record('synthetic.booking_flow.duration_ms', $totalDuration);
            
            // Alert if performance degrades
            if ($totalDuration > 5000) { // 5 seconds threshold
                $this->alerting->warning('Booking flow performance degradation', [
                    'duration_ms' => $totalDuration,
                    'checkpoints' => $checkpoints
                ]);
            }
            
        } catch (\Exception $e) {
            // Record failure
            $this->metrics->record('synthetic.booking_flow.failure', 1);
            
            // Send critical alert
            $this->alerting->critical('Booking flow synthetic test failed', [
                'error' => $e->getMessage(),
                'checkpoints' => $checkpoints,
                'production_url' => $this->productionUrl
            ]);
            
            throw $e;
        }
    }

    /**
     * Synthetic test: API endpoint monitoring
     * Runs every 1 minute
     */
    public function test_api_endpoints_availability()
    {
        $endpoints = [
            ['method' => 'GET', 'path' => '/api/health', 'timeout' => 3],
            ['method' => 'GET', 'path' => '/api/appointments', 'timeout' => 5],
            ['method' => 'GET', 'path' => '/api/customers', 'timeout' => 5],
            ['method' => 'GET', 'path' => '/api/services', 'timeout' => 5],
            ['method' => 'GET', 'path' => '/api/staff', 'timeout' => 5],
        ];
        
        $results = [];
        $failures = [];
        
        foreach ($endpoints as $endpoint) {
            $url = $this->productionUrl . $endpoint['path'];
            $start = microtime(true);
            
            try {
                $response = Http::timeout($endpoint['timeout'])
                    ->withHeaders(['X-Synthetic-Test' => 'true'])
                    ->{strtolower($endpoint['method'])}($url);
                
                $duration = (microtime(true) - $start) * 1000;
                
                $results[$endpoint['path']] = [
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                    'success' => $response->successful()
                ];
                
                // Record metrics
                $this->metrics->record("synthetic.endpoint.{$endpoint['method']}.{$endpoint['path']}.duration", $duration);
                $this->metrics->record("synthetic.endpoint.{$endpoint['method']}.{$endpoint['path']}.status", $response->status());
                
                // Check response time SLA
                if ($duration > 1000) { // 1 second SLA
                    $failures[] = "{$endpoint['path']} exceeded SLA: {$duration}ms";
                }
                
                // Check status code
                if (!$response->successful()) {
                    $failures[] = "{$endpoint['path']} returned {$response->status()}";
                }
                
            } catch (\Exception $e) {
                $failures[] = "{$endpoint['path']} failed: " . $e->getMessage();
                $results[$endpoint['path']] = [
                    'status' => 0,
                    'duration_ms' => (microtime(true) - $start) * 1000,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Alert on failures
        if (!empty($failures)) {
            $this->alerting->error('API endpoints failing synthetic tests', [
                'failures' => $failures,
                'results' => $results
            ]);
        }
        
        $this->assertEmpty($failures, 'Endpoint failures detected: ' . implode(', ', $failures));
    }

    /**
     * Synthetic test: Database performance monitoring
     * Runs every 5 minutes
     */
    public function test_database_performance()
    {
        $queries = [
            [
                'name' => 'appointment_search',
                'query' => 'SELECT * FROM appointments WHERE starts_at >= ? AND starts_at <= ? AND branch_id = ?',
                'params' => [now()->startOfDay(), now()->endOfDay(), 1],
                'max_duration_ms' => 50
            ],
            [
                'name' => 'customer_lookup',
                'query' => 'SELECT * FROM customers WHERE phone = ? OR email = ?',
                'params' => ['+49123456789', 'test@example.com'],
                'max_duration_ms' => 30
            ],
            [
                'name' => 'availability_check',
                'query' => 'SELECT COUNT(*) FROM appointments WHERE staff_id = ? AND starts_at BETWEEN ? AND ?',
                'params' => [1, now()->startOfDay(), now()->endOfDay()],
                'max_duration_ms' => 20
            ]
        ];
        
        foreach ($queries as $queryTest) {
            $start = microtime(true);
            
            try {
                $result = \DB::select($queryTest['query'], $queryTest['params']);
                $duration = (microtime(true) - $start) * 1000;
                
                // Record metrics
                $this->metrics->record("synthetic.database.{$queryTest['name']}.duration_ms", $duration);
                
                // Check performance threshold
                if ($duration > $queryTest['max_duration_ms']) {
                    $this->alerting->warning('Database query performance degradation', [
                        'query' => $queryTest['name'],
                        'duration_ms' => $duration,
                        'threshold_ms' => $queryTest['max_duration_ms']
                    ]);
                }
                
                $this->assertLessThan(
                    $queryTest['max_duration_ms'] * 2, // Allow 2x threshold before failing
                    $duration,
                    "Query {$queryTest['name']} exceeded performance threshold"
                );
                
            } catch (\Exception $e) {
                $this->alerting->critical('Database query failed in synthetic test', [
                    'query' => $queryTest['name'],
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }

    /**
     * Synthetic test: External service integration monitoring
     * Runs every 10 minutes
     */
    public function test_external_service_integrations()
    {
        $services = [
            [
                'name' => 'Cal.com',
                'test' => function() {
                    $response = Http::timeout(10)
                        ->withToken(config('services.calcom.token'))
                        ->get('https://api.cal.com/v2/event-types');
                    return $response->successful();
                }
            ],
            [
                'name' => 'Retell.ai',
                'test' => function() {
                    $response = Http::timeout(10)
                        ->withToken(config('services.retell.api_key'))
                        ->get('https://api.retellai.com/v2/agents');
                    return $response->successful();
                }
            ],
            [
                'name' => 'Stripe',
                'test' => function() {
                    \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                    $balance = \Stripe\Balance::retrieve();
                    return !empty($balance->available);
                }
            ]
        ];
        
        $serviceStatus = [];
        
        foreach ($services as $service) {
            $start = microtime(true);
            
            try {
                $success = $service['test']();
                $duration = (microtime(true) - $start) * 1000;
                
                $serviceStatus[$service['name']] = [
                    'available' => $success,
                    'response_time_ms' => $duration
                ];
                
                // Record metrics
                $this->metrics->record("synthetic.external_service.{$service['name']}.available", $success ? 1 : 0);
                $this->metrics->record("synthetic.external_service.{$service['name']}.response_time", $duration);
                
                if (!$success) {
                    $this->alerting->error("External service {$service['name']} is unavailable");
                }
                
            } catch (\Exception $e) {
                $serviceStatus[$service['name']] = [
                    'available' => false,
                    'error' => $e->getMessage()
                ];
                
                $this->alerting->critical("External service {$service['name']} integration failed", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // At least one service should be available
        $availableServices = array_filter($serviceStatus, fn($s) => $s['available']);
        $this->assertNotEmpty($availableServices, 'All external services are unavailable');
    }

    /**
     * Synthetic test: Real user scenario simulation
     * Runs every 30 minutes
     */
    public function test_real_user_scenario_simulation()
    {
        // Simulate a complete user journey
        $scenario = [
            'user' => 'synthetic-' . uniqid(),
            'steps' => []
        ];
        
        try {
            // Step 1: User visits website (simulate page load)
            $start = microtime(true);
            $response = Http::timeout(10)->get($this->productionUrl);
            $scenario['steps']['homepage_load'] = [
                'duration_ms' => (microtime(true) - $start) * 1000,
                'status' => $response->status()
            ];
            
            // Step 2: User searches for service
            $start = microtime(true);
            $response = Http::timeout(5)->get($this->productionUrl . '/api/services?search=haircut');
            $scenario['steps']['service_search'] = [
                'duration_ms' => (microtime(true) - $start) * 1000,
                'results' => count($response->json('data', []))
            ];
            
            // Step 3: User checks availability
            $serviceId = $response->json('data.0.id', 1);
            $start = microtime(true);
            $response = Http::timeout(5)->post($this->productionUrl . '/api/availability/check', [
                'service_id' => $serviceId,
                'date' => now()->addDays(3)->format('Y-m-d'),
                'preferred_time' => '14:00'
            ]);
            $scenario['steps']['availability_check'] = [
                'duration_ms' => (microtime(true) - $start) * 1000,
                'available' => $response->json('data.available', false)
            ];
            
            // Step 4: Simulate booking (dry run)
            if ($response->json('data.available')) {
                $start = microtime(true);
                $response = Http::timeout(10)->post($this->productionUrl . '/api/appointments/simulate', [
                    'customer' => [
                        'name' => 'Synthetic User',
                        'phone' => '+49000000001',
                        'email' => 'synthetic@test.com'
                    ],
                    'service_id' => $serviceId,
                    'slot' => $response->json('data.suggested_slot'),
                    'dry_run' => true
                ]);
                $scenario['steps']['booking_simulation'] = [
                    'duration_ms' => (microtime(true) - $start) * 1000,
                    'success' => $response->successful()
                ];
            }
            
            // Calculate total journey time
            $totalTime = array_sum(array_column(array_column($scenario['steps'], 'duration_ms'), 0));
            
            // Record journey metrics
            $this->metrics->record('synthetic.user_journey.complete', 1);
            $this->metrics->record('synthetic.user_journey.total_duration_ms', $totalTime);
            
            // Alert if journey takes too long
            if ($totalTime > 15000) { // 15 seconds threshold
                $this->alerting->warning('User journey performance degradation', [
                    'total_duration_ms' => $totalTime,
                    'scenario' => $scenario
                ]);
            }
            
        } catch (\Exception $e) {
            $this->metrics->record('synthetic.user_journey.failed', 1);
            $this->alerting->critical('User journey synthetic test failed', [
                'error' => $e->getMessage(),
                'scenario' => $scenario
            ]);
            throw $e;
        }
    }

    /**
     * Synthetic test: Security monitoring
     * Runs every hour
     */
    public function test_security_monitoring()
    {
        $securityChecks = [];
        
        // Check SSL certificate
        $sslInfo = $this->checkSSLCertificate($this->productionUrl);
        $securityChecks['ssl_certificate'] = $sslInfo;
        
        if ($sslInfo['days_until_expiry'] < 30) {
            $this->alerting->warning('SSL certificate expiring soon', $sslInfo);
        }
        
        // Check security headers
        $response = Http::get($this->productionUrl);
        $headers = $response->headers();
        
        $requiredHeaders = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Strict-Transport-Security' => true,
            'Content-Security-Policy' => true
        ];
        
        foreach ($requiredHeaders as $header => $expected) {
            if ($expected === true) {
                $securityChecks['headers'][$header] = isset($headers[$header]);
                if (!isset($headers[$header])) {
                    $this->alerting->error("Missing security header: {$header}");
                }
            } else {
                $securityChecks['headers'][$header] = ($headers[$header] ?? '') === $expected;
                if (($headers[$header] ?? '') !== $expected) {
                    $this->alerting->error("Invalid security header: {$header}");
                }
            }
        }
        
        // Check for exposed endpoints
        $exposedEndpoints = [
            '/.env',
            '/.git/config',
            '/phpinfo.php',
            '/debug',
            '/telescope'
        ];
        
        foreach ($exposedEndpoints as $endpoint) {
            $response = Http::get($this->productionUrl . $endpoint);
            $securityChecks['exposed_endpoints'][$endpoint] = $response->status() === 404;
            
            if ($response->status() !== 404) {
                $this->alerting->critical("Exposed endpoint detected: {$endpoint}", [
                    'status' => $response->status()
                ]);
            }
        }
        
        // Store security check results
        Cache::put('synthetic.security_check', $securityChecks, now()->addHour());
    }

    /**
     * Helper methods
     */
    protected function checkSSLCertificate($url)
    {
        $urlParts = parse_url($url);
        $host = $urlParts['host'];
        
        $context = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
        $stream = stream_socket_client(
            "ssl://{$host}:443", 
            $errno, 
            $errstr, 
            30, 
            STREAM_CLIENT_CONNECT, 
            $context
        );
        
        $params = stream_context_get_params($stream);
        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        
        return [
            'valid' => true,
            'issuer' => $cert['issuer']['O'] ?? 'Unknown',
            'valid_from' => date('Y-m-d', $cert['validFrom_time_t']),
            'valid_to' => date('Y-m-d', $cert['validTo_time_t']),
            'days_until_expiry' => floor(($cert['validTo_time_t'] - time()) / 86400)
        ];
    }
}
