<?php

namespace App\Filament\Admin\Resources\PrepaidBalanceResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\PrepaidBalance;
use App\Models\BalanceTransaction;
use Illuminate\Support\Facades\DB;

class PrepaidBalanceStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalBalance = PrepaidBalance::sum('balance');
        $totalReserved = PrepaidBalance::sum('reserved_balance');
        $companiesWithLowBalance = PrepaidBalance::whereRaw('balance - reserved_balance < low_balance_threshold')->count();
        
        // Monthly revenue
        $monthlyRevenue = BalanceTransaction::where('type', 'credit')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
            
        // Monthly usage
        $monthlyUsage = BalanceTransaction::where('type', 'debit')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        
        return [
            Stat::make('Gesamtguthaben', number_format($totalBalance, 2, ',', '.') . ' €')
                ->description(number_format($totalReserved, 2, ',', '.') . ' € reserviert')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('success'),
                
            Stat::make('Aufladungen (Monat)', number_format($monthlyRevenue, 2, ',', '.') . ' €')
                ->description('Einnahmen diesen Monat')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Verbrauch (Monat)', number_format($monthlyUsage, 2, ',', '.') . ' €')
                ->description('Nutzung diesen Monat')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),
                
            Stat::make('Niedriges Guthaben', $companiesWithLowBalance)
                ->description('Firmen unter Warnschwelle')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($companiesWithLowBalance > 0 ? 'danger' : 'success'),
        ];
    }
}