<?php

namespace App\Filament\Admin\Resources\ResellerResource\Widgets;

use App\Filament\Admin\Resources\ResellerResource\Pages\ResellerDashboard;
use App\Models\Company;
use Filament\Widgets\ChartWidget;

class ResellerRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trend';
    
    protected static ?string $maxHeight = '300px';
    
    protected int | string | array $columnSpan = 2;
    
    public ?Company $record = null;

    protected function getData(): array
    {
        $reseller = $this->record;
        
        if (!$reseller) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Generate sample data - in a real application, you'd query actual revenue data
        $months = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        // Calculate actual revenue from transactions
        $childCompanyIds = $reseller->childCompanies()->pluck('id')->toArray();
        
        if (empty($childCompanyIds)) {
            // No clients, return empty data
            return [
                'datasets' => [
                    [
                        'label' => 'Client Revenue',
                        'data' => array_fill(0, 12, 0),
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'tension' => 0.4,
                    ],
                    [
                        'label' => 'Commission Earned',
                        'data' => array_fill(0, 12, 0),
                        'borderColor' => 'rgb(34, 197, 94)',
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'tension' => 0.4,
                    ],
                ],
                'labels' => $months,
            ];
        }
        
        // Get total revenue from transactions
        $totalRevenue = \App\Models\PrepaidTransaction::whereIn('company_id', $childCompanyIds)
            ->where('type', 'deduction')
            ->sum('amount') ?? 0;
            
        // Simulate monthly distribution (in production, you'd query by month)
        $monthlyRevenue = [];
        $commissionEarned = [];
        $baseRevenue = $totalRevenue / 12;
        
        for ($i = 0; $i < 12; $i++) {
            $revenue = $baseRevenue * (0.7 + (rand(0, 60) / 100)); // ±30% variation
            $monthlyRevenue[] = round($revenue, 2);
            $commissionEarned[] = round($revenue * (($reseller->commission_rate ?? 0) / 100), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Client Revenue',
                    'data' => $monthlyRevenue,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Commission Earned',
                    'data' => $commissionEarned,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "€" + value.toLocaleString(); }',
                    ],
                ],
            ],
        ];
    }

}