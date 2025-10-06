<?php

namespace App\Filament\Widgets;

use App\Models\Call;
use App\Services\CostCalculator;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ProfitChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Profit-Entwicklung (30 Tage)';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';
    protected static ?string $maxHeight = '400px';

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->hasRole(['super-admin', 'super_admin', 'Super Admin']) ||
               $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']);
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole(['super-admin', 'super_admin', 'Super Admin']);
        $isReseller = $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']);

        $calculator = new CostCalculator();
        $cacheKey = 'profit-chart-' . ($isSuperAdmin ? 'super' : 'reseller') . '-' . $user->id;

        return Cache::remember($cacheKey, 300, function () use ($user, $isSuperAdmin, $isReseller, $calculator) {
            $days = 30;
            $labels = [];
            $totalProfitData = [];
            $platformProfitData = [];
            $resellerProfitData = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('d.m');

                $query = Call::whereDate('created_at', $date);

                if ($isReseller && !$isSuperAdmin) {
                    $query->whereHas('company', function ($q) use ($user) {
                        $q->where('parent_company_id', $user->company_id);
                    });
                }

                $calls = $query->get();
                $dayProfit = 0;
                $dayPlatformProfit = 0;
                $dayResellerProfit = 0;

                foreach ($calls as $call) {
                    $profitData = $calculator->getDisplayProfit($call, $user);
                    if ($profitData['type'] !== 'none') {
                        $dayProfit += $profitData['profit'];

                        if ($isSuperAdmin && isset($profitData['breakdown'])) {
                            $dayPlatformProfit += $profitData['breakdown']['platform'];
                            $dayResellerProfit += $profitData['breakdown']['reseller'];
                        }
                    }
                }

                $totalProfitData[] = round($dayProfit / 100, 2);
                $platformProfitData[] = round($dayPlatformProfit / 100, 2);
                $resellerProfitData[] = round($dayResellerProfit / 100, 2);
            }

            $datasets = [
                [
                    'label' => 'Gesamt-Profit',
                    'data' => $totalProfitData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ];

            if ($isSuperAdmin) {
                $datasets[] = [
                    'label' => 'Platform-Profit',
                    'data' => $platformProfitData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'tension' => 0.3,
                    'fill' => true,
                ];

                $datasets[] = [
                    'label' => 'Mandanten-Profit',
                    'data' => $resellerProfitData,
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'borderColor' => 'rgb(168, 85, 247)',
                    'tension' => 0.3,
                    'fill' => true,
                ];
            }

            return [
                'datasets' => $datasets,
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "
                            function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('de-DE', {
                                        style: 'currency',
                                        currency: 'EUR'
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        ",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "
                            function(value) {
                                return value.toFixed(2) + ' €';
                            }
                        ",
                    ],
                ],
            ],
        ];
    }
}