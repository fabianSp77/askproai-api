<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ServiceGateway\AgingAnalysisWidget;
use App\Filament\Widgets\ServiceGateway\CasesByStatusChart;
use App\Filament\Widgets\ServiceGateway\CasesByPriorityChart;
use App\Filament\Widgets\ServiceGateway\CategoryDistributionWidget;
use App\Filament\Widgets\ServiceGateway\FirstResponseTimeWidget;
use App\Filament\Widgets\ServiceGateway\SlaComplianceStats;
use App\Filament\Widgets\ServiceGateway\WorkloadByGroupChart;
use App\Filament\Widgets\ServiceGateway\RecentCasesWidget;
use App\Filament\Widgets\ServiceGateway\OutputDeliveryStats;
use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Support\Facades\Auth;

/**
 * Service Gateway Dashboard
 *
 * ServiceNow-style overview dashboard with KPIs, charts, and workload distribution.
 * Provides at-a-glance visibility into service desk operations.
 *
 * FEATURE: Company filter for super-admins to view all companies or filter by specific company.
 * Regular users only see their own company's data (no filter shown).
 */
class ServiceGatewayDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Service Gateway Dashboard';
    protected static ?int $navigationSort = 9;
    protected static string $routePath = '/service-gateway-dashboard';

    /**
     * Only show when Service Gateway is enabled.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('gateway.mode_enabled', false);
    }

    /**
     * Check if current user can view all companies.
     */
    protected function canViewAllCompanies(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'super-admin', 'Admin', 'reseller_admin']);
    }

    /**
     * Dashboard filter form - Company selector and time range for admins.
     *
     * Super-admins/Admins can:
     * - Select "Alle Unternehmen" to see aggregated data
     * - Select specific company to filter
     * - Choose preset time ranges or custom date range
     *
     * Regular users don't see the filter (uses their company automatically).
     */
    public function filtersForm(Form $form): Form
    {
        // Only show filter for users who can view all companies
        if (!$this->canViewAllCompanies()) {
            return $form->schema([]);
        }

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('company_id')
                            ->label('Unternehmen')
                            ->placeholder('Alle Unternehmen')
                            ->options(fn () => Company::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn () => $this->dispatch('filtersUpdated', filters: $this->filters))
                            ->columnSpan(1),
                        Select::make('time_range')
                            ->label('Zeitraum')
                            ->options([
                                'today' => 'Heute',
                                'week' => 'Diese Woche',
                                'month' => 'Dieser Monat',
                                'quarter' => 'Dieses Quartal',
                                'all' => 'Gesamt',
                                'custom' => 'Benutzerdefiniert...',
                            ])
                            ->default('month')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn () => $this->dispatch('filtersUpdated', filters: $this->filters))
                            ->columnSpan(1),
                        // Custom date range pickers (visible only when 'custom' is selected)
                        DatePicker::make('date_from')
                            ->label('Von')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->placeholder('TT.MM.JJJJ')
                            ->maxDate(now())
                            ->visible(fn (Get $get) => $get('time_range') === 'custom')
                            ->live()
                            ->afterStateUpdated(fn () => $this->dispatch('filtersUpdated', filters: $this->filters))
                            ->columnSpan(1),
                        DatePicker::make('date_to')
                            ->label('Bis')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->placeholder('TT.MM.JJJJ')
                            ->default(now())
                            ->maxDate(now())
                            ->visible(fn (Get $get) => $get('time_range') === 'custom')
                            ->live()
                            ->afterStateUpdated(fn () => $this->dispatch('filtersUpdated', filters: $this->filters))
                            ->columnSpan(1),
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'md' => 4,
                        'xl' => 6, // Extra space for date pickers
                    ])
                    ->compact(),
            ]);
    }

    /**
     * Override parent getWidgets() to prevent Cal.com widgets from appearing.
     *
     * The parent Dashboard class includes Cal.com widgets in getWidgets().
     * We explicitly return an empty array to ensure only Service Gateway
     * widgets (defined in getHeaderWidgets/getFooterWidgets) are shown.
     */
    public function getWidgets(): array
    {
        return [];
    }

    /**
     * Pass filters to header/footer widgets.
     *
     * CRITICAL: Without this override, header/footer widgets receive empty data
     * and don't get the filter values. The Filament Dashboard base class only
     * passes filters to main widgets (getWidgets), not header/footer widgets.
     *
     * @see vendor/filament/filament/resources/views/components/page/index.blade.php
     */
    public function getWidgetData(): array
    {
        return [
            'filters' => $this->filters,
        ];
    }

    /**
     * Header widgets - Stats overview (3 columns)
     *
     * Row 1: SLA Compliance + Output Delivery + First Response Time
     */
    protected function getHeaderWidgets(): array
    {
        return [
            SlaComplianceStats::class,
            OutputDeliveryStats::class,
            FirstResponseTimeWidget::class,
        ];
    }

    /**
     * Main content widgets - Charts and tables
     *
     * Row 1: Status + Priority + Aging Analysis
     * Row 2: Category Distribution + Workload by Group
     * Row 3: Recent Cases Table (full width)
     */
    protected function getFooterWidgets(): array
    {
        return [
            CasesByStatusChart::class,
            CasesByPriorityChart::class,
            AgingAnalysisWidget::class,
            CategoryDistributionWidget::class,
            WorkloadByGroupChart::class,
            RecentCasesWidget::class,
        ];
    }

    /**
     * Widget column configuration
     */
    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
