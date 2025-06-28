<?php

namespace Tests\E2E\Mocks;

use App\Services\Calcom\CalcomV2Client;
use App\Services\Calcom\DTOs\AttendeeDTO;
use App\Services\Calcom\DTOs\BookingDTO;
use App\Services\Calcom\DTOs\EventTypeDTO;
use App\Services\Calcom\DTOs\ScheduleDTO;
use App\Services\Calcom\DTOs\SlotDTO;
use App\Services\Calcom\Exceptions\CalcomApiException;
use App\Services\Calcom\Exceptions\CalcomRateLimitException;
use App\Services\Calcom\Exceptions\CalcomValidationException;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class MockCalcomV2Client extends CalcomV2Client
{
    protected array $mockResponses = [];
    protected array $requestHistory = [];
    protected bool $shouldFail = false;
    protected ?string $failureType = null;
    protected int $requestCount = 0;
    protected array $createdBookings = [];
    protected array $availableSlots = [];

    public function __construct()
    {
        // Don't call parent constructor to avoid HTTP client setup
    }

    /**
     * Set mock response for next request
     */
    public function mockResponse($response): self
    {
        $this->mockResponses[] = $response;
        return $this;
    }

    /**
     * Configure client to fail with specific error
     */
    public function shouldFail(string $type = 'generic', int $afterRequests = 0): self
    {
        $this->shouldFail = true;
        $this->failureType = $type;
        $this->requestCount = $afterRequests;
        return $this;
    }

    /**
     * Reset mock state
     */
    public function reset(): void
    {
        $this->mockResponses = [];
        $this->requestHistory = [];
        $this->shouldFail = false;
        $this->failureType = null;
        $this->requestCount = 0;
        $this->createdBookings = [];
        $this->availableSlots = [];
    }

    /**
     * Get request history
     */
    public function getRequestHistory(): array
    {
        return $this->requestHistory;
    }

    /**
     * Get created bookings
     */
    public function getCreatedBookings(): array
    {
        return $this->createdBookings;
    }

    /**
     * Set available slots for testing
     */
    public function setAvailableSlots(array $slots): self
    {
        $this->availableSlots = $slots;
        return $this;
    }

    /**
     * Mock implementation of getEventTypes
     */
    public function getEventTypes(): array
    {
        $this->recordRequest('GET', '/event-types');

        if ($this->shouldFailNow()) {
            $this->throwFailure();
        }

        if (!empty($this->mockResponses)) {
            return array_shift($this->mockResponses);
        }

        // Default response
        return [
            new EventTypeDTO([
                'id' => 12345,
                'title' => 'Mock Consultation',
                'slug' => 'mock-consultation',
                'description' => 'A mock consultation for testing',
                'length' => 30,
                'locations' => [
                    ['type' => 'inPerson', 'address' => 'Test Address']
                ],
                'price' => 0,
                'currency' => 'EUR',
                'metadata' => [],
            ]),
            new EventTypeDTO([
                'id' => 12346,
                'title' => 'Mock Follow-up',
                'slug' => 'mock-followup',
                'description' => 'A mock follow-up for testing',
                'length' => 15,
                'locations' => [
                    ['type' => 'phone', 'phoneNumber' => '+1234567890']
                ],
                'price' => 0,
                'currency' => 'EUR',
                'metadata' => [],
            ]),
        ];
    }

    /**
     * Mock implementation of getAvailableSlots
     */
    public function getAvailableSlots(
        int $eventTypeId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $timeZone = null
    ): array {
        $this->recordRequest('GET', '/slots/available', [
            'eventTypeId' => $eventTypeId,
            'startTime' => $startDate->toIso8601String(),
            'endTime' => $endDate->toIso8601String(),
            'timeZone' => $timeZone ?? 'Europe/Berlin',
        ]);

        if ($this->shouldFailNow()) {
            $this->throwFailure();
        }

        if (!empty($this->mockResponses)) {
            return array_shift($this->mockResponses);
        }

        if (!empty($this->availableSlots)) {
            return $this->availableSlots;
        }

        // Generate default available slots
        $slots = [];
        $current = $startDate->copy()->setTime(9, 0);
        $end = $endDate->copy()->setTime(17, 0);

        while ($current->lte($end)) {
            // Skip weekends
            if (!$current->isWeekend()) {
                // Morning slots
                if ($current->hour >= 9 && $current->hour < 12) {
                    $slots[] = new SlotDTO([
                        'time' => $current->toIso8601String(),
                        'duration' => 30,
                        'workingHours' => [
                            'start' => $current->copy()->setTime(9, 0)->toIso8601String(),
                            'end' => $current->copy()->setTime(17, 0)->toIso8601String(),
                        ],
                    ]);
                }
                // Afternoon slots (skip lunch hour)
                if ($current->hour >= 14 && $current->hour < 17) {
                    $slots[] = new SlotDTO([
                        'time' => $current->toIso8601String(),
                        'duration' => 30,
                        'workingHours' => [
                            'start' => $current->copy()->setTime(9, 0)->toIso8601String(),
                            'end' => $current->copy()->setTime(17, 0)->toIso8601String(),
                        ],
                    ]);
                }
            }
            
            $current->addMinutes(30);
        }

        return $slots;
    }

    /**
     * Mock implementation of createBooking
     */
    public function createBooking(array $data): BookingDTO
    {
        $this->recordRequest('POST', '/bookings', $data);

        if ($this->shouldFailNow()) {
            $this->throwFailure();
        }

        if (!empty($this->mockResponses)) {
            $booking = array_shift($this->mockResponses);
            $this->createdBookings[] = $booking;
            return $booking;
        }

        // Create default booking response
        $bookingId = rand(10000, 99999);
        $uid = 'mock_' . Str::random(10);
        
        $booking = new BookingDTO([
            'id' => $bookingId,
            'uid' => $uid,
            'title' => $data['title'] ?? 'Mock Booking',
            'status' => 'accepted',
            'startTime' => $data['start'],
            'endTime' => $data['end'],
            'attendees' => [
                new AttendeeDTO([
                    'id' => rand(1000, 9999),
                    'email' => $data['responses']['email'] ?? 'mock@example.com',
                    'name' => $data['responses']['name'] ?? 'Mock Customer',
                    'timeZone' => $data['timeZone'] ?? 'Europe/Berlin',
                    'locale' => $data['language'] ?? 'de',
                ]),
            ],
            'user' => [
                'id' => $data['userId'] ?? 1,
                'email' => 'mock.staff@example.com',
                'name' => 'Mock Staff',
                'timeZone' => 'Europe/Berlin',
            ],
            'payment' => [],
            'metadata' => $data['metadata'] ?? [],
            'responses' => $data['responses'] ?? [],
            'location' => $data['location'] ?? 'In Person',
            'description' => $data['description'] ?? null,
            'rescheduled' => false,
            'createdAt' => Carbon::now()->toIso8601String(),
        ]);

        $this->createdBookings[] = $booking;
        return $booking;
    }

    /**
     * Mock implementation of getBooking
     */
    public function getBooking(string $uid): BookingDTO
    {
        $this->recordRequest('GET', "/bookings/{$uid}");

        if ($this->shouldFailNow()) {
            $this->throwFailure();
        }

        if (!empty($this->mockResponses)) {
            return array_shift($this->mockResponses);
        }

        // Check if we have this booking in our created bookings
        foreach ($this->createdBookings as $booking) {
            if ($booking->uid === $uid) {
                return $booking;
            }
        }

        // Return default booking
        return new BookingDTO([
            'id' => rand(10000, 99999),
            'uid' => $uid,
            'title' => 'Mock Retrieved Booking',
            'status' => 'accepted',
            'startTime' => Carbon::now()->addDay()->toIso8601String(),
            'endTime' => Carbon::now()->addDay()->addMinutes(30)->toIso8601String(),
            'attendees' => [
                new AttendeeDTO([
                    'id' => rand(1000, 9999),
                    'email' => 'retrieved@example.com',
                    'name' => 'Retrieved Customer',
                    'timeZone' => 'Europe/Berlin',
                ]),
            ],
            'user' => [
                'id' => 1,
                'email' => 'staff@example.com',
                'name' => 'Staff Member',
                'timeZone' => 'Europe/Berlin',
            ],
            'payment' => [],
            'metadata' => [],
            'responses' => [],
        ]);
    }

    /**
     * Mock implementation of cancelBooking
     */
    public function cancelBooking(string $uid, ?string $reason = null): bool
    {
        $this->recordRequest('DELETE', "/bookings/{$uid}/cancel", [
            'cancellationReason' => $reason,
        ]);

        if ($this->shouldFailNow()) {
            $this->throwFailure();
        }

        // Remove from created bookings if exists
        $this->createdBookings = array_filter($this->createdBookings, function ($booking) use ($uid) {
            return $booking->uid !== $uid;
        });

        return true;
    }

    /**
     * Mock implementation of rescheduleBooking
     */
    public function rescheduleBooking(string $uid, array $data): BookingDTO
    {
        $this->recordRequest('PATCH', "/bookings/{$uid}/reschedule", $data);

        if ($this->shouldFailNow()) {
            $this->throwFailure();
        }

        if (!empty($this->mockResponses)) {
            return array_shift($this->mockResponses);
        }

        // Find existing booking or create new
        $existingBooking = null;
        foreach ($this->createdBookings as $key => $booking) {
            if ($booking->uid === $uid) {
                $existingBooking = $booking;
                unset($this->createdBookings[$key]);
                break;
            }
        }

        $rescheduledBooking = new BookingDTO([
            'id' => $existingBooking ? $existingBooking->id : rand(10000, 99999),
            'uid' => 'rescheduled_' . Str::random(10),
            'title' => $existingBooking ? $existingBooking->title : 'Rescheduled Booking',
            'status' => 'accepted',
            'startTime' => $data['start'],
            'endTime' => $data['end'],
            'attendees' => $existingBooking ? $existingBooking->attendees : [
                new AttendeeDTO([
                    'id' => rand(1000, 9999),
                    'email' => 'rescheduled@example.com',
                    'name' => 'Rescheduled Customer',
                    'timeZone' => 'Europe/Berlin',
                ]),
            ],
            'user' => $existingBooking ? $existingBooking->user : [
                'id' => 1,
                'email' => 'staff@example.com',
                'name' => 'Staff Member',
                'timeZone' => 'Europe/Berlin',
            ],
            'payment' => [],
            'metadata' => array_merge(
                $existingBooking ? $existingBooking->metadata : [],
                ['rescheduled_from' => $uid]
            ),
            'responses' => $data['responses'] ?? [],
            'rescheduled' => true,
            'previousUid' => $uid,
        ]);

        $this->createdBookings[] = $rescheduledBooking;
        return $rescheduledBooking;
    }

    /**
     * Mock implementation of getSchedules
     */
    public function getSchedules(): array
    {
        $this->recordRequest('GET', '/schedules');

        if ($this->shouldFailNow()) {
            $this->throwFailure();
        }

        if (!empty($this->mockResponses)) {
            return array_shift($this->mockResponses);
        }

        return [
            new ScheduleDTO([
                'id' => 1,
                'name' => 'Default Schedule',
                'timeZone' => 'Europe/Berlin',
                'availability' => [
                    [
                        'days' => [1, 2, 3, 4, 5], // Monday to Friday
                        'startTime' => '09:00',
                        'endTime' => '17:00',
                    ],
                ],
                'isDefault' => true,
            ]),
        ];
    }

    /**
     * Record request for history
     */
    protected function recordRequest(string $method, string $endpoint, ?array $data = null): void
    {
        $this->requestHistory[] = [
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data,
            'timestamp' => now(),
        ];
    }

    /**
     * Check if should fail now
     */
    protected function shouldFailNow(): bool
    {
        if (!$this->shouldFail) {
            return false;
        }

        if ($this->requestCount > 0) {
            $this->requestCount--;
            return false;
        }

        return true;
    }

    /**
     * Throw appropriate failure
     */
    protected function throwFailure(): void
    {
        switch ($this->failureType) {
            case 'rate_limit':
                throw new CalcomRateLimitException('Rate limit exceeded', 429);
                
            case 'validation':
                throw new CalcomValidationException('Validation failed', 422);
                
            case 'not_found':
                throw new CalcomApiException('Resource not found', 404);
                
            case 'server_error':
                throw new CalcomApiException('Internal server error', 500);
                
            case 'timeout':
                throw new \Exception('Connection timeout');
                
            default:
                throw new CalcomApiException('Mock failure', 400);
        }
    }

    /**
     * Simulate network delay
     */
    public function withDelay(int $milliseconds): self
    {
        usleep($milliseconds * 1000);
        return $this;
    }

    /**
     * Get last request
     */
    public function getLastRequest(): ?array
    {
        return end($this->requestHistory) ?: null;
    }

    /**
     * Assert request was made
     */
    public function assertRequestMade(string $method, string $endpoint): bool
    {
        foreach ($this->requestHistory as $request) {
            if ($request['method'] === $method && $request['endpoint'] === $endpoint) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get request count
     */
    public function getRequestCount(): int
    {
        return count($this->requestHistory);
    }
}