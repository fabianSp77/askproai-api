<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Customer;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class CustomerInsightsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.customer-insights';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;
    
    public function getInsights(): array
    {
        return [
            'segments' => $this->getCustomerSegments(),
            'topCustomers' => $this->getTopCustomers(),
            'riskCustomers' => $this->getRiskCustomers(),
            'growthMetrics' => $this->getGrowthMetrics(),
        ];
    }
    
    private function getCustomerSegments(): array
    {
        $totalCustomers = Customer::count();
        
        return [
            [
                'label' => '👑 VIP Kunden',
                'count' => Customer::has('appointments', '>=', 10)->count(),
                'percentage' => $totalCustomers > 0 ? round((Customer::has('appointments', '>=', 10)->count() / $totalCustomers) * 100, 1) : 0,
                'color' => 'purple',
                'description' => '10+ Termine',
            ],
            [
                'label' => '⭐ Stammkunden',
                'count' => Customer::has('appointments', '>=', 3)->has('appointments', '<', 10)->count(),
                'percentage' => $totalCustomers > 0 ? round((Customer::has('appointments', '>=', 3)->has('appointments', '<', 10)->count() / $totalCustomers) * 100, 1) : 0,
                'color' => 'blue',
                'description' => '3-9 Termine',
            ],
            [
                'label' => '🌱 Neue Kunden',
                'count' => Customer::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
                'percentage' => $totalCustomers > 0 ? round((Customer::where('created_at', '>=', Carbon::now()->subDays(30))->count() / $totalCustomers) * 100, 1) : 0,
                'color' => 'green',
                'description' => 'Letzte 30 Tage',
            ],
            [
                'label' => '⚠️ Inaktive',
                'count' => Customer::whereDoesntHave('appointments', function ($query) {
                    $query->where('starts_at', '>=', Carbon::now()->subMonths(3));
                })->count(),
                'percentage' => $totalCustomers > 0 ? round((Customer::whereDoesntHave('appointments', function ($query) {
                    $query->where('starts_at', '>=', Carbon::now()->subMonths(3));
                })->count() / $totalCustomers) * 100, 1) : 0,
                'color' => 'yellow',
                'description' => '3+ Monate inaktiv',
            ],
        ];
    }
    
    private function getTopCustomers(): array
    {
        return Customer::select('customers.id', 'customers.name')
            ->selectRaw('COUNT(appointments.id) as appointments_count')
            ->selectRaw('SUM(services.price) as total_revenue')
            ->selectRaw('MAX(appointments.starts_at) as last_appointment')
            ->leftJoin('appointments', 'customers.id', '=', 'appointments.customer_id')
            ->leftJoin('services', 'appointments.service_id', '=', 'services.id')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'avatar' => null, // No avatar_url column exists
                    'appointments' => $customer->appointments_count ?? 0,
                    'revenue' => $customer->total_revenue ?? 0,
                    'last_seen' => $customer->last_appointment ? Carbon::parse($customer->last_appointment)->diffForHumans() : 'Nie',
                    'tags' => $this->getCustomerTags($customer),
                ];
            })
            ->toArray();
    }
    
    private function getRiskCustomers(): array
    {
        // Customers with recent no-shows or cancellations
        return Customer::select('customers.id', 'customers.name')
            ->selectRaw('COUNT(CASE WHEN appointments.status = "no_show" THEN 1 END) as no_shows')
            ->selectRaw('COUNT(CASE WHEN appointments.status = "cancelled" THEN 1 END) as cancellations')
            ->selectRaw('MAX(appointments.starts_at) as last_appointment')
            ->leftJoin('appointments', 'customers.id', '=', 'appointments.customer_id')
            ->where('appointments.created_at', '>=', Carbon::now()->subMonths(3))
            ->groupBy('customers.id', 'customers.name')
            ->having('no_shows', '>', 0)
            ->orHaving('cancellations', '>', 2)
            ->orderByDesc('no_shows')
            ->limit(5)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'no_shows' => $customer->no_shows,
                    'cancellations' => $customer->cancellations,
                    'risk_level' => $this->calculateRiskLevel($customer->no_shows, $customer->cancellations),
                ];
            })
            ->toArray();
    }
    
    private function getGrowthMetrics(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth();
        
        return [
            'new_today' => Customer::whereDate('created_at', $today)->count(),
            'new_this_month' => Customer::where('created_at', '>=', $thisMonth)->count(),
            'growth_rate' => $this->calculateGrowthRate(),
            'churn_rate' => $this->calculateChurnRate(),
            'lifetime_value' => $this->calculateAverageLifetimeValue(),
        ];
    }
    
    private function getCustomerTags($customer): array
    {
        $tags = [];
        
        if ($customer->appointments_count >= 10) {
            $tags[] = ['label' => 'VIP', 'color' => 'purple'];
        }
        
        if ($customer->total_revenue >= 1000) {
            $tags[] = ['label' => 'High Value', 'color' => 'green'];
        }
        
        if ($customer->last_appointment && Carbon::parse($customer->last_appointment)->isToday()) {
            $tags[] = ['label' => 'Heute', 'color' => 'blue'];
        }
        
        return $tags;
    }
    
    private function calculateRiskLevel($noShows, $cancellations): array
    {
        $score = ($noShows * 3) + ($cancellations * 1);
        
        if ($score >= 5) {
            return ['level' => 'Hoch', 'color' => 'red'];
        } elseif ($score >= 3) {
            return ['level' => 'Mittel', 'color' => 'yellow'];
        } else {
            return ['level' => 'Niedrig', 'color' => 'green'];
        }
    }
    
    private function calculateGrowthRate(): float
    {
        $thisMonth = Customer::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        $lastMonth = Customer::whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();
        
        return $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;
    }
    
    private function calculateChurnRate(): float
    {
        $totalCustomers = Customer::where('created_at', '<=', Carbon::now()->subMonths(3))->count();
        $inactiveCustomers = Customer::where('created_at', '<=', Carbon::now()->subMonths(3))
            ->whereDoesntHave('appointments', function ($query) {
                $query->where('starts_at', '>=', Carbon::now()->subMonths(3));
            })->count();
        
        return $totalCustomers > 0 ? round(($inactiveCustomers / $totalCustomers) * 100, 1) : 0;
    }
    
    private function calculateAverageLifetimeValue(): float
    {
        return round(
            Appointment::join('services', 'appointments.service_id', '=', 'services.id')
                ->avg('services.price') * 
            Customer::has('appointments')->avg(
                DB::raw('(SELECT COUNT(*) FROM appointments WHERE customer_id = customers.id)')
            ), 2
        );
    }
}