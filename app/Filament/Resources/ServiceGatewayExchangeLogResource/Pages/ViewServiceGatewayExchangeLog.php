<?php

namespace App\Filament\Resources\ServiceGatewayExchangeLogResource\Pages;

use App\Filament\Resources\ServiceGatewayExchangeLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceGatewayExchangeLog extends ViewRecord
{
    protected static string $resource = ServiceGatewayExchangeLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Exchange Log: ' . substr($this->record->event_id, 0, 8) . '...';
    }

    public function getSubheading(): ?string
    {
        $direction = $this->record->direction === 'outbound' ? 'Ausgehend' : 'Eingehend';
        $status = $this->record->isSuccessful() ? 'Erfolgreich' : 'Fehlgeschlagen';

        return "{$direction} | {$this->record->http_method} | {$status}";
    }
}
