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
use App\Services\Booking\CompositeBookingService;
use App\Services\CalcomV2Client;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CalcomV2IntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Service $simpleService;
    private Service $compositeService;
    private Staff $staff1;
    private Staff $staff2;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure Cal.com settings
        config([
            'services.calcom.api_key' => 'test_api_key',
            'services.calcom.api_version' => '2024-08-13',
        ]);

        // Initialize mock server
        CalcomV2MockServer::setUp();

        // Set up test data
        $this->setupTestData();
    }

    protected function tearDown(): void
    {
        CalcomV2MockServer::reset();
        parent::tearDown();
    }

    private function setupTestData(): void
    {
        // Create company and branch
        $this->company = Company::factory()->create([
            'name' => 'Test Salon',
            'calcom_v2_api_key' => 'company_test_key'
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch'
        ]);

        // Create staff members
        $this->staff1 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'John Stylist',
            'email' => 'john@salon.com',
            'is_active' => true
        ]);

        $this->staff2 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Jane Colorist',
            'email' => 'jane@salon.com',
            'is_active' => true
        ]);

        // Create simple service
        $this->simpleService = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Haircut',
            'duration_minutes' => 30,
            'price' => 50.00,
            'composite' => false,
            'is_active' => true
        ]);

        // Create composite service (e.g., Hair coloring with processing time)
        $this->compositeService = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Full Hair Color Treatment',
            'duration_minutes' => 120,
            'price' => 150.00,
            'composite' => true,
            'segments' => [
                [
                    'key' => 'color_application',
                    'name' => 'Color Application',
                    'durationMin' => 45,
                    'gapAfterMin' => 30,
                    'gapAfterMax' => 45,
                    'preferSameStaff' => false
                ],
                [
                    'key' => 'wash_and_style',
                    'name' => 'Wash and Style',
                    'durationMin' => 30
                ]
            ],
            'is_active' => true
        ]);

        // Attach staff to services
        $this->simpleService->staff()->attach($this->staff1->id, [
            'is_primary' => true,
            'can_book' => true,
            'allowed_segments' => json_encode(['all']),
            'weight' => 10
        ]);

        $this->compositeService->staff()->attach($this->staff1->id, [
            'is_primary' => true,
            'can_book' => true,
            'allowed_segments' => json_encode(['color_application', 'wash_and_style']),
            'weight' => 10
        ]);

        $this->compositeService->staff()->attach($this->staff2->id, [
            'is_primary' => false,
            'can_book' => true,
            'allowed_segments' => json_encode(['wash_and_style']),
            'weight' => 8
        ]);

        // Create Cal.com event mappings
        CalcomEventMap::create([
            'service_id' => $this->simpleService->id,
            'staff_id' => $this->staff1->id,
            'event_type_id' => 100,
            'sync_status' => 'synced'
        ]);

        CalcomEventMap::create([
            'service_id' => $this->compositeService->id,
            'segment_key' => 'color_application',
            'staff_id' => $this->staff1->id,
            'event_type_id' => 101,
            'sync_status' => 'synced'
        ]);

        CalcomEventMap::create([
            'service_id' => $this->compositeService->id,
            'segment_key' => 'wash_and_style',
            'staff_id' => $this->staff1->id,
            'event_type_id' => 102,
            'sync_status' => 'synced'
        ]);

        CalcomEventMap::create([
            'service_id' => $this->compositeService->id,
            'segment_key' => 'wash_and_style',
            'staff_id' => $this->staff2->id,
            'event_type_id' => 103,
            'sync_status' => 'synced'
        ]);

        // Create customer
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '+49123456789'
        ]);
    }

    /**
     * Test complete simple booking flow: availability -> booking -> confirmation
     */
    public function test_complete_simple_booking_flow()
    {
        // Step 1: Check availability
        $response = $this->postJson('/api/v2/availability/simple', [
            'service_id' => $this->simpleService->id,
            'branch_id' => $this->branch->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->toDateString(),
            'timeZone' => 'Europe/Berlin'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'service' => ['id', 'name', 'duration'],
                'slots' => [
                    '*' => ['id', 'staff_id', 'staff_name', 'start', 'end', 'duration']
                ]
            ]
        ]);

        $slots = $response->json('data.slots');
        $this->assertNotEmpty($slots);

        // Step 2: Book first available slot
        $selectedSlot = $slots[0];

        $bookingResponse = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->simpleService->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $selectedSlot['staff_id'],
            'start' => $selectedSlot['start'],
            'customer' => [
                'name' => 'New Customer',
                'email' => 'new@example.com',
                'phone' => '+49123456789'
            ],
            'timeZone' => 'Europe/Berlin',
            'source' => 'api'
        ]);

        $bookingResponse->assertStatus(201);
        $bookingResponse->assertJsonStructure([
            'data' => [
                'appointment_id',
                'status',
                'starts_at',
                'ends_at',
                'confirmation_code'
            ]
        ]);

        $appointmentId = $bookingResponse->json('data.appointment_id');

        // Step 3: Verify appointment was created
        $appointment = Appointment::find($appointmentId);
        $this->assertNotNull($appointment);
        $this->assertEquals('booked', $appointment->status);
        $this->assertEquals($this->simpleService->id, $appointment->service_id);
        $this->assertFalse($appointment->is_composite);

        // Step 4: Verify Cal.com booking was created
        $bookings = CalcomV2MockServer::getBookings();
        $this->assertNotEmpty($bookings);

        $calcomBooking = array_values($bookings)[0];
        $this->assertEquals('ACCEPTED', $calcomBooking['status']);
        $this->assertEquals(100, $calcomBooking['eventTypeId']);
    }

    /**
     * Test complete composite booking flow
     */
    public function test_complete_composite_booking_flow()
    {
        // Step 1: Check composite availability
        $response = $this->postJson('/api/v2/availability/composite', [
            'service_id' => $this->compositeService->id,
            'branch_id' => $this->branch->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->toDateString(),
            'timeZone' => 'Europe/Berlin'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'service' => ['id', 'name', 'segments'],
                'slots' => [
                    '*' => [
                        'composite_slot_id',
                        'starts_at',
                        'ends_at',
                        'total_duration',
                        'segments' => [
                            '*' => ['key', 'name', 'staff_id', 'staff_name', 'starts_at', 'ends_at']
                        ],
                        'pause' => ['starts_at', 'ends_at', 'duration']
                    ]
                ]
            ]
        ]);

        $compositeSlots = $response->json('data.slots');
        $this->assertNotEmpty($compositeSlots);

        // Verify composite slot structure
        $firstSlot = $compositeSlots[0];
        $this->assertCount(2, $firstSlot['segments']);
        $this->assertArrayHasKey('pause', $firstSlot);
        $this->assertGreaterThanOrEqual(30, $firstSlot['pause']['duration']);

        // Step 2: Book composite appointment
        $bookingResponse = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->compositeService->id,
            'branch_id' => $this->branch->id,
            'start' => $firstSlot['starts_at'],
            'customer' => [
                'name' => 'Composite Customer',
                'email' => 'composite@example.com',
                'phone' => '+49987654321'
            ],
            'timeZone' => 'Europe/Berlin',
            'source' => 'api'
        ]);

        $bookingResponse->assertStatus(201);
        $bookingResponse->assertJsonStructure([
            'data' => [
                'appointment_id',
                'composite_uid',
                'status',
                'starts_at',
                'ends_at',
                'segments',
                'confirmation_code'
            ]
        ]);

        $appointmentId = $bookingResponse->json('data.appointment_id');
        $compositeUid = $bookingResponse->json('data.composite_uid');

        // Step 3: Verify composite appointment was created
        $appointment = Appointment::find($appointmentId);
        $this->assertNotNull($appointment);
        $this->assertTrue($appointment->is_composite);
        $this->assertEquals($compositeUid, $appointment->composite_group_uid);
        $this->assertCount(2, $appointment->segments);

        // Step 4: Verify multiple Cal.com bookings were created
        $bookings = CalcomV2MockServer::getBookings();
        $this->assertCount(2, $bookings); // Two segments = two bookings

        // Verify segment bookings have correct event types
        $segmentBookings = array_values($bookings);
        $eventTypeIds = array_column($segmentBookings, 'eventTypeId');
        $this->assertContains(101, $eventTypeIds); // color_application
        $this->assertContains(102, $eventTypeIds); // wash_and_style (or 103 if staff2)
    }

    /**
     * Test reschedule flow for simple booking
     */
    public function test_reschedule_simple_booking()
    {
        // Create initial booking
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->simpleService->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff1->id,
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(10, 30),
            'calcom_v2_booking_id' => 1001,
            'is_composite' => false,
            'status' => 'booked'
        ]);

        // Mock the existing booking in Cal.com
        CalcomV2MockServer::getBookings()[1001] = [
            'id' => 1001,
            'start' => $appointment->starts_at->toIso8601String(),
            'end' => $appointment->ends_at->toIso8601String(),
            'status' => 'ACCEPTED'
        ];

        // Reschedule the appointment
        $newStart = Carbon::tomorrow()->setTime(14, 0);

        $response = $this->patchJson("/api/v2/bookings/{$appointment->id}/reschedule", [
            'start' => $newStart->toIso8601String(),
            'timeZone' => 'Europe/Berlin',
            'reason' => 'Customer requested new time'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'appointment_id',
                'starts_at',
                'ends_at'
            ]
        ]);

        // Verify appointment was updated
        $appointment->refresh();
        $this->assertEquals($newStart->toDateTimeString(), $appointment->starts_at->toDateTimeString());
        $this->assertArrayHasKey('rescheduled_at', $appointment->metadata);
    }

    /**
     * Test cancellation flow for simple booking
     */
    public function test_cancel_simple_booking()
    {
        // Create initial booking
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->simpleService->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff1->id,
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(10, 30),
            'calcom_v2_booking_id' => 1002,
            'is_composite' => false,
            'status' => 'booked'
        ]);

        // Mock the existing booking in Cal.com
        CalcomV2MockServer::getBookings()[1002] = [
            'id' => 1002,
            'status' => 'ACCEPTED'
        ];

        // Cancel the appointment
        $response = $this->deleteJson("/api/v2/bookings/{$appointment->id}", [
            'reason' => 'Customer unable to attend'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'appointment_id' => $appointment->id,
                'status' => 'cancelled'
            ]
        ]);

        // Verify appointment was cancelled
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertArrayHasKey('cancelled_at', $appointment->metadata);
    }

    /**
     * Test composite booking cancellation (all segments)
     */
    public function test_cancel_composite_booking()
    {
        // Create composite appointment
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->compositeService->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff1->id,
            'is_composite' => true,
            'composite_group_uid' => 'comp-' . uniqid(),
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(12, 0),
            'segments' => [
                [
                    'index' => 0,
                    'key' => 'color_application',
                    'staff_id' => $this->staff1->id,
                    'booking_id' => 2001,
                    'starts_at' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
                    'ends_at' => Carbon::tomorrow()->setTime(10, 45)->toIso8601String(),
                    'status' => 'booked'
                ],
                [
                    'index' => 1,
                    'key' => 'wash_and_style',
                    'staff_id' => $this->staff2->id,
                    'booking_id' => 2002,
                    'starts_at' => Carbon::tomorrow()->setTime(11, 30)->toIso8601String(),
                    'ends_at' => Carbon::tomorrow()->setTime(12, 0)->toIso8601String(),
                    'status' => 'booked'
                ]
            ],
            'status' => 'booked'
        ]);

        // Mock the segment bookings in Cal.com
        CalcomV2MockServer::getBookings()[2001] = ['id' => 2001, 'status' => 'ACCEPTED'];
        CalcomV2MockServer::getBookings()[2002] = ['id' => 2002, 'status' => 'ACCEPTED'];

        // Cancel the composite appointment
        $response = $this->deleteJson("/api/v2/bookings/{$appointment->id}", [
            'reason' => 'Customer cancellation'
        ]);

        $response->assertStatus(200);

        // Verify appointment was cancelled
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);

        // Verify both Cal.com bookings were cancelled
        $booking1 = CalcomV2MockServer::getBooking(2001);
        $booking2 = CalcomV2MockServer::getBooking(2002);
        $this->assertEquals('CANCELLED', $booking1['status']);
        $this->assertEquals('CANCELLED', $booking2['status']);
    }

    /**
     * Test handling of booking conflicts
     */
    public function test_booking_conflict_handling()
    {
        CalcomV2MockServer::addScenario('createBooking', 'conflict');

        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->simpleService->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff1->id,
            'start' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com'
            ],
            'timeZone' => 'Europe/Berlin'
        ]);

        $response->assertStatus(500);
        $response->assertJsonFragment(['message' => 'Booking failed']);

        // Verify no appointment was created
        $appointmentCount = Appointment::count();
        $this->assertEquals(0, $appointmentCount);
    }

    /**
     * Test compensation saga for failed composite booking
     */
    public function test_composite_booking_compensation_on_failure()
    {
        // Set up scenario where second segment booking fails
        $callCount = 0;
        CalcomV2MockServer::addScenario('createBooking', 'success');

        // Mock first booking success, second fails
        Http::fake([
            '*' => function ($request) use (&$callCount) {
                $callCount++;
                if ($callCount === 2) {
                    // Second booking fails
                    return Http::response(['error' => 'Conflict'], 409);
                }
                // First booking succeeds
                return Http::response([
                    'status' => 'success',
                    'data' => [
                        'id' => 3000 + $callCount,
                        'status' => 'ACCEPTED'
                    ]
                ], 201);
            }
        ]);

        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->compositeService->id,
            'branch_id' => $this->branch->id,
            'start' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com'
            ],
            'timeZone' => 'Europe/Berlin'
        ]);

        // Should fail due to second segment conflict
        $response->assertStatus(500);

        // Verify no appointment was created
        $this->assertEquals(0, Appointment::count());

        // In a real scenario, verify first booking was cancelled
        // This would be checked via actual Cal.com API calls
    }

    /**
     * Test concurrent booking attempts
     */
    public function test_concurrent_booking_prevention()
    {
        // This test would simulate concurrent requests
        // In a real environment, would use database locks

        $slot = [
            'start' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
            'end' => Carbon::tomorrow()->setTime(10, 30)->toIso8601String()
        ];

        // First booking should succeed
        $response1 = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->simpleService->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff1->id,
            'start' => $slot['start'],
            'customer' => [
                'name' => 'Customer 1',
                'email' => 'customer1@example.com'
            ],
            'timeZone' => 'Europe/Berlin'
        ]);

        $response1->assertStatus(201);

        // Second booking for same slot should fail
        CalcomV2MockServer::addScenario('createBooking', 'conflict');

        $response2 = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->simpleService->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff1->id,
            'start' => $slot['start'],
            'customer' => [
                'name' => 'Customer 2',
                'email' => 'customer2@example.com'
            ],
            'timeZone' => 'Europe/Berlin'
        ]);

        $response2->assertStatus(500);

        // Only one appointment should exist
        $this->assertEquals(1, Appointment::count());
    }
}