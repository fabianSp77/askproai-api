<?php

namespace Tests\Unit\MCP;

use App\Models\Branch;
use App\Models\CalcomEventType;
use App\Models\Company;
use App\Services\CalcomV2Service;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use App\Services\MCP\Servers\CalcomMCPServer;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CalcomMCPServerTest extends TestCase
{
    private CalcomMCPServer $server;
    private $mockCalcomService;
    private Company $company;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockCalcomService = Mockery::mock(CalcomV2Service::class);
        $this->server = new CalcomMCPServer($this->mockCalcomService);
        
        $this->company = Company::factory()->create([
            'calcom_api_key' => 'test_api_key'
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'calcom_event_type_id' => 54321
        ]);
    }

    #[Test]

    public function test_can_handle_calcom_methods()
    {
        $this->assertTrue($this->server->canHandle('calcom.availability'));
        $this->assertTrue($this->server->canHandle('calcom.booking.create'));
        $this->assertTrue($this->server->canHandle('calcom.booking.cancel'));
        $this->assertTrue($this->server->canHandle('calcom.event_types'));
        $this->assertFalse($this->server->canHandle('database.query'));
    }

    #[Test]

    public function test_check_availability()
    {
        $request = new MCPRequest([
            'method' => 'calcom.availability',
            'params' => [
                'event_type_id' => 54321,
                'date_from' => '2025-06-25',
                'date_to' => '2025-06-25',
                'timezone' => 'Europe/Berlin'
            ]
        ]);
        
        $mockSlots = [
            ['time' => '2025-06-25T09:00:00+02:00'],
            ['time' => '2025-06-25T09:30:00+02:00'],
            ['time' => '2025-06-25T10:00:00+02:00']
        ];
        
        $this->mockCalcomService->shouldReceive('getAvailableSlots')
            ->once()
            ->with(Mockery::on(function($params) {
                return $params['eventTypeId'] == 54321 &&
                       $params['dateFrom'] == '2025-06-25';
            }))
            ->andReturn(['slots' => $mockSlots]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertCount(3, $response->getData()['slots']);
        $this->assertEquals('09:00', $response->getData()['slots'][0]);
    }

    #[Test]

    public function test_create_booking()
    {
        $request = new MCPRequest([
            'method' => 'calcom.booking.create',
            'params' => [
                'event_type_id' => 54321,
                'start_time' => '2025-06-25 10:00:00',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'notes' => 'First appointment'
            ]
        ]);
        
        $mockBooking = [
            'id' => 123456,
            'uid' => 'booking_uid_123',
            'startTime' => '2025-06-25T10:00:00+02:00',
            'endTime' => '2025-06-25T10:30:00+02:00'
        ];
        
        $this->mockCalcomService->shouldReceive('createBooking')
            ->once()
            ->with(Mockery::on(function($params) {
                return $params['eventTypeId'] == 54321 &&
                       $params['name'] == 'John Doe';
            }))
            ->andReturn($mockBooking);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(123456, $response->getData()['id']);
        $this->assertEquals('booking_uid_123', $response->getData()['uid']);
    }

    #[Test]

    public function test_cancel_booking()
    {
        $request = new MCPRequest([
            'method' => 'calcom.booking.cancel',
            'params' => [
                'booking_id' => 123456,
                'reason' => 'Customer request'
            ]
        ]);
        
        $this->mockCalcomService->shouldReceive('cancelBooking')
            ->once()
            ->with(123456, 'Customer request')
            ->andReturn(['success' => true]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->getData()['cancelled']);
    }

    #[Test]

    public function test_get_event_types()
    {
        $request = new MCPRequest([
            'method' => 'calcom.event_types',
            'params' => [
                'company_id' => $this->company->id
            ]
        ]);
        
        $mockEventTypes = [
            [
                'id' => 54321,
                'title' => 'Consultation',
                'slug' => 'consultation',
                'duration' => 30
            ],
            [
                'id' => 54322,
                'title' => 'Follow-up',
                'slug' => 'follow-up',
                'duration' => 15
            ]
        ];
        
        $this->mockCalcomService->shouldReceive('getEventTypes')
            ->once()
            ->andReturn(['event_types' => $mockEventTypes]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertCount(2, $response->getData()['event_types']);
        $this->assertEquals('Consultation', $response->getData()['event_types'][0]['title']);
    }

    #[Test]

    public function test_handle_api_error()
    {
        $request = new MCPRequest([
            'method' => 'calcom.availability',
            'params' => [
                'event_type_id' => 99999 // Non-existent
            ]
        ]);
        
        $this->mockCalcomService->shouldReceive('getAvailableSlots')
            ->once()
            ->andThrow(new \Exception('Event type not found', 404));
        
        $response = $this->server->execute($request);
        
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('CALCOM_API_ERROR', $response->getError()->getCode());
        $this->assertStringContainsString('Event type not found', $response->getError()->getMessage());
    }

    #[Test]

    public function test_validate_required_params()
    {
        $request = new MCPRequest([
            'method' => 'calcom.booking.create',
            'params' => [
                // Missing required params
                'name' => 'John Doe'
            ]
        ]);
        
        $response = $this->server->execute($request);
        
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('MISSING_PARAMS', $response->getError()->getCode());
    }

    #[Test]

    public function test_reschedule_booking()
    {
        $request = new MCPRequest([
            'method' => 'calcom.booking.reschedule',
            'params' => [
                'booking_id' => 123456,
                'new_start_time' => '2025-06-26 14:00:00',
                'reason' => 'Schedule conflict'
            ]
        ]);
        
        $this->mockCalcomService->shouldReceive('rescheduleBooking')
            ->once()
            ->with(123456, Mockery::any())
            ->andReturn([
                'id' => 123457,
                'startTime' => '2025-06-26T14:00:00+02:00'
            ]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(123457, $response->getData()['id']);
    }

    #[Test]

    public function test_bulk_availability_check()
    {
        $request = new MCPRequest([
            'method' => 'calcom.availability.bulk',
            'params' => [
                'event_type_ids' => [54321, 54322],
                'date' => '2025-06-25'
            ]
        ]);
        
        $this->mockCalcomService->shouldReceive('getAvailableSlots')
            ->twice()
            ->andReturn(
                ['slots' => [['time' => '2025-06-25T09:00:00+02:00']]],
                ['slots' => [['time' => '2025-06-25T10:00:00+02:00']]]
            );
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertArrayHasKey('54321', $response->getData()['availability']);
        $this->assertArrayHasKey('54322', $response->getData()['availability']);
    }

    #[Test]

    public function test_sync_event_types()
    {
        $request = new MCPRequest([
            'method' => 'calcom.sync.event_types',
            'params' => [
                'company_id' => $this->company->id
            ]
        ]);
        
        $mockEventTypes = [
            ['id' => 54321, 'title' => 'Updated Consultation'],
            ['id' => 54323, 'title' => 'New Service']
        ];
        
        $this->mockCalcomService->shouldReceive('getEventTypes')
            ->once()
            ->andReturn(['event_types' => $mockEventTypes]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(2, $response->getData()['synced_count']);
        $this->assertArrayHasKey('created', $response->getData());
        $this->assertArrayHasKey('updated', $response->getData());
    }

    #[Test]

    public function test_handle_rate_limiting()
    {
        $request = new MCPRequest([
            'method' => 'calcom.availability',
            'params' => ['event_type_id' => 54321]
        ]);
        
        $this->mockCalcomService->shouldReceive('getAvailableSlots')
            ->once()
            ->andThrow(new \Exception('Rate limit exceeded', 429));
        
        $response = $this->server->execute($request);
        
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $response->getError()->getCode());
        $this->assertEquals(429, $response->getError()->getStatusCode());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}