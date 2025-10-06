<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
                    // Get all available columns from the table configuration
                    $columns = [
                        ['key' => 'name', 'label' => 'Name', 'visible' => true],
                        ['key' => 'contact_info', 'label' => 'Kontakt', 'visible' => true],
                        ['key' => 'company_type', 'label' => 'Typ', 'visible' => true],
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
}
