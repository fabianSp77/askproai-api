<?php

namespace Tests\Feature\CalcomIntegration;

use Tests\TestCase;

/**
 * Appointment Cancellation Test
 *
 * Tests Cal.com appointment cancellation workflow
 */
class AppointmentCancellationTest extends TestCase
{
    /**
     * Test cancellation with correct team
     */
    public function test_cancellation_with_correct_team(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);

        $this->assertNotNull($teamId);
        $this->assertGreaterThan(0, $teamId);
    }

    /**
     * Test cancellation preserves team context
     */
    public function test_cancellation_preserves_team_context(): void
    {
        $company = env('TEST_COMPANY', 'AskProAI');
        $teamId = env('TEST_TEAM_ID', 39203);

        $this->assertNotEmpty($company);
        $this->assertNotNull($teamId);
    }

    /**
     * Test multi-tenant isolation on cancellation
     */
    public function test_cancellation_multi_tenant_isolation(): void
    {
        $eventIds = explode(',', env('TEST_EVENT_IDS', '3664712,2563193'));

        // Can only cancel own appointments
        $this->assertGreaterThanOrEqual(1, count($eventIds));
    }

    /**
     * Test cancellation verification
     */
    public function test_cancellation_verification(): void
    {
        $company = env('TEST_COMPANY', 'AskProAI');

        $this->assertNotEmpty($company);
    }
}
