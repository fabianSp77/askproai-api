<?php

namespace App\Services\RealTime;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Exceptions\SlotNotAvailableException;
use App\Exceptions\SlotReservationException;

/**
 * Manages appointment slot reservations using Redis for high-performance
 * concurrent access control
 */
class RedisSlotManager
{
    // Slot states
    const STATE_AVAILABLE = 'available';
    const STATE_RESERVED = 'reserved';
    const STATE_BOOKED = 'booked';
    const STATE_BLOCKED = 'blocked';
    
    // Default reservation TTL (5 minutes)
    private int $reservationTTL = 300;
    
    // Slot key patterns
    private string $slotKeyPattern = 'slot:%s:%s:%s'; // branch:staff:datetime
    private string $reservationPattern = 'reservation:%s';
    private string $slotIndexPattern = 'slots:index:%s:%s'; // branch:date
    
    /**
     * Try to reserve a slot atomically
     */
    public function tryReserveSlot(
        int $branchId,
        int $staffId,
        Carbon $startTime,
        Carbon $endTime,
        string $reservationId
    ): bool {
        $slotKey = $this->getSlotKey($branchId, $staffId, $startTime);
        
        // Lua script for atomic reservation
        $script = <<<'LUA'
            local slot_key = KEYS[1]
            local reservation_key = KEYS[2]
            local slot_index = KEYS[3]
            
            local reservation_id = ARGV[1]
            local ttl = tonumber(ARGV[2])
            local start_time = ARGV[3]
            local end_time = ARGV[4]
            local metadata = ARGV[5]
            
            -- Check if slot exists and is available
            local current_state = redis.call('hget', slot_key, 'state')
            
            if current_state and current_state ~= 'available' then
                return {0, current_state}  -- Slot not available
            end
            
            -- Reserve the slot
            redis.call('hmset', slot_key, 
                'state', 'reserved',
                'reservation_id', reservation_id,
                'reserved_at', ARGV[6],
                'start_time', start_time,
                'end_time', end_time
            )
            redis.call('expire', slot_key, ttl)
            
            -- Create reservation record
            redis.call('hmset', reservation_key,
                'slot_key', slot_key,
                'reservation_id', reservation_id,
                'branch_id', ARGV[7],
                'staff_id', ARGV[8],
                'start_time', start_time,
                'end_time', end_time,
                'metadata', metadata,
                'created_at', ARGV[6]
            )
            redis.call('expire', reservation_key, ttl)
            
            -- Add to slot index for this date
            redis.call('zadd', slot_index, ARGV[9], slot_key)
            
            -- Add to active reservations set
            redis.call('zadd', 'reservations:active', ARGV[10], reservation_key)
            
            return {1, 'reserved'}
LUA;
        
        $reservationKey = sprintf($this->reservationPattern, $reservationId);
        $slotIndex = sprintf($this->slotIndexPattern, $branchId, $startTime->format('Y-m-d'));
        
        $result = Redis::eval(
            $script,
            3,
            $slotKey,
            $reservationKey,
            $slotIndex,
            $reservationId,
            $this->reservationTTL,
            $startTime->toIso8601String(),
            $endTime->toIso8601String(),
            json_encode(['branch_id' => $branchId, 'staff_id' => $staffId]),
            now()->toIso8601String(),
            $branchId,
            $staffId,
            $startTime->timestamp,
            now()->timestamp + $this->reservationTTL
        );
        
        $success = $result[0] === 1;
        
        if ($success) {
            Log::info('Slot reserved successfully', [
                'reservation_id' => $reservationId,
                'slot_key' => $slotKey,
                'branch_id' => $branchId,
                'staff_id' => $staffId,
                'time' => $startTime->format('Y-m-d H:i'),
            ]);
        } else {
            Log::warning('Failed to reserve slot', [
                'reservation_id' => $reservationId,
                'slot_key' => $slotKey,
                'current_state' => $result[1],
            ]);
        }
        
        return $success;
    }
    
    /**
     * Convert a reservation to a permanent booking
     */
    public function convertReservationToBooking(string $reservationId, int $appointmentId): bool
    {
        $script = <<<'LUA'
            local reservation_key = KEYS[1]
            
            local appointment_id = ARGV[1]
            local now = ARGV[2]
            
            -- Get reservation data
            local reservation = redis.call('hgetall', reservation_key)
            if #reservation == 0 then
                return {0, 'reservation_not_found'}
            end
            
            -- Convert to hash table
            local res_data = {}
            for i = 1, #reservation, 2 do
                res_data[reservation[i]] = reservation[i + 1]
            end
            
            local slot_key = res_data['slot_key']
            
            -- Update slot to booked state
            redis.call('hmset', slot_key,
                'state', 'booked',
                'appointment_id', appointment_id,
                'booked_at', now,
                'reservation_id', nil
            )
            redis.call('persist', slot_key)  -- Remove TTL
            
            -- Remove reservation
            redis.call('del', reservation_key)
            redis.call('zrem', 'reservations:active', reservation_key)
            
            -- Add to booked slots
            redis.call('zadd', 'slots:booked:' .. res_data['branch_id'], ARGV[3], slot_key)
            
            return {1, 'converted'}
LUA;
        
        $reservationKey = sprintf($this->reservationPattern, $reservationId);
        
        $result = Redis::eval(
            $script,
            1,
            $reservationKey,
            $appointmentId,
            now()->toIso8601String(),
            now()->timestamp
        );
        
        $success = $result[0] === 1;
        
        if ($success) {
            Log::info('Reservation converted to booking', [
                'reservation_id' => $reservationId,
                'appointment_id' => $appointmentId,
            ]);
        } else {
            Log::error('Failed to convert reservation', [
                'reservation_id' => $reservationId,
                'reason' => $result[1],
            ]);
        }
        
        return $success;
    }
    
    /**
     * Release a reserved slot
     */
    public function releaseSlot(string $reservationId): bool
    {
        $script = <<<'LUA'
            local reservation_key = KEYS[1]
            
            -- Get reservation data
            local slot_key = redis.call('hget', reservation_key, 'slot_key')
            if not slot_key then
                return {0, 'reservation_not_found'}
            end
            
            local branch_id = redis.call('hget', reservation_key, 'branch_id')
            local start_time = redis.call('hget', reservation_key, 'start_time')
            
            -- Check if slot is still reserved by this reservation
            local current_res_id = redis.call('hget', slot_key, 'reservation_id')
            if current_res_id ~= ARGV[1] then
                return {0, 'not_owner'}
            end
            
            -- Release the slot
            redis.call('hmset', slot_key,
                'state', 'available',
                'reservation_id', '',
                'reserved_at', ''
            )
            redis.call('expire', slot_key, 3600)  -- Keep for 1 hour
            
            -- Remove reservation
            redis.call('del', reservation_key)
            redis.call('zrem', 'reservations:active', reservation_key)
            
            return {1, 'released'}
LUA;
        
        $reservationKey = sprintf($this->reservationPattern, $reservationId);
        
        $result = Redis::eval(
            $script,
            1,
            $reservationKey,
            $reservationId
        );
        
        $success = $result[0] === 1;
        
        if ($success) {
            Log::info('Slot released', ['reservation_id' => $reservationId]);
        }
        
        return $success;
    }
    
    /**
     * Check if a slot is available
     */
    public function isSlotAvailable(
        int $branchId,
        int $staffId,
        Carbon $startTime,
        Carbon $endTime
    ): bool {
        $slotKey = $this->getSlotKey($branchId, $staffId, $startTime);
        
        // Check all slots in the time range
        $slots = $this->getSlotsInRange($branchId, $staffId, $startTime, $endTime);
        
        foreach ($slots as $slot) {
            $state = Redis::hget($slot, 'state');
            if ($state && $state !== self::STATE_AVAILABLE) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get all slots in a time range
     */
    public function getSlotsInRange(
        int $branchId,
        int $staffId,
        Carbon $startTime,
        Carbon $endTime
    ): array {
        $slots = [];
        $current = $startTime->copy();
        
        // Generate slot keys for each 15-minute interval
        while ($current->lt($endTime)) {
            $slots[] = $this->getSlotKey($branchId, $staffId, $current);
            $current->addMinutes(15);
        }
        
        return $slots;
    }
    
    /**
     * Block slots for breaks, holidays, etc.
     */
    public function blockSlots(
        int $branchId,
        int $staffId,
        Carbon $startTime,
        Carbon $endTime,
        string $reason = 'blocked'
    ): int {
        $slots = $this->getSlotsInRange($branchId, $staffId, $startTime, $endTime);
        $blocked = 0;
        
        $pipe = Redis::pipeline();
        
        foreach ($slots as $slotKey) {
            $pipe->hget($slotKey, 'state');
        }
        
        $states = $pipe->execute();
        
        $pipe = Redis::pipeline();
        
        foreach ($slots as $index => $slotKey) {
            // Only block if available or not set
            if (!$states[$index] || $states[$index] === self::STATE_AVAILABLE) {
                $pipe->hmset($slotKey, [
                    'state' => self::STATE_BLOCKED,
                    'blocked_reason' => $reason,
                    'blocked_at' => now()->toIso8601String(),
                ]);
                $pipe->expire($slotKey, 86400); // 24 hours
                $blocked++;
            }
        }
        
        $pipe->execute();
        
        Log::info('Slots blocked', [
            'branch_id' => $branchId,
            'staff_id' => $staffId,
            'start' => $startTime->toIso8601String(),
            'end' => $endTime->toIso8601String(),
            'blocked_count' => $blocked,
            'reason' => $reason,
        ]);
        
        return $blocked;
    }
    
    /**
     * Find next available slot
     */
    public function findNextAvailableSlot(
        int $branchId,
        int $staffId,
        Carbon $afterTime,
        int $duration = 30,
        int $searchDays = 7
    ): ?array {
        $searchEnd = $afterTime->copy()->addDays($searchDays);
        $current = $afterTime->copy();
        
        // Round to next 15-minute interval
        $minutes = $current->minute;
        $roundedMinutes = ceil($minutes / 15) * 15;
        $current->minute($roundedMinutes % 60);
        if ($roundedMinutes >= 60) {
            $current->addHour();
        }
        
        while ($current->lt($searchEnd)) {
            // Skip outside business hours
            if (!$this->isWithinBusinessHours($branchId, $current)) {
                $current->addMinutes(15);
                continue;
            }
            
            // Check if slot is available
            $endTime = $current->copy()->addMinutes($duration);
            
            if ($this->isSlotAvailable($branchId, $staffId, $current, $endTime)) {
                return [
                    'branch_id' => $branchId,
                    'staff_id' => $staffId,
                    'start' => $current->toIso8601String(),
                    'end' => $endTime->toIso8601String(),
                    'duration' => $duration,
                ];
            }
            
            $current->addMinutes(15);
        }
        
        return null;
    }
    
    /**
     * Get availability matrix for multiple staff
     */
    public function getAvailabilityMatrix(
        int $branchId,
        array $staffIds,
        Carbon $date,
        int $slotDuration = 30
    ): array {
        $matrix = [];
        $dayStart = $date->copy()->setTime(8, 0);
        $dayEnd = $date->copy()->setTime(20, 0);
        
        // Initialize matrix
        foreach ($staffIds as $staffId) {
            $matrix[$staffId] = [];
        }
        
        // Check each time slot
        $current = $dayStart->copy();
        while ($current->lt($dayEnd)) {
            $slotEnd = $current->copy()->addMinutes($slotDuration);
            
            foreach ($staffIds as $staffId) {
                $available = $this->isSlotAvailable($branchId, $staffId, $current, $slotEnd);
                $matrix[$staffId][$current->format('H:i')] = $available;
            }
            
            $current->addMinutes(15);
        }
        
        return $matrix;
    }
    
    /**
     * Clean up expired reservations
     */
    public function cleanupExpiredReservations(): int
    {
        $script = <<<'LUA'
            local now = tonumber(ARGV[1])
            local expired_count = 0
            
            -- Get expired reservations
            local expired = redis.call('zrangebyscore', 'reservations:active', 0, now)
            
            for _, reservation_key in ipairs(expired) do
                -- Get slot key before deleting reservation
                local slot_key = redis.call('hget', reservation_key, 'slot_key')
                
                if slot_key then
                    -- Check if slot is still reserved by this reservation
                    local res_id = redis.call('hget', reservation_key, 'reservation_id')
                    local current_res_id = redis.call('hget', slot_key, 'reservation_id')
                    
                    if res_id == current_res_id then
                        -- Release the slot
                        redis.call('hmset', slot_key,
                            'state', 'available',
                            'reservation_id', '',
                            'reserved_at', ''
                        )
                    end
                end
                
                -- Remove reservation
                redis.call('del', reservation_key)
                expired_count = expired_count + 1
            end
            
            -- Remove from active set
            if #expired > 0 then
                redis.call('zrem', 'reservations:active', unpack(expired))
            end
            
            return expired_count
LUA;
        
        $cleaned = Redis::eval($script, 0, now()->timestamp);
        
        if ($cleaned > 0) {
            Log::info('Cleaned up expired reservations', ['count' => $cleaned]);
        }
        
        return $cleaned;
    }
    
    /**
     * Get slot statistics for monitoring
     */
    public function getSlotStatistics(int $branchId, Carbon $date): array
    {
        $stats = [
            'total_slots' => 0,
            'available' => 0,
            'reserved' => 0,
            'booked' => 0,
            'blocked' => 0,
        ];
        
        $pattern = sprintf('slot:%d:*:%s*', $branchId, $date->format('Y-m-d'));
        $slots = Redis::keys($pattern);
        
        $pipe = Redis::pipeline();
        foreach ($slots as $slot) {
            $pipe->hget($slot, 'state');
        }
        $states = $pipe->execute();
        
        foreach ($states as $state) {
            $stats['total_slots']++;
            $stats[$state ?? 'available']++;
        }
        
        return $stats;
    }
    
    /**
     * Generate slot key
     */
    private function getSlotKey(int $branchId, int $staffId, Carbon $time): string
    {
        return sprintf(
            $this->slotKeyPattern,
            $branchId,
            $staffId,
            $time->format('Y-m-d\TH:i:00')
        );
    }
    
    /**
     * Check if time is within business hours
     */
    private function isWithinBusinessHours(int $branchId, Carbon $time): bool
    {
        // This would check actual branch business hours
        // For now, assume 8 AM to 8 PM
        $hour = $time->hour;
        return $hour >= 8 && $hour < 20;
    }
    
    /**
     * Set custom reservation TTL
     */
    public function setReservationTTL(int $seconds): void
    {
        $this->reservationTTL = max(60, min(3600, $seconds)); // Between 1 minute and 1 hour
    }
}