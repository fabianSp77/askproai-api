<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use App\Models\Company;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class MultiCompanyOverviewWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.multi-company-overview-widget';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    public function mount(): void
    {
        // Initialize widget
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && $user->hasRole('Super Admin');
    }

    public function getCompaniesData(): array
    {
        // Get top 5 most active companies
        $companies = Company::with(['prepaidBalance', 'portalUsers', 'branches'])
            ->withCount([
                'calls as calls_today' => function ($query) {
                    $query->whereDate('created_at', today());
                },
                'appointments as appointments_today' => function ($query) {
                    $query->whereDate('created_at', today());
                },
            ])
            ->orderByDesc('calls_today')
            ->limit(5)
            ->get();

        return $companies->map(function ($company) {
            $balance = $company->prepaidBalance;

            // Get monthly stats
            $monthlyStats = DB::table('calls')
                ->where('company_id', $company->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->selectRaw('COUNT(*) as total_calls, SUM(duration_sec) / 60 as total_minutes')
                ->first();

            return [
                'id' => $company->id,
                'name' => $company->name,
                'balance' => $balance?->getEffectiveBalance() ?? 0,
                'is_low_balance' => $balance && $balance->isLowBalance(),
                'portal_users' => $company->portalUsers->count(),
                'branches' => $company->branches->count(),
                'calls_today' => $company->calls_today,
                'appointments_today' => $company->appointments_today,
                'monthly_calls' => $monthlyStats->total_calls ?? 0,
                'monthly_minutes' => round($monthlyStats->total_minutes ?? 0, 0),
            ];
        })->toArray();
    }

    public function getTotalStats(): array
    {
        return [
            'total_companies' => Company::count(),
            'active_today' => Company::whereHas('calls', function ($query) {
                $query->whereDate('created_at', today());
            })->count(),
            'total_revenue_today' => DB::table('balance_transactions')
                ->whereDate('created_at', today())
                ->where('type', 'debit')
                ->sum('amount'),
            'total_calls_today' => Call::whereDate('created_at', today())->count(),
        ];
    }
}
