<?php

namespace App\Filament\Admin\Resources\BranchResource\Pages;

use App\Filament\Admin\Resources\BranchResource;
use App\Filament\Admin\Resources\BranchResource\Widgets\RetellAgentProvisioningWidget;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeValidate(): void
    {
        // Entfernen Sie Validierungsregeln für Felder, die noch nicht ausgefüllt sind
        // Dies erlaubt das Zwischenspeichern
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Wenn kein retell_agent_id gesetzt ist, setzen wir es auf null statt auf einen leeren String
        if (empty($data['retell_agent_id'])) {
            $data['retell_agent_id'] = null;
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        $configProgress = $this->record->configuration_progress['percentage'] ?? 0;
        
        if ($configProgress < 100) {
            return Notification::make()
                ->success()
                ->title('Zwischenstand gespeichert')
                ->body("Konfiguration zu {$configProgress}% abgeschlossen. Bitte vervollständigen Sie die fehlenden Informationen.")
                ->duration(5000);
        }

        return Notification::make()
            ->success()
            ->title('Filiale gespeichert')
            ->body('Alle Änderungen wurden erfolgreich gespeichert.');
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            RetellAgentProvisioningWidget::make([
                'record' => $this->record,
            ]),
        ];
    }
    
    protected function getFooterWidgets(): array 
    {
        return [];
    }
}
