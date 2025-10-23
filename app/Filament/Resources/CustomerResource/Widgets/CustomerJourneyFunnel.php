<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Customer;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class CustomerJourneyFunnel extends Widget
{
    protected static string $view = 'filament.resources.customer-resource.widgets.customer-journey-funnel';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $data = Cache::remember('customer-journey-funnel-' . now()->format('Y-m-d'), 600, function () {
            return $this->calculateFunnelData();
        });

        return [
            'funnelData' => $data['funnel'],
            'conversionRates' => $data['conversions'],
            'totalCustomers' => $data['total'],
            'timeframes' => $data['timeframes'],
        ];
    }

    private function calculateFunnelData(): array
    {
        // Define journey stages in order
        $stages = [
            'lead' => ['label' => 'Leads', 'color' => '#94A3B8', 'icon' => '🌱'],
            'prospect' => ['label' => 'Interessenten', 'color' => '#60A5FA', 'icon' => '🔍'],
            'customer' => ['label' => 'Kunden', 'color' => '#34D399', 'icon' => '⭐'],
            'regular' => ['label' => 'Stammkunden', 'color' => '#A78BFA', 'icon' => '💎'],
            'vip' => ['label' => 'VIP', 'color' => '#FCD34D', 'icon' => '👑'],
        ];

        $total = Customer::count();

        // Get counts for each stage
        $stageCounts = Customer::selectRaw('journey_status, COUNT(*) as count')
            ->whereIn('journey_status', array_keys($stages))
            ->groupBy('journey_status')
            ->pluck('count', 'journey_status')
            ->toArray();

        // Calculate funnel data with percentages
        $funnelData = [];
        $previousCount = $total;

        foreach ($stages as $key => $stage) {
            $count = $stageCounts[$key] ?? 0;
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;

            $funnelData[] = [
                'stage' => $key,
                'label' => $stage['label'],
                'icon' => $stage['icon'],
                'count' => $count,
                'percentage' => $percentage,
                'color' => $stage['color'],
                'width' => max(20, $percentage), // Minimum width for visibility
            ];
        }

        // Calculate conversion rates between stages
        $conversions = [];
        $stageKeys = array_keys($stages);

        for ($i = 0; $i < count($stageKeys) - 1; $i++) {
            $currentCount = $stageCounts[$stageKeys[$i]] ?? 0;
            $nextCount = $stageCounts[$stageKeys[$i + 1]] ?? 0;

            $conversionRate = $currentCount > 0
                ? round(($nextCount / $currentCount) * 100, 1)
                : 0;

            $conversions[] = [
                'from' => $stages[$stageKeys[$i]]['label'],
                'to' => $stages[$stageKeys[$i + 1]]['label'],
                'rate' => $conversionRate,
                'color' => $conversionRate > 50 ? 'success' : ($conversionRate > 25 ? 'warning' : 'danger'),
            ];
        }

        // Calculate average time in each stage
        $timeframes = Customer::selectRaw("
            journey_status,
            AVG(DATEDIFF(NOW(), created_at)) as avg_days
        ")
            ->whereIn('journey_status', array_keys($stages))
            ->groupBy('journey_status')
            ->pluck('avg_days', 'journey_status')
            ->toArray();

        return [
            'funnel' => $funnelData,
            'conversions' => $conversions,
            'total' => $total,
            'timeframes' => array_map(fn($days) => round($days ?? 0), $timeframes),
        ];
    }

    public function getActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Aktualisieren')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->refreshFunnel()),
        ];
    }

    /**
     * Refresh funnel data
     * Extracted from closure for Livewire serialization
     */
    private function refreshFunnel(): void
    {
        Cache::forget('customer-journey-funnel-' . now()->format('Y-m-d'));
        $this->dispatch('$refresh');
    }
}