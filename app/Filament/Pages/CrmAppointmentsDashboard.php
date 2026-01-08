<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CrmAppointments\CallVolumeStats;
use App\Filament\Widgets\CrmAppointments\ConversionStats;
use App\Filament\Widgets\CrmAppointments\PerformanceStats;
use App\Filament\Widgets\CrmAppointments\CallsByStatusChart;
use App\Filament\Widgets\CrmAppointments\HourlyDistributionChart;
use App\Filament\Widgets\CrmAppointments\ConversionTrendChart;
use App\Filament\Widgets\CrmAppointments\AgentPerformanceChart;
use App\Filament\Widgets\CrmAppointments\RecentCallsWidget;
use App\Models\Company;
use App\Models\RetellAgent;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Support\Facades\Auth;

/**
 * CRM Appointments Dashboard
 *
 * Real-time overview of appointment calls with KPIs, conversion metrics,
 * and agent performance. Designed for call center managers and executives.
 *
 * Layout:
 * - Row 1: KPI Stats (Call Volume, Conversion, Performance)
 * - Row 2: Charts (Status, Hourly Distribution, Conversion Trend)
 * - Row 3: Agent Performance + Recent Calls
 *
 * FEATURE: Company filter for super-admins, Agent filter for all users.
 */
class CrmAppointmentsDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Anruf-Dashboard';
    protected static ?string $title = 'Terminierungsanrufe Dashboard';
    protected static ?int $navigationSort = 1;
    protected static string $routePath = '/crm-appointments-dashboard';

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
     * Dashboard filter form - Company, Agent, and Time Range selectors.
     */
    public function filtersForm(Form $form): Form
    {
        $schema = [];

        // Company filter only for super-admins
        if ($this->canViewAllCompanies()) {
            $schema[] = Select::make('company_id')
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
                ->afterStateUpdated(fn () => $this->dispatch('crmFiltersUpdated', filters: $this->filters))
                ->columnSpan(1);
        }

        // Agent filter - available to all users
        $schema[] = Select::make('agent_id')
            ->label('Agent')
            ->placeholder('Alle Agents')
            ->options(function () {
                $query = RetellAgent::query()
                    ->where('is_active', true)
                    ->orderBy('name');

                // Filter by user's company if not super-admin
                if (!$this->canViewAllCompanies()) {
                    $query->where('company_id', Auth::user()?->company_id);
                }

                return $query->pluck('name', 'agent_id')->toArray();
            })
            ->searchable()
            ->preload()
            ->native(false)
            ->live()
            ->afterStateUpdated(fn () => $this->dispatch('crmFiltersUpdated', filters: $this->filters))
            ->columnSpan(1);

        // Time range filter with custom date option
        $schema[] = Select::make('time_range')
            ->label('Zeitraum')
            ->options([
                'today' => 'Heute',
                'week' => 'Diese Woche',
                'month' => 'Dieser Monat',
                'quarter' => 'Dieses Quartal',
                'all' => 'Gesamt',
                'custom' => 'Benutzerdefiniert...',
            ])
            ->default('today')
            ->native(false)
            ->live()
            ->afterStateUpdated(fn () => $this->dispatch('crmFiltersUpdated', filters: $this->filters))
            ->columnSpan(1);

        // Custom date range pickers (visible only when 'custom' is selected)
        $schema[] = DatePicker::make('date_from')
            ->label('Von')
            ->native(false)
            ->displayFormat('d.m.Y')
            ->placeholder('TT.MM.JJJJ')
            ->maxDate(now())
            ->visible(fn (Get $get) => $get('time_range') === 'custom')
            ->live()
            ->afterStateUpdated(fn () => $this->dispatch('crmFiltersUpdated', filters: $this->filters))
            ->columnSpan(1);

        $schema[] = DatePicker::make('date_to')
            ->label('Bis')
            ->native(false)
            ->displayFormat('d.m.Y')
            ->placeholder('TT.MM.JJJJ')
            ->default(now())
            ->maxDate(now())
            ->visible(fn (Get $get) => $get('time_range') === 'custom')
            ->live()
            ->afterStateUpdated(fn () => $this->dispatch('crmFiltersUpdated', filters: $this->filters))
            ->columnSpan(1);

        // Calculate columns: base 3 + company filter (1) + custom dates (2 when visible)
        $baseColumns = $this->canViewAllCompanies() ? 4 : 3;

        return $form
            ->schema([
                Section::make()
                    ->schema($schema)
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'md' => $baseColumns,
                        'xl' => $baseColumns + 2, // Extra space for date pickers
                    ])
                    ->compact(),
            ]);
    }

    /**
     * Override parent getWidgets() to prevent other widgets from appearing.
     */
    public function getWidgets(): array
    {
        return [];
    }

    /**
     * Pass filters to header/footer widgets.
     */
    public function getWidgetData(): array
    {
        return [
            'filters' => $this->filters,
        ];
    }

    /**
     * Header widgets - KPI Stats (3 columns)
     *
     * Row 1: Call Volume + Conversion Stats + Performance Stats
     */
    protected function getHeaderWidgets(): array
    {
        return [
            CallVolumeStats::class,
            ConversionStats::class,
            PerformanceStats::class,
        ];
    }

    /**
     * Main content widgets - Charts and tables
     *
     * Row 2: Status + Hourly Distribution + Conversion Trend
     * Row 3: Agent Performance + Recent Calls
     */
    protected function getFooterWidgets(): array
    {
        return [
            CallsByStatusChart::class,
            HourlyDistributionChart::class,
            ConversionTrendChart::class,
            AgentPerformanceChart::class,
            RecentCallsWidget::class,
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
