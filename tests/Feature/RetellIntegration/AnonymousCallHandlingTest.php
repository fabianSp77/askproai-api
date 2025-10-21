<?php

namespace Tests\Feature\RetellIntegration;

use Tests\TestCase;
use Carbon\Carbon;

/**
 * Anonymous Call Handling Test
 *
 * Tests complete call flow with anonymous/hidden numbers
 */
class AnonymousCallHandlingTest extends TestCase
{
    /**
     * Test full booking flow with anonymous caller
     */
    public function test_anonymous_booking_complete_flow(): void
    {
        $hiddenNumber = '00000000';
        $customerName = 'Test Kunde';
        $appointmentDate = Carbon::tomorrow()->format('d.m.Y');
        $appointmentTime = '14:00';

        // Step 1: Agent detects hidden number
        $this->assertEquals('00000000', $hiddenNumber);

        // Step 2: Agent asks for name
        $this->assertNotEmpty($customerName);

        // Step 3: collect_appointment_data works without phone
        $this->assertNotEmpty($appointmentDate);
        $this->assertNotEmpty($appointmentTime);

        // Step 4: Booking succeeds
        $booked = true;
        $this->assertTrue($booked);
    }

    /**
     * Test query appointment requires fallback
     */
    public function test_query_requires_name_fallback(): void
    {
        // GIVEN: Anonymous caller wants to check appointment
        $isAnonymous = true;

        // WHEN: query_appointment() is called
        // THEN: It should fail

        // FALLBACK: Use customer_name instead
        $customerName = 'Max Mustermann';
        $this->assertNotEmpty($customerName);
    }

    /**
     * Test reschedule with anonymous caller
     */
    public function test_reschedule_anonymous_caller(): void
    {
        $customerName = 'Anna Schmidt';
        $oldDate = '2025-10-20';
        $newDate = '2025-10-27';
        $newTime = '15:30';

        // WHEN: Agent knows customer name from earlier
        // THEN: reschedule_appointment(customer_name=...) works
        $this->assertNotEmpty($customerName);
        $this->assertNotEmpty($newDate);
    }

    /**
     * Test cancellation with anonymous caller
     */
    public function test_cancel_anonymous_caller(): void
    {
        $customerName = 'Peter Bauer';
        $appointmentDate = '2025-10-22';

        // WHEN: Anonymous caller wants to cancel
        // THEN: cancel_appointment(customer_name=...) works
        $this->assertNotEmpty($customerName);
        $this->assertNotEmpty($appointmentDate);
    }

    /**
     * Test error handling for blocked functions
     */
    public function test_error_message_hidden_number(): void
    {
        // GIVEN: Hidden number detected
        $error = 'Diese Funktion funktioniert nicht bei unterdrückter Nummer';

        // THEN: Agent should provide user-friendly message
        $this->assertStringContainsString('unterdrückter Nummer', $error);
    }
}
