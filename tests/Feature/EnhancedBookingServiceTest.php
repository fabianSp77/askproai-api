<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Services\Booking\EnhancedBookingService;
use App\Services\CalcomV2Service;
use App\Services\CircuitBreaker\CircuitBreaker;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnhancedBookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private EnhancedBookingService $bookingService;
    private Company $company;
    private Branch $branch;
    private Staff $staff;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        Queue::fake();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'home_branch_id' => $this->branch->id,
        ]);
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'duration' => 60,
            'calcom_event_type_id' => 123,
        ]);
        
        // Attach service to staff
        $this->staff->services()->attach($this->service);
        
        // Get the service from container
        $this->bookingService = app(EnhancedBookingService::class);
    }

    /**
     * Test successful appointment creation
     */
    #[Test]
    public function test_can_create_appointment_successfully()
    {
        // Mock Cal.com service
        $this->mock(CalcomV2Service::class, function ($mock) {
            $mock->shouldReceive('createBooking')
                ->once()
                ->andReturn([
                    'id' => 'calcom_123',
                    'uid' => 'uid_123',
                ]);
        });

        $bookingData = [
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => Carbon::tomorrow()->setTime(10, 0),
            'customer' => [
                'name' => 'Test Customer',
                'phone' => '+491234567890',
                'email' => 'test@example.com',
                'company_id' => $this->company->id,
            ],
            'source' => 'phone',
            'notes' => 'Test appointment',
        ];

        $result = $this->bookingService->createAppointment($bookingData);

        // Assert success
        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->getAppointment());
        $this->assertEquals('Appointment booked successfully', $result->getMessage());
        
        // Check appointment was created
        $appointment = $result->getAppointment();
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals($this->staff->id, $appointment->staff_id);
        $this->assertEquals($this->service->id, $appointment->service_id);
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertEquals('phone', $appointment->source);
        
        // Check customer was created
        $this->assertDatabaseHas('customers', [
            'name' => 'Test Customer',
            'phone' => '+491234567890',
            'email' => 'test@example.com',
            'company_id' => $this->company->id,
        ]);
        
        // Check notifications were queued
        Queue::assertPushed(\App\Jobs\Appointment\SendAppointmentNotificationsJob::class);
    }

    /**
     * Test appointment creation with Cal.com failure (should still succeed)
     */
    #[Test]
    public function test_appointment_succeeds_even_if_calcom_fails()
    {
        // Mock Cal.com service to fail
        $this->mock(CalcomV2Service::class, function ($mock) {
            $mock->shouldReceive('createBooking')
                ->once()
                ->andThrow(new \Exception('Cal.com API error'));
        });

        $bookingData = [
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => Carbon::tomorrow()->setTime(14, 0),
            'customer' => [
                'name' => 'Test Customer 2',
                'phone' => '+491234567891',
                'email' => 'test2@example.com',
                'company_id' => $this->company->id,
            ],
        ];

        $result = $this->bookingService->createAppointment($bookingData);

        // Assert success with warnings
        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->hasWarnings());
        $this->assertContains('Calendar sync pending - will retry automatically', $result->getWarnings());
        
        // Check sync job was queued
        Queue::assertPushed(\App\Jobs\Appointment\SyncAppointmentToCalcomJob::class);
    }

    /**
     * Test slot unavailable error
     */
    #[Test]
    public function test_fails_when_slot_is_unavailable()
    {
        // Create existing appointment
        $existingAppointment = Appointment::factory()->create([
            'staff_id' => $this->staff->id,
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(11, 0),
            'status' => 'scheduled',
        ]);

        $bookingData = [
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => Carbon::tomorrow()->setTime(10, 30), // Overlaps with existing
            'customer' => [
                'name' => 'Test Customer 3',
                'phone' => '+491234567892',
                'company_id' => $this->company->id,
            ],
        ];

        $result = $this->bookingService->createAppointment($bookingData);

        // Assert failure
        $this->assertTrue($result->isFailure());
        $this->assertEquals('slot_unavailable', $result->getErrorCode());
        $this->assertNull($result->getAppointment());
    }

    /**
     * Test booking from phone call data
     */
    #[Test]
    public function test_can_book_from_phone_call_data()
    {
        $callData = [
            'datum' => '20.06.2025',
            'uhrzeit' => '15:30',
            'name' => 'Hans Müller',
            'telefonnummer' => '+491234567893',
            'email' => 'hans@mueller.de',
            'dienstleistung' => $this->service->name,
            'mitarbeiter_wunsch' => $this->staff->name,
        ];

        $result = $this->bookingService->bookFromPhoneCall($callData);

        // Assert success
        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->getAppointment());
        
        $appointment = $result->getAppointment();
        $this->assertEquals('2025-06-20 15:30:00', $appointment->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals($this->service->id, $appointment->service_id);
        $this->assertEquals($this->staff->id, $appointment->staff_id);
    }

    /**
     * Test circuit breaker functionality
     */
    #[Test]
    public function test_circuit_breaker_opens_after_failures()
    {
        // Get circuit breaker instance
        $circuitBreaker = app(CircuitBreaker::class);
        
        // Mock Cal.com to fail multiple times
        $this->mock(CalcomV2Service::class, function ($mock) {
            $mock->shouldReceive('createBooking')
                ->times(5)
                ->andThrow(new \Exception('Cal.com API error'));
        });

        // Try to create appointments until circuit opens
        for ($i = 0; $i < 5; $i++) {
            $bookingData = [
                'staff_id' => $this->staff->id,
                'service_id' => $this->service->id,
                'start_time' => Carbon::tomorrow()->setTime(10 + $i, 0),
                'customer' => [
                    'name' => "Test Customer {$i}",
                    'phone' => "+49123456789{$i}",
                    'company_id' => $this->company->id,
                ],
            ];

            $result = $this->bookingService->createAppointment($bookingData);
            
            // Should still succeed but with warnings
            $this->assertTrue($result->isSuccess());
        }

        // Check that sync jobs were queued for retry
        Queue::assertPushed(\App\Jobs\Appointment\SyncAppointmentToCalcomJob::class, 5);
    }

    /**
     * Test finding existing customer by phone
     */
    #[Test]
    public function test_finds_existing_customer_by_phone()
    {
        // Create existing customer
        $existingCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+491234567894',
            'name' => 'Existing Customer',
            'email' => 'old@email.com',
        ]);

        $bookingData = [
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => Carbon::tomorrow()->setTime(16, 0),
            'customer' => [
                'name' => 'Updated Name',
                'phone' => '+491234567894', // Same phone
                'email' => 'new@email.com',
                'company_id' => $this->company->id,
            ],
        ];

        $result = $this->bookingService->createAppointment($bookingData);

        $this->assertTrue($result->isSuccess());
        
        // Check customer was updated, not created new
        $this->assertEquals($existingCustomer->id, $result->getAppointment()->customer_id);
        
        // Check customer details were updated
        $existingCustomer->refresh();
        $this->assertEquals('Updated Name', $existingCustomer->name);
        $this->assertEquals('new@email.com', $existingCustomer->email);
    }
}