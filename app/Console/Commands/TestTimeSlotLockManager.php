<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Staff;
use App\Services\Locking\TimeSlotLockManager;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestTimeSlotLockManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:lock-manager';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the TimeSlotLockManager service';

    private TimeSlotLockManager $lockManager;

    /**
     * Execute the console command.
     */
    public function handle(TimeSlotLockManager $lockManager)
    {
        $this->lockManager = $lockManager;

        $this->info('Testing TimeSlotLockManager Service');
        $this->line('=====================================');

        // Get test data
        $company = Company::first();
        if (!$company) {
            $this->error('No company found in database. Please seed the database first.');
            return 1;
        }

        $branch = $company->branches()->first();
        if (!$branch) {
            $this->error('No branch found for company. Please seed the database first.');
            return 1;
        }

        $staff = $branch->staff()->first();
        if (!$staff) {
            $this->error('No staff found for branch. Please seed the database first.');
            return 1;
        }

        $this->info("Using: Company: {$company->name}, Branch: {$branch->name}, Staff: {$staff->name}");
        $this->newLine();

        // Test 1: Acquire a lock
        $this->info('Test 1: Acquiring a lock for a time slot');
        $startTime = Carbon::now()->addHour();
        $endTime = $startTime->copy()->addMinutes(30);
        
        $lockToken = $this->lockManager->acquireLock(
            $branch->id,
            $staff->id,
            $startTime,
            $endTime
        );

        if ($lockToken) {
            $this->info("✓ Lock acquired successfully!");
            $this->line("  Lock Token: " . substr($lockToken, 0, 8) . "...");
            $this->line("  Time Slot: {$startTime->format('H:i')} - {$endTime->format('H:i')}");
        } else {
            $this->error("✗ Failed to acquire lock");
        }
        $this->newLine();

        // Test 2: Try to acquire same slot (should fail)
        $this->info('Test 2: Attempting to acquire same time slot (should fail)');
        $lockToken2 = $this->lockManager->acquireLock(
            $branch->id,
            $staff->id,
            $startTime,
            $endTime
        );

        if ($lockToken2) {
            $this->error("✗ Lock was acquired - this should not happen!");
        } else {
            $this->info("✓ Lock correctly prevented (slot already locked)");
        }
        $this->newLine();

        // Test 3: Check if slot is locked
        $this->info('Test 3: Checking if slot is locked');
        $isLocked = $this->lockManager->isSlotLocked($staff->id, $startTime, $endTime);
        
        if ($isLocked) {
            $this->info("✓ Slot is correctly reported as locked");
        } else {
            $this->error("✗ Slot should be locked but isn't");
        }
        $this->newLine();

        // Test 4: Extend lock
        $this->info('Test 4: Extending the lock');
        if ($lockToken) {
            $extended = $this->lockManager->extendLock($lockToken, 10);
            if ($extended) {
                $this->info("✓ Lock extended successfully by 10 minutes");
            } else {
                $this->error("✗ Failed to extend lock");
            }
        }
        $this->newLine();

        // Test 5: Get lock info
        $this->info('Test 5: Getting lock information');
        if ($lockToken) {
            $lockInfo = $this->lockManager->getLockInfo($lockToken);
            if ($lockInfo) {
                $this->info("✓ Lock information retrieved:");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Staff ID', $lockInfo->staff_id],
                        ['Start Time', $lockInfo->starts_at->format('Y-m-d H:i:s')],
                        ['End Time', $lockInfo->ends_at->format('Y-m-d H:i:s')],
                        ['Expires At', $lockInfo->lock_expires_at->format('Y-m-d H:i:s')],
                        ['Is Active', $lockInfo->isActive() ? 'Yes' : 'No'],
                    ]
                );
            }
        }
        $this->newLine();

        // Test 6: Get all active locks for branch
        $this->info('Test 6: Getting all active locks for branch');
        $activeLocks = $this->lockManager->getActiveLocksForBranch($branch->id);
        $this->line("Found {$activeLocks->count()} active lock(s) for branch");
        if ($activeLocks->isNotEmpty()) {
            $this->table(
                ['Staff', 'Start', 'End', 'Expires In'],
                $activeLocks->map(function ($lock) {
                    return [
                        $lock->staff->name,
                        $lock->starts_at->format('H:i'),
                        $lock->ends_at->format('H:i'),
                        $lock->lock_expires_at->diffForHumans(),
                    ];
                })->toArray()
            );
        }
        $this->newLine();

        // Test 7: Release lock
        $this->info('Test 7: Releasing the lock');
        if ($lockToken) {
            $released = $this->lockManager->releaseLock($lockToken);
            if ($released) {
                $this->info("✓ Lock released successfully");
            } else {
                $this->error("✗ Failed to release lock");
            }
        }
        $this->newLine();

        // Test 8: Verify slot is now available
        $this->info('Test 8: Verifying slot is now available');
        $isLocked = $this->lockManager->isSlotLocked($staff->id, $startTime, $endTime);
        
        if (!$isLocked) {
            $this->info("✓ Slot is now available");
        } else {
            $this->error("✗ Slot is still locked");
        }
        $this->newLine();

        // Test 9: Test overlapping time slots
        $this->info('Test 9: Testing overlapping time slot detection');
        $baseTime = Carbon::now()->addHours(2);
        
        // Lock 14:00 - 14:30
        $lock1 = $this->lockManager->acquireLock(
            $branch->id,
            $staff->id,
            $baseTime,
            $baseTime->copy()->addMinutes(30)
        );
        $this->line("Locked 14:00 - 14:30: " . ($lock1 ? "✓" : "✗"));
        
        // Try 14:15 - 14:45 (should fail - overlaps)
        $lock2 = $this->lockManager->acquireLock(
            $branch->id,
            $staff->id,
            $baseTime->copy()->addMinutes(15),
            $baseTime->copy()->addMinutes(45)
        );
        $this->line("Try 14:15 - 14:45: " . (!$lock2 ? "✓ Correctly blocked" : "✗ Should have been blocked"));
        
        // Try 14:30 - 15:00 (should succeed - no overlap)
        $lock3 = $this->lockManager->acquireLock(
            $branch->id,
            $staff->id,
            $baseTime->copy()->addMinutes(30),
            $baseTime->copy()->addMinutes(60)
        );
        $this->line("Try 14:30 - 15:00: " . ($lock3 ? "✓ Correctly allowed" : "✗ Should have been allowed"));
        
        // Clean up
        if ($lock1) $this->lockManager->releaseLock($lock1);
        if ($lock3) $this->lockManager->releaseLock($lock3);
        $this->newLine();

        // Test 10: Statistics
        $this->info('Test 10: Lock statistics');
        $stats = $this->lockManager->getLockStatistics();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Active Locks', $stats['total_active_locks']],
                ['Total Expired Locks', $stats['total_expired_locks']],
                ['Average Lock Duration', ($stats['average_lock_duration'] ?? 0) . ' minutes'],
            ]
        );

        $this->newLine();
        $this->info('All tests completed!');

        return 0;
    }
}