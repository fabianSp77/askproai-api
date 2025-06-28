<?php

namespace Tests\Unit\Services\Locking;

use App\Models\AppointmentLock;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Staff;
use App\Services\Locking\TimeSlotLockManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TimeSlotLockManagerTest extends TestCase
{
    use RefreshDatabase;

    private TimeSlotLockManager $lockManager;
    private Company $company;
    private Branch $branch;
    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->lockManager = new TimeSlotLockManager();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
    }

    /** @test */
    public function it_can_acquire_a_lock_for_available_time_slot()
    {
        $startTime = Carbon::now()->addHour();
        $endTime = $startTime->copy()->addMinutes(30);
        
        $lockToken = $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $startTime,
            $endTime
        );
        
        $this->assertNotNull($lockToken);
        $this->assertIsString($lockToken);
        
        // Verify lock was created in database
        $this->assertDatabaseHas('appointment_locks', [
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'lock_token' => $lockToken,
        ]);
    }

    /** @test */
    #[Test]
    public function it_prevents_double_booking_with_concurrent_requests()
    {
        $startTime = Carbon::now()->addHour();
        $endTime = $startTime->copy()->addMinutes(30);
        
        // First lock should succeed
        $lockToken1 = $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $startTime,
            $endTime
        );
        
        // Second lock for same time should fail
        $lockToken2 = $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $startTime,
            $endTime
        );
        
        $this->assertNotNull($lockToken1);
        $this->assertNull($lockToken2);
        
        // Only one lock should exist
        $this->assertEquals(1, AppointmentLock::count());
    }

    /** @test */
    public function it_can_release_a_lock()
    {
        $startTime = Carbon::now()->addHour();
        $endTime = $startTime->copy()->addMinutes(30);
        
        $lockToken = $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $startTime,
            $endTime
        );
        
        $this->assertNotNull($lockToken);
        
        // Release the lock
        $released = $this->lockManager->releaseLock($lockToken);
        
        $this->assertTrue($released);
        $this->assertDatabaseMissing('appointment_locks', [
            'lock_token' => $lockToken,
        ]);
    }

    /** @test */
    #[Test]
    public function it_can_extend_an_active_lock()
    {
        $startTime = Carbon::now()->addHour();
        $endTime = $startTime->copy()->addMinutes(30);
        
        $lockToken = $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $startTime,
            $endTime,
            5 // 5 minutes initial expiry
        );
        
        $lock = AppointmentLock::where('lock_token', $lockToken)->first();
        $originalExpiry = $lock->lock_expires_at;
        
        // Extend the lock
        $extended = $this->lockManager->extendLock($lockToken, 10);
        
        $this->assertTrue($extended);
        
        $lock->refresh();
        $this->assertTrue($lock->lock_expires_at->greaterThan($originalExpiry));
    }

    /** @test */
    public function it_cannot_extend_an_expired_lock()
    {
        $startTime = Carbon::now()->addHour();
        $endTime = $startTime->copy()->addMinutes(30);
        
        // Create a lock that's already expired
        $lock = AppointmentLock::create([
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'starts_at' => $startTime,
            'ends_at' => $endTime,
            'lock_token' => 'expired-token',
            'lock_expires_at' => Carbon::now()->subMinute(), // Already expired
        ]);
        
        $extended = $this->lockManager->extendLock($lock->lock_token);
        
        $this->assertFalse($extended);
    }

    /** @test */
    #[Test]
    public function it_checks_if_slot_is_locked()
    {
        $startTime = Carbon::now()->addHour();
        $endTime = $startTime->copy()->addMinutes(30);
        
        // Check before lock
        $isLocked = $this->lockManager->isSlotLocked(
            $this->staff->id,
            $startTime,
            $endTime
        );
        $this->assertFalse($isLocked);
        
        // Acquire lock
        $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $startTime,
            $endTime
        );
        
        // Check after lock
        $isLocked = $this->lockManager->isSlotLocked(
            $this->staff->id,
            $startTime,
            $endTime
        );
        $this->assertTrue($isLocked);
    }

    /** @test */
    public function it_cleans_up_expired_locks()
    {
        // Create some expired locks
        AppointmentLock::create([
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'starts_at' => Carbon::now(),
            'ends_at' => Carbon::now()->addMinutes(30),
            'lock_token' => 'expired-1',
            'lock_expires_at' => Carbon::now()->subHour(),
        ]);
        
        AppointmentLock::create([
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'starts_at' => Carbon::now()->addHour(),
            'ends_at' => Carbon::now()->addHour()->addMinutes(30),
            'lock_token' => 'expired-2',
            'lock_expires_at' => Carbon::now()->subMinutes(30),
        ]);
        
        // Create an active lock
        AppointmentLock::create([
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'starts_at' => Carbon::now()->addHours(2),
            'ends_at' => Carbon::now()->addHours(2)->addMinutes(30),
            'lock_token' => 'active-1',
            'lock_expires_at' => Carbon::now()->addMinutes(10),
        ]);
        
        $this->assertEquals(3, AppointmentLock::count());
        
        $cleaned = $this->lockManager->cleanupExpiredLocks();
        
        $this->assertEquals(2, $cleaned);
        $this->assertEquals(1, AppointmentLock::count());
        $this->assertDatabaseHas('appointment_locks', ['lock_token' => 'active-1']);
    }

    /** @test */
    #[Test]
    public function it_handles_overlapping_time_slots_correctly()
    {
        $baseTime = Carbon::now()->addHour();
        
        // Lock 10:00 - 10:30
        $lock1 = $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $baseTime,
            $baseTime->copy()->addMinutes(30)
        );
        $this->assertNotNull($lock1);
        
        // Try to lock 10:15 - 10:45 (overlaps with first)
        $lock2 = $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $baseTime->copy()->addMinutes(15),
            $baseTime->copy()->addMinutes(45)
        );
        $this->assertNull($lock2);
        
        // Try to lock 09:30 - 10:15 (overlaps with first)
        $lock3 = $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $baseTime->copy()->subMinutes(30),
            $baseTime->copy()->addMinutes(15)
        );
        $this->assertNull($lock3);
        
        // Try to lock 10:30 - 11:00 (no overlap)
        $lock4 = $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $baseTime->copy()->addMinutes(30),
            $baseTime->copy()->addMinutes(60)
        );
        $this->assertNotNull($lock4);
    }

    /** @test */
    public function it_validates_lock_tokens()
    {
        $startTime = Carbon::now()->addHour();
        $endTime = $startTime->copy()->addMinutes(30);
        
        $lockToken = $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $startTime,
            $endTime
        );
        
        // Valid token
        $this->assertTrue($this->lockManager->validateLockToken($lockToken));
        
        // Invalid token
        $this->assertFalse($this->lockManager->validateLockToken('invalid-token'));
        
        // Empty token
        $this->assertFalse($this->lockManager->validateLockToken(''));
    }

    /** @test */
    #[Test]
    public function it_gets_active_locks_for_staff()
    {
        $baseTime = Carbon::now()->addHour();
        
        // Create multiple locks for the staff
        $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $baseTime,
            $baseTime->copy()->addMinutes(30)
        );
        
        $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $baseTime->copy()->addHour(),
            $baseTime->copy()->addHour()->addMinutes(30)
        );
        
        $activeLocks = $this->lockManager->getActiveLocksForStaff($this->staff->id);
        
        $this->assertCount(2, $activeLocks);
        $this->assertEquals($this->staff->id, $activeLocks->first()->staff_id);
    }

    /** @test */
    public function it_handles_different_branches_independently()
    {
        $branch2 = Branch::factory()->create(['company_id' => $this->company->id]);
        $staff2 = Staff::factory()->create(['branch_id' => $branch2->id]);
        
        $startTime = Carbon::now()->addHour();
        $endTime = $startTime->copy()->addMinutes(30);
        
        // Lock in branch 1
        $lock1 = $this->lockManager->acquireLock(
            $this->branch->id,
            $this->staff->id,
            $startTime,
            $endTime
        );
        
        // Same time slot in branch 2 should work
        $lock2 = $this->lockManager->acquireLock(
            $branch2->id,
            $staff2->id,
            $startTime,
            $endTime
        );
        
        $this->assertNotNull($lock1);
        $this->assertNotNull($lock2);
        $this->assertNotEquals($lock1, $lock2);
    }
}