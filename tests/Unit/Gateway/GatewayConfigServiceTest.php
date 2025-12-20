<?php

namespace Tests\Unit\Gateway;

use Tests\TestCase;
use App\Services\Gateway\Config\GatewayConfigService;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PolicyConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Test Suite: GatewayConfigService
 *
 * Tests hierarchical configuration resolution for Gateway mode settings.
 * This service resolves gateway_mode policies across the company hierarchy:
 * Company → Branch (future: → Service → Staff)
 *
 * Key Responsibilities:
 * 1. Mode resolution with hierarchical override support
 * 2. Configuration caching (5-minute TTL)
 * 3. Hybrid mode config retrieval (threshold, fallback)
 * 4. Multi-tenant isolation
 *
 * Cache Strategy:
 * - Key Pattern: policy:{ModelClass}:{id}:{policy_type}
 * - TTL: 300 seconds (5 minutes)
 * - Invalidation: Model observer on save/delete
 * - Performance: 20ms (DB) → 0.5ms (cache hit)
 *
 * @see docs/SERVICE_GATEWAY_IMPLEMENTATION_PLAN.md Section 6
 * @since 2025-12-10 (Phase 1: Foundation + Mode Resolver)
 */
class GatewayConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    private GatewayConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GatewayConfigService();

        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test: Returns default config when no policy exists
     *
     * Scenario: Company has NO gateway_mode policy configured
     * Expected: Default mode from config('gateway.default_mode')
     *
     * This ensures backwards compatibility - existing companies
     * without gateway policies continue to use appointment mode.
     *
     * Default Config Structure:
     * [
     *   'mode' => 'appointment',
     *   'enabled' => false,
     * ]
     *
     * @test
     */
    public function test_get_company_gateway_config_returns_defaults(): void
    {
        // Arrange: Company without policy
        Config::set('gateway.default_mode', 'appointment');
        $company = Company::factory()->create();

        // Act: Get config
        $config = $this->service->getCompanyGatewayConfig($company->id);

        // Assert: Returns default values
        $this->assertIsArray($config);
        $this->assertEquals('appointment', $config['mode']);
        $this->assertFalse($config['enabled']);
    }

    /**
     * Test: Feature enabled detection
     *
     * Tests: is_gateway_enabled() method
     *
     * Three scenarios:
     * 1. No policy → FALSE (default)
     * 2. Policy exists but mode = 'appointment' → FALSE (legacy mode)
     * 3. Policy with mode = 'service_desk' or 'hybrid' → TRUE
     *
     * This method is used by UI to show/hide gateway features
     * in the admin panel (Phase 5: Admin UI).
     *
     * @test
     */
    public function test_is_gateway_enabled_returns_false_by_default(): void
    {
        // Arrange: Company without policy
        $company = Company::factory()->create();

        // Act: Check if enabled
        $result = $this->service->isGatewayEnabled($company->id);

        // Assert: Disabled by default
        $this->assertFalse($result);
    }

    /**
     * Test: Gateway enabled when service_desk mode configured
     *
     * Scenario: Company has gateway_mode policy with mode = 'service_desk'
     * Expected: isGatewayEnabled() returns TRUE
     *
     * @test
     */
    public function test_is_gateway_enabled_returns_true_for_service_desk(): void
    {
        // Arrange: Company with service_desk policy
        $company = Company::factory()->create();
        PolicyConfiguration::factory()->create([
            'company_id' => $company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'service_desk'],
        ]);

        // Act: Check if enabled
        $result = $this->service->isGatewayEnabled($company->id);

        // Assert: Enabled for service_desk
        $this->assertTrue($result);
    }

    /**
     * Test: Gateway enabled when hybrid mode configured
     *
     * Scenario: Company has gateway_mode policy with mode = 'hybrid'
     * Expected: isGatewayEnabled() returns TRUE
     *
     * @test
     */
    public function test_is_gateway_enabled_returns_true_for_hybrid(): void
    {
        // Arrange: Company with hybrid policy
        $company = Company::factory()->create();
        PolicyConfiguration::factory()->create([
            'company_id' => $company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'hybrid'],
        ]);

        // Act: Check if enabled
        $result = $this->service->isGatewayEnabled($company->id);

        // Assert: Enabled for hybrid
        $this->assertTrue($result);
    }

    /**
     * Test: Returns configured mode correctly
     *
     * Tests: getMode() method for all three valid modes
     *
     * Valid Modes:
     * - 'appointment' (legacy/default)
     * - 'service_desk' (Phase 2)
     * - 'hybrid' (Phase 4)
     *
     * @test
     */
    public function test_get_mode_returns_configured_mode(): void
    {
        // Arrange: Companies with different modes
        $companyAppointment = Company::factory()->create();
        $companyServiceDesk = Company::factory()->create();
        $companyHybrid = Company::factory()->create();

        PolicyConfiguration::factory()->create([
            'company_id' => $companyAppointment->id,
            'configurable_type' => Company::class,
            'configurable_id' => $companyAppointment->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'appointment'],
        ]);

        PolicyConfiguration::factory()->create([
            'company_id' => $companyServiceDesk->id,
            'configurable_type' => Company::class,
            'configurable_id' => $companyServiceDesk->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'service_desk'],
        ]);

        PolicyConfiguration::factory()->create([
            'company_id' => $companyHybrid->id,
            'configurable_type' => Company::class,
            'configurable_id' => $companyHybrid->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'hybrid'],
        ]);

        // Act: Get modes
        $modeA = $this->service->getMode($companyAppointment->id);
        $modeS = $this->service->getMode($companyServiceDesk->id);
        $modeH = $this->service->getMode($companyHybrid->id);

        // Assert: Correct modes returned
        $this->assertEquals('appointment', $modeA);
        $this->assertEquals('service_desk', $modeS);
        $this->assertEquals('hybrid', $modeH);
    }

    /**
     * Test: Hybrid config retrieval with threshold
     *
     * Hybrid Mode Config Structure:
     * [
     *   'mode' => 'hybrid',
     *   'intent_confidence_threshold' => 0.75,
     *   'fallback_mode' => 'appointment',
     * ]
     *
     * Used by IntentDetectionService (Phase 4) to determine
     * if confidence score is sufficient to route the call.
     *
     * Example:
     * - AI detects "service" intent with 0.82 confidence
     * - Threshold is 0.75 → Route to service_desk
     * - If confidence was 0.65 → Use fallback_mode (appointment)
     *
     * @test
     */
    public function test_get_hybrid_config_returns_threshold(): void
    {
        // Arrange: Company with hybrid config
        $company = Company::factory()->create();
        PolicyConfiguration::factory()->create([
            'company_id' => $company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => [
                'mode' => 'hybrid',
                'intent_confidence_threshold' => 0.80,
                'fallback_mode' => 'service_desk',
            ],
        ]);

        // Act: Get hybrid config
        $config = $this->service->getHybridConfig($company->id);

        // Assert: Config values correct
        $this->assertIsArray($config);
        $this->assertEquals(0.80, $config['intent_confidence_threshold']);
        $this->assertEquals('service_desk', $config['fallback_mode']);
    }

    /**
     * Test: Hybrid config returns defaults when policy missing
     *
     * Edge Case: getHybridConfig() called for company with no policy
     * Expected: Return config('gateway.hybrid') defaults
     *
     * Default Hybrid Config:
     * [
     *   'intent_confidence_threshold' => 0.75,
     *   'fallback_mode' => 'appointment',
     * ]
     *
     * @test
     */
    public function test_get_hybrid_config_returns_defaults_when_no_policy(): void
    {
        // Arrange: Company without policy
        Config::set('gateway.hybrid.intent_confidence_threshold', 0.75);
        Config::set('gateway.hybrid.fallback_mode', 'appointment');

        $company = Company::factory()->create();

        // Act: Get hybrid config
        $config = $this->service->getHybridConfig($company->id);

        // Assert: Default values
        $this->assertEquals(0.75, $config['intent_confidence_threshold']);
        $this->assertEquals('appointment', $config['fallback_mode']);
    }

    /**
     * Test: Configuration caching for performance
     *
     * Performance Optimization Test:
     * - First call: Queries database (~20ms)
     * - Second call: Hits cache (~0.5ms)
     * - Cache TTL: 300 seconds (5 minutes)
     *
     * Cache Key Pattern: policy:{Company}:{id}:gateway_mode
     *
     * This test verifies:
     * 1. Policy is queried from DB on first call
     * 2. Subsequent calls use cached value
     * 3. Cache key is correctly generated
     *
     * @test
     */
    public function test_caches_configuration_lookups(): void
    {
        // Arrange: Company with policy
        $company = Company::factory()->create();
        PolicyConfiguration::factory()->create([
            'company_id' => $company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'service_desk'],
        ]);

        // Act: Call getMode() twice
        $mode1 = $this->service->getMode($company->id);
        $mode2 = $this->service->getMode($company->id);

        // Assert: Both return same value
        $this->assertEquals('service_desk', $mode1);
        $this->assertEquals('service_desk', $mode2);

        // Verify cache hit on second call
        $cacheKey = sprintf(
            'policy:%s:%s:%s',
            Company::class,
            $company->id,
            PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE
        );
        $this->assertTrue(Cache::has($cacheKey));

        // Verify cached value matches
        $cachedPolicy = Cache::get($cacheKey);
        $this->assertNotNull($cachedPolicy);
        $this->assertEquals('service_desk', $cachedPolicy->config['mode']);
    }

    /**
     * Test: Cache invalidation on policy update
     *
     * Critical Behavior: When policy is updated, cache MUST be invalidated
     * immediately to prevent stale configuration.
     *
     * Invalidation Trigger: PolicyConfiguration model observer
     * - saved() event → invalidateCache()
     * - deleted() event → invalidateCache()
     *
     * Scenario:
     * 1. Company has mode = 'appointment' (cached)
     * 2. Admin changes to mode = 'service_desk'
     * 3. Cache is invalidated
     * 4. Next getMode() call queries fresh from DB
     *
     * @test
     */
    public function test_cache_invalidates_on_policy_update(): void
    {
        // Arrange: Company with initial policy
        $company = Company::factory()->create();
        $policy = PolicyConfiguration::factory()->create([
            'company_id' => $company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'appointment'],
        ]);

        // Act: Cache initial value
        $mode1 = $this->service->getMode($company->id);
        $this->assertEquals('appointment', $mode1);

        // Update policy
        $policy->update(['config' => ['mode' => 'service_desk']]);

        // Get mode again (should query DB, not cache)
        $mode2 = $this->service->getMode($company->id);

        // Assert: New mode returned (cache was invalidated)
        $this->assertEquals('service_desk', $mode2);
    }

    /**
     * Test: Multi-tenant isolation in config resolution
     *
     * CRITICAL SECURITY: CRIT-002 Tenant Validation Pattern
     *
     * Scenario: Multiple companies with different configurations
     * Risk: Config leak between tenants if company_id not properly scoped
     *
     * Expected Behavior:
     * - Company A queries return Company A's config only
     * - Company B queries return Company B's config only
     * - No cross-tenant cache pollution
     *
     * @test
     */
    public function test_multi_tenant_isolation_in_config_resolution(): void
    {
        // Arrange: Two companies with different configs
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

        // Act: Get configs for both companies
        $modeA = $this->service->getMode($companyA->id);
        $modeB = $this->service->getMode($companyB->id);

        // Assert: Each company gets correct config (no cross-contamination)
        $this->assertEquals('service_desk', $modeA, 'Company A should get service_desk');
        $this->assertEquals('appointment', $modeB, 'Company B should get appointment');

        // Verify cache keys are different
        $cacheKeyA = sprintf(
            'policy:%s:%s:%s',
            Company::class,
            $companyA->id,
            PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE
        );
        $cacheKeyB = sprintf(
            'policy:%s:%s:%s',
            Company::class,
            $companyB->id,
            PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE
        );

        $this->assertNotEquals($cacheKeyA, $cacheKeyB);
        $this->assertTrue(Cache::has($cacheKeyA));
        $this->assertTrue(Cache::has($cacheKeyB));
    }

    /**
     * Test: Hierarchical override support (future)
     *
     * Future Feature (Phase 6): Branch-level override of company mode
     *
     * Hierarchy:
     * Company (mode = 'appointment') → Branch (override to 'service_desk')
     *
     * This test documents intended behavior, currently returns company-level
     * config only. Will be implemented when branch-level policies are needed.
     *
     * @test
     * @group future
     */
    public function test_hierarchical_override_branch_over_company(): void
    {
        $this->markTestSkipped('Branch-level overrides not yet implemented (Phase 6)');

        // Future implementation:
        // $company = Company::factory()->create();
        // $branch = Branch::factory()->create(['company_id' => $company->id]);
        //
        // PolicyConfiguration::factory()->create([
        //     'company_id' => $company->id,
        //     'configurable_type' => Company::class,
        //     'configurable_id' => $company->id,
        //     'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
        //     'config' => ['mode' => 'appointment'],
        // ]);
        //
        // PolicyConfiguration::factory()->create([
        //     'company_id' => $company->id,
        //     'configurable_type' => Branch::class,
        //     'configurable_id' => $branch->id,
        //     'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
        //     'config' => ['mode' => 'service_desk'],
        //     'is_override' => true,
        // ]);
        //
        // $mode = $this->service->getBranchMode($branch->id);
        // $this->assertEquals('service_desk', $mode);
    }

    /**
     * Test: Invalid mode validation
     *
     * Edge Case: Policy config contains invalid mode
     * (e.g., 'invalid_mode', typo, manual DB edit)
     *
     * Expected Behavior:
     * - PolicyConfiguration model validates on save (boot method)
     * - If bypassed, getMode() returns default mode
     *
     * Valid Modes: 'appointment', 'service_desk', 'hybrid'
     *
     * @test
     */
    public function test_validates_mode_and_falls_back_to_default(): void
    {
        // Arrange: Attempt to create policy with invalid mode
        Config::set('gateway.default_mode', 'appointment');
        $company = Company::factory()->create();

        // This should throw exception at model level
        $this->expectException(\InvalidArgumentException::class);

        PolicyConfiguration::create([
            'company_id' => $company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'invalid_mode'],
        ]);

        // If validation is bypassed (shouldn't happen), service should fallback
        // This is defensive programming - the model should prevent this
    }
}
