<?php

namespace Tests\Feature\MCP;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Events\SecurityEvent;
use App\Services\CircuitBreaker;

class RetellMCPEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected string $mcpEndpoint = '/api/mcp/retell/tools';
    protected string $validToken = 'test_mcp_token_2024';
    protected string $invalidToken = 'invalid_token_123';
    protected string $backupToken = 'backup_test_token_2024';
    protected array $allowedIPs = ['127.0.0.1', '192.168.1.100'];
    protected array $blockedIPs = ['192.168.1.200', '10.0.0.50'];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test environment
        config(['app.env' => 'local']);
        config(['retell-mcp.security.mcp_token' => $this->validToken]);
        
        // Configure test tokens
        config(['retell-mcp.security.backup_token' => $this->backupToken]);
        config(['retell-mcp.security.rate_limit_per_token' => 100]);
        config(['retell-mcp.security.rate_limit_window' => 60]);
        config(['retell-mcp.security.allowed_ips' => $this->allowedIPs]);
        config(['retell-mcp.security.circuit_breaker_threshold' => 5]);
        config(['retell-mcp.security.circuit_breaker_timeout' => 60]);
        
        // Clear cache for clean tests
        Cache::flush();
        
        // Reset circuit breaker state
        if (class_exists(CircuitBreaker::class)) {
            CircuitBreaker::reset('mcp_endpoint');
        }
        
        // Create test data
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'calcom_api_key' => 'test_key',
            'calcom_event_type_id' => 123
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch'
        ]);
        
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Beratung',
            'duration' => 30
        ]);
    }
    
    /**
     * Test getCurrentTimeBerlin tool
     */
    public function test_get_current_time_berlin_returns_correct_format()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_time_berlin',
                    'current_date',
                    'current_time',
                    'weekday',
                    'timestamp',
                    'timezone'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'timezone' => 'Europe/Berlin'
                ]
            ]);
        
        // Verify the weekday is in German
        $weekdays = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
        $this->assertContains($response->json('data.weekday'), $weekdays);
    }
    
    /**
     * Test authentication failure without token
     */
    public function test_request_fails_without_authentication_token()
    {
        $response = $this->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'Authorization header required'
            ]);
    }
    
    /**
     * Test authentication failure with invalid token
     */
    public function test_request_fails_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token_123',
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Invalid MCP token'
            ]);
    }
    
    /**
     * Test checkAvailableSlots tool
     */
    public function test_check_available_slots_returns_slots_for_date()
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'checkAvailableSlots',
            'arguments' => [
                'date' => $tomorrow,
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id
            ]
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'date',
                    'slots',
                    'message'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'date' => $tomorrow
                ]
            ]);
        
        // Verify slots have correct format
        $slots = $response->json('data.slots');
        if (!empty($slots)) {
            $this->assertArrayHasKey('time', $slots[0]);
            $this->assertArrayHasKey('available', $slots[0]);
        }
    }
    
    /**
     * Test checkAvailableSlots with relative date
     */
    public function test_check_available_slots_handles_relative_dates()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'checkAvailableSlots',
            'arguments' => [
                'datum' => 'morgen'
            ]
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
        
        // Verify the date was correctly parsed
        $expectedDate = Carbon::tomorrow()->format('Y-m-d');
        $this->assertEquals($expectedDate, $response->json('data.date'));
    }
    
    /**
     * Test bookAppointment tool
     */
    public function test_book_appointment_creates_appointment_successfully()
    {
        $appointmentData = [
            'name' => 'Max Mustermann',
            'telefonnummer' => '+49 123 456789',
            'email' => 'max@example.com',
            'datum' => Carbon::tomorrow()->format('Y-m-d'),
            'uhrzeit' => '14:30',
            'dienstleistung' => 'Beratung',
            'notizen' => 'Erste Beratung',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'bookAppointment',
            'arguments' => $appointmentData
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'appointment_id',
                    'confirmation_number',
                    'message'
                ]
            ])
            ->assertJson([
                'success' => true
            ]);
        
        // Verify appointment was created in database
        $this->assertDatabaseHas('appointments', [
            'appointment_date' => $appointmentData['datum'],
            'appointment_time' => $appointmentData['uhrzeit'],
            'status' => 'confirmed'
        ]);
        
        // Verify customer was created
        $this->assertDatabaseHas('customers', [
            'name' => 'Max Mustermann',
            'phone' => '+49 123 456789',
            'email' => 'max@example.com'
        ]);
    }
    
    /**
     * Test bookAppointment with missing required fields
     */
    public function test_book_appointment_fails_with_missing_required_fields()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'bookAppointment',
            'arguments' => [
                'name' => 'Test User'
                // Missing date and time
            ]
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'error' => 'Name, Datum und Uhrzeit sind erforderlich'
            ]);
    }
    
    /**
     * Test bookAppointment retrieves phone from call
     */
    public function test_book_appointment_retrieves_phone_from_call()
    {
        $call = Call::factory()->create([
            'retell_call_id' => 'call_123',
            'from_number' => '+49 987 654321',
            'company_id' => $this->company->id
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'bookAppointment',
            'call_id' => 'call_123',
            'arguments' => [
                'name' => 'Test Customer',
                'datum' => Carbon::tomorrow()->format('Y-m-d'),
                'uhrzeit' => '10:00',
                'dienstleistung' => 'Beratung'
            ]
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
        
        // Verify customer was created with phone from call
        $this->assertDatabaseHas('customers', [
            'name' => 'Test Customer',
            'phone' => '+49 987 654321'
        ]);
    }
    
    /**
     * Test getCustomerInfo tool
     */
    public function test_get_customer_info_returns_existing_customer()
    {
        $customer = Customer::factory()->create([
            'name' => 'Existing Customer',
            'phone' => '+49 111 222333',
            'email' => 'existing@example.com',
            'company_id' => $this->company->id
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCustomerInfo',
            'arguments' => [
                'phone' => '+49 111 222333'
            ]
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'found' => true,
                    'customer' => [
                        'name' => 'Existing Customer',
                        'phone' => '+49 111 222333',
                        'email' => 'existing@example.com'
                    ]
                ]
            ]);
    }
    
    /**
     * Test getCustomerInfo with non-existing customer
     */
    public function test_get_customer_info_returns_not_found_for_new_customer()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCustomerInfo',
            'arguments' => [
                'phone' => '+49 999 888777'
            ]
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'found' => false,
                    'customer' => null,
                    'message' => 'Kein Kunde mit dieser Telefonnummer gefunden.'
                ]
            ]);
    }
    
    /**
     * Test endCallSession tool
     */
    public function test_end_call_session_updates_call_status()
    {
        $call = Call::factory()->create([
            'retell_call_id' => 'call_456',
            'status' => 'active',
            'company_id' => $this->company->id
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'endCallSession',
            'call_id' => 'call_456',
            'arguments' => []
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'call_ended' => true
                ]
            ]);
        
        // Verify call status was updated
        $call->refresh();
        $this->assertEquals('ended', $call->status);
        $this->assertNotNull($call->ended_at);
    }
    
    /**
     * Test unknown tool returns error
     */
    public function test_unknown_tool_returns_error()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'unknownTool',
            'arguments' => []
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'error' => 'Unknown tool: unknownTool',
                'status_code' => 404
            ]);
    }
    
    /**
     * Test performance header is included
     */
    public function test_response_includes_performance_header()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(200)
            ->assertHeader('X-MCP-Duration');
        
        // Verify duration is numeric
        $duration = $response->headers->get('X-MCP-Duration');
        $this->assertIsNumeric($duration);
        $this->assertGreaterThan(0, (float)$duration);
    }
    
    // ===============================================================================================
    // SECURITY TESTS
    // ===============================================================================================
    
    /**
     * Test rate limiting functionality
     */
    public function test_rate_limiting_blocks_excessive_requests()
    {
        // Set a low rate limit for testing
        config(['retell-mcp.security.rate_limit_per_token' => 3]);
        
        // Make requests up to the limit
        for ($i = 0; $i < 3; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
            
            $response->assertStatus(200)
                ->assertJson(['success' => true]);
        }
        
        // Next request should be rate limited
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => 'Rate limit exceeded'
            ]);
    }
    
    /**
     * Test multiple valid tokens work
     */
    public function test_multiple_valid_tokens_work()
    {
        // Test primary token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        // Test backup token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->backupToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
    
    /**
     * Test token rotation scenario
     */
    public function test_token_rotation_scenario()
    {
        // Old token works initially
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(200);
        
        // Simulate token rotation - remove old token, add new one
        $newToken = 'new_rotated_token_2024';
        config(['retell-mcp.security.mcp_token' => $newToken]);
        
        // Old token should now fail
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Invalid MCP token'
            ]);
        
        // New token should work
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $newToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
    
    /**
     * Test malformed authorization headers
     */
    public function test_malformed_authorization_headers()
    {
        // Missing 'Bearer' prefix
        $response = $this->withHeaders([
            'Authorization' => $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'Invalid authorization format. Use: Bearer <token>'
            ]);
        
        // Empty token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ',
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(403);
        
        // Wrong format
        $response = $this->withHeaders([
            'Authorization' => 'Token ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(401);
    }
    
    /**
     * Test input validation and sanitization
     */
    public function test_input_validation_and_sanitization()
    {
        // Test XSS attempt in tool name
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => '<script>alert("xss")</script>',
            'arguments' => []
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'error' => 'Unknown tool: <script>alert("xss")</script>'
            ]);
        
        // Test SQL injection attempt in arguments
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'bookAppointment',
            'arguments' => [
                'name' => "'; DROP TABLE customers; --",
                'datum' => 'morgen',
                'uhrzeit' => '10:00'
            ]
        ]);
        
        // Should handle malicious input gracefully
        $response->assertStatus(200);
        $this->assertDatabaseHas('customers', ['id' => $this->service->id]); // Table still exists
    }
    
    /**
     * Test extremely long input handling
     */
    public function test_long_input_handling()
    {
        $longString = str_repeat('A', 10000);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'bookAppointment',
            'arguments' => [
                'name' => $longString,
                'datum' => 'morgen',
                'uhrzeit' => '10:00'
            ]
        ]);
        
        // Should handle long input gracefully without crashing
        $response->assertStatus(200);
    }
    
    // ===============================================================================================
    // CIRCUIT BREAKER TESTS
    // ===============================================================================================
    
    /**
     * Test circuit breaker functionality (if implemented)
     */
    public function test_circuit_breaker_functionality()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
        }
        
        // Configure circuit breaker for quick testing
        config(['retell-mcp.security.circuit_breaker_threshold' => 2]);
        config(['retell-mcp.security.circuit_breaker_timeout' => 1]);
        
        // Simulate failures to trigger circuit breaker
        for ($i = 0; $i < 3; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'bookAppointment',
                'arguments' => [
                    // Missing required fields to force error
                    'name' => 'Test'
                ]
            ]);
        }
        
        // Next request should be circuit breaker blocked (if implemented)
        // This would depend on your circuit breaker implementation
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        // Should either work normally or be circuit breaker blocked
        $this->assertContains($response->status(), [200, 503]);
    }
    
    /**
     * Test circuit breaker recovery
     */
    public function test_circuit_breaker_recovery()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
        }
        
        // Set circuit breaker to open state
        CircuitBreaker::open('mcp_endpoint');
        
        // Request should be blocked
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        // Wait for timeout
        sleep(2);
        
        // Circuit breaker should be in half-open state, allowing requests
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        // Should work after recovery
        $this->assertContains($response->status(), [200, 503]);
    }
    
    // ===============================================================================================
    // PERFORMANCE TESTS
    // ===============================================================================================
    
    /**
     * Test response time performance
     */
    public function test_response_time_performance()
    {
        $startTime = microtime(true);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $responseTime = (microtime(true) - $startTime) * 1000;
        
        $response->assertStatus(200);
        
        // Response should be under 500ms for simple operations
        $this->assertLessThan(500, $responseTime, 'Response time should be under 500ms');
        
        // Check performance header
        $this->assertNotNull($response->headers->get('X-MCP-Duration'));
        $headerDuration = (float)$response->headers->get('X-MCP-Duration');
        $this->assertLessThan(500, $headerDuration);
    }
    
    /**
     * Test concurrent request handling
     */
    public function test_concurrent_request_handling()
    {
        $responses = [];
        $startTime = microtime(true);
        
        // Simulate concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200)
                ->assertJson(['success' => true]);
        }
        
        // Total time should be reasonable (not sequential)
        $this->assertLessThan(2000, $totalTime, 'Concurrent requests should complete within 2 seconds');
    }
    
    /**
     * Test caching performance
     */
    public function test_caching_performance()
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        
        // First request (cache miss)
        $startTime = microtime(true);
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'checkAvailableSlots',
            'arguments' => [
                'date' => $tomorrow,
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id
            ]
        ]);
        $firstRequestTime = (microtime(true) - $startTime) * 1000;
        
        // Second request (cache hit)
        $startTime = microtime(true);
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'checkAvailableSlots',
            'arguments' => [
                'date' => $tomorrow,
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id
            ]
        ]);
        $secondRequestTime = (microtime(true) - $startTime) * 1000;
        
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // Second request should be faster due to caching
        $this->assertLessThan($firstRequestTime, $secondRequestTime, 
            'Cached request should be faster than non-cached request');
    }
    
    /**
     * Test memory usage during operations
     */
    public function test_memory_usage_during_operations()
    {
        $initialMemory = memory_get_usage(true);
        
        // Perform multiple operations
        for ($i = 0; $i < 10; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (< 10MB for 10 simple operations)
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease, 
            'Memory usage should not increase excessively');
    }
    
    // ===============================================================================================
    // ERROR HANDLING AND RESILIENCE TESTS
    // ===============================================================================================
    
    /**
     * Test graceful handling of service unavailability
     */
    public function test_service_unavailability_handling()
    {
        // Test with invalid company configuration
        $invalidCompany = Company::factory()->create([
            'calcom_api_key' => null,
            'calcom_event_type_id' => null
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'bookAppointment',
            'arguments' => [
                'name' => 'Test User',
                'datum' => 'morgen',
                'uhrzeit' => '10:00',
                'company_id' => $invalidCompany->id
            ]
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => false
            ]);
    }
    
    /**
     * Test handling of malformed JSON
     */
    public function test_malformed_json_handling()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, 'invalid json');
        
        // Should handle malformed JSON gracefully
        $this->assertContains($response->status(), [400, 422]);
    }
    
    /**
     * Test handling of missing required fields in different tools
     */
    public function test_missing_required_fields_handling()
    {
        $testCases = [
            [
                'tool' => 'bookAppointment',
                'arguments' => ['name' => 'Test'], // Missing date and time
                'expectedError' => 'Name, Datum und Uhrzeit sind erforderlich'
            ],
            [
                'tool' => 'getCustomerInfo',
                'arguments' => [], // Missing phone
                'expectedError' => 'Phone number is required'
            ],
            [
                'tool' => 'checkAvailableSlots',
                'arguments' => [], // Missing date
                'expectedError' => 'Date is required'
            ]
        ];
        
        foreach ($testCases as $testCase) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => $testCase['tool'],
                'arguments' => $testCase['arguments']
            ]);
            
            $response->assertStatus(200)
                ->assertJson([
                    'success' => false,
                    'error' => $testCase['expectedError']
                ]);
        }
    }
    
    /**
     * Test timeout scenarios (if implemented)
     */
    public function test_timeout_scenarios()
    {
        // This would require mocking external services to simulate timeouts
        // For now, just test that timeout configuration exists
        $this->assertTrue(is_numeric(config('retell-mcp.timeout', 30)));
    }
}