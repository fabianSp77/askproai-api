<?php

namespace App\Filament\Admin\Widgets;

use App\Models\BillingPeriod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class BillingPeriodSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected function getStats(): array
    {
        $currentPeriod = BillingPeriod::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
            
        $pendingInvoices = BillingPeriod::where('status', 'processed')
            ->where('is_invoiced', false)
            ->count();
            
        $currentMonthRevenue = BillingPeriod::whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->sum('total_revenue');
            
        $averageMargin = BillingPeriod::whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->avg('margin_percentage') ?? 0;
        
        return [
            Stat::make('Current Period', $currentPeriod ? $currentPeriod->start_date->format('F Y') : 'None')
                ->description($currentPeriod ? 
                    'Days remaining: ' . now()->diffInDays($currentPeriod->end_date) : 
                    'No active period'
                )
                ->icon('heroicon-o-calendar')
                ->color($currentPeriod ? 'primary' : 'gray'),
                
            Stat::make('Pending Invoices', $pendingInvoices)
                ->description('Ready to invoice')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-document-text')
                ->color($pendingInvoices > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.billing-periods.index', [
                    'tableFilters' => ['not_invoiced' => ['value' => true]]
                ])),
                
            Stat::make('Monthly Revenue', Number::currency($currentMonthRevenue, 'EUR'))
                ->description('Current month total')
                ->icon('heroicon-o-currency-euro')
                ->color('success')
                ->chart([
                    $this->getMonthlyRevenueChart()
                ]),
                
            Stat::make('Average Margin', Number::percentage($averageMargin))
                ->description($averageMargin >= 30 ? 'Healthy margin' : 'Below target')
                ->icon('heroicon-o-chart-bar')
                ->color($averageMargin >= 30 ? 'success' : 'warning'),
        ];
    }
    
    protected function getMonthlyRevenueChart(): array
    {
        return BillingPeriod::selectRaw('DAY(start_date) as day, SUM(total_revenue) as revenue')
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('revenue')
            ->toArray();
    }
    
    public static function canView(): bool
    {
        return auth()->user()->can('view_billing_periods');
    }
}