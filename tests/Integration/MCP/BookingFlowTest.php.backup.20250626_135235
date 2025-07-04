<?php

namespace Tests\Integration\MCP;

use App\Services\MCP\MCPOrchestrator;
use App\Services\Booking\UniversalBookingOrchestrator;
use App\Services\CalcomV2Service;
use App\Services\AppointmentService;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\CalcomEventType;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Staff $staff;
    private Service $service;
    private Customer $customer;
    private CalcomEventType $eventType;
    private MCPOrchestrator $orchestrator;
    private UniversalBookingOrchestrator $bookingOrchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data hierarchy
        $this->company = Company::factory()->create([
            'name' => 'Test Medical Practice',
            'calcom_api_key' => 'cal_test_key_123',
            'is_active' => true
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Downtown Clinic',
            'calcom_event_type_id' => 54321,
            'calcom_user_id' => 12345,
            'is_active' => true,
            'timezone' => 'Europe/Berlin'
        ]);
        
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Dr. Sarah Schmidt',
            'email' => 'dr.schmidt@example.com',
            'calcom_user_id' => 67890
        ]);
        
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'General Consultation',
            'duration' => 30,
            'price' => 80.00,
            'buffer_time' => 10
        ]);
        
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Smith',
            'phone' => '+491701234567',
            'email' => 'john.smith@example.com'
        ]);
        
        $this->eventType = CalcomEventType::factory()->create([
            'company_id' => $this->company->id,
            'calcom_event_type_id' => 54321,
            'title' => 'General Consultation',
            'slug' => 'general-consultation',
            'duration' => 30
        ]);
        
        // Attach staff to service
        $this->staff->services()->attach($this->service->id);
        
        $this->orchestrator = app(MCPOrchestrator::class);
        $this->bookingOrchestrator = app(UniversalBookingOrchestrator::class);
    }

    public function test_complete_booking_flow_with_availability_check()
    {
        Event::fake();
        
        // Mock Cal.com availability response
        Http::fake([
            'api.cal.com/v2/slots/available-slots*' => Http::response([
                'status' => 'success',
                'data' => [
                    'slots' => [
                        ['time' => '2025-06-25T10:00:00+02:00'],
                        ['time' => '2025-06-25T10:30:00+02:00'],
                        ['time' => '2025-06-25T11:00:00+02:00']
                    ]
                ]
            ], 200),
            'api.cal.com/v2/bookings' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 123456,
                    'uid' => 'booking_uid_123',
                    'startTime' => '2025-06-25T10:00:00+02:00',
                    'endTime' => '2025-06-25T10:30:00+02:00'
                ]
            ], 201)
        ]);
        
        // Step 1: Check availability
        $availabilityRequest = [
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'date' => '2025-06-25',
            'staff_id' => $this->staff->id
        ];
        
        $availability = $this->bookingOrchestrator->checkAvailability($availabilityRequest);
        
        $this->assertTrue($availability['success']);
        $this->assertCount(3, $availability['slots']);
        $this->assertContains('10:00', $availability['slots']);
        
        // Step 2: Create booking
        $bookingRequest = [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'start_time' => '2025-06-25 10:00:00',
            'notes' => 'First visit - general checkup'
        ];
        
        $result = $this->bookingOrchestrator->createBooking($bookingRequest);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('appointment_id', $result);
        $this->assertArrayHasKey('calcom_booking_id', $result);
        
        // Verify appointment was created
        $appointment = Appointment::find($result['appointment_id']);
        $this->assertNotNull($appointment);
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertEquals(123456, $appointment->calcom_booking_id);
        $this->assertEquals($this->customer->id, $appointment->customer_id);
        $this->assertEquals($this->staff->id, $appointment->staff_id);
        
        // Verify events were fired
        Event::assertDispatched('appointment.created');
        Event::assertDispatched('calcom.booking.created');
    }

    public function test_booking_flow_with_slot_collision()
    {
        // Create existing appointment
        $existingAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'start_time' => '2025-06-25 10:00:00',
            'end_time' => '2025-06-25 10:30:00',
            'status' => 'scheduled'
        ]);
        
        // Lock the slot
        Cache::put(
            "appointment_lock:{$this->branch->id}:2025-06-25 10:00:00",
            ['locked_by' => 'existing_booking'],
            300
        );
        
        // Try to book same slot
        $bookingRequest = [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'start_time' => '2025-06-25 10:00:00'
        ];
        
        $result = $this->bookingOrchestrator->createBooking($bookingRequest);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('SLOT_NOT_AVAILABLE', $result['error_code']);
        $this->assertStringContainsString('time slot is no longer available', $result['message']);
    }

    public function test_booking_flow_with_calcom_failure()
    {
        // Mock Cal.com API failure
        Http::fake([
            'api.cal.com/v2/bookings' => Http::response([
                'status' => 'error',
                'message' => 'Invalid time slot'
            ], 400)
        ]);
        
        $bookingRequest = [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'start_time' => '2025-06-25 10:00:00'
        ];
        
        $result = $this->bookingOrchestrator->createBooking($bookingRequest);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('EXTERNAL_API_ERROR', $result['error_code']);
        
        // Verify appointment was not created
        $appointmentCount = Appointment::where('customer_id', $this->customer->id)
            ->where('start_time', '2025-06-25 10:00:00')
            ->count();
        $this->assertEquals(0, $appointmentCount);
    }

    public function test_booking_flow_with_validation_errors()
    {
        $invalidRequests = [
            [
                'data' => ['customer_id' => 99999], // Non-existent customer
                'expected_error' => 'Customer not found'
            ],
            [
                'data' => ['start_time' => '2025-06-24 10:00:00'], // Past date
                'expected_error' => 'Cannot book appointments in the past'
            ],
            [
                'data' => ['service_id' => null], // Missing service
                'expected_error' => 'Service is required'
            ]
        ];
        
        foreach ($invalidRequests as $test) {
            $request = array_merge([
                'customer_id' => $this->customer->id,
                'service_id' => $this->service->id,
                'staff_id' => $this->staff->id,
                'branch_id' => $this->branch->id,
                'start_time' => '2025-06-25 10:00:00'
            ], $test['data']);
            
            $result = $this->bookingOrchestrator->createBooking($request);
            
            $this->assertFalse($result['success']);
            $this->assertStringContainsString($test['expected_error'], $result['message']);
        }
    }

    public function test_booking_flow_with_buffer_time()
    {
        // Create appointment that ends at 10:30
        Appointment::factory()->create([
            'staff_id' => $this->staff->id,
            'start_time' => '2025-06-25 10:00:00',
            'end_time' => '2025-06-25 10:30:00',
            'status' => 'scheduled'
        ]);
        
        // Try to book at 10:35 (within 10-minute buffer)
        $bookingRequest = [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'start_time' => '2025-06-25 10:35:00'
        ];
        
        $result = $this->bookingOrchestrator->createBooking($bookingRequest);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('buffer time', $result['message']);
        
        // Try to book at 10:40 (after buffer)
        $bookingRequest['start_time'] = '2025-06-25 10:40:00';
        
        Http::fake([
            'api.cal.com/v2/bookings' => Http::response([
                'status' => 'success',
                'data' => ['id' => 123457]
            ], 201)
        ]);
        
        $result = $this->bookingOrchestrator->createBooking($bookingRequest);
        
        $this->assertTrue($result['success']);
    }

    public function test_booking_flow_with_working_hours_validation()
    {
        // Set branch working hours (9 AM - 5 PM)
        $this->branch->update([
            'business_hours' => [
                'monday' => ['start' => '09:00', 'end' => '17:00'],
                'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                'thursday' => ['start' => '09:00', 'end' => '17:00'],
                'friday' => ['start' => '09:00', 'end' => '17:00'],
                'saturday' => null,
                'sunday' => null
            ]
        ]);
        
        // Try to book outside working hours
        $bookingRequest = [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'start_time' => '2025-06-25 18:00:00' // 6 PM - after hours
        ];
        
        $result = $this->bookingOrchestrator->createBooking($bookingRequest);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('outside business hours', $result['message']);
    }

    public function test_booking_flow_with_concurrent_requests()
    {
        $slot = '2025-06-25 14:00:00';
        $results = [];
        
        // Mock successful Cal.com responses
        Http::fake([
            'api.cal.com/v2/bookings' => Http::sequence()
                ->push(['status' => 'success', 'data' => ['id' => 1001]], 201)
                ->push(['status' => 'error', 'message' => 'Slot taken'], 409)
        ]);
        
        // Simulate concurrent booking attempts
        $processes = [];
        for ($i = 0; $i < 2; $i++) {
            $customer = Customer::factory()->create(['company_id' => $this->company->id]);
            
            $processes[] = [
                'customer_id' => $customer->id,
                'service_id' => $this->service->id,
                'staff_id' => $this->staff->id,
                'branch_id' => $this->branch->id,
                'start_time' => $slot
            ];
        }
        
        // Process bookings
        foreach ($processes as $request) {
            $results[] = $this->bookingOrchestrator->createBooking($request);
        }
        
        // Only one should succeed
        $successCount = collect($results)->filter(fn($r) => $r['success'])->count();
        $this->assertEquals(1, $successCount);
        
        // Verify only one appointment was created
        $appointmentCount = Appointment::where('start_time', $slot)->count();
        $this->assertEquals(1, $appointmentCount);
    }

    public function test_booking_cancellation_flow()
    {
        // Create appointment
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'calcom_booking_id' => 999888,
            'status' => 'scheduled'
        ]);
        
        // Mock Cal.com cancellation
        Http::fake([
            'api.cal.com/v2/bookings/999888' => Http::response(null, 204)
        ]);
        
        $result = $this->bookingOrchestrator->cancelBooking($appointment->id, 'Customer request');
        
        $this->assertTrue($result['success']);
        
        // Verify appointment status
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertStringContainsString('Customer request', $appointment->notes);
    }

    public function test_booking_rescheduling_flow()
    {
        // Create original appointment
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'start_time' => '2025-06-25 10:00:00',
            'calcom_booking_id' => 777666,
            'status' => 'scheduled'
        ]);
        
        // Mock Cal.com reschedule
        Http::fake([
            'api.cal.com/v2/bookings/777666' => Http::response(null, 204),
            'api.cal.com/v2/bookings' => Http::response([
                'status' => 'success',
                'data' => ['id' => 777667]
            ], 201)
        ]);
        
        $result = $this->bookingOrchestrator->rescheduleBooking(
            $appointment->id,
            '2025-06-26 14:00:00'
        );
        
        $this->assertTrue($result['success']);
        
        // Verify appointment was updated
        $appointment->refresh();
        $this->assertEquals('2025-06-26 14:00:00', $appointment->start_time);
        $this->assertEquals(777667, $appointment->calcom_booking_id);
    }
}