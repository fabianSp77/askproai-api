<?php

namespace App\Filament\Resources\AggregateInvoiceResource\Pages;

use App\Filament\Resources\AggregateInvoiceResource;
use App\Models\AggregateInvoice;
use App\Services\Billing\StripeInvoicingService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewAggregateInvoice extends ViewRecord
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
                ->action(function () {
                    $service = app(StripeInvoicingService::class);
                    $service->finalizeAndSend($this->record);

                    Notification::make()
                        ->title('Rechnung versendet')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('preview')
                ->label('Stripe Vorschau')
                ->icon('heroicon-o-eye')
                ->url(fn () => $this->record->stripe_hosted_invoice_url)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->stripe_hosted_invoice_url),

            Actions\Action::make('pdf')
                ->label('PDF Download')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => $this->record->stripe_pdf_url)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->stripe_pdf_url),

            Actions\Action::make('mark_paid')
                ->label('Als bezahlt markieren')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === AggregateInvoice::STATUS_OPEN)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->markAsPaid();

                    Notification::make()
                        ->title('Rechnung als bezahlt markiert')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === AggregateInvoice::STATUS_DRAFT),
        ];
    }
}
