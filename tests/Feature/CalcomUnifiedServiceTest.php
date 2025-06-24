<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CalcomUnifiedService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class CalcomUnifiedServiceTest extends TestCase
{
    protected CalcomUnifiedService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        Config::set('services.calcom.api_key', 'test_api_key');
        Config::set('services.calcom.team_slug', 'test-team');
        Config::set('services.calcom.api_version', 'v2');
        Config::set('services.calcom.enable_fallback', true);
        Config::set('services.calcom.v2_api_version', '2024-08-13');
        
        $this->service = new CalcomUnifiedService();
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_can_fetch_event_types_with_v2_api()
    {
        Http::fake([
            'https://api.cal.com/v2/event-types*' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'id' => 12345,
                        'title' => 'Test Event Type',
                        'slug' => 'test-event',
                        'length' => 30
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->getEventTypes();

        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertEquals(12345, $result[0]['id']);
        $this->assertEquals('Test Event Type', $result[0]['title']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_falls_back_to_v1_when_v2_fails_for_event_types()
    {
        Http::fake([
            'https://api.cal.com/v2/event-types*' => Http::response(null, 403),
            'https://api.cal.com/v1/event-types*' => Http::response([
                'event_types' => [
                    [
                        'id' => 12345,
                        'title' => 'Test Event Type V1',
                        'slug' => 'test-event-v1',
                        'length' => 30
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->getEventTypes();

        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertEquals('Test Event Type V1', $result['event_types'][0]['title']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_can_check_availability_with_v2_api()
    {
        $eventTypeId = 12345;
        $dateFrom = Carbon::now()->addDay()->toIso8601String();
        $dateTo = Carbon::now()->addDays(2)->toIso8601String();

        Http::fake([
            'https://api.cal.com/v2/slots/available*' => Http::response([
                'status' => 'success',
                'data' => [
                    'slots' => [
                        '2024-06-15' => [
                            ['time' => '2024-06-15T09:00:00+00:00'],
                            ['time' => '2024-06-15T10:00:00+00:00'],
                            ['time' => '2024-06-15T11:00:00+00:00']
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->checkAvailability($eventTypeId, $dateFrom, $dateTo);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('slots', $result);
        $this->assertCount(3, $result['slots']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_normalizes_v2_availability_response_to_v1_format()
    {
        $eventTypeId = 12345;
        $dateFrom = Carbon::now()->addDay()->toIso8601String();
        $dateTo = Carbon::now()->addDays(2)->toIso8601String();

        Http::fake([
            'https://api.cal.com/v2/slots/available*' => Http::response([
                'status' => 'success',
                'data' => [
                    'slots' => [
                        '2024-06-15' => [
                            ['time' => '2024-06-15T09:00:00+00:00'],
                            ['time' => '2024-06-15T10:00:00+00:00']
                        ],
                        '2024-06-16' => [
                            ['time' => '2024-06-16T14:00:00+00:00']
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->checkAvailability($eventTypeId, $dateFrom, $dateTo);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('slots', $result);
        $this->assertCount(3, $result['slots']);
        
        // Check normalized structure
        $firstSlot = $result['slots'][0];
        $this->assertArrayHasKey('time', $firstSlot);
        $this->assertArrayHasKey('date', $firstSlot);
        $this->assertEquals('2024-06-15', $firstSlot['date']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_can_create_booking_with_v2_api()
    {
        $eventTypeId = 12345;
        $startTime = Carbon::now()->addDay()->setHour(14)->toIso8601String();
        $customerData = [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+491234567890'
        ];
        $notes = 'Test booking notes';

        Http::fake([
            'https://api.cal.com/v2/bookings*' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 98765,
                    'uid' => 'abc123xyz',
                    'title' => 'Test Booking',
                    'start' => $startTime,
                    'end' => Carbon::parse($startTime)->addMinutes(30)->toIso8601String(),
                    'status' => 'ACCEPTED',
                    'attendees' => [
                        [
                            'name' => 'Test Customer',
                            'email' => 'test@example.com'
                        ]
                    ]
                ]
            ], 201)
        ]);

        $result = $this->service->bookAppointment($eventTypeId, $startTime, null, $customerData, $notes);

        $this->assertNotNull($result);
        $this->assertEquals(98765, $result['id']);
        $this->assertEquals('abc123xyz', $result['uid']);
        $this->assertEquals('ACCEPTED', $result['status']);
        $this->assertEquals('v2', $result['api_version']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_includes_phone_in_notes_for_v2_booking()
    {
        $eventTypeId = 12345;
        $startTime = Carbon::now()->addDay()->setHour(14)->toIso8601String();
        $customerData = [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+491234567890'
        ];

        Http::fake([
            'https://api.cal.com/v2/bookings*' => Http::sequence()
                ->push(['status' => 'success', 'data' => ['id' => 1]], 201)
        ]);

        $this->service->bookAppointment($eventTypeId, $startTime, null, $customerData);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['metadata']['notes']) && 
                   str_contains($data['metadata']['notes'], 'Telefon: +491234567890');
        });
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_can_get_booking_details()
    {
        $bookingId = 'abc123xyz';

        Http::fake([
            'https://api.cal.com/v2/bookings/abc123xyz*' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 98765,
                    'uid' => 'abc123xyz',
                    'title' => 'Test Booking',
                    'status' => 'ACCEPTED'
                ]
            ], 200)
        ]);

        $result = $this->service->getBooking($bookingId);

        $this->assertNotNull($result);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('abc123xyz', $result['data']['uid']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_can_cancel_booking()
    {
        $bookingId = 'abc123xyz';
        $reason = 'Customer requested cancellation';

        Http::fake([
            'https://api.cal.com/v2/bookings/abc123xyz/cancel*' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 98765,
                    'uid' => 'abc123xyz',
                    'status' => 'CANCELLED'
                ]
            ], 200)
        ]);

        $result = $this->service->cancelBooking($bookingId, $reason);

        $this->assertNotNull($result);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('CANCELLED', $result['data']['status']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_can_test_api_connectivity()
    {
        Http::fake([
            'https://api.cal.com/v1/event-types*' => Http::response(null, 403),
            'https://api.cal.com/v2/event-types*' => Http::response([
                'status' => 'success',
                'data' => []
            ], 200)
        ]);

        $results = $this->service->testConnection();

        $this->assertFalse($results['v1']);
        $this->assertTrue($results['v2']);
        $this->assertEquals('v2', $results['recommended_version']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_handles_v1_api_when_configured()
    {
        Config::set('services.calcom.api_version', 'v1');
        $this->service = new CalcomUnifiedService();

        $eventTypeId = 12345;
        $dateFrom = Carbon::now()->addDay()->toIso8601String();
        $dateTo = Carbon::now()->addDays(2)->toIso8601String();

        Http::fake([
            'https://api.cal.com/v1/availability*' => Http::response([
                'slots' => [
                    ['time' => '2024-06-15T09:00:00+00:00'],
                    ['time' => '2024-06-15T10:00:00+00:00']
                ]
            ], 200)
        ]);

        $result = $this->service->checkAvailability($eventTypeId, $dateFrom, $dateTo);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('slots', $result);
        $this->assertCount(2, $result['slots']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_properly_formats_v1_booking_request()
    {
        Config::set('services.calcom.api_version', 'v1');
        $this->service = new CalcomUnifiedService();

        $eventTypeId = 12345;
        $startTime = Carbon::now()->addDay()->setHour(14)->toIso8601String();
        $customerData = [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+491234567890'
        ];

        Http::fake([
            'https://api.cal.com/v1/bookings*' => Http::response([
                'id' => 98765,
                'uid' => 'abc123xyz',
                'title' => 'Test Booking',
                'status' => 'ACCEPTED'
            ], 201)
        ]);

        $result = $this->service->bookAppointment($eventTypeId, $startTime, null, $customerData);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['responses']['name']) && 
                   $data['responses']['name'] === 'Test Customer' &&
                   isset($data['responses']['notes']) &&
                   str_contains($data['responses']['notes'], 'Telefon: +491234567890');
        });
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            'https://api.cal.com/v2/event-types*' => Http::response([
                'error' => 'Unauthorized'
            ], 401)
        ]);

        $result = $this->service->getEventTypes();

        $this->assertNull($result);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function it_disables_fallback_when_configured()
    {
        Config::set('services.calcom.enable_fallback', false);
        $this->service = new CalcomUnifiedService();

        Http::fake([
            'https://api.cal.com/v2/event-types*' => Http::response(null, 403),
            'https://api.cal.com/v1/event-types*' => Http::response([
                'event_types' => []
            ], 200)
        ]);

        $result = $this->service->getEventTypes();

        // Should not fall back to v1
        $this->assertNull($result);
        
        // Verify v1 was not called
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'v1');
        });
    }
}