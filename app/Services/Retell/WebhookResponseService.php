<?php

namespace App\Services\Retell;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Webhook Response Service
 *
 * Centralized response formatting for Retell.ai webhooks and function calls
 *
 * KEY PRINCIPLE: Retell AI requires HTTP 200 for function call responses
 * to prevent call interruption. Use success/error flags in JSON body.
 *
 * CONSISTENT STRUCTURE:
 * - Success responses: { success: true, data: {...}, message?: string }
 * - Error responses: { success: false, error: string }
 * - Webhook events: { success: true, event: string, data: {...} }
 */
class WebhookResponseService implements WebhookResponseInterface
{
    protected DateTimeParser $dateTimeParser;

    public function __construct(DateTimeParser $dateTimeParser)
    {
        $this->dateTimeParser = $dateTimeParser;
    }
    /**
     * Create success response for Retell function calls
     *
     * Always returns HTTP 200 with success=true
     *
     * @param array $data Response data
     * @param string|null $message Optional success message
     * @return Response
     */
    public function success(array $data, ?string $message = null): Response
    {
        $response = [
            'success' => true,
            'data' => $data
        ];

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, 200);
    }

    /**
     * Create error response for Retell function calls
     *
     * Always returns HTTP 200 to not break active calls
     * Logs error with context for debugging
     *
     * @param string $message Error message (user-friendly German)
     * @param array $context Optional error context for logging
     * @param array $dateTimeContext Optional date/time context for agent temporal awareness
     * @return Response
     */
    public function error(string $message, array $context = [], array $dateTimeContext = []): Response
    {
        // Log error with context for debugging
        if (!empty($context)) {
            Log::error('Retell function call error', [
                'message' => $message,
                'context' => $context,
                'ip' => request()->ip(),
            ]);
        }

        $response = [
            'success' => false,
            'error' => $message
        ];

        // Add date/time context if provided (for agent temporal awareness)
        if (!empty($dateTimeContext)) {
            $response['context'] = $dateTimeContext;
        }

        return response()->json($response, 200); // Always 200 to not break the call
    }

    /**
     * Create webhook event success response
     *
     * Standard acknowledgment for webhook events
     *
     * @param string $event Event type
     * @param array $data Event-specific data
     * @return Response
     */
    public function webhookSuccess(string $event, array $data = []): Response
    {
        $response = [
            'success' => true,
            'event' => $event,
            'message' => ucfirst(str_replace('_', ' ', $event)) . ' processed successfully'
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        Log::info("Webhook event processed: {$event}", [
            'event' => $event,
            'data_keys' => array_keys($data),
        ]);

        return response()->json($response, 200);
    }

    /**
     * Create validation error response
     *
     * Returns HTTP 400 for webhook validation failures
     *
     * @param string $field Field that failed validation
     * @param string $message Error message
     * @return Response
     */
    public function validationError(string $field, string $message): Response
    {
        Log::warning('Webhook validation error', [
            'field' => $field,
            'message' => $message,
            'ip' => request()->ip(),
        ]);

        return response()->json([
            'success' => false,
            'error' => $message,
            'field' => $field,
            'type' => 'validation_error'
        ], 400);
    }

    /**
     * Create not found error response
     *
     * Returns HTTP 404 for resource not found
     *
     * @param string $resource Resource type
     * @param string $message Error message
     * @return Response
     */
    public function notFound(string $resource, string $message): Response
    {
        Log::warning('Resource not found in webhook', [
            'resource' => $resource,
            'message' => $message,
            'ip' => request()->ip(),
        ]);

        return response()->json([
            'success' => false,
            'error' => $message,
            'resource' => $resource,
            'type' => 'not_found'
        ], 404);
    }

    /**
     * Create server error response
     *
     * Returns HTTP 500 for unexpected server errors
     *
     * @param \Exception $exception Exception that occurred
     * @param array $context Additional context
     * @return Response
     */
    public function serverError(\Exception $exception, array $context = []): Response
    {
        Log::error('Server error in webhook processing', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'ip' => request()->ip(),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Internal server error occurred',
            'type' => 'server_error',
            // Include exception message only in non-production
            'debug' => config('app.debug') ? $exception->getMessage() : null
        ], 500);
    }

    /**
     * Create availability response
     *
     * Specialized response for time slot availability
     *
     * @param array $slots Available time slots
     * @param string $date Date of availability check
     * @return Response
     */
    public function availability(array $slots, string $date): Response
    {
        $hasSlots = !empty($slots);

        return response()->json([
            'success' => true,
            'available' => $hasSlots,
            'date' => $date,
            'slots' => $slots,
            'count' => count($slots),
            'message' => $hasSlots
                ? count($slots) . ' Termine verfügbar am ' . $date
                : 'Keine Termine verfügbar am ' . $date
        ], 200);
    }

    /**
     * Create booking confirmation response
     *
     * Specialized response for successful booking
     *
     * @param array $booking Booking details
     * @return Response
     */
    public function bookingConfirmed(array $booking): Response
    {
        Log::info('Booking confirmed via webhook', [
            'booking_id' => $booking['id'] ?? null,
            'service_id' => $booking['service_id'] ?? null,
            'time' => $booking['time'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'booked' => true,
            'booking' => $booking,
            'message' => 'Termin erfolgreich gebucht',
            'confirmation' => true
        ], 200);
    }

    /**
     * Create call tracking response
     *
     * Specialized response for call tracking events
     * Includes custom_data for Retell AI context
     *
     * @param array $callData Call tracking data
     * @param array $customData Custom data for Retell AI
     * @return Response
     */
    public function callTracking(array $callData, array $customData = []): Response
    {
        $response = [
            'success' => true,
            'tracking' => true,
            'call_id' => $callData['call_id'] ?? null,
            'status' => $callData['status'] ?? 'tracked',
        ];

        // Add custom data if provided (for AI context)
        // IMPORTANT: Retell expects 'retell_llm_dynamic_variables' key, not 'custom_data'
        if (!empty($customData)) {
            $response['retell_llm_dynamic_variables'] = $customData;
        }

        // Add response data if provided (for AI instructions)
        if (isset($callData['response_data'])) {
            $response['response_data'] = $callData['response_data'];
        }

        Log::info('Call tracking response sent', [
            'call_id' => $callData['call_id'] ?? null,
            'has_custom_data' => !empty($customData),
            'dynamic_variables_count' => !empty($customData) ? count($customData) : 0,
        ]);

        return response()->json($response, 200);
    }

    /**
     * Format alternatives with natural spoken German datetime
     *
     * FIX 2025-11-05: Natural date/time formatting for voice AI
     * User feedback: "Wochentag hinzufügen, Jahr weglassen, Zeit natürlich"
     *
     * Before: "am 11.11.2025, 15:20 Uhr"
     * After:  "am Montag, den 11. November um 15 Uhr 20"
     *
     * @param array $alternatives Array of alternative slots with 'time' key
     * @param bool $useColloquialTime Use "Viertel nach", "halb" (default: false)
     * @return array Formatted alternatives with 'spoken' field
     */
    public function formatAlternativesSpoken(array $alternatives, bool $useColloquialTime = false): array
    {
        return array_map(function($alt) use ($useColloquialTime) {
            // Keep original structure
            $formatted = $alt;

            // Add natural spoken format
            if (isset($alt['time'])) {
                $formatted['spoken'] = $this->dateTimeParser->formatSpokenDateTime(
                    $alt['time'],
                    $useColloquialTime
                );
            }

            return $formatted;
        }, $alternatives);
    }

    /**
     * Format single datetime as natural spoken German
     *
     * FIX 2025-11-05: Helper for confirmation messages
     *
     * @param string $datetime DateTime to format
     * @param bool $compact Use compact format (without weekday)
     * @param bool $useColloquialTime Use "Viertel nach", "halb"
     * @return string Natural German format
     */
    public function formatSpoken(string $datetime, bool $compact = false, bool $useColloquialTime = false): string
    {
        if ($compact) {
            return $this->dateTimeParser->formatSpokenDateTimeCompact($datetime, $useColloquialTime);
        }

        return $this->dateTimeParser->formatSpokenDateTime($datetime, $useColloquialTime);
    }

    /**
     * Create availability response with natural spoken alternatives
     *
     * FIX 2025-11-05: Enhanced availability response with spoken format
     * Replaces old numeric format with natural German
     *
     * @param bool $available Whether requested time is available
     * @param string $requestedTime Original requested time
     * @param array $alternatives Alternative time slots
     * @param string|null $message Custom message
     * @return Response
     */
    public function availabilityWithAlternatives(
        bool $available,
        string $requestedTime,
        array $alternatives = [],
        ?string $message = null
    ): Response {
        // Format alternatives with spoken datetime
        $formattedAlternatives = $this->formatAlternativesSpoken($alternatives);

        // Build spoken message if not provided
        if (!$message && !empty($formattedAlternatives)) {
            $alternativesList = array_map(fn($alt) => $alt['spoken'], $formattedAlternatives);

            if (count($alternativesList) === 1) {
                $message = "Ich habe leider keinen Termin zu Ihrer gewünschten Zeit gefunden, " .
                          "aber ich kann Ihnen folgende Alternative anbieten: " .
                          $alternativesList[0] . ". Passt Ihnen dieser Termin?";
            } else {
                $lastAlternative = array_pop($alternativesList);
                $message = "Ich habe leider keinen Termin zu Ihrer gewünschten Zeit gefunden, " .
                          "aber ich kann Ihnen folgende Alternativen anbieten: " .
                          implode(', ', $alternativesList) .
                          " oder " . $lastAlternative .
                          ". Welcher Termin würde Ihnen besser passen?";
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'available' => $available,
                'message' => $message ?? 'Verfügbarkeit geprüft',
                'requested_time' => $requestedTime,
                'alternatives' => $formattedAlternatives,
            ]
        ], 200);
    }
}