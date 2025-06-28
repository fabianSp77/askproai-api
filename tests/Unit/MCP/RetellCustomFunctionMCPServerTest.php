<?php

namespace Tests\Unit\MCP;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Services\MCP\RetellCustomFunctionMCPServer;
use App\Services\PhoneNumberResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RetellCustomFunctionMCPServerTest extends TestCase
{
    use RefreshDatabase;

    private RetellCustomFunctionMCPServer $server;
    private $mockPhoneResolver;
    private Company $company;
    private Branch $branch;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+491234567890'
        ]);
        
        $this->mockPhoneResolver = Mockery::mock(PhoneNumberResolver::class);
        $this->app->instance(PhoneNumberResolver::class, $this->mockPhoneResolver);
        
        $this->server = new RetellCustomFunctionMCPServer();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_collects_appointment_information()
    {
        // Arrange
        $params = [
            'call_id' => 'call_123',
            'name' => 'John Doe',
            'date' => '2025-07-15',
            'time' => '14:00',
            'service' => 'Consultation',
            'notes' => 'First appointment'
        ];
        
        // Act
        $result = $this->server->collectAppointmentInformation($params);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Appointment information collected successfully', $result['message']);
        
        // Verify data was cached
        $cachedData = Cache::get('retell:appointment:call_123');
        $this->assertNotNull($cachedData);
        $this->assertEquals('John Doe', $cachedData['customer_name']);
        $this->assertEquals('2025-07-15', $cachedData['appointment_date']);
        $this->assertEquals('14:00', $cachedData['appointment_time']);
        $this->assertEquals('Consultation', $cachedData['service_type']);
    }

    /** @test */
    #[Test]
    public function it_validates_required_fields_for_appointment_collection()
    {
        // Arrange
        $params = [
            'call_id' => 'call_123',
            'name' => 'John Doe',
            // Missing date and time
            'service' => 'Consultation'
        ];
        
        // Act
        $result = $this->server->collectAppointmentInformation($params);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContains('Missing required fields', $result['error']);
    }

    /** @test */
    public function it_normalizes_date_format()
    {
        // Arrange
        $params = [
            'call_id' => 'call_123',
            'name' => 'John Doe',
            'date' => '15.07.2025', // German format
            'time' => '14:00',
            'service' => 'Beratung'
        ];
        
        // Act
        $result = $this->server->collectAppointmentInformation($params);
        
        // Assert
        $this->assertTrue($result['success']);
        
        $cachedData = Cache::get('retell:appointment:call_123');
        $this->assertEquals('2025-07-15', $cachedData['appointment_date']);
    }

    /** @test */
    #[Test]
    public function it_can_find_appointments_by_phone()
    {
        // Arrange
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        
        $appointment1 = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDays(5),
            'ends_at' => now()->addDays(5)->addHour(),
            'status' => 'confirmed'
        ]);
        
        $appointment2 = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHour(),
            'status' => 'scheduled'
        ]);
        
        // Past appointment (should not be included)
        Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $service->id,
            'starts_at' => now()->subDays(5),
            'status' => 'completed'
        ]);
        
        $params = [
            'phone' => '+491234567890',
            'language' => 'de'
        ];
        
        // Act
        $result = $this->server->findAppointmentsByPhone($params);
        
        // Assert
        $this->assertTrue($result['found']);
        $this->assertCount(2, $result['appointments']);
        $this->assertEquals($appointment1->id, $result['appointments'][0]['id']);
        $this->assertEquals($appointment2->id, $result['appointments'][1]['id']);
        $this->assertStringContains('2 Termine gefunden', $result['message']);
    }

    /** @test */
    public function it_handles_no_appointments_found()
    {
        // Arrange
        $params = [
            'phone' => '+499999999999', // Non-existent phone
            'language' => 'en'
        ];
        
        // Act
        $result = $this->server->findAppointmentsByPhone($params);
        
        // Assert
        $this->assertFalse($result['found']);
        $this->assertEmpty($result['appointments']);
        $this->assertEquals('No appointments found for this phone number', $result['message']);
    }

    /** @test */
    #[Test]
    public function it_can_change_appointment_details()
    {
        // Arrange
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDays(5)->setTime(14, 0),
            'ends_at' => now()->addDays(5)->setTime(15, 0),
            'status' => 'confirmed'
        ]);
        
        $params = [
            'appointment_id' => $appointment->id,
            'phone' => $this->customer->phone,
            'new_date' => now()->addDays(7)->format('Y-m-d'),
            'new_time' => '16:00',
            'language' => 'en'
        ];
        
        // Act
        $result = $this->server->changeAppointmentDetails($params);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Appointment successfully rescheduled', $result['message']);
        
        // Verify appointment was updated
        $appointment->refresh();
        $this->assertEquals(now()->addDays(7)->setTime(16, 0)->format('Y-m-d H:i'), 
                          $appointment->starts_at->format('Y-m-d H:i'));
    }

    /** @test */
    public function it_validates_phone_ownership_for_changes()
    {
        // Arrange
        $otherCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+499999999999'
        ]);
        
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $otherCustomer->id,
            'service_id' => $service->id
        ]);
        
        $params = [
            'appointment_id' => $appointment->id,
            'phone' => '+491234567890', // Different phone
            'new_date' => now()->addDays(7)->format('Y-m-d'),
            'new_time' => '16:00'
        ];
        
        // Act
        $result = $this->server->changeAppointmentDetails($params);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContains('not authorized', $result['error']);
    }

    /** @test */
    #[Test]
    public function it_can_cancel_appointment()
    {
        // Arrange
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDays(5),
            'status' => 'confirmed'
        ]);
        
        $params = [
            'appointment_id' => $appointment->id,
            'phone' => $this->customer->phone,
            'reason' => 'Schedule conflict',
            'language' => 'en'
        ];
        
        // Act
        $result = $this->server->cancelAppointment($params);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Appointment cancelled successfully', $result['message']);
        
        // Verify appointment was cancelled
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
    }

    /** @test */
    public function it_prevents_cancelling_past_appointments()
    {
        // Arrange
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $service->id,
            'starts_at' => now()->subDays(1), // Past appointment
            'status' => 'completed'
        ]);
        
        $params = [
            'appointment_id' => $appointment->id,
            'phone' => $this->customer->phone,
            'reason' => 'Too late'
        ];
        
        // Act
        $result = $this->server->cancelAppointment($params);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContains('Cannot cancel past appointments', $result['error']);
    }

    /** @test */
    #[Test]
    public function it_formats_appointment_for_voice_output()
    {
        // Arrange
        $service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Hair Cut'
        ]);
        
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDays(5)->setTime(14, 30),
            'duration_minutes' => 60
        ]);
        
        // Act
        $formatted = $this->invokePrivateMethod($this->server, 'formatAppointmentForVoice', [$appointment, 'en']);
        
        // Assert
        $this->assertArrayHasKey('id', $formatted);
        $this->assertArrayHasKey('date', $formatted);
        $this->assertArrayHasKey('time', $formatted);
        $this->assertArrayHasKey('service', $formatted);
        $this->assertArrayHasKey('duration', $formatted);
        $this->assertArrayHasKey('readable_datetime', $formatted);
        
        $this->assertEquals('14:30', $formatted['time']);
        $this->assertEquals('Hair Cut', $formatted['service']);
        $this->assertEquals(60, $formatted['duration']);
    }

    /** @test */
    public function it_supports_multiple_languages()
    {
        // Arrange
        $params = [
            'phone' => '+499999999999',
            'language' => 'de'
        ];
        
        // Act
        $result = $this->server->findAppointmentsByPhone($params);
        
        // Assert
        $this->assertEquals('Keine Termine für diese Telefonnummer gefunden', $result['message']);
    }

    /** @test */
    #[Test]
    public function it_uses_correct_cache_ttl()
    {
        // Arrange
        Cache::spy();
        
        $params = [
            'call_id' => 'call_123',
            'name' => 'John Doe',
            'date' => '2025-07-15',
            'time' => '14:00',
            'service' => 'Consultation'
        ];
        
        // Act
        $this->server->collectAppointmentInformation($params);
        
        // Assert
        Cache::shouldHaveReceived('put')
            ->once()
            ->with(
                'retell:appointment:call_123',
                Mockery::any(),
                3600 // 1 hour TTL
            );
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
}