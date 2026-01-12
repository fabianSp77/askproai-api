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
                ->authorize('finalize')
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
                ->visible(fn () => $this->record->stripe_hosted_invoice_url && auth()->user()->can('viewStripeLink', $this->record)),

            Actions\Action::make('pdf')
                ->label('PDF Download')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => $this->record->stripe_pdf_url)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->stripe_pdf_url)
                ->authorize('downloadPdf'),

            Actions\Action::make('resend')
                ->label('Erneut senden')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->status === AggregateInvoice::STATUS_OPEN && $this->record->sent_at)
                ->authorize('resend')
                ->requiresConfirmation()
                ->modalHeading('Rechnung erneut senden')
                ->modalDescription(fn () => "Die Rechnung wird erneut an {$this->record->partnerCompany->getPartnerBillingEmail()} gesendet.")
                ->modalSubmitActionLabel('Erneut senden')
                ->action(function () {
                    try {
                        $service = app(StripeInvoicingService::class);
                        $service->resendInvoice($this->record);

                        Notification::make()
                            ->title('Rechnung erneut versendet')
                            ->body('Die Rechnung wurde über Stripe erneut an den Partner gesendet.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim Versenden')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('mark_paid')
                ->label('Als bezahlt markieren')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === AggregateInvoice::STATUS_OPEN)
                ->authorize('markAsPaid')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->markAsPaid();

                    Notification::make()
                        ->title('Rechnung als bezahlt markiert')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('void')
                ->label('Stornieren')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, [AggregateInvoice::STATUS_DRAFT, AggregateInvoice::STATUS_OPEN]))
                ->authorize('void')
                ->requiresConfirmation()
                ->modalHeading('Rechnung stornieren')
                ->modalDescription('Möchten Sie diese Rechnung wirklich stornieren? Diese Aktion kann nicht rückgängig gemacht werden.')
                ->action(function () {
                    try {
                        $service = app(StripeInvoicingService::class);
                        $service->voidStripeInvoice($this->record);

                        Notification::make()
                            ->title('Rechnung storniert')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim Stornieren')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === AggregateInvoice::STATUS_DRAFT),
        ];
    }
}
