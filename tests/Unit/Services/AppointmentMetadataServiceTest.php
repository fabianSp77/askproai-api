<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ASK-010: Unit Tests for Appointment Metadata Service Operations
 *
 * PURPOSE: Test metadata handling logic in isolation
 *
 * COVERAGE:
 * - Metadata field population during creation
 * - Metadata preservation during updates
 * - Metadata validation and constraints
 * - Edge cases and error handling
 */
class AppointmentMetadataServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Service $service;
    private Customer $customer;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test fixtures
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->user = User::factory()->create();
    }

    /**
     * UNIT TEST: Booking metadata initialization
     */
    public function test_booking_metadata_defaults_are_applied()
    {
        // Act: Create appointment without explicit metadata
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'scheduled',
        ]);

        // Assert: Default metadata should not interfere
        $this->assertNull($appointment->created_by);
        $this->assertNull($appointment->booking_source);
        $this->assertNull($appointment->booked_by_user_id);
    }

    /**
     * UNIT TEST: Explicit booking metadata is preserved
     */
    public function test_explicit_booking_metadata_is_preserved()
    {
        // Act: Create appointment with explicit metadata
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'scheduled',
            'created_by' => 'customer',
            'booking_source' => 'retell_webhook',
            'booked_by_user_id' => null,
        ]);

        // Assert: Explicit values preserved
        $this->assertEquals('customer', $appointment->created_by);
        $this->assertEquals('retell_webhook', $appointment->booking_source);
        $this->assertNull($appointment->booked_by_user_id);
    }

    /**
     * UNIT TEST: Reschedule metadata updates correctly
     */
    public function test_reschedule_metadata_updates_correctly()
    {
        // Arrange: Create base appointment
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

        // Act: Update with reschedule metadata
        $newTime = Carbon::now()->addDays(5)->setTime(14, 0, 0);
        $rescheduledAt = now();

        $appointment->update([
            'starts_at' => $newTime,
            'ends_at' => $newTime->copy()->addHour(),
            'rescheduled_at' => $rescheduledAt,
            'rescheduled_by' => 'customer',
            'reschedule_source' => 'customer_portal',
            'previous_starts_at' => $originalTime,
        ]);

        // Assert: Reschedule metadata populated
        $appointment->refresh();

        $this->assertEquals($rescheduledAt->format('Y-m-d H:i:s'),
            $appointment->rescheduled_at->format('Y-m-d H:i:s'));
        $this->assertEquals('customer', $appointment->rescheduled_by);
        $this->assertEquals('customer_portal', $appointment->reschedule_source);
        $this->assertEquals($originalTime->format('Y-m-d H:i:s'),
            $appointment->previous_starts_at->format('Y-m-d H:i:s'));

        // Original booking metadata preserved
        $this->assertEquals('customer', $appointment->created_by);
        $this->assertEquals('retell_webhook', $appointment->booking_source);
    }

    /**
     * UNIT TEST: Cancellation metadata updates correctly
     */
    public function test_cancellation_metadata_updates_correctly()
    {
        // Arrange: Create appointment
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'scheduled',
            'created_by' => 'customer',
            'booking_source' => 'retell_webhook',
        ]);

        // Act: Cancel appointment
        $cancelledAt = now();
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => $cancelledAt,
            'cancelled_by' => 'customer',
            'cancellation_source' => 'retell_api',
        ]);

        // Assert: Cancellation metadata populated
        $appointment->refresh();

        $this->assertEquals('cancelled', $appointment->status);
        $this->assertEquals($cancelledAt->format('Y-m-d H:i:s'),
            $appointment->cancelled_at->format('Y-m-d H:i:s'));
        $this->assertEquals('customer', $appointment->cancelled_by);
        $this->assertEquals('retell_api', $appointment->cancellation_source);

        // Original booking metadata preserved
        $this->assertEquals('customer', $appointment->created_by);
        $this->assertEquals('retell_webhook', $appointment->booking_source);
    }

    /**
     * UNIT TEST: Metadata field types are correct
     */
    public function test_metadata_field_types_are_correct()
    {
        // Act: Create appointment with all metadata
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'scheduled',
            'created_by' => 'customer',
            'booking_source' => 'retell_webhook',
            'booked_by_user_id' => $this->user->id,
            'rescheduled_at' => now(),
            'rescheduled_by' => 'customer',
            'reschedule_source' => 'customer_portal',
            'previous_starts_at' => Carbon::now()->addDays(2),
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_source' => null,
        ]);

        // Assert: Field types
        $this->assertIsString($appointment->created_by);
        $this->assertIsString($appointment->booking_source);
        $this->assertIsInt($appointment->booked_by_user_id);

        $this->assertInstanceOf(Carbon::class, $appointment->rescheduled_at);
        $this->assertIsString($appointment->rescheduled_by);
        $this->assertIsString($appointment->reschedule_source);
        $this->assertInstanceOf(Carbon::class, $appointment->previous_starts_at);

        $this->assertNull($appointment->cancelled_at);
        $this->assertNull($appointment->cancelled_by);
        $this->assertNull($appointment->cancellation_source);
    }

    /**
     * UNIT TEST: Metadata preserved through status changes
     */
    public function test_metadata_preserved_through_status_changes()
    {
        // Arrange: Create appointment
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'scheduled',
            'created_by' => 'customer',
            'booking_source' => 'retell_webhook',
        ]);

        // Act: Change status multiple times
        $appointment->update(['status' => 'confirmed']);
        $appointment->refresh();
        $this->assertEquals('customer', $appointment->created_by);

        $appointment->update(['status' => 'rescheduled']);
        $appointment->refresh();
        $this->assertEquals('customer', $appointment->created_by);

        $appointment->update(['status' => 'completed']);
        $appointment->refresh();
        $this->assertEquals('customer', $appointment->created_by);

        // Assert: Metadata preserved through all status changes
        $this->assertEquals('customer', $appointment->created_by);
        $this->assertEquals('retell_webhook', $appointment->booking_source);
    }

    /**
     * UNIT TEST: Multiple metadata sources handled correctly
     */
    public function test_multiple_metadata_sources_handled_correctly()
    {
        // Test various booking sources
        $sources = [
            ['created_by' => 'customer', 'source' => 'retell_webhook'],
            ['created_by' => 'staff', 'source' => 'crm_admin'],
            ['created_by' => 'customer', 'source' => 'customer_portal'],
            ['created_by' => 'system', 'source' => 'api'],
            ['created_by' => 'customer', 'source' => 'calcom_webhook'],
        ];

        foreach ($sources as $metadata) {
            $appointment = Appointment::create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'service_id' => $this->service->id,
                'branch_id' => $this->branch->id,
                'starts_at' => Carbon::now()->addDays(3),
                'ends_at' => Carbon::now()->addDays(3)->addHour(),
                'status' => 'scheduled',
                'created_by' => $metadata['created_by'],
                'booking_source' => $metadata['source'],
            ]);

            $this->assertEquals($metadata['created_by'], $appointment->created_by);
            $this->assertEquals($metadata['source'], $appointment->booking_source);
        }
    }

    /**
     * UNIT TEST: Null metadata values handled correctly
     */
    public function test_null_metadata_values_handled_correctly()
    {
        // Act: Create appointment with some null metadata
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'scheduled',
            'created_by' => 'customer',
            'booking_source' => 'retell_webhook',
            'booked_by_user_id' => null,
            'rescheduled_at' => null,
            'rescheduled_by' => null,
            'cancelled_at' => null,
            'cancelled_by' => null,
        ]);

        // Assert: Null values preserved
        $this->assertNull($appointment->booked_by_user_id);
        $this->assertNull($appointment->rescheduled_at);
        $this->assertNull($appointment->rescheduled_by);
        $this->assertNull($appointment->cancelled_at);
        $this->assertNull($appointment->cancelled_by);

        // Non-null values preserved
        $this->assertNotNull($appointment->created_by);
        $this->assertNotNull($appointment->booking_source);
    }

    /**
     * UNIT TEST: Timestamp precision validation
     */
    public function test_timestamp_precision_is_maintained()
    {
        // Arrange: Create specific timestamps
        $rescheduledAt = Carbon::now()->setTime(14, 30, 45);
        $cancelledAt = Carbon::now()->setTime(15, 45, 30);

        // Act: Create appointment with precise timestamps
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'cancelled',
            'rescheduled_at' => $rescheduledAt,
            'cancelled_at' => $cancelledAt,
        ]);

        // Assert: Timestamp precision maintained
        $appointment->refresh();

        $this->assertEquals($rescheduledAt->format('Y-m-d H:i:s'),
            $appointment->rescheduled_at->format('Y-m-d H:i:s'));
        $this->assertEquals($cancelledAt->format('Y-m-d H:i:s'),
            $appointment->cancelled_at->format('Y-m-d H:i:s'));
    }

    /**
     * UNIT TEST: Metadata immutability for critical fields
     */
    public function test_booking_metadata_is_not_accidentally_overwritten()
    {
        // Arrange: Create appointment with booking metadata
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'scheduled',
            'created_by' => 'customer',
            'booking_source' => 'retell_webhook',
        ]);

        $originalCreatedBy = $appointment->created_by;
        $originalBookingSource = $appointment->booking_source;

        // Act: Update other fields (reschedule)
        $appointment->update([
            'starts_at' => Carbon::now()->addDays(5),
            'rescheduled_at' => now(),
            'rescheduled_by' => 'staff',
        ]);

        // Assert: Booking metadata unchanged
        $appointment->refresh();

        $this->assertEquals($originalCreatedBy, $appointment->created_by,
            'created_by should not change during reschedule');
        $this->assertEquals($originalBookingSource, $appointment->booking_source,
            'booking_source should not change during reschedule');

        // But reschedule metadata is set
        $this->assertNotNull($appointment->rescheduled_at);
        $this->assertEquals('staff', $appointment->rescheduled_by);
    }

    /**
     * UNIT TEST: Metadata query filtering works
     */
    public function test_metadata_query_filtering_works()
    {
        // Arrange: Create appointments with different metadata
        $customerBooking = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'scheduled',
            'created_by' => 'customer',
            'booking_source' => 'retell_webhook',
        ]);

        $staffBooking = Appointment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addDays(4),
            'ends_at' => Carbon::now()->addDays(4)->addHour(),
            'status' => 'scheduled',
            'created_by' => 'staff',
            'booking_source' => 'crm_admin',
        ]);

        // Act & Assert: Query by created_by
        $customerBookings = Appointment::where('created_by', 'customer')->get();
        $this->assertCount(1, $customerBookings);
        $this->assertEquals($customerBooking->id, $customerBookings->first()->id);

        $staffBookings = Appointment::where('created_by', 'staff')->get();
        $this->assertCount(1, $staffBookings);
        $this->assertEquals($staffBooking->id, $staffBookings->first()->id);

        // Query by booking_source
        $retellBookings = Appointment::where('booking_source', 'retell_webhook')->get();
        $this->assertCount(1, $retellBookings);

        $crmBookings = Appointment::where('booking_source', 'crm_admin')->get();
        $this->assertCount(1, $crmBookings);
    }
}
