<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AppointmentBookingService;
use App\Services\Locking\TimeSlotLockManager;
use App\Services\CalcomV2Service;
use App\Services\NotificationService;
use App\Services\AvailabilityService;
use PHPUnit\Framework\Attributes\Test;
use Mockery;

class AppointmentBookingServiceLockUnitTest extends TestCase
{
    private $bookingService;
    private $lockManager;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->lockManager = Mockery::mock(TimeSlotLockManager::class);
        $calcomService = Mockery::mock(CalcomV2Service::class);
        $notificationService = Mockery::mock(NotificationService::class);
        $availabilityService = Mockery::mock(AvailabilityService::class);
        
        // Create service with mocked dependencies
        $this->bookingService = new AppointmentBookingService(
            $calcomService,
            $notificationService,
            $availabilityService,
            $this->lockManager
        );
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    #[Test]
    public function it_can_check_if_slot_is_locked()
    {
        // Create a mock staff object
        $staff = Mockery::mock();
        $staff->id = '123';
        
        $startTime = '2025-04-15 14:30:00';
        $endTime = '2025-04-15 15:00:00';
        
        $this->lockManager->shouldReceive('isSlotLocked')
            ->once()
            ->with('123', $startTime, $endTime)
            ->andReturn(true);
        
        $isLocked = $this->bookingService->isSlotLocked($staff, $startTime, $endTime);
        
        $this->assertTrue($isLocked);
    }
    
    #[Test]
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
    
    #[Test]
    public function it_can_cleanup_expired_locks()
    {
        $this->lockManager->shouldReceive('cleanupExpiredLocks')
            ->once()
            ->andReturn(5);
        
        $cleaned = $this->bookingService->cleanupExpiredLocks();
        
        $this->assertEquals(5, $cleaned);
    }
}