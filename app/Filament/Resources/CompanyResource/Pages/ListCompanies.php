<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\CompanyResource\Widgets\PartnerStatsWidget;
use App\Models\Company;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('manageColumns')
                ->label('Spalten verwalten')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->modalHeading('Spalten verwalten')
                ->modalSubheading('Wählen Sie die anzuzeigenden Spalten aus')
                ->modalContent(function () {
                    $columns = [
                        ['key' => 'name', 'label' => 'Name', 'visible' => true],
                        ['key' => 'contact_info', 'label' => 'Kontakt', 'visible' => true],
                        ['key' => 'company_type', 'label' => 'Typ', 'visible' => true],
                        ['key' => 'partner_status', 'label' => 'Partner-Beziehung', 'visible' => true],
                        ['key' => 'billing_status', 'label' => 'Abrechnungsstatus', 'visible' => true],
                        ['key' => 'credit_balance', 'label' => 'Guthaben', 'visible' => true],
                        ['key' => 'infrastructure', 'label' => 'Infrastruktur', 'visible' => true],
                        ['key' => 'total_profit', 'label' => 'Profit', 'visible' => false],
                        ['key' => 'subscription_status', 'label' => 'Abo-Status', 'visible' => true],
                        ['key' => 'activity_status', 'label' => 'Aktivität', 'visible' => true],
                        ['key' => 'updated_at', 'label' => 'Aktualisiert', 'visible' => false],
                        ['key' => 'created_at', 'label' => 'Erstellt', 'visible' => false],
                        ['key' => 'branches_count', 'label' => 'Filialen', 'visible' => true],
                        ['key' => 'staff_count', 'label' => 'Mitarbeiter', 'visible' => true],
                        ['key' => 'phone_numbers_count', 'label' => 'Telefonnummern', 'visible' => false],
                        ['key' => 'services_count', 'label' => 'Services', 'visible' => false],
                        ['key' => 'industry', 'label' => 'Branche', 'visible' => true],
                        ['key' => 'has_api_integrations', 'label' => 'API Integrationen', 'visible' => false],
                        ['key' => 'calcom_api_status', 'label' => 'Cal.com Status', 'visible' => false],
                        ['key' => 'retell_api_status', 'label' => 'Retell Status', 'visible' => false],
                        ['key' => 'uses_whitelabel', 'label' => 'White Label', 'visible' => false],
                        ['key' => 'trial_status', 'label' => 'Test-Status', 'visible' => false],
                        ['key' => 'archived_status', 'label' => 'Archiviert', 'visible' => false],
                        ['key' => 'deletion_info', 'label' => 'Löschinfo', 'visible' => false],
                        ['key' => 'balance', 'label' => 'Kontostand', 'visible' => true],
                    ];

                    return view('filament.modals.column-manager-fixed', [
                        'resource' => 'companies',
                        'columns' => $columns,
                    ]);
                })
                ->modalWidth('lg')
                ->modalFooterActions([]),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PartnerStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }

    public function getTabs(): array
    {
        // Cache counts for 2 minutes to avoid repeated queries
        $counts = Cache::remember('company_tabs_counts_' . now()->format('Y-m-d-H-i'), 120, function () {
            return [
                'all' => Company::count(),
                'partners' => Company::where('is_partner', true)->count(),
                'managed' => Company::whereNotNull('managed_by_company_id')->count(),
                'unassigned' => Company::whereNull('managed_by_company_id')
                    ->where('is_partner', false)
                    ->where('is_active', true)
                    ->count(),
            ];
        });

        // Get top partners with managed companies for dynamic tabs
        $topPartners = Company::where('is_partner', true)
            ->withCount('managedCompanies')
            ->having('managed_companies_count', '>', 0)
            ->orderByDesc('managed_companies_count')
            ->limit(3)
            ->get();

        $tabs = [
            'all' => Tab::make('Alle')
                ->badge($counts['all'])
                ->badgeColor('gray'),

            'partners' => Tab::make('Partner')
                ->icon('heroicon-m-star')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_partner', true))
                ->badge($counts['partners'])
                ->badgeColor('success'),

            'managed' => Tab::make('Verwaltet')
                ->icon('heroicon-m-arrow-right-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('managed_by_company_id'))
                ->badge($counts['managed'])
                ->badgeColor('info'),

            'unassigned' => Tab::make('Ohne Partner')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->whereNull('managed_by_company_id')
                        ->where('is_partner', false)
                        ->where('is_active', true)
                )
                ->badge($counts['unassigned'])
                ->badgeColor($counts['unassigned'] > 5 ? 'warning' : 'gray'),
        ];

        // Add dynamic tabs for top partners
        foreach ($topPartners as $partner) {
            $tabKey = 'partner_' . $partner->id;
            $tabs[$tabKey] = Tab::make($partner->name)
                ->icon('heroicon-m-building-office-2')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('managed_by_company_id', $partner->id)
                )
                ->badge($partner->managed_companies_count)
                ->badgeColor('primary');
        }

        return $tabs;
    }
}
