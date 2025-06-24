<?php

namespace Tests\Unit\MCP;

use Tests\TestCase;
use App\Services\MCP\AppointmentManagementMCPServer;
use App\Services\CalcomV2Service;
use App\Services\NotificationService;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

class AppointmentManagementMCPServerTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentManagementMCPServer $server;
    private $mockCalcomService;
    private $mockNotificationService;
    private Company $company;
    private Branch $branch;
    private Customer $customer;
    private Service $service;
    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+491234567890',
            'email' => 'customer@example.com'
        ]);
        $this->service = Service::factory()->create(['company_id' => $this->company->id]);
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);
        
        $this->mockCalcomService = Mockery::mock(CalcomV2Service::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);
        
        $this->app->instance(CalcomV2Service::class, $this->mockCalcomService);
        $this->app->instance(NotificationService::class, $this->mockNotificationService);
        
        $this->server = new AppointmentManagementMCPServer(
            $this->mockCalcomService,
            $this->mockNotificationService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_finds_appointments_by_phone_number()
    {
        // Arrange
        $appointment1 = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHour(),
            'status' => 'confirmed'
        ]);
        
        $appointment2 = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'starts_at' => now()->addDays(7),
            'ends_at' => now()->addDays(7)->addHour(),
            'status' => 'scheduled'
        ]);
        
        $params = ['phone' => '+491234567890'];
        
        // Act
        $result = $this->server->findAppointments($params);
        
        // Assert
        $this->assertCount(2, $result['appointments']);
        $this->assertEquals($this->customer->name, $result['customer_name']);
        $this->assertEquals($appointment1->id, $result['appointments'][0]['id']);
        $this->assertEquals($appointment2->id, $result['appointments'][1]['id']);
    }

    /** @test */
    public function it_returns_empty_array_for_unknown_phone()
    {
        // Arrange
        $params = ['phone' => '+499999999999'];
        
        // Act
        $result = $this->server->findAppointments($params);
        
        // Assert
        $this->assertEmpty($result['appointments']);
        $this->assertEquals('No appointments found', $result['message']);
    }

    /** @test */
    public function it_normalizes_phone_numbers()
    {
        // Arrange
        $params = ['phone' => '01234567890']; // Without country code
        
        // Act
        $result = $this->server->findAppointments($params);
        
        // Assert
        // Should still find the customer with normalized phone
        $this->assertEquals($this->customer->name, $result['customer_name']);
    }

    /** @test */
    public function it_can_reschedule_appointment()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'starts_at' => now()->addDays(5)->setTime(14, 0),
            'ends_at' => now()->addDays(5)->setTime(15, 0),
            'status' => 'confirmed',
            'calcom_booking_uid' => 'booking_123'
        ]);
        
        $newDate = now()->addDays(7)->format('Y-m-d');
        $newTime = '16:00';
        
        $params = [
            'appointment_id' => $appointment->id,
            'phone' => $this->customer->phone,
            'new_date' => $newDate,
            'new_time' => $newTime
        ];
        
        // Mock Cal.com reschedule
        $this->mockCalcomService
            ->shouldReceive('rescheduleBooking')
            ->once()
            ->with('booking_123', Mockery::on(function($data) use ($newDate, $newTime) {
                return $data['start'] === $newDate . 'T' . $newTime . ':00+02:00';
            }))
            ->andReturn(['success' => true]);
        
        // Mock notification
        $this->mockNotificationService
            ->shouldReceive('sendAppointmentRescheduled')
            ->once();
        
        // Act
        $result = $this->server->rescheduleAppointment($params);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('appointment', $result);
        $this->assertEquals($appointment->id, $result['appointment']['id']);
        $this->assertTrue($result['appointment']['confirmation_sent']);
        
        // Verify appointment was updated
        $appointment->refresh();
        $this->assertEquals($newDate . ' ' . $newTime, $appointment->starts_at->format('Y-m-d H:i'));
    }

    /** @test */
    public function it_validates_ownership_before_rescheduling()
    {
        // Arrange
        $otherCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+499999999999'
        ]);
        
        $appointment = Appointment::factory()->create([
            'customer_id' => $otherCustomer->id,
            'service_id' => $this->service->id
        ]);
        
        $params = [
            'appointment_id' => $appointment->id,
            'phone' => '+491234567890', // Different customer's phone
            'new_date' => now()->addDays(7)->format('Y-m-d'),
            'new_time' => '16:00'
        ];
        
        // Act
        $result = $this->server->rescheduleAppointment($params);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Appointment not found or access denied', $result['error']);
    }

    /** @test */
    public function it_prevents_rescheduling_past_appointments()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'starts_at' => now()->subDays(1),
            'status' => 'completed'
        ]);
        
        $params = [
            'appointment_id' => $appointment->id,
            'phone' => $this->customer->phone,
            'new_date' => now()->addDays(7)->format('Y-m-d'),
            'new_time' => '16:00'
        ];
        
        // Act
        $result = $this->server->rescheduleAppointment($params);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Cannot reschedule past appointments', $result['error']);
    }

    /** @test */
    public function it_can_cancel_appointment()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'starts_at' => now()->addDays(3),
            'status' => 'confirmed',
            'calcom_booking_uid' => 'booking_456'
        ]);
        
        $params = [
            'appointment_id' => $appointment->id,
            'phone' => $this->customer->phone,
            'reason' => 'Schedule conflict'
        ];
        
        // Mock Cal.com cancellation
        $this->mockCalcomService
            ->shouldReceive('cancelBooking')
            ->once()
            ->with('booking_456', 'Schedule conflict')
            ->andReturn(['success' => true]);
        
        // Mock notification
        $this->mockNotificationService
            ->shouldReceive('sendAppointmentCancelled')
            ->once();
        
        // Act
        $result = $this->server->cancelAppointment($params);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Appointment cancelled successfully', $result['message']);
        $this->assertFalse($result['refund_applicable']);
        
        // Verify appointment was cancelled
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
    }

    /** @test */
    public function it_determines_refund_eligibility()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'starts_at' => now()->addHours(23), // Less than 24 hours
            'status' => 'confirmed',
            'calcom_booking_uid' => 'booking_789'
        ]);
        
        $params = [
            'appointment_id' => $appointment->id,
            'phone' => $this->customer->phone,
            'reason' => 'Emergency'
        ];
        
        $this->mockCalcomService
            ->shouldReceive('cancelBooking')
            ->once()
            ->andReturn(['success' => true]);
        
        $this->mockNotificationService
            ->shouldReceive('sendAppointmentCancelled')
            ->once();
        
        // Act
        $result = $this->server->cancelAppointment($params);
        
        // Assert
        $this->assertTrue($result['refund_applicable']);
    }

    /** @test */
    public function it_handles_calcom_api_failures_gracefully()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'calcom_booking_uid' => 'booking_fail'
        ]);
        
        $params = [
            'appointment_id' => $appointment->id,
            'phone' => $this->customer->phone,
            'new_date' => now()->addDays(7)->format('Y-m-d'),
            'new_time' => '16:00'
        ];
        
        $this->mockCalcomService
            ->shouldReceive('rescheduleBooking')
            ->once()
            ->andThrow(new \Exception('Cal.com API error'));
        
        // Act
        $result = $this->server->rescheduleAppointment($params);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContains('Failed to reschedule', $result['error']);
    }

    /** @test */
    public function it_limits_appointments_returned()
    {
        // Arrange
        // Create 10 appointments
        for ($i = 0; $i < 10; $i++) {
            Appointment::factory()->create([
                'customer_id' => $this->customer->id,
                'starts_at' => now()->addDays($i + 1),
                'status' => 'confirmed'
            ]);
        }
        
        $params = ['phone' => $this->customer->phone];
        
        // Act
        $result = $this->server->findAppointments($params);
        
        // Assert
        $this->assertCount(5, $result['appointments']); // Should limit to 5
    }

    /** @test */
    public function it_caches_customer_lookups()
    {
        // Arrange
        Cache::spy();
        $params = ['phone' => '+491234567890'];
        
        // Act
        $this->server->findAppointments($params);
        $this->server->findAppointments($params); // Second call
        
        // Assert
        Cache::shouldHaveReceived('remember')
            ->twice()
            ->with(
                Mockery::on(function($key) {
                    return str_contains($key, 'customer:phone:');
                }),
                300, // 5 minutes
                Mockery::any()
            );
    }

    /** @test */
    public function it_includes_service_duration_in_response()
    {
        // Arrange
        $service = Service::factory()->create([
            'company_id' => $this->company->id,
            'duration_minutes' => 45
        ]);
        
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDays(2)->setTime(10, 0),
            'duration_minutes' => 45
        ]);
        
        $params = ['phone' => $this->customer->phone];
        
        // Act
        $result = $this->server->findAppointments($params);
        
        // Assert
        $this->assertEquals(45, $result['appointments'][0]['duration']);
    }

    /** @test */
    public function it_includes_branch_and_staff_info()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'starts_at' => now()->addDays(1)
        ]);
        
        $params = ['phone' => $this->customer->phone];
        
        // Act
        $result = $this->server->findAppointments($params);
        
        // Assert
        $this->assertEquals($this->branch->name, $result['appointments'][0]['branch']);
        $this->assertEquals($this->staff->name, $result['appointments'][0]['staff']);
    }
}