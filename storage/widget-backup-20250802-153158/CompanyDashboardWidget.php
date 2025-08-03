<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Appointment;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CompanyDashboardWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.company-dashboard';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;
    
    public function getDashboardData(): array
    {
        $companies = Company::with(['branches', 'staff', 'services'])->get();
        
        return [
            'overview' => $this->getOverviewStats($companies),
            'topCompanies' => $this->getTopCompanies(),
            'growthMetrics' => $this->getGrowthMetrics(),
            'integrationStatus' => $this->getIntegrationStatus($companies),
        ];
    }
    
    private function getOverviewStats($companies): array
    {
        return [
            'total_companies' => $companies->count(),
            'active_companies' => $companies->where('is_active', true)->count(),
            'total_branches' => Branch::count(),
            'total_staff' => Staff::count(),
            'total_services' => Service::count(),
            'total_appointments' => Appointment::whereIn('branch_id', Branch::pluck('id'))->count(),
        ];
    }
    
    private function getTopCompanies(): array
    {
        return Company::select(
                'companies.id',
                'companies.name',
                'companies.is_active',
                'companies.created_at'
            )
            ->selectRaw('COUNT(DISTINCT branches.id) as branch_count')
            ->selectRaw('COUNT(DISTINCT staff.id) as staff_count')
            ->selectRaw('COUNT(DISTINCT appointments.id) as appointment_count')
            ->leftJoin('branches', 'companies.id', '=', 'branches.company_id')
            ->leftJoin('staff', 'companies.id', '=', 'staff.company_id')
            ->leftJoin('appointments', 'branches.id', '=', 'appointments.branch_id')
            ->groupBy('companies.id', 'companies.name', 'companies.is_active', 'companies.created_at')
            ->orderByDesc('appointment_count')
            ->limit(5)
            ->get()
            ->map(function ($company) {
                $revenue = Appointment::join('branches', 'appointments.branch_id', '=', 'branches.id')
                    ->join('services', 'appointments.service_id', '=', 'services.id')
                    ->where('branches.company_id', $company->id)
                    ->where('appointments.status', 'completed')
                    ->sum('services.price');
                    
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'branches' => $company->branch_count,
                    'staff' => $company->staff_count,
                    'appointments' => $company->appointment_count,
                    'revenue' => $revenue,
                    'is_active' => $company->is_active,
                    'health_score' => $this->calculateHealthScore($company),
                ];
            })
            ->toArray();
    }
    
    private function getGrowthMetrics(): array
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        $companiesThisMonth = Company::where('created_at', '>=', $thisMonth)->count();
        $companiesLastMonth = Company::whereBetween('created_at', [$lastMonth, $thisMonth])->count();
        
        $appointmentsThisMonth = Appointment::where('created_at', '>=', $thisMonth)->count();
        $appointmentsLastMonth = Appointment::whereBetween('created_at', [$lastMonth, $thisMonth])->count();
        
        return [
            'company_growth' => $companiesLastMonth > 0 ? 
                round((($companiesThisMonth - $companiesLastMonth) / $companiesLastMonth) * 100, 1) : 0,
            'appointment_growth' => $appointmentsLastMonth > 0 ?
                round((($appointmentsThisMonth - $appointmentsLastMonth) / $appointmentsLastMonth) * 100, 1) : 0,
            'new_companies_today' => Company::whereDate('created_at', Carbon::today())->count(),
            'new_companies_this_month' => $companiesThisMonth,
            'avg_branches_per_company' => Company::has('branches')->avg(
                DB::raw('(SELECT COUNT(*) FROM branches WHERE company_id = companies.id)')
            ) ?? 0,
            'avg_staff_per_company' => Company::has('staff')->avg(
                DB::raw('(SELECT COUNT(*) FROM staff WHERE company_id = companies.id)')
            ) ?? 0,
        ];
    }
    
    private function getIntegrationStatus($companies): array
    {
        $totalCompanies = $companies->count();
        
        return [
            'calcom' => [
                'connected' => $companies->whereNotNull('calcom_api_key')->count(),
                'percentage' => $totalCompanies > 0 ? 
                    round(($companies->whereNotNull('calcom_api_key')->count() / $totalCompanies) * 100, 1) : 0,
                'color' => 'blue',
                'icon' => 'calendar-days',
            ],
            'retell' => [
                'connected' => $companies->whereNotNull('retell_api_key')->count(),
                'percentage' => $totalCompanies > 0 ?
                    round(($companies->whereNotNull('retell_api_key')->count() / $totalCompanies) * 100, 1) : 0,
                'color' => 'purple',
                'icon' => 'phone',
            ],
            'billing' => [
                'connected' => $companies->where('billing_status', 'active')->count(),
                'percentage' => $totalCompanies > 0 ?
                    round(($companies->where('billing_status', 'active')->count() / $totalCompanies) * 100, 1) : 0,
                'color' => 'green',
                'icon' => 'credit-card',
            ],
            'fully_integrated' => [
                'connected' => $companies
                    ->whereNotNull('calcom_api_key')
                    ->whereNotNull('retell_api_key')
                    ->where('billing_status', 'active')
                    ->count(),
                'percentage' => $totalCompanies > 0 ?
                    round(($companies
                        ->whereNotNull('calcom_api_key')
                        ->whereNotNull('retell_api_key')
                        ->where('billing_status', 'active')
                        ->count() / $totalCompanies) * 100, 1) : 0,
                'color' => 'emerald',
                'icon' => 'check-circle',
            ],
        ];
    }
    
    private function calculateHealthScore($company): int
    {
        $score = 0;
        
        // Active status (20 points)
        if ($company->is_active) $score += 20;
        
        // Has branches (20 points)
        if ($company->branch_count > 0) $score += 20;
        
        // Has staff (20 points)
        if ($company->staff_count > 0) $score += 20;
        
        // Has appointments (20 points)
        if ($company->appointment_count > 0) $score += 20;
        
        // Integration status (20 points)
        if ($company->calcom_api_key) $score += 10;
        if ($company->retell_api_key) $score += 10;
        
        return $score;
    }
}