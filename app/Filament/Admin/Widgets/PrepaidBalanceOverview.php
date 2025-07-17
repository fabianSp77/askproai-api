<?php

namespace App\Filament\Admin\Widgets;

use App\Models\PrepaidBalance;
use App\Models\BalanceTransaction;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrepaidBalanceOverview extends Widget
{
    protected static string $view = 'filament.admin.widgets.prepaid-balance-overview';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;
    
    protected static bool $isLazy = false;
    
    public function getDisplayName(): string
    {
        return 'Prepaid Guthaben Übersicht';
    }
    
    protected function getViewData(): array
    {
        $now = Carbon::now();
        
        // Gesamtstatistiken
        $totalBalance = PrepaidBalance::sum('balance');
        $totalReserved = PrepaidBalance::sum('reserved_balance');
        $companiesWithLowBalance = PrepaidBalance::whereRaw('balance - reserved_balance < low_balance_threshold')->count();
        $autoTopupEnabled = PrepaidBalance::where('auto_topup_enabled', true)->count();
        
        // Transaktionen heute
        $todayCredits = BalanceTransaction::whereDate('created_at', $now->toDateString())
            ->where('type', 'credit')
            ->sum('amount');
            
        $todayDebits = BalanceTransaction::whereDate('created_at', $now->toDateString())
            ->where('type', 'debit')
            ->sum('amount');
        
        // Top 5 Verbraucher diesen Monat
        $topConsumers = DB::table('balance_transactions')
            ->join('companies', 'balance_transactions.company_id', '=', 'companies.id')
            ->where('balance_transactions.type', 'debit')
            ->whereMonth('balance_transactions.created_at', $now->month)
            ->whereYear('balance_transactions.created_at', $now->year)
            ->select(
                'companies.name as company_name',
                'companies.id as company_id',
                DB::raw('SUM(balance_transactions.amount) as total_consumption')
            )
            ->groupBy('companies.id', 'companies.name')
            ->orderByDesc('total_consumption')
            ->limit(5)
            ->get();
        
        // Kritische Guthaben (< 10€)
        $criticalBalances = PrepaidBalance::with('company')
            ->whereRaw('balance - reserved_balance < 10')
            ->orderBy('balance')
            ->limit(5)
            ->get();
        
        return [
            'totalBalance' => $totalBalance,
            'totalReserved' => $totalReserved,
            'companiesWithLowBalance' => $companiesWithLowBalance,
            'autoTopupEnabled' => $autoTopupEnabled,
            'todayCredits' => $todayCredits,
            'todayDebits' => $todayDebits,
            'topConsumers' => $topConsumers,
            'criticalBalances' => $criticalBalances,
        ];
    }
}