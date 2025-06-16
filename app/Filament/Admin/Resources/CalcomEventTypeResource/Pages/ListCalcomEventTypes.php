<?php

namespace App\Filament\Admin\Resources\CalcomEventTypeResource\Pages;

use App\Filament\Admin\Resources\CalcomEventTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCalcomEventTypes extends ListRecords
{
    protected static string $resource = CalcomEventTypeResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\EventTypeAnalyticsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('sync_all')
                ->label('Alle synchronisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Event-Types synchronisieren')
                ->modalDescription('Möchten Sie wirklich alle Event-Types für alle Unternehmen synchronisieren?')
                ->action(function () {
                    $companies = \App\Models\Company::whereNotNull('calcom_api_key')->get();
                    $syncService = new \App\Services\CalcomSyncService();
                    
                    $totalSynced = 0;
                    $errors = [];
                    
                    foreach ($companies as $company) {
                        try {
                            $result = $syncService->syncEventTypesForCompany($company->id);
                            $totalSynced += $result['synced_count'];
                        } catch (\Exception $e) {
                            $errors[] = $company->name . ': ' . $e->getMessage();
                        }
                    }
                    
                    if (empty($errors)) {
                        $this->notify('success', "$totalSynced Event-Types erfolgreich synchronisiert.");
                    } else {
                        $this->notify('warning', "$totalSynced Event-Types synchronisiert. Fehler: " . implode(', ', $errors));
                    }
                })
        ];
    }
}