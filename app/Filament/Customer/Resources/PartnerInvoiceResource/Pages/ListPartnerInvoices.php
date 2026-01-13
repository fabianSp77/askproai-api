<?php

namespace App\Filament\Customer\Resources\PartnerInvoiceResource\Pages;

use App\Filament\Customer\Resources\PartnerInvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListPartnerInvoices extends ListRecords
{
    protected static string $resource = PartnerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
