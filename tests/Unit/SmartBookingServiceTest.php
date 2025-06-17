<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SmartBookingService;
use App\Services\CalcomV2Service;
use App\Services\RetellV2Service;
use App\Services\PhoneNumberResolver;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Carbon\Carbon;

class SmartBookingServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private SmartBookingService $service;
    private $mockCalcom;
    private $mockRetell;
    private $mockResolver;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->mockCalcom = Mockery::mock(CalcomV2Service::class);
        $this->mockRetell = Mockery::mock(RetellV2Service::class);
        $this->mockResolver = Mockery::mock(PhoneNumberResolver::class);
        
        // Create service with mocks
        $this->service = new SmartBookingService(
            $this->mockCalcom,
            $this->mockRetell,
            $this->mockResolver
        );
    }
    
    /** @test */
    public function it_processes_incoming_call_and_creates_appointment()
    {
        // Create test data
        $company = \App\Models\Company::factory()->create();
        $branch = Branch::factory()->create([
            'company_id' => $company->id,
            'active' => true,
            'calcom_event_type_id' => 12345
        ]);
        
        // Webhook data
        $webhookData = [
            'call_id' => 'call_test123',
            'to_number' => '+493012345678',
            'from_number' => '+491512345678',
            'agent_id' => 'agent_test',
            'status' => 'completed',
            'start_timestamp' => now()->subMinutes(5),
            'end_timestamp' => now(),
            'call_length' => 300,
            'appointment_requested' => true,
            '_customer_name' => 'Test Kunde',
            '_customer_email' => 'test@example.com',
            '_requested_date' => Carbon::tomorrow()->format('Y-m-d'),
            '_requested_time' => '14:00',
            '_service_type' => 'Beratung'
        ];
        
        // Mock resolver to return our branch
        $this->mockResolver->shouldReceive('resolveFromWebhook')
            ->andReturn([
                'branch_id' => $branch->id,
                'company_id' => $branch->company_id,
                'agent_id' => null
            ]);
        
        // Mock Cal.com availability
        $this->mockCalcom->shouldReceive('getAvailability')
            ->andReturn([
                [
                    'time' => Carbon::tomorrow()->setTime(14, 0)->toIso8601String(),
                    'duration' => 30
                ]
            ]);
        
        // Mock Cal.com booking
        $this->mockCalcom->shouldReceive('createBooking')
            ->andReturn([
                'id' => 'calcom_booking_123',
                'status' => 'ACCEPTED'
            ]);
        
        // Execute
        $appointment = $this->service->handleIncomingCall($webhookData);
        
        // Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals($branch->id, $appointment->branch_id);
        $this->assertEquals('calcom_booking_123', $appointment->external_id);
        $this->assertEquals('confirmed', $appointment->status);
        
        // Check customer was created
        $customer = Customer::where('phone', '+491512345678')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Test Kunde', $customer->name);
        $this->assertEquals('test@example.com', $customer->email);
        
        // Check call was created
        $call = Call::where('call_id', 'call_test123')->first();
        $this->assertNotNull($call);
        $this->assertEquals($branch->id, $call->branch_id);
    }
    
    /** @test */
    public function it_handles_missing_appointment_request()
    {
        // Webhook data without appointment request
        $webhookData = [
            'call_id' => 'call_test456',
            'to_number' => '+493012345678',
            'appointment_requested' => false
        ];
        
        // Mock resolver
        $this->mockResolver->shouldReceive('resolveFromWebhook')
            ->andReturn([
                'branch_id' => 1,
                'company_id' => 1,
                'agent_id' => null
            ]);
        
        // Execute
        $appointment = $this->service->handleIncomingCall($webhookData);
        
        // Assert no appointment created
        $this->assertNull($appointment);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}