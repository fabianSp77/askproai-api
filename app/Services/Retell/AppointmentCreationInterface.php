<?php

namespace App\Services\Retell;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;

/**
 * Appointment Creation Interface
 *
 * Centralized contract for appointment creation from calls
 *
 * RESPONSIBILITY: Orchestrate appointment creation with alternatives,
 * Cal.com integration, and nested booking support
 *
 * SCOPE:
 * - Appointment creation from booking details
 * - Cal.com booking integration
 * - Alternative time slot search
 * - Nested booking orchestration
 * - Local appointment record creation
 * - Customer creation/lookup
 *
 * OUT OF SCOPE (separate service):
 * - Booking details extraction from transcript (BookingDetailsExtractor)
 * - Call analysis and insights (CallAnalysisService)
 */
interface AppointmentCreationInterface
{
    /**
     * Create appointment from call with automatic alternative search
     *
     * Main orchestration method that:
     * 1. Validates booking confidence
     * 2. Ensures customer exists (creates if needed)
     * 3. Finds appropriate service
     * 4. Attempts Cal.com booking at desired time
     * 5. Searches for alternatives if needed
     * 6. Creates local appointment record
     * 7. Tracks booking success/failure
     *
     * @param Call $call Call record with booking details
     * @param array $bookingDetails Extracted booking information
     * @return Appointment|null Created appointment or null if failed
     */
    public function createFromCall(Call $call, array $bookingDetails): ?Appointment;

    /**
     * Create appointment with explicit parameters (bypass extraction)
     *
     * Direct appointment creation when all details are known
     * Useful for manual bookings or API-driven bookings
     *
     * @param Customer $customer Customer making the appointment
     * @param Service $service Service being booked
     * @param \Carbon\Carbon $startTime Desired start time
     * @param int $durationMinutes Appointment duration
     * @param Call|null $call Optional associated call
     * @param bool $searchAlternatives Whether to search for alternatives if desired time unavailable
     * @return Appointment|null Created appointment or null if failed
     */
    public function createDirect(
        Customer $customer,
        Service $service,
        \Carbon\Carbon $startTime,
        int $durationMinutes,
        ?Call $call = null,
        bool $searchAlternatives = true
    ): ?Appointment;

    /**
     * Create local appointment record in database
     *
     * Creates the Appointment model instance with proper relationships
     * and metadata tracking
     *
     * @param Customer $customer Customer for appointment
     * @param Service $service Service being booked
     * @param array $bookingDetails Booking information and metadata
     * @param string|null $calcomBookingId External Cal.com booking ID
     * @param Call|null $call Optional associated call
     * @return Appointment Created appointment instance
     */
    public function createLocalRecord(
        Customer $customer,
        Service $service,
        array $bookingDetails,
        ?string $calcomBookingId = null,
        ?Call $call = null
    ): Appointment;

    /**
     * Ensure customer exists for appointment booking
     *
     * Finds existing customer or creates new one from call data
     * Handles anonymous calls by extracting name from analysis
     *
     * @param Call $call Call record with customer information
     * @return Customer|null Customer instance or null if creation failed
     */
    public function ensureCustomer(Call $call): ?Customer;

    /**
     * Find appropriate service for booking
     *
     * Resolves service from booking details and company context
     * Uses ServiceSelectionService for branch-aware service selection
     *
     * @param array $bookingDetails Booking information including service name
     * @param int $companyId Company ID for service lookup
     * @param string|null $branchId Optional branch UUID for filtering
     * @return Service|null Found service or null
     */
    public function findService(array $bookingDetails, int $companyId, ?string $branchId = null): ?Service;

    /**
     * Book appointment in Cal.com
     *
     * Creates booking in external Cal.com system
     * Returns booking ID on success, null on failure
     *
     * @param Customer $customer Customer making appointment
     * @param Service $service Service being booked
     * @param \Carbon\Carbon $startTime Start time
     * @param int $durationMinutes Duration
     * @param Call|null $call Optional call for context
     * @return array|null ['booking_id' => string, 'booking_data' => array] or null
     */
    public function bookInCalcom(
        Customer $customer,
        Service $service,
        \Carbon\Carbon $startTime,
        int $durationMinutes,
        ?Call $call = null
    ): ?array;

    /**
     * Search for alternative appointment times
     *
     * Uses AppointmentAlternativeFinder to search for available slots
     * when desired time is not available
     *
     * @param \Carbon\Carbon $desiredTime Original requested time
     * @param int $durationMinutes Appointment duration
     * @param int $eventTypeId Cal.com event type ID
     * @return array Array of alternative slots with ranking
     */
    public function findAlternatives(
        \Carbon\Carbon $desiredTime,
        int $durationMinutes,
        int $eventTypeId
    ): array;

    /**
     * Book alternative time slot
     *
     * Attempts to book first available alternative
     * Updates booking details with alternative information
     *
     * @param array $alternatives Array of alternative slots
     * @param Customer $customer Customer making appointment
     * @param Service $service Service being booked
     * @param int $durationMinutes Duration
     * @param Call $call Call for tracking
     * @param array $bookingDetails Original booking details (will be updated)
     * @return array|null ['booking_id' => string, 'alternative_time' => Carbon, 'alternative_type' => string] or null
     */
    public function bookAlternative(
        array $alternatives,
        Customer $customer,
        Service $service,
        int $durationMinutes,
        Call $call,
        array &$bookingDetails
    ): ?array;

    /**
     * Handle nested booking creation
     *
     * For services with interruption periods (e.g., hair coloring)
     * Creates main booking with nested slots during processing time
     *
     * @param array $bookingData Main booking information
     * @param Service $service Service being booked
     * @param Customer $customer Customer making appointment
     * @param Call $call Call for tracking
     * @return Appointment|null Created appointment with nested structure or null
     */
    public function createNestedBooking(
        array $bookingData,
        Service $service,
        Customer $customer,
        Call $call
    ): ?Appointment;

    /**
     * Determine if service supports nested booking
     *
     * Checks if service type allows interruption periods
     *
     * @param string $serviceType Service type identifier
     * @return bool True if service supports nesting
     */
    public function supportsNesting(string $serviceType): bool;

    /**
     * Determine service type for nested booking classification
     *
     * Maps service name to nested booking type
     * (coloring, perm, highlights, etc.)
     *
     * @param string $serviceName Service name
     * @return string Service type identifier
     */
    public function determineServiceType(string $serviceName): string;

    /**
     * Validate booking confidence threshold
     *
     * Checks if extracted booking details meet minimum confidence
     * for automatic appointment creation
     *
     * @param array $bookingDetails Booking details with confidence score
     * @return bool True if confidence is acceptable
     */
    public function validateConfidence(array $bookingDetails): bool;

    /**
     * Notify customer about alternative booking
     *
     * Sends notification when appointment was booked at
     * alternative time instead of requested time
     *
     * @param Customer $customer Customer to notify
     * @param \Carbon\Carbon $requestedTime Original requested time
     * @param \Carbon\Carbon $bookedTime Actually booked time
     * @param array $alternative Alternative details
     * @return void
     */
    public function notifyCustomerAboutAlternative(
        Customer $customer,
        \Carbon\Carbon $requestedTime,
        \Carbon\Carbon $bookedTime,
        array $alternative
    ): void;
}