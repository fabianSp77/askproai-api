<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use App\Models\RetellAICallCampaign;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class AICallStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $companyId = auth()->user()->company_id;

        // Get outbound call stats for today
        $todayOutboundCalls = Call::where('company_id', $companyId)
            ->where('direction', 'outbound')
            ->whereDate('created_at', today())
            ->count();

        // Get total outbound calls this month
        $monthlyOutboundCalls = Call::where('company_id', $companyId)
            ->where('direction', 'outbound')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Get active campaigns
        $activeCampaigns = RetellAICallCampaign::where('company_id', $companyId)
            ->whereIn('status', ['running', 'scheduled'])
            ->count();

        // Calculate average success rate
        $avgSuccessRate = RetellAICallCampaign::where('company_id', $companyId)
            ->where('status', 'completed')
            ->where('calls_completed', '>', 0)
            ->selectRaw('AVG((calls_completed * 100.0) / (calls_completed + calls_failed)) as avg_rate')
            ->value('avg_rate') ?? 0;

        return [
            Stat::make('Today\'s Outbound Calls', Number::format($todayOutboundCalls))
                ->description($todayOutboundCalls > 0 ? '+' . $todayOutboundCalls . ' calls made today' : 'No calls made today')
                ->descriptionIcon($todayOutboundCalls > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
                ->color($todayOutboundCalls > 0 ? 'success' : 'gray')
                ->chart($this->getHourlyCallsChart()),

            Stat::make('Monthly Outbound Calls', Number::format($monthlyOutboundCalls))
                ->description('Total for ' . now()->format('F'))
                ->descriptionIcon('heroicon-m-phone-arrow-up-right')
                ->color('primary'),

            Stat::make('Active Campaigns', $activeCampaigns)
                ->description($activeCampaigns === 1 ? '1 campaign running' : $activeCampaigns . ' campaigns running')
                ->descriptionIcon('heroicon-m-play')
                ->color($activeCampaigns > 0 ? 'warning' : 'gray'),

            Stat::make('Avg. Success Rate', Number::percentage($avgSuccessRate))
                ->description('Across all campaigns')
                ->descriptionIcon($avgSuccessRate >= 80 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($avgSuccessRate >= 80 ? 'success' : ($avgSuccessRate >= 60 ? 'warning' : 'danger')),
        ];
    }

    protected function getHourlyCallsChart(): array
    {
        $calls = Call::where('company_id', auth()->user()->company_id)
            ->where('direction', 'outbound')
            ->whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $chart = array_fill(0, 24, 0);
        foreach ($calls as $hour => $count) {
            $chart[$hour] = $count;
        }

        // Return last 12 hours
        $currentHour = now()->hour;
        $last12Hours = [];
        for ($i = 11; $i >= 0; $i--) {
            $hour = ($currentHour - $i + 24) % 24;
            $last12Hours[] = $chart[$hour];
        }

        return $last12Hours;
    }
}
