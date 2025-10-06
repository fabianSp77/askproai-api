<?php

namespace App\Services\Notifications;

use App\Models\Customer;
use App\Models\NotificationQueue;
use App\Models\NotificationAnalytics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeliveryOptimizer
{
    protected array $openRates = [];
    protected array $clickRates = [];

    /**
     * Get optimal send time for notification
     */
    public function getOptimalSendTime(
        $notifiable,
        string $channel,
        string $type,
        bool $immediate = false
    ): ?Carbon {
        // If immediate, send now
        if ($immediate) {
            return now();
        }

        // Check user's preferred time
        $preferredTime = $this->getUserPreferredTime($notifiable, $channel);
        if ($preferredTime) {
            return $this->getNextOccurrence($preferredTime);
        }

        // Use machine learning to find best time
        $optimalTime = $this->predictOptimalTime($notifiable, $channel, $type);
        if ($optimalTime) {
            return $optimalTime;
        }

        // Default to business hours
        return $this->getNextBusinessHour();
    }

    /**
     * Get user's preferred notification time
     */
    protected function getUserPreferredTime($notifiable, string $channel): ?Carbon
    {
        $preference = DB::table('notification_preferences')
            ->where('customer_id', $notifiable->id)
            ->where('channel', $channel)
            ->first();

        if ($preference && $preference->preferred_time) {
            return Carbon::parse($preference->preferred_time);
        }

        return null;
    }

    /**
     * Predict optimal time using historical data
     */
    protected function predictOptimalTime($notifiable, string $channel, string $type): ?Carbon
    {
        $key = "optimal_time:{$channel}:{$type}";

        return Cache::remember($key, 3600, function () use ($notifiable, $channel, $type) {
            // Get historical engagement data
            $engagementData = $this->getEngagementData($channel, $type);

            if (empty($engagementData)) {
                return null;
            }

            // Find hour with best engagement
            $bestHour = $this->findBestEngagementHour($engagementData);

            // Get next occurrence of that hour
            $now = now();
            $targetTime = $now->copy()->setHour($bestHour)->setMinute(0);

            if ($targetTime->isPast()) {
                $targetTime->addDay();
            }

            // Avoid quiet hours
            return $this->avoidQuietHours($targetTime, $notifiable);
        });
    }

    /**
     * Get engagement data for channel and type
     */
    protected function getEngagementData(string $channel, string $type): array
    {
        return DB::table('notification_queue')
            ->select(
                DB::raw('HOUR(sent_at) as hour'),
                DB::raw('COUNT(*) as sent'),
                DB::raw('SUM(opened_at IS NOT NULL) as opened'),
                DB::raw('SUM(clicked_at IS NOT NULL) as clicked')
            )
            ->where('channel', $channel)
            ->where('type', $type)
            ->whereNotNull('sent_at')
            ->where('sent_at', '>=', now()->subDays(30))
            ->groupBy('hour')
            ->get()
            ->mapWithKeys(function ($row) {
                $openRate = $row->sent > 0 ? ($row->opened / $row->sent) : 0;
                $clickRate = $row->opened > 0 ? ($row->clicked / $row->opened) : 0;

                return [
                    $row->hour => [
                        'sent' => $row->sent,
                        'open_rate' => $openRate,
                        'click_rate' => $clickRate,
                        'engagement_score' => ($openRate * 0.6) + ($clickRate * 0.4)
                    ]
                ];
            })
            ->toArray();
    }

    /**
     * Find hour with best engagement
     */
    protected function findBestEngagementHour(array $engagementData): int
    {
        if (empty($engagementData)) {
            return 10; // Default to 10 AM
        }

        $bestHour = 10;
        $bestScore = 0;

        foreach ($engagementData as $hour => $data) {
            if ($data['engagement_score'] > $bestScore) {
                $bestScore = $data['engagement_score'];
                $bestHour = $hour;
            }
        }

        return $bestHour;
    }

    /**
     * Get next business hour
     */
    protected function getNextBusinessHour(): Carbon
    {
        $now = now();
        $businessStart = 9; // 9 AM
        $businessEnd = 18;  // 6 PM

        // If within business hours, send now
        if ($now->hour >= $businessStart && $now->hour < $businessEnd && !$now->isWeekend()) {
            return $now;
        }

        // Find next business hour
        $next = $now->copy();

        if ($now->hour >= $businessEnd || $now->isWeekend()) {
            // Next business day at 9 AM
            $next->addDay();
            while ($next->isWeekend()) {
                $next->addDay();
            }
            $next->setHour($businessStart)->setMinute(0);
        } else {
            // Today at 9 AM
            $next->setHour($businessStart)->setMinute(0);
        }

        return $next;
    }

    /**
     * Get next occurrence of a specific time
     */
    protected function getNextOccurrence(Carbon $time): Carbon
    {
        $now = now();
        $next = $now->copy()
            ->setHour($time->hour)
            ->setMinute($time->minute)
            ->setSecond(0);

        if ($next->isPast()) {
            $next->addDay();
        }

        return $next;
    }

    /**
     * Avoid user's quiet hours
     */
    protected function avoidQuietHours(Carbon $time, $notifiable): Carbon
    {
        $preference = DB::table('notification_preferences')
            ->where('customer_id', $notifiable->id)
            ->first();

        if (!$preference || !$preference->quiet_hours) {
            return $time;
        }

        $quietHours = json_decode($preference->quiet_hours, true);

        if (!isset($quietHours['start']) || !isset($quietHours['end'])) {
            return $time;
        }

        $quietStart = Carbon::parse($quietHours['start']);
        $quietEnd = Carbon::parse($quietHours['end']);

        // Check if time falls within quiet hours
        if ($this->isInQuietHours($time, $quietStart, $quietEnd)) {
            // Move to after quiet hours
            $time = $time->copy()
                ->setHour($quietEnd->hour)
                ->setMinute($quietEnd->minute);

            // If moved to past, add a day
            if ($time->isPast()) {
                $time->addDay();
            }
        }

        return $time;
    }

    /**
     * Check if time is in quiet hours
     */
    protected function isInQuietHours(Carbon $time, Carbon $start, Carbon $end): bool
    {
        $timeMinutes = $time->hour * 60 + $time->minute;
        $startMinutes = $start->hour * 60 + $start->minute;
        $endMinutes = $end->hour * 60 + $end->minute;

        // Handle overnight quiet hours
        if ($endMinutes < $startMinutes) {
            return $timeMinutes >= $startMinutes || $timeMinutes <= $endMinutes;
        }

        return $timeMinutes >= $startMinutes && $timeMinutes <= $endMinutes;
    }

    /**
     * Batch notifications for efficiency
     */
    public function batchNotifications(array $notifications): array
    {
        $batches = [];

        foreach ($notifications as $notification) {
            $key = $notification['channel'] . '_' . $notification['provider'];

            if (!isset($batches[$key])) {
                $batches[$key] = [];
            }

            $batches[$key][] = $notification;
        }

        return $batches;
    }

    /**
     * Calculate delivery cost
     */
    public function calculateCost(string $channel, string $provider, array $content): float
    {
        $baseCosts = [
            'email' => 0.0001,
            'sms' => 0.05,
            'whatsapp' => 0.005,
            'push' => 0.0001
        ];

        $cost = $baseCosts[$channel] ?? 0;

        // Add provider-specific costs
        if ($channel === 'sms') {
            // Calculate based on message length
            $messageLength = strlen($content['text'] ?? '');
            $parts = ceil($messageLength / 160);
            $cost *= $parts;
        }

        // Add media costs
        if (!empty($content['media'])) {
            $cost += 0.002;
        }

        return $cost;
    }

    /**
     * Get channel reliability score
     */
    public function getChannelReliability(string $channel): float
    {
        $key = "channel_reliability:{$channel}";

        return Cache::remember($key, 3600, function () use ($channel) {
            $stats = DB::table('notification_queue')
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(status = "sent") as sent'),
                    DB::raw('SUM(status = "delivered") as delivered'),
                    DB::raw('SUM(status = "failed") as failed')
                )
                ->where('channel', $channel)
                ->where('created_at', '>=', now()->subDays(7))
                ->first();

            if ($stats->total == 0) {
                return 1.0; // No data, assume reliable
            }

            $deliveryRate = $stats->delivered / $stats->total;
            $failureRate = $stats->failed / $stats->total;

            return max(0, min(1, $deliveryRate - ($failureRate * 2)));
        });
    }

    /**
     * Prioritize channels based on reliability and cost
     */
    public function prioritizeChannels(array $channels): array
    {
        $scored = [];

        foreach ($channels as $channel) {
            $reliability = $this->getChannelReliability($channel);
            $cost = $this->getChannelCost($channel);

            // Score based on reliability (70%) and cost (30%)
            $score = ($reliability * 0.7) + ((1 - $cost) * 0.3);

            $scored[] = [
                'channel' => $channel,
                'score' => $score,
                'reliability' => $reliability,
                'cost' => $cost
            ];
        }

        // Sort by score descending
        usort($scored, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_column($scored, 'channel');
    }

    /**
     * Get normalized channel cost
     */
    protected function getChannelCost(string $channel): float
    {
        $costs = [
            'email' => 0.01,
            'push' => 0.01,
            'whatsapp' => 0.1,
            'sms' => 1.0
        ];

        return $costs[$channel] ?? 0.5;
    }

    /**
     * Throttle notifications to prevent spam
     */
    public function shouldThrottle($notifiable, string $channel): bool
    {
        $key = "notification_throttle:{$notifiable->id}:{$channel}";
        $limit = $this->getThrottleLimit($channel);
        $window = 3600; // 1 hour

        $count = Cache::get($key, 0);

        if ($count >= $limit) {
            return true;
        }

        Cache::increment($key);
        Cache::expire($key, $window);

        return false;
    }

    /**
     * Get throttle limit for channel
     */
    protected function getThrottleLimit(string $channel): int
    {
        $limits = [
            'email' => 10,
            'sms' => 5,
            'whatsapp' => 8,
            'push' => 20
        ];

        return $limits[$channel] ?? 10;
    }
}