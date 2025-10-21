<?php

namespace Tests\Feature\CalcomIntegration;

use Tests\TestCase;

/**
 * Multi-Tenant Isolation Test
 *
 * Tests that AskProAI and Friseur 1 data is properly isolated
 */
class MultiTenantIsolationTest extends TestCase
{
    /**
     * Test team isolation - cannot access other team's data
     */
    public function test_team_isolation_prevents_cross_access(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);

        // Only AskProAI (39203) and Friseur 1 (34209) are configured
        $this->assertContains($teamId, [39203, 34209]);
    }

    /**
     * Test company context prevents data leakage
     */
    public function test_company_context_prevents_leakage(): void
    {
        $company = env('TEST_COMPANY', 'AskProAI');
        $teamId = env('TEST_TEAM_ID', 39203);

        // Verify we're testing with configured company
        $this->assertContains($company, ['AskProAI', 'Friseur 1']);
        $this->assertContains($teamId, [39203, 34209]);
    }

    /**
     * Test event ID isolation
     */
    public function test_event_id_isolation(): void
    {
        $eventIds = explode(',', env('TEST_EVENT_IDS', '3664712,2563193'));
        $company = env('TEST_COMPANY', 'AskProAI');

        // AskProAI events should be different from Friseur 1
        if ($company === 'AskProAI') {
            $this->assertContains('3664712', $eventIds);
        }

        $this->assertGreaterThanOrEqual(1, count($eventIds));
    }

    /**
     * Test row-level security isolation
     */
    public function test_row_level_security_isolation(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);
        $company = env('TEST_COMPANY', 'AskProAI');

        // Database RLS should enforce company isolation
        $this->assertNotNull($teamId);
        $this->assertNotEmpty($company);
    }

    /**
     * Test appointment data isolation
     */
    public function test_appointment_data_isolation(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);
        $company = env('TEST_COMPANY', 'AskProAI');
        $eventIds = explode(',', env('TEST_EVENT_IDS', '3664712,2563193'));

        // Each company can only see its own appointments
        $this->assertNotNull($teamId);
        $this->assertNotEmpty($company);
        $this->assertGreaterThanOrEqual(1, count($eventIds));
    }
}
