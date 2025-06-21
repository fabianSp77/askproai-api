<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard;
use Livewire\Attributes\Reactive;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Company;
use Filament\Notifications\Notification;

class OperationsDashboard extends Dashboard  
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Operations Dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationGroup = 'Täglicher Betrieb';
    protected static ?int $navigationSort = 20;
    
    protected static string $view = 'filament.admin.pages.operations-dashboard';
    
    public static function canAccess(): bool
    {
        return auth()->check();
    }
    
    public static function getSlug(): string
    {
        return '';
    }
    
    // Filter States
    public ?string $dateFilter = 'last7days';
    public ?int $selectedCompany = null;
    public ?array $selectedBranches = [];
    public ?string $startDate = null;
    public ?string $endDate = null;
    public bool $showDatePicker = false;
    
    public function mount(): void
    {
        // Load filter states from session
        $this->dateFilter = Session::get('dashboard.dateFilter', 'last7days');
        $this->selectedCompany = Session::get('dashboard.selectedCompany', auth()->user()->company_id ?? null);
        $this->selectedBranches = Session::get('dashboard.selectedBranches', []);
        $this->startDate = Session::get('dashboard.startDate');
        $this->endDate = Session::get('dashboard.endDate');
        
        // Apply date filter
        $this->applyDateFilter();
        
        // Update widget filters
        $this->updateWidgetFilters();
    }
    
    public function getHeading(): string
    {
        return 'Operations Center';
    }
    
    public function getSubheading(): ?string
    {
        return null; // We'll show filters in the custom view instead
    }
    
    // Date filter methods
    public function setDateFilter(string $filter): void
    {
        $this->dateFilter = $filter;
        $this->showDatePicker = ($filter === 'custom');
        
        if ($filter !== 'custom') {
            $this->applyDateFilter();
            Session::put('dashboard.dateFilter', $this->dateFilter);
            $this->refresh();
        }
    }
    
    public function applyCustomDateRange(): void
    {
        if ($this->startDate && $this->endDate) {
            Session::put('dashboard.startDate', $this->startDate);
            Session::put('dashboard.endDate', $this->endDate);
            Session::put('dashboard.dateFilter', 'custom');
            $this->showDatePicker = false;
            $this->refresh();
        }
    }
    
    // Branch filter methods
    public function toggleBranch(string $branchId): void
    {
        if (in_array($branchId, $this->selectedBranches)) {
            $this->selectedBranches = array_diff($this->selectedBranches, [$branchId]);
        } else {
            $this->selectedBranches[] = $branchId;
        }
        
        Session::put('dashboard.selectedBranches', $this->selectedBranches);
        $this->refresh();
    }
    
    public function selectAllBranches(): void
    {
        $this->selectedBranches = [];
        Session::put('dashboard.selectedBranches', []);
        $this->refresh();
    }
    
    public function clearBranchSelection(): void
    {
        $this->selectedBranches = [];
        Session::put('dashboard.selectedBranches', []);
        $this->refresh();
    }
    
    public function resetAllFilters(): void
    {
        $this->dateFilter = 'last7days';
        $this->selectedCompany = auth()->user()->company_id;
        $this->selectedBranches = [];
        $this->startDate = null;
        $this->endDate = null;
        $this->showDatePicker = false;
        
        // Clear session
        Session::put('dashboard.dateFilter', 'last7days');
        Session::put('dashboard.selectedCompany', $this->selectedCompany);
        Session::put('dashboard.selectedBranches', []);
        Session::put('dashboard.startDate', null);
        Session::put('dashboard.endDate', null);
        
        $this->applyDateFilter();
        $this->refresh();
    }
    
    // Export method
    public function exportData(): void
    {
        // TODO: Implement actual export logic
        \Filament\Notifications\Notification::make()
            ->title('Export wird vorbereitet')
            ->body('Die Daten werden für den Export aufbereitet. Sie erhalten eine Benachrichtigung, wenn der Download bereit ist.')
            ->success()
            ->send();
    }
    
    // Company filter methods
    public function setCompany(int $companyId): void
    {
        $this->selectedCompany = $companyId;
        $this->selectedBranches = []; // Reset branch selection when company changes
        Session::put('dashboard.selectedCompany', $companyId);
        Session::put('dashboard.selectedBranches', []);
        $this->refresh();
    }
    
    public function getAvailableCompanies(): array
    {
        // Check if user is super admin or has access to multiple companies
        $user = auth()->user();
        
        // For now, show all active companies (later we can add permission checks)
        return Company::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($company) => [
                'id' => $company->id,
                'name' => $company->name,
                'selected' => $this->selectedCompany == $company->id
            ])
            ->toArray();
    }
    
    // Helper methods
    public function getAvailableBranches(): array
    {
        if (!$this->selectedCompany) {
            return [];
        }
        
        return Branch::where('company_id', $this->selectedCompany)
            ->where('active', true)
            ->get()
            ->map(fn($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'selected' => empty($this->selectedBranches) || in_array($branch->id, $this->selectedBranches)
            ])
            ->toArray();
    }
    
    public function getDateRangeLabel(): string
    {
        return match ($this->dateFilter) {
            'today' => 'Heute',
            'yesterday' => 'Gestern',
            'last7days' => 'Letzte 7 Tage',
            'last30days' => 'Letzte 30 Tage',
            'thisMonth' => 'Dieser Monat',
            'lastMonth' => 'Letzter Monat',
            'thisYear' => 'Dieses Jahr',
            'custom' => Carbon::parse($this->startDate)->format('d.m.Y') . ' - ' . Carbon::parse($this->endDate)->format('d.m.Y'),
            default => 'Heute'
        };
    }
    
    public function getBranchFilterLabel(): string
    {
        if (empty($this->selectedBranches)) {
            return 'Alle Filialen';
        }
        
        $count = count($this->selectedBranches);
        $companyId = $this->selectedCompany ?? auth()->user()->company_id ?? null;
        
        if (!$companyId) {
            return 'Keine Firma ausgewählt';
        }
        
        $total = Branch::where('company_id', $companyId)->count();
        
        if ($count === $total) {
            return 'Alle Filialen';
        }
        
        return $count . ' von ' . $total . ' Filialen';
    }
    
    protected function applyDateFilter(): void
    {
        switch ($this->dateFilter) {
            case 'today':
                $this->startDate = Carbon::today()->format('Y-m-d');
                $this->endDate = Carbon::today()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = Carbon::yesterday()->format('Y-m-d');
                $this->endDate = Carbon::yesterday()->format('Y-m-d');
                break;
            case 'last7days':
                $this->startDate = Carbon::now()->subDays(7)->format('Y-m-d');
                $this->endDate = Carbon::today()->format('Y-m-d');
                break;
            case 'last30days':
                $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
                $this->endDate = Carbon::today()->format('Y-m-d');
                break;
            case 'thisMonth':
                $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'lastMonth':
                $this->startDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'thisYear':
                $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfYear()->format('Y-m-d');
                break;
        }
    }
    
    public function refresh(): void
    {
        // Update widget filters
        $this->updateWidgetFilters();
        
        // Emit event to refresh widgets
        $this->dispatch('refreshWidgets');
    }
    
    protected function updateWidgetFilters(): void
    {
        $filters = [
            'dateFilter' => $this->dateFilter,
            'companyId' => $this->selectedCompany,
            'branchFilter' => empty($this->selectedBranches) ? 'all' : implode(',', $this->selectedBranches),
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];
        
        try {
            foreach ($this->getWidgets() as $widgetClass) {
                if (class_exists($widgetClass) && method_exists($widgetClass, 'setFilters')) {
                    $widgetClass::setFilters($filters);
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't break the page
            \Log::error('Error updating widget filters: ' . $e->getMessage());
        }
    }
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\CompactOperationsWidget::class,
            \App\Filament\Admin\Widgets\InsightsActionsWidget::class,
            \App\Filament\Admin\Widgets\FinancialIntelligenceWidget::class,
            \App\Filament\Admin\Widgets\BranchPerformanceMatrixWidget::class,
            \App\Filament\Admin\Widgets\LiveActivityFeedWidget::class,
        ];
    }
    
    public function getVisibleWidgets(): array
    {
        return $this->getWidgets();
    }
    
    public function getWidgetData(): array
    {
        return [];
    }
    
    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 2,
            'lg' => 4,
            'xl' => 4,
            '2xl' => 4,
        ];
    }
    
}