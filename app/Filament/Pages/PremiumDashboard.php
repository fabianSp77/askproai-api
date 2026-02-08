<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Premium\AISummaryWidget;
use App\Filament\Widgets\Premium\CalendarWidget;
use App\Filament\Widgets\Premium\InvoicesListWidget;
use App\Filament\Widgets\Premium\RevenueBarChart;
use App\Filament\Widgets\Premium\SpendingDonutChart;
use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

/**
 * Premium Dashboard - Financial Analytics
 *
 * Premium dark-themed dashboard with financial analytics widgets.
 * Inspired by Outcrowd Financial Analytics Dashboard design.
 *
 * ACCESS: Super-Admin and Reseller-Admin only
 * FEATURE FLAG: features.premium_dashboard
 * ROUTE: /admin/premium-dashboard
 */
class PremiumDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?string $navigationLabel = 'Premium Dashboard';
    protected static ?string $title = 'Financial Analytics';
    protected static ?int $navigationSort = 1;
    protected static string $routePath = '/premium-dashboard';

    /**
     * Only show when feature flag is enabled and user has access.
     */
    public static function shouldRegisterNavigation(): bool
    {
        if (!config('features.premium_dashboard', false)) {
            return false;
        }

        return static::canAccess();
    }

    /**
     * Check if current user can access the premium dashboard.
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'super-admin', 'Admin', 'reseller_admin', 'reseller_owner']);
    }

    /**
     * Get the custom view for this page.
     */
    public function getView(): string
    {
        return 'filament.pages.premium-dashboard';
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
     * Dashboard filter form - Company selector and time range.
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
                            ->columnSpan(1),
                        Select::make('time_range')
                            ->label('Zeitraum')
                            ->options([
                                'today' => 'Heute',
                                'week' => 'Diese Woche',
                                'month' => 'Dieser Monat',
                                'quarter' => 'Dieses Quartal',
                                'year' => 'Dieses Jahr',
                                'all' => 'Gesamt',
                                'custom' => 'Benutzerdefiniert...',
                            ])
                            ->default('month')
                            ->native(false)
                            ->live()
                            ->columnSpan(1),
                        DatePicker::make('date_from')
                            ->label('Von')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->placeholder('TT.MM.JJJJ')
                            ->maxDate(now())
                            ->visible(fn (Get $get) => $get('time_range') === 'custom')
                            ->live()
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
                            ->columnSpan(1),
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'md' => 4,
                        'xl' => 6,
                    ])
                    ->compact(),
            ]);
    }

    /**
     * Override getWidgets to prevent default Cal.com widgets.
     */
    public function getWidgets(): array
    {
        return [];
    }

    /**
     * Pass filters to header/footer widgets.
     *
     * CRITICAL: Without this override, header/footer widgets receive empty data.
     */
    public function getWidgetData(): array
    {
        return [
            'filters' => $this->filters,
        ];
    }

    /**
     * Handle filter updates from child widgets (e.g., CalendarWidget date selection).
     */
    #[On('setDashboardFilters')]
    public function handleSetDashboardFilters(array $filters): void
    {
        $this->filters = array_merge($this->filters ?? [], $filters);
    }

    /**
     * Header widgets - AI Summary (full width).
     */
    protected function getHeaderWidgets(): array
    {
        return [
            AISummaryWidget::class,
        ];
    }

    /**
     * Footer widgets - Charts and lists.
     */
    protected function getFooterWidgets(): array
    {
        return [
            RevenueBarChart::class,
            SpendingDonutChart::class,
            CalendarWidget::class,
            InvoicesListWidget::class,
        ];
    }

    /**
     * Header widget column configuration.
     */
    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 1,
            'xl' => 1,
        ];
    }

    /**
     * Footer widget column configuration.
     */
    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }
}
