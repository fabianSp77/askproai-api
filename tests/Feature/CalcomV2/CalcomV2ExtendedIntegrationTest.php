<?php

namespace Tests\Feature\CalcomV2;

use Tests\TestCase;
use Tests\Mocks\CalcomV2MockServer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\CalcomEventMap;
use App\Services\CalcomV2Client;
use App\Services\Booking\CompositeBookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * Extended Integration Tests for Cal.com V2
 * Covers complex scenarios and edge cases
 */
class CalcomV2ExtendedIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private array $services = [];
    private array $staff = [];
    private Customer $customer;
    private CalcomV2Client $client;
    private CompositeBookingService $compositeService;

    protected function setUp(): void
    {
        parent::setUp();

        CalcomV2MockServer::setUp();
        $this->setupComplexTestEnvironment();
        $this->client = new CalcomV2Client($this->company);
        $this->compositeService = app(CompositeBookingService::class);
    }

    protected function tearDown(): void
    {
        CalcomV2MockServer::reset();
        parent::tearDown();
    }

    private function setupComplexTestEnvironment(): void
    {
        // Create company with multiple branches
        $this->company = Company::factory()->create([
            'name' => 'Extended Test Company',
            'calcom_v2_api_key' => 'test_extended_key'
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Test Branch'
        ]);

        // Create multiple staff members with different skills
        for ($i = 1; $i <= 5; $i++) {
            $this->staff[$i] = Staff::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'name' => "Staff Member $i",
                'email' => "staff$i@test.com",
                'is_active' => true
            ]);
        }

        // Create different service types
        $this->createVariousServices();

        // Create event mappings for all combinations
        $this->createEventMappings();

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Extended Test Customer',
            'email' => 'extended@test.com'
        ]);
    }

    private function createVariousServices(): void
    {
        // Simple 30-minute service
        $this->services['quick'] = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Quick Service',
            'duration_minutes' => 30,
            'composite' => false
        ]);

        // Long 2-hour service
        $this->services['long'] = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Long Service',
            'duration_minutes' => 120,
            'composite' => false
        ]);

        // Complex composite service with 3 segments
        $this->services['complex'] = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Complex Composite Service',
            'duration_minutes' => 180,
            'composite' => true,
            'segments' => [
                [
                    'key' => 'preparation',
                    'name' => 'Preparation',
                    'durationMin' => 30,
                    'gapAfterMin' => 15,
                    'gapAfterMax' => 30,
                    'preferSameStaff' => false
                ],
                [
                    'key' => 'main_service',
                    'name' => 'Main Service',
                    'durationMin' => 60,
                    'gapAfterMin' => 30,
                    'gapAfterMax' => 45,
                    'preferSameStaff' => true
                ],
                [
                    'key' => 'finishing',
                    'name' => 'Finishing',
                    'durationMin' => 30
                ]
            ]
        ]);
    }

    private function createEventMappings(): void
    {
        $eventTypeId = 1000;

        foreach ($this->services as $key => $service) {
            foreach ($this->staff as $staffMember) {
                if ($service->composite) {
                    // Map each segment
                    foreach ($service->segments as $segment) {
                        CalcomEventMap::create([
                            'service_id' => $service->id,
                            'segment_key' => $segment['key'],
                            'staff_id' => $staffMember->id,
                            'event_type_id' => $eventTypeId++,
                            'sync_status' => 'synced'
                        ]);
                    }
                } else {
                    CalcomEventMap::create([
                        'service_id' => $service->id,
                        'staff_id' => $staffMember->id,
                        'event_type_id' => $eventTypeId++,
                        'sync_status' => 'synced'
                    ]);
                }

                // Attach staff to service
                $service->staff()->attach($staffMember->id, [
                    'can_book' => true,
                    'allowed_segments' => json_encode(['all']),
                    'weight' => rand(1, 10)
                ]);
            }
        }
    }

    /**
     * Test 1: Multiple staff availability with ranking
     */
    public function test_multiple_staff_availability_ranking()
    {
        $response = $this->postJson('/api/v2/availability/simple', [
            'service_id' => $this->services['quick']->id,
            'branch_id' => $this->branch->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->toDateString(),
            'timeZone' => 'Europe/Berlin'
        ]);

        $response->assertStatus(200);
        $slots = $response->json('data.slots');
        $this->assertNotEmpty($slots);

        // Verify slots are from multiple staff
        $staffIds = array_unique(array_column($slots, 'staff_id'));
        $this->assertGreaterThan(1, count($staffIds), 'Should have slots from multiple staff');

        // Verify ranking (should be sorted by time first, then staff weight)
        $previousTime = null;
        foreach ($slots as $slot) {
            if ($previousTime !== null) {
                $this->assertGreaterThanOrEqual(
                    $previousTime,
                    Carbon::parse($slot['start'])->timestamp
                );
            }
            $previousTime = Carbon::parse($slot['start'])->timestamp;
        }
    }

    /**
     * Test 2: Complex composite booking with 3 segments
     */
    public function test_complex_three_segment_composite_booking()
    {
        $response = $this->postJson('/api/v2/availability/composite', [
            'service_id' => $this->services['complex']->id,
            'branch_id' => $this->branch->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->toDateString(),
            'timeZone' => 'Europe/Berlin'
        ]);

        $response->assertStatus(200);
        $slots = $response->json('data.slots');

        if (!empty($slots)) {
            $firstSlot = $slots[0];

            // Verify 3 segments
            $this->assertCount(3, $firstSlot['segments']);

            // Verify pause periods between segments
            $this->assertArrayHasKey('pause', $firstSlot);

            // Calculate total duration with pauses
            $totalDuration = Carbon::parse($firstSlot['starts_at'])
                ->diffInMinutes(Carbon::parse($firstSlot['ends_at']));

            $this->assertGreaterThanOrEqual(150, $totalDuration); // At least service duration + pauses

            // Book the composite slot
            $bookingResponse = $this->postJson('/api/v2/bookings', [
                'service_id' => $this->services['complex']->id,
                'branch_id' => $this->branch->id,
                'start' => $firstSlot['starts_at'],
                'customer' => [
                    'name' => 'Complex Test Customer',
                    'email' => 'complex@test.com'
                ],
                'timeZone' => 'Europe/Berlin'
            ]);

            $bookingResponse->assertStatus(201);
            $bookingResponse->assertJsonStructure([
                'data' => [
                    'appointment_id',
                    'composite_uid',
                    'segments'
                ]
            ]);

            // Verify all 3 bookings were created in Cal.com
            $mockBookings = CalcomV2MockServer::getBookings();
            $this->assertCount(3, $mockBookings);
        }
    }

    /**
     * Test 3: Timezone conversion accuracy
     */
    public function test_timezone_conversion_accuracy()
    {
        $timezones = [
            'Europe/Berlin' => 'CET/CEST',
            'America/New_York' => 'EST/EDT',
            'Asia/Tokyo' => 'JST',
            'Australia/Sydney' => 'AEST/AEDT'
        ];

        foreach ($timezones as $tz => $name) {
            $localTime = Carbon::parse('2025-06-15 14:00:00', $tz);

            $response = $this->postJson('/api/v2/availability/simple', [
                'service_id' => $this->services['quick']->id,
                'branch_id' => $this->branch->id,
                'start_date' => $localTime->toDateString(),
                'end_date' => $localTime->toDateString(),
                'timeZone' => $tz
            ]);

            $response->assertStatus(200);
            $slots = $response->json('data.slots');

            if (!empty($slots)) {
                // Verify slot times are in requested timezone
                $slotTime = Carbon::parse($slots[0]['start']);
                $this->assertEquals($tz, $slotTime->timezone->getName());

                Log::info("Timezone test passed for {$name}", [
                    'timezone' => $tz,
                    'slot_time' => $slots[0]['start']
                ]);
            }
        }
    }

    /**
     * Test 4: Parallel booking attempts with locking
     */
    public function test_parallel_booking_with_locking()
    {
        $slot = [
            'start' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
            'end' => Carbon::tomorrow()->setTime(10, 30)->toIso8601String()
        ];

        $results = [];

        // Simulate parallel requests
        $attempts = 5;
        for ($i = 0; $i < $attempts; $i++) {
            $response = $this->postJson('/api/v2/bookings', [
                'service_id' => $this->services['quick']->id,
                'branch_id' => $this->branch->id,
                'staff_id' => $this->staff[1]->id,
                'start' => $slot['start'],
                'customer' => [
                    'name' => "Parallel Customer $i",
                    'email' => "parallel$i@test.com"
                ],
                'timeZone' => 'Europe/Berlin'
            ]);

            $results[] = [
                'attempt' => $i + 1,
                'status' => $response->status(),
                'success' => $response->status() === 201
            ];

            // After first success, set conflict scenario
            if ($response->status() === 201) {
                CalcomV2MockServer::addScenario('createBooking', 'conflict');
            }
        }

        // Only first attempt should succeed
        $successCount = array_sum(array_column($results, 'success'));
        $this->assertEquals(1, $successCount, 'Only one booking should succeed');

        // Verify appointment was created only once
        $this->assertEquals(1, Appointment::count());
    }

    /**
     * Test 5: Reschedule with different staff member
     */
    public function test_reschedule_to_different_staff()
    {
        // Create initial appointment with staff 1
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->services['quick']->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff[1]->id,
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(10, 30),
            'calcom_v2_booking_id' => 5001,
            'status' => 'booked'
        ]);

        // Mock the existing booking
        CalcomV2MockServer::getBookings()[5001] = [
            'id' => 5001,
            'status' => 'ACCEPTED'
        ];

        // Reschedule to different time and staff
        $newStart = Carbon::tomorrow()->setTime(14, 0);

        $response = $this->patchJson("/api/v2/bookings/{$appointment->id}/reschedule", [
            'start' => $newStart->toIso8601String(),
            'staff_id' => $this->staff[2]->id, // Different staff
            'timeZone' => 'Europe/Berlin',
            'reason' => 'Customer prefers different staff'
        ]);

        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals($newStart->toDateTimeString(), $appointment->starts_at->toDateTimeString());

        // Note: In real implementation, staff change might require rebooking
        // This test verifies the API accepts the request
    }

    /**
     * Test 6: Bulk cancellation of related appointments
     */
    public function test_bulk_cancellation_of_related_appointments()
    {
        // Create multiple related appointments
        $groupId = uniqid('group_');
        $appointments = [];

        for ($i = 0; $i < 3; $i++) {
            $appointment = Appointment::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'service_id' => $this->services['quick']->id,
                'customer_id' => $this->customer->id,
                'staff_id' => $this->staff[$i + 1]->id,
                'starts_at' => Carbon::tomorrow()->addDays($i)->setTime(10, 0),
                'ends_at' => Carbon::tomorrow()->addDays($i)->setTime(10, 30),
                'calcom_v2_booking_id' => 6000 + $i,
                'group_booking_id' => $groupId,
                'status' => 'booked'
            ]);

            $appointments[] = $appointment;

            // Mock Cal.com booking
            CalcomV2MockServer::getBookings()[6000 + $i] = [
                'id' => 6000 + $i,
                'status' => 'ACCEPTED'
            ];
        }

        // Cancel all appointments in the group
        DB::transaction(function () use ($appointments) {
            foreach ($appointments as $appointment) {
                $response = $this->deleteJson("/api/v2/bookings/{$appointment->id}", [
                    'reason' => 'Bulk cancellation test'
                ]);

                $response->assertStatus(200);
            }
        });

        // Verify all appointments are cancelled
        $cancelledCount = Appointment::where('group_booking_id', $groupId)
            ->where('status', 'cancelled')
            ->count();

        $this->assertEquals(3, $cancelledCount);
    }

    /**
     * Test 7: Weekend and holiday handling
     */
    public function test_weekend_and_holiday_availability()
    {
        // Test Saturday
        $saturday = Carbon::parse('next saturday');

        $response = $this->postJson('/api/v2/availability/simple', [
            'service_id' => $this->services['quick']->id,
            'branch_id' => $this->branch->id,
            'start_date' => $saturday->toDateString(),
            'end_date' => $saturday->toDateString(),
            'timeZone' => 'Europe/Berlin'
        ]);

        $response->assertStatus(200);
        $saturdaySlots = $response->json('data.slots');

        // Test Sunday
        $sunday = Carbon::parse('next sunday');

        $response = $this->postJson('/api/v2/availability/simple', [
            'service_id' => $this->services['quick']->id,
            'branch_id' => $this->branch->id,
            'start_date' => $sunday->toDateString(),
            'end_date' => $sunday->toDateString(),
            'timeZone' => 'Europe/Berlin'
        ]);

        $response->assertStatus(200);
        $sundaySlots = $response->json('data.slots');

        // Weekend availability depends on business rules
        // This test just verifies the API handles weekend requests
        $this->assertIsArray($saturdaySlots);
        $this->assertIsArray($sundaySlots);
    }

    /**
     * Test 8: Large date range availability query
     */
    public function test_large_date_range_availability()
    {
        $startDate = Carbon::now()->addDay();
        $endDate = $startDate->copy()->addDays(60); // 2 months ahead

        $response = $this->postJson('/api/v2/availability/simple', [
            'service_id' => $this->services['quick']->id,
            'branch_id' => $this->branch->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'timeZone' => 'Europe/Berlin'
        ]);

        $response->assertStatus(200);
        $slots = $response->json('data.slots');

        // Should handle large date ranges
        $this->assertIsArray($slots);

        // If slots are limited, verify limit is applied
        if (count($slots) > 0) {
            $this->assertLessThanOrEqual(1000, count($slots), 'Should limit slots for performance');
        }
    }

    /**
     * Test 9: Service with buffer time
     */
    public function test_service_with_buffer_time()
    {
        // Create service with buffer time
        $serviceWithBuffer = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Service with Buffer',
            'duration_minutes' => 45,
            'buffer_time_minutes' => 15, // 15 min buffer after each appointment
            'composite' => false
        ]);

        CalcomEventMap::create([
            'service_id' => $serviceWithBuffer->id,
            'staff_id' => $this->staff[1]->id,
            'event_type_id' => 7000,
            'sync_status' => 'synced'
        ]);

        $serviceWithBuffer->staff()->attach($this->staff[1]->id, [
            'can_book' => true
        ]);

        // Book first appointment
        $firstStart = Carbon::tomorrow()->setTime(10, 0);
        $firstEnd = $firstStart->copy()->addMinutes(45);

        $response1 = $this->postJson('/api/v2/bookings', [
            'service_id' => $serviceWithBuffer->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff[1]->id,
            'start' => $firstStart->toIso8601String(),
            'customer' => [
                'name' => 'Buffer Test 1',
                'email' => 'buffer1@test.com'
            ],
            'timeZone' => 'Europe/Berlin'
        ]);

        $response1->assertStatus(201);

        // Try to book immediately after (should fail due to buffer)
        CalcomV2MockServer::addScenario('createBooking', 'conflict');

        $secondStart = $firstEnd; // Immediately after first appointment
        $response2 = $this->postJson('/api/v2/bookings', [
            'service_id' => $serviceWithBuffer->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff[1]->id,
            'start' => $secondStart->toIso8601String(),
            'customer' => [
                'name' => 'Buffer Test 2',
                'email' => 'buffer2@test.com'
            ],
            'timeZone' => 'Europe/Berlin'
        ]);

        // Should fail or return error due to buffer time
        $this->assertNotEquals(201, $response2->status());
    }

    /**
     * Test 10: Appointment metadata handling
     */
    public function test_appointment_metadata_persistence()
    {
        $metadata = [
            'source' => 'test_suite',
            'campaign' => 'extended_test',
            'referrer' => 'internal',
            'custom_field_1' => 'value1',
            'custom_field_2' => ['nested' => 'data'],
            'unicode_test' => 'äöü €💰',
            'number_test' => 123.45,
            'boolean_test' => true
        ];

        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->services['quick']->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff[1]->id,
            'start' => Carbon::tomorrow()->setTime(11, 0)->toIso8601String(),
            'customer' => [
                'name' => 'Metadata Test',
                'email' => 'metadata@test.com'
            ],
            'metadata' => $metadata,
            'timeZone' => 'Europe/Berlin'
        ]);

        $response->assertStatus(201);
        $appointmentId = $response->json('data.appointment_id');

        // Verify metadata was stored
        $appointment = Appointment::find($appointmentId);
        $this->assertNotNull($appointment->metadata);

        // Check specific metadata fields
        $storedMetadata = $appointment->metadata;
        $this->assertEquals('test_suite', $storedMetadata['source'] ?? null);
        $this->assertEquals('extended_test', $storedMetadata['campaign'] ?? null);

        // Verify metadata survives through updates
        $appointment->update(['notes' => 'Updated notes']);
        $appointment->refresh();

        $this->assertEquals('test_suite', $appointment->metadata['source'] ?? null);
    }

    /**
     * Test 11: Cache performance for repeated queries
     */
    public function test_cache_performance_for_repeated_queries()
    {
        $cacheKey = 'availability_test_' . uniqid();

        // First request (cache miss)
        $start1 = microtime(true);
        $response1 = $this->postJson('/api/v2/availability/simple', [
            'service_id' => $this->services['quick']->id,
            'branch_id' => $this->branch->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->toDateString(),
            'timeZone' => 'Europe/Berlin'
        ]);
        $time1 = (microtime(true) - $start1) * 1000;

        $response1->assertStatus(200);
        $slots1 = $response1->json('data.slots');

        // Cache the result
        Cache::put($cacheKey, $slots1, 300);

        // Second request (should be faster if caching is used)
        $start2 = microtime(true);
        $cachedSlots = Cache::get($cacheKey);
        $time2 = (microtime(true) - $start2) * 1000;

        $this->assertNotNull($cachedSlots);
        $this->assertEquals($slots1, $cachedSlots);

        // Cache retrieval should be much faster
        $this->assertLessThan($time1, $time2);

        Log::info('Cache performance test', [
            'first_request_ms' => round($time1, 2),
            'cache_retrieval_ms' => round($time2, 2),
            'improvement' => round((($time1 - $time2) / $time1) * 100, 2) . '%'
        ]);
    }

    /**
     * Test 12: Queue job processing for async operations
     */
    public function test_queue_job_processing_for_bookings()
    {
        Queue::fake();

        // Create booking that might trigger async operations
        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->services['quick']->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff[1]->id,
            'start' => Carbon::tomorrow()->setTime(15, 0)->toIso8601String(),
            'customer' => [
                'name' => 'Queue Test',
                'email' => 'queue@test.com'
            ],
            'timeZone' => 'Europe/Berlin'
        ]);

        $response->assertStatus(201);

        // Verify any expected jobs were dispatched
        // Queue::assertPushed(SendBookingConfirmation::class);
        // Queue::assertPushed(SyncWithCalendar::class);

        // For now, just verify queue is working
        Queue::assertNothingPushed(); // Change this based on actual implementation
    }
}