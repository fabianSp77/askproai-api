<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use App\Models\Customer;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerMetricsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.customer-metrics';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public array $customerData = [];

    public string $timeRange = '30days';

    public ?int $companyId = null;

    public ?string $branchId = null;

    public function mount(): void
    {
        $this->companyId = session('filter_company_id') ?? auth()->user()?->company_id;
        $this->branchId = session('filter_branch_id');
        $this->loadCustomerData();
    }

    public function setTimeRange(string $range): void
    {
        $this->timeRange = $range;
        $this->loadCustomerData();
    }

    protected function loadCustomerData(): void
    {
        $cacheKey = "customer_metrics_{$this->companyId}_{$this->branchId}_{$this->timeRange}";

        $this->customerData = Cache::remember($cacheKey, 300, function () {
            return [
                'overview' => $this->getCustomerOverview(),
                'acquisition' => $this->getCustomerAcquisition(),
                'retention' => $this->getRetentionMetrics(),
                'lifetime_value' => $this->getLifetimeValueMetrics(),
                'segments' => $this->getCustomerSegments(),
                'cross_branch' => $this->getCrossBranchActivity(),
                'top_customers' => $this->getTopCustomers(),
                'churn_risk' => $this->getChurnRiskCustomers(),
            ];
        });
    }

    protected function getCustomerOverview(): array
    {
        $endDate = Carbon::now();
        $startDate = $this->getStartDate();

        // Base query for customers
        $customerQuery = Customer::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, function ($q) {
                $q->whereHas('appointments', fn ($q) => $q->where('branch_id', $this->branchId));
            });

        // Total customers
        $totalCustomers = (clone $customerQuery)->count();

        // New customers in period
        $newCustomers = (clone $customerQuery)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Active customers (had appointment in period)
        $activeCustomers = (clone $customerQuery)
            ->whereHas('appointments', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('starts_at', [$startDate, $endDate])
                    ->where('status', 'completed');
            })
            ->count();

        // Returning customers
        $returningCustomers = (clone $customerQuery)
            ->whereHas('appointments', function ($q) use ($startDate) {
                $q->where('starts_at', '<', $startDate)
                    ->where('status', 'completed');
            })
            ->whereHas('appointments', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('starts_at', [$startDate, $endDate])
                    ->where('status', 'completed');
            })
            ->count();

        // Average appointments per customer
        $totalAppointments = Appointment::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn ($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        $avgAppointmentsPerCustomer = $activeCustomers > 0
            ? round($totalAppointments / $activeCustomers, 1)
            : 0;

        // Growth rate
        $previousPeriodCustomers = $this->getPreviousPeriodNewCustomers();
        $growthRate = $previousPeriodCustomers > 0
            ? round((($newCustomers - $previousPeriodCustomers) / $previousPeriodCustomers) * 100, 1)
            : 0;

        return [
            'total' => $totalCustomers,
            'new' => $newCustomers,
            'active' => $activeCustomers,
            'returning' => $returningCustomers,
            'avg_appointments' => $avgAppointmentsPerCustomer,
            'growth_rate' => $growthRate,
            'growth_direction' => $growthRate > 0 ? 'up' : ($growthRate < 0 ? 'down' : 'stable'),
            'retention_rate' => $activeCustomers > 0
                ? round(($returningCustomers / $activeCustomers) * 100, 1)
                : 0,
        ];
    }

    protected function getCustomerAcquisition(): array
    {
        $periods = $this->getPeriods();
        $acquisition = [];

        foreach ($periods as $period) {
            $newCustomers = Customer::query()
                ->when($this->companyId, function ($q) {
                    $q->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId));
                })
                ->when($this->branchId, function ($q) {
                    $q->whereHas('appointments', fn ($q) => $q->where('branch_id', $this->branchId));
                })
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->count();

            // Get acquisition cost (simplified - would need marketing spend data)
            $marketingCost = 500; // Placeholder
            $acquisitionCost = $newCustomers > 0 ? round($marketingCost / $newCustomers, 2) : 0;

            $acquisition[] = [
                'period' => $period['label'],
                'new_customers' => $newCustomers,
                'acquisition_cost' => $acquisitionCost,
            ];
        }

        return $acquisition;
    }

    protected function getRetentionMetrics(): array
    {
        // Cohort analysis - customers by month they joined
        $cohorts = [];

        for ($i = 5; $i >= 0; $i--) {
            $cohortStart = Carbon::now()->subMonths($i)->startOfMonth();
            $cohortEnd = Carbon::now()->subMonths($i)->endOfMonth();

            // Get customers who joined in this cohort
            $cohortCustomers = Customer::query()
                ->when($this->companyId, function ($q) {
                    $q->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId));
                })
                ->when($this->branchId, function ($q) {
                    $q->whereHas('appointments', fn ($q) => $q->where('branch_id', $this->branchId));
                })
                ->whereBetween('created_at', [$cohortStart, $cohortEnd])
                ->pluck('id');

            $cohortSize = $cohortCustomers->count();

            if ($cohortSize === 0) {
                continue;
            }

            $retention = [];

            // Check retention for each subsequent month
            for ($j = 0; $j <= $i; $j++) {
                $checkStart = $cohortStart->copy()->addMonths($j)->startOfMonth();
                $checkEnd = $cohortStart->copy()->addMonths($j)->endOfMonth();

                $retained = Customer::whereIn('id', $cohortCustomers)
                    ->whereHas('appointments', function ($q) use ($checkStart, $checkEnd) {
                        $q->whereBetween('starts_at', [$checkStart, $checkEnd])
                            ->where('status', 'completed');
                    })
                    ->count();

                $retention[] = round(($retained / $cohortSize) * 100, 1);
            }

            $cohorts[] = [
                'month' => $cohortStart->format('M Y'),
                'size' => $cohortSize,
                'retention' => $retention,
            ];
        }

        return $cohorts;
    }

    protected function getLifetimeValueMetrics(): array
    {
        // Calculate average customer lifetime value
        $customers = Customer::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, function ($q) {
                $q->whereHas('appointments', fn ($q) => $q->where('branch_id', $this->branchId));
            })
            ->with(['appointments' => function ($q) {
                $q->where('status', 'completed')
                    ->leftJoin('calcom_event_types', 'appointments.calcom_event_type_id', '=', 'calcom_event_types.id')
                    ->select('appointments.*', DB::raw('COALESCE(appointments.price, calcom_event_types.price, 0) as price'));
            }])
            ->limit(1000)
            ->get();

        $lifetimeValues = [];
        $totalValue = 0;
        $customerCount = 0;

        foreach ($customers as $customer) {
            $customerValue = $customer->appointments->sum('price');
            if ($customerValue > 0) {
                $lifetimeValues[] = $customerValue;
                $totalValue += $customerValue;
                $customerCount++;
            }
        }

        $avgLifetimeValue = $customerCount > 0 ? round($totalValue / $customerCount, 2) : 0;

        // Distribution
        $distribution = [
            '0-50' => 0,
            '50-200' => 0,
            '200-500' => 0,
            '500-1000' => 0,
            '1000+' => 0,
        ];

        foreach ($lifetimeValues as $value) {
            if ($value <= 50) {
                $distribution['0-50']++;
            } elseif ($value <= 200) {
                $distribution['50-200']++;
            } elseif ($value <= 500) {
                $distribution['200-500']++;
            } elseif ($value <= 1000) {
                $distribution['500-1000']++;
            } else {
                $distribution['1000+']++;
            }
        }

        return [
            'average' => $avgLifetimeValue,
            'total_value' => $totalValue,
            'distribution' => $distribution,
        ];
    }

    protected function getCustomerSegments(): array
    {
        $endDate = Carbon::now();

        // VIP customers (high value, frequent visits)
        $vipCustomers = Customer::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, function ($q) {
                $q->whereHas('appointments', fn ($q) => $q->where('branch_id', $this->branchId));
            })
            ->withCount(['appointments as appointment_count' => function ($q) {
                $q->where('status', 'completed');
            }])
            ->having('appointment_count', '>=', 10)
            ->count();

        // Regular customers (3-9 visits)
        $regularCustomers = Customer::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, function ($q) {
                $q->whereHas('appointments', fn ($q) => $q->where('branch_id', $this->branchId));
            })
            ->withCount(['appointments as appointment_count' => function ($q) {
                $q->where('status', 'completed');
            }])
            ->havingBetween('appointment_count', [3, 9])
            ->count();

        // Occasional customers (1-2 visits)
        $occasionalCustomers = Customer::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, function ($q) {
                $q->whereHas('appointments', fn ($q) => $q->where('branch_id', $this->branchId));
            })
            ->withCount(['appointments as appointment_count' => function ($q) {
                $q->where('status', 'completed');
            }])
            ->havingBetween('appointment_count', [1, 2])
            ->count();

        // At-risk customers (no visit in 60+ days)
        $atRiskCustomers = Customer::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, function ($q) {
                $q->whereHas('appointments', fn ($q) => $q->where('branch_id', $this->branchId));
            })
            ->whereHas('appointments', function ($q) {
                $q->where('status', 'completed')
                    ->where('starts_at', '<', Carbon::now()->subDays(60));
            })
            ->whereDoesntHave('appointments', function ($q) {
                $q->where('status', 'completed')
                    ->where('starts_at', '>=', Carbon::now()->subDays(60));
            })
            ->count();

        return [
            'vip' => $vipCustomers,
            'regular' => $regularCustomers,
            'occasional' => $occasionalCustomers,
            'at_risk' => $atRiskCustomers,
        ];
    }

    protected function getCrossBranchActivity(): array
    {
        if (! $this->companyId) {
            return [];
        }

        // Customers who visited multiple branches
        $crossBranchCustomers = Customer::query()
            ->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId))
            ->withCount(['appointments as branch_count' => function ($q) {
                $q->select(DB::raw('COUNT(DISTINCT branch_id)'));
            }])
            ->having('branch_count', '>', 1)
            ->get();

        $crossBranchData = [];

        foreach ($crossBranchCustomers as $customer) {
            $branches = $customer->appointments()
                ->with('branch')
                ->select('branch_id', DB::raw('COUNT(*) as visit_count'))
                ->groupBy('branch_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'branch_name' => $item->branch->name ?? 'Unknown',
                        'visits' => $item->visit_count,
                    ];
                });

            $crossBranchData[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'branches_visited' => $branches->count(),
                'total_visits' => $branches->sum('visits'),
                'branches' => $branches,
            ];
        }

        return [
            'total_cross_branch_customers' => count($crossBranchData),
            'percentage' => $this->customerData['overview']['total'] > 0
                ? round((count($crossBranchData) / $this->customerData['overview']['total']) * 100, 1)
                : 0,
            'customers' => array_slice($crossBranchData, 0, 10), // Top 10
        ];
    }

    protected function getTopCustomers(): array
    {
        return Customer::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, function ($q) {
                $q->whereHas('appointments', fn ($q) => $q->where('branch_id', $this->branchId));
            })
            ->withCount(['appointments as appointment_count' => function ($q) {
                $q->where('status', 'completed');
            }])
            ->with(['appointments' => function ($q) {
                $q->where('status', 'completed')
                    ->leftJoin('calcom_event_types', 'appointments.calcom_event_type_id', '=', 'calcom_event_types.id')
                    ->select('appointments.*', DB::raw('COALESCE(appointments.price, calcom_event_types.price, 0) as price'));
            }])
            ->having('appointment_count', '>', 0)
            ->orderByDesc('appointment_count')
            ->limit(10)
            ->get()
            ->map(function ($customer) {
                $totalRevenue = $customer->appointments->sum('price');
                $lastVisit = $customer->appointments->max('starts_at');

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'appointments' => $customer->appointment_count,
                    'total_revenue' => $totalRevenue,
                    'avg_revenue' => $customer->appointment_count > 0
                        ? round($totalRevenue / $customer->appointment_count, 2)
                        : 0,
                    'last_visit' => $lastVisit ? Carbon::parse($lastVisit)->diffForHumans() : 'Never',
                    'days_since_last_visit' => $lastVisit
                        ? Carbon::parse($lastVisit)->diffInDays(now())
                        : null,
                ];
            })
            ->toArray();
    }

    protected function getChurnRiskCustomers(): array
    {
        // Customers at risk of churning (no appointment in 60-90 days)
        return Customer::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, function ($q) {
                $q->whereHas('appointments', fn ($q) => $q->where('branch_id', $this->branchId));
            })
            ->whereHas('appointments', function ($q) {
                $q->where('status', 'completed')
                    ->whereBetween('starts_at', [Carbon::now()->subDays(90), Carbon::now()->subDays(60)]);
            })
            ->whereDoesntHave('appointments', function ($q) {
                $q->where('starts_at', '>=', Carbon::now()->subDays(60));
            })
            ->withCount(['appointments as total_appointments' => function ($q) {
                $q->where('status', 'completed');
            }])
            ->with(['appointments' => function ($q) {
                $q->where('status', 'completed')
                    ->latest('starts_at')
                    ->limit(1);
            }])
            ->orderByDesc('total_appointments')
            ->limit(20)
            ->get()
            ->map(function ($customer) {
                $lastAppointment = $customer->appointments->first();

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'total_appointments' => $customer->total_appointments,
                    'last_visit' => $lastAppointment
                        ? Carbon::parse($lastAppointment->starts_at)->format('d.m.Y')
                        : 'Unknown',
                    'days_inactive' => $lastAppointment
                        ? Carbon::parse($lastAppointment->starts_at)->diffInDays(now())
                        : null,
                    'risk_level' => $customer->total_appointments >= 5 ? 'high' : 'medium',
                ];
            })
            ->toArray();
    }

    protected function getStartDate(): Carbon
    {
        switch ($this->timeRange) {
            case '7days':
                return Carbon::now()->subDays(7);
            case '30days':
                return Carbon::now()->subDays(30);
            case '90days':
                return Carbon::now()->subDays(90);
            case '1year':
                return Carbon::now()->subYear();
            default:
                return Carbon::now()->subDays(30);
        }
    }

    protected function getPeriods(): array
    {
        $periods = [];

        switch ($this->timeRange) {
            case '7days':
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $periods[] = [
                        'start' => $date->copy()->startOfDay(),
                        'end' => $date->copy()->endOfDay(),
                        'label' => $date->format('D'),
                    ];
                }

                break;
            case '30days':
                for ($i = 4; $i >= 0; $i--) {
                    $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
                    $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
                    $periods[] = [
                        'start' => $weekStart,
                        'end' => $weekEnd,
                        'label' => 'W' . $weekStart->weekOfYear,
                    ];
                }

                break;
            case '90days':
            case '1year':
                $months = $this->timeRange === '90days' ? 3 : 12;
                for ($i = $months - 1; $i >= 0; $i--) {
                    $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                    $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                    $periods[] = [
                        'start' => $monthStart,
                        'end' => $monthEnd,
                        'label' => $monthStart->format('M'),
                    ];
                }

                break;
        }

        return $periods;
    }

    protected function getPreviousPeriodNewCustomers(): int
    {
        $currentStart = $this->getStartDate();
        $currentEnd = Carbon::now();
        $periodLength = $currentStart->diffInDays($currentEnd);

        $previousStart = $currentStart->copy()->subDays($periodLength);
        $previousEnd = $currentStart->copy()->subDay();

        return Customer::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('appointments.branch', fn ($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, function ($q) {
                $q->whereHas('appointments', fn ($q) => $q->where('branch_id', $this->branchId));
            })
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();
    }

    public function getTimeRangeOptions(): array
    {
        return [
            '7days' => 'Last 7 Days',
            '30days' => 'Last 30 Days',
            '90days' => 'Last 90 Days',
            '1year' => 'Last Year',
        ];
    }

    public static function canView(): bool
    {
        return true;
    }
}
