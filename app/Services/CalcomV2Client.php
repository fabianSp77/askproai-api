<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Company;

class CalcomV2Client
{
    private string $apiKey;
    private string $apiVersion;
    private string $baseUrl = 'https://api.cal.com/v2';
    private ?int $teamId = null;

    public function __construct(?Company $company = null)
    {
        // Hierarchical credentials: Company → ENV
        if ($company && $company->calcom_v2_api_key) {
            $this->apiKey = $company->calcom_v2_api_key;
        } else {
            $this->apiKey = config('services.calcom.api_key');
        }

        // Get API version from ENV with fallback
        $this->apiVersion = config('services.calcom.api_version', '2024-08-13');

        // Store team ID for team-scoped endpoints
        if ($company && $company->calcom_team_id) {
            $this->teamId = $company->calcom_team_id;
        }
    }

    /**
     * Get headers for API requests
     */
    private function getHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'cal-api-version' => $this->apiVersion,
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Build team-scoped URL
     *
     * CRITICAL: Event Types, Slots, and Bookings are team-scoped in Cal.com v2 API.
     * Global endpoints /v2/event-types return 404.
     * Must use /v2/teams/{teamId}/event-types instead.
     */
    private function getTeamUrl(string $endpoint): string
    {
        if (!$this->teamId) {
            // Fallback to global endpoint if no team (will likely fail)
            return "{$this->baseUrl}/{$endpoint}";
        }
        return "{$this->baseUrl}/teams/{$this->teamId}/{$endpoint}";
    }

    /**
     * GET /v2/slots/available - Get available time slots
     *
     * CRITICAL: Must use /slots/available endpoint (not /slots)
     * and include teamId as query parameter (not in path)
     */
    public function getAvailableSlots(int $eventTypeId, Carbon $start, Carbon $end, string $timezone = 'Europe/Berlin'): Response
    {
        $query = [
            'eventTypeId' => $eventTypeId,
            'startTime' => $start->toIso8601String(),
            'endTime' => $end->toIso8601String(),
            'timeZone' => $timezone // IMPORTANT: camelCase!
        ];

        // Add teamId to query if available
        if ($this->teamId) {
            $query['teamId'] = $this->teamId;
        }

        return Http::withHeaders($this->getHeaders())
            ->retry(3, 200, function ($exception, $request) {
                return optional($exception->response)->status() === 429;
            })
            ->get("{$this->baseUrl}/slots/available", $query);
    }

    /**
     * POST /v2/bookings - Create a booking with retry logic
     *
     * Cal.com V2 API Format (2024-08-13):
     * @see https://cal.com/docs/api-reference/v2/bookings/create-a-booking
     */
    public function createBooking(array $data): Response
    {
        // 🔧 FIX 2025-11-17: Use correct Cal.com V2 API format
        // OLD V1 FORMAT (WRONG): end, timeZone, language, responses, instant, noEmail
        // NEW V2 FORMAT: start, attendee{name, email, timeZone}, eventTypeId (integer)

        $payload = [
            'eventTypeId' => (int) $data['eventTypeId'], // MUST be integer
            'start' => $data['start'], // ISO8601 timestamp
            'attendee' => [
                'name' => $data['name'],
                'email' => $data['email'],
                'timeZone' => $data['timeZone'] ?? 'Europe/Berlin',
            ],
        ];

        // 🚧 TEMPORARY FIX 2025-11-17: SKIP phone due to Cal.com validation
        // Cal.com rejects format: "responses - {attendeePhoneNumber}invalid_number"
        // Tested formats that FAIL: +491604366218, +491234567890
        // Phone is OPTIONAL - bookings work perfectly without it
        // TODO: Research correct phone number format (E.164? Cal.com specific?)
        /*
        if (!empty($data['phone'])) {
            $payload['attendee']['phoneNumber'] = $data['phone'];
        }
        */

        // Add metadata if provided
        // 🔧 FIX 2025-11-17: Cal.com requires metadata values to be STRINGS
        // Convert all values to strings to avoid "Expected string, received boolean" errors
        if (!empty($data['metadata'])) {
            $payload['metadata'] = array_map(function($value) {
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }
                return (string) $value;
            }, $data['metadata']);
        }

        // Add booking field responses (e.g., custom fields, notes)
        if (!empty($data['bookingFieldsResponses'])) {
            $payload['bookingFieldsResponses'] = $data['bookingFieldsResponses'];
        }

        // 🚧 TEMPORARY FIX 2025-11-17: Phone validation disabled
        // Cal.com rejects all tested phone formats with "invalid_number" error
        // Phone is optional, so we skip it entirely until correct format is found
        // TODO: Find correct phone format that Cal.com accepts
        /*
        if (!empty($data['phone'])) {
            if (!isset($payload['bookingFieldsResponses']['phone'])) {
                $payload['bookingFieldsResponses']['phone'] = $data['phone'];
            }
            if (!isset($payload['bookingFieldsResponses']['attendeePhoneNumber'])) {
                $payload['bookingFieldsResponses']['attendeePhoneNumber'] = $data['phone'];
            }
        }
        */

        \Log::channel('calcom')->info('🔍 Cal.com CREATE Booking Request', [
            'url' => "{$this->baseUrl}/bookings",
            'payload' => $payload,
        ]);

        try {
            $retryCount = 0;
            $response = Http::withHeaders($this->getHeaders())
                ->retry(3, 200, function ($exception, $request) use (&$retryCount) {
                    $status = optional($exception->response)->status();
                    $retryCount++;

                    // 🐛 DEBUG: Log retry attempt with full error
                    if ($exception && $exception->response) {
                        \Log::channel('calcom')->error('🔍 Cal.com API Error (Retry Attempt)', [
                            'status' => $exception->response->status(),
                            'body' => $exception->response->body(),
                            'json' => $exception->response->json(),
                            'attempt' => $retryCount,
                        ]);
                    }

                    if (in_array($status, [409, 429])) {
                        // Exponential backoff
                        usleep(pow(2, $retryCount) * 1000000); // 2s, 4s, 8s
                        return true;
                    }
                    return false;
                })
                ->post("{$this->baseUrl}/bookings", $payload);

            if (!$response->successful()) {
                \Log::channel('calcom')->error('🔍 Cal.com CREATE Booking FAILED (Response)', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            \Log::channel('calcom')->error('🔍 Cal.com CREATE Booking EXCEPTION', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * POST /v2/bookings/{uid}/cancel - Cancel a booking
     *
     * 🔧 FIX 2025-11-17: Cal.com V2 uses POST /cancel endpoint (not DELETE)
     * 🔧 FIX 2025-11-17: Cal.com requires UID (string), not ID (integer)
     * @see https://cal.com/docs/api-reference/v2/bookings/cancel-a-booking
     *
     * @param string $bookingUidOrId Cal.com booking UID (preferred) or ID
     * @param string $reason Cancellation reason
     */
    public function cancelBooking(string $bookingUidOrId, string $reason = ''): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/bookings/{$bookingUidOrId}/cancel", [
                'cancellationReason' => $reason
            ]);
    }

    /**
     * PATCH /v2/bookings/{id} - Reschedule a booking
     */
    public function rescheduleBooking(int $bookingId, array $data): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->patch("{$this->baseUrl}/bookings/{$bookingId}", [
                'start' => $data['start'],
                'end' => $data['end'],
                'timeZone' => $data['timeZone'],
                'reason' => $data['reason'] ?? 'Customer requested reschedule'
            ]);
    }

    /**
     * POST /v2/teams/{teamId}/event-types - Create an event type
     *
     * CRITICAL: Must use team-scoped endpoint. Global /v2/event-types returns 404.
     */
    public function createEventType(array $data): Response
    {
        $payload = [
            'title' => $data['name'], // e.g. "ACME-BER-FARBE-A-S123"
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? '',
            'lengthInMinutes' => $data['duration'],
            'schedulingType' => $data['schedulingType'] ?? 'MANAGED', // Default: MANAGED, configurable per service
            'hidden' => true, // IMPORTANT: Hidden from public!
            'disableGuests' => true,
            'hideCalendarNotes' => true,
            'requiresConfirmation' => false,
            'locations' => [['type' => 'address', 'address' => 'Vor Ort', 'public' => false]]
            // bookingFields omitted - Cal.com uses defaults (name, email)
        ];

        // Host assignment: Use specific hosts if provided, otherwise assign all team members
        // Phase 1: Default to assignAllTeamMembers for simplicity
        // Phase 2: Will support service-specific staff via service_staff table
        if (isset($data['hosts']) && !empty($data['hosts'])) {
            // Specific hosts provided (format: [{userId: 123, mandatory: true, priority: "high"}])
            $payload['hosts'] = $data['hosts'];
        } else {
            // Default: Assign all team members automatically
            $payload['assignAllTeamMembers'] = $data['assignAllTeamMembers'] ?? true;
        }

        return Http::withHeaders($this->getHeaders())
            ->post($this->getTeamUrl('event-types'), $payload);
    }

    /**
     * PATCH /v2/teams/{teamId}/event-types/{id} - Update an event type
     *
     * CRITICAL: Must use team-scoped endpoint.
     */
    public function updateEventType(int $eventTypeId, array $data): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->patch($this->getTeamUrl("event-types/{$eventTypeId}"), $data);
    }

    /**
     * DELETE /v2/teams/{teamId}/event-types/{id} - Delete an event type
     *
     * CRITICAL: Must use team-scoped endpoint.
     */
    public function deleteEventType(int $eventTypeId): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->delete($this->getTeamUrl("event-types/{$eventTypeId}"));
    }

    /**
     * GET /v2/teams/{teamId}/event-types - Get all event types
     *
     * CRITICAL: Must use team-scoped endpoint. Global /v2/event-types returns 404.
     */
    public function getEventTypes(): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->get($this->getTeamUrl('event-types'));
    }

    /**
     * GET /v2/teams/{teamId}/event-types/{id} - Get single event type
     *
     * CRITICAL: Must use team-scoped endpoint. Global /v2/event-types/{id} returns 404.
     */
    public function getEventType(int $eventTypeId): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->get($this->getTeamUrl("event-types/{$eventTypeId}"));
    }

    /**
     * GET /v2/bookings - Get bookings
     */
    public function getBookings(array $filters = []): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl}/bookings", $filters);
    }

    /**
     * GET /v2/bookings/{id} - Get single booking
     */
    public function getBooking(int $bookingId): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl}/bookings/{$bookingId}");
    }

    /**
     * POST /v2/webhooks - Register a webhook
     */
    public function registerWebhook(string $url, array $triggers): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/webhooks", [
                'subscriberUrl' => $url,
                'eventTriggers' => $triggers, // ["booking.created", "booking.cancelled"]
                'active' => true,
                'secret' => config('services.calcom.webhook_secret')
            ]);
    }

    /**
     * GET /v2/webhooks - List webhooks
     */
    public function getWebhooks(): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl}/webhooks");
    }

    /**
     * DELETE /v2/webhooks/{id} - Delete a webhook
     */
    public function deleteWebhook(int $webhookId): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->delete("{$this->baseUrl}/webhooks/{$webhookId}");
    }

    /**
     * Reserve a slot temporarily
     *
     * CRITICAL: Must use team-scoped endpoint.
     */
    public function reserveSlot(int $eventTypeId, string $start, string $end): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->post($this->getTeamUrl('slots/reserve'), [
                'eventTypeId' => $eventTypeId,
                'start' => $start,
                'end' => $end,
                'timeZone' => 'Europe/Berlin'
            ]);
    }

    /**
     * Release a reserved slot
     *
     * CRITICAL: Must use team-scoped endpoint.
     */
    public function releaseSlot(string $reservationId): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->delete($this->getTeamUrl("slots/reserve/{$reservationId}"));
    }
}