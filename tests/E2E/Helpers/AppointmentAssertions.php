<?php

namespace Tests\E2E\Helpers;

use PHPUnit\Framework\Attributes\Test;

trait AppointmentAssertions
{
    /**
     * Assert that an appointment was created with all expected attributes
     */
    protected function assertAppointmentCreated(
        array $expectedAttributes,
        ?Appointment $appointment = null
    ): Appointment {
        if (!$appointment) {
            $appointment = Appointment::latest()->first();
        }

        Assert::assertNotNull($appointment, 'No appointment was created');

        foreach ($expectedAttributes as $key => $value) {
            if ($value instanceof Carbon) {
                Assert::assertEquals(
                    $value->format('Y-m-d H:i:s'),
                    $appointment->$key->format('Y-m-d H:i:s'),
                    "Appointment {$key} does not match expected value"
                );
            } else {
                Assert::assertEquals(
                    $value,
                    $appointment->$key,
                    "Appointment {$key} does not match expected value"
                );
            }
        }

        return $appointment;
    }

    /**
     * Assert that a customer was created or found with expected attributes
     */
    protected function assertCustomerExists(array $attributes): Customer
    {
        $customer = Customer::where('phone', $attributes['phone'])->first();
        
        Assert::assertNotNull($customer, 'Customer was not created');
        
        foreach ($attributes as $key => $value) {
            if ($key !== 'phone') {
                Assert::assertEquals(
                    $value,
                    $customer->$key,
                    "Customer {$key} does not match expected value"
                );
            }
        }

        return $customer;
    }

    /**
     * Assert that a call record exists with expected state
     */
    protected function assertCallRecordExists(array $attributes): Call
    {
        $query = Call::query();
        
        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }
        
        $call = $query->first();
        
        Assert::assertNotNull($call, 'Call record was not found with specified attributes');
        
        return $call;
    }

    /**
     * Assert that an appointment has proper relationships
     */
    protected function assertAppointmentRelationships(
        Appointment $appointment,
        ?Customer $customer = null,
        ?Staff $staff = null,
        ?Service $service = null,
        ?Branch $branch = null,
        ?Company $company = null
    ): void {
        if ($customer) {
            Assert::assertTrue(
                $appointment->customer->is($customer),
                'Appointment customer relationship does not match'
            );
        }

        if ($staff) {
            Assert::assertTrue(
                $appointment->staff->is($staff),
                'Appointment staff relationship does not match'
            );
        }

        if ($service) {
            Assert::assertTrue(
                $appointment->service->is($service),
                'Appointment service relationship does not match'
            );
        }

        if ($branch) {
            Assert::assertTrue(
                $appointment->branch->is($branch),
                'Appointment branch relationship does not match'
            );
        }

        if ($company) {
            Assert::assertTrue(
                $appointment->company->is($company),
                'Appointment company relationship does not match'
            );
        }
    }

    /**
     * Assert appointment was synced with Cal.com
     */
    protected function assertCalcomSynced(Appointment $appointment): void
    {
        Assert::assertNotNull(
            $appointment->calcom_booking_id,
            'Appointment was not synced with Cal.com (missing booking ID)'
        );
        
        Assert::assertNotNull(
            $appointment->calcom_uid,
            'Appointment was not synced with Cal.com (missing UID)'
        );
        
        Assert::assertNotEmpty(
            $appointment->calcom_uid,
            'Cal.com UID should not be empty'
        );
    }

    /**
     * Assert no appointment was created
     */
    protected function assertNoAppointmentCreated(
        ?int $companyId = null,
        ?int $customerId = null
    ): void {
        $query = Appointment::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        
        Assert::assertEquals(
            0,
            $query->count(),
            'Appointment was created when it should not have been'
        );
    }

    /**
     * Assert appointment has specific status
     */
    protected function assertAppointmentStatus(
        Appointment $appointment,
        string $expectedStatus
    ): void {
        $appointment->refresh();
        
        Assert::assertEquals(
            $expectedStatus,
            $appointment->status,
            "Appointment status is {$appointment->status}, expected {$expectedStatus}"
        );
    }

    /**
     * Assert call is linked to appointment
     */
    protected function assertCallLinkedToAppointment(
        Call $call,
        Appointment $appointment
    ): void {
        $call->refresh();
        
        Assert::assertEquals(
            $appointment->id,
            $call->appointment_id,
            'Call is not linked to the appointment'
        );
        
        Assert::assertEquals(
            $appointment->customer_id,
            $call->customer_id,
            'Call customer does not match appointment customer'
        );
    }

    /**
     * Assert activity log entry exists
     */
    protected function assertActivityLogged(
        string $description,
        string $subjectType,
        int $subjectId,
        ?array $properties = null
    ): void {
        $query = DB::table('activity_log')
            ->where('description', $description)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId);
        
        if ($properties) {
            $query->where('properties', json_encode($properties));
        }
        
        Assert::assertTrue(
            $query->exists(),
            "Activity log entry not found for {$description} on {$subjectType}#{$subjectId}"
        );
    }

    /**
     * Assert metric was recorded
     */
    protected function assertMetricRecorded(
        string $metricType,
        int $companyId,
        ?array $dimensions = null
    ): void {
        $query = DB::table('metrics')
            ->where('metric_type', $metricType)
            ->where('company_id', $companyId);
        
        if ($dimensions) {
            $query->where('dimensions', json_encode($dimensions));
        }
        
        Assert::assertTrue(
            $query->exists(),
            "Metric {$metricType} was not recorded for company {$companyId}"
        );
    }

    /**
     * Assert appointment count for a customer
     */
    protected function assertCustomerAppointmentCount(
        Customer $customer,
        int $expectedCount
    ): void {
        $customer->refresh();
        
        $actualCount = $customer->appointments()->count();
        
        Assert::assertEquals(
            $expectedCount,
            $actualCount,
            "Customer has {$actualCount} appointments, expected {$expectedCount}"
        );
        
        Assert::assertEquals(
            $expectedCount,
            $customer->total_appointments,
            "Customer total_appointments counter is incorrect"
        );
    }

    /**
     * Assert appointment time slot
     */
    protected function assertAppointmentTimeSlot(
        Appointment $appointment,
        Carbon $expectedStart,
        Carbon $expectedEnd
    ): void {
        Assert::assertEquals(
            $expectedStart->format('Y-m-d H:i:s'),
            $appointment->start_time->format('Y-m-d H:i:s'),
            'Appointment start time does not match'
        );
        
        Assert::assertEquals(
            $expectedEnd->format('Y-m-d H:i:s'),
            $appointment->end_time->format('Y-m-d H:i:s'),
            'Appointment end time does not match'
        );
        
        $duration = $appointment->start_time->diffInMinutes($appointment->end_time);
        Assert::assertEquals(
            $appointment->duration,
            $duration,
            'Appointment duration does not match time slot'
        );
    }

    /**
     * Assert appointment price and service
     */
    protected function assertAppointmentPricing(
        Appointment $appointment,
        float $expectedPrice,
        ?Service $service = null
    ): void {
        Assert::assertEquals(
            $expectedPrice,
            $appointment->price,
            "Appointment price is {$appointment->price}, expected {$expectedPrice}"
        );
        
        if ($service) {
            Assert::assertEquals(
                $service->id,
                $appointment->service_id,
                'Appointment service does not match'
            );
            
            // If no custom price, should match service price
            if (!$appointment->custom_price) {
                Assert::assertEquals(
                    $service->price,
                    $appointment->price,
                    'Appointment price should match service price'
                );
            }
        }
    }

    /**
     * Assert complete booking flow state
     */
    protected function assertCompleteBookingState(
        Call $call,
        Customer $customer,
        Appointment $appointment,
        array $expectations = []
    ): void {
        // Call state
        $this->assertCallRecordExists([
            'id' => $call->id,
            'status' => $expectations['call_status'] ?? 'completed',
            'appointment_id' => $appointment->id,
            'customer_id' => $customer->id,
        ]);

        // Customer state
        $this->assertCustomerExists([
            'id' => $customer->id,
            'company_id' => $appointment->company_id,
            'is_active' => true,
        ]);

        // Appointment state
        $this->assertAppointmentStatus(
            $appointment,
            $expectations['appointment_status'] ?? 'scheduled'
        );

        // Cal.com sync
        if ($expectations['calcom_synced'] ?? true) {
            $this->assertCalcomSynced($appointment);
        }

        // Relationships
        $this->assertCallLinkedToAppointment($call, $appointment);
        $this->assertAppointmentRelationships(
            $appointment,
            $customer,
            $appointment->staff,
            $appointment->service,
            $appointment->branch,
            $appointment->company
        );
    }
}