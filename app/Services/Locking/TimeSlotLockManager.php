<?php

namespace App\Services\Locking;

use App\Models\Branch;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

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
                
                // Check if there's already an active lock for this time range using cache
                $lockKey = $this->getLockKey($staffId, $startTime, $endTime);
                $existingLock = Cache::get($lockKey);
                
                if ($existingLock) {
                    Log::warning('Lock acquisition failed - slot already locked', [
                        'branch_id' => $branchId,
                        'staff_id' => $staffId,
                        'start_time' => $startTime->toIso8601String(),
                        'end_time' => $endTime->toIso8601String(),
                        'existing_lock_token' => substr($existingLock['lock_token'] ?? '', 0, 8) . '...',
                        'existing_lock_expires' => $existingLock['expires_at'] ?? 'unknown',
                    ]);
                    
                    return null;
                }
                
                // Generate unique lock token
                $lockToken = $this->generateLockToken();
                
                // Create the lock using cache
                $lockData = [
                    'branch_id' => $branchId,
                    'staff_id' => $staffId,
                    'starts_at' => $startTime->toIso8601String(),
                    'ends_at' => $endTime->toIso8601String(),
                    'lock_token' => $lockToken,
                    'expires_at' => now()->addMinutes($lockMinutes)->toIso8601String(),
                    'created_at' => now()->toIso8601String(),
                ];
                
                // Store in cache with expiration
                if (Cache::add($lockKey, $lockData, $lockMinutes * 60)) {
                    // Track this lock key
                    $allKeys = Cache::get('appointment_lock_keys', []);
                    $allKeys[] = $lockKey;
                    Cache::put('appointment_lock_keys', array_unique($allKeys), 86400);
                    
                    Log::info('Lock acquired successfully', [
                        'branch_id' => $branchId,
                        'staff_id' => $staffId,
                        'start_time' => $startTime->toIso8601String(),
                        'end_time' => $endTime->toIso8601String(),
                        'lock_token' => substr($lockToken, 0, 8) . '...',
                        'expires_in_minutes' => $lockMinutes,
                    ]);
                    
                    return $lockToken;
                } else {
                    // Lock acquisition failed - race condition
                    Log::warning('Lock acquisition failed - race condition detected', [
                        'branch_id' => $branchId,
                        'staff_id' => $staffId,
                        'start_time' => $startTime->toIso8601String(),
                        'end_time' => $endTime->toIso8601String(),
                    ]);
                    
                    return null;
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
    public function getActiveLocksForBranch(string $branchId): array
    {
        $allKeys = Cache::get('appointment_lock_keys', []);
        $branchLocks = [];
        
        foreach ($allKeys as $key) {
            $lockData = Cache::get($key);
            if ($lockData && isset($lockData['branch_id']) && $lockData['branch_id'] === $branchId) {
                $expiresAt = Carbon::parse($lockData['expires_at']);
                if (!$expiresAt->isPast()) {
                    $branchLocks[] = $lockData;
                }
            }
        }
        
        // Sort by start time
        usort($branchLocks, function($a, $b) {
            return Carbon::parse($a['starts_at'])->timestamp - Carbon::parse($b['starts_at'])->timestamp;
        });
        
        return $branchLocks;
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
            $allKeys = Cache::get('appointment_lock_keys', []);
            $count = 0;
            $remainingKeys = [];
            
            foreach ($allKeys as $key) {
                $lockData = Cache::get($key);
                if ($lockData && isset($lockData['staff_id']) && $lockData['staff_id'] === $staffId) {
                    Cache::forget($key);
                    $count++;
                } else {
                    $remainingKeys[] = $key;
                }
            }
            
            // Update the tracking list
            Cache::put('appointment_lock_keys', $remainingKeys, 86400);
            
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
        
        if (!$lock) {
            return false;
        }
        
        // Check if lock is still active
        $expiresAt = Carbon::parse($lock['expires_at']);
        return !$expiresAt->isPast();
    }

    /**
     * Get statistics about current locks.
     *
     * @return array
     */
    public function getLockStatistics(): array
    {
        $allKeys = Cache::get('appointment_lock_keys', []);
        $activeLocks = 0;
        $expiredLocks = 0;
        $locksByBranch = [];
        $totalDuration = 0;
        $lockCount = 0;
        
        foreach ($allKeys as $key) {
            $lockData = Cache::get($key);
            if ($lockData) {
                $expiresAt = Carbon::parse($lockData['expires_at']);
                
                if ($expiresAt->isPast()) {
                    $expiredLocks++;
                } else {
                    $activeLocks++;
                    
                    // Count by branch
                    $branchId = $lockData['branch_id'];
                    $locksByBranch[$branchId] = ($locksByBranch[$branchId] ?? 0) + 1;
                    
                    // Calculate duration
                    $createdAt = Carbon::parse($lockData['created_at']);
                    $duration = $expiresAt->diffInMinutes($createdAt);
                    $totalDuration += $duration;
                    $lockCount++;
                }
            }
        }
        
        return [
            'total_active_locks' => $activeLocks,
            'total_expired_locks' => $expiredLocks,
            'locks_by_branch' => $locksByBranch,
            'average_lock_duration' => $lockCount > 0 ? round($totalDuration / $lockCount, 2) : 0,
        ];
    }
    
    /**
     * Generate a cache key for a lock
     */
    private function getLockKey(string $staffId, Carbon $startTime, Carbon $endTime): string
    {
        return sprintf(
            'appointment_lock:%s:%s:%s',
            $staffId,
            $startTime->format('YmdHis'),
            $endTime->format('YmdHis')
        );
    }
}