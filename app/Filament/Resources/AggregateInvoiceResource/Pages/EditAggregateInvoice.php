<?php

namespace App\Filament\Resources\AggregateInvoiceResource\Pages;

use App\Filament\Resources\AggregateInvoiceResource;
use App\Models\AggregateInvoice;
use App\Services\Billing\StripeInvoicingService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAggregateInvoice extends EditRecord
{
    protected static string $resource = AggregateInvoiceResource::class;

    /**
     * Convert cents to EUR for form display.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['discount'] = ($data['discount_cents'] ?? 0) / 100;
        return $data;
    }

    /**
     * Convert EUR to cents for storage.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['discount_cents'] = (int) round(((float) ($data['discount'] ?? 0)) * 100);
        unset($data['discount']);
        return $data;
    }

    /**
     * Recalculate totals after save.
     */
    protected function afterSave(): void
    {
        // FIX: Refresh model vor Berechnung (frische Daten aus DB)
        $this->record->refresh();

        // Berechne Summen mit aktuellen Werten
        $this->record->calculateTotals();

        // FIX: Refresh nach Berechnung für UI-Sync
        $this->record->refresh();

        // UX: Notification mit aktualisierten Beträgen
        Notification::make()
            ->title('Rechnung aktualisiert')
            ->body(sprintf(
                'Rabatt: %s | Gesamtbetrag: %s',
                $this->record->formatted_discount,
                $this->record->formatted_total
            ))
            ->success()
            ->duration(5000)
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send')
                ->label('Versenden')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->record->status === AggregateInvoice::STATUS_DRAFT)
                ->requiresConfirmation()
                ->modalHeading('Rechnung versenden?')
                ->modalDescription('Die Rechnung wird finalisiert und per E-Mail an den Partner versendet. Dieser Vorgang kann nicht rückgängig gemacht werden.')
                ->action(function () {
                    $service = app(StripeInvoicingService::class);
                    $service->finalizeAndSend($this->record);

                    Notification::make()
                        ->title('Rechnung versendet')
                        ->body('Die Rechnung wurde erfolgreich versendet.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\ViewAction::make(),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === AggregateInvoice::STATUS_DRAFT),
        ];
    }

    protected function getRedirectUrl(): string
    {
        // FIX: Zeige Ergebnis (View-Seite) statt Liste
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
