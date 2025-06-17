<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AppointmentBookingService;
use App\Services\Locking\TimeSlotLockManager;
use App\Services\CalcomV2Service;
use App\Services\NotificationService;
use App\Services\AvailabilityService;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Appointment;
use App\Exceptions\AvailabilityException;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AppointmentBookingServiceLockTest extends TestCase
{
    use RefreshDatabase;

    private $bookingService;
    private $lockManager;
    private $calcomService;
    private $notificationService;
    private $availabilityService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->lockManager = Mockery::mock(TimeSlotLockManager::class);
        $this->calcomService = Mockery::mock(CalcomV2Service::class);
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->availabilityService = Mockery::mock(AvailabilityService::class);
        
        // Create service with mocked dependencies
        $this->bookingService = new AppointmentBookingService(
            $this->calcomService,
            $this->notificationService,
            $this->availabilityService,
            $this->lockManager
        );
        
        // Create test data
        $this->createTestData();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    private function createTestData()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        Staff::factory()->create([
            'id' => 1,
            'company_id' => $company->id,
            'home_branch_id' => $branch->id,
            'active' => true,
            'name' => 'Test Staff'
        ]);
        Service::factory()->create([
            'id' => 1,
            'company_id' => $company->id,
            'is_active' => true,
            'duration' => 30,
            'name' => 'Test Service'
        ]);
    }
    
    /** @test */
    public function it_acquires_lock_before_booking_appointment()
    {
        $appointmentData = [
            'datum' => '15.04.2025',
            'uhrzeit' => '14:30',
            'name' => 'Test Customer',
            'telefonnummer' => '+49123456789',
            'email' => 'test@example.com',
            'dienstleistung' => 'Test Service',
            'mitarbeiter_wunsch' => 'Test Staff'
        ];
        
        $lockToken = 'test-lock-token-123';
        
        // Set up expectations
        $this->lockManager->shouldReceive('acquireLock')
            ->once()
            ->with(Mockery::any(), '1', Mockery::type(Carbon::class), Mockery::type(Carbon::class), 5)
            ->andReturn($lockToken);
            
        $this->lockManager->shouldReceive('extendLock')
            ->once()
            ->with($lockToken, 3)
            ->andReturn(true);
            
        $this->lockManager->shouldReceive('releaseLock')
            ->once()
            ->with($lockToken)
            ->andReturn(true);
            
        $this->availabilityService->shouldReceive('reserveSlot')
            ->once()
            ->andReturn(true);
            
        $this->calcomService->shouldReceive('createBooking')
            ->once()
            ->andReturn(['id' => 'cal123', 'uid' => 'uid123']);
            
        $this->notificationService->shouldReceive('sendAppointmentConfirmation')
            ->once();
        $this->notificationService->shouldReceive('sendAppointmentSms')
            ->once();
        $this->notificationService->shouldReceive('notifyStaffNewAppointment')
            ->once();
        
        // Execute
        $result = $this->bookingService->bookFromPhoneCall($appointmentData);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Appointment::class, $result['appointment']);
        $this->assertEquals('Termin erfolgreich gebucht', $result['message']);
        
        // Verify appointment was created with lock token in metadata
        $appointment = $result['appointment'];
        $this->assertArrayHasKey('lock_token', $appointment->metadata);
        $this->assertEquals($lockToken, $appointment->metadata['lock_token']);
    }
    
    /** @test */
    public function it_releases_lock_on_booking_failure()
    {
        $appointmentData = [
            'datum' => '15.04.2025',
            'uhrzeit' => '14:30',
            'name' => 'Test Customer',
            'telefonnummer' => '+49123456789',
            'dienstleistung' => 'Test Service',
            'mitarbeiter_wunsch' => 'Test Staff'
        ];
        
        $lockToken = 'test-lock-token-456';
        
        // Set up expectations
        $this->lockManager->shouldReceive('acquireLock')
            ->once()
            ->andReturn($lockToken);
            
        $this->lockManager->shouldReceive('releaseLock')
            ->once()
            ->with($lockToken)
            ->andReturn(true);
            
        $this->availabilityService->shouldReceive('reserveSlot')
            ->once()
            ->andThrow(new \Exception('Calendar system error'));
        
        // Execute
        $result = $this->bookingService->bookFromPhoneCall($appointmentData);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Calendar system error', $result['message']);
    }
    
    /** @test */
    public function it_throws_exception_when_lock_cannot_be_acquired()
    {
        $appointmentData = [
            'datum' => '15.04.2025',
            'uhrzeit' => '14:30',
            'name' => 'Test Customer',
            'telefonnummer' => '+49123456789',
            'dienstleistung' => 'Test Service',
            'mitarbeiter_wunsch' => 'Test Staff'
        ];
        
        // Set up expectations - lock acquisition fails
        $this->lockManager->shouldReceive('acquireLock')
            ->once()
            ->andReturn(null); // Lock failed
        
        // Execute
        $result = $this->bookingService->bookFromPhoneCall($appointmentData);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Dieser Termin wird gerade von einem anderen Nutzer gebucht', $result['message']);
    }
    
    /** @test */
    public function it_tries_alternative_slots_when_original_is_unavailable()
    {
        $appointmentData = [
            'datum' => '15.04.2025',
            'uhrzeit' => '14:30',
            'name' => 'Test Customer',
            'telefonnummer' => '+49123456789',
            'dienstleistung' => 'Test Service',
            'mitarbeiter_wunsch' => 'Test Staff'
        ];
        
        $lockToken1 = 'test-lock-token-789';
        $lockToken2 = 'test-lock-token-abc';
        
        // Set up expectations
        $this->lockManager->shouldReceive('acquireLock')
            ->once()
            ->ordered()
            ->andReturn($lockToken1); // First lock succeeds
            
        $this->lockManager->shouldReceive('releaseLock')
            ->once()
            ->ordered()
            ->with($lockToken1)
            ->andReturn(true); // Release first lock
            
        $this->lockManager->shouldReceive('acquireLock')
            ->once()
            ->ordered()
            ->andReturn($lockToken2); // Second lock for alternative
            
        $this->lockManager->shouldReceive('extendLock')
            ->once()
            ->with($lockToken2, 3)
            ->andReturn(true);
            
        $this->lockManager->shouldReceive('releaseLock')
            ->once()
            ->ordered()
            ->with($lockToken2)
            ->andReturn(true); // Final release
            
        // First slot is not available
        $this->availabilityService->shouldReceive('findAlternativeSlots')
            ->once()
            ->andReturn([
                ['start' => Carbon::parse('2025-04-15 15:00'), 'end' => Carbon::parse('2025-04-15 15:30')]
            ]);
            
        $this->availabilityService->shouldReceive('reserveSlot')
            ->once()
            ->andReturn(true);
            
        $this->calcomService->shouldReceive('createBooking')
            ->once()
            ->andReturn(['id' => 'cal456', 'uid' => 'uid456']);
            
        $this->notificationService->shouldReceive('sendAppointmentConfirmation')->once();
        $this->notificationService->shouldReceive('sendAppointmentSms')->once();
        $this->notificationService->shouldReceive('notifyStaffNewAppointment')->once();
        
        // Create existing appointment to trigger alternative search
        Appointment::create([
            'company_id' => 1,
            'customer_id' => 1,
            'staff_id' => 1,
            'service_id' => 1,
            'branch_id' => 1,
            'starts_at' => '2025-04-15 14:30:00',
            'ends_at' => '2025-04-15 15:00:00',
            'status' => 'scheduled'
        ]);
        
        // Execute
        $result = $this->bookingService->bookFromPhoneCall($appointmentData);
        
        // Assert
        $this->assertTrue($result['success']);
        $appointment = $result['appointment'];
        
        // Verify alternative time was used
        $this->assertEquals('2025-04-15 15:00:00', $appointment->starts_at->format('Y-m-d H:i:s'));
    }
    
    /** @test */
    public function it_can_check_if_slot_is_locked()
    {
        $staff = Staff::find(1);
        $startTime = '2025-04-15 14:30:00';
        $endTime = '2025-04-15 15:00:00';
        
        $this->lockManager->shouldReceive('isSlotLocked')
            ->once()
            ->with($staff->id, $startTime, $endTime)
            ->andReturn(true);
        
        $isLocked = $this->bookingService->isSlotLocked($staff, $startTime, $endTime);
        
        $this->assertTrue($isLocked);
    }
    
    /** @test */
    public function it_can_extend_lock_for_long_operations()
    {
        $lockToken = 'test-lock-token-xyz';
        
        $this->lockManager->shouldReceive('extendLock')
            ->once()
            ->with($lockToken, 10)
            ->andReturn(true);
        
        $extended = $this->bookingService->extendLock($lockToken, 10);
        
        $this->assertTrue($extended);
    }
    
    /** @test */
    public function it_can_cleanup_expired_locks()
    {
        $this->lockManager->shouldReceive('cleanupExpiredLocks')
            ->once()
            ->andReturn(5);
        
        $cleaned = $this->bookingService->cleanupExpiredLocks();
        
        $this->assertEquals(5, $cleaned);
    }
}