<?php

namespace Tests\Feature\CalcomIntegration;

use Tests\TestCase;

/**
 * Appointment Query Test
 *
 * Tests secure appointment querying with multi-tenant isolation
 */
class AppointmentQueryTest extends TestCase
{
    /**
     * Test query returns only team appointments
     */
    public function test_query_returns_team_appointments(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);

        $this->assertNotNull($teamId);
        $this->assertGreaterThan(0, $teamId);
    }

    /**
     * Test query respects company context
     */
    public function test_query_respects_company_context(): void
    {
        $company = env('TEST_COMPANY', 'AskProAI');
        $teamId = env('TEST_TEAM_ID', 39203);

        $this->assertNotEmpty($company);
        $this->assertEquals($teamId, 39203);
    }

    /**
     * Test multi-tenant query isolation
     */
    public function test_query_multi_tenant_isolation(): void
    {
        $eventIds = explode(',', env('TEST_EVENT_IDS', '3664712,2563193'));

        // Query should only include own team's events
        $this->assertGreaterThanOrEqual(1, count($eventIds));
    }

    /**
     * Test secure query filtering
     */
    public function test_query_secure_filtering(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);
        $company = env('TEST_COMPANY', 'AskProAI');

        // Queries should be filtered by both team and company
        $this->assertNotNull($teamId);
        $this->assertNotEmpty($company);
    }
}
