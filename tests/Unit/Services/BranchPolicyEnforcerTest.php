<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Branch;
use App\Models\Call;
use App\Models\PolicyConfiguration;
use App\Services\Policy\BranchPolicyEnforcer;
use App\ValueObjects\AnonymousCallDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BranchPolicyEnforcer Test Suite
 *
 * Tests policy enforcement logic including:
 * - Anonymous caller restrictions (hard-coded security rules)
 * - Branch-level policy configuration
 * - Time-based restrictions
 * - Default permissive behavior
 */
class BranchPolicyEnforcerTest extends TestCase
{
    use RefreshDatabase;

    private BranchPolicyEnforcer $enforcer;
    private Branch $branch;
    private Call $regularCall;
    private Call $anonymousCall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enforcer = app(BranchPolicyEnforcer::class);

        // Create test branch
        $this->branch = Branch::factory()->create();

        // Create regular call (with phone number)
        $this->regularCall = Call::factory()->create([
            'from_number' => '+4915112345678',
            'branch_id' => $this->branch->id,
            'company_id' => $this->branch->company_id,
        ]);

        // Create anonymous call
        $this->anonymousCall = Call::factory()->create([
            'from_number' => 'anonymous',
            'branch_id' => $this->branch->id,
            'company_id' => $this->branch->company_id,
        ]);
    }

    /**
     * Test: Anonymous callers CANNOT reschedule appointments
     */
    public function test_anonymous_caller_cannot_reschedule()
    {
        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->anonymousCall,
            'reschedule'
        );

        $this->assertFalse($result['allowed']);
        $this->assertEquals('anonymous_caller_restricted', $result['reason']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test: Anonymous callers CANNOT cancel appointments
     */
    public function test_anonymous_caller_cannot_cancel()
    {
        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->anonymousCall,
            'cancel'
        );

        $this->assertFalse($result['allowed']);
        $this->assertEquals('anonymous_caller_restricted', $result['reason']);
    }

    /**
     * Test: Anonymous callers CANNOT query appointments
     */
    public function test_anonymous_caller_cannot_query_appointments()
    {
        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->anonymousCall,
            'appointment_inquiry'
        );

        $this->assertFalse($result['allowed']);
        $this->assertEquals('anonymous_caller_restricted', $result['reason']);
    }

    /**
     * Test: Anonymous callers CAN book appointments
     */
    public function test_anonymous_caller_can_book()
    {
        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->anonymousCall,
            'booking'
        );

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test: Anonymous callers CAN check availability
     */
    public function test_anonymous_caller_can_check_availability()
    {
        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->anonymousCall,
            'availability_inquiry'
        );

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test: Anonymous callers CAN get service information
     */
    public function test_anonymous_caller_can_get_service_info()
    {
        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->anonymousCall,
            'service_info'
        );

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test: Anonymous callers CAN get opening hours
     */
    public function test_anonymous_caller_can_get_opening_hours()
    {
        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->anonymousCall,
            'opening_hours'
        );

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test: Anonymous callers CAN request callback
     */
    public function test_anonymous_caller_can_request_callback()
    {
        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->anonymousCall,
            'callback'
        );

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test: Regular callers pass all security checks
     */
    public function test_regular_caller_passes_all_operations()
    {
        $operations = [
            'booking', 'reschedule', 'cancel', 'appointment_inquiry',
            'availability_inquiry', 'service_info', 'opening_hours', 'callback'
        ];

        foreach ($operations as $operation) {
            $result = $this->enforcer->isOperationAllowed(
                $this->branch,
                $this->regularCall,
                $operation
            );

            $this->assertTrue(
                $result['allowed'],
                "Regular caller should be allowed to: {$operation}"
            );
        }
    }

    /**
     * Test: Policy can disable operations (enabled: false)
     */
    public function test_policy_can_disable_operation()
    {
        // Create policy that disables booking
        PolicyConfiguration::create([
            'company_id' => $this->branch->company_id,
            'configurable_type' => Branch::class,
            'configurable_id' => $this->branch->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_BOOKING,
            'config' => [
                'enabled' => false,
                'disabled_message' => 'Buchungen sind derzeit nicht möglich.',
            ],
        ]);

        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->regularCall,
            'booking'
        );

        $this->assertFalse($result['allowed']);
        $this->assertEquals('policy_disabled', $result['reason']);
        $this->assertStringContainsString('nicht möglich', $result['message']);
    }

    /**
     * Test: Default behavior is permissive (no policy = allowed)
     */
    public function test_no_policy_defaults_to_allow()
    {
        // No policy configured
        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->regularCall,
            'booking'
        );

        $this->assertTrue($result['allowed']);
        $this->assertEquals('no_policy_default_allow', $result['reason']);
    }

    /**
     * Test: Time restrictions work correctly
     */
    public function test_time_restrictions()
    {
        $now = now('Europe/Berlin');
        $dayOfWeek = strtolower($now->format('l'));

        // Create policy with time restrictions (allow only 09:00-17:00)
        PolicyConfiguration::create([
            'company_id' => $this->branch->company_id,
            'configurable_type' => Branch::class,
            'configurable_id' => $this->branch->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_BOOKING,
            'config' => [
                'enabled' => true,
                'allowed_hours' => [
                    $dayOfWeek => ['09:00-17:00'],
                ],
            ],
        ]);

        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->regularCall,
            'booking'
        );

        // Result depends on current time
        // If outside 09:00-17:00, should be denied
        $currentTime = $now->format('H:i');
        if ($currentTime < '09:00' || $currentTime > '17:00') {
            $this->assertFalse($result['allowed']);
            $this->assertEquals('outside_allowed_hours', $result['reason']);
        } else {
            $this->assertTrue($result['allowed']);
        }
    }

    /**
     * Test: Policy caching works
     */
    public function test_policy_caching()
    {
        // Create policy
        $policy = PolicyConfiguration::create([
            'company_id' => $this->branch->company_id,
            'configurable_type' => Branch::class,
            'configurable_id' => $this->branch->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_BOOKING,
            'config' => ['enabled' => true],
        ]);

        // First call - should cache
        $cached = PolicyConfiguration::getCachedPolicy($this->branch, PolicyConfiguration::POLICY_TYPE_BOOKING);
        $this->assertNotNull($cached);
        $this->assertEquals($policy->id, $cached->id);

        // Second call - should hit cache
        $cachedAgain = PolicyConfiguration::getCachedPolicy($this->branch, PolicyConfiguration::POLICY_TYPE_BOOKING);
        $this->assertNotNull($cachedAgain);
        $this->assertEquals($policy->id, $cachedAgain->id);

        // Update policy - cache should invalidate
        $policy->config = ['enabled' => false];
        $policy->save();

        // Should get updated policy
        $updated = PolicyConfiguration::getCachedPolicy($this->branch, PolicyConfiguration::POLICY_TYPE_BOOKING);
        $this->assertFalse($updated->config['enabled']);
    }

    /**
     * Test: Unknown operations default to allow
     */
    public function test_unknown_operation_defaults_to_allow()
    {
        $result = $this->enforcer->isOperationAllowed(
            $this->branch,
            $this->regularCall,
            'unknown_operation'
        );

        $this->assertTrue($result['allowed']);
        $this->assertEquals('unknown_operation_default_allow', $result['reason']);
    }
}
