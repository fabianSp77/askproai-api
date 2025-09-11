<?php

namespace App\Filament\Reseller\Widgets;

use App\Models\Customer;
use App\Models\CommissionLedger;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ResellerStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $reseller = app('current_reseller');
        
        // Calculate current month metrics
        $currentMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');
        
        // Customer metrics
        $totalCustomers = Customer::where('reseller_id', $reseller->id)->count();
        $activeCustomers = Customer::where('reseller_id', $reseller->id)
            ->where('balance_cents', '>', 0)
            ->count();
        $newCustomersThisMonth = Customer::where('reseller_id', $reseller->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        // Revenue metrics (from customer transactions)
        $monthlyRevenue = Transaction::whereHas('tenant', function ($query) use ($reseller) {
                $query->where('parent_id', $reseller->id);
            })
            ->where('type', 'usage')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum(DB::raw('ABS(amount_cents)'));
        
        $lastMonthRevenue = Transaction::whereHas('tenant', function ($query) use ($reseller) {
                $query->where('parent_id', $reseller->id);
            })
            ->where('type', 'usage')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum(DB::raw('ABS(amount_cents)'));
        
        // Commission metrics
        $pendingCommission = CommissionLedger::where('reseller_id', $reseller->id)
            ->where('status', 'pending')
            ->sum('commission_cents');
        
        $monthlyCommission = CommissionLedger::where('reseller_id', $reseller->id)
            ->where('period', $currentMonth)
            ->sum('commission_cents');
        
        $lastMonthCommission = CommissionLedger::where('reseller_id', $reseller->id)
            ->where('period', $lastMonth)
            ->sum('commission_cents');
        
        // Calculate trends
        $revenueTrend = $lastMonthRevenue > 0 
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;
        
        $commissionTrend = $lastMonthCommission > 0
            ? round((($monthlyCommission - $lastMonthCommission) / $lastMonthCommission) * 100, 1)
            : 0;

        return [
            Stat::make('Aktive Kunden', $activeCustomers . ' / ' . $totalCustomers)
                ->description($newCustomersThisMonth . ' neue Kunden diesen Monat')
                ->descriptionIcon('heroicon-m-user-plus')
                ->chart([7, 4, 6, 8, 12, 15, 13, 16, 18])
                ->color('primary'),
            
            Stat::make('Monatsumsatz', $this->formatCurrency($monthlyRevenue))
                ->description(($revenueTrend >= 0 ? '+' : '') . $revenueTrend . '% zum Vormonat')
                ->descriptionIcon($revenueTrend >= 0 ? 'heroicon-m-trending-up' : 'heroicon-m-trending-down')
                ->chart([6500, 7200, 8100, 9300, 8900, 10200, 11500, $monthlyRevenue])
                ->color($revenueTrend >= 0 ? 'success' : 'danger'),
            
            Stat::make('Provision (Monat)', $this->formatCurrency($monthlyCommission))
                ->description(($commissionTrend >= 0 ? '+' : '') . $commissionTrend . '% zum Vormonat')
                ->descriptionIcon($commissionTrend >= 0 ? 'heroicon-m-trending-up' : 'heroicon-m-trending-down')
                ->chart([1625, 1800, 2025, 2325, 2225, 2550, 2875, $monthlyCommission])
                ->color('success'),
            
            Stat::make('Ausstehende Auszahlung', $this->formatCurrency($pendingCommission))
                ->description($pendingCommission >= 1000 ? 'Auszahlung möglich' : 'Min. 10€ für Auszahlung')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($pendingCommission >= 1000 ? 'warning' : 'gray')
                ->extraAttributes([
                    'class' => $pendingCommission >= 1000 ? 'ring-2 ring-warning-500' : '',
                ]),
        ];
    }

    protected function formatCurrency(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.') . ' €';
    }

    public static function canView(): bool
    {
        return true;
    }
}