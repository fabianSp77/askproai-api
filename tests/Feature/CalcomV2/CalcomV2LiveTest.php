<?php

namespace Tests\Feature\CalcomV2;

use Tests\TestCase;
use App\Services\CalcomV2Client;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\CalcomEventMap;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * LIVE TESTS - Tests against real Cal.com API
 *
 * WARNUNG: Diese Tests verwenden die echte Cal.com API!
 * - Erstellt echte Buchungen
 * - Verursacht API-Calls
 * - Cleanup nach jedem Test wichtig
 *
 * Nur in Staging/Test-Umgebung ausführen!
 */
class CalcomV2LiveTest extends TestCase
{
    use RefreshDatabase;

    private CalcomV2Client $client;
    private Company $company;
    private Branch $branch;
    private Service $service;
    private Staff $staff;
    private array $createdBookings = [];
    private bool $isLiveTestEnabled = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if live tests are enabled
        $this->isLiveTestEnabled = env('CALCOM_LIVE_TESTS_ENABLED', false);

        if (!$this->isLiveTestEnabled) {
            $this->markTestSkipped('Cal.com live tests are disabled. Set CALCOM_LIVE_TESTS_ENABLED=true to run.');
        }

        // Verify we have real API credentials
        if (!env('CALCOM_API_KEY') || str_contains(env('CALCOM_API_KEY'), 'test')) {
            $this->markTestSkipped('Real Cal.com API key required for live tests.');
        }

        // Setup test data
        $this->setupTestEnvironment();

        // Create real client with actual API key
        $this->client = new CalcomV2Client($this->company);
    }

    protected function tearDown(): void
    {
        // WICHTIG: Cleanup aller erstellten Buchungen
        $this->cleanupBookings();

        parent::tearDown();
    }

    private function setupTestEnvironment(): void
    {
        // Create test company with real API key
        $this->company = Company::factory()->create([
            'name' => 'Live Test Company',
            'calcom_v2_api_key' => env('CALCOM_API_KEY')
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Live Test Branch'
        ]);

        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Live Test Staff',
            'email' => 'livetest@example.com'
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Live Test Service',
            'duration_minutes' => 30,
            'price' => 50.00
        ]);

        // Create real event mapping
        CalcomEventMap::create([
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'event_type_id' => env('CALCOM_EVENT_TYPE_ID', 2026302),
            'sync_status' => 'synced'
        ]);
    }

    private function cleanupBookings(): void
    {
        foreach ($this->createdBookings as $bookingId) {
            try {
                $this->client->cancelBooking($bookingId, 'Test cleanup');
                Log::info("Cleaned up test booking: {$bookingId}");
            } catch (\Exception $e) {
                Log::warning("Failed to cleanup booking {$bookingId}: " . $e->getMessage());
            }
        }
    }

    /**
     * Test 1: Real availability check
     */
    public function test_real_availability_check()
    {
        $start = Carbon::now()->addDays(7)->startOfDay()->setTime(9, 0);
        $end = $start->copy()->endOfDay()->setTime(18, 0);

        $response = $this->client->getAvailableSlots(
            env('CALCOM_EVENT_TYPE_ID', 2026302),
            $start,
            $end,
            'Europe/Berlin'
        );

        $this->assertTrue($response->successful(), 'API request failed: ' . $response->body());
        $this->assertEquals(200, $response->status());

        $slots = $response->json('data.slots');
        $this->assertIsArray($slots);

        if (!empty($slots)) {
            $firstSlot = $slots[0];
            $this->assertArrayHasKey('start', $firstSlot);
            $this->assertArrayHasKey('end', $firstSlot);

            // Verify timezone handling
            $slotTime = Carbon::parse($firstSlot['start']);
            $this->assertEquals('Europe/Berlin', $slotTime->timezone->getName());
        }

        Log::info('Live availability check passed', [
            'slots_found' => count($slots),
            'date_range' => "{$start->toDateString()} to {$end->toDateString()}"
        ]);
    }

    /**
     * Test 2: Real booking creation and cancellation
     */
    public function test_real_booking_lifecycle()
    {
        // Step 1: Find available slot
        $start = Carbon::now()->addDays(14)->startOfDay()->setTime(10, 0);
        $end = $start->copy()->addHours(8);

        $slotsResponse = $this->client->getAvailableSlots(
            env('CALCOM_EVENT_TYPE_ID', 2026302),
            $start,
            $end,
            'Europe/Berlin'
        );

        $this->assertTrue($slotsResponse->successful());
        $slots = $slotsResponse->json('data.slots');
        $this->assertNotEmpty($slots, 'No available slots found for booking test');

        $selectedSlot = $slots[0];

        // Step 2: Create booking
        $bookingData = [
            'eventTypeId' => env('CALCOM_EVENT_TYPE_ID', 2026302),
            'start' => $selectedSlot['start'],
            'end' => $selectedSlot['end'],
            'timeZone' => 'Europe/Berlin',
            'name' => 'Live Test Customer',
            'email' => 'livetest' . uniqid() . '@example.com',
            'metadata' => [
                'test_id' => uniqid('live_test_'),
                'test_type' => 'lifecycle',
                'timestamp' => Carbon::now()->toIso8601String()
            ]
        ];

        $bookingResponse = $this->client->createBooking($bookingData);

        $this->assertTrue($bookingResponse->successful(), 'Booking creation failed: ' . $bookingResponse->body());
        $this->assertEquals(201, $bookingResponse->status());

        $bookingId = $bookingResponse->json('data.id');
        $this->assertNotNull($bookingId);
        $this->createdBookings[] = $bookingId; // Track for cleanup

        // Step 3: Verify booking exists
        $getResponse = $this->client->getBooking($bookingId);
        $this->assertTrue($getResponse->successful());
        $this->assertEquals($bookingId, $getResponse->json('data.id'));

        // Step 4: Cancel booking
        $cancelResponse = $this->client->cancelBooking($bookingId, 'Live test cancellation');
        $this->assertTrue($cancelResponse->successful());

        // Remove from cleanup list since we cancelled it
        $this->createdBookings = array_diff($this->createdBookings, [$bookingId]);

        Log::info('Live booking lifecycle test passed', [
            'booking_id' => $bookingId,
            'slot' => $selectedSlot
        ]);
    }

    /**
     * Test 3: Real reschedule operation
     */
    public function test_real_booking_reschedule()
    {
        // Create initial booking
        $initialStart = Carbon::now()->addDays(21)->setTime(14, 0);
        $initialEnd = $initialStart->copy()->addMinutes(30);

        // Find slot for initial booking
        $slotsResponse = $this->client->getAvailableSlots(
            env('CALCOM_EVENT_TYPE_ID', 2026302),
            $initialStart->copy()->startOfDay(),
            $initialStart->copy()->endOfDay(),
            'Europe/Berlin'
        );

        $slots = $slotsResponse->json('data.slots');
        $this->assertNotEmpty($slots);

        // Create booking
        $bookingResponse = $this->client->createBooking([
            'eventTypeId' => env('CALCOM_EVENT_TYPE_ID', 2026302),
            'start' => $slots[0]['start'],
            'end' => $slots[0]['end'],
            'timeZone' => 'Europe/Berlin',
            'name' => 'Reschedule Test',
            'email' => 'reschedule' . uniqid() . '@example.com'
        ]);

        $bookingId = $bookingResponse->json('data.id');
        $this->createdBookings[] = $bookingId;

        // Find new slot for rescheduling
        $newStart = Carbon::now()->addDays(22)->setTime(10, 0);
        $newSlotsResponse = $this->client->getAvailableSlots(
            env('CALCOM_EVENT_TYPE_ID', 2026302),
            $newStart->copy()->startOfDay(),
            $newStart->copy()->endOfDay(),
            'Europe/Berlin'
        );

        $newSlots = $newSlotsResponse->json('data.slots');
        $this->assertNotEmpty($newSlots);

        // Reschedule the booking
        $rescheduleResponse = $this->client->rescheduleBooking($bookingId, [
            'start' => $newSlots[0]['start'],
            'end' => $newSlots[0]['end'],
            'timeZone' => 'Europe/Berlin',
            'reason' => 'Live test reschedule'
        ]);

        $this->assertTrue($rescheduleResponse->successful(), 'Reschedule failed: ' . $rescheduleResponse->body());

        // Cleanup
        $this->client->cancelBooking($bookingId, 'Test cleanup after reschedule');
        $this->createdBookings = array_diff($this->createdBookings, [$bookingId]);

        Log::info('Live reschedule test passed', ['booking_id' => $bookingId]);
    }

    /**
     * Test 4: Multiple timezone handling
     */
    public function test_real_timezone_handling()
    {
        $timezones = ['Europe/Berlin', 'America/New_York', 'Asia/Tokyo'];
        $baseDate = Carbon::now()->addDays(30);

        foreach ($timezones as $timezone) {
            $start = $baseDate->copy()->setTimezone($timezone)->setTime(9, 0);
            $end = $start->copy()->addHours(8);

            $response = $this->client->getAvailableSlots(
                env('CALCOM_EVENT_TYPE_ID', 2026302),
                $start,
                $end,
                $timezone
            );

            $this->assertTrue($response->successful(), "Failed for timezone: {$timezone}");

            $slots = $response->json('data.slots');

            if (!empty($slots)) {
                // Verify slots are in correct timezone
                $firstSlot = Carbon::parse($slots[0]['start']);
                $this->assertEquals($timezone, $firstSlot->timezone->getName());
            }

            Log::info("Timezone test passed: {$timezone}", ['slots_count' => count($slots)]);
        }
    }

    /**
     * Test 5: Concurrent booking attempts (race condition)
     */
    public function test_real_concurrent_booking_prevention()
    {
        // Find an available slot
        $start = Carbon::now()->addDays(35)->setTime(11, 0);
        $slotsResponse = $this->client->getAvailableSlots(
            env('CALCOM_EVENT_TYPE_ID', 2026302),
            $start->copy()->startOfDay(),
            $start->copy()->endOfDay(),
            'Europe/Berlin'
        );

        $slots = $slotsResponse->json('data.slots');
        $this->assertNotEmpty($slots);
        $targetSlot = $slots[0];

        // Attempt to book the same slot twice
        $booking1Data = [
            'eventTypeId' => env('CALCOM_EVENT_TYPE_ID', 2026302),
            'start' => $targetSlot['start'],
            'end' => $targetSlot['end'],
            'timeZone' => 'Europe/Berlin',
            'name' => 'First Customer',
            'email' => 'first' . uniqid() . '@example.com'
        ];

        $booking2Data = [
            'eventTypeId' => env('CALCOM_EVENT_TYPE_ID', 2026302),
            'start' => $targetSlot['start'],
            'end' => $targetSlot['end'],
            'timeZone' => 'Europe/Berlin',
            'name' => 'Second Customer',
            'email' => 'second' . uniqid() . '@example.com'
        ];

        // First booking should succeed
        $response1 = $this->client->createBooking($booking1Data);
        $this->assertTrue($response1->successful());
        $bookingId1 = $response1->json('data.id');
        $this->createdBookings[] = $bookingId1;

        // Second booking should fail (slot already taken)
        $response2 = $this->client->createBooking($booking2Data);

        // Expect either 409 (conflict) or another error status
        $this->assertFalse($response2->successful() && $response2->status() === 201,
            'Second booking should have been prevented');

        // Cleanup
        $this->client->cancelBooking($bookingId1, 'Test cleanup');
        $this->createdBookings = array_diff($this->createdBookings, [$bookingId1]);

        Log::info('Concurrent booking prevention test passed');
    }

    /**
     * Test 6: API response time monitoring
     */
    public function test_real_api_performance()
    {
        $iterations = 5;
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);

            $response = $this->client->getAvailableSlots(
                env('CALCOM_EVENT_TYPE_ID', 2026302),
                Carbon::now()->addDays(40 + $i),
                Carbon::now()->addDays(40 + $i)->addHours(8),
                'Europe/Berlin'
            );

            $duration = (microtime(true) - $start) * 1000; // Convert to ms
            $times[] = $duration;

            $this->assertTrue($response->successful());

            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 second
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);

        Log::info('API Performance Test Results', [
            'average_ms' => round($avgTime, 2),
            'max_ms' => round($maxTime, 2),
            'min_ms' => round($minTime, 2),
            'iterations' => $iterations
        ]);

        // Assert reasonable response times (adjust thresholds as needed)
        $this->assertLessThan(3000, $avgTime, 'Average response time too high');
        $this->assertLessThan(5000, $maxTime, 'Max response time too high');
    }

    /**
     * Test 7: Webhook signature validation
     */
    public function test_webhook_signature_validation()
    {
        $payload = [
            'triggerEvent' => 'BOOKING.CREATED',
            'payload' => [
                'id' => 12345,
                'eventTypeId' => env('CALCOM_EVENT_TYPE_ID'),
                'title' => 'Test Booking',
                'start' => Carbon::now()->addDays(45)->toIso8601String(),
                'end' => Carbon::now()->addDays(45)->addMinutes(30)->toIso8601String()
            ]
        ];

        $secret = env('CALCOM_WEBHOOK_SECRET');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => $signature
        ]);

        $response->assertStatus(200);
        $response->assertJson(['received' => true]);

        Log::info('Webhook signature validation test passed');
    }

    /**
     * Test 8: Error recovery mechanisms
     */
    public function test_real_error_recovery()
    {
        // Test with invalid event type ID
        $response = $this->client->getAvailableSlots(
            99999999, // Non-existent event type
            Carbon::tomorrow(),
            Carbon::tomorrow()->addHours(8),
            'Europe/Berlin'
        );

        // Should handle error gracefully
        $this->assertFalse($response->successful());
        $this->assertContains($response->status(), [404, 400, 422]);

        // Test with past date (should fail)
        $response = $this->client->createBooking([
            'eventTypeId' => env('CALCOM_EVENT_TYPE_ID'),
            'start' => Carbon::yesterday()->toIso8601String(),
            'end' => Carbon::yesterday()->addMinutes(30)->toIso8601String(),
            'timeZone' => 'Europe/Berlin',
            'name' => 'Past Test',
            'email' => 'past@example.com'
        ]);

        $this->assertFalse($response->successful());

        Log::info('Error recovery test passed');
    }

    /**
     * Test 9: Bulk operations performance
     */
    public function test_real_bulk_availability_check()
    {
        $startTime = microtime(true);
        $requests = [];

        // Prepare 10 parallel availability checks
        for ($i = 0; $i < 10; $i++) {
            $date = Carbon::now()->addDays(50 + $i);
            $requests[] = [
                'start' => $date->copy()->setTime(9, 0),
                'end' => $date->copy()->setTime(17, 0)
            ];
        }

        $responses = [];
        foreach ($requests as $request) {
            $response = $this->client->getAvailableSlots(
                env('CALCOM_EVENT_TYPE_ID'),
                $request['start'],
                $request['end'],
                'Europe/Berlin'
            );

            $this->assertTrue($response->successful());
            $responses[] = $response->json();

            usleep(100000); // 100ms delay to avoid rate limiting
        }

        $totalTime = (microtime(true) - $startTime) * 1000;
        $avgTimePerRequest = $totalTime / count($requests);

        Log::info('Bulk operations test completed', [
            'total_requests' => count($requests),
            'total_time_ms' => round($totalTime, 2),
            'avg_time_per_request_ms' => round($avgTimePerRequest, 2)
        ]);

        $this->assertLessThan(2000, $avgTimePerRequest, 'Bulk operation too slow');
    }

    /**
     * Test 10: Data integrity verification
     */
    public function test_real_data_integrity()
    {
        // Create a booking with specific metadata
        $testId = uniqid('integrity_');
        $metadata = [
            'test_id' => $testId,
            'test_data' => 'äöüß€@#', // Special characters
            'test_number' => 12345.67,
            'test_bool' => true,
            'test_array' => ['a', 'b', 'c']
        ];

        $start = Carbon::now()->addDays(60)->setTime(15, 0);

        // Get available slot first
        $slotsResponse = $this->client->getAvailableSlots(
            env('CALCOM_EVENT_TYPE_ID'),
            $start->copy()->startOfDay(),
            $start->copy()->endOfDay(),
            'Europe/Berlin'
        );

        $slots = $slotsResponse->json('data.slots');
        $this->assertNotEmpty($slots);

        // Create booking with metadata
        $bookingResponse = $this->client->createBooking([
            'eventTypeId' => env('CALCOM_EVENT_TYPE_ID'),
            'start' => $slots[0]['start'],
            'end' => $slots[0]['end'],
            'timeZone' => 'Europe/Berlin',
            'name' => 'Integrity Test üöä',
            'email' => 'integrity' . uniqid() . '@example.com',
            'metadata' => $metadata
        ]);

        $this->assertTrue($bookingResponse->successful());
        $bookingId = $bookingResponse->json('data.id');
        $this->createdBookings[] = $bookingId;

        // Retrieve booking and verify data integrity
        $getResponse = $this->client->getBooking($bookingId);
        $this->assertTrue($getResponse->successful());

        $retrievedData = $getResponse->json('data');
        $this->assertEquals($bookingId, $retrievedData['id']);

        // Verify metadata integrity (if returned by API)
        if (isset($retrievedData['metadata'])) {
            $this->assertEquals($testId, $retrievedData['metadata']['test_id'] ?? null);
        }

        // Cleanup
        $this->client->cancelBooking($bookingId, 'Integrity test cleanup');
        $this->createdBookings = array_diff($this->createdBookings, [$bookingId]);

        Log::info('Data integrity test passed', ['test_id' => $testId]);
    }
}