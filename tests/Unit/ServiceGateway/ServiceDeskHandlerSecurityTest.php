<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceGateway;

use App\Http\Controllers\ServiceDeskHandler;
use App\Models\Call;
use App\Services\ServiceDesk\ServiceDeskLockService;
use App\Services\Retell\CallLifecycleService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Unit Tests for H-001 (Cache Isolation) and H-002 (Authorization Guard)
 *
 * These tests verify the security fixes without database dependencies,
 * using mocks and reflection to test the pure logic.
 *
 * @package Tests\Unit\ServiceGateway
 * @since 2026-01-05
 */
class ServiceDeskHandlerSecurityTest extends TestCase
{
    private ServiceDeskHandler $handler;
    private CallLifecycleService $mockCallLifecycle;
    private ServiceDeskLockService $mockLockService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockCallLifecycle = Mockery::mock(CallLifecycleService::class);
        $this->mockLockService = Mockery::mock(ServiceDeskLockService::class);

        // Create handler with mocks
        $this->handler = new ServiceDeskHandler(
            $this->mockLockService,
            $this->mockCallLifecycle
        );

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // H-001: Cache Isolation Tests
    // =========================================================================

    /**
     * @test
     * @group security
     * @group h-001
     */
    public function cache_ttl_constant_is_300_seconds(): void
    {
        $reflection = new ReflectionClass(ServiceDeskHandler::class);
        $constant = $reflection->getConstant('CACHE_TTL_SECONDS');

        $this->assertEquals(300, $constant, 'Cache TTL should be 300 seconds (5 minutes)');
        $this->assertLessThan(3600, $constant, 'Cache TTL should be less than 1 hour (was 3600)');
    }

    /**
     * @test
     * @group security
     * @group h-001
     */
    public function build_cache_key_includes_company_id(): void
    {
        // Create a mock Call with company_id
        $mockCall = Mockery::mock(Call::class)->makePartial();
        $mockCall->company_id = 42;
        $mockCall->retell_call_id = 'call_test_123';

        $this->mockCallLifecycle
            ->shouldReceive('getCallContext')
            ->with('call_test_123')
            ->once()
            ->andReturn($mockCall);

        // Get the private method via reflection
        $method = new ReflectionMethod(ServiceDeskHandler::class, 'buildCacheKey');
        $method->setAccessible(true);

        $cacheKey = $method->invoke($this->handler, 'issue', 'call_test_123');

        // Verify company_id is included in the key
        $this->assertEquals('service_desk:42:issue:call_test_123', $cacheKey);
        $this->assertStringContainsString(':42:', $cacheKey, 'Cache key must contain company_id');
    }

    /**
     * @test
     * @group security
     * @group h-001
     */
    public function build_cache_key_accepts_explicit_company_id(): void
    {
        // No mock needed - company_id is provided directly
        $method = new ReflectionMethod(ServiceDeskHandler::class, 'buildCacheKey');
        $method->setAccessible(true);

        $cacheKey = $method->invoke($this->handler, 'category', 'call_456', 99);

        $this->assertEquals('service_desk:99:category:call_456', $cacheKey);
    }

    /**
     * @test
     * @group security
     * @group h-001
     */
    public function build_cache_key_falls_back_without_company_id(): void
    {
        // Mock call context without company_id
        $this->mockCallLifecycle
            ->shouldReceive('getCallContext')
            ->with('call_orphan')
            ->once()
            ->andReturn(null);

        $method = new ReflectionMethod(ServiceDeskHandler::class, 'buildCacheKey');
        $method->setAccessible(true);

        $cacheKey = $method->invoke($this->handler, 'issue', 'call_orphan');

        // Fallback format without company_id
        $this->assertEquals('service_desk:issue:call_orphan', $cacheKey);
    }

    /**
     * @test
     * @group security
     * @group h-001
     */
    public function cache_keys_are_tenant_isolated(): void
    {
        // Simulate two different companies
        $companyAId = 1;
        $companyBId = 2;
        $callId = 'call_shared_id';

        $keyA = "service_desk:{$companyAId}:issue:{$callId}";
        $keyB = "service_desk:{$companyBId}:issue:{$callId}";

        // Store data for both companies
        Cache::put($keyA, ['subject' => 'Company A Issue'], 300);
        Cache::put($keyB, ['subject' => 'Company B Issue'], 300);

        // Verify isolation
        $dataA = Cache::get($keyA);
        $dataB = Cache::get($keyB);

        $this->assertEquals('Company A Issue', $dataA['subject']);
        $this->assertEquals('Company B Issue', $dataB['subject']);

        // Different keys should not overlap
        $this->assertNotEquals($keyA, $keyB);
    }

    // =========================================================================
    // H-002: Authorization Guard Tests
    // =========================================================================

    /**
     * @test
     * @group security
     * @group h-002
     */
    public function validate_api_context_returns_null_for_missing_call_context(): void
    {
        $this->mockCallLifecycle
            ->shouldReceive('getCallContext')
            ->with('call_invalid')
            ->once()
            ->andReturn(null);

        $method = new ReflectionMethod(ServiceDeskHandler::class, 'validateApiContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, 'call_invalid', 'route_ticket');

        $this->assertNull($result, 'Should return null when call context is missing');
    }

    /**
     * @test
     * @group security
     * @group h-002
     */
    public function validate_api_context_returns_null_for_missing_company_id(): void
    {
        $mockCall = Mockery::mock(Call::class)->makePartial();
        $mockCall->company_id = null; // Missing company_id
        $mockCall->retell_call_id = 'call_orphan';

        $this->mockCallLifecycle
            ->shouldReceive('getCallContext')
            ->with('call_orphan')
            ->once()
            ->andReturn($mockCall);

        $method = new ReflectionMethod(ServiceDeskHandler::class, 'validateApiContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, 'call_orphan', 'route_ticket');

        $this->assertNull($result, 'Should return null when company_id is missing');
    }

    /**
     * @test
     * @group security
     * @group h-002
     */
    public function validate_api_context_returns_context_for_valid_call(): void
    {
        $mockCall = Mockery::mock(Call::class)->makePartial();
        $mockCall->company_id = 42;
        $mockCall->retell_call_id = 'call_valid';

        $this->mockCallLifecycle
            ->shouldReceive('getCallContext')
            ->with('call_valid')
            ->once()
            ->andReturn($mockCall);

        $method = new ReflectionMethod(ServiceDeskHandler::class, 'validateApiContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, 'call_valid', 'route_ticket');

        $this->assertNotNull($result, 'Should return context for valid call');
        $this->assertEquals(42, $result->company_id);
    }

    /**
     * @test
     * @group security
     * @group h-002
     */
    public function validate_api_context_rejects_cancelled_calls(): void
    {
        $mockCall = Mockery::mock(Call::class)->makePartial();
        $mockCall->company_id = 42;
        $mockCall->retell_call_id = 'call_cancelled';
        $mockCall->status = 'cancelled';

        $this->mockCallLifecycle
            ->shouldReceive('getCallContext')
            ->with('call_cancelled')
            ->once()
            ->andReturn($mockCall);

        $method = new ReflectionMethod(ServiceDeskHandler::class, 'validateApiContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, 'call_cancelled', 'route_ticket');

        $this->assertNull($result, 'Should return null for cancelled calls');
    }

    /**
     * @test
     * @group security
     * @group h-002
     */
    public function validate_api_context_rejects_rejected_calls(): void
    {
        $mockCall = Mockery::mock(Call::class)->makePartial();
        $mockCall->company_id = 42;
        $mockCall->retell_call_id = 'call_rejected';
        $mockCall->status = 'rejected';

        $this->mockCallLifecycle
            ->shouldReceive('getCallContext')
            ->with('call_rejected')
            ->once()
            ->andReturn($mockCall);

        $method = new ReflectionMethod(ServiceDeskHandler::class, 'validateApiContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, 'call_rejected', 'finalize_ticket');

        $this->assertNull($result, 'Should return null for rejected calls');
    }

    /**
     * @test
     * @group security
     * @group h-002
     */
    public function validate_api_context_allows_ongoing_calls(): void
    {
        $mockCall = Mockery::mock(Call::class)->makePartial();
        $mockCall->company_id = 42;
        $mockCall->retell_call_id = 'call_ongoing';
        $mockCall->status = 'ongoing';

        $this->mockCallLifecycle
            ->shouldReceive('getCallContext')
            ->with('call_ongoing')
            ->once()
            ->andReturn($mockCall);

        $method = new ReflectionMethod(ServiceDeskHandler::class, 'validateApiContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, 'call_ongoing', 'route_ticket');

        $this->assertNotNull($result, 'Should allow ongoing calls');
        $this->assertEquals('ongoing', $result->status);
    }

    // =========================================================================
    // Integration-like Tests (handle() method)
    // =========================================================================

    /**
     * @test
     * @group security
     * @group h-002
     */
    public function handle_checks_authorization_for_critical_operations(): void
    {
        // Mock missing call context for route_ticket
        $this->mockCallLifecycle
            ->shouldReceive('getCallContext')
            ->with('call_unauthorized')
            ->andReturn(null);

        $response = $this->handler->handle('route_ticket', [], 'call_unauthorized');

        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('H-002', $responseData['error']);
    }

    /**
     * @test
     * @group security
     * @group h-002
     */
    public function handle_checks_authorization_for_finalize_ticket(): void
    {
        // Mock missing call context for finalize_ticket
        $this->mockCallLifecycle
            ->shouldReceive('getCallContext')
            ->with('call_unauthorized_finalize')
            ->andReturn(null);

        $response = $this->handler->handle('finalize_ticket', [], 'call_unauthorized_finalize');

        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('H-002', $responseData['error']);
    }

    /**
     * @test
     * @group security
     * @group h-002
     */
    public function handle_skips_authorization_for_non_critical_operations(): void
    {
        // For detect_intent, authorization is not required
        // The mock should not be called for authorization check
        $this->mockCallLifecycle
            ->shouldReceive('getCallContext')
            ->with('call_detect')
            ->andReturn(null);

        // This should NOT return 403 because detect_intent is not in criticalOperations
        $response = $this->handler->handle('detect_intent', [], 'call_detect');

        // detect_intent might fail for other reasons, but NOT with H-002 error
        $responseData = json_decode($response->getContent(), true);
        if ($response->getStatusCode() === 403) {
            $this->assertStringNotContainsString('H-002', $responseData['error'] ?? '');
        }
    }
}
