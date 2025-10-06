<?php

namespace Tests\Mocks;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;
use Carbon\Carbon;

/**
 * Mock server for Cal.com V2 API testing
 * Simulates all Cal.com V2 endpoints with configurable responses
 */
class CalcomV2MockServer
{
    private static array $scenarios = [];
    private static string $defaultScenario = 'success';
    private static array $bookings = [];
    private static array $eventTypes = [];
    private static int $bookingIdCounter = 1000;
    private static int $eventTypeIdCounter = 100;

    /**
     * Initialize the mock server with fake HTTP responses
     */
    public static function setUp(): void
    {
        self::reset();
        self::registerFakeResponses();
    }

    /**
     * Reset the mock server state
     */
    public static function reset(): void
    {
        self::$scenarios = [];
        self::$bookings = [];
        self::$eventTypes = [];
        self::$bookingIdCounter = 1000;
        self::$eventTypeIdCounter = 100;
        self::$defaultScenario = 'success';
    }

    /**
     * Set the scenario for next requests
     */
    public static function setScenario(string $scenario): void
    {
        self::$defaultScenario = $scenario;
    }

    /**
     * Register all fake HTTP responses
     */
    private static function registerFakeResponses(): void
    {
        Http::fake([
            // GET /v2/slots - Available slots
            'https://api.cal.com/v2/slots*' => function (Request $request) {
                return self::handleGetSlots($request);
            },

            // POST /v2/slots/reserve - Reserve slot
            'https://api.cal.com/v2/slots/reserve' => function (Request $request) {
                return self::handleReserveSlot($request);
            },

            // DELETE /v2/slots/reserve/* - Release reservation
            'https://api.cal.com/v2/slots/reserve/*' => function (Request $request) {
                return self::handleReleaseSlot($request);
            },

            // POST /v2/bookings - Create booking
            'https://api.cal.com/v2/bookings' => function (Request $request) {
                return self::handleCreateBooking($request);
            },

            // GET /v2/bookings - List bookings
            'https://api.cal.com/v2/bookings' => function (Request $request) {
                if ($request->method() === 'GET') {
                    return self::handleGetBookings($request);
                }
                return self::handleCreateBooking($request);
            },

            // GET /v2/bookings/{id} - Get single booking
            'https://api.cal.com/v2/bookings/*' => function (Request $request) {
                $id = basename($request->url());

                if ($request->method() === 'GET') {
                    return self::handleGetBooking($id);
                } elseif ($request->method() === 'PATCH') {
                    return self::handleRescheduleBooking($id, $request);
                } elseif ($request->method() === 'DELETE') {
                    return self::handleCancelBooking($id, $request);
                }
            },

            // Event Types endpoints
            'https://api.cal.com/v2/event-types*' => function (Request $request) {
                return self::handleEventTypes($request);
            },

            // Webhooks endpoints
            'https://api.cal.com/v2/webhooks*' => function (Request $request) {
                return self::handleWebhooks($request);
            },
        ]);
    }

    /**
     * Handle GET /v2/slots
     */
    private static function handleGetSlots(Request $request): array
    {
        $scenario = self::$scenarios['getSlots'] ?? self::$defaultScenario;

        if ($scenario === 'error') {
            return [
                'status' => 500,
                'body' => json_encode(['error' => 'Internal server error'])
            ];
        }

        if ($scenario === 'no_availability') {
            return [
                'status' => 200,
                'body' => json_encode([
                    'status' => 'success',
                    'data' => [
                        'slots' => []
                    ]
                ])
            ];
        }

        // Generate mock slots
        $start = Carbon::parse($request->data()['startTime'] ?? 'tomorrow 9am');
        $end = Carbon::parse($request->data()['endTime'] ?? 'tomorrow 6pm');

        $slots = [];
        $current = $start->copy();

        while ($current->lt($end)) {
            // Skip lunch hours (12-1pm)
            if ($current->hour !== 12) {
                $slots[] = [
                    'start' => $current->toIso8601String(),
                    'end' => $current->copy()->addMinutes(30)->toIso8601String(),
                ];
            }
            $current->addMinutes(30);
        }

        return [
            'status' => 200,
            'body' => json_encode([
                'status' => 'success',
                'data' => [
                    'slots' => $slots
                ]
            ])
        ];
    }

    /**
     * Handle POST /v2/bookings
     */
    private static function handleCreateBooking(Request $request): array
    {
        $scenario = self::$scenarios['createBooking'] ?? self::$defaultScenario;

        if ($scenario === 'conflict') {
            return [
                'status' => 409,
                'body' => json_encode([
                    'error' => 'Slot already booked',
                    'code' => 'SLOT_UNAVAILABLE'
                ])
            ];
        }

        if ($scenario === 'error') {
            return [
                'status' => 500,
                'body' => json_encode(['error' => 'Internal server error'])
            ];
        }

        $data = $request->data();
        $bookingId = self::$bookingIdCounter++;

        $booking = [
            'id' => $bookingId,
            'uid' => 'mock-' . uniqid(),
            'eventTypeId' => $data['eventTypeId'],
            'title' => 'Mock Booking',
            'description' => '',
            'start' => $data['start'],
            'end' => $data['end'],
            'timeZone' => $data['timeZone'] ?? 'Europe/Berlin',
            'attendees' => [
                [
                    'name' => $data['responses']['name'] ?? 'Test User',
                    'email' => $data['responses']['email'] ?? 'test@example.com',
                    'timeZone' => $data['timeZone'] ?? 'Europe/Berlin',
                ]
            ],
            'status' => 'ACCEPTED',
            'metadata' => $data['metadata'] ?? [],
            'createdAt' => Carbon::now()->toIso8601String(),
        ];

        self::$bookings[$bookingId] = $booking;

        return [
            'status' => 201,
            'body' => json_encode([
                'status' => 'success',
                'data' => $booking
            ])
        ];
    }

    /**
     * Handle PATCH /v2/bookings/{id}
     */
    private static function handleRescheduleBooking(string $id, Request $request): array
    {
        $scenario = self::$scenarios['rescheduleBooking'] ?? self::$defaultScenario;

        if ($scenario === 'not_found' || !isset(self::$bookings[$id])) {
            return [
                'status' => 404,
                'body' => json_encode(['error' => 'Booking not found'])
            ];
        }

        if ($scenario === 'error') {
            return [
                'status' => 500,
                'body' => json_encode(['error' => 'Failed to reschedule'])
            ];
        }

        $data = $request->data();
        self::$bookings[$id]['start'] = $data['start'];
        self::$bookings[$id]['end'] = $data['end'];
        self::$bookings[$id]['rescheduled'] = true;
        self::$bookings[$id]['rescheduledAt'] = Carbon::now()->toIso8601String();

        return [
            'status' => 200,
            'body' => json_encode([
                'status' => 'success',
                'data' => self::$bookings[$id]
            ])
        ];
    }

    /**
     * Handle DELETE /v2/bookings/{id}
     */
    private static function handleCancelBooking(string $id, Request $request): array
    {
        $scenario = self::$scenarios['cancelBooking'] ?? self::$defaultScenario;

        if ($scenario === 'not_found' || !isset(self::$bookings[$id])) {
            return [
                'status' => 404,
                'body' => json_encode(['error' => 'Booking not found'])
            ];
        }

        if ($scenario === 'error') {
            return [
                'status' => 500,
                'body' => json_encode(['error' => 'Failed to cancel'])
            ];
        }

        self::$bookings[$id]['status'] = 'CANCELLED';
        self::$bookings[$id]['cancelledAt'] = Carbon::now()->toIso8601String();
        self::$bookings[$id]['cancellationReason'] = $request->data()['cancellationReason'] ?? 'User requested';

        return [
            'status' => 200,
            'body' => json_encode([
                'status' => 'success',
                'data' => [
                    'message' => 'Booking cancelled successfully'
                ]
            ])
        ];
    }

    /**
     * Handle GET /v2/bookings
     */
    private static function handleGetBookings(Request $request): array
    {
        return [
            'status' => 200,
            'body' => json_encode([
                'status' => 'success',
                'data' => array_values(self::$bookings)
            ])
        ];
    }

    /**
     * Handle GET /v2/bookings/{id}
     */
    private static function handleGetBooking(string $id): array
    {
        if (!isset(self::$bookings[$id])) {
            return [
                'status' => 404,
                'body' => json_encode(['error' => 'Booking not found'])
            ];
        }

        return [
            'status' => 200,
            'body' => json_encode([
                'status' => 'success',
                'data' => self::$bookings[$id]
            ])
        ];
    }

    /**
     * Handle POST /v2/slots/reserve
     */
    private static function handleReserveSlot(Request $request): array
    {
        $scenario = self::$scenarios['reserveSlot'] ?? self::$defaultScenario;

        if ($scenario === 'unavailable') {
            return [
                'status' => 409,
                'body' => json_encode([
                    'error' => 'Slot not available for reservation',
                    'code' => 'SLOT_UNAVAILABLE'
                ])
            ];
        }

        return [
            'status' => 201,
            'body' => json_encode([
                'status' => 'success',
                'data' => [
                    'reservationId' => 'res_' . uniqid(),
                    'expiresAt' => Carbon::now()->addMinutes(10)->toIso8601String(),
                ]
            ])
        ];
    }

    /**
     * Handle DELETE /v2/slots/reserve/{id}
     */
    private static function handleReleaseSlot(Request $request): array
    {
        return [
            'status' => 200,
            'body' => json_encode([
                'status' => 'success',
                'data' => [
                    'message' => 'Reservation released successfully'
                ]
            ])
        ];
    }

    /**
     * Handle event types endpoints
     */
    private static function handleEventTypes(Request $request): array
    {
        if ($request->method() === 'POST') {
            $data = $request->data();
            $id = self::$eventTypeIdCounter++;

            $eventType = [
                'id' => $id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'lengthInMinutes' => $data['lengthInMinutes'],
                'hidden' => $data['hidden'] ?? false,
            ];

            self::$eventTypes[$id] = $eventType;

            return [
                'status' => 201,
                'body' => json_encode([
                    'status' => 'success',
                    'data' => $eventType
                ])
            ];
        }

        return [
            'status' => 200,
            'body' => json_encode([
                'status' => 'success',
                'data' => array_values(self::$eventTypes)
            ])
        ];
    }

    /**
     * Handle webhooks endpoints
     */
    private static function handleWebhooks(Request $request): array
    {
        if ($request->method() === 'POST') {
            return [
                'status' => 201,
                'body' => json_encode([
                    'status' => 'success',
                    'data' => [
                        'id' => uniqid('webhook_'),
                        'subscriberUrl' => $request->data()['subscriberUrl'],
                        'eventTriggers' => $request->data()['eventTriggers'],
                        'active' => true,
                    ]
                ])
            ];
        }

        return [
            'status' => 200,
            'body' => json_encode([
                'status' => 'success',
                'data' => []
            ])
        ];
    }

    /**
     * Add a specific scenario for testing
     */
    public static function addScenario(string $endpoint, string $scenario): void
    {
        self::$scenarios[$endpoint] = $scenario;
    }

    /**
     * Get all created bookings (for assertions)
     */
    public static function getBookings(): array
    {
        return self::$bookings;
    }

    /**
     * Get a specific booking
     */
    public static function getBooking(int $id): ?array
    {
        return self::$bookings[$id] ?? null;
    }
}