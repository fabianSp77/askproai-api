<?php

namespace App\Services\Retell;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Carbon;

/**
 * Call Lifecycle Interface
 *
 * Centralized contract for call state management across Retell.ai webhooks
 *
 * RESPONSIBILITY: Manage call creation, state transitions, and lifecycle tracking
 *
 * STATE MACHINE:
 * - inbound → ongoing (call_started)
 * - ongoing → completed (call_ended)
 * - completed → analyzed (call_analyzed)
 *
 * CACHING STRATEGY:
 * - Request-scoped caching to reduce database queries (3-4 queries saved per request)
 * - Cache cleared at end of request lifecycle
 *
 * CALL CREATION FLOW:
 * 1. call_inbound: Creates temporary call with temp_id
 * 2. call_started: Finds temp call and updates with real retell_call_id
 * 3. Fallback: Creates new call if not found
 */
interface CallLifecycleInterface
{
    /**
     * Create a new call record
     *
     * Creates call with proper company/branch isolation and initial state
     *
     * @param array $callData Call data from Retell webhook/function call
     * @param int|null $companyId Company ID for tenant isolation
     * @param string|null $phoneNumberId Phone number UUID for tracking
     * @param string|null $branchId Branch UUID for isolation (VULN-003 fix)
     * @return Call Created call instance
     */
    public function createCall(
        array $callData,
        ?int $companyId = null,
        ?string $phoneNumberId = null,
        ?string $branchId = null
    ): Call;

    /**
     * Create a temporary call record (used by call_inbound)
     *
     * Creates call with temporary ID before Retell assigns real call_id
     *
     * @param string $fromNumber Caller's phone number
     * @param string $toNumber Called phone number (our number)
     * @param int|null $companyId Company ID
     * @param string|null $phoneNumberId Phone number UUID
     * @param string|null $branchId Branch UUID
     * @param string|null $agentId Retell agent ID
     * @return Call Created temporary call instance
     */
    public function createTemporaryCall(
        string $fromNumber,
        string $toNumber,
        ?int $companyId = null,
        ?string $phoneNumberId = null,
        ?string $branchId = null,
        ?string $agentId = null
    ): Call;

    /**
     * Find call by Retell call ID
     *
     * Uses request-scoped caching to avoid duplicate queries
     * Tries retell_call_id first, then external_id as fallback
     *
     * @param string $retellCallId Retell call ID
     * @param bool $withRelations Load relationships (phoneNumber, customer, etc.)
     * @return Call|null Found call or null
     */
    public function findCallByRetellId(string $retellCallId, bool $withRelations = false): ?Call;

    /**
     * Find temporary call for matching with real call ID
     *
     * Searches for recent temporary calls (within 10 minutes) to match
     * with real Retell call ID during call_started event
     *
     * @return Call|null Found temporary call or null
     */
    public function findRecentTemporaryCall(): ?Call;

    /**
     * Update temporary call with real Retell call ID
     *
     * Called when call_started event arrives with real call_id
     *
     * @param Call $tempCall Temporary call to update
     * @param string $realCallId Real Retell call ID
     * @param array $additionalData Additional call data to update
     * @return Call Updated call instance
     */
    public function upgradeTemporaryCall(
        Call $tempCall,
        string $realCallId,
        array $additionalData = []
    ): Call;

    /**
     * Update call status (state transition)
     *
     * Handles state machine transitions with validation
     * Logs state changes for audit trail
     *
     * Valid transitions:
     * - inbound → ongoing
     * - ongoing → completed
     * - completed → analyzed
     *
     * @param Call $call Call to update
     * @param string $newStatus New status
     * @param array $additionalData Additional fields to update
     * @return Call Updated call instance
     */
    public function updateCallStatus(Call $call, string $newStatus, array $additionalData = []): Call;

    /**
     * Link customer to call
     *
     * Associates customer with call for relationship tracking
     * Used after customer identification/creation
     *
     * @param Call $call Call to link
     * @param Customer $customer Customer to associate
     * @return Call Updated call instance
     */
    public function linkCustomer(Call $call, Customer $customer): Call;

    /**
     * Link appointment to call (conversion tracking)
     *
     * Marks call as successfully converted to appointment
     * Tracks appointment creation from call
     *
     * @param Call $call Call to link
     * @param Appointment $appointment Created appointment
     * @return Call Updated call instance
     */
    public function linkAppointment(Call $call, Appointment $appointment): Call;

    /**
     * Track booking details
     *
     * Stores booking information from call analysis
     * Includes customer data, service, date/time
     *
     * @param Call $call Call to update
     * @param array $bookingDetails Booking information
     * @param bool $confirmed Whether booking is confirmed
     * @param string|null $bookingId External booking ID (Cal.com)
     * @return Call Updated call instance
     */
    public function trackBooking(
        Call $call,
        array $bookingDetails,
        bool $confirmed = false,
        ?string $bookingId = null
    ): Call;

    /**
     * Track failed booking attempt
     *
     * Stores failed booking for manual review
     * Marks call as requiring manual processing
     *
     * @param Call $call Call to update
     * @param array $bookingDetails Attempted booking information
     * @param string $failureReason Reason for booking failure
     * @return Call Updated call instance
     */
    public function trackFailedBooking(
        Call $call,
        array $bookingDetails,
        string $failureReason
    ): Call;

    /**
     * Update call analysis data
     *
     * Stores AI analysis results (transcript, insights, sentiment)
     * Merges with existing analysis data
     *
     * @param Call $call Call to update
     * @param array $analysisData Analysis information
     * @return Call Updated call instance
     */
    public function updateAnalysis(Call $call, array $analysisData): Call;

    /**
     * Get call context with caching
     *
     * Retrieves call with related data (phoneNumber, company, branch)
     * Uses request-scoped cache to avoid duplicate queries
     *
     * @param string $retellCallId Retell call ID
     * @return Call|null Call with context or null
     */
    public function getCallContext(string $retellCallId): ?Call;

    /**
     * Find recent call for company context lookup
     *
     * Fallback method to resolve company_id from recent calls
     * Used when phone number resolution fails
     *
     * @param int $minutesBack How many minutes back to search (default: 30)
     * @return Call|null Recent call with company_id or null
     */
    public function findRecentCallWithCompany(int $minutesBack = 30): ?Call;

    /**
     * Clear request-scoped cache
     *
     * Called at end of request to free memory
     * Cache is automatically cleared by PHP garbage collection
     */
    public function clearCache(): void;
}