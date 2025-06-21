<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CostAnalysisWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.cost-analysis';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
        'xl' => 1,
    ];
    
    public array $metrics = [];
    public array $breakdown = [];
    public string $period = 'month';
    
    public function mount(): void
    {
        $this->calculateMetrics();
    }
    
    public function calculateMetrics(): void
    {
        $this->metrics = Cache::remember("cost_analysis_{$this->period}", 300, function () {
            $dateRange = $this->getDateRange();
            
            // Calculate costs
            $totalCalls = Call::whereBetween('created_at', $dateRange)->count();
            $totalBookings = Appointment::whereBetween('created_at', $dateRange)->count();
            $newCustomers = Customer::whereBetween('created_at', $dateRange)->count();
            
            // Cost assumptions (would come from config/database in production)
            $costPerCall = 0.50; // €0.50 per AI call
            $marketingCostPerMonth = 5000; // Fixed marketing budget
            $platformCostPerMonth = 2000; // SaaS platform costs
            
            // Calculate revenue
            $revenue = Appointment::join('calcom_event_types', 'appointments.calcom_event_type_id', '=', 'calcom_event_types.id')
                ->whereBetween('appointments.starts_at', $dateRange)
                ->where('appointments.status', 'completed')
                ->sum('calcom_event_types.price');
            
            // Calculate metrics
            $totalCosts = ($totalCalls * $costPerCall) + $marketingCostPerMonth + $platformCostPerMonth;
            $costPerBooking = $totalBookings > 0 ? $totalCosts / $totalBookings : 0;
            $costPerCustomer = $newCustomers > 0 ? $totalCosts / $newCustomers : 0;
            $roi = $totalCosts > 0 ? (($revenue - $totalCosts) / $totalCosts) * 100 : 0;
            $margin = $revenue > 0 ? (($revenue - $totalCosts) / $revenue) * 100 : 0;
            
            // Cost breakdown
            $this->breakdown = [
                'ai_calls' => $totalCalls * $costPerCall,
                'marketing' => $marketingCostPerMonth,
                'platform' => $platformCostPerMonth,
                'other' => 500, // Misc costs
            ];
            
            return [
                'total_costs' => $totalCosts,
                'total_revenue' => $revenue,
                'profit' => $revenue - $totalCosts,
                'cost_per_booking' => $costPerBooking,
                'cost_per_customer' => $costPerCustomer,
                'roi' => $roi,
                'margin' => $margin,
                'total_bookings' => $totalBookings,
                'conversion_cost' => $totalBookings > 0 ? $totalCosts / $totalBookings : 0,
            ];
        });
    }
    
    private function getDateRange(): array
    {
        $end = Carbon::now();
        
        switch ($this->period) {
            case 'week':
                $start = $end->copy()->startOfWeek();
                break;
            case 'month':
                $start = $end->copy()->startOfMonth();
                break;
            case 'quarter':
                $start = $end->copy()->startOfQuarter();
                break;
            default:
                $start = $end->copy()->subMonth();
        }
        
        return [$start, $end];
    }
    
    public function updatedPeriod(): void
    {
        $this->calculateMetrics();
    }
    
    public function getCostBreakdownChartData(): array
    {
        return [
            'labels' => ['AI Calls', 'Marketing', 'Platform', 'Other'],
            'data' => array_values($this->breakdown),
            'backgroundColor' => [
                'rgb(59, 130, 246)',
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(156, 163, 175)',
            ],
        ];
    }
}