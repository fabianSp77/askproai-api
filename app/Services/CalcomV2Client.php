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
     */
    public function createBooking(array $data): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->retry(3, 200, function ($exception, $request) {
                $status = optional($exception->response)->status();
                if (in_array($status, [409, 429])) {
                    // Exponential backoff outside the closure
                    usleep(pow(2, $request->retries) * 1000000); // 2s, 4s, 8s
                    return true;
                }
                return false;
            })
            ->post("{$this->baseUrl}/bookings", [
                'eventTypeId' => $data['eventTypeId'],
                'start' => $data['start'],
                'end' => $data['end'],
                'timeZone' => $data['timeZone'], // camelCase!
                'language' => 'de',
                'metadata' => $data['metadata'] ?? (object)[],
                'responses' => [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'guests' => [],
                    'location' => ['optionValue' => '', 'value' => 'Vor Ort']
                ],
                'instant' => false,
                'noEmail' => true // No Cal.com emails!
            ]);
    }

    /**
     * DELETE /v2/bookings/{id} - Cancel a booking
     */
    public function cancelBooking(int $bookingId, string $reason = ''): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->delete("{$this->baseUrl}/bookings/{$bookingId}", [
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
        return Http::withHeaders($this->getHeaders())
            ->post($this->getTeamUrl('event-types'), [
                'title' => $data['name'], // e.g. "ACME-BER-FARBE-A-S123"
                'slug' => Str::slug($data['name']),
                'description' => $data['description'] ?? '',
                'lengthInMinutes' => $data['duration'],
                'hidden' => true, // IMPORTANT: Hidden from public!
                'disableGuests' => true,
                'hideCalendarNotes' => true,
                'requiresConfirmation' => false,
                'bookingFields' => [
                    ['name' => 'name', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'type' => 'email', 'required' => true]
                ],
                'locations' => [['type' => 'inPerson']]
            ]);
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