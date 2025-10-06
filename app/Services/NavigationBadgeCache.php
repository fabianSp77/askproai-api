<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\CallbackRequest;
use App\Models\Appointment;
use App\Models\NotificationQueue;
// Add other models as needed

class NavigationBadgeCache
{
    /**
     * Cache duration in seconds (5 minutes)
     */
    const CACHE_TTL = 300;

    /**
     * Get pending callback requests count with caching
     */
    public static function pendingCallbacksCount(): int
    {
        return Cache::remember(
            'nav_badge_callbacks_pending',
            self::CACHE_TTL,
            fn () => CallbackRequest::where('status', CallbackRequest::STATUS_PENDING)->count()
        );
    }

    /**
     * Get today's appointments count with caching
     */
    public static function todayAppointmentsCount(): int
    {
        return Cache::remember(
            'nav_badge_appointments_today',
            self::CACHE_TTL,
            fn () => Appointment::whereDate('scheduled_at', today())->count()
        );
    }

    /**
     * Get pending notifications count with caching
     */
    public static function pendingNotificationsCount(): int
    {
        return Cache::remember(
            'nav_badge_notifications_pending',
            self::CACHE_TTL,
            fn () => NotificationQueue::where('status', 'pending')->count()
        );
    }

    /**
     * Invalidate all navigation badge caches
     */
    public static function invalidateAll(): void
    {
        $keys = [
            'nav_badge_callbacks_pending',
            'nav_badge_appointments_today',
            'nav_badge_notifications_pending',
            // Add all badge cache keys
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalidate specific badge cache
     */
    public static function invalidate(string $key): void
    {
        Cache::forget("nav_badge_{$key}");
    }
}
