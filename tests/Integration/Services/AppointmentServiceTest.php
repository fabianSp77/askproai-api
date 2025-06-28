<?php

namespace Tests\Integration\Services;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentCreated;
use App\Events\AppointmentRescheduled;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Repositories\AppointmentRepository;
use App\Repositories\CustomerRepository;
use App\Services\AppointmentService;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AppointmentService $appointmentService;
    protected Company $company;
    protected Branch $branch;
    protected Staff $staff;
    protected Service $service;
    protected Customer $customer;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->for($this->company)->create();
        $this->staff = Staff::factory()->for($this->company)->for($this->branch)->create();
        $this->service = Service::factory()->for($this->company)->create([
            'duration' => 30,
            'price' => 50.00,
        ]);
        $this->customer = Customer::factory()->for($this->company)->create();
        
        // Create and authenticate user
        $this->user = User::factory()->for($this->company)->create();
        $this->actingAs($this->user);

        // Attach service to staff
        $this->staff->services()->attach($this->service);

        // Mock CalcomService
        $calcomServiceMock = Mockery::mock(CalcomService::class);
        $this->app->instance(CalcomService::class, $calcomServiceMock);

        // Create service instance with real repositories
        $this->appointmentService = new AppointmentService(
            new AppointmentRepository(),
            new CustomerRepository(),
            $calcomServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test complete appointment creation workflow
     */
    #[Test]
    public function test_creates_appointment_with_customer_and_fires_event()
    {
        Event::fake();
        Mail::fake();

        $appointmentData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '+491234567890',
            'customer_email' => 'john@example.com',
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addDay()->setTime(10, 0),
            'ends_at' => Carbon::now()->addDay()->setTime(10, 30),
            'price' => 50.00,
            'notes' => 'First appointment',
            'source' => 'phone',
        ];

        $appointment = $this->appointmentService->create($appointmentData);

        // Assert appointment was created
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'status' => 'scheduled',
            'price' => 50.00,
            'source' => 'phone',
        ]);

        // Assert customer was created
        $this->assertNotNull($appointment->customer);
        $this->assertEquals('John Doe', $appointment->customer->name);
        $this->assertEquals('+491234567890', $appointment->customer->phone);
        $this->assertEquals('john@example.com', $appointment->customer->email);

        // Assert event was fired
        Event::assertDispatched(AppointmentCreated::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id;
        });

        // Assert relationships are loaded
        $this->assertTrue($appointment->relationLoaded('customer'));
        $this->assertTrue($appointment->relationLoaded('staff'));
        $this->assertTrue($appointment->relationLoaded('service'));
        $this->assertTrue($appointment->relationLoaded('branch'));
    }

    /**
     * Test appointment creation with Cal.com integration
     */
    #[Test]
    public function test_creates_appointment_with_calcom_booking()
    {
        Event::fake();

        $calcomBookingId = 12345;
        $calcomEventTypeId = 999;

        // Setup Cal.com mock
        $calcomServiceMock = Mockery::mock(CalcomService::class);
        $calcomServiceMock->shouldReceive('createBooking')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['eventTypeId'] == 999 &&
                       isset($data['start']) &&
                       isset($data['responses']) &&
                       $data['responses']['name'] == 'Jane Smith' &&
                       $data['responses']['phone'] == '+499876543210';
            }))
            ->andReturn(['id' => $calcomBookingId]);

        $this->app->instance(CalcomService::class, $calcomServiceMock);

        $this->appointmentService = new AppointmentService(
            new AppointmentRepository(),
            new CustomerRepository(),
            $calcomServiceMock
        );

        $appointmentData = [
            'customer_name' => 'Jane Smith',
            'customer_phone' => '+499876543210',
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addDays(2)->setTime(14, 0),
            'ends_at' => Carbon::now()->addDays(2)->setTime(14, 30),
            'calcom_event_type_id' => $calcomEventTypeId,
        ];

        $appointment = $this->appointmentService->create($appointmentData);

        // Assert appointment has Cal.com booking ID
        $this->assertEquals($calcomBookingId, $appointment->calcom_booking_id);
        $this->assertEquals($calcomEventTypeId, $appointment->calcom_event_type_id);
    }

    /**
     * Test appointment creation fails when time slot is not available
     */
    #[Test]
    public function test_fails_to_create_appointment_when_slot_unavailable()
    {
        Event::fake();

        // Create existing appointment
        $existingAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $this->staff->id,
            'starts_at' => Carbon::now()->addDay()->setTime(10, 0),
            'ends_at' => Carbon::now()->addDay()->setTime(11, 0),
            'status' => 'scheduled',
        ]);

        $appointmentData = [
            'customer_name' => 'Test Customer',
            'customer_phone' => '+491111111111',
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addDay()->setTime(10, 30),
            'ends_at' => Carbon::now()->addDay()->setTime(11, 30),
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Time slot is not available');

        $this->appointmentService->create($appointmentData);

        // Assert no appointment was created
        $this->assertDatabaseMissing('appointments', [
            'customer_id' => Customer::where('phone', '+491111111111')->first()?->id,
        ]);

        // Assert no event was fired
        Event::assertNotDispatched(AppointmentCreated::class);
    }

    /**
     * Test appointment rescheduling workflow
     */
    #[Test]
    public function test_reschedules_appointment_and_updates_calcom()
    {
        Event::fake();

        // Create appointment with Cal.com booking
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'starts_at' => Carbon::now()->addDays(3)->setTime(10, 0),
            'ends_at' => Carbon::now()->addDays(3)->setTime(10, 30),
            'calcom_booking_id' => 54321,
            'status' => 'scheduled',
        ]);

        // Setup Cal.com mock
        $calcomServiceMock = Mockery::mock(CalcomService::class);
        $calcomServiceMock->shouldReceive('rescheduleBooking')
            ->once()
            ->with(54321, Mockery::type(Carbon::class))
            ->andReturn(true);

        $this->app->instance(CalcomService::class, $calcomServiceMock);

        $this->appointmentService = new AppointmentService(
            new AppointmentRepository(),
            new CustomerRepository(),
            $calcomServiceMock
        );

        $newStartTime = Carbon::now()->addDays(3)->setTime(14, 0);
        $updateData = [
            'starts_at' => $newStartTime,
            'ends_at' => $newStartTime->copy()->addMinutes(30),
        ];

        $updatedAppointment = $this->appointmentService->update($appointment->id, $updateData);

        // Assert appointment was updated
        $this->assertEquals($newStartTime->toDateTimeString(), $updatedAppointment->starts_at->toDateTimeString());

        // Assert event was fired
        Event::assertDispatched(AppointmentRescheduled::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id;
        });
    }

    /**
     * Test appointment cancellation workflow
     */
    #[Test]
    public function test_cancels_appointment_and_fires_event()
    {
        Event::fake();

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'status' => 'scheduled',
            'calcom_booking_id' => 67890,
        ]);

        // Setup Cal.com mock
        $calcomServiceMock = Mockery::mock(CalcomService::class);
        $calcomServiceMock->shouldReceive('cancelBooking')
            ->once()
            ->with(67890, 'Customer requested cancellation')
            ->andReturn(true);

        $this->app->instance(CalcomService::class, $calcomServiceMock);

        $this->appointmentService = new AppointmentService(
            new AppointmentRepository(),
            new CustomerRepository(),
            $calcomServiceMock
        );

        $result = $this->appointmentService->cancel($appointment->id, 'Customer requested cancellation');

        // Assert appointment was cancelled
        $this->assertTrue($result);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Customer requested cancellation',
        ]);

        // Assert cancelled_at timestamp is set
        $cancelledAppointment = Appointment::find($appointment->id);
        $this->assertNotNull($cancelledAppointment->cancelled_at);

        // Assert event was fired
        Event::assertDispatched(AppointmentCancelled::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id;
        });
    }

    /**
     * Test transaction rollback on Cal.com failure
     */
    #[Test]
    public function test_rolls_back_appointment_creation_on_calcom_failure()
    {
        Event::fake();

        // Setup Cal.com mock to throw exception
        $calcomServiceMock = Mockery::mock(CalcomService::class);
        $calcomServiceMock->shouldReceive('createBooking')
            ->once()
            ->andThrow(new \Exception('Cal.com API error'));

        $this->app->instance(CalcomService::class, $calcomServiceMock);

        $this->appointmentService = new AppointmentService(
            new AppointmentRepository(),
            new CustomerRepository(),
            $calcomServiceMock
        );

        $appointmentData = [
            'customer_name' => 'Failed Booking',
            'customer_phone' => '+492222222222',
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addDays(5)->setTime(10, 0),
            'ends_at' => Carbon::now()->addDays(5)->setTime(10, 30),
            'calcom_event_type_id' => 888,
        ];

        // Should not throw exception, but log error
        $appointment = $this->appointmentService->create($appointmentData);

        // Assert appointment was created without Cal.com booking
        $this->assertNotNull($appointment);
        $this->assertNull($appointment->calcom_booking_id);
        
        // Assert customer was still created
        $this->assertDatabaseHas('customers', [
            'name' => 'Failed Booking',
            'phone' => '+492222222222',
        ]);

        // Assert event was still fired
        Event::assertDispatched(AppointmentCreated::class);
    }

    /**
     * Test marking appointment as no-show
     */
    #[Test]
    public function test_marks_appointment_as_no_show_and_updates_customer()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'no_show_count' => 1,
        ]);

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'scheduled',
        ]);

        $result = $this->appointmentService->markAsNoShow($appointment->id);

        // Assert appointment status updated
        $this->assertTrue($result);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'no_show',
        ]);

        // Assert no_show_at timestamp is set
        $noShowAppointment = Appointment::find($appointment->id);
        $this->assertNotNull($noShowAppointment->no_show_at);

        // Assert customer no-show count incremented
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'no_show_count' => 2,
        ]);
    }

    /**
     * Test getting available time slots
     */
    #[Test]
    public function test_gets_available_time_slots_excluding_booked_appointments()
    {
        // Create some existing appointments
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $this->staff->id,
            'starts_at' => Carbon::today()->setTime(10, 0),
            'ends_at' => Carbon::today()->setTime(11, 0),
            'status' => 'scheduled',
        ]);

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $this->staff->id,
            'starts_at' => Carbon::today()->setTime(14, 0),
            'ends_at' => Carbon::today()->setTime(15, 0),
            'status' => 'scheduled',
        ]);

        $slots = $this->appointmentService->getAvailableSlots(
            $this->staff->id,
            Carbon::today(),
            30 // 30 minute slots
        );

        // Assert slots structure
        $this->assertIsArray($slots);
        $this->assertNotEmpty($slots);

        // Assert specific slots are available
        $slotTimes = array_column($slots, 'start');
        $this->assertContains('09:00', $slotTimes); // Before first appointment
        $this->assertContains('11:00', $slotTimes); // After first appointment
        $this->assertContains('13:30', $slotTimes); // Before second appointment
        $this->assertContains('15:00', $slotTimes); // After second appointment

        // Assert booked slots are not available
        $this->assertNotContains('10:00', $slotTimes);
        $this->assertNotContains('10:30', $slotTimes);
        $this->assertNotContains('14:00', $slotTimes);
        $this->assertNotContains('14:30', $slotTimes);

        // Assert slot structure
        foreach ($slots as $slot) {
            $this->assertArrayHasKey('start', $slot);
            $this->assertArrayHasKey('end', $slot);
            $this->assertArrayHasKey('datetime', $slot);
        }
    }

    /**
     * Test appointment statistics calculation
     */
    #[Test]
    public function test_calculates_appointment_statistics()
    {
        // Create various appointments
        $appointments = [
            // Completed appointments
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'completed',
                'price' => 100,
                'starts_at' => Carbon::now()->subDays(5),
            ]),
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'completed',
                'price' => 150,
                'starts_at' => Carbon::now()->subDays(3),
            ]),
            // Cancelled appointment
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'cancelled',
                'price' => 75,
                'starts_at' => Carbon::now()->subDays(2),
            ]),
            // No-show
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'no_show',
                'price' => 50,
                'starts_at' => Carbon::now()->subDay(),
            ]),
        ];

        $stats = $this->appointmentService->getStatistics(
            Carbon::now()->subWeek(),
            Carbon::now()
        );

        // Assert statistics structure and values
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_appointments', $stats);
        $this->assertArrayHasKey('completed_appointments', $stats);
        $this->assertArrayHasKey('cancelled_appointments', $stats);
        $this->assertArrayHasKey('no_show_appointments', $stats);
        $this->assertArrayHasKey('total_revenue', $stats);
        $this->assertArrayHasKey('average_appointment_value', $stats);

        $this->assertEquals(4, $stats['total_appointments']);
        $this->assertEquals(2, $stats['completed_appointments']);
        $this->assertEquals(1, $stats['cancelled_appointments']);
        $this->assertEquals(1, $stats['no_show_appointments']);
        $this->assertEquals(250, $stats['total_revenue']); // 100 + 150
        $this->assertEquals(125, $stats['average_appointment_value']); // 250 / 2
    }

    /**
     * Test complete appointment workflow
     */
    #[Test]
    public function test_completes_appointment_with_additional_data()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
            'price' => 75,
        ]);

        $completionData = [
            'actual_price' => 85,
            'payment_method' => 'cash',
            'notes' => 'Customer paid extra for additional service',
        ];

        $result = $this->appointmentService->complete($appointment->id, $completionData);

        // Assert appointment was completed
        $this->assertTrue($result);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'completed',
            'actual_price' => 85,
            'payment_method' => 'cash',
        ]);

        // Assert completed_at timestamp is set
        $completedAppointment = Appointment::find($appointment->id);
        $this->assertNotNull($completedAppointment->completed_at);
    }

    /**
     * Test availability check with excluded appointment
     */
    #[Test]
    public function test_checks_availability_excluding_specific_appointment()
    {
        // Create existing appointment
        $existingAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $this->staff->id,
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(11, 0),
            'status' => 'scheduled',
        ]);

        // Check availability for the same time slot, excluding the existing appointment
        $isAvailable = $this->appointmentService->checkAvailability(
            $this->staff->id,
            Carbon::tomorrow()->setTime(10, 0),
            Carbon::tomorrow()->setTime(11, 0),
            $existingAppointment->id
        );

        // Should be available when excluding the existing appointment
        $this->assertTrue($isAvailable);

        // Check without excluding - should not be available
        $isAvailable = $this->appointmentService->checkAvailability(
            $this->staff->id,
            Carbon::tomorrow()->setTime(10, 0),
            Carbon::tomorrow()->setTime(11, 0)
        );

        $this->assertFalse($isAvailable);
    }

    /**
     * Test creating appointment with existing customer by phone
     */
    #[Test]
    public function test_creates_appointment_with_existing_customer_by_phone()
    {
        Event::fake();

        // Create existing customer
        $existingCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+493333333333',
            'name' => 'Existing Customer',
            'email' => 'existing@example.com',
        ]);

        $appointmentData = [
            'customer_name' => 'Different Name', // Should use existing customer
            'customer_phone' => '+493333333333',
            'customer_email' => 'different@example.com', // Should keep existing email
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'starts_at' => Carbon::tomorrow()->setTime(11, 0),
            'ends_at' => Carbon::tomorrow()->setTime(11, 30),
        ];

        $appointment = $this->appointmentService->create($appointmentData);

        // Assert existing customer was used
        $this->assertEquals($existingCustomer->id, $appointment->customer_id);
        $this->assertEquals('Existing Customer', $appointment->customer->name);
        $this->assertEquals('existing@example.com', $appointment->customer->email);

        // Assert no new customer was created
        $this->assertEquals(1, Customer::where('phone', '+493333333333')->count());
    }
}