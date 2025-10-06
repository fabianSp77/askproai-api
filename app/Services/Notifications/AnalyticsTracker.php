<?php

namespace App\Services\Notifications;

use App\Models\NotificationQueue;
use App\Models\NotificationAnalytics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsTracker
{
    /**
     * Track sent notification
     */
    public function trackSent(NotificationQueue $notification): void
    {
        $this->incrementMetric($notification, 'sent_count');
        $this->updateDeliveryTime($notification);
    }

    /**
     * Track delivered notification
     */
    public function trackDelivered(NotificationQueue $notification): void
    {
        $this->incrementMetric($notification, 'delivered_count');

        // Update delivery time
        if ($notification->sent_at) {
            $deliveryTime = now()->diffInSeconds($notification->sent_at);
            $this->updateAverageDeliveryTime($notification, $deliveryTime);
        }
    }

    /**
     * Track opened notification
     */
    public function trackOpened(NotificationQueue $notification): void
    {
        $this->incrementMetric($notification, 'opened_count');

        // Update notification record
        $notification->update(['opened_at' => now()]);

        // Track open rate
        $this->updateOpenRate($notification);
    }

    /**
     * Track clicked notification
     */
    public function trackClicked(NotificationQueue $notification): void
    {
        $this->incrementMetric($notification, 'clicked_count');

        // Update notification record
        $notification->update(['clicked_at' => now()]);

        // Track click rate
        $this->updateClickRate($notification);
    }

    /**
     * Track failed notification
     */
    public function trackFailed(NotificationQueue $notification): void
    {
        $this->incrementMetric($notification, 'failed_count');
    }

    /**
     * Track bounced notification
     */
    public function trackBounced(NotificationQueue $notification): void
    {
        $this->incrementMetric($notification, 'bounced_count');
    }

    /**
     * Increment analytics metric
     */
    protected function incrementMetric(NotificationQueue $notification, string $metric): void
    {
        $date = now()->toDateString();
        $companyId = $notification->notifiable->company_id ?? null;

        DB::table('notification_analytics')
            ->updateOrInsert(
                [
                    'date' => $date,
                    'company_id' => $companyId,
                    'channel' => $notification->channel,
                    'type' => $notification->type
                ],
                [
                    $metric => DB::raw("{$metric} + 1"),
                    'updated_at' => now()
                ]
            );

        // Clear cache
        $this->clearAnalyticsCache($companyId, $notification->channel);
    }

    /**
     * Update average delivery time
     */
    protected function updateAverageDeliveryTime(NotificationQueue $notification, int $seconds): void
    {
        $date = now()->toDateString();
        $companyId = $notification->notifiable->company_id ?? null;

        $analytics = DB::table('notification_analytics')
            ->where('date', $date)
            ->where('company_id', $companyId)
            ->where('channel', $notification->channel)
            ->where('type', $notification->type)
            ->first();

        if ($analytics) {
            $currentAvg = $analytics->avg_delivery_time ?? 0;
            $currentCount = $analytics->delivered_count ?? 1;

            // Calculate new average
            $newAvg = (($currentAvg * ($currentCount - 1)) + $seconds) / $currentCount;

            DB::table('notification_analytics')
                ->where('id', $analytics->id)
                ->update([
                    'avg_delivery_time' => round($newAvg),
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Update open rate
     */
    protected function updateOpenRate(NotificationQueue $notification): void
    {
        $this->updateRate($notification, 'open_rate', 'opened_count', 'sent_count');
    }

    /**
     * Update click rate
     */
    protected function updateClickRate(NotificationQueue $notification): void
    {
        $this->updateRate($notification, 'click_rate', 'clicked_count', 'opened_count');
    }

    /**
     * Update rate metric
     */
    protected function updateRate(NotificationQueue $notification, string $rateField, string $numerator, string $denominator): void
    {
        $date = now()->toDateString();
        $companyId = $notification->notifiable->company_id ?? null;

        $analytics = DB::table('notification_analytics')
            ->where('date', $date)
            ->where('company_id', $companyId)
            ->where('channel', $notification->channel)
            ->where('type', $notification->type)
            ->first();

        if ($analytics && $analytics->$denominator > 0) {
            $rate = ($analytics->$numerator / $analytics->$denominator) * 100;

            DB::table('notification_analytics')
                ->where('id', $analytics->id)
                ->update([
                    $rateField => round($rate, 2),
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Update delivery time
     */
    protected function updateDeliveryTime(NotificationQueue $notification): void
    {
        if ($notification->scheduled_at && $notification->sent_at) {
            $delay = $notification->sent_at->diffInSeconds($notification->scheduled_at);

            // Track if notification was delayed
            if ($delay > 300) { // More than 5 minutes late
                $this->trackDelay($notification, $delay);
            }
        }
    }

    /**
     * Track notification delay
     */
    protected function trackDelay(NotificationQueue $notification, int $delaySeconds): void
    {
        DB::table('notification_deliveries')->insert([
            'notification_queue_id' => $notification->id,
            'event' => 'delayed',
            'data' => json_encode([
                'delay_seconds' => $delaySeconds,
                'scheduled_at' => $notification->scheduled_at->toIso8601String(),
                'sent_at' => $notification->sent_at->toIso8601String()
            ]),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Get analytics summary
     */
    public function getSummary(?int $companyId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = DB::table('notification_analytics');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($startDate) {
            $query->where('date', '>=', $startDate->toDateString());
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate->toDateString());
        }

        $data = $query
            ->select(
                'channel',
                DB::raw('SUM(sent_count) as total_sent'),
                DB::raw('SUM(delivered_count) as total_delivered'),
                DB::raw('SUM(opened_count) as total_opened'),
                DB::raw('SUM(clicked_count) as total_clicked'),
                DB::raw('SUM(failed_count) as total_failed'),
                DB::raw('SUM(bounced_count) as total_bounced'),
                DB::raw('SUM(total_cost) as total_cost'),
                DB::raw('AVG(delivery_rate) as avg_delivery_rate'),
                DB::raw('AVG(open_rate) as avg_open_rate'),
                DB::raw('AVG(click_rate) as avg_click_rate'),
                DB::raw('AVG(avg_delivery_time) as avg_delivery_time')
            )
            ->groupBy('channel')
            ->get();

        return [
            'by_channel' => $data->keyBy('channel')->toArray(),
            'totals' => [
                'sent' => $data->sum('total_sent'),
                'delivered' => $data->sum('total_delivered'),
                'opened' => $data->sum('total_opened'),
                'clicked' => $data->sum('total_clicked'),
                'failed' => $data->sum('total_failed'),
                'cost' => $data->sum('total_cost')
            ],
            'rates' => [
                'delivery' => $data->avg('avg_delivery_rate'),
                'open' => $data->avg('avg_open_rate'),
                'click' => $data->avg('avg_click_rate')
            ]
        ];
    }

    /**
     * Get trending metrics
     */
    public function getTrending(?int $companyId = null, int $days = 7): array
    {
        $startDate = now()->subDays($days);

        $query = DB::table('notification_analytics')
            ->where('date', '>=', $startDate->toDateString());

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query
            ->select(
                'date',
                'channel',
                DB::raw('SUM(sent_count) as sent'),
                DB::raw('SUM(delivered_count) as delivered'),
                DB::raw('SUM(opened_count) as opened'),
                DB::raw('SUM(clicked_count) as clicked'),
                DB::raw('SUM(failed_count) as failed')
            )
            ->groupBy('date', 'channel')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->toArray();
    }

    /**
     * Get channel performance
     */
    public function getChannelPerformance(?int $companyId = null, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $query = DB::table('notification_analytics')
            ->where('date', '>=', $startDate->toDateString());

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query
            ->select(
                'channel',
                DB::raw('AVG(delivery_rate) as avg_delivery_rate'),
                DB::raw('AVG(open_rate) as avg_open_rate'),
                DB::raw('AVG(click_rate) as avg_click_rate'),
                DB::raw('AVG(avg_delivery_time) as avg_delivery_time'),
                DB::raw('SUM(total_cost) / NULLIF(SUM(sent_count), 0) as cost_per_message'),
                DB::raw('SUM(failed_count) / NULLIF(SUM(sent_count), 0) * 100 as failure_rate')
            )
            ->groupBy('channel')
            ->get()
            ->keyBy('channel')
            ->toArray();
    }

    /**
     * Get top performing templates
     */
    public function getTopTemplates(?int $companyId = null, int $limit = 10): array
    {
        $query = DB::table('notification_queue')
            ->join('notification_templates', 'notification_queue.template_key', '=', 'notification_templates.key')
            ->where('notification_queue.created_at', '>=', now()->subDays(30));

        if ($companyId) {
            $query->whereRaw('JSON_EXTRACT(notification_queue.data, "$.company_id") = ?', [$companyId]);
        }

        return $query
            ->select(
                'notification_templates.key',
                'notification_templates.name',
                'notification_templates.channel',
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('SUM(notification_queue.opened_at IS NOT NULL) / COUNT(*) * 100 as open_rate'),
                DB::raw('SUM(notification_queue.clicked_at IS NOT NULL) / NULLIF(SUM(notification_queue.opened_at IS NOT NULL), 0) * 100 as click_rate')
            )
            ->groupBy('notification_templates.key', 'notification_templates.name', 'notification_templates.channel')
            ->orderByDesc('open_rate')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Calculate cost for period
     */
    public function calculateCost(?int $companyId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = DB::table('notification_queue');

        if ($companyId) {
            $query->whereRaw('JSON_EXTRACT(data, "$.company_id") = ?', [$companyId]);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $data = $query
            ->select(
                'channel',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(cost) as total_cost')
            )
            ->whereNotNull('cost')
            ->groupBy('channel')
            ->get();

        return [
            'by_channel' => $data->keyBy('channel')->toArray(),
            'total' => $data->sum('total_cost'),
            'count' => $data->sum('count'),
            'average' => $data->sum('count') > 0 ? $data->sum('total_cost') / $data->sum('count') : 0
        ];
    }

    /**
     * Clear analytics cache
     */
    protected function clearAnalyticsCache(?int $companyId, string $channel): void
    {
        $keys = [
            "analytics_summary:{$companyId}",
            "analytics_channel:{$channel}",
            "analytics_trending:{$companyId}"
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Generate analytics report
     */
    public function generateReport(?int $companyId = null, string $period = 'month'): array
    {
        $startDate = match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            default => now()->subMonth()
        };

        return [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => now()->toDateString(),
            'summary' => $this->getSummary($companyId, $startDate, now()),
            'trending' => $this->getTrending($companyId, 30),
            'channel_performance' => $this->getChannelPerformance($companyId),
            'top_templates' => $this->getTopTemplates($companyId),
            'cost_analysis' => $this->calculateCost($companyId, $startDate, now()),
            'generated_at' => now()->toIso8601String()
        ];
    }
}