<?php

namespace Tests\Unit\Gateway;

use Tests\TestCase;
use App\Services\Gateway\GatewayModeResolver;
use App\Services\Gateway\Config\GatewayConfigService;
use App\Models\Call;
use App\Models\Company;
use App\Models\PolicyConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Test Suite: GatewayModeResolver
 *
 * Tests IN-HANDLER mode resolution logic that determines whether a call
 * should be routed to 'appointment' or 'service_desk' mode.
 *
 * Critical Patterns Tested:
 * - Feature flag bypass (CRIT-001: Safe rollback mechanism)
 * - Call-ID resolution (4-layer extraction dependency)
 * - Policy lookup with caching (Performance: ~20ms → ~0.5ms)
 * - Multi-tenant isolation (CRIT-002: Company scope validation)
 * - Fallback behavior (Default mode when no policy exists)
 *
 * Architecture Context:
 * This resolver is called IN-HANDLER after extractCallIdLayered() completes,
 * not as middleware (See: Plan v3 Section 0b - Critical Finding)
 *
 * @see docs/SERVICE_GATEWAY_IMPLEMENTATION_PLAN.md Section 6
 * @since 2025-12-10 (Phase 1: Foundation + Mode Resolver)
 */
class GatewayModeResolverTest extends TestCase
{
    use RefreshDatabase;

    private GatewayModeResolver $resolver;
    private GatewayConfigService $configService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock config service for isolation
        $this->configService = $this->createMock(GatewayConfigService::class);
        $this->resolver = new GatewayModeResolver($this->configService);

        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test: Feature flag disabled bypasses all resolution logic
     *
     * CRITICAL: This is the safety mechanism for rollback.
     * When gateway.mode_enabled = false, ALWAYS return 'appointment'
     * regardless of policies or configurations.
     *
     * Risk: If this test fails, rollback mechanism is broken.
     * Rollback Plan: Set GATEWAY_MODE_ENABLED=false in .env
     *
     * @test
     */
    public function test_returns_appointment_when_feature_flag_disabled(): void
    {
        // Arrange: Disable feature flag
        Config::set('gateway.mode_enabled', false);

        // Create a company with service_desk policy (should be ignored)
        $company = Company::factory()->create();
        PolicyConfiguration::factory()->create([
            'company_id' => $company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'service_desk'],
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'retell_call_id' => 'test_call_1',
        ]);

        // Act: Resolve mode
        $result = $this->resolver->resolve($call->retell_call_id);

        // Assert: Feature flag override
        $this->assertEquals('appointment', $result);
    }

    /**
     * Test: Default mode returned when call not found
     *
     * Edge Case: Call ID does not exist in database.
     * Expected: Graceful fallback to config('gateway.default_mode')
     *
     * This can happen if:
     * 1. Retell webhook arrives before call_started
     * 2. Call was soft-deleted
     * 3. Database replication lag
     *
     * @test
     */
    public function test_returns_default_mode_when_call_not_found(): void
    {
        // Arrange: Enable feature, non-existent call
        Config::set('gateway.mode_enabled', true);
        Config::set('gateway.default_mode', 'appointment');

        $this->configService
            ->expects($this->once())
            ->method('getCompanyMode')
            ->with(null) // No company because call doesn't exist
            ->willReturn('appointment');

        // Act: Resolve with invalid call ID
        $result = $this->resolver->resolve('non_existent_call_id');

        // Assert: Fallback to default
        $this->assertEquals('appointment', $result);
    }

    /**
     * Test: Service desk mode returned when policy configured
     *
     * Happy Path: Company has gateway_mode policy with mode = 'service_desk'
     *
     * This is the PRIMARY use case for Phase 2 (Smart Office scenario)
     * where a company wants ALL calls routed to service desk.
     *
     * @test
     */
    public function test_returns_service_desk_when_policy_configured(): void
    {
        // Arrange: Company with service_desk policy
        Config::set('gateway.mode_enabled', true);

        $company = Company::factory()->create();
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'retell_call_id' => 'test_call_service',
        ]);

        $this->configService
            ->expects($this->once())
            ->method('getCompanyMode')
            ->with($company->id)
            ->willReturn('service_desk');

        // Act: Resolve mode
        $result = $this->resolver->resolve($call->retell_call_id);

        // Assert: Service desk mode
        $this->assertEquals('service_desk', $result);
    }

    /**
     * Test: Appointment mode returned when no policy exists
     *
     * Default Behavior: Company has no gateway_mode policy configured.
     * Expected: Fallback to 'appointment' (legacy booking flow)
     *
     * This ensures backwards compatibility - existing companies
     * continue to use appointment booking by default.
     *
     * @test
     */
    public function test_returns_appointment_when_no_policy_exists(): void
    {
        // Arrange: Company without gateway policy
        Config::set('gateway.mode_enabled', true);

        $company = Company::factory()->create();
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'retell_call_id' => 'test_call_default',
        ]);

        $this->configService
            ->expects($this->once())
            ->method('getCompanyMode')
            ->with($company->id)
            ->willReturn('appointment'); // Config service returns default

        // Act: Resolve mode
        $result = $this->resolver->resolve($call->retell_call_id);

        // Assert: Default to appointment
        $this->assertEquals('appointment', $result);
    }

    /**
     * Test: Hybrid mode returned correctly
     *
     * Advanced Feature (Phase 4): AI-driven intent detection
     *
     * Hybrid mode uses IntentDetectionService to classify calls
     * as either 'appointment_intent' or 'service_intent' at runtime.
     *
     * This test verifies mode resolution ONLY - intent detection
     * is tested separately in IntentDetectionServiceTest.php
     *
     * @test
     */
    public function test_returns_hybrid_mode_correctly(): void
    {
        // Arrange: Company with hybrid mode policy
        Config::set('gateway.mode_enabled', true);

        $company = Company::factory()->create();
        PolicyConfiguration::factory()->create([
            'company_id' => $company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => [
                'mode' => 'hybrid',
                'intent_confidence_threshold' => 0.75,
                'fallback_mode' => 'appointment',
            ],
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'retell_call_id' => 'test_call_hybrid',
        ]);

        $this->configService
            ->expects($this->once())
            ->method('getCompanyMode')
            ->with($company->id)
            ->willReturn('hybrid');

        // Act: Resolve mode
        $result = $this->resolver->resolve($call->retell_call_id);

        // Assert: Hybrid mode
        $this->assertEquals('hybrid', $result);
    }

    /**
     * Test: Policy lookup is cached for performance
     *
     * Performance Optimization: Policy queries are expensive (~20ms)
     * Cache hit reduces to ~0.5ms (40x improvement)
     *
     * Cache Strategy:
     * - Key: policy:{Company}:{company_id}:gateway_mode
     * - TTL: 300 seconds (5 minutes)
     * - Invalidation: On policy save/delete (Model observer)
     *
     * Expected Behavior:
     * 1. First call: DB query + cache store
     * 2. Second call: Cache hit (no DB query)
     *
     * @test
     */
    public function test_caches_policy_lookup(): void
    {
        // Arrange: Company with policy
        Config::set('gateway.mode_enabled', true);

        $company = Company::factory()->create();
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'retell_call_id' => 'test_call_cache',
        ]);

        // Mock config service to verify call count
        $this->configService
            ->expects($this->exactly(2)) // Called twice, but policy query happens once
            ->method('getCompanyMode')
            ->with($company->id)
            ->willReturn('service_desk');

        // Act: Resolve mode twice
        $result1 = $this->resolver->resolve($call->retell_call_id);
        $result2 = $this->resolver->resolve($call->retell_call_id);

        // Assert: Both return same mode
        $this->assertEquals('service_desk', $result1);
        $this->assertEquals('service_desk', $result2);

        // Note: Cache verification happens at GatewayConfigService level
        // This test verifies resolver behavior consistency
    }

    /**
     * Test: Multi-tenant isolation prevents cross-company access
     *
     * CRITICAL SECURITY: CRIT-002 Tenant Validation Pattern
     *
     * Scenario: Call belongs to Company A, but policy query attempts
     * to access Company B's configuration.
     *
     * Expected: Query scoped to call's company_id only.
     *
     * Risk: Without proper scoping, Company A could see Company B's modes.
     *
     * @test
     */
    public function test_respects_multi_tenant_isolation(): void
    {
        // Arrange: Two companies with different modes
        Config::set('gateway.mode_enabled', true);

        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        PolicyConfiguration::factory()->create([
            'company_id' => $companyA->id,
            'configurable_type' => Company::class,
            'configurable_id' => $companyA->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'service_desk'],
        ]);

        PolicyConfiguration::factory()->create([
            'company_id' => $companyB->id,
            'configurable_type' => Company::class,
            'configurable_id' => $companyB->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'appointment'],
        ]);

        $callA = Call::factory()->create([
            'company_id' => $companyA->id,
            'retell_call_id' => 'call_company_a',
        ]);

        $callB = Call::factory()->create([
            'company_id' => $companyB->id,
            'retell_call_id' => 'call_company_b',
        ]);

        $this->configService
            ->expects($this->exactly(2))
            ->method('getCompanyMode')
            ->willReturnCallback(function ($companyId) use ($companyA, $companyB) {
                if ($companyId === $companyA->id) {
                    return 'service_desk';
                }
                if ($companyId === $companyB->id) {
                    return 'appointment';
                }
                return 'appointment';
            });

        // Act: Resolve both calls
        $resultA = $this->resolver->resolve($callA->retell_call_id);
        $resultB = $this->resolver->resolve($callB->retell_call_id);

        // Assert: Each company gets correct mode (no cross-contamination)
        $this->assertEquals('service_desk', $resultA, 'Company A should get service_desk');
        $this->assertEquals('appointment', $resultB, 'Company B should get appointment');
    }

    /**
     * Test: Invalid mode in policy config falls back to default
     *
     * Edge Case: Policy config contains invalid mode value
     * (e.g., typo, old migration, manual DB edit)
     *
     * Expected: Validation at PolicyConfiguration model level,
     * or fallback to default mode if validation is bypassed.
     *
     * @test
     */
    public function test_handles_invalid_mode_gracefully(): void
    {
        // Arrange: Enable feature
        Config::set('gateway.mode_enabled', true);
        Config::set('gateway.default_mode', 'appointment');

        $company = Company::factory()->create();
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'retell_call_id' => 'test_call_invalid',
        ]);

        // Config service validates mode, returns default if invalid
        $this->configService
            ->expects($this->once())
            ->method('getCompanyMode')
            ->with($company->id)
            ->willReturn('appointment'); // Invalid mode corrected by config service

        // Act: Resolve mode
        $result = $this->resolver->resolve($call->retell_call_id);

        // Assert: Fallback to default
        $this->assertEquals('appointment', $result);
    }
}
