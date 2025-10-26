<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use App\Services\CalcomEventTypeManager;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function afterCreate(): void
    {
        // Save policy configurations
        ServiceResource::savePolicyConfiguration($this->record, $this->data);

        // Create Cal.com event types for composite services
        if ($this->record->composite && !empty($this->record->segments)) {
            try {
                $manager = new CalcomEventTypeManager($this->record->company);
                $mappings = $manager->createSegmentEventTypes($this->record);

                Notification::make()
                    ->success()
                    ->title('Composite Service Created')
                    ->body(count($mappings) . ' Cal.com event types created for segments')
                    ->send();

                Log::info("Created " . count($mappings) . " Cal.com event types for Service {$this->record->id}");

            } catch (\Exception $e) {
                Notification::make()
                    ->warning()
                    ->title('Cal.com Event Types Not Created')
                    ->body('Service created but Cal.com event types failed: ' . $e->getMessage())
                    ->send();

                Log::error("Failed to create Cal.com event types for Service {$this->record->id}: " . $e->getMessage());
            }
        }
    }
}
