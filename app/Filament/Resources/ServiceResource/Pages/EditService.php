<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use App\Services\CalcomEventTypeManager;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Save policy configurations
        ServiceResource::savePolicyConfiguration($this->record, $this->data);

        // Update Cal.com event types for composite services
        if ($this->record->composite && !empty($this->record->segments)) {
            try {
                $manager = new CalcomEventTypeManager($this->record->company);

                // Check if this is first time setup (no existing mappings)
                $existingMappings = \App\Models\CalcomEventMap::where('service_id', $this->record->id)->count();

                if ($existingMappings === 0) {
                    // First time - create event types
                    $mappings = $manager->createSegmentEventTypes($this->record);

                    Notification::make()
                        ->success()
                        ->title('✅ Composite Service erstellt')
                        ->body(
                            count($mappings) . ' Cal.com Event Types wurden erstellt' . "\n" .
                            '→ Alle Segmente haben Hosts zugewiesen' . "\n" .
                            '→ Event Types sind jetzt in Cal.com sichtbar (Hidden)' . "\n" .
                            '→ Event Types → Filter: Hidden'
                        )
                        ->duration(10000) // Show for 10 seconds
                        ->send();
                } else {
                    // Update existing event types
                    $manager->updateSegmentEventTypes($this->record);

                    Notification::make()
                        ->success()
                        ->title('✅ Composite Service aktualisiert')
                        ->body(
                            'Cal.com Event Types synchronisiert' . "\n" .
                            '→ Segmente: ' . count($this->record->segments) . ' Event Types' . "\n" .
                            '→ Alle Änderungen wurden übertragen'
                        )
                        ->duration(7000)
                        ->send();
                }

                Log::info("Updated Cal.com event types for Service {$this->record->id}");

            } catch (\Exception $e) {
                Notification::make()
                    ->warning()
                    ->title('⚠️ Cal.com Sync Warning')
                    ->body(
                        'Service wurde gespeichert, aber Cal.com Synchronisation fehlgeschlagen' . "\n" .
                        '→ Fehler: ' . $e->getMessage() . "\n" .
                        '→ Bitte später erneut versuchen oder Support kontaktieren'
                    )
                    ->duration(15000)
                    ->send();

                Log::error("Failed to sync Cal.com event types for Service {$this->record->id}: " . $e->getMessage());
            }
        } elseif (!$this->record->composite) {
            // Service changed from composite to non-composite - cleanup event types
            try {
                $manager = new CalcomEventTypeManager($this->record->company);
                $deletedCount = \App\Models\CalcomEventMap::where('service_id', $this->record->id)->count();
                $manager->deleteSegmentEventTypes($this->record);

                if ($deletedCount > 0) {
                    Notification::make()
                        ->info()
                        ->title('ℹ️ Event Types entfernt')
                        ->body(
                            'Service ist nicht mehr composite' . "\n" .
                            '→ ' . $deletedCount . ' Cal.com Event Types wurden gelöscht' . "\n" .
                            '→ Service-Daten bleiben erhalten'
                        )
                        ->duration(7000)
                        ->send();
                }

                Log::info("Deleted Cal.com event types for non-composite Service {$this->record->id}");
            } catch (\Exception $e) {
                Log::error("Failed to delete Cal.com event types for Service {$this->record->id}: " . $e->getMessage());
            }
        }
    }
}
