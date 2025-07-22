<?php

namespace App\Filament\Admin\Widgets;

use App\Models\RetellAICallCampaign;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;

class CampaignPerformanceInsightsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.campaign-performance-insights';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Campaign Performance Insights';

    protected function getViewData(): array
    {
        $companyId = auth()->user()->company_id;

        // Get campaign statistics
        $totalCampaigns = RetellAICallCampaign::where('company_id', $companyId)->count();
        $activeCampaigns = RetellAICallCampaign::where('company_id', $companyId)
            ->whereIn('status', ['running', 'scheduled'])
            ->count();

        // Calculate overall performance
        $completedCampaigns = RetellAICallCampaign::where('company_id', $companyId)
            ->where('status', 'completed')
            ->get();

        $overallMetrics = [
            'total_calls' => $completedCampaigns->sum('calls_completed') + $completedCampaigns->sum('calls_failed'),
            'successful_calls' => $completedCampaigns->sum('calls_completed'),
            'failed_calls' => $completedCampaigns->sum('calls_failed'),
            'average_success_rate' => $completedCampaigns->count() > 0
                ? round($completedCampaigns->avg('success_rate'))
                : 0,
        ];

        // Get top performing campaigns
        $topCampaigns = RetellAICallCampaign::where('company_id', $companyId)
            ->where('status', 'completed')
            ->where('total_targets', '>', 0)
            ->orderByRaw('(calls_completed * 100.0 / total_targets) DESC')
            ->take(5)
            ->get()
            ->map(function ($campaign) {
                return [
                    'name' => $campaign->name,
                    'success_rate' => $campaign->success_rate,
                    'total_calls' => $campaign->calls_completed + $campaign->calls_failed,
                    'completion_date' => $campaign->completed_at?->format('M d, Y'),
                ];
            });

        // Get recent campaign activity
        $recentActivity = RetellAICallCampaign::where('company_id', $companyId)
            ->whereIn('status', ['running', 'completed'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'progress' => $campaign->completion_percentage,
                    'calls_made' => $campaign->calls_completed + $campaign->calls_failed,
                    'total_targets' => $campaign->total_targets,
                    'last_update' => $campaign->updated_at->diffForHumans(),
                ];
            });

        // Campaign insights
        $insights = $this->generateInsights($completedCampaigns, $overallMetrics);

        return [
            'totalCampaigns' => $totalCampaigns,
            'activeCampaigns' => $activeCampaigns,
            'overallMetrics' => $overallMetrics,
            'topCampaigns' => $topCampaigns,
            'recentActivity' => $recentActivity,
            'insights' => $insights,
        ];
    }

    protected function generateInsights($completedCampaigns, $overallMetrics): array
    {
        $insights = [];

        // Success rate insight
        if ($overallMetrics['average_success_rate'] >= 80) {
            $insights[] = [
                'type' => 'success',
                'icon' => 'heroicon-o-check-circle',
                'message' => 'Excellent performance! Your campaigns have an average success rate of ' . $overallMetrics['average_success_rate'] . '%',
            ];
        } elseif ($overallMetrics['average_success_rate'] < 60) {
            $insights[] = [
                'type' => 'warning',
                'icon' => 'heroicon-o-exclamation-triangle',
                'message' => 'Campaign performance could be improved. Consider testing different agent configurations or call scripts.',
            ];
        }

        // Volume insight
        if ($overallMetrics['total_calls'] > 1000) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'heroicon-o-phone',
                'message' => 'You\'ve made ' . Number::format($overallMetrics['total_calls']) . ' calls through campaigns. Consider batch processing for better performance.',
            ];
        }

        // Best time insights
        $callsByHour = \App\Models\Call::where('company_id', auth()->user()->company_id)
            ->where('direction', 'outbound')
            ->where('status', 'completed')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->first();

        if ($callsByHour) {
            $hour = $callsByHour->hour;
            $timeRange = sprintf('%02d:00-%02d:00', $hour, ($hour + 1) % 24);
            $insights[] = [
                'type' => 'info',
                'icon' => 'heroicon-o-clock',
                'message' => "Best call success rate during {$timeRange}. Schedule campaigns during this time for better results.",
            ];
        }

        return $insights;
    }
}
