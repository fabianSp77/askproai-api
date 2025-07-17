<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Analytics\RoiCalculationService;
use Carbon\Carbon;

class BranchPerformanceMatrixWidget extends FilterableWidget
{
    protected static string $view = 'filament.admin.widgets.branch-performance-matrix-widget-v2';
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
        'xl' => 2,
    ];
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '60s';
    
    public string $sortBy = 'roi'; // roi, revenue, conversion, calls
    public string $sortDirection = 'desc';
    
    protected function getRoiService(): RoiCalculationService
    {
        return app(RoiCalculationService::class);
    }
    
    public function mount(): void
    {
        parent::mount();
    }
    
    protected function getListeners(): array
    {
        return [
            'periodChanged' => 'handlePeriodChange',
        ];
    }
    
    public function handlePeriodChange($period): void
    {
        $this->dateFilter = $period;
    }
    
    public function sortBy($column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }
    
    protected function getViewData(): array
    {
        $company = auth()->user()->company;
        [$startDate, $endDate] = $this->getDateRange();
        
        // Get company-wide ROI with branch breakdown
        $companyRoi = $this->getRoiService()->getCompanyWideRoi($company, $startDate, $endDate);
        
        // Sort branches based on current sort settings
        $branches = $this->sortBranches($companyRoi['branch_breakdown']);
        
        // Calculate averages for benchmarking
        $avgRoi = count($branches) > 0 
            ? array_sum(array_column($branches, 'roi_percentage')) / count($branches)
            : 0;
            
        $avgConversion = count($branches) > 0 
            ? array_sum(array_column($branches, 'conversion_rate')) / count($branches)
            : 0;
        
        return array_merge(parent::getViewData(), [
            'branches' => $branches,
            'companyTotal' => $companyRoi['company_total'],
            'topPerformer' => $companyRoi['top_performer'],
            'bottomPerformer' => $companyRoi['bottom_performer'],
            'avgRoi' => round($avgRoi, 1),
            'avgConversion' => round($avgConversion, 1),
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
        ]);
    }
    
    protected function sortBranches(array $branches): array
    {
        $multiplier = $this->sortDirection === 'asc' ? 1 : -1;
        
        usort($branches, function($a, $b) use ($multiplier) {
            $aValue = match($this->sortBy) {
                'roi' => $a['roi_percentage'],
                'revenue' => $a['revenue'],
                'conversion' => $a['conversion_rate'],
                'calls' => $a['calls'],
                default => $a['roi_percentage'],
            };
            
            $bValue = match($this->sortBy) {
                'roi' => $b['roi_percentage'],
                'revenue' => $b['revenue'],
                'conversion' => $b['conversion_rate'],
                'calls' => $b['calls'],
                default => $b['roi_percentage'],
            };
            
            return ($aValue <=> $bValue) * $multiplier;
        });
        
        return $branches;
    }
    
    protected function getDateRange(): array
    {
        // Use filter from parent class
        return [
            $this->getStartDate(),
            $this->getEndDate()
        ];
    }
    
    public function getPerformanceColor(float $value, float $average): string
    {
        $percentage = $average > 0 ? ($value / $average) * 100 : 0;
        
        if ($percentage >= 120) {
            return 'text-green-600 bg-green-50 dark:bg-green-900/20';
        } elseif ($percentage >= 80) {
            return 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20';
        } else {
            return 'text-red-600 bg-red-50 dark:bg-red-900/20';
        }
    }
    
    public function getStatusBadge(float $roi): array
    {
        if ($roi >= 150) {
            return ['label' => 'Top', 'color' => 'success', 'icon' => 'heroicon-o-fire'];
        } elseif ($roi >= 100) {
            return ['label' => 'Gut', 'color' => 'success', 'icon' => 'heroicon-o-arrow-trending-up'];
        } elseif ($roi >= 50) {
            return ['label' => 'OK', 'color' => 'warning', 'icon' => 'heroicon-o-minus'];
        } elseif ($roi >= 0) {
            return ['label' => 'Schwach', 'color' => 'danger', 'icon' => 'heroicon-o-arrow-trending-down'];
        } else {
            return ['label' => 'Verlust', 'color' => 'danger', 'icon' => 'heroicon-o-exclamation-triangle'];
        }
    }
}