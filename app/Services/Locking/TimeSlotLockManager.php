<?php

namespace App\Services\Locking;

use App\Models\AppointmentLock;
use App\Models\Branch;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TimeSlotLockManager
{
    /**
     * Default lock expiration time in minutes.
     */
    private const DEFAULT_LOCK_MINUTES = 5;

    /**
     * Maximum lock duration in minutes.
     */
    private const MAX_LOCK_MINUTES = 30;

    /**
     * Acquire a lock for a specific time slot.
     *
     * @param string $branchId
     * @param string $staffId
     * @param Carbon|string $startTime
     * @param Carbon|string $endTime
     * @param int $lockMinutes
     * @return string|null Lock token if successful, null if slot is already locked
     */
    public function acquireLock(
        string $branchId,
        string $staffId,
        $startTime,
        $endTime,
        int $lockMinutes = self::DEFAULT_LOCK_MINUTES
    ): ?string {
        // Ensure times are Carbon instances
        $startTime = Carbon::parse($startTime);
        $endTime = Carbon::parse($endTime);
        
        // Validate lock duration
        $lockMinutes = min($lockMinutes, self::MAX_LOCK_MINUTES);
        
        try {
            return DB::transaction(function () use ($branchId, $staffId, $startTime, $endTime, $lockMinutes) {
                // Clean up any expired locks first
                $this->cleanupExpiredLocks();
                
                // Check if there's already an active lock for this time range
                $existingLock = AppointmentLock::active()
                    ->where('staff_id', $staffId)
                    ->forTimeRange($startTime, $endTime)
                    ->lockForUpdate() // Prevent race conditions
                    ->first();
                
                if ($existingLock) {
                    Log::warning('Lock acquisition failed - slot already locked', [
                        'branch_id' => $branchId,
                        'staff_id' => $staffId,
                        'start_time' => $startTime->toIso8601String(),
                        'end_time' => $endTime->toIso8601String(),
                        'existing_lock_token' => substr($existingLock->lock_token, 0, 8) . '...',
                        'existing_lock_expires' => $existingLock->lock_expires_at->toIso8601String(),
                    ]);
                    
                    return null;
                }
                
                // Generate unique lock token
                $lockToken = $this->generateLockToken();
                
                // Create the lock - using raw insert to handle race conditions
                try {
                    DB::table('appointment_locks')->insert([
                        'branch_id' => $branchId,
                        'staff_id' => $staffId,
                        'starts_at' => $startTime,
                        'ends_at' => $endTime,
                        'lock_token' => $lockToken,
                        'lock_expires_at' => now()->addMinutes($lockMinutes),
                        'created_at' => now(),
                    ]);
                    
                    Log::info('Lock acquired successfully', [
                        'branch_id' => $branchId,
                        'staff_id' => $staffId,
                        'start_time' => $startTime->toIso8601String(),
                        'end_time' => $endTime->toIso8601String(),
                        'lock_token' => substr($lockToken, 0, 8) . '...',
                        'expires_in_minutes' => $lockMinutes,
                    ]);
                    
                    return $lockToken;
                    
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handle duplicate key error (race condition)
                    if ($e->getCode() == 23000) { // Duplicate entry error
                        Log::warning('Lock acquisition failed - race condition detected', [
                            'branch_id' => $branchId,
                            'staff_id' => $staffId,
                            'start_time' => $startTime->toIso8601String(),
                            'end_time' => $endTime->toIso8601String(),
                        ]);
                        
                        return null;
                    }
                    
                    throw $e;
                }
            });
        } catch (\Exception $e) {
            Log::error('Error acquiring lock', [
                'branch_id' => $branchId,
                'staff_id' => $staffId,
                'start_time' => $startTime->toIso8601String(),
                'end_time' => $endTime->toIso8601String(),
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Release a lock using the lock token.
     *
     * @param string $lockToken
     * @return bool
     */
    public function releaseLock(string $lockToken): bool
    {
        if (empty($lockToken)) {
            return false;
        }
        
        try {
            $lock = AppointmentLock::where('lock_token', $lockToken)->first();
            
            if (!$lock) {
                Log::warning('Attempted to release non-existent lock', [
                    'lock_token' => substr($lockToken, 0, 8) . '...',
                ]);
                return false;
            }
            
            $deleted = $lock->delete();
            
            if ($deleted) {
                Log::info('Lock released successfully', [
                    'lock_token' => substr($lockToken, 0, 8) . '...',
                    'staff_id' => $lock->staff_id,
                    'start_time' => $lock->starts_at->toIso8601String(),
                    'end_time' => $lock->ends_at->toIso8601String(),
                ]);
            }
            
            return $deleted;
            
        } catch (\Exception $e) {
            Log::error('Error releasing lock', [
                'lock_token' => substr($lockToken, 0, 8) . '...',
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Extend an existing lock.
     *
     * @param string $lockToken
     * @param int $additionalMinutes
     * @return bool
     */
    public function extendLock(string $lockToken, int $additionalMinutes = 5): bool
    {
        if (empty($lockToken)) {
            return false;
        }
        
        $additionalMinutes = min($additionalMinutes, self::MAX_LOCK_MINUTES);
        
        try {
            return DB::transaction(function () use ($lockToken, $additionalMinutes) {
                $lock = AppointmentLock::where('lock_token', $lockToken)
                    ->lockForUpdate()
                    ->first();
                
                if (!$lock) {
                    Log::warning('Attempted to extend non-existent lock', [
                        'lock_token' => substr($lockToken, 0, 8) . '...',
                    ]);
                    return false;
                }
                
                if ($lock->isExpired()) {
                    Log::warning('Attempted to extend expired lock', [
                        'lock_token' => substr($lockToken, 0, 8) . '...',
                        'expired_at' => $lock->lock_expires_at->toIso8601String(),
                    ]);
                    return false;
                }
                
                $extended = $lock->extend($additionalMinutes);
                
                if ($extended) {
                    Log::info('Lock extended successfully', [
                        'lock_token' => substr($lockToken, 0, 8) . '...',
                        'new_expiry' => $lock->lock_expires_at->toIso8601String(),
                        'extended_by_minutes' => $additionalMinutes,
                    ]);
                }
                
                return $extended;
            });
        } catch (\Exception $e) {
            Log::error('Error extending lock', [
                'lock_token' => substr($lockToken, 0, 8) . '...',
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Check if a time slot is currently locked.
     *
     * @param string $staffId
     * @param Carbon|string $startTime
     * @param Carbon|string $endTime
     * @return bool
     */
    public function isSlotLocked(string $staffId, $startTime, $endTime): bool
    {
        $startTime = Carbon::parse($startTime);
        $endTime = Carbon::parse($endTime);
        
        return AppointmentLock::active()
            ->where('staff_id', $staffId)
            ->forTimeRange($startTime, $endTime)
            ->exists();
    }

    /**
     * Get lock information for a specific token.
     *
     * @param string $lockToken
     * @return AppointmentLock|null
     */
    public function getLockInfo(string $lockToken): ?AppointmentLock
    {
        if (empty($lockToken)) {
            return null;
        }
        
        return AppointmentLock::where('lock_token', $lockToken)->first();
    }

    /**
     * Clean up expired locks.
     *
     * @return int Number of locks cleaned up
     */
    public function cleanupExpiredLocks(): int
    {
        try {
            $count = AppointmentLock::cleanupExpired();
            
            if ($count > 0) {
                Log::info('Cleaned up expired locks', [
                    'count' => $count,
                ]);
            }
            
            return $count;
        } catch (\Exception $e) {
            Log::error('Error cleaning up expired locks', [
                'error' => $e->getMessage(),
            ]);
            
            return 0;
        }
    }

    /**
     * Get all active locks for a staff member.
     *
     * @param string $staffId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveLocksForStaff(string $staffId)
    {
        return AppointmentLock::active()
            ->where('staff_id', $staffId)
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Get all active locks for a branch.
     *
     * @param string $branchId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveLocksForBranch(string $branchId)
    {
        return AppointmentLock::active()
            ->where('branch_id', $branchId)
            ->with('staff')
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Force release all locks for a specific staff member.
     * This should only be used in emergency situations.
     *
     * @param string $staffId
     * @return int Number of locks released
     */
    public function forceReleaseStaffLocks(string $staffId): int
    {
        try {
            $count = AppointmentLock::where('staff_id', $staffId)->delete();
            
            if ($count > 0) {
                Log::warning('Force released locks for staff', [
                    'staff_id' => $staffId,
                    'count' => $count,
                ]);
            }
            
            return $count;
        } catch (\Exception $e) {
            Log::error('Error force releasing staff locks', [
                'staff_id' => $staffId,
                'error' => $e->getMessage(),
            ]);
            
            return 0;
        }
    }

    /**
     * Generate a unique lock token.
     *
     * @return string
     */
    private function generateLockToken(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Validate and verify a lock token.
     *
     * @param string $lockToken
     * @return bool
     */
    public function validateLockToken(string $lockToken): bool
    {
        if (empty($lockToken)) {
            return false;
        }
        
        $lock = $this->getLockInfo($lockToken);
        
        return $lock && $lock->isActive();
    }

    /**
     * Get statistics about current locks.
     *
     * @return array
     */
    public function getLockStatistics(): array
    {
        return [
            'total_active_locks' => AppointmentLock::active()->count(),
            'total_expired_locks' => AppointmentLock::expired()->count(),
            'locks_by_branch' => AppointmentLock::active()
                ->select('branch_id', DB::raw('COUNT(*) as count'))
                ->groupBy('branch_id')
                ->pluck('count', 'branch_id')
                ->toArray(),
            'average_lock_duration' => AppointmentLock::active()
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, lock_expires_at)) as avg_duration')
                ->value('avg_duration'),
        ];
    }
}