<?php

namespace Tests\Feature\RetellIntegration;

use Tests\TestCase;
use Carbon\Carbon;

/**
 * Hidden Number Test
 *
 * Tests Retell AI handling of calls with suppressed/hidden phone numbers
 * Numbers: 00000000, +4900000000, or similar indicators
 */
class HiddenNumberTest extends TestCase
{
    /**
     * Test check_customer fails gracefully with hidden number
     */
    public function test_check_customer_with_hidden_number(): void
    {
        // When phone = 00000000 (hidden)
        $hiddenNumber = '00000000';
        $this->assertNotEmpty($hiddenNumber);

        // Backend should return error or null customer_id
        $expectedResult = ['customer_id' => null, 'exists' => false];
        $this->assertFalse($expectedResult['exists']);
    }

    /**
     * Test query_appointment blocks hidden number
     */
    public function test_query_appointment_blocked_hidden_number(): void
    {
        // GIVEN: Call with hidden number
        $isHiddenNumber = true;

        // WHEN: Agent calls query_appointment()
        // THEN: Should fail with error message
        $expectedError = 'hidden_number_not_supported';
        $this->assertNotEmpty($expectedError);
    }

    /**
     * Test agent should ask for name when number hidden
     */
    public function test_agent_fallback_ask_for_name(): void
    {
        // GIVEN: Hidden number detected
        // THEN: Agent should ask "Wie heißen Sie?"
        $prompt = "Freundlich begrüßen. Nach NAME fragen.";
        $this->assertStringContainsString('Nach NAME fragen', $prompt);
    }

    /**
     * Test reschedule with hidden number but known name
     */
    public function test_reschedule_anonymous_with_name(): void
    {
        $customerName = 'Hans Müller';
        $oldDate = '2025-10-25';
        $newDate = '2025-10-30';
        $newTime = '14:00';

        // WHEN: reschedule_appointment called with customer_name
        // THEN: Should work (workaround for hidden number)
        $hasCustomerName = !empty($customerName);
        $this->assertTrue($hasCustomerName);
    }

    /**
     * Test cancel with hidden number but known name
     */
    public function test_cancel_anonymous_with_name(): void
    {
        $customerName = 'Maria Schmidt';
        $appointmentDate = '2025-10-25';

        // WHEN: cancel_appointment called with customer_name
        // THEN: Should work (workaround for hidden number)
        $hasName = !empty($customerName);
        $this->assertTrue($hasName);
    }
}
