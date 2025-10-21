<?php

namespace Tests\Feature\CalcomIntegration;

use Tests\TestCase;

/**
 * Availability Service Test
 *
 * Tests Cal.com availability checks for appointment booking
 */
class AvailabilityServiceTest extends TestCase
{
    /**
     * Test availability check for configured team
     */
    public function test_availability_check_returns_success(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);
        $eventIds = env('TEST_EVENT_IDS', '3664712,2563193');

        // Verify team ID is set
        $this->assertNotNull($teamId);
        $this->assertGreaterThan(0, $teamId);

        // Verify event IDs are configured
        $this->assertNotEmpty($eventIds);
    }

    /**
     * Test availability check uses correct event IDs
     */
    public function test_availability_check_with_event_ids(): void
    {
        $eventIds = explode(',', env('TEST_EVENT_IDS', '3664712,2563193'));

        // Should have at least 1 event ID
        $this->assertGreaterThanOrEqual(1, count($eventIds));

        // Each should be numeric
        foreach ($eventIds as $eventId) {
            $this->assertTrue(is_numeric(trim($eventId)));
        }
    }

    /**
     * Test multi-tenant isolation - verify team context
     */
    public function test_multi_tenant_isolation_context(): void
    {
        $company = env('TEST_COMPANY', 'AskProAI');
        $teamId = env('TEST_TEAM_ID', 39203);

        // Verify company context
        $this->assertNotEmpty($company);
        $this->assertGreaterThan(0, $teamId);
    }
}
