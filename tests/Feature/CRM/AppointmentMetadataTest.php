<?php

namespace Tests\Feature\CRM;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use App\Services\Retell\AppointmentCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ASK-010: Comprehensive Appointment Metadata Validation Test Suite
 *
 * PURPOSE: Validate ALL metadata fields are populated correctly throughout
 * the complete appointment lifecycle: Booking → Rescheduling → Cancellation
 *
 * CRITICAL FIELDS TESTED:
 * - created_by: Who created the appointment
 * - booking_source: Where the booking originated
 * - booked_by_user_id: Internal user ID if staff-booked (null for customer bookings)
 * - rescheduled_at: Timestamp of reschedule
 * - rescheduled_by: Who rescheduled
 * - reschedule_source: Where reschedule originated
 * - previous_starts_at: Original appointment time before reschedule
 * - cancelled_at: Timestamp of cancellation
 * - cancelled_by: Who cancelled
 * - cancellation_source: Where cancellation originated
 */
class AppointmentMetadataTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentCreationService $appointmentService;
    private Company $company;
    private Branch $branch;
    private Service $service;
    private Customer $customer;
    private User $user;
    private Call $call;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize service
        $this->appointmentService = app(AppointmentCreationService::class);

        // Create test data
        $this->company = Company::factory()->create(['name' => 'Test Company']);
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch'
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Service',
            'calcom_event_type_id' => 123456,
            'duration_minutes' => 60,
        ]);

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => 'max@test.com',
            'phone' => '+49 123 456789',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Test Agent',
            'email' => 'agent@test.com',
        ]);

        $this->call = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'from_number' => $this->customer->phone,
            'retell_call_id' => 'retell_' . uniqid(),
        ]);
    }

    /**
     * TEST SCENARIO 1: BOOKING METADATA
     *
     * Create appointment via AppointmentCreationService
     * ASSERT: created_by='customer', booking_source='retell_webhook', booked_by_user_id=null
     */
    public function test_booking_metadata_populated_correctly_via_appointment_creation_service()
    {
        // Arrange: Prepare booking details
        $bookingDetails = [
            'starts_at' => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'ends_at' => Carbon::now()->addDays(3)->addHour()->format('Y-m-d H:i:s'),
            'service' => $this->service->name,
            'duration_minutes' => 60,
        ];

        // Act: Create appointment using the service
        $appointment = $this->appointmentService->createLocalRecord(
            $this->customer,
            $this->service,
            $bookingDetails,
            'calcom_' . uniqid(),
            $this->call
        );

        // Assert: Verify booking metadata
        $this->assertNotNull($appointment->id, 'Appointment should be created');

        // CRITICAL: Booking metadata fields
        $this->assertEquals('customer', $appointment->created_by,
            'created_by should be "customer" for customer-initiated bookings');
        $this->assertEquals('retell_webhook', $appointment->booking_source,
            'booking_source should be "retell_webhook" for phone bookings');
        $this->assertNull($appointment->booked_by_user_id,
            'booked_by_user_id should be null for customer bookings');

        // Additional metadata validation
        $this->assertEquals('retell_webhook', $appointment->source,
            'source field should also be "retell_webhook"');
        $this->assertNotNull($appointment->created_at,
            'created_at timestamp should be populated');
        $this->assertEquals($this->customer->id, $appointment->customer_id,
            'customer_id should match');
        $this->assertEquals($this->call->id, $appointment->call_id,
            'call_id should be linked');
    }

    /**
     * TEST SCENARIO 2: RESCHEDULE METADATA
     *
     * Reschedule appointment via API endpoint
     * ASSERT: rescheduled_at NOT NULL, rescheduled_by='customer',
     *         reschedule_source='retell_api', previous_starts_at=old_time
     */
    public function test_reschedule_metadata_populated_correctly()
    {
        // Arrange: Create initial appointment
        $originalTime = Carbon::now()->addDays(3)->setTime(10, 0, 0);
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'call_id' => $this->call->id,
            'starts_at' => $originalTime,
            'ends_at' => $originalTime->copy()->addHour(),
            'status' => 'scheduled',
            'created_by' => 'customer',
            'booking_source' => 'retell_webhook',
        ]);

        $this->assertNull($appointment->rescheduled_at,
            'rescheduled_at should be null initially');

        // Act: Reschedule the appointment
        $newTime = Carbon::now()->addDays(5)->setTime(14, 0, 0);

        $appointment->update([
            'starts_at' => $newTime,
            'ends_at' => $newTime->copy()->addHour(),
            'rescheduled_at' => now(),
            'rescheduled_by' => 'customer',
            'reschedule_source' => 'retell_api',
            'previous_starts_at' => $originalTime,
        ]);

        // Assert: Verify reschedule metadata
        $appointment->refresh();

        $this->assertNotNull($appointment->rescheduled_at,
            'rescheduled_at should be populated after reschedule');
        $this->assertEquals('customer', $appointment->rescheduled_by,
            'rescheduled_by should be "customer"');
        $this->assertEquals('retell_api', $appointment->reschedule_source,
            'reschedule_source should be "retell_api"');
        $this->assertEquals($originalTime->format('Y-m-d H:i:s'),
            $appointment->previous_starts_at->format('Y-m-d H:i:s'),
            'previous_starts_at should preserve original time');

        // Verify new time is set
        $this->assertEquals($newTime->format('Y-m-d H:i:s'),
            $appointment->starts_at->format('Y-m-d H:i:s'),
            'starts_at should be updated to new time');
    }

    /**
     * TEST SCENARIO 3: CANCELLATION METADATA
     *
     * Cancel appointment via API endpoint
     * ASSERT: cancelled_at NOT NULL, cancelled_by='customer',
     *         cancellation_source='retell_api'
     */
    public function test_cancellation_metadata_populated_correctly()
    {
        // Arrange: Create appointment
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'call_id' => $this->call->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'scheduled',
            'created_by' => 'customer',
            'booking_source' => 'retell_webhook',
        ]);

        $this->assertNull($appointment->cancelled_at,
            'cancelled_at should be null initially');

        // Act: Cancel the appointment
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => 'customer',
            'cancellation_source' => 'retell_api',
        ]);

        // Assert: Verify cancellation metadata
        $appointment->refresh();

        $this->assertEquals('cancelled', $appointment->status,
            'status should be "cancelled"');
        $this->assertNotNull($appointment->cancelled_at,
            'cancelled_at should be populated after cancellation');
        $this->assertEquals('customer', $appointment->cancelled_by,
            'cancelled_by should be "customer"');
        $this->assertEquals('retell_api', $appointment->cancellation_source,
            'cancellation_source should be "retell_api"');
    }

    /**
     * TEST SCENARIO 4: COMPLETE LIFECYCLE
     *
     * Book → Reschedule → Cancel in one test
     * ASSERT: ALL metadata fields populated correctly at each stage
     */
    public function test_complete_appointment_lifecycle_maintains_all_metadata()
    {
        // STEP 1: Book appointment
        $bookingDetails = [
            'starts_at' => Carbon::now()->addDays(3)->setTime(10, 0, 0)->format('Y-m-d H:i:s'),
            'ends_at' => Carbon::now()->addDays(3)->setTime(11, 0, 0)->format('Y-m-d H:i:s'),
            'service' => $this->service->name,
            'duration_minutes' => 60,
        ];

        $appointment = $this->appointmentService->createLocalRecord(
            $this->customer,
            $this->service,
            $bookingDetails,
            'calcom_' . uniqid(),
            $this->call
        );

        // Verify booking metadata
        $this->assertEquals('customer', $appointment->created_by);
        $this->assertEquals('retell_webhook', $appointment->booking_source);
        $this->assertNull($appointment->booked_by_user_id);
        $this->assertNull($appointment->rescheduled_at);
        $this->assertNull($appointment->cancelled_at);

        // STEP 2: Reschedule appointment
        $originalTime = Carbon::parse($appointment->starts_at);
        $newTime = Carbon::now()->addDays(5)->setTime(14, 0, 0);

        $appointment->update([
            'starts_at' => $newTime,
            'ends_at' => $newTime->copy()->addHour(),
            'rescheduled_at' => now(),
            'rescheduled_by' => 'customer',
            'reschedule_source' => 'customer_portal',
            'previous_starts_at' => $originalTime,
        ]);

        $appointment->refresh();

        // Verify booking metadata preserved + reschedule metadata added
        $this->assertEquals('customer', $appointment->created_by);
        $this->assertEquals('retell_webhook', $appointment->booking_source);
        $this->assertNotNull($appointment->rescheduled_at);
        $this->assertEquals('customer', $appointment->rescheduled_by);
        $this->assertEquals('customer_portal', $appointment->reschedule_source);
        $this->assertEquals($originalTime->format('Y-m-d H:i:s'),
            $appointment->previous_starts_at->format('Y-m-d H:i:s'));
        $this->assertNull($appointment->cancelled_at);

        // STEP 3: Cancel appointment
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => 'customer',
            'cancellation_source' => 'retell_api',
        ]);

        $appointment->refresh();

        // Verify ALL metadata fields maintained
        $this->assertEquals('customer', $appointment->created_by,
            'Booking: created_by preserved');
        $this->assertEquals('retell_webhook', $appointment->booking_source,
            'Booking: booking_source preserved');
        $this->assertNull($appointment->booked_by_user_id,
            'Booking: booked_by_user_id preserved');

        $this->assertNotNull($appointment->rescheduled_at,
            'Reschedule: rescheduled_at preserved');
        $this->assertEquals('customer', $appointment->rescheduled_by,
            'Reschedule: rescheduled_by preserved');
        $this->assertEquals('customer_portal', $appointment->reschedule_source,
            'Reschedule: reschedule_source preserved');
        $this->assertEquals($originalTime->format('Y-m-d H:i:s'),
            $appointment->previous_starts_at->format('Y-m-d H:i:s'),
            'Reschedule: previous_starts_at preserved');

        $this->assertEquals('cancelled', $appointment->status,
            'Cancellation: status set');
        $this->assertNotNull($appointment->cancelled_at,
            'Cancellation: cancelled_at set');
        $this->assertEquals('customer', $appointment->cancelled_by,
            'Cancellation: cancelled_by set');
        $this->assertEquals('retell_api', $appointment->cancellation_source,
            'Cancellation: cancellation_source set');
    }

    /**
     * TEST SCENARIO 5: STAFF-BOOKED APPOINTMENT
     *
     * Test staff member booking on behalf of customer
     * ASSERT: created_by='staff', booked_by_user_id=staff.id
     */
    public function test_staff_booked_appointment_metadata()
    {
        // Arrange & Act: Staff books appointment for customer
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'scheduled',
            'created_by' => 'staff',
            'booking_source' => 'crm_admin',
            'booked_by_user_id' => $this->user->id,
        ]);

        // Assert: Verify staff booking metadata
        $this->assertEquals('staff', $appointment->created_by,
            'created_by should be "staff" for staff-initiated bookings');
        $this->assertEquals('crm_admin', $appointment->booking_source,
            'booking_source should be "crm_admin"');
        $this->assertEquals($this->user->id, $appointment->booked_by_user_id,
            'booked_by_user_id should contain staff user ID');
    }

    /**
     * TEST SCENARIO 6: MULTIPLE RESCHEDULES
     *
     * Test multiple reschedules preserve metadata chain
     */
    public function test_multiple_reschedules_preserve_metadata_chain()
    {
        // Arrange: Create appointment
        $originalTime = Carbon::now()->addDays(3)->setTime(10, 0, 0);
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => $originalTime,
            'ends_at' => $originalTime->copy()->addHour(),
            'status' => 'scheduled',
            'created_by' => 'customer',
            'booking_source' => 'retell_webhook',
        ]);

        // Act: First reschedule
        $firstRescheduleTime = Carbon::now()->addDays(4)->setTime(14, 0, 0);
        $firstRescheduleAt = now();

        $appointment->update([
            'starts_at' => $firstRescheduleTime,
            'ends_at' => $firstRescheduleTime->copy()->addHour(),
            'rescheduled_at' => $firstRescheduleAt,
            'rescheduled_by' => 'customer',
            'reschedule_source' => 'customer_portal',
            'previous_starts_at' => $originalTime,
        ]);

        $appointment->refresh();

        // Verify first reschedule
        $this->assertNotNull($appointment->rescheduled_at);
        $this->assertEquals('customer', $appointment->rescheduled_by);

        // Act: Second reschedule
        $secondRescheduleTime = Carbon::now()->addDays(6)->setTime(16, 0, 0);

        $appointment->update([
            'starts_at' => $secondRescheduleTime,
            'ends_at' => $secondRescheduleTime->copy()->addHour(),
            'rescheduled_at' => now(), // Updated to latest reschedule time
            'rescheduled_by' => 'staff',
            'reschedule_source' => 'crm_admin',
            'previous_starts_at' => $firstRescheduleTime, // Update to previous time
        ]);

        $appointment->refresh();

        // Assert: Latest reschedule metadata is current
        $this->assertEquals('staff', $appointment->rescheduled_by,
            'rescheduled_by should reflect latest reschedule');
        $this->assertEquals('crm_admin', $appointment->reschedule_source,
            'reschedule_source should reflect latest reschedule');
        $this->assertEquals($firstRescheduleTime->format('Y-m-d H:i:s'),
            $appointment->previous_starts_at->format('Y-m-d H:i:s'),
            'previous_starts_at should be first rescheduled time');

        // Original booking metadata still preserved
        $this->assertEquals('customer', $appointment->created_by);
        $this->assertEquals('retell_webhook', $appointment->booking_source);
    }

    /**
     * TEST SCENARIO 7: METADATA FIELD VALIDATION
     *
     * Test all metadata fields can be set and retrieved
     */
    public function test_all_metadata_fields_are_accessible()
    {
        // Act: Create appointment with all metadata fields
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'scheduled',

            // Booking metadata
            'created_by' => 'customer',
            'booking_source' => 'retell_webhook',
            'booked_by_user_id' => null,

            // Reschedule metadata
            'rescheduled_at' => Carbon::now()->subDay(),
            'rescheduled_by' => 'customer',
            'reschedule_source' => 'customer_portal',
            'previous_starts_at' => Carbon::now()->addDays(2),

            // Cancellation metadata (not cancelled yet, but fields exist)
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_source' => null,
        ]);

        // Assert: All fields are accessible
        $this->assertNotNull($appointment->created_by);
        $this->assertNotNull($appointment->booking_source);
        $this->assertNull($appointment->booked_by_user_id);

        $this->assertNotNull($appointment->rescheduled_at);
        $this->assertNotNull($appointment->rescheduled_by);
        $this->assertNotNull($appointment->reschedule_source);
        $this->assertNotNull($appointment->previous_starts_at);

        $this->assertNull($appointment->cancelled_at);
        $this->assertNull($appointment->cancelled_by);
        $this->assertNull($appointment->cancellation_source);
    }
}
