<?php

namespace App\Filament\Resources\AggregateInvoiceResource\Pages;

use App\Filament\Resources\AggregateInvoiceResource;
use App\Models\AggregateInvoice;
use Filament\Resources\Pages\CreateRecord;

class CreateAggregateInvoice extends CreateRecord
{
    protected static string $resource = AggregateInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] = AggregateInvoice::generateInvoiceNumber();
        $data['status'] = AggregateInvoice::STATUS_DRAFT;
        $data['currency'] = 'EUR';
        $data['tax_rate'] = 19.00;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
