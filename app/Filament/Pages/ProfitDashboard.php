<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Models\Call;
use App\Models\Company;
use App\Services\CostCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

class ProfitDashboard extends Page
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.profit-dashboard';
    protected static ?string $title = 'Profit-Dashboard';
    protected static ?string $navigationLabel = 'Profit-Dashboard 💰';

    public function mount(): void
    {
        // Check permissions - Redirect customers
        $user = auth()->user();
        if (!$user || (!$user->hasRole(['super-admin', 'super_admin', 'Super Admin']) &&
                      !$user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']))) {

            Notification::make()
                ->title('Zugriff verweigert')
                ->body('Sie haben keine Berechtigung, das Profit-Dashboard zu sehen.')
                ->danger()
                ->send();

            redirect()->to('/admin');
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Only Super-Admin and Reseller roles can access
        return $user->hasRole(['super-admin', 'super_admin', 'Super Admin']) ||
               $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole(['super-admin', 'super_admin', 'Super Admin']);
        $isReseller = $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']);

        // Cache key based on user role and ID
        $cacheKey = 'profit-dashboard-' . ($isSuperAdmin ? 'super' : 'reseller') . '-' . $user->id;
        $cacheDuration = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheDuration, function () use ($user, $isSuperAdmin, $isReseller) {
            $data = [
                'isSuperAdmin' => $isSuperAdmin,
                'isReseller' => $isReseller,
                'stats' => $this->calculateStats($user, $isSuperAdmin, $isReseller),
                'chartData' => $this->getChartData($user, $isSuperAdmin, $isReseller),
                'topPerformers' => $this->getTopPerformers($user, $isSuperAdmin, $isReseller),
                'profitTrends' => $this->getProfitTrends($user, $isSuperAdmin, $isReseller),
                'alerts' => $this->getProfitAlerts($user, $isSuperAdmin, $isReseller),
            ];

            return $data;
        });
    }

    private function calculateStats($user, $isSuperAdmin, $isReseller): array
    {
        $calculator = new CostCalculator();

        // Base query
        $query = Call::query();

        // Filter based on role
        if ($isReseller && !$isSuperAdmin) {
            // Reseller sees only their customers' calls
            $query->whereHas('company', function ($q) use ($user) {
                $q->where('parent_company_id', $user->company_id);
            });
        }
        // Super admin sees all

        // Today's stats
        $todayCalls = (clone $query)->whereDate('created_at', today())->get();
        $todayProfit = 0;
        $todayPlatformProfit = 0;
        $todayResellerProfit = 0;

        foreach ($todayCalls as $call) {
            $profitData = $calculator->getDisplayProfit($call, $user);
            if ($profitData['type'] !== 'none') {
                $todayProfit += $profitData['profit'];

                if ($isSuperAdmin && isset($profitData['breakdown'])) {
                    $todayPlatformProfit += $profitData['breakdown']['platform'];
                    $todayResellerProfit += $profitData['breakdown']['reseller'];
                }
            }
        }

        // This month's stats
        $monthCalls = (clone $query)->whereMonth('created_at', now()->month)
                                   ->whereYear('created_at', now()->year)
                                   ->get();
        $monthProfit = 0;
        foreach ($monthCalls as $call) {
            $profitData = $calculator->getDisplayProfit($call, $user);
            if ($profitData['type'] !== 'none') {
                $monthProfit += $profitData['profit'];
            }
        }

        // Calculate average margin
        $avgMargin = 0;
        if ($todayCalls->count() > 0) {
            $totalMargin = $todayCalls->sum('profit_margin_total');
            $avgMargin = round($totalMargin / $todayCalls->count(), 2);
        }

        return [
            'todayProfit' => $todayProfit,
            'todayPlatformProfit' => $todayPlatformProfit,
            'todayResellerProfit' => $todayResellerProfit,
            'monthProfit' => $monthProfit,
            'todayCallCount' => $todayCalls->count(),
            'monthCallCount' => $monthCalls->count(),
            'avgMargin' => $avgMargin,
            'currency' => '€',
        ];
    }

    private function getChartData($user, $isSuperAdmin, $isReseller): array
    {
        $calculator = new CostCalculator();
        $days = 30; // Last 30 days
        $chartData = [
            'labels' => [],
            'totalProfit' => [],
            'platformProfit' => [],
            'resellerProfit' => [],
        ];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chartData['labels'][] = $date->format('d.m');

            // Get calls for this day
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

            $chartData['totalProfit'][] = round($dayProfit / 100, 2);
            $chartData['platformProfit'][] = round($dayPlatformProfit / 100, 2);
            $chartData['resellerProfit'][] = round($dayResellerProfit / 100, 2);
        }

        return $chartData;
    }

    private function getTopPerformers($user, $isSuperAdmin, $isReseller): array
    {
        if ($isSuperAdmin) {
            // Top profitable companies
            $companies = Company::withSum(['calls' => function ($query) {
                $query->whereNotNull('total_profit');
            }], 'total_profit')
            ->orderByDesc('calls_sum_total_profit')
            ->limit(10)
            ->get();

            return $companies->map(function ($company) {
                return [
                    'name' => $company->name,
                    'profit' => $company->calls_sum_total_profit ?? 0,
                    'type' => $company->company_type,
                ];
            })->toArray();
        } elseif ($isReseller) {
            // Top profitable customers for reseller
            $companies = Company::where('parent_company_id', $user->company_id)
                ->withSum(['calls' => function ($query) {
                    $query->whereNotNull('reseller_profit');
                }], 'reseller_profit')
                ->orderByDesc('calls_sum_reseller_profit')
                ->limit(5)
                ->get();

            return $companies->map(function ($company) {
                return [
                    'name' => $company->name,
                    'profit' => $company->calls_sum_reseller_profit ?? 0,
                    'type' => 'customer',
                ];
            })->toArray();
        }

        return [];
    }

    private function getProfitTrends($user, $isSuperAdmin, $isReseller): array
    {
        // Compare current period with previous period
        $currentPeriodStart = now()->subDays(30);
        $previousPeriodStart = now()->subDays(60);
        $previousPeriodEnd = now()->subDays(31);

        $query = Call::query();
        if ($isReseller && !$isSuperAdmin) {
            $query->whereHas('company', function ($q) use ($user) {
                $q->where('parent_company_id', $user->company_id);
            });
        }

        // Current period profit
        $currentProfit = (clone $query)
            ->whereBetween('created_at', [$currentPeriodStart, now()])
            ->sum('total_profit');

        // Previous period profit
        $previousProfit = (clone $query)
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->sum('total_profit');

        $trend = 0;
        if ($previousProfit > 0) {
            $trend = round((($currentProfit - $previousProfit) / $previousProfit) * 100, 2);
        }

        return [
            'current' => $currentProfit,
            'previous' => $previousProfit,
            'trend' => $trend,
            'trendDirection' => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'stable'),
        ];
    }

    private function getProfitAlerts($user, $isSuperAdmin, $isReseller): array
    {
        $alerts = [];
        $query = Call::whereDate('created_at', today());

        if ($isReseller && !$isSuperAdmin) {
            $query->whereHas('company', function ($q) use ($user) {
                $q->where('parent_company_id', $user->company_id);
            });
        }

        // Check for low margin calls
        $lowMarginCalls = (clone $query)
            ->where('profit_margin_total', '<', 20)
            ->where('profit_margin_total', '>', 0)
            ->count();

        if ($lowMarginCalls > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "$lowMarginCalls Anrufe heute mit niedriger Marge (<20%)",
                'icon' => '⚠️',
            ];
        }

        // Check for negative profit
        $negativeProfit = (clone $query)
            ->where('total_profit', '<', 0)
            ->count();

        if ($negativeProfit > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "$negativeProfit Anrufe mit negativem Profit!",
                'icon' => '🔴',
            ];
        }

        // Check for high performers
        $highMarginCalls = (clone $query)
            ->where('profit_margin_total', '>', 50)
            ->count();

        if ($highMarginCalls > 0) {
            $alerts[] = [
                'type' => 'success',
                'message' => "$highMarginCalls Anrufe mit hoher Marge (>50%)",
                'icon' => '✅',
            ];
        }

        return $alerts;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ProfitOverviewWidget::class,
        ];
    }
}