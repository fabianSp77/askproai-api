<?php

namespace App\Services\Retell;

use Symfony\Component\HttpFoundation\Response;

/**
 * Webhook Response Interface
 *
 * Standardized response formatting for Retell.ai webhooks and function calls
 *
 * IMPORTANT: Retell AI requires HTTP 200 for ALL responses (including errors)
 * to prevent call interruption. Use appropriate success/error flags in JSON body.
 */
interface WebhookResponseInterface
{
    /**
     * Create success response for Retell function calls
     *
     * Always returns HTTP 200 with success=true and data payload
     *
     * @param array $data Response data
     * @param string|null $message Optional success message
     * @return Response
     */
    public function success(array $data, ?string $message = null): Response;

    /**
     * Create error response for Retell function calls
     *
     * Always returns HTTP 200 with success=false to not break active calls
     *
     * @param string $message Error message (user-friendly German)
     * @param array $context Optional error context for logging
     * @return Response
     */
    public function error(string $message, array $context = []): Response;

    /**
     * Create webhook event success response
     *
     * Used for webhook events (call_started, call_ended, call_analyzed)
     * Returns HTTP 200 with event acknowledgment
     *
     * @param string $event Event type (e.g., 'call_started', 'call_ended')
     * @param array $data Event-specific data
     * @return Response
     */
    public function webhookSuccess(string $event, array $data = []): Response;

    /**
     * Create validation error response
     *
     * Returns HTTP 400 for webhook validation failures
     * (phone number not registered, missing required fields)
     *
     * @param string $field Field that failed validation
     * @param string $message Error message
     * @return Response
     */
    public function validationError(string $field, string $message): Response;

    /**
     * Create not found error response
     *
     * Returns HTTP 404 for resource not found
     * (phone number not registered, service not found)
     *
     * @param string $resource Resource type (e.g., 'phone_number', 'service')
     * @param string $message Error message
     * @return Response
     */
    public function notFound(string $resource, string $message): Response;

    /**
     * Create server error response
     *
     * Returns HTTP 500 for unexpected server errors
     * Includes error tracking for debugging
     *
     * @param \Exception $exception Exception that occurred
     * @param array $context Additional context
     * @return Response
     */
    public function serverError(\Exception $exception, array $context = []): Response;

    /**
     * Create availability response
     *
     * Specialized response for time slot availability
     * Returns HTTP 200 with formatted availability data
     *
     * @param array $slots Available time slots
     * @param string $date Date of availability check
     * @return Response
     */
    public function availability(array $slots, string $date): Response;

    /**
     * Create booking confirmation response
     *
     * Specialized response for successful booking
     * Returns HTTP 200 with booking details
     *
     * @param array $booking Booking details (id, time, service, etc.)
     * @return Response
     */
    public function bookingConfirmed(array $booking): Response;

    /**
     * Create call tracking response
     *
     * Specialized response for call tracking events
     * Includes custom_data for AI context
     *
     * @param array $callData Call tracking data
     * @param array $customData Custom data for Retell AI
     * @return Response
     */
    public function callTracking(array $callData, array $customData = []): Response;
}