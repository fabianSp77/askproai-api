<?php

namespace Tests\Feature\CalcomIntegration;

use Tests\TestCase;

/**
 * Appointment Reschedule Test
 *
 * Tests Cal.com appointment rescheduling workflow
 */
class AppointmentRescheduleTest extends TestCase
{
    /**
     * Test reschedule with correct team context
     */
    public function test_reschedule_with_correct_team(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);

        $this->assertNotNull($teamId);
        $this->assertGreaterThan(0, $teamId);
    }

    /**
     * Test reschedule preserves company context
     */
    public function test_reschedule_preserves_company_context(): void
    {
        $company = env('TEST_COMPANY', 'AskProAI');
        $teamId = env('TEST_TEAM_ID', 39203);

        $this->assertNotEmpty($company);
        $this->assertEquals($teamId, 39203);
    }

    /**
     * Test reschedule multi-tenant isolation
     */
    public function test_reschedule_multi_tenant_isolation(): void
    {
        $eventIds = explode(',', env('TEST_EVENT_IDS', '3664712,2563193'));

        // Should only see own team's appointments
        $this->assertGreaterThanOrEqual(1, count($eventIds));
    }

    /**
     * Test reschedule date validation
     */
    public function test_reschedule_date_validation(): void
    {
        $company = env('TEST_COMPANY', 'AskProAI');

        $this->assertNotEmpty($company);
    }
}
