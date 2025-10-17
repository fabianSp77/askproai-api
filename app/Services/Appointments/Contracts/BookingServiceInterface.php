<?php

namespace App\Services\Appointments\Contracts;

use App\Models\Appointment;
use App\Models\Customer;
use Carbon\Carbon;

interface BookingServiceInterface
{
    /**
     * Persist a new appointment after performing all validations.
     */
    public function createAppointment(array $data): Appointment;

    /**
     * Validate incoming booking data and return validity plus messages.
     */
    public function validateBookingData(array $data): array;

    /**
     * Detect if a booking already exists within a time threshold.
     */
    public function checkDuplicateBooking(string $customerId, string $serviceId, Carbon $datetime): bool;

    /**
     * Resolve an existing customer or create a new one for the booking.
     */
    public function findOrCreateCustomer(array $customerData): Customer;
}
