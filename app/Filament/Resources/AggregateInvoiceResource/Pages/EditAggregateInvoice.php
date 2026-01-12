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
        return $this->getResource()::getUrl('index');
    }
}
