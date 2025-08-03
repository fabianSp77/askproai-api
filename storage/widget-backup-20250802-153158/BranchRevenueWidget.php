<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use App\Models\Branch;
use Carbon\Carbon;

class BranchRevenueWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.branch-revenue-widget';
    protected int | string | array $columnSpan = 2;
    protected static ?int $sort = 5;

    public ?int $companyId = null;
    public ?int $selectedBranchId = null;

    protected function getListeners(): array
    {
        return [
            'tenantFilterUpdated' => 'handleTenantFilterUpdate',
        ];
    }

    public function handleTenantFilterUpdate($companyId, $branchId): void
    {
        $this->companyId = $companyId;
        $this->selectedBranchId = $branchId;
    }

    protected function getViewData(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        return [
            'branches' => $this->getBranchRevenue($startOfMonth, $endOfMonth),
            'totalRevenue' => $this->getTotalRevenue($startOfMonth, $endOfMonth),
            'period' => $startOfMonth->format('F Y'),
            'chartData' => $this->getChartData(),
        ];
    }

    protected function getBranchRevenue($startDate, $endDate)
    {
        $query = Branch::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('id', $this->selectedBranchId))
            ->withSum(['appointments' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('starts_at', [$startDate, $endDate])
                    ->where('status', 'completed');
            }], 'price')
            ->withCount(['appointments' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('starts_at', [$startDate, $endDate])
                    ->where('status', 'completed');
            }])
            ->orderBy('appointments_sum_price', 'desc')
            ->limit(5)
            ->get();

        $maxRevenue = $query->max('appointments_sum_price') ?: 1;

        return $query->map(function ($branch) use ($maxRevenue) {
            $revenue = $branch->appointments_sum_price ?: 0;
            return [
                'name' => $branch->name,
                'revenue' => $revenue,
                'appointments_count' => $branch->appointments_count ?: 0,
                'percentage' => $maxRevenue > 0 ? round(($revenue / $maxRevenue) * 100) : 0,
                'avg_per_appointment' => $branch->appointments_count > 0 
                    ? round($revenue / $branch->appointments_count, 2) 
                    : 0,
            ];
        });
    }

    protected function getTotalRevenue($startDate, $endDate): float
    {
        return Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('price');
    }

    protected function getChartData(): array
    {
        $days = [];
        $revenues = [];
        
        // Last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days[] = $date->format('d.m');
            
            $revenue = Appointment::query()
                ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
                ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
                ->whereDate('starts_at', $date)
                ->where('status', 'completed')
                ->sum('price');
                
            $revenues[] = $revenue;
        }
        
        return [
            'labels' => $days,
            'data' => $revenues,
        ];
    }
}