<?php

namespace App\Filament\Resources\ServiceOutputConfigurationResource\Pages;

use App\Filament\Resources\ServiceOutputConfigurationResource;
use App\Filament\Resources\ServiceOutputConfigurationResource\RelationManagers\DeliveryLogsRelationManager;
use App\Jobs\ServiceGateway\TestWebhookDeliveryJob;
use App\Models\ServiceOutputConfiguration;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditServiceOutputConfiguration extends EditRecord
{
    protected static string $resource = ServiceOutputConfigurationResource::class;

    /**
     * Enable RelationManagers as combined tabs with form content.
     */
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    /**
     * Transform database format to Repeater format when loading.
     *
     * Database: email_recipients: ['a@x.de', 'b@x.de'], muted_recipients: ['b@x.de']
     * Repeater: recipient_entries: [['email' => 'a@x.de', 'is_active' => true], ['email' => 'b@x.de', 'is_active' => false]]
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $emailRecipients = $data['email_recipients'] ?? [];
        $mutedRecipients = $data['muted_recipients'] ?? [];

        $entries = [];
        foreach ($emailRecipients as $email) {
            $entries[] = [
                'email' => $email,
                'is_active' => !in_array($email, $mutedRecipients),
            ];
        }

        $data['recipient_entries'] = $entries;

        return $data;
    }

    /**
     * Transform Repeater format back to database format when saving.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $entries = $data['recipient_entries'] ?? [];

        $emailRecipients = [];
        $mutedRecipients = [];

        foreach ($entries as $entry) {
            $email = trim($entry['email'] ?? '');
            if (empty($email)) {
                continue;
            }

            $emailRecipients[] = $email;

            if (!($entry['is_active'] ?? true)) {
                $mutedRecipients[] = $email;
            }
        }

        $data['email_recipients'] = array_values(array_unique($emailRecipients));
        $data['muted_recipients'] = array_values(array_unique($mutedRecipients));

        // Remove the virtual field
        unset($data['recipient_entries']);

        return $data;
    }

    /**
     * Explicitly include RelationManagers on Edit page.
     */
    public function getRelationManagers(): array
    {
        return [
            DeliveryLogsRelationManager::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('test_webhook')
                ->label('Webhook testen')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Test-Webhook senden')
                ->modalDescription('Ein Test-Webhook wird an die konfigurierte URL gesendet. Die Nachricht enthält Testdaten mit is_test=true Flag.')
                ->modalSubmitActionLabel('Test senden')
                ->visible(fn () => $this->record->sendsWebhook() && !empty($this->record->webhook_url))
                ->action(function () {
                    try {
                        // Log the dispatch attempt for debugging
                        \Illuminate\Support\Facades\Log::info('[TestWebhook] Button clicked', [
                            'configuration_id' => $this->record->id,
                            'webhook_url' => $this->record->webhook_url,
                            'user_id' => auth()->id(),
                        ]);

                        // Dispatch test webhook job
                        TestWebhookDeliveryJob::dispatch($this->record->id);

                        // Verify job was queued (for debugging)
                        $pendingJobs = \Illuminate\Support\Facades\DB::table('jobs')
                            ->where('queue', 'default')
                            ->count();

                        \Illuminate\Support\Facades\Log::info('[TestWebhook] Job dispatched', [
                            'configuration_id' => $this->record->id,
                            'pending_jobs' => $pendingJobs,
                        ]);

                        Notification::make()
                            ->title('Test-Webhook gesendet')
                            ->body('Der Test-Webhook wird an ' . $this->record->webhook_url . ' gesendet. Die Delivery Historie wird automatisch aktualisiert.')
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('[TestWebhook] Dispatch failed', [
                            'configuration_id' => $this->record->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('Fehler beim Senden')
                            ->body('Der Test-Webhook konnte nicht gesendet werden: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->categories()->exists()) {
                        Notification::make()
                            ->title('Konfiguration kann nicht geloescht werden')
                            ->body('Es existieren noch Kategorien, die diese Konfiguration verwenden.')
                            ->danger()
                            ->send();
                        $action->cancel();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
