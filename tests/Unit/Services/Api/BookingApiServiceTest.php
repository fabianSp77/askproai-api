<?php

namespace Tests\Unit\Services\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as HttpResponse;
use Tests\TestCase;
use App\Services\Api\BookingApiService;
use App\Services\Booking\CompositeBookingService;
use App\Services\CalcomV2Client;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\CalcomEventMap;
use App\Models\Branch;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mockery;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class BookingApiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingApiService $bookingService;
    protected $mockCompositeService;
    protected $mockCalcomClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCompositeService = Mockery::mock(CompositeBookingService::class);
        $this->mockCalcomClient = Mockery::mock(CalcomV2Client::class);

        $this->bookingService = new BookingApiService(
            $this->mockCompositeService,
            $this->mockCalcomClient
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeResponse(array $data, int $status = 200): HttpResponse
    {
        return new HttpResponse(
            new Psr7Response(
                $status,
                ['Content-Type' => 'application/json'],
                json_encode($data)
            )
        );
    }

    #[Test]
    public function it_creates_simple_booking_successfully(): void
    {
        // Arrange
        $service = Service::factory()->create([
            'composite' => false,
            'duration_minutes' => 60
        ]);

        $customer = Customer::factory()->create();

        $branch = Branch::factory()->create(['company_id' => $service->company_id]);

        $eventMapping = CalcomEventMap::factory()->create([
            'company_id' => $service->company_id,
            'service_id' => $service->id,
            'branch_id' => $branch->id,
            'staff_id' => null,
            'sync_status' => 'synced',
            'event_type_id' => 123
        ]);

        $bookingData = [
            'service_id' => $service->id,
            'branch_id' => 1,
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => Carbon::now()->addDay()->format('Y-m-d\TH:i:s'),
            'source' => 'api'
        ];

        $calcomResponse = $this->makeResponse([
            'data' => [
                'id' => 'cal_booking_123',
                'status' => 'confirmed'
            ]
        ]);

        $this->mockCalcomClient
            ->shouldReceive('createBooking')
            ->once()
            ->andReturn($calcomResponse);

        // Act
        $result = $this->bookingService->createBooking($bookingData);

        // Assert
        $this->assertArrayHasKey('appointment_id', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('confirmation_code', $result);
        $this->assertEquals('booked', $result['status']);
    }

    #[Test]
    public function it_creates_composite_booking_successfully(): void
    {
        // Arrange
        $service = Service::factory()->create([
            'composite' => true,
            'segments' => [
                ['key' => 'A', 'name' => 'Segment A', 'durationMin' => 30, 'gapAfterMin' => 30],
                ['key' => 'B', 'name' => 'Segment B', 'durationMin' => 30]
            ]
        ]);

        $appointment = Appointment::factory()->create([
            'composite_group_uid' => 'comp_123456',
            'status' => 'booked'
        ]);

        $bookingData = [
            'service_id' => $service->id,
            'branch_id' => 1,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => Carbon::now()->addDay()->format('Y-m-d\TH:i:s')
        ];

        $this->mockCompositeService
            ->shouldReceive('bookComposite')
            ->once()
            ->andReturn($appointment);

        // Act
        $result = $this->bookingService->createBooking($bookingData);

        // Assert
        $this->assertArrayHasKey('appointment_id', $result);
        $this->assertArrayHasKey('composite_uid', $result);
        $this->assertEquals('comp_123456', $result['composite_uid']);
    }

    #[Test]
    public function it_throws_exception_when_no_calcom_mapping_exists(): void
    {
        // Arrange
        $service = Service::factory()->create([
            'composite' => false
        ]);

        $bookingData = [
            'service_id' => $service->id,
            'branch_id' => 1,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => Carbon::now()->addDay()->format('Y-m-d\TH:i:s')
        ];

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No Cal.com event mapping found for this service');

        $this->bookingService->createBooking($bookingData);
    }

    #[Test]
    public function it_reschedules_simple_appointment_successfully(): void
    {
        // Arrange
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $appointment = Appointment::factory()->create([
            'service_id' => $service->id,
            'is_composite' => false,
            'calcom_v2_booking_id' => 123
        ]);

        $rescheduleData = [
            'start' => Carbon::now()->addDays(2)->format('Y-m-d\TH:i:s'),
            'timeZone' => 'Europe/Berlin',
            'reason' => 'Customer request'
        ];

        $calcomResponse = $this->makeResponse(['success' => true]);

        $this->mockCalcomClient
            ->shouldReceive('rescheduleBooking')
            ->once()
            ->andReturn($calcomResponse);

        // Act
        $result = $this->bookingService->rescheduleAppointment($appointment->id, $rescheduleData);

        // Assert
        $this->assertArrayHasKey('appointment_id', $result);
        $this->assertEquals($appointment->id, $result['appointment_id']);
    }

    #[Test]
    public function it_cancels_appointment_successfully(): void
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'is_composite' => false,
            'calcom_v2_booking_id' => 456,
            'status' => 'booked'
        ]);

        $calcomResponse = $this->makeResponse(['success' => true]);

        $this->mockCalcomClient
            ->shouldReceive('cancelBooking')
            ->once()
            ->andReturn($calcomResponse);

        // Act
        $result = $this->bookingService->cancelAppointment($appointment->id, 'Customer request');

        // Assert
        $this->assertArrayHasKey('appointment_id', $result);
        $this->assertEquals('cancelled', $result['status']);

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
    }

    #[Test]
    public function it_validates_booking_availability(): void
    {
        // Arrange
        $service = Service::factory()->create();
        $branchId = 1;
        $start = Carbon::now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i:s');

        $availabilityService = Mockery::mock(\App\Services\Booking\AvailabilityService::class);
        $availabilityService->shouldReceive('getAvailableSlots')
            ->once()
            ->andReturn(collect([
                ['start' => $start, 'end' => Carbon::parse($start)->addHour()->format('Y-m-d\TH:i:s')]
            ]));

        $this->app->instance(\App\Services\Booking\AvailabilityService::class, $availabilityService);

        // Act
        $isAvailable = $this->bookingService->validateBookingAvailability(
            $service->id,
            $branchId,
            $start
        );

        // Assert
        $this->assertTrue($isAvailable);
    }

    #[Test]
    public function it_builds_segments_correctly_from_service(): void
    {
        // Arrange
        $service = Service::factory()->create([
            'composite' => true,
            'segments' => [
                ['key' => 'A', 'name' => 'First', 'durationMin' => 30, 'gapAfterMin' => 15],
                ['key' => 'B', 'name' => 'Second', 'durationMin' => 45]
            ]
        ]);

        $startTime = Carbon::now()->addDay()->setTime(10, 0);
        $bookingData = [
            'service_id' => $service->id,
            'branch_id' => 1,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => $startTime->format('Y-m-d\TH:i:s')
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('buildSegmentsFromService');
        $method->setAccessible(true);

        // Act
        $segments = $method->invoke($this->bookingService, $service, $bookingData);

        // Assert
        $this->assertCount(2, $segments);

        $this->assertEquals('A', $segments[0]['key']);
        $this->assertEquals('First', $segments[0]['name']);
        $this->assertEquals(
            $startTime->format('Y-m-d\TH:i:s'),
            Carbon::parse($segments[0]['starts_at'])->format('Y-m-d\TH:i:s')
        );

        $this->assertEquals('B', $segments[1]['key']);
        $this->assertEquals('Second', $segments[1]['name']);
        $this->assertEquals(
            $startTime->addMinutes(45)->format('Y-m-d\TH:i:s'), // 30 min + 15 min gap
            Carbon::parse($segments[1]['starts_at'])->format('Y-m-d\TH:i:s')
        );
    }
}