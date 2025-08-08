<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Clear Calendar Cache Job
 * 
 * Asynchronously clears calendar-related caches
 * to maintain data consistency after calendar changes
 */
class ClearCalendarCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected string $calendarId;
    protected ?array $specificKeys;
    
    /**
     * Create a new job instance.
     */
    public function __construct(string $calendarId, ?array $specificKeys = null)
    {
        $this->calendarId = $calendarId;
        $this->specificKeys = $specificKeys;
        
        // Set queue and delay
        $this->onQueue('calendar-cache');
        $this->delay(now()->addSeconds(5)); // Small delay to ensure consistency
    }
    
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->specificKeys) {
                $this->clearSpecificKeys();
            } else {
                $this->clearAllCalendarCaches();
            }
            
            Log::info('Calendar cache cleared successfully', [
                'calendar_id' => $this->calendarId,
                'specific_keys' => $this->specificKeys ? count($this->specificKeys) : null
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to clear calendar cache', [
                'calendar_id' => $this->calendarId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Clear all caches for this calendar
     */
    protected function clearAllCalendarCaches(): void
    {
        $patterns = [
            "calendar_slots_{$this->calendarId}_*",
            "calendar_batch_slots_*{$this->calendarId}*",
            "availability_check_{$this->calendarId}_*",
            "busy_times_{$this->calendarId}_*"
        ];
        
        foreach ($patterns as $pattern) {
            $this->clearCachePattern($pattern);
        }
    }
    
    /**
     * Clear specific cache keys
     */
    protected function clearSpecificKeys(): void
    {
        foreach ($this->specificKeys as $key) {
            Cache::forget($key);
        }
    }
    
    /**
     * Clear cache keys matching a pattern
     */
    protected function clearCachePattern(string $pattern): void
    {
        // This is a simplified implementation
        // In production, you might want to use Redis SCAN or similar
        $keys = Cache::get('cache_keys_registry', []);
        
        foreach ($keys as $key) {
            if ($this->matchesPattern($key, $pattern)) {
                Cache::forget($key);
            }
        }
    }
    
    /**
     * Check if key matches pattern
     */
    protected function matchesPattern(string $key, string $pattern): bool
    {
        // Convert pattern to regex
        $regex = str_replace('*', '.*', preg_quote($pattern, '/'));
        return preg_match("/^{$regex}$/", $key);
    }
}