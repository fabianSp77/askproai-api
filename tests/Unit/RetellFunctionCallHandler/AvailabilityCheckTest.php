<?php

use Carbon\Carbon;
use Tests\TestCase;

class AvailabilityCheckTest extends TestCase
{
    /**
     * Test that isTimeAvailable correctly handles flat array of slots from Cal.com
     *
     * BUG FIX 2025-10-19: Cal.com returns flat array [{'time': '13:30', ...}, ...]
     * but the method was expecting date-indexed array {'2025-10-20': ['13:30', ...]}
     */
    public function test_is_time_available_with_flat_slot_array()
    {
        // Simulate Cal.com's actual response format: flat array of slot objects
        $slots = [
            ['time' => '07:00', 'duration' => 60],
            ['time' => '07:30', 'duration' => 60],
            ['time' => '08:00', 'duration' => 60],
            ['time' => '12:30', 'duration' => 60],
            // NOTE: 13:00 is MISSING - this is the key test
            ['time' => '13:30', 'duration' => 60],
            ['time' => '14:30', 'duration' => 60],
        ];

        $requestedTime = Carbon::parse('2025-10-20 13:00');

        // Create reflection to access private method
        $controller = app(\App\Http\Controllers\RetellFunctionCallHandler::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isTimeAvailable');
        $method->setAccessible(true);

        // This should return FALSE because 13:00 is not in the slots array
        $result = $method->invoke($controller, $requestedTime, $slots);

        $this->assertFalse($result, 'Expected 13:00 to NOT be available (it is missing from slots)');
    }

    /**
     * Test that isTimeAvailable correctly returns true for available times
     */
    public function test_is_time_available_returns_true_for_available_time()
    {
        $slots = [
            ['time' => '07:00', 'duration' => 60],
            ['time' => '13:30', 'duration' => 60],  // This one IS available
            ['time' => '14:30', 'duration' => 60],
        ];

        $requestedTime = Carbon::parse('2025-10-20 13:30');

        $controller = app(\App\Http\Controllers\RetellFunctionCallHandler::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isTimeAvailable');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $requestedTime, $slots);

        $this->assertTrue($result, 'Expected 13:30 to be available');
    }

    /**
     * Test with string slots (alternative format)
     */
    public function test_is_time_available_with_string_slots()
    {
        $slots = [
            '07:00',
            '13:30',
            '14:30',
        ];

        $requestedTime = Carbon::parse('2025-10-20 13:30');

        $controller = app(\App\Http\Controllers\RetellFunctionCallHandler::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isTimeAvailable');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $requestedTime, $slots);

        $this->assertTrue($result, 'Expected 13:30 to be available with string format slots');
    }

    /**
     * Test exact time matching (not fuzzy matching)
     */
    public function test_is_time_available_requires_exact_match()
    {
        $slots = [
            ['time' => '13:00', 'duration' => 60],
            ['time' => '13:30', 'duration' => 60],
        ];

        $requestedTime = Carbon::parse('2025-10-20 13:15');  // Request 13:15, only 13:00 and 13:30 available

        $controller = app(\App\Http\Controllers\RetellFunctionCallHandler::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isTimeAvailable');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $requestedTime, $slots);

        $this->assertFalse($result, 'Expected 13:15 to NOT be available (not exact match)');
    }
}
