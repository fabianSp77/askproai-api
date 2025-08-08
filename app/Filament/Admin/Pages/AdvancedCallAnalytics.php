<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Company;
use App\Models\RetellAgent;
use App\Services\AdvancedCallAnalyticsService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class AdvancedCallAnalytics extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = "heroicon-o-chart-bar";
    protected static ?string $navigationGroup = "📊 Analytics";
    protected static ?string $navigationLabel = "Advanced Call Analytics";
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.admin.pages.advanced-call-analytics';
    
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $companyId = null;
    public ?string $agentId = null;
    public string $timeframe = '30d';
    public string $viewMode = 'overview';
    
    // Analytics Data
    public array $analyticsData = [];
    public array $realtimeMetrics = [];
    public bool $isLoading = false;
    
    protected AdvancedCallAnalyticsService $analyticsService;
    
    public function boot(AdvancedCallAnalyticsService $analyticsService): void
    {
        $this->analyticsService = $analyticsService;
    }
    
    public function mount(): void
    {
        $this->initializeFilters();
        $this->loadAnalytics();
    }
    
    protected function initializeFilters(): void
    {
        // Set default date range based on timeframe
        switch ($this->timeframe) {
            case '7d':
                $this->dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
                break;
            case '30d':
                $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
                break;
            case '90d':
                $this->dateFrom = Carbon::now()->subDays(90)->format('Y-m-d');
                break;
            default:
                $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        }
        
        $this->dateTo = Carbon::now()->format('Y-m-d');
        
        // Set company context for non-super admins
        $user = auth()->user();
        if ($user && !$user->hasRole(['Super Admin', 'super_admin'])) {
            $companies = Company::whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();
            
            if ($companies->count() === 1) {
                $this->companyId = $companies->first()->id;
            }
        }
    }
    
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->hasRole(['Super Admin', 'super_admin']);
        
        return $form
            ->schema([
                Grid::make()
                    ->columns(['default' => 1, 'sm' => 2, 'md' => 4, 'lg' => 6])
                    ->schema([
                        ToggleButtons::make('timeframe')
                            ->label('Time Period')
                            ->options([
                                '7d' => 'Last 7 days',
                                '30d' => 'Last 30 days',
                                '90d' => 'Last 90 days',
                                'custom' => 'Custom Range',
                            ])
                            ->default('30d')
                            ->inline()
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                $this->timeframe = $state;
                                if ($state !== 'custom') {
                                    $this->initializeFilters();
                                    $this->loadAnalytics();
                                }
                            })
                            ->columnSpan(['default' => 1, 'md' => 2]),
                            
                        Select::make('companyId')
                            ->label('Company')
                            ->options(function () use ($user, $isSuperAdmin) {
                                if ($isSuperAdmin) {
                                    return Company::pluck('name', 'id')->toArray();
                                }
                                
                                return Company::whereHas('users', function ($query) use ($user) {
                                    $query->where('user_id', $user->id);
                                })->pluck('name', 'id');
                            })
                            ->placeholder($isSuperAdmin ? 'All Companies' : 'Select Company')
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                $this->companyId = $state ? (int)$state : null;
                                $this->loadAnalytics();
                            })
                            ->columnSpan(['default' => 1, 'md' => 1]),
                            
                        Select::make('agentId')
                            ->label('AI Agent')
                            ->options(function () {
                                $query = RetellAgent::query();
                                if ($this->companyId) {
                                    $query->where('company_id', $this->companyId);
                                }
                                return $query->pluck('name', 'retell_agent_id')->toArray();
                            })
                            ->placeholder('All Agents')
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                $this->agentId = $state;
                                $this->loadAnalytics();
                            })
                            ->columnSpan(['default' => 1, 'md' => 1]),
                            
                        ToggleButtons::make('viewMode')
                            ->label('View')
                            ->options([
                                'overview' => 'Overview',
                                'agents' => 'Agent Performance',
                                'funnel' => 'Conversion Funnel',
                                'journey' => 'Customer Journey',
                                'predictions' => 'Predictive Analytics',
                            ])
                            ->default('overview')
                            ->inline()
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                $this->viewMode = $state;
                                $this->loadAnalytics();
                            })
                            ->columnSpan(['default' => 1, 'md' => 2]),
                    ]),
                    
                Grid::make()
                    ->columns(['default' => 1, 'sm' => 2])
                    ->schema([
                        DatePicker::make('dateFrom')
                            ->label('From Date')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn() => $this->loadAnalytics())
                            ->visible(fn() => $this->timeframe === 'custom'),
                            
                        DatePicker::make('dateTo')
                            ->label('To Date')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn() => $this->loadAnalytics())
                            ->visible(fn() => $this->timeframe === 'custom'),
                    ]),
            ]);
    }
    
    public function loadAnalytics(): void
    {
        if (!$this->dateFrom || !$this->dateTo) {
            return;
        }
        
        $this->isLoading = true;
        
        try {
            $filters = [
                'date_from' => Carbon::parse($this->dateFrom)->startOfDay(),
                'date_to' => Carbon::parse($this->dateTo)->endOfDay(),
                'company_id' => $this->companyId,
                'agent_id' => $this->agentId,
            ];
            
            $this->analyticsData = $this->analyticsService->getDashboardMetrics($filters);
            $this->realtimeMetrics = $this->analyticsService->getRealTimeKPIs($filters);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error loading analytics')
                ->body('Failed to load analytics data: ' . $e->getMessage())
                ->danger()
                ->send();
                
            $this->analyticsData = [];
            $this->realtimeMetrics = [];
        }
        
        $this->isLoading = false;
    }
    
    protected function getActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-m-arrow-path')
                ->action(fn() => $this->loadAnalytics()),
                
            Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-m-document-arrow-down')
                ->action(fn() => $this->exportReport())
                ->disabled(fn() => empty($this->analyticsData)),
                
            Action::make('schedule')
                ->label('Schedule Report')
                ->icon('heroicon-m-clock')
                ->action(fn() => $this->scheduleReport())
                ->disabled(fn() => empty($this->analyticsData)),
        ];
    }
    
    public function exportReport(): void
    {
        // Implementation for exporting analytics report
        Notification::make()
            ->title('Report Export')
            ->body('Analytics report export functionality coming soon!')
            ->info()
            ->send();
    }
    
    public function scheduleReport(): void
    {
        // Implementation for scheduling recurring reports
        Notification::make()
            ->title('Schedule Report')
            ->body('Report scheduling functionality coming soon!')
            ->info()
            ->send();
    }
    
    public function getTitle(): string
    {
        return 'Advanced Call Analytics';
    }
    
    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'analyticsData' => $this->analyticsData,
            'realtimeMetrics' => $this->realtimeMetrics,
            'isLoading' => $this->isLoading,
            'viewMode' => $this->viewMode,
        ]);
    }
}
