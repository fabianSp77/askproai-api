<?php

namespace App\Filament\Customer\Resources\PartnerInvoiceResource\Pages;

use App\Filament\Customer\Resources\PartnerInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPartnerInvoice extends ViewRecord
{
    protected static string $resource = PartnerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pdf')
                ->label('PDF herunterladen')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => $this->record->stripe_pdf_url)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->stripe_pdf_url)
                ->color('success'),

            Actions\Action::make('online')
                ->label('Online ansehen')
                ->icon('heroicon-o-globe-alt')
                ->url(fn () => $this->record->stripe_hosted_invoice_url)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->stripe_hosted_invoice_url)
                ->color('primary'),
        ];
    }
}
