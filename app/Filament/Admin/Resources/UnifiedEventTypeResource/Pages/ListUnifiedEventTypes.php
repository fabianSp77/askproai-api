<?php

namespace App\Filament\Admin\Resources\UnifiedEventTypeResource\Pages;

use App\Filament\Admin\Resources\UnifiedEventTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\UnifiedEventType;
use App\Services\CalcomImportService;
use Filament\Notifications\Notification;
use Filament\Forms;


class ListUnifiedEventTypes extends ListRecords
{
    protected static string $resource = UnifiedEventTypeResource::class;

protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importFromCalcom')
                ->label('Event Types aus Cal.com importieren')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Cal.com Event Types importieren')
                ->modalDescription('Dies wird alle Event Types von Cal.com abrufen und neue importieren. Duplikate werden zur Überprüfung markiert.')
                ->modalSubmitActionLabel('Importieren')
                ->action(function () {
                    $service = new CalcomImportService();
                    $results = $service->importEventTypes();
                    
                    // Zeige Ergebnisse
                    $message = sprintf(
                        'Import abgeschlossen: %d importiert, %d Duplikate, %d übersprungen, %d Fehler',
                        $results['imported'],
                        $results['duplicates'],
                        $results['skipped'],
                        $results['errors']
                    );
                    
                    if ($results['imported'] > 0) {
                        Notification::make()
                            ->title('Import erfolgreich')
                            ->body($message)
                            ->success()
                            ->send();
                    } elseif ($results['duplicates'] > 0) {
                        Notification::make()
                            ->title('Duplikate gefunden')
                            ->body($message . ' - Bitte überprüfen Sie die Duplikate im entsprechenden Tab.')
                            ->warning()
                            ->persistent()
                            ->send();
                    } elseif ($results['skipped'] > 0) {
                        Notification::make()
                            ->title('Keine neuen Event Types')
                            ->body($message)
                            ->info()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Import fehlgeschlagen')
                            ->body($message)
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\CreateAction::make(),
        ];
    }
public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle Event Types')
                ->badge(UnifiedEventType::count()),
                
            'duplicates' => Tab::make('Duplikate')
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(UnifiedEventType::duplicates()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->duplicates()),
                
            'unassigned' => Tab::make('Nicht zugeordnet')
                ->icon('heroicon-o-x-circle')
                ->badge(UnifiedEventType::unassigned()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->unassigned()),
                
            'assigned' => Tab::make('Zugeordnet')
                ->icon('heroicon-o-check-circle')
                ->badge(UnifiedEventType::assigned()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->assigned()),
        ];
    }

}
