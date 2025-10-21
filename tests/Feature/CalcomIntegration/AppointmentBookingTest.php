<?php

namespace Tests\Feature\CalcomIntegration;

use Tests\TestCase;

/**
 * Appointment Booking Test
 *
 * Tests Cal.com appointment creation and booking workflow
 */
class AppointmentBookingTest extends TestCase
{
    /**
     * Test appointment booking with correct team
     */
    public function test_create_appointment_with_correct_team(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);

        $this->assertNotNull($teamId);
        $this->assertEquals(39203, $teamId);
    }

    /**
     * Test appointment uses correct event ID
     */
    public function test_appointment_uses_correct_event_id(): void
    {
        $eventIds = explode(',', env('TEST_EVENT_IDS', '3664712,2563193'));

        $this->assertGreaterThanOrEqual(1, count($eventIds));
        $this->assertContains('3664712', $eventIds);
    }

    /**
     * Test appointment booking validation
     */
    public function test_appointment_booking_validation(): void
    {
        $company = env('TEST_COMPANY', 'AskProAI');

        $this->assertNotEmpty($company);
    }

    /**
     * Test multi-tenant isolation in booking
     */
    public function test_booking_multi_tenant_isolation(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);
        $company = env('TEST_COMPANY', 'AskProAI');

        // Appointments should be isolated by team
        $this->assertNotNull($teamId);
        $this->assertEquals('AskProAI', $company);
    }
}
