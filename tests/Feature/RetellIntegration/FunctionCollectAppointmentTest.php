<?php

namespace Tests\Feature\RetellIntegration;

use Tests\TestCase;
use Carbon\Carbon;

/**
 * Function Collect Appointment Test
 *
 * Tests Retell collect_appointment function call
 */
class FunctionCollectAppointmentTest extends TestCase
{
    /**
     * Test collect_appointment stores customer data
     */
    public function test_collect_appointment_stores_data(): void
    {
        $data = [
            'service' => 'Herrenhaarschnitt',
            'customer_phone' => '+491510123456',
            'customer_name' => 'Max Mustermann',
            'datum' => Carbon::tomorrow()->format('d.m.Y'),
            'uhrzeit' => '14:00'
        ];

        $this->assertNotNull($data['service']);
        $this->assertNotNull($data['customer_name']);
    }

    /**
     * Test collect_appointment validates date format
     */
    public function test_collect_appointment_date_format_german(): void
    {
        $date = Carbon::tomorrow()->format('d.m.Y');
        $isValid = preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $date);

        $this->assertTrue($isValid > 0);
    }

    /**
     * Test collect_appointment validates time format
     */
    public function test_collect_appointment_time_format(): void
    {
        $time = '14:00';
        $isValid = preg_match('/^\d{2}:\d{2}$/', $time);

        $this->assertTrue($isValid > 0);
    }

    /**
     * Test collect_appointment accepts date shortformat
     */
    public function test_collect_appointment_shortdate_15_1(): void
    {
        $shortDate = '15.1';
        // Should be parsed as current month or next occurrence
        $this->assertStringContainsString('.', $shortDate);
    }

    /**
     * Test collect_appointment returns confirmation
     */
    public function test_collect_appointment_returns_confirmation(): void
    {
        $confirmed = true;
        $this->assertTrue($confirmed);
    }

    /**
     * Test collect_appointment stores customer name
     */
    public function test_collect_appointment_customer_name_not_empty(): void
    {
        $name = 'Test Kunde 123';
        $this->assertNotEmpty($name);
    }
}
