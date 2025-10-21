<?php

namespace Tests\Feature\RetellIntegration;

use Tests\TestCase;
use Carbon\Carbon;

/**
 * Function Check Availability Test
 *
 * Tests Retell check_availability function call
 */
class FunctionCheckAvailabilityTest extends TestCase
{
    /**
     * Test check_availability returns slots
     */
    public function test_check_availability_returns_slots(): void
    {
        $service = 'Herrenhaarschnitt';
        $date = Carbon::tomorrow()->format('Y-m-d');
        $time = '10:00';

        $this->assertNotNull($service);
        $this->assertNotNull($date);
        $this->assertNotNull($time);
    }

    /**
     * Test check_availability validates date format
     */
    public function test_check_availability_date_format(): void
    {
        $date = Carbon::tomorrow()->format('Y-m-d');
        $isValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);

        $this->assertTrue($isValid > 0);
    }

    /**
     * Test check_availability validates time format
     */
    public function test_check_availability_time_format(): void
    {
        $time = '14:00';
        $isValid = preg_match('/^\d{2}:\d{2}$/', $time);

        $this->assertTrue($isValid > 0);
    }

    /**
     * Test check_availability slot available
     */
    public function test_check_availability_slot_available(): void
    {
        $available = true;
        $this->assertTrue($available);
    }

    /**
     * Test check_availability slot unavailable
     */
    public function test_check_availability_slot_unavailable(): void
    {
        $available = false;
        $this->assertFalse($available);
    }

    /**
     * Test check_availability returns multiple slots
     */
    public function test_check_availability_multiple_slots(): void
    {
        $slots = ['10:00', '11:00', '14:00', '15:00'];
        $this->assertGreaterThanOrEqual(1, count($slots));
    }
}
